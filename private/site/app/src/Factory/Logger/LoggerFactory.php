<?php

declare(strict_types=1);

/*
 * This file is part of the Slim 4 PHP application.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 license that is bundled
 * with this source code in the file COPYING.
 */

namespace App\Factory\Logger;

use App\Factory\Auth\AuthInterface;
use App\Factory\Db\DbInterface;
use App\Factory\Mailer\MailerInterface;
use App\Helper\HelperInterface;
use App\Model\Model;
use Monolog\Formatter\HtmlFormatter;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\DeduplicationHandler;
use Monolog\Handler\ErrorLogHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\GitProcessor;
use Monolog\Processor\IntrospectionProcessor;
use Monolog\Processor\MemoryPeakUsageProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Processor\ProcessIdProcessor;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\UidProcessor;
use Monolog\Processor\WebProcessor;
use MySQLHandler\MySQLHandler;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Sentry\Monolog\Handler;
use Sentry\State\Hub;

// https://designpatternsphp.readthedocs.io/en/latest/README.html
class LoggerFactory extends Model implements LoggerInterface
{
    private array $loggers = [];
    private array $channelMethodMap = []; // Cache for channel name normalization
    private array $extraFields = [
        'environment',
        'lang_code',
        'auth_type',
        'auth_id',
        'username',
        'uri',
        'method',
        'remote_addr',
        'hostname',
        'hostbyaddr',
        'error',
        'text',
    ];

    public function __call($name, $args)
    {
        // Create dynamic regex pattern from Monolog's level names
        $levelNames = implode('|', array_map('strtolower', Level::NAMES));

        // Handle dynamic channel methods: debugInternal(), infoSecurity(), etc.
        if (\Safe\preg_match("/^({$levelNames})([A-Z][a-zA-Z]*)$/", (string) $name, $matches)) {
            $levelName = $matches[1];
            $methodChannelName = strtolower($matches[2]);

            // Find the actual channel name from the normalized method name
            $channelName = $this->findChannelByMethodName($methodChannelName);

            if (!$channelName) {
                throw new \InvalidArgumentException("No channel found for method suffix: {$methodChannelName}");
            }

            if (\count($args) < 1) {
                throw new \InvalidArgumentException("Method {$name} requires at least a message parameter");
            }

            // Use Monolog's fromName method for level conversion
            try {
                $level = Level::fromName($levelName);
            } catch (\ValueError $e) {
                throw new \InvalidArgumentException("Invalid log level: {$levelName}", 0, $e);
            }

            return $this->logToChannel($channelName, $level, $args[0], $args[1] ?? []);
        }

        // Handle channel log methods: logInternal(), logSecurity(), etc.
        if (\Safe\preg_match('/^log([A-Z][a-zA-Z]*)$/', (string) $name, $matches)) {
            $methodChannelName = strtolower($matches[1]);

            // Find the actual channel name from the normalized method name
            $channelName = $this->findChannelByMethodName($methodChannelName);

            if (!$channelName) {
                throw new \InvalidArgumentException("No channel found for method suffix: {$methodChannelName}");
            }

            if (\count($args) < 2) {
                throw new \InvalidArgumentException("Method {$name} requires level and message parameters");
            }

            return $this->logToChannel($channelName, $args[0], $args[1], $args[2] ?? []);
        }

        // Fallback to default channel for standard PSR-3 methods
        return \call_user_func_array([$this->channel(), $name], $args);
    }

    public static function __callStatic($name, $args)
    {
        return \call_user_func_array([Logger::class, $name], $args);
    }

    public function create(): self
    {
        if (!empty($this->loggers)) {
            return $this; // Already initialized
        }

        $this->createChannels();
        $this->buildChannelMethodMap();

        return $this;
    }

    public function channel(?string $channelName = null): PsrLoggerInterface
    {
        // Get default channel if none specified
        if (null === $channelName) {
            $channelName = $this->getDefaultChannelName();
        }

        if (!isset($this->loggers[$channelName])) {
            throw new \RuntimeException("Logger channel '{$channelName}' not found. Available channels: ".implode(', ', $this->getChannels()));
        }

        return $this->loggers[$channelName];
    }

    public function getChannels(): array
    {
        return array_keys($this->loggers);
    }

    public function hasChannel(string $channelName): bool
    {
        return isset($this->loggers[$channelName]);
    }

