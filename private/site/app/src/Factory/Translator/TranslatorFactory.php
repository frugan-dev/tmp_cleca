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

namespace App\Factory\Translator;

use App\Factory\Logger\LoggerInterface;
use App\Factory\Session\SessionInterface;
use App\Factory\Translator\Loader\MoFileLoader;
use App\Factory\Translator\Loader\PoFileLoader;
use App\Model\Model;
use App\Service\Route\RouteParsingService;
use Negotiation\LanguageNegotiator;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\Loader\CsvFileLoader;
use Symfony\Component\Translation\Loader\IniFileLoader;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Cache\CacheInterface as PsrCacheInterface;

// https://www.cssigniter.com/wordpress-poedit-translation-secrets/
// https://designpatternsphp.readthedocs.io/en/latest/README.html
class TranslatorFactory extends Model implements TranslatorInterface
{
    public ?int $id = null;

    public ?string $code = null;

    public ?string $locale = null;

    public ?string $localeCharset = null;

    public ?int $acceptId = null;

    public ?string $acceptCode = null;

    public ?string $routeCode = null;

    public ?array $arr = [];

    public ?array $codeArr = [];

    protected ?Translator $instance = null;

    /**
     * Available translation file loaders and their extensions.
     */
    protected array $availableLoaders = [
        'mo' => ['class' => MoFileLoader::class, 'extensions' => ['mo']],
        'po' => ['class' => PoFileLoader::class, 'extensions' => ['po']],
        'php' => ['class' => PhpFileLoader::class, 'extensions' => ['php']],
        'yaml' => ['class' => YamlFileLoader::class, 'extensions' => ['yaml', 'yml']],
        'json' => ['class' => JsonFileLoader::class, 'extensions' => ['json']],
        'xliff' => ['class' => XliffFileLoader::class, 'extensions' => ['xlf', 'xliff']],
        'csv' => ['class' => CsvFileLoader::class, 'extensions' => ['csv']],
        'ini' => ['class' => IniFileLoader::class, 'extensions' => ['ini']],
    ];

    /**
     * Enabled loaders (configurable).
     */
    protected array $enabledLoaders = [];

    /**
     * File format priority order.
     */
    protected array $formatPriority = [];

    public function __construct(
        protected ContainerInterface $container,
        protected SessionInterface $session,
        protected LoggerInterface $logger,
        protected RouteParsingService $routeParsingService,
        protected ?EventDispatcherInterface $eventDispatcher = null
    ) {}

