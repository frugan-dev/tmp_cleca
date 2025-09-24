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

namespace App\Factory\Translator\EventListener;

use App\Factory\Cache\CacheInterface;
use App\Factory\Logger\LoggerInterface;
use App\Factory\Translator\TranslatorInterface;
use App\Helper\HelperInterface;
use Psr\Container\ContainerInterface;

abstract class BaseTranslationListener
{
    /**
     * Track plural translations to group them together.
     * Enhanced to support context.
     */
    protected static array $pluralTranslations = [];

    /**
     * Static cache for file initialization to avoid repeated checks.
     */
    private static array $initializedFiles = [];

    public function __construct(
        protected readonly ContainerInterface $container,
        protected readonly HelperInterface $helper,
        protected readonly LoggerInterface $logger,
        protected readonly TranslatorInterface $translator,
        protected readonly ?CacheInterface $cache = null
    ) {}

    /**
     * Get configuration value with environment fallback using existing ConfigManager.
     *
     * @param null|mixed $default
     */
    protected function getConfigWithFallback(string $suffix = '', $default = null, ?array $prefixes = null): mixed
    {
        $env = $this->container->get('env');

        $prefixes ??= [
            "lang.{$env}",
            'lang',
        ];

        $config = $this->container->get('config');

        return $config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }

    /**
     * Check if the current listener is enabled based on configuration.
     */
    protected function isEnabled(string $listenerType): bool
    {
        return $this->getConfigWithFallback("listeners.{$listenerType}.enabled", false);
    }