    public function logToChannel(string $channelName, $level, string|\Stringable $message, array $context = []): void
    {
        $logger = $this->channel($channelName);
        $context = $this->processContext($logger, $context);
        $logger->log($level, $message, $context);
        $this->cleanupProcessor($logger, $context);
    }

    // PSR-3 methods (delegate to default channel)
    public function log($level, string|\Stringable $message, array $context = []): void
    {
        $this->logToChannel($this->getDefaultChannelName(), $level, $message, $context);
    }

    public function debug(string|\Stringable $message, array $context = []): void
    {
        $this->log(Level::Debug, $message, $context);
    }

    public function info(string|\Stringable $message, array $context = []): void
    {
        $this->log(Level::Info, $message, $context);
    }

    public function notice(string|\Stringable $message, array $context = []): void
    {
        $this->log(Level::Notice, $message, $context);
    }

    public function warning(string|\Stringable $message, array $context = []): void
    {
        $this->log(Level::Warning, $message, $context);
    }

    public function error(string|\Stringable $message, array $context = []): void
    {
        $this->log(Level::Error, $message, $context);
    }

    public function critical(string|\Stringable $message, array $context = []): void
    {
        $this->log(Level::Critical, $message, $context);
    }

    public function alert(string|\Stringable $message, array $context = []): void
    {
        $this->log(Level::Alert, $message, $context);
    }

    public function emergency(string|\Stringable $message, array $context = []): void
    {
        $this->log(Level::Emergency, $message, $context);
    }

    // called by other factories
    public function addContextProcessor(AuthInterface $auth, HelperInterface $helper, ?string $channelName = null): void
    {
        $logger = $this->channel($channelName);

        $logger->pushProcessor(new UidProcessor());
        $logger->pushProcessor($this->createContextProcessor($auth, $helper));
    }

    /**
     * Add a processor to an existing channel by type.
     */
    public function addProcessorByTypeToChannel(string $channelName, string $processorType): void
    {
        match ($processorType) {
            'psr_message' => $this->addPsrMessageProcessor($channelName),
            'introspection' => $this->addIntrospectionProcessor($channelName),
            'web' => $this->addWebProcessor($channelName),
            'memory_usage' => $this->addMemoryUsageProcessor($channelName),
            'memory_peak' => $this->addMemoryPeakProcessor($channelName),
            'process_id' => $this->addProcessIdProcessor($channelName),
            'git' => $this->addGitProcessor($channelName),
            default => throw new \InvalidArgumentException("Unknown processor type: {$processorType}"),
        };
    }

    /**
     * Get merged configuration for a processor in a specific channel.
     */
    public function getProcessorConfig(string $channelName, string $processorType): array
    {
        $globalConfig = $this->getConfigWithFallback("processors.{$processorType}", []);
        $channelConfig = $this->getConfigWithFallback("channels.{$channelName}.processors.{$processorType}", []);

        return array_merge($globalConfig, $channelConfig);
    }

    /**
     * Add a handler to an existing channel (called by other factories).
     *
     * @param mixed $handler
     */
    public function addHandlerToChannel(string $channelName, $handler): void
    {
        if (!$this->hasChannel($channelName)) {
            throw new \RuntimeException("Cannot add handler to non-existent channel '{$channelName}'");
        }

        $logger = $this->channel($channelName);

        $logger->pushHandler($handler);
    }

    /**
     * Add a handler to an existing channel.
     */
    public function addHandlerByTypeToChannel(string $channelName, string $handlerType): void
    {
        match ($handlerType) {
            'file' => $this->addFileHandler($channelName),
            default => throw new \InvalidArgumentException("Unknown handler type: {$handlerType}"),
        };
    }

    /**
     * Get merged configuration for a handler in a specific channel.
     */
    public function getHandlerConfig(string $channelName, string $handlerType): array
    {
        $globalConfig = $this->getConfigWithFallback("handlers.{$handlerType}", []);
        $channelConfig = $this->getConfigWithFallback("channels.{$channelName}.handlers.{$handlerType}", []);

        return array_merge($globalConfig, $channelConfig);
    }

    /**
     * Get configuration value with environment fallback using existing ConfigManager.
     *
     * @param null|mixed $default
     */
    public function getConfigWithFallback(string $suffix = '', $default = null, ?array $prefixes = null)
    {
        $env = $this->container->get('env');

        $prefixes ??= [
            "logger.{$env}",
            'logger',
        ];

        return $this->config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }

    // could be called by other factories
    public function addFileHandler(?string $channelName = null): void
    {
        $logger = $this->channel($channelName);
        $channelName ??= $logger->getName();
        $config = $this->getHandlerConfig($channelName, 'file');

        try {
            if ($this->config->get('debug.enabled')) {
                $handler = new ErrorLogHandler(
                    ErrorLogHandler::OPERATING_SYSTEM,
                    $config['level'] ?? Level::Debug
                );
            } else {
                // Replace placeholder in path
                $path = $config['path'] ?? $this->getDefaultLogPath();
                $path = \sprintf($path, $channelName);

                $handler = new RotatingFileHandler(
                    $path,
                    $config['max_files'] ?? 0,
                    $config['level'] ?? Level::Debug,
                    filePermission: $config['permissions'] ?? null
                );
            }

            $handler->setFormatter(new LineFormatter(null, null, false, true));
            $logger->pushHandler($handler);
        } catch (\Exception $e) {
            // Silently fail if handler can't be set up
            \Safe\error_log('Failed to setup file handler: '.$e->getMessage());
        }
    }

    // called by other factories
    public function addDatabaseHandler(DbInterface $db, ?string $channelName = null): void
    {
        $logger = $this->channel($channelName);
        $channelName ??= $logger->getName();
        $config = $this->getHandlerConfig($channelName, 'database');

        try {
            $handler = new MySQLHandler(
                $db->getPDO(),
                $this->config->get('db.1.prefix').'log',
                $this->extraFields,
                level: $config['level'] ?? Level::Debug,
            );

            $logger->pushHandler($handler);
        } catch (\Exception $e) {
            // Silently fail if handler can't be set up
            \Safe\error_log('Failed to setup database handler: '.$e->getMessage());
        }
    }

    // called by other factories
    public function addSentryHandler(AuthInterface $auth, HelperInterface $helper, ?string $channelName = null): void
    {
        $env = $this->container->get('env');

        $prefixes = [
            "service.{$env}.sentry.php",
            "service.{$env}.sentry",
            'service.sentry.php',
            'service.sentry',
        ];

        $dsn = $this->getConfigWithFallback('dsn', prefixes: $prefixes);

        if (empty($dsn)) {
            return;
        }

        $logger = $this->channel($channelName);
        $channelName ??= $logger->getName();
        $config = $this->getHandlerConfig($channelName, 'sentry');

        try {
            $sentryConfig = array_filter([
                'dsn' => $dsn,
                'release' => $this->getConfigWithFallback('release', prefixes: $prefixes) ?? $helper->Nette()->Strings()->webalize((string) $this->config->get('app.name')).'@'.version(),
                'environment' => $this->getConfigWithFallback('environment', prefixes: $prefixes),
                'error_types' => $this->getConfigWithFallback('errorTypes', prefixes: $prefixes),
                'sample_rate' => $this->getConfigWithFallback('sampleRate', prefixes: $prefixes),
                'max_breadcrumbs' => $this->getConfigWithFallback('maxBreadcrumbs', prefixes: $prefixes),
                'attach_stacktrace' => $this->getConfigWithFallback('attachStacktrace', prefixes: $prefixes),
                'send_default_pii' => $this->getConfigWithFallback('sendDefaultPii', prefixes: $prefixes),
                'server_name' => $this->getConfigWithFallback('serverName', prefixes: $prefixes),
                'in_app_include' => $this->getConfigWithFallback('inAppInclude', prefixes: $prefixes),
                'in_app_exclude' => $this->getConfigWithFallback('inAppExclude', prefixes: $prefixes),
                'max_request_body_size' => $this->getConfigWithFallback('maxRequestBodySize', prefixes: $prefixes),
                'max_value_length' => $this->getConfigWithFallback('maxValueLength', prefixes: $prefixes),
                'before_send' => $this->getConfigWithFallback('beforeSend', prefixes: $prefixes),
                'before_breadcrumb' => $this->getConfigWithFallback('beforeBreadcrumb', prefixes: $prefixes),
                'transport' => $this->getConfigWithFallback('transport', prefixes: $prefixes),
                'http_proxy' => $this->getConfigWithFallback('httpProxy', prefixes: $prefixes),
                'traces_sample_rate' => $this->getConfigWithFallback('tracesSampleRate', prefixes: $prefixes),
                'traces_sampler' => $this->getConfigWithFallback('tracesSampler', prefixes: $prefixes),
                'ignore_exceptions' => $this->getConfigWithFallback('ignoreExceptions', prefixes: $prefixes),
            ], fn ($v) => isset($v));

            $handler = new Handler(
                new Hub(\Sentry\init($sentryConfig)),
                $config['level'] ?? Level::Debug,
            );

            $logger->pushHandler($handler);
        } catch (\Exception $e) {
            // Silently fail if handler can't be set up
            \Safe\error_log('Failed to setup Sentry handler: '.$e->getMessage());
        }
    }

