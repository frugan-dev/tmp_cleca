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

namespace App\Factory\Mailer;

use App\Factory\Logger\LoggerInterface;
use App\Factory\Mailer\OAuth2\TokenProvider\TokenProviderFactory;
use App\Factory\Mailer\Transport\TrackedTransport;
use App\Factory\Mailer\Transport\TransportFactoryRegistry;
use App\Helper\HelperInterface;
use App\Model\Model;
use Laminas\Stdlib\ArrayUtils;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mailer\Mailer;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mailer\Transport\FailoverTransport;
use Symfony\Component\Mailer\Transport\RoundRobinTransport;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MailerFactory extends Model implements MailerInterface
{
    protected ?Mailer $instance = null;
    protected ?Email $message = null;

    // Track transport info for logging
    protected array $transportInfo = [
        'type' => null,
        'provider' => null,
        'host' => null,
        'port' => null,
        'fallback_used' => false,
        'attempts' => [],
        'configured_transports' => [],
        'failed_transports' => [],
    ];

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger,
        protected HelperInterface $helper,
        protected TransportFactoryRegistry $transportRegistry
    ) {}

    public function __call($name, $args)
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. Call create() first.', $this->getShortName()));
        }

        return \call_user_func_array([$this->instance, $name], $args);
    }

    public function getInstance(): ?\Symfony\Component\Mailer\MailerInterface
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. It should be initialized via container factory.', $this->getShortName()));
        }

        return $this->instance;
    }

    public function getTransportInfo(): array
    {
        return $this->transportInfo;
    }

    public function create(): self
    {
        if (null !== $this->instance) {
            return $this; // Already initialized
        }

        $transports = $this->buildTransports();

        if (empty($transports)) {
            // https://symfony.com/doc/current/mailer.html#disabling-delivery
            $transports['null'] = 'null://null';
            $this->transportInfo['type'] = 'null';
        }

        $technique = $this->config->get('mail.transports.technique', 'failover');
        $dsnString = $technique.'('.implode(' ', $transports).')';

        // Create transport with custom registry
        $transport = $this->createTransportWithCustomFactories($dsnString);
        $this->instance = new Mailer($transport);

        $this->transportInfo['technique'] = $technique;
        $this->transportInfo['transport_count'] = \count($transports);

        $this->logger->addEmailHandler($this);

        return $this;
    }

    public function prepare(array $params = []): void
    {
        $params = ArrayUtils::merge(
            [
                'date' => null,

                // Specifies where bounces should go (Swift Mailer reads this for other uses)
                'returnPath' => null,

                // Specifies the address of the person who physically sent the message (higher precedence than From:)
                'sender' => null,

                // Specifies the addresses of the intended recipients
                'to' => null,

                // Specifies the subject line that is displayed in the recipients' mail client
                'subject' => null,

                // Specifies the address of the person who the message is from. This can be multiple addresses if multiple people wrote the message.
                // If you set multiple From: addresses then you absolutely must set a Sender: address to indicate who physically sent the message.
                'from' => null,

                // Specifies the address where replies are sent to
                'replyTo' => null,

                // Specifies the addresses of recipients who will be copied in on the message
                'cc' => null,

                // Specifies the addresses of recipients who the message will be blind-copied to. Other recipients will not be aware of these copies.
                'bcc' => null,

                'html' => '',

                'text' => '',

                'attachments' => null,

                'embedded' => null,
            ],
            $params
        );

        if (!isset($params['returnPath'])) {
            $params['returnPath'] = $this->config->get('mail.returnPath');
        }

        if (!isset($params['sender'])) {
            $params['sender'] = $this->config->get('mail.sender');
        }

        if (!isset($params['to'])) {
            $params['to'] = $this->config->get('debug.emailsTo');
        }

        if (\Safe\preg_match('/'.implode('|', array_map('preg_quote', $this->config->get('mail.dmarcRejectSender', []), array_fill(0, is_countable($this->config->get('mail.dmarcRejectSender', [])) ? \count($this->config->get('mail.dmarcRejectSender', [])) : 0, '/'))).'/i', \is_array($params['sender']) ? array_key_first($params['sender']) : (string) $params['sender'])) {
            $params['sender'] = $this->config->get('mail.sender');
        }

        if (!isset($params['from'])) {
            $params['from'] = $this->config->get('mail.from', $params['sender']);
        }

        if (\is_array($params['from'])) {
            if (\Safe\preg_grep('/'.implode('|', array_map('preg_quote', $this->config->get('mail.dmarcRejectSender', []), array_fill(0, is_countable($this->config->get('mail.dmarcRejectSender', [])) ? \count($this->config->get('mail.dmarcRejectSender', [])) : 0, '/'))).'/i', array_keys($params['from']))) {
                $params['from'] = $this->config->get('mail.sender');
            }
        } elseif (\Safe\preg_match('/'.implode('|', array_map('preg_quote', $this->config->get('mail.dmarcRejectSender', []), array_fill(0, is_countable($this->config->get('mail.dmarcRejectSender', [])) ? \count($this->config->get('mail.dmarcRejectSender', [])) : 0, '/'))).'/i', (string) $params['from'])) {
            $params['from'] = $this->config->get('mail.sender');
        }

        $argsFrom = $params['from'];
        if (\is_array($params['from'])) {
            $argsFrom = [];
            foreach ($params['from'] as $key => $val) {
                $argsFrom[] = \is_int($key) ? $val : new Address($key, $val);
            }
        }

        $argsSender = $params['sender'];
        if (\is_array($params['sender'])) {
            $argsSender = [];
            foreach ($params['sender'] as $key => $val) {
                $argsSender[] = \is_int($key) ? $val : new Address($key, $val);
            }
        }

        // https://github.com/symfony/symfony/issues/41322
        // https://stackoverflow.com/a/14253556/3929620
        // https://stackoverflow.com/a/25873119/3929620
        $this->message = new Email()
            ->returnPath($params['returnPath']) // overwritten by the Sender
            ->sender(...(array) $argsSender)
            ->from(...(array) $argsFrom)
            ->subject($params['subject'])
            ->html($params['html'])
            ->text($params['text'])
        ;

        if (isset($params['date'])) {
            if (!$params['date'] instanceof \DateTimeInterface) {
                throw new \Exception(\sprintf('"date" param must be an instance of "%s" (got "%s").', \DateTimeInterface::class, get_debug_type($params['date'])));
            }

            $this->message->date($params['date']);
        }

        if (isset($params['replyTo'])) {
            $args = $params['replyTo'];
            if (\is_array($params['replyTo'])) {
                $args = [];
                foreach ($params['replyTo'] as $key => $val) {
                    $args[] = \is_int($key) ? $val : new Address($key, $val);
                }
            }
            $this->message->replyTo(...(array) $args);
        }

        if (isset($params['cc'])) {
            $args = $params['cc'];
            if (\is_array($params['cc'])) {
                $args = [];
                foreach ($params['cc'] as $key => $val) {
                    $args[] = \is_int($key) ? $val : new Address($key, $val);
                }
            }
            $this->message->cc(...(array) $args);
        }

        if (isset($params['bcc'])) {
            $args = $params['bcc'];
            if (\is_array($params['bcc'])) {
                $args = [];
                foreach ($params['bcc'] as $key => $val) {
                    $args[] = \is_int($key) ? $val : new Address($key, $val);
                }
            }
            $this->message->bcc(...(array) $args);
        }

        if (isset($params['attachments'])) {
            if ((is_countable($params['attachments']) ? \count($params['attachments']) : 0) > 0) {
                foreach ($params['attachments'] as $itemKey => $itemVal) {
                    $args = \is_int($itemKey) ? $itemVal : [$itemKey, $itemVal];

                    $this->message->attachFromPath(...(array) $args);
                }
            }
        }

        if (isset($params['embedded'])) {
            if ((is_countable($params['embedded']) ? \count($params['embedded']) : 0) > 0) {
                foreach ($params['embedded'] as $itemKey => $itemVal) {
                    $args = \is_int($itemKey) ? $itemVal : [$itemKey, $itemVal];

                    $this->message->embedFromPath(...(array) $args);
                }
            }
        }

        $args = $params['to'];
        if (\is_array($params['to'])) {
            $args = [];
            foreach ($params['to'] as $key => $val) {
                $args[] = \is_int($key) ? $val : new Address($key, $val);
            }
        }

        $this->message->to(...(array) $args);
    }

    public function send(): bool
    {
        try {
            // Clear previous tracking info
            TrackedTransport::clearAllTrackingInfo();

            $this->instance->send($this->message);

            // Get actual transport info from tracker
            $actualTransportInfo = TrackedTransport::getLastUsedTransportInfo();
            $failedTransports = TrackedTransport::getFailedTransports();
            $transportSummary = TrackedTransport::getTransportSummary();

            if ($actualTransportInfo) {
                // Update our transport info with actual usage data
                $this->transportInfo['type'] = $actualTransportInfo['type'];
                $this->transportInfo['provider'] = $actualTransportInfo['provider'];
                $this->transportInfo['provider_type'] = $actualTransportInfo['provider_type'] ?? null;

                if (isset($actualTransportInfo['metadata']['host'])) {
                    $this->transportInfo['host'] = $actualTransportInfo['metadata']['host'];
                }
                if (isset($actualTransportInfo['metadata']['port'])) {
                    $this->transportInfo['port'] = $actualTransportInfo['metadata']['port'];
                }

                $this->transportInfo['actual_class'] = $actualTransportInfo['class'];
            }

            // Update failed transports with real failures
            if (!empty($failedTransports)) {
                $this->transportInfo['runtime_failed_transports'] = [];
                $this->transportInfo['runtime_failed_providers'] = [];

                foreach ($failedTransports as $failure) {
                    $failureKey = $failure['provider'] ?? $failure['type'];
                    $this->transportInfo['runtime_failed_transports'][] = $failureKey.': '.$failure['error'];

                    if ($failure['provider']) {
                        $this->transportInfo['runtime_failed_providers'][] = $failure['provider'];
                    }
                }

                // Update failed_transports based on runtime failures
                $this->updateFailedTransportsFromRuntime($failedTransports);
            }

            // Transport summary information
            $this->transportInfo = array_merge($this->transportInfo, [
                'total_attempts' => $transportSummary['total_attempts'],
                'total_failures' => $transportSummary['total_failures'],
                'success_count' => $transportSummary['success_count'],
                'failed_providers' => $transportSummary['failed_providers'],
                'successful_provider' => $transportSummary['successful_provider'],
            ]);

            // Technical debug info (includes sensitive data like DSNs and all technical details)
            $debugInfo = array_merge(
                ['message_subject' => $this->message->getSubject()],
                $this->transportInfo,
                $actualTransportInfo ?? []
            );

            if (!empty($failedTransports)) {
                $debugInfo['detailed_failures'] = $failedTransports;
            }

            $this->logger->debugInternal('Email sent successfully with transport details', $debugInfo);

            // User-friendly log (no sensitive data, minimal technical info)
            if (!empty($this->config->get('logger.mailer.level'))) {
                $lines = [];
                $lines[] = \sprintf('%1$s: %2$s', 'Subject', $this->message->getSubject());
                $lines[] = \sprintf('%1$s: %2$s', 'Date', $this->message->getDate()?->format('c'));
                $lines[] = \sprintf('%1$s: %2$s', 'Return-Path', $this->message->getReturnPath()->getEncodedAddress());
                $lines[] = \sprintf('%1$s: %2$s', 'Sender', $this->message->getSender()->getEncodedAddress());
                $lines[] = \sprintf('%1$s: %2$s', 'From', implode(', ', $this->stringifyAddresses($this->message->getFrom())));
                $lines[] = \sprintf('%1$s: %2$s', 'Reply-To', implode(', ', $this->stringifyAddresses($this->message->getReplyTo())));
                $lines[] = \sprintf('%1$s: %2$s', 'To', implode(', ', $this->stringifyAddresses($this->message->getTo())));
                $lines[] = \sprintf('%1$s: %2$s', 'Cc', implode(', ', $this->stringifyAddresses($this->message->getCc())));
                $lines[] = \sprintf('%1$s: %2$s', 'Bcc', implode(', ', $this->stringifyAddresses($this->message->getBcc())));
                $lines[] = \sprintf('%1$s: %2$s', 'X-Priority', $this->message->getPriority());
                $lines[] = \sprintf('%1$s: %2$s', 'Attachments', implode(', ', $this->stringifyAttachments($this->message->getAttachments())));
                $lines[] = \sprintf('%1$s: %2$s', 'Text', $this->message->getTextBody());

                // Basic transport info (user-friendly, no technical details)
                $lines[] = \sprintf('%1$s: %2$s', 'Transport Type', $this->transportInfo['type'] ?? 'unknown');
                $lines[] = \sprintf('%1$s: %2$s', 'Transport Technique', $this->transportInfo['technique'] ?? 'unknown');

                // Show failed transports (if any)
                if (!empty($this->transportInfo['failed_transports'])) {
                    $lines[] = \sprintf('%1$s: %2$s', 'Failed Transports', implode(', ', $this->transportInfo['failed_transports']));
                }

                // Show failed providers (if any)
                if (!empty($this->transportInfo['failed_providers'])) {
                    $lines[] = \sprintf('%1$s: %2$s', 'Failed Providers', implode(', ', $this->transportInfo['failed_providers']));
                }

                // Show successful provider (if any)
                if (!empty($this->transportInfo['provider'])) {
                    $providerLabel = $this->getProviderLabel($this->transportInfo['provider_type']);
                    $lines[] = \sprintf('%1$s: %2$s', $providerLabel, $this->transportInfo['provider']);
                }

                $logLevel = $this->config->get('logger.mailer.level');
                $message = \sprintf(
                    __('Sent email -> %1$s', null, $this->config->get('logger.locale')),
                    $this->helper->Nette()->Strings()->truncate($this->message->getSubject(), 300)
                );
                $logged = false;

                if (method_exists($this->logger, $this->config->get('logger.mailer.level'))) {
                    try {
                        $this->logger->{$logLevel}($message, [
                            'text' => implode(PHP_EOL, $lines),
                        ]);
                        $logged = true;
                    } catch (\Throwable) {
                        // Custom logger failed, will fallback to error_log
                    }
                }

                // Fallback to error_log if custom logging failed or level not configured
                if (!$logged) {
                    \Safe\error_log("MailerFactory: {$message}");
                }
            }

            return true;
        } catch (\Exception $e) {
            // Capture complete failures
            $failedTransports = TrackedTransport::getFailedTransports();
            $transportSummary = TrackedTransport::getTransportSummary();

            // Update failed_transports for complete failures
            if (!empty($failedTransports)) {
                $this->updateFailedTransportsFromRuntime($failedTransports);
            }

            $errorInfo = [
                'error' => $e->getMessage(),
                'transport_info' => $this->transportInfo,
            ];

            if (!empty($failedTransports)) {
                $errorInfo['detailed_failures'] = $failedTransports;
                $errorInfo['failed_providers'] = $transportSummary['failed_providers'];
                $errorInfo['total_attempts'] = $transportSummary['total_attempts'];
            }

            $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, $errorInfo);
        }

        return false;
    }

    public function getMessage(): ?Email
    {
        return $this->message;
    }

    protected function stringifyAddresses(array $addresses): array
    {
        return array_map(fn (Address $a) => $a->getEncodedAddress(), $addresses);
    }

    protected function stringifyAttachments(array $attachments): array
    {
        return array_map(fn (DataPart $a) => $a->getFilename(), $attachments);
    }

    /**
     * Update failed_transports based on runtime failures.
     *
     * This method consolidates runtime failures into the failed_transports array,
     * grouping OAuth2 provider failures under the 'oauth2' transport type.
     */
    private function updateFailedTransportsFromRuntime(array $failedTransports): void
    {
        $failedTransportTypes = [];

        foreach ($failedTransports as $failure) {
            $transportType = $failure['type'];

            // Group OAuth2 providers under 'oauth2' transport type
            if ('oauth2' === $transportType) {
                $failedTransportTypes['oauth2'] = 'oauth2: OAuth2 providers failed';
            } else {
                // For non-OAuth2 transports, include the specific error
                $failedTransportTypes[$transportType] = $transportType.': '.$failure['error'];
            }
        }

        // Merge with existing failed_transports (from building phase)
        $this->transportInfo['failed_transports'] = array_merge(
            $this->transportInfo['failed_transports'],
            array_values($failedTransportTypes)
        );

        // Remove duplicates
        $this->transportInfo['failed_transports'] = array_unique($this->transportInfo['failed_transports']);
    }

    /**
     * Build all configured transports with enhanced OAuth2 provider fallback.
     */
    private function buildTransports(): array
    {
        $transports = [];
        $transportTypes = (array) $this->config->get('mail.transports');

        $this->logger->debugInternal('Building mail transports', [
            'requested_transports' => $transportTypes,
        ]);

        foreach ($transportTypes as $key => $transportType) {
            try {
                $this->logger->debugInternal("Attempting to build transport: {$transportType}");

                if ('oauth2' === $transportType) {
                    // Build OAuth2 transports for all providers
                    $oauth2Transports = $this->buildOAuth2Transports();
                    $transports = array_merge($transports, $oauth2Transports);
                } else {
                    $dsn = $this->buildTransportDsn($transportType);
                    if ($dsn) {
                        $transports[$transportType] = $dsn;
                        $this->transportInfo['configured_transports'][] = $transportType;
                        $this->transportInfo['attempts'][] = "{$transportType}: configured";
                        $this->logger->debugInternal("Transport configured: {$transportType}");
                    } else {
                        $this->transportInfo['failed_transports'][] = $transportType.': no DSN';
                        $this->transportInfo['attempts'][] = "{$transportType}: no DSN generated";
                        $this->logger->warning("Transport failed - no DSN: {$transportType}");
                    }
                }
            } catch (\Exception $e) {
                $this->logger->error("Failed to build transport: {$transportType}", [
                    'error' => $e->getMessage(),
                ]);

                $this->transportInfo['failed_transports'][] = $transportType.': '.$e->getMessage();
                $this->transportInfo['attempts'][] = "{$transportType}: failed ({$e->getMessage()})";
            }
        }

        $this->logger->debugInternal('Transport building completed', [
            'total_requested' => \count($transportTypes),
            'total_configured' => \count($transports),
            'configured_transports' => $this->transportInfo['configured_transports'],
            'failed_transports' => $this->transportInfo['failed_transports'],
        ]);

        return $transports;
    }

    /**
     * Build OAuth2 transports for all available providers.
     */
    private function buildOAuth2Transports(): array
    {
        $transports = [];

        try {
            $tokenProviderFactory = $this->container->get(TokenProviderFactory::class);
            $availableProviders = $tokenProviderFactory->getAvailableProviders();

            if (empty($availableProviders)) {
                $this->logger->warning('No OAuth2 providers configured');
                $this->transportInfo['failed_transports'][] = 'oauth2: no providers configured';
                $this->transportInfo['attempts'][] = 'oauth2: no providers configured';

                return $transports;
            }

            $skipHealthCheck = $this->config->get('mail.oauth2.skip_health_check', false);

            if ($skipHealthCheck) {
                $this->logger->debugInternal('Skipping OAuth2 health checks due to configuration');
                $providersToUse = $availableProviders;
            } else {
                // Use TokenProviderFactory's intelligent provider selection
                // It already handles mail.oauth2.provider internally in createWithFallback()
                $providersToUse = $tokenProviderFactory->getHealthyProviders();
            }

            if (empty($providersToUse)) {
                $message = $skipHealthCheck ? 'No OAuth2 providers available' : 'No healthy OAuth2 providers available';
                $this->logger->warning($message);
                $this->transportInfo['failed_transports'][] = 'oauth2: '.strtolower($message);
                $this->transportInfo['attempts'][] = 'oauth2: '.strtolower($message);

                return $transports;
            }

            $this->transportInfo['fallback_used'] = \count($providersToUse) > 1;
            $this->transportInfo['health_check_skipped'] = $skipHealthCheck;

            $this->logger->debugInternal('Building OAuth2 transports', [
                'providers_to_use' => $providersToUse,
                'total_available' => \count($availableProviders),
                'health_check_skipped' => $skipHealthCheck,
                'technique' => $this->config->get('mail.transports.technique', 'failover'),
            ]);

            foreach ($providersToUse as $providerName) {
                try {
                    $this->logger->debugInternal("Building OAuth2 transport for provider: {$providerName}");

                    $dsn = $this->buildOAuth2TransportForProvider($providerName);
                    if ($dsn) {
                        $transportKey = "oauth2_{$providerName}";
                        $transports[$transportKey] = $dsn;

                        $this->transportInfo['configured_transports'][] = $transportKey;
                        $this->transportInfo['attempts'][] = "oauth2 ({$providerName}): configured";
                        $this->logger->debugInternal("OAuth2 transport configured: {$providerName}");
                    } else {
                        $this->transportInfo['failed_transports'][] = "oauth2 ({$providerName}): DSN build failed";
                        $this->transportInfo['attempts'][] = "oauth2 ({$providerName}): DSN build failed";
                    }
                } catch (\Exception $e) {
                    $this->logger->error("Failed to build OAuth2 transport: {$providerName}", [
                        'error' => $e->getMessage(),
                    ]);

                    $this->transportInfo['failed_transports'][] = "oauth2 ({$providerName}): ".$e->getMessage();
                    $this->transportInfo['attempts'][] = "oauth2 ({$providerName}): failed ({$e->getMessage()})";

                    continue;
                }
            }

            $this->logger->debugInternal('OAuth2 transports building completed', [
                'configured_transports' => array_keys($transports),
                'providers_used' => $providersToUse,
                'health_check_skipped' => $skipHealthCheck,
                'total_attempts' => \count($this->transportInfo['attempts']),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to build OAuth2 transports', [
                'error' => $e->getMessage(),
            ]);
            $this->transportInfo['failed_transports'][] = 'oauth2: '.$e->getMessage();
        }

        return $transports;
    }

    /**
     * Build OAuth2 transport DSN for a specific provider.
     */
    private function buildOAuth2TransportForProvider(string $providerName): ?string
    {
        try {
            $providerConfig = $this->config->get("mail.oauth2.providers.{$providerName}.config", []);
            $smtpConfig = $providerConfig['smtp'] ?? $this->config->get('mail.smtp', []);
            $smtpConfig['host'] ??= $this->config->get('mail.smtp.host', 'localhost');
            $smtpConfig['port'] ??= $this->config->get('mail.smtp.port', 587);

            // Build SMTP DSN with OAuth2 (empty password signals OAuth2 usage)
            $dsn = 'smtp://';

            if (!empty($smtpConfig['username'])) {
                $dsn .= rawurlencode((string) $smtpConfig['username']).':@';
            } else {
                $dsn .= ':@';
            }

            $dsn .= $smtpConfig['host'].':'.$smtpConfig['port'];

            // Add SMTP options
            $options = $this->buildSmtpOptions($smtpConfig);

            // Add provider identifier for OAuth2 transport factory
            $options[] = 'oauth2_provider='.rawurlencode($providerName);

            if (!empty($options)) {
                $dsn .= '?'.implode('&', $options);
            }

            $this->logger->debugInternal("Built OAuth2 transport DSN for provider: {$providerName}", [
                'provider' => $providerName,
                'host' => $smtpConfig['host'],
                'port' => $smtpConfig['port'],
            ]);

            return $dsn;
        } catch (\Exception $e) {
            $this->logger->error("Failed to build OAuth2 transport for provider: {$providerName}", [
                'error' => $e->getMessage(),
                'provider' => $providerName,
            ]);

            return null;
        }
    }

    /**
     * Build DSN for a specific transport type.
     */
    private function buildTransportDsn(string $transportType): ?string
    {
        // https://github.com/symfony/mailer
        // https://symfony.com/doc/current/mailer.html
        // https://github.com/swiftmailer/swiftmailer/issues/866
        // https://github.com/swiftmailer/swiftmailer/issues/633
        switch ($transportType) {
            case 'oauth2':
                // This case is now handled by buildOAuth2Transports()
                return null;

                // it requires proc_*() functions
            case 'smtp':
            case 'smtps':
                return $this->buildSmtpTransport($transportType);

            case 'sendmail':
                return $this->buildSendmailTransport();

                // When using native://default, if php.ini uses the sendmail -t command, you won't have error reporting and Bcc headers won't be removed.
                // It's highly recommended to NOT use native://default as you cannot control how sendmail is configured (prefer using sendmail://default if possible).
            case 'native':
                $this->transportInfo['type'] = 'native';

                return 'native://default';

            case 'mail':
                $this->transportInfo['type'] = 'mail';

                return 'mail://default';

            case 'mail+api':
                $this->transportInfo['type'] = 'mail+api';

                return 'mail+api://default';

            default:
                $this->logger->warning("Unknown transport type: {$transportType}");

                return null;
        }
    }

    /**
     * Build standard SMTP transport DSN.
     */
    private function buildSmtpTransport(string $protocol): string
    {
        $host = $this->config->get("mail.{$protocol}.host");
        $port = $this->config->get("mail.{$protocol}.port");

        if (empty($host)) {
            throw new \RuntimeException("Missing SMTP host for {$protocol} transport");
        }

        $dsn = $protocol.'://';

        if (!empty($this->config->get('mail.'.$protocol.'.username'))) {
            $dsn .= rawurlencode((string) $this->config->get('mail.'.$protocol.'.username'));
        }

        $dsn .= ':';

        if (!empty($this->config->get('mail.'.$protocol.'.password'))) {
            $dsn .= rawurlencode((string) $this->config->get('mail.'.$protocol.'.password'));
        }

        $dsn .= '@'.$host.':'.$port;

        $options = $this->buildSmtpOptions($this->config->get('mail.'.$protocol, []));
        if (!empty($options)) {
            $dsn .= '?'.implode('&', $options);
        }

        $this->logger->debugInternal('Built SMTP DSN', [
            'protocol' => $protocol,
            'host' => $host,
            'port' => $port,
        ]);

        return $dsn;
    }

    /**
     * Build sendmail transport DSN.
     */
    private function buildSendmailTransport(): string
    {
        $dsn = 'sendmail://default';

        if ($this->config->has('mail.sendmail.command')) {
            $command = strtr(rawurlencode((string) $this->config->get('mail.sendmail.command')), [
                '%2F' => '/',
            ]);
            $dsn .= '?command='.$command;

            $this->logger->debugInternal('Built sendmail DSN with custom command', [
                'command' => $this->config->get('mail.sendmail.command'),
            ]);
        } else {
            $this->logger->debugInternal('Built sendmail DSN with default command');
        }

        return $dsn;
    }

    /**
     * Build SMTP options array.
     */
    private function buildSmtpOptions(array $config): array
    {
        $options = [];
        $optionKeys = [
            'verify_peer',
            'local_domain',
            'restart_threshold',
            'restart_threshold_sleep',
            'ping_threshold',
        ];

        foreach ($optionKeys as $option) {
            if (isset($config[$option])) {
                $options[] = $option.'='.rawurlencode((string) $config[$option]);
            }
        }

        return $options;
    }

    /**
     * Create transport with custom factories registered and automatic tracking.
     */
    private function createTransportWithCustomFactories(string $dsnString): TransportInterface
    {
        // Get event dispatcher if available
        $dispatcher = null;
        if ($this->container->has(EventDispatcherInterface::class)) {
            $dispatcher = $this->container->get(EventDispatcherInterface::class);
        }

        // Get HTTP client if available
        $httpClient = null;
        if ($this->container->has(HttpClientInterface::class)) {
            $httpClient = $this->container->get(HttpClientInterface::class);
        }

        // Get logger if available
        $logger = null;
        if ($this->container->has(\Psr\Log\LoggerInterface::class)) {
            $logger = $this->container->get(\Psr\Log\LoggerInterface::class);
        }

        // Get default factories
        $factories = Transport::getDefaultFactories($dispatcher, $httpClient, $logger);

        // Convert to array to allow modifications
        if ($factories instanceof \Generator || $factories instanceof \Iterator) {
            $factories = iterator_to_array($factories);
        } elseif (!\is_array($factories)) {
            $factories = (array) $factories;
        }

        // Add custom factories from registry
        $customFactories = $this->transportRegistry->getFactories();
        foreach ($customFactories as $customFactory) {
            $factories[] = $customFactory;
        }

        // Add OAuth2 support through decorated factory if available
        try {
            if ($this->container->has(TransportFactoryInterface::class)) {
                $oauthFactory = $this->container->get(TransportFactoryInterface::class);

                // Replace the default EsmtpTransportFactory with our OAuth2-enabled version
                $factories = array_filter($factories, fn ($factory) => !($factory instanceof EsmtpTransportFactory));

                // Add the OAuth2-enabled factory
                $factories[] = $oauthFactory;

                $this->logger->debugInternal('OAuth2 transport factory registered');
            }
        } catch (\Exception $e) {
            $this->logger->warning('Failed to register OAuth2 transport factory', [
                'error' => $e->getMessage(),
            ]);
        }

        // Create Transport factory instance
        $transportFactory = new Transport($factories);

        // Handle compound transports by wrapping individual DSNs
        if (\Safe\preg_match('/^(failover|roundrobin)\((.+)\)$/', $dsnString, $matches)) {
            $technique = $matches[1];
            $innerDsns = $matches[2];

            $this->logger->debugInternal('Creating compound transport with tracking', [
                'technique' => $technique,
                'inner_dsns' => $innerDsns,
            ]);

            // Parse individual DSNs and create tracked transports
            $dsnParts = explode(' ', $innerDsns);
            $trackedTransports = [];

            foreach ($dsnParts as $singleDsn) {
                $singleDsn = trim($singleDsn);
                if (empty($singleDsn)) {
                    continue;
                }

                try {
                    // Create individual transport
                    $singleTransport = $transportFactory->fromString($singleDsn);

                    // Wrap with tracking
                    $trackedTransport = $this->wrapTransportWithTracking($singleTransport, $singleDsn);
                    $trackedTransports[] = $trackedTransport;

                    $this->logger->debugInternal('Wrapped individual transport with tracking', [
                        'dsn' => $singleDsn,
                        'transport_class' => $singleTransport::class,
                    ]);
                } catch (\Exception $e) {
                    $this->logger->warning("Failed to create transport for DSN: {$singleDsn}", [
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }
            }

            if (empty($trackedTransports)) {
                throw new \RuntimeException("No valid transports could be created from compound DSN: {$dsnString}");
            }

            // Create compound transport from tracked transports
            if ('roundrobin' === $technique) {
                return new RoundRobinTransport($trackedTransports);
            } else {
                return new FailoverTransport($trackedTransports);
            }
        }

        // Handle single transport
        $actualTransport = $transportFactory->fromString($dsnString);

        return $this->wrapTransportWithTracking($actualTransport, $dsnString);
    }

    /**
     * Simplified DSN analysis - just extract basic info from TrackedTransport metadata.
     */
    private function wrapTransportWithTracking(TransportInterface $transport, string $dsnString): TransportInterface
    {
        // Simple DSN parsing for metadata
        $metadata = ['dsn' => $dsnString];
        $type = 'unknown';
        $provider = null;
        $providerType = null;

        // Extract basic info from DSN
        if (\Safe\preg_match('/^([^:]+):\/\//', $dsnString, $matches)) {
            $scheme = $matches[1];
            $type = $scheme;

            // Check for OAuth2 provider parameter
            if (\Safe\preg_match('/oauth2_provider=([^&]+)/', $dsnString, $providerMatches)) {
                $type = 'oauth2';
                $provider = urldecode($providerMatches[1]);
                $providerType = 'oauth2';
            }

            // Check for API provider parameter (future extensibility)
            elseif (\Safe\preg_match('/api_provider=([^&]+)/', $dsnString, $providerMatches)) {
                $type = 'api';
                $provider = urldecode($providerMatches[1]);
                $providerType = 'api';
            }

            // Extract host and port for SMTP-like transports
            if (\in_array($scheme, ['smtp', 'smtps'], true) && \Safe\preg_match('/\/\/[^@]*@?([^:\/]+):?(\d+)?/', $dsnString, $hostMatches)) {
                $metadata['host'] = $hostMatches[1];
                if (isset($hostMatches[2])) {
                    $metadata['port'] = (int) $hostMatches[2];
                }
            }
        }

        return new TrackedTransport($transport, $type, $provider, $providerType, $metadata);
    }

    /**
     * Get appropriate provider label based on provider type.
     */
    private function getProviderLabel(?string $providerType): string
    {
        return match ($providerType) {
            'oauth2' => 'OAuth2 Provider',
            'api' => 'API Provider',
            'webhook' => 'Webhook Provider',
            'smtp' => 'SMTP Provider',
            default => 'Provider'
        };
    }
}