    /**
     * Get the file path for the listener based on configuration.
     */
    protected function getFilePath(string $listenerType, string $defaultFileName): string
    {
        return $this->getConfigWithFallback(
            "listeners.{$listenerType}.file",
            \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null)."/cache/{$defaultFileName}")
        );
    }

    /**
     * Check if the translation is already present in the file.
     * Enhanced to support context.
     */
    protected function isTranslationInFile(string $contents, string $message, ?string $context = null, ?string $domain = null, bool $isPlural = false, ?string $pluralForm = null): bool
    {
        $domain ??= $this->translator->getDefaultDomain();
        $defaultDomain = $this->translator->getDefaultDomain();

        // For plural translations, check for _n() function calls
        if ($isPlural && $pluralForm) {
            $patterns = [
                // Standard _n() calls
                "_n('{$message}', '{$pluralForm}'",
                "_n(\"{$message}\", \"{$pluralForm}\"",
                "_nx('{$message}', '{$pluralForm}'",
                "_nx(\"{$message}\", \"{$pluralForm}\"",
            ];

            // Add domain-specific patterns if not default domain
            if ($defaultDomain !== $domain) {
                $patterns[] = "_n('{$message}', '{$pluralForm}', 1, [], '{$domain}')";
                $patterns[] = "_n(\"{$message}\", \"{$pluralForm}\", 1, [], \"{$domain}\")";
                $patterns[] = "_nx('{$message}', '{$pluralForm}', 1, '{$context}', [], '{$domain}')";
                $patterns[] = "_nx(\"{$message}\", \"{$pluralForm}\", 1, \"{$context}\", [], \"{$domain}\")";
            }

            // Add context-specific patterns
            if ($context) {
                $patterns[] = "_nx('{$message}', '{$pluralForm}', 1, '{$context}')";
                $patterns[] = "_nx(\"{$message}\", \"{$pluralForm}\", 1, \"{$context}\")";
            }
        } else {
            // Simple string search for performance - more accurate than regex for this case
            $searchPatterns = [
                "__('{$message}'", // Single quotes
                "__(\"{$message}\"", // Double quotes
            ];

            // Add domain-specific patterns if not default domain
            if ($defaultDomain !== $domain) {
                $searchPatterns[] = "__('{$message}', [], '{$domain}')";
                $searchPatterns[] = "__(\"{$message}\", [], \"{$domain}\")";
            } else {
                // For default domain, also check patterns without domain
                $searchPatterns[] = "__('{$message}')";
                $searchPatterns[] = "__(\"{$message}\")";
            }

            // Add context-specific patterns
            if ($context) {
                $searchPatterns[] = "_x('{$message}', '{$context}')";
                $searchPatterns[] = "_x(\"{$message}\", \"{$context}\")";
                if ($defaultDomain !== $domain) {
                    $searchPatterns[] = "_x('{$message}', '{$context}', [], '{$domain}')";
                    $searchPatterns[] = "_x(\"{$message}\", \"{$context}\", [], \"{$domain}\")";
                }
            }

            $patterns = $searchPatterns;
        }

        return array_any($patterns, fn ($pattern) => str_contains($contents, (string) $pattern));
    }

    /**
     * Generate the function call string that Poedit can parse.
     * Enhanced to support context.
     */
    protected function generateFunctionCall(string $message, ?string $context = null, ?string $domain = null, string $comment = '', bool $isPlural = false, ?string $pluralForm = null): string
    {
        $domain ??= $this->translator->getDefaultDomain();
        $defaultDomain = $this->translator->getDefaultDomain();

        $escapedMessage = addcslashes($message, "'");

        if ($isPlural && $pluralForm) {
            $escapedPlural = addcslashes($pluralForm, "'");

            if ($context) {
                // Use _nx() for plural translations with context
                $escapedContext = addcslashes($context, "'");
                $functionCall = "_nx('{$escapedMessage}', '{$escapedPlural}', 1, '{$escapedContext}'";
            } else {
                // Use _n() for plural translations without context
                $functionCall = "_n('{$escapedMessage}', '{$escapedPlural}', 1";
            }

            if ($defaultDomain !== $domain) {
                $escapedDomain = addcslashes($domain, "'");
                $functionCall .= ", [], '{$escapedDomain}'";
            }
        } else {
            if ($context) {
                // Use _x() for singular translations with context
                $escapedContext = addcslashes($context, "'");
                $functionCall = "_x('{$escapedMessage}', '{$escapedContext}'";
            } else {
                // Use __() for singular translations without context
                $functionCall = "__('{$escapedMessage}'";
            }

            if ($defaultDomain !== $domain) {
                $escapedDomain = addcslashes($domain, "'");
                $functionCall .= ", [], '{$escapedDomain}'";
            }
        }

        $functionCall .= ');';

        if ($comment) {
            $functionCall .= ' // '.$comment;
        }

        $functionCall .= PHP_EOL;

        return $functionCall;
    }

    /**
     * Initialize the translation file if it doesn't exist.
     */
    protected function initializeFile(string $filePath, string $description): void
    {
        // Use static cache to avoid repeated file system checks
        if (isset(self::$initializedFiles[$filePath])) {
            return;
        }

        if (!file_exists($filePath)) {
            $dir = \dirname($filePath);
            if (!is_dir($dir)) {
                \Safe\mkdir($dir, 0o755, true);
            }

            \Safe\file_put_contents(
                $filePath,
                '<?php'.PHP_EOL.PHP_EOL.'// AUTO-GENERATED FILE - DO NOT EDIT!'.PHP_EOL."// {$description}".PHP_EOL.PHP_EOL
            );

            // Only log file creation, not every initialization check
            $this->logger->infoInternal("Initialized translation file: {$filePath}");
        }

        // Mark as initialized
        self::$initializedFiles[$filePath] = true;
    }

    /**
     * Get cache key for a translation entry.
     * Enhanced to support context.
     */
    protected function getCacheKey(string $prefix, string $message, ?string $context = null, ?string $locale = null, ?string $domain = null): string
    {
        return $prefix.'.'.$this->helper->Strings()->crc32(
            $this->helper->Nette()->Json()->encode([
                'message' => $message,
                'context' => $context,
                'locale' => $locale,
                'domain' => $domain,
            ])
        );
    }

    /**
     * Store plural translation for later processing.
     * Enhanced to support context.
     */
    protected function storePluralTranslation(string $singular, string $plural, ?string $context = null, string $comment = '', ?string $domain = null): void
    {
        $domain ??= $this->translator->getDefaultDomain();
        $key = md5($singular.'|'.($context ?? 'null').'|'.($domain ?? 'null'));
        self::$pluralTranslations[$key] = [
            'singular' => $singular,
            'plural' => $plural,
            'context' => $context,
            'comment' => $comment,
            'domain' => $domain,
        ];
    }

    /**
     * Check if this is part of a plural translation.
     * Enhanced to support context.
     */
    protected function isPartOfPluralTranslation(string $message, ?string $context = null, ?string $domain = null): ?array
    {
        $domain ??= $this->translator->getDefaultDomain();

        // Check if this message is stored as singular
        $key = md5($message.'|'.($context ?? 'null').'|'.($domain ?? 'null'));
        if (isset(self::$pluralTranslations[$key])) {
            return self::$pluralTranslations[$key];
        }

        // Check if this message is stored as plural
        foreach (self::$pluralTranslations as $data) {
            if ($data['plural'] === $message && ($data['context'] ?? null) === $context && $data['domain'] === $domain) {
                return $data;
            }
        }

        return null;
    }

    /**
     * Process and write plural translations at the end of request.
     * Enhanced to support context.
     */
    protected function writePendingPluralTranslations(string $filePath): void
    {
        if (empty(self::$pluralTranslations)) {
            return;
        }

        $contents = \Safe\file_get_contents($filePath);
        $toWrite = [];

        foreach (self::$pluralTranslations as $data) {
            if (!$this->isTranslationInFile($contents, $data['singular'], $data['context'] ?? null, $data['domain'], true, $data['plural'])) {
                $functionCall = $this->generateFunctionCall(
                    $data['singular'],
                    $data['context'] ?? null,
                    $data['domain'],
                    $data['comment'],
                    true,
                    $data['plural']
                );
                $toWrite[] = $functionCall;
            }
        }

        if (!empty($toWrite)) {
            \Safe\file_put_contents($filePath, implode('', $toWrite), FILE_APPEND | LOCK_EX);
        }

        // Clear the plural translations cache
        self::$pluralTranslations = [];
    }

    /**
     * Warn about invalid context usage.
     */
    protected function warnInvalidContext(string $context, string $message, ?string $domain = null): void
    {
        $domain ??= $this->translator->getDefaultDomain();
        $contexts = $this->translator->getConfiguredContexts();
        $this->logger->warningInternal('Invalid translation context used', [
            'context' => $context,
            'message' => $message,
            'domain' => $domain,
            'available_contexts' => $contexts,
        ]);
    }

    /**
     * Normalize context for consistency.
     */
    protected function normalizeContext(?string $context): ?string
    {
        if (null === $context) {
            return null;
        }

        // Trim and convert to lowercase for consistency
        $normalized = trim(strtolower($context));

        // Return null for empty strings
        return '' === $normalized ? null : $normalized;
    }

    /**
     * Generate context-aware comment for translations.
     */
    protected function generateContextComment(string $baseComment, ?string $context): string
    {
        if (null === $context) {
            return $baseComment;
        }

        return $baseComment." [context: {$context}]";
    }
}