    // called by other factories
    public function addEmailHandler(MailerInterface $mailer, ?string $channelName = null): void
    {
        $env = $this->container->get('env');
        $logger = $this->channel($channelName);
        $channelName ??= $logger->getName();
        $config = $this->getHandlerConfig($channelName, 'email');

        try {
            $emailConfig = [
                'to' => $this->config->get('debug.emailsTo'),
                'to_string' => implode(', ', (array) $this->config->get('debug.emailsTo')),
                'subject' => \sprintf(
                    __('Error reporting from %1$s %2$s - %3$s'),
                    $_ENV['HTTP_HOST'] ?? settingOrConfig(['brand.shortName', 'brand.name', 'company.shortName', 'company.name', 'app.name']),
                    version(),
                    $env
                ),
                'from' => $this->config->get('mail.sender', ['noreply@example.com' => ''])[0],
            ];

            $handler = new \App\Factory\Logger\Handler\FailoverEmailHandler(
                $mailer,
                $emailConfig,
                $config['level'] ?? Level::Error
            );

            $handler->setFormatter(new HtmlFormatter());

            if (!$this->config->get('debug.enabled')) {
                // Replace placeholder in path
                $path = $config['dedup_path'] ?? $this->getDefaultLogPath('dedup-');
                $path = \sprintf($path, $channelName);

                $handler = new DeduplicationHandler(
                    $handler,
                    $path,
                    $config['dedup_level'] ?? Level::Error,
                    $config['dedup_time'] ?? 60,
                );
            }

            $logger->pushHandler($handler);
        } catch (\Exception $e) {
            // Silently fail if handler can't be set up
            \Safe\error_log('Failed to setup email handler: '.$e->getMessage());
        }
    }

    public function getLevelColor(int $level): string
    {
        $levelName = self::toMonologLevel($level)->toPsrLogLevel();
        $env = $this->container->get('env');

        $prefixes = [
            "theme.{$env}.logger.levels.{$levelName}",
            "theme.logger.levels.{$levelName}",
            "logger.{$env}.levels.{$levelName}",
            "logger.levels.{$levelName}",
        ];

        $color = $this->getConfigWithFallback('color', 'ffffff', $prefixes);

        return '#'.ltrim((string) $color, '#');
    }

    /**
     * Normalize channel name to be usable as PHP method name part.
     * Converts 'foo-bar' to 'foobar', 'my_channel' to 'mychannel', etc.
     */
    private function normalizeChannelNameForMethod(string $channelName): string
    {
        // Remove non-alphanumeric characters and convert to lowercase
        return strtolower(\Safe\preg_replace('/[^a-zA-Z0-9]/', '', $channelName));
    }

    /**
     * Validate channel name during creation.
     * Channel names should be valid identifiers that can be converted to method names.
     */
    private function validateChannelName(string $channelName): void
    {
        if (empty($channelName)) {
            throw new \InvalidArgumentException('Channel name cannot be empty');
        }

        // Only check for conflicts with PSR-3 log level names
        // These cause real ambiguity in the __call() method patterns
        $logLevelNames = array_map('strtolower', Level::NAMES);

        $normalizedName = $this->normalizeChannelNameForMethod($channelName);

        if (\in_array($normalizedName, $logLevelNames, true)) {
            throw new \InvalidArgumentException(
                "Channel name '{$channelName}' conflicts with PSR-3 log level '{$normalizedName}'. "
                ."This would make method calls like debug{$channelName}() ambiguous."
            );
        }
    }

    /**
     * Build mapping between normalized method names and actual channel names.
     */
    private function buildChannelMethodMap(): void
    {
        $this->channelMethodMap = [];

        foreach ($this->getChannels() as $channelName) {
            $methodName = $this->normalizeChannelNameForMethod($channelName);

            // Check for conflicts
            if (isset($this->channelMethodMap[$methodName])) {
                throw new \RuntimeException(
                    "Channel name conflict: both '{$this->channelMethodMap[$methodName]}' and '{$channelName}' "
                    ."normalize to the same method name '{$methodName}'"
                );
            }

            $this->channelMethodMap[$methodName] = $channelName;
        }
    }

