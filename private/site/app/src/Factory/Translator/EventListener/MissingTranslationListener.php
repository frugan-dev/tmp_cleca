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

class MissingTranslationListener extends BaseTranslationListener
{
    /**
     * In-memory cache to avoid processing the same translation multiple times per request.
     */
    private array $processedTranslations = [];

    /**
     * Handle missing translations (when a translation is not found).
     * This method is called when a translation is requested but not found in the translation files.
     */
    public function handleMissingTranslation(string $message, ?string $context = null, ?string $locale = null, ?string $domain = null): void
    {
        // Check if listener is enabled
        if (!$this->isEnabled('missing_translation')) {
            return;
        }

        // Skip empty messages
        if (empty($message)) {
            return;
        }

        // Validate context if provided
        if (null !== $context && !$this->translator->isValidContext($context)) {
            $this->warnInvalidContext($context, $message, $domain);
            // Continue processing but log the warning
        }

        // Normalize context
        $context = $this->normalizeContext($context);

        // Use default domain if none provided
        $domain ??= $this->translator->getDefaultDomain();

        // Get current locale if not provided
        if (null === $locale) {
            $locale = $this->translator->localeCharset ?? $this->translator->locale ?? null;

            // Still null? Get from session or config
            if (null === $locale) {
                $langArr = $this->getConfigWithFallback('arr', []);
                $fallbackId = $this->getConfigWithFallback('fallbackId', 1);
                $locale = $langArr[$fallbackId]['localeCharset'] ?? 'en_US';
            }
        }

        // Check if current language is a source language - if so, skip
        if ($this->isSourceLanguage($locale)) {
            $this->logger->debugInternal('Skipping source language', [
                'message' => $message,
                'context' => $context,
                'locale' => $locale,
                'domain' => $domain,
            ]);

            return;
        }

        // Analyze context to determine if this is a plural translation
        $contextAnalysis = $this->analyzeTranslationContext($message, $context, $domain);
        $isPlural = $contextAnalysis['isPlural'];
        $callingFunction = $contextAnalysis['callingFunction'];
        $pluralInfo = $contextAnalysis['pluralInfo'];

        // Create a unique key for this translation request
        $translationKey = $this->createTranslationKey($message, $context, $locale, $domain, $isPlural);

        // Skip if already processed in this request
        if (isset($this->processedTranslations[$translationKey])) {
            return;
        }

        // Mark as processed
        $this->processedTranslations[$translationKey] = true;

        $this->logger->debugInternal('Processing missing translation', [
            'message' => $message,
            'context' => $context,
            'locale' => $locale,
            'domain' => $domain,
            'is_plural' => $isPlural,
            'calling_function' => $callingFunction,
            'plural_info' => $pluralInfo,
        ]);

        // Check if translation actually exists in the translation files
        if ($this->translationExistsInFiles($message, $context, $locale, $domain, $isPlural, $pluralInfo)) {
            $this->logger->debugInternal('Translation exists, skipping', [
                'message' => $message,
                'context' => $context,
                'locale' => $locale,
                'domain' => $domain,
                'is_plural' => $isPlural,
            ]);

            return;
        }

        // For plural translations, store them for later processing
        if ($isPlural && $pluralInfo && isset($pluralInfo['singular'], $pluralInfo['plural'])) {
            $comment = $this->generateContextComment(
                \sprintf('Missing translation [locale: %s]', $locale),
                $context
            );
            $this->storePluralTranslation(
                $pluralInfo['singular'],
                $pluralInfo['plural'],
                $context,
                $comment,
                $domain
            );

            $this->logger->infoInternal('Stored plural translation for later processing', [
                'singular' => $pluralInfo['singular'],
                'plural' => $pluralInfo['plural'],
                'context' => $context,
                'locale' => $locale,
                'domain' => $domain,
            ]);

            // Register shutdown function to write plural translations
            static $registered = false;
            if (!$registered) {
                $registered = true;
                register_shutdown_function(function (): void {
                    $filePath = $this->getFilePath('missing_translation', 'missing_translation.php');
                    $this->writePendingPluralTranslations($filePath);
                });
            }

            return;
        }

        // Check if this is part of a plural translation we've already seen
        $pluralData = $this->isPartOfPluralTranslation($message, $context, $domain);
        if ($pluralData) {
            // Skip individual processing as it's part of a plural
            return;
        }

        // Check cache to avoid writing duplicates across requests
        if (null !== $this->cache) {
            $cacheKey = $this->getCacheKey('missing_translation', $message, $context, $locale, $domain);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return;
            }
        }

        // Get missing translation file path
        $missingTranslationFile = $this->getFilePath('missing_translation', 'missing_translation.php');

        $this->initializeFile($missingTranslationFile, 'Missing translations for Poedit extraction');

        $contents = \Safe\file_get_contents($missingTranslationFile);

        // Check if this translation is already in the file
        $found = $this->isTranslationInFile($contents, $message, $context, $domain);

        if (!$found) {
            // Generate the function call string for Poedit to pick up
            $comment = $this->generateContextComment(
                \sprintf('Missing translation [locale: %s]', $locale),
                $context
            );
            $functionCall = $this->generateFunctionCall($message, $context, $domain, $comment);
            \Safe\file_put_contents($missingTranslationFile, $functionCall, FILE_APPEND | LOCK_EX);

            // Log when we actually add something new
            $this->logger->infoInternal('Added missing translation', [
                'message' => $message,
                'context' => $context,
                'locale' => $locale,
                'domain' => $domain,
                'is_plural' => $isPlural,
                'calling_function' => $callingFunction,
            ]);
        }