    public function __call($name, $args)
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. Call create() first.', $this->getShortName()));
        }

        return \call_user_func_array([$this->instance, $name], $args);
    }

    public function getInstance(): ?Translator
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. It should be initialized via container factory.', $this->getShortName()));
        }

        return $this->instance;
    }

    public function create(?int $langId = null): self
    {
        if (null !== $this->instance) {
            return $this; // Already initialized
        }

        $this->prepare($langId);

        // Create Symfony Translator instance
        $this->instance = new Translator($this->localeCharset);

        // Set fallback locale
        $fallbackId = $this->getConfigWithFallback('fallbackId');
        $this->instance->setFallbackLocales([$this->arr[$fallbackId]['localeCharset']]);

        $this->logger->debugInternal('Translator instance created', [
            'main_locale' => $this->localeCharset,
            'fallback_locales' => [$this->arr[$fallbackId]['localeCharset']],
        ]);

        // Setup loaders
        $this->setupLoaders();

        // Load translation files for different domains
        $this->loadTranslationFiles();

        return $this;
    }

    public function prepare(?int $langId = null): void
    {
        $this->arr = $this->getConfigWithFallback('arr', []);
        $fallbackId = $this->getConfigWithFallback('fallbackId');
        $contentNegotiation = $this->getConfigWithFallback('contentNegotiation.enabled');

        // Setup enabled loaders from config
        $this->enabledLoaders = $this->getConfigWithFallback('loaders.enabled', ['mo']);
        $this->formatPriority = $this->getConfigWithFallback('loaders.priority', ['mo', 'po', 'php', 'yaml', 'json', 'xliff', 'csv', 'ini']);

        $this->codeArr = [];
        foreach ($this->arr as $key => $val) {
            $this->codeArr[$key] = $val['isoCode'];
        }

        // Use RouteParsingService to get route information before routing middleware
        try {
            // Get language parameter from route
            $this->routeCode = $this->routeParsingService->getLanguage();

            // If no direct lang parameter, check in params string
            if (empty($this->routeCode)) {
                $paramsString = $this->routeParsingService->getParamsString();
                if ($paramsString) {
                    $params = explode('/', $paramsString);
                    if (($paramId = array_search('lang', $params, true)) !== false) {
                        if (isset($params[$paramId + 1])) {
                            $this->routeCode = (string) $params[$paramId + 1];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback if route parsing fails - this can happen during early initialization
            $this->logger->warningInternal('RouteParsingService failed in TranslatorFactory', [
                'exception' => $e,
            ]);
        }

        if (!empty($contentNegotiation)) {
            if (!empty($accept = $this->request?->getHeaderLine('Accept-Language'))) {
                // Try to use content negotiation if available
                $this->acceptCode = $this->negotiateLanguage($accept, $this->codeArr);
                if ($this->acceptCode) {
                    $this->acceptId = array_search($this->acceptCode, $this->codeArr, true);
                }
            }
        }

        if ($langId && \array_key_exists($langId, $this->codeArr)) {
            $this->code = $this->codeArr[$langId];
            $this->session->set('lang', $this->code);
        } elseif (!empty($this->routeCode) && \in_array($this->routeCode, $this->codeArr, true)) {
            $this->code = $this->routeCode;
            $this->session->set('lang', $this->code);
        } elseif ($this->session->get('lang') && \in_array($this->session->get('lang'), $this->codeArr, true)) {
            $this->code = $this->session->get('lang');
        } elseif (!empty($this->acceptCode)) {
            $this->code = $this->acceptCode;
            $this->session->set('lang', $this->code);
            $this->session->set('contentNegotiation', true);
        } else {
            $this->code = $this->codeArr[$fallbackId];
            $this->session->set('lang', $this->code);
        }

        $this->id = array_search($this->code, $this->codeArr, true);
        $this->locale = $this->arr[$this->id]['locale'];
        $this->localeCharset = $this->arr[$this->id]['localeCharset'];

        // Debug language selection
        $this->logger->debugInternal('Language selection completed', [
            'selected_code' => $this->code,
            'selected_id' => $this->id,
            'locale' => $this->locale,
            'locale_charset' => $this->localeCharset,
            'route_code' => $this->routeCode,
            'accept_code' => $this->acceptCode,
            'session_lang' => $this->session->get('lang'),
            'fallback_id' => $fallbackId,
        ]);

        // Setting a language isn't enough for some systems and the putenv() should be used to define the current locale.
        \Safe\putenv('LC_ALL='.$this->localeCharset);

        // https://www.php.net/manual/en/function.setlocale.php#46640
        // https://stackoverflow.com/a/30052829/3929620
        // The locale string need to be supported by the server
        // setlocale(LC_ALL, $this->localeCharset); // for all of the below
        setlocale(LC_COLLATE, $this->localeCharset); // for string comparison, see strcoll()
        setlocale(LC_CTYPE, $this->localeCharset); // for character classification and conversion, for example strtoupper()
        setlocale(LC_MONETARY, $this->localeCharset); // for localeconv()
        // setlocale(LC_NUMERIC, 'en_US'); // for decimal separator (See also localeconv())
        setlocale(LC_TIME, $this->localeCharset); // for date and time formatting with strftime() (deprecated in PHP >= 8.2)
        setlocale(LC_MESSAGES, $this->localeCharset); // for system responses (available if PHP was compiled with libintl)

        if (\extension_loaded('intl')) { // PHP 5 >= 5.3.0, PECL intl >= 1.0.0
            \Locale::setDefault($this->localeCharset);
        }
    }

    public function setCache(?PsrCacheInterface $cache): void
    {
        if (null !== $this->instance && null !== $cache) {
            $this->instance->setCache($cache);
        }
    }

    /**
     * Translate a message with context support.
     * Parameter substitution is handled by the calling code.
     */
    public function translate(string $message, ?string $context = null, ?string $locale = null, ?string $domain = null): string
    {
        if (null === $this->instance) {
            return $message;
        }

        // Validate context if provided
        if (null !== $context && !$this->isValidContext($context)) {
            $this->logger->warningInternal('Invalid context used in translation', [
                'message' => $message,
                'context' => $context,
                'available_contexts' => $this->getConfiguredContexts(),
            ]);
        }

        // Build the translation key with context support
        $translationKey = $this->buildTranslationKey($message, $context);

        $domain ??= $this->getDefaultDomain();

        $translated = null;

        if ($this->hasTranslation($message, $context, $locale, $domain)) {
            // Get translation from Symfony
            $translated = $this->instance->trans($translationKey, [], $domain, $locale);
        } else {
            // Fallback to non-contextual translation if contextual not found
            $translated = $this->instance->trans($message, [], $domain, $locale);
        }

        // Check if the translation is empty or unchanged - if so, return the original message
        if (empty(trim($translated)) || $translated === $message) {
            return $message;
        }

        return $translated;
    }

    public function translatePlural(string $singular, string $plural, int $number, ?string $context = null, ?string $locale = null, ?string $domain = null): string
    {
        if (null === $this->instance) {
            return $number <= 1 ? $singular : $plural;
        }

        // Validate context if provided
        if (null !== $context && !$this->isValidContext($context)) {
            $this->logger->warningInternal('Invalid context used in plural translation', [
                'singular' => $singular,
                'plural' => $plural,
                'context' => $context,
                'available_contexts' => $this->getConfiguredContexts(),
            ]);
        }

        $domain ??= $this->getDefaultDomain();
        $currentLocale = $locale ?? $this->localeCharset;
        $originalResult = $number <= 1 ? $singular : $plural;

        if (null === $currentLocale) {
            return $originalResult;
        }

        try {
            $catalogue = $this->instance->getCatalogue($currentLocale);

            // Build plural translation keys inline to avoid __call interception
            // Try with context first if provided
            if (null !== $context) {
                // Build contextual key: "context\004singular|plural"
                $contextualKey = $context."\004".$singular.'|'.$plural;

                if ($catalogue->has($contextualKey, $domain)) {
                    $translationString = $catalogue->get($contextualKey, $domain);

                    // Extract plural form inline
                    $forms = explode('|', $translationString);

                    // Remove empty trailing forms (marked with '-' by our loaders)
                    $cleanForms = array_filter($forms, fn ($form) => '-' !== $form && '' !== trim($form));

                    if (!empty($cleanForms)) {
                        // Simple plural rule for most languages
                        // Form 0: singular (n == 1), Form 1: plural (n != 1)
                        $index = (1 === $number) ? 0 : 1;

                        // If the requested index doesn't exist, fallback to the last available form
                        if (!isset($cleanForms[$index])) {
                            $index = \count($cleanForms) - 1;
                        }

                        if (isset($cleanForms[$index]) && !empty(trim($cleanForms[$index]))) {
                            return $cleanForms[$index];
                        }
                    }
                }
            }

            // Fallback: try without context (standard plural key)
            $pluralKey = $singular.'|'.$plural;

            if ($catalogue->has($pluralKey, $domain)) {
                try {
                    $translationString = $catalogue->get($pluralKey, $domain);

                    if (!\is_string($translationString)) {
                        $this->logger->warningInternal('Translation is not a string', [
                            'translation' => $translationString,
                            'type' => \gettype($translationString),
                        ]);
                        // Convert to string if possible
                        $translationString = (string) $translationString;
                    }

                    // Extract plural form inline
                    $forms = explode('|', $translationString);

                    // Remove empty trailing forms (marked with '-' by our loaders)
                    $cleanForms = array_filter($forms, fn ($form) => '-' !== $form && '' !== trim($form));

                    if (!empty($cleanForms)) {
                        // Simple plural rule for most languages
                        // Form 0: singular (n == 1), Form 1: plural (n != 1)
                        $index = (1 === $number) ? 0 : 1;

                        // If the requested index doesn't exist, fallback to the last available form
                        if (!isset($cleanForms[$index])) {
                            $index = \count($cleanForms) - 1;
                        }

                        if (isset($cleanForms[$index]) && !empty(trim($cleanForms[$index]))) {
                            return $cleanForms[$index];
                        }
                    }
                } catch (\Throwable $exception) {
                    $this->logger->warningInternal('Error getting translation from catalogue', [
                        'exception' => $exception,
                        'key' => $pluralKey,
                        'domain' => $domain,
                        'locale' => $currentLocale,
                    ]);
                }
            }

            // If no plural translation found, try individual forms
            // Try singular with context
            if (null !== $context) {
                $singularKey = $context."\004".$singular;
                if ($catalogue->has($singularKey, $domain)) {
                    $translation = $catalogue->get($singularKey, $domain);
                    if (!empty(trim($translation))) {
                        return $translation;
                    }
                }
            }

            // Try singular without context
            if ($catalogue->has($singular, $domain)) {
                $translation = $catalogue->get($singular, $domain);
                if (!empty(trim($translation))) {
                    return $translation;
                }
            }
        } catch (\Throwable $exception) {
            $this->logger->warningInternal('Error in plural translation', [
                'exception' => $exception,
                'singular' => $singular,
                'plural' => $plural,
                'context' => $context,
                'locale' => $currentLocale,
                'domain' => $domain,
            ]);
        }

        // Final fallback: return the appropriate form based on number
        return $originalResult;
    }

    /**
     * Enhanced translation existence check with context support.
     */
    public function hasTranslation(string $message, ?string $context = null, ?string $locale = null, ?string $domain = null): bool
    {
        if (null === $this->instance) {
            return false;
        }

        try {
            $currentLocale = $locale ?? $this->localeCharset;
            if (null === $currentLocale) {
                return false;
            }

            $translationKey = $this->buildTranslationKey($message, $context);
            $catalogue = $this->instance->getCatalogue($currentLocale);

            $domain ??= $this->getDefaultDomain();

            return $catalogue->has($translationKey, $domain);
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Enhanced plural translation existence check with context support.
     */
    public function hasPluralTranslation(string $singular, string $plural, ?string $context = null, ?string $locale = null, ?string $domain = null): bool
    {
        if (null === $this->instance) {
            return false;
        }

        try {
            $currentLocale = $locale ?? $this->localeCharset;
            if (null === $currentLocale) {
                return false;
            }

            $catalogue = $this->instance->getCatalogue($currentLocale);
            $domain ??= $this->getDefaultDomain();

            // Check for plural key with context
            if (null !== $context) {
                $contextualKey = $context."\004".$singular.'|'.$plural;
                if ($catalogue->has($contextualKey, $domain)) {
                    return true;
                }
            }

            // Check for plural key without context
            $pluralKey = $singular.'|'.$plural;
            if ($catalogue->has($pluralKey, $domain)) {
                return true;
            }

            // Fallback: check individual forms
            if (null !== $context) {
                $singularKey = $context."\004".$singular;
                if ($catalogue->has($singularKey, $domain)) {
                    return true;
                }
            }

            if ($catalogue->has($singular, $domain)) {
                return true;
            }
        } catch (\Throwable $exception) {
            $this->logger->warningInternal('Error checking plural translation existence', [
                'exception' => $exception,
                'singular' => $singular,
                'plural' => $plural,
                'context' => $context,
                'locale' => $locale,
                'domain' => $domain,
            ]);
        }

        return false;
    }

    /**
     * Get all available translation files for debugging.
     */
    public function getAvailableTranslationFiles(): array
    {
        $files = [];
        $domains = $this->getConfiguredDomains();
        $localeDir = $this->getConfigWithFallback('locale_dir', _ROOT.'/app/locale');

        foreach ($domains as $domain) {
            foreach ($this->arr as $langData) {
                $locale = $langData['localeCharset'];

                foreach ($this->enabledLoaders as $loaderName) {
                    $loaderConfig = $this->availableLoaders[$loaderName];

                    foreach ($loaderConfig['extensions'] as $extension) {
                        $fileName = $this->buildTranslationFileName($locale, $domain, $extension);
                        $filePath = $localeDir.'/'.$fileName;

                        $files[] = [
                            'locale' => $locale,
                            'domain' => $domain,
                            'format' => $loaderName,
                            'extension' => $extension,
                            'filename' => $fileName,
                            'path' => $filePath,
                            'exists' => file_exists($filePath),
                            'size' => file_exists($filePath) ? \Safe\filesize($filePath) : 0,
                        ];
                    }
                }
            }
        }

        return $files;
    }

    /**
     * Get the default domain dynamically from configuration.
     */
    public function getDefaultDomain(): string
    {
        $domains = $this->getConfiguredDomains();

        return $domains[0] ?? 'messages';
    }

    /**
     * Get all configured translation domains.
     */
    public function getConfiguredDomains(): array
    {
        return $this->getConfigWithFallback('domains', ['messages']);
    }

    /**
     * Get all configured translation contexts.
     */
    public function getConfiguredContexts(): array
    {
        return $this->getConfigWithFallback('contexts', ['default']);
    }

    /**
     * Validate that a context is configured in the system.
     */
    public function isValidContext(?string $context): bool
    {
        if (null === $context) {
            return true; // null context is always valid
        }

        $configuredContexts = $this->getConfiguredContexts();

        return \in_array($context, $configuredContexts, true);
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
            "lang.{$env}",
            'lang',
        ];

        return $this->config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }

    /**
     * Debug method to get all available messages in a domain for debugging.
     */
    public function getAvailableMessages(?string $locale = null, ?string $domain = null): array
    {
        if (null === $this->instance) {
            return [];
        }

        try {
            $currentLocale = $locale ?? $this->localeCharset;
            if (null === $currentLocale) {
                return [];
            }

            $catalogue = $this->instance->getCatalogue($currentLocale);

            $domain ??= $this->getDefaultDomain();

            return $catalogue->all($domain);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Build translation key with context support.
     * In Symfony Translation with gettext files, context is concatenated to the message ID.
     */
    protected function buildTranslationKey(string $message, ?string $context): string
    {
        if (null === $context) {
            return $message;
        }

        // Symfony concatenates context + message without separator for msgctxt.
        // Prepend context to the message with ASCII 4 separator.
        return $context."\004".$message;
    }

    /**
     * Setup translation file loaders based on configuration.
     */
    protected function setupLoaders(): void
    {
        foreach ($this->enabledLoaders as $loaderName) {
            if (!isset($this->availableLoaders[$loaderName])) {
                throw new \InvalidArgumentException("Unknown loader: {$loaderName}");
            }

            $loaderConfig = $this->availableLoaders[$loaderName];
            $loaderClass = $loaderConfig['class'];

            // Check if the loader class exists and required dependencies are available
            if (!class_exists($loaderClass)) {
                $this->logger->warningInternal("Translation loader {$loaderClass} not available, skipping {$loaderName} format");

                continue;
            }

            // Special handling for YAML loader
            if ('yaml' === $loaderName && !class_exists(Yaml::class)) {
                $this->logger->warningInternal('YAML component not installed, skipping YAML translation files');

                continue;
            }

            // Register the loader for all its extensions
            foreach ($loaderConfig['extensions'] as $extension) {
                $this->instance->addLoader($extension, new $loaderClass());
            }
        }
    }

    /**
     * Load translation files for different domains and formats.
     */
    protected function loadTranslationFiles(): void
    {
        $domains = $this->getConfiguredDomains();
        $localeDir = $this->getConfigWithFallback('locale_dir', _ROOT.'/app/locale');

        foreach ($domains as $domain) {
            foreach ($this->arr as $langData) {
                $this->loadTranslationFilesForLocale($langData['localeCharset'], $domain, $localeDir);
            }
        }
    }

    /**
     * Load translation files for a specific locale and domain.
     */
    protected function loadTranslationFilesForLocale(string $locale, string $domain, string $localeDir): void
    {
        // Sort loaders by priority
        $sortedLoaders = [];
        foreach ($this->formatPriority as $format) {
            if (\in_array($format, $this->enabledLoaders, true)) {
                $sortedLoaders[] = $format;
            }
        }

        foreach ($sortedLoaders as $loaderName) {
            $loaderConfig = $this->availableLoaders[$loaderName];

            foreach ($loaderConfig['extensions'] as $extension) {
                $fileName = $this->buildTranslationFileName($locale, $domain, $extension);
                $filePath = $localeDir.'/'.$fileName;

                if (file_exists($filePath)) {
                    $this->instance->addResource($extension, $filePath, $locale, $domain);

                    $this->logger->debugInternal('Loaded translation file', [
                        'extension' => $extension,
                        'filePath' => $filePath,
                        'locale' => $locale,
                        'domain' => $domain,
                    ]);

                    // If we found a file for this domain/locale combo, we can break
                    // This implements file format priority - first found wins
                    if ($this->getConfigWithFallback('loaders.use_priority', true)) {
                        break 2; // Break both foreach loops
                    }
                }
            }
        }
    }

    /**
     * Build translation file name based on configuration patterns.
     */
    protected function buildTranslationFileName(string $locale, string $domain, string $extension): string
    {
        $pattern = $this->getConfigWithFallback('filename_pattern', '{locale}.{extension}');

        // Support for domain-specific patterns
        $domainPattern = $this->getConfigWithFallback("filename_patterns.{$domain}");
        if ($domainPattern) {
            $pattern = $domainPattern;
        }

        return str_replace(
            ['{locale}', '{domain}', '{extension}'],
            [$locale, $domain, $extension],
            $pattern
        );
    }

    /**
     * Negotiate the best language from Accept-Language header.
     * Uses willdurand/Negotiation if available, otherwise falls back to simple parsing.
     */
    protected function negotiateLanguage(string $acceptLanguageHeader, array $availableLanguages): ?string
    {
        // Try to use Negotiation library if available (from middlewares/negotiation)
        if (class_exists(LanguageNegotiator::class)) {
            try {
                $negotiator = new LanguageNegotiator();
                $bestLanguage = $negotiator->getBest($acceptLanguageHeader, $availableLanguages);

                if ($bestLanguage) {
                    return $bestLanguage->getValue();
                }
            } catch (\Throwable $e) {
                // Fallback to simple parsing if negotiation fails
                $this->logger->warningInternal('Language negotiation failed', [
                    'exception' => $e,
                ]);
            }
        }

        // Fallback: Simple Accept-Language parsing
        return $this->parseAcceptLanguageHeader($acceptLanguageHeader, $availableLanguages);
    }

    /**
     * Simple Accept-Language header parser as fallback.
     * Parses headers like: "en-US,en;q=0.9,fr;q=0.8".
     */
    protected function parseAcceptLanguageHeader(string $header, array $availableLanguages): ?string
    {
        // Parse Accept-Language header
        $languages = [];
        $parts = explode(',', $header);

        foreach ($parts as $part) {
            $part = trim($part);
            if (str_contains($part, ';q=')) {
                [$lang, $quality] = explode(';q=', $part, 2);
                $languages[trim($lang)] = (float) $quality;
            } else {
                $languages[trim($part)] = 1.0;
            }
        }

        // Sort by quality (highest first)
        arsort($languages);

        // Find best match
        foreach ($languages as $requestedLang => $quality) {
            // Direct match
            if (\in_array($requestedLang, $availableLanguages, true)) {
                return $requestedLang;
            }

            // Language-only match (e.g., "en" matches "en-US")
            $langOnly = strtok($requestedLang, '-');
            foreach ($availableLanguages as $available) {
                if (strtok($available, '-') === $langOnly) {
                    return $available;
                }
            }
        }

        return null;
    }
}