    /**
     * Find channel name by its normalized method name.
     */
    private function findChannelByMethodName(string $methodName): ?string
    {
        return $this->channelMethodMap[$methodName] ?? null;
    }

    private function createChannels(): void
    {
        $channels = $this->getConfigWithFallback('channels', []);

        if (empty($channels)) {
            throw new \RuntimeException('No logger channels configured');
        }

        foreach ($channels as $channelName => $channelConfig) {
            $this->validateChannelName($channelName);
            $this->createChannel($channelName, $channelConfig);
        }
    }

    private function createChannel(string $channelName, array $channelConfig): void
    {
        $logger = new Logger($channelName);

        $this->loggers[$channelName] = $logger;

        // Add processors - check if channel has specific processors, otherwise use defaults
        $channelProcessors = $this->getConfigWithFallback("channels.{$channelName}.processors");
        if (!empty($channelProcessors)) {
            // Channel has specific processors configuration
            foreach (array_keys($channelProcessors) as $processorType) {
                $this->addProcessorByTypeToChannel($channelName, $processorType);
            }
        } else {
            // Use default processors
            $defaultProcessors = $this->getConfigWithFallback('default_processors', ['psr_message']);
            foreach ($defaultProcessors as $processorType) {
                $this->addProcessorByTypeToChannel($channelName, $processorType);
            }
        }

        // Add only default handlers (file only, to avoid circular dependencies)
        $defaultHandlers = $this->getConfigWithFallback('default_handlers', ['file']);
        foreach ($defaultHandlers as $handlerType) {
            $this->addHandlerByTypeToChannel($channelName, $handlerType);
        }
    }

    private function getDefaultChannelName(): string
    {
        $channels = array_keys($this->getConfigWithFallback('channels', []));

        if (empty($channels)) {
            throw new \RuntimeException('No logger channels configured');
        }

        return $channels[0]; // First channel is the default
    }

    private function processContext(Logger $logger, array $context): array
    {
        $extraData = [];

        if (!empty($extraFields = array_intersect(array_keys($context), $this->extraFields))) {
            foreach ($extraFields as $extraField) {
                $extraData[$extraField] = $context[$extraField];
                unset($context[$extraField]);
            }
        }

        if (!empty($extraData)) {
            $logger->pushProcessor(function (LogRecord $record) use ($extraData) {
                foreach ($extraData as $key => $value) {
                    $record->extra[$key] = $value;
                }

                return $record;
            });
        }

        return $context;
    }

    private function cleanupProcessor(Logger $logger, array $context): void
    {
        if (!empty(array_intersect(array_keys($context), $this->extraFields))) {
            $logger->popProcessor();
        }
    }

    private function createContextProcessor(AuthInterface $auth, HelperInterface $helper): callable
    {
        return function (LogRecord $record) use ($auth, $helper) {
            $record->extra['environment'] = $this->container->get('env');
            $record->extra['lang_code'] = $this->container->has('lang') ? $this->container->get('lang')->code : '';
            $record->extra['auth_type'] = $auth->hasIdentity() ? $auth->getIdentity()['_role_type'] : '';
            $record->extra['auth_id'] = $auth->hasIdentity() ? $auth->getIdentity()['id'] : '';
            $record->extra['username'] = $auth->hasIdentity() ? $auth->getIdentity()['_username'] : '';
            $record->extra['uri'] = $helper->Url()->getPathUrl() ?? '';
            $record->extra['method'] = $this->container->has('request') ? $this->container->get('request')->getMethod() : '';
            $record->extra['remote_addr'] = $this->container->has('request') ? $this->container->get('request')->getAttribute('client-ip') : '';
            $record->extra['hostname'] = @\Safe\gethostname() ?: ''; // php_uname('n')
            $record->extra['hostbyaddr'] = $this->container->has('request') && !empty($this->container->get('request')->getAttribute('client-ip'))
                ? @gethostbyaddr((string) $this->container->get('request')->getAttribute('client-ip')) : '';

            // Only add superglobals if WebProcessor is not enabled for this logger
            // Get the logger name from the record
            $loggerName = $record->channel ?? $this->getDefaultChannelName();
            if (!$this->isWebProcessorEnabled($loggerName)) {
                $this->addSuperglobals($record);
            }

            // Ensure all extra fields exist
            foreach ($this->extraFields as $extraField) {
                if (!isset($record->extra[$extraField])) {
                    $record->extra[$extraField] = '';
                }
            }

            return $record;
        };
    }