        // Cache this entry to avoid repeated processing across requests
        if (null !== $this->cache) {
            $this->cache->saveItem($cacheItem, true);
        }
    }

    /**
     * Analyze the translation context by examining the call stack.
     * This determines if the translation call is from a plural function and other context information.
     */
    private function analyzeTranslationContext(string $message, ?string $context, ?string $domain): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 15);

        $isPlural = false;
        $callingFunction = 'unknown';
        $sourceInfo = null;
        $pluralInfo = null;

        // Look through the call stack to understand the context
        foreach ($trace as $index => $frame) {
            if (!isset($frame['function'])) {
                continue;
            }

            $function = $frame['function'];

            // Identify the calling translation function
            if (\in_array($function, ['__', '_e', '_x', '_ex', '_n', '_en', '_nx', '_enx', 'translate', 'translatePlural'], true)) {
                $callingFunction = $function;

                // Determine if it's a plural function
                if (\in_array($function, ['_n', '_en', '_nx', '_enx', 'translatePlural'], true)) {
                    $isPlural = true;

                    // Try to get the plural form from the current frame's args
                    if (isset($frame['args']) && \count($frame['args']) >= 2) {
                        $pluralInfo = [
                            'singular' => $frame['args'][0] ?? $message,
                            'plural' => $frame['args'][1] ?? $message,
                            'number' => $frame['args'][2] ?? null,
                        ];
                    }
                }

                // Get source file information for additional analysis
                if (isset($frame['file'], $frame['line'])) {
                    $sourceInfo = [
                        'file' => $frame['file'],
                        'line' => $frame['line'],
                    ];
                }

                break; // Found the translation function, stop looking
            }
        }

        return [
            'isPlural' => $isPlural,
            'callingFunction' => $callingFunction,
            'sourceInfo' => $sourceInfo,
            'pluralInfo' => $pluralInfo,
        ];
    }

    /**
     * Check if the current locale/language is a source language.
     */
    private function isSourceLanguage(?string $locale): bool
    {
        if (null === $locale) {
            return false;
        }

        // Get source languages from configuration
        $sourceLanguages = $this->getConfigWithFallback('listeners.missing_translation.source_languages', []);

        // If no source languages configured, use fallback language as source
        if (empty($sourceLanguages)) {
            $fallbackId = $this->getConfigWithFallback('fallbackId', 1);
            $langArr = $this->getConfigWithFallback('arr', []);

            if (isset($langArr[$fallbackId]['localeCharset'])) {
                $sourceLanguages = [$langArr[$fallbackId]['localeCharset']];
            }

            // Also check for common English locales as defaults
            if (empty($sourceLanguages)) {
                $sourceLanguages = ['en_US', 'en_US.UTF-8', 'en_GB', 'en_GB.UTF-8', 'en'];
            }
        }

        // Normalize locale for comparison
        $normalizedLocale = str_replace('.UTF-8', '', $locale);

        // Check if current locale matches any source language
        foreach ($sourceLanguages as $sourceLang) {
            $normalizedSource = str_replace('.UTF-8', '', (string) $sourceLang);

            if ($normalizedLocale === $normalizedSource) {
                return true;
            }

            // Check if locale starts with source language (e.g., en_US matches en)
            if (str_starts_with($normalizedLocale, $normalizedSource)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Create a unique key for this translation request.
     */
    private function createTranslationKey(string $message, ?string $context, ?string $locale, ?string $domain, bool $isPlural): string
    {
        return md5($message.'|'.($context ?? 'null').'|'.($locale ?? 'null').'|'.($domain ?? 'null').'|'.($isPlural ? '1' : '0'));
    }

    /**
     * Check if the translation actually exists in the translation files.
     * This is the main method to prevent false positives.
     */
    private function translationExistsInFiles(string $message, ?string $context, ?string $locale, ?string $domain, bool $isPlural, ?array $pluralInfo = null): bool
    {
        try {
            if (null === $locale) {
                $this->logger->warningInternal('No locale provided for translation check', [
                    'message' => $message,
                    'context' => $context,
                    'domain' => $domain,
                ]);

                return false;
            }

            // First check if translation exists normally
            if ($this->translator->hasTranslation($message, $context, $locale, $domain)) {
                return true;
            }

            // For plural translations, we need to check differently
            if ($isPlural) {
                // If we have plural info from context analysis, use it
                if ($pluralInfo && isset($pluralInfo['singular'], $pluralInfo['plural'])) {
                    $singular = $pluralInfo['singular'];
                    $plural = $pluralInfo['plural'];
                } else {
                    // Otherwise, assume the message is both singular and plural
                    $singular = $message;
                    $plural = $message;
                }

                // Check if plural translation exists
                if ($this->translator->hasPluralTranslation($singular, $plural, $context, $locale, $domain)) {
                    return true;
                }

                // Also check with actual translation to be sure
                $testNumbers = [0, 1, 2, 5, 10];
                foreach ($testNumbers as $num) {
                    $translation = $this->translator->translatePlural($singular, $plural, $num, $context, $locale, $domain);

                    // If we get a different translation than the original message, it exists
                    if ($translation !== $singular && $translation !== $plural) {
                        return true;
                    }
                }
            } else {
                // For non-plural translations, also check by attempting translation
                $translation = $this->translator->translate($message, $context, $locale, $domain);
                if ($translation !== $message) {
                    return true;
                }
            }

            return false;
        } catch (\Throwable $e) {
            // Log the error for debugging
            $this->logger->warningInternal('Error checking translation existence', [
                'message' => $message,
                'context' => $context,
                'locale' => $locale,
                'domain' => $domain,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // If we can't check properly, assume it exists to avoid false positives
            return true;
        }
    }
}