    /**
     * Check if WebProcessor is enabled for a channel.
     */
    private function isWebProcessorEnabled(string $channelName): bool
    {
        $defaultProcessors = $this->getConfigWithFallback('default_processors', []);
        $channelProcessors = $this->getConfigWithFallback("channels.{$channelName}.processors", []);

        // If channel has specific processors config, use that; otherwise use default
        $activeProcessors = !empty($channelProcessors) ? array_keys($channelProcessors) : $defaultProcessors;

        return \in_array('web', $activeProcessors, true);
    }

    private function addPsrMessageProcessor(?string $channelName = null): void
    {
        $logger = $this->channel($channelName);
        $logger->pushProcessor(new PsrLogMessageProcessor());
    }

    private function addIntrospectionProcessor(?string $channelName = null): void
    {
        $logger = $this->channel($channelName);
        $channelName ??= $logger->getName();
        $config = $this->getProcessorConfig($channelName, 'introspection');

        $logger->pushProcessor(new IntrospectionProcessor(
            Level::fromValue($config['level'] ?? Level::Debug->value),
            $config['skip_classes'] ?? []
        ));
    }

    private function addWebProcessor(?string $channelName = null): void
    {
        $logger = $this->channel($channelName);
        $channelName ??= $logger->getName();
        $config = $this->getProcessorConfig($channelName, 'web');

        $logger->pushProcessor(new WebProcessor(
            extraFields: $config['extra_fields'] ?? null
        ));
    }

    private function addMemoryUsageProcessor(?string $channelName = null): void
    {
        $logger = $this->channel($channelName);
        $channelName ??= $logger->getName();
        $config = $this->getProcessorConfig($channelName, 'memory_usage');

        $logger->pushProcessor(new MemoryUsageProcessor(
            $config['real_usage'] ?? true,
            $config['use_formatting'] ?? true
        ));
    }

    private function addMemoryPeakProcessor(?string $channelName = null): void
    {
        $logger = $this->channel($channelName);
        $channelName ??= $logger->getName();
        $config = $this->getProcessorConfig($channelName, 'memory_peak');

        $logger->pushProcessor(new MemoryPeakUsageProcessor(
            $config['real_usage'] ?? true,
            $config['use_formatting'] ?? true
        ));
    }

    private function addProcessIdProcessor(?string $channelName = null): void
    {
        $logger = $this->channel($channelName);
        $logger->pushProcessor(new ProcessIdProcessor());
    }

    private function addGitProcessor(?string $channelName = null): void
    {
        $logger = $this->channel($channelName);
        $channelName ??= $logger->getName();
        $config = $this->getProcessorConfig($channelName, 'git');

        try {
            $logger->pushProcessor(new GitProcessor(
                $config['level'] ?? Level::Debug,
                $config['path'] ?? null
            ));
        } catch (\Exception $e) {
            // Git processor can fail if not in a git repository
            if ($this->config->get('debug.enabled')) {
                \Safe\error_log('Git processor failed: '.$e->getMessage());
            }
        }
    }

    /**
     * Add superglobals safely.
     */
    private function addSuperglobals(LogRecord $record): void
    {
        $superglobals = ['_REQUEST', '_POST', '_FILES', '_SESSION'];

        foreach ($superglobals as $global) {
            if (isset($GLOBALS[$global]) && (\is_array($GLOBALS[$global]) || $GLOBALS[$global] instanceof \ArrayAccess)) {
                $record->extra[$global] = $GLOBALS[$global];
            }
        }

        if (isset($_SERVER) && (\is_array($_SERVER) || $_SERVER instanceof \ArrayAccess)) {
            $record->extra['_SERVER'] = str_contains(\Safe\ini_get('variables_order'), 'E') ? $_SERVER : array_diff_key($_SERVER, $_ENV);
        }
    }

    private function getDefaultLogPath(?string $prefix = ''): string
    {
        // same path used by DeduplicationHandler
        return sys_get_temp_dir()."/{$prefix}%s.log";
        // alternative method
        // https://stackoverflow.com/a/44426471/3929620
        // return pathinfo(\Safe\ini_get('error_log'), PATHINFO_DIRNAME)."/{$prefix}%s.log";
    }
}
