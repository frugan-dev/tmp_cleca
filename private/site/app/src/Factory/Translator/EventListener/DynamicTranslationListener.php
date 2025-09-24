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

class DynamicTranslationListener extends BaseTranslationListener
{
    /**
     * In-memory cache to avoid processing the same translation multiple times per request.
     */
    private array $processedTranslations = [];

    /**
     * Cache for dynamic analysis results to avoid repeated file reading.
     */
    private array $dynamicAnalysisCache = [];

    /**
     * Handle dynamic translations (variables passed to translation functions).
     * This method analyzes the call stack to determine if the translation
     * call is dynamic (using variables) rather than static strings.
     */
    public function handleDynamicTranslation(string $message, ?string $context = null, ?string $locale = null, ?string $domain = null): void
    {
        // Check if listener is enabled
        if (!$this->isEnabled('dynamic_translation')) {
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

        // Create a unique key for this translation request
        $translationKey = $this->createTranslationKey($message, $context, $locale, $domain);

        // Skip if already processed in this request
        if (isset($this->processedTranslations[$translationKey])) {
            return;
        }

        // Mark as processed
        $this->processedTranslations[$translationKey] = true;

        // Analyze call stack to determine if this is a dynamic translation
        $contextAnalysis = $this->analyzeTranslationContext($context);

        if (!$contextAnalysis['isDynamic']) {
            return;
        }

        // For plural translations, store them for later processing
        if ($contextAnalysis['isPlural'] && $contextAnalysis['pluralInfo']
            && isset($contextAnalysis['pluralInfo']['singular'], $contextAnalysis['pluralInfo']['plural'])) {
            $comment = $this->generateContextComment('Dynamic plural translation', $context);
            $this->storePluralTranslation(
                $contextAnalysis['pluralInfo']['singular'],
                $contextAnalysis['pluralInfo']['plural'],
                $context,
                $comment,
                $domain
            );

            $this->logger->infoInternal('Stored dynamic plural translation', [
                'singular' => $contextAnalysis['pluralInfo']['singular'],
                'plural' => $contextAnalysis['pluralInfo']['plural'],
                'context' => $context,
                'domain' => $domain,
            ]);

            // Register shutdown function to write plural translations
            static $registered = false;
            if (!$registered) {
                $registered = true;
                register_shutdown_function(function (): void {
                    $filePath = $this->getFilePath('dynamic_translation', 'dynamic_translation.php');
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
            $cacheKey = $this->getCacheKey('dynamic_translation', $message, $context, $locale, $domain);
            $cacheItem = $this->cache->getItem($cacheKey);
            if ($cacheItem->isHit()) {
                return;
            }
        }

        // Get dynamic translation file path
        $dynamicTranslationFile = $this->getFilePath('dynamic_translation', 'dynamic_translation.php');

        $this->initializeFile($dynamicTranslationFile, 'Dynamic translations for Poedit extraction');

        $contents = \Safe\file_get_contents($dynamicTranslationFile);

        // Check if this translation is already in the file
        $found = $this->isTranslationInFile($contents, $message, $context, $domain);

        if (!$found) {
            // Generate the function call string for Poedit to pick up
            $comment = $this->generateContextComment('Dynamic translation', $context);
            $functionCall = $this->generateFunctionCall($message, $context, $domain, $comment);
            \Safe\file_put_contents($dynamicTranslationFile, $functionCall, FILE_APPEND | LOCK_EX);

            // Only log when we actually add something new
            $this->logger->infoInternal('Added dynamic translation', [
                'message' => $message,
                'context' => $context,
                'domain' => $domain,
                'calling_function' => $contextAnalysis['callingFunction'],
                'source_file' => basename($contextAnalysis['sourceInfo']['file'] ?? 'unknown'),
                'source_line' => $contextAnalysis['sourceInfo']['line'] ?? 'unknown',
            ]);
        }

        // Cache this entry to avoid repeated processing across requests
        if (null !== $this->cache) {
            $this->cache->saveItem($cacheItem, true);
        }
    }

    /**
     * Analyze the translation context by examining the call stack.
     */
    private function analyzeTranslationContext(?string $context): array
    {
        $trace = debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT, 15);

        $isDynamic = false;
        $isPlural = false;
        $callingFunction = 'unknown';
        $sourceInfo = null;
        $pluralInfo = null;

        // Look for the translation function call in the trace
        foreach ($trace as $index => $frame) {
            if (!isset($frame['function'])) {
                continue;
            }

            $function = $frame['function'];

            // Check if this is a translation function
            if (\in_array($function, ['__', '_e', '_x', '_ex', '_n', '_en', '_nx', '_enx', 'translate', 'translatePlural'], true)) {
                $callingFunction = $function;

                // Determine if it's a plural function
                if (\in_array($function, ['_n', '_en', '_nx', '_enx', 'translatePlural'], true)) {
                    $isPlural = true;

                    // Try to get the plural form from the current frame's args
                    if (isset($frame['args']) && \count($frame['args']) >= 2) {
                        $pluralInfo = [
                            'singular' => $frame['args'][0] ?? null,
                            'plural' => $frame['args'][1] ?? null,
                            'number' => $frame['args'][2] ?? null,
                        ];
                    }
                }

                // Get the file and line where the translation function was called
                if (isset($frame['file'], $frame['line'])) {
                    $sourceInfo = [
                        'file' => $frame['file'],
                        'line' => $frame['line'],
                    ];

                    $isDynamic = $this->analyzeSourceCode($frame['file'], $frame['line']);
                }

                break; // Found the translation function, stop looking
            }
        }

        return [
            'isDynamic' => $isDynamic,
            'isPlural' => $isPlural,
            'callingFunction' => $callingFunction,
            'sourceInfo' => $sourceInfo,
            'pluralInfo' => $pluralInfo,
        ];
    }

    /**
     * Create a unique key for this translation request.
     */
    private function createTranslationKey(string $message, ?string $context, ?string $locale, ?string $domain): string
    {
        return md5($message.'|'.($context ?? 'null').'|'.($locale ?? 'null').'|'.$domain);
    }

    /**
     * Analyze the source code at the given file and line to determine
     * if the translation call uses variables.
     */
    private function analyzeSourceCode(string $file, int $line): bool
    {
        // Create cache key for this analysis
        $cacheKey = $file.':'.$line;

        // Return cached result if available
        if (isset($this->dynamicAnalysisCache[$cacheKey])) {
            return $this->dynamicAnalysisCache[$cacheKey];
        }

        $result = false;

        if (!file_exists($file)) {
            $this->dynamicAnalysisCache[$cacheKey] = $result;

            return $result;
        }

        try {
            $lines = \Safe\file($file, FILE_IGNORE_NEW_LINES);
            if (false === $lines || !isset($lines[$line - 1])) {
                $this->dynamicAnalysisCache[$cacheKey] = $result;

                return $result;
            }

            $codeLine = $lines[$line - 1];

            // Check if this line contains sprintf placeholders - if so, be very conservative
            $hasSprintfPlaceholders = \Safe\preg_match('/\b(?:__|_n|_x|_nx)\s*\([^)]*%\d+\$[a-zA-Z]/', $codeLine);

            if ($hasSprintfPlaceholders) {
                // Only check for very obvious dynamic patterns when sprintf placeholders are present
                if (\Safe\preg_match('/\b(?:__|_n|_x|_nx)\s*\(\s*\$[a-zA-Z_][a-zA-Z0-9_]*\s*[,)]/', $codeLine)) {
                    $result = true;
                }
                $this->dynamicAnalysisCache[$cacheKey] = $result;

                return $result;
            }

            // Dynamic patterns to detect
            $dynamicPatterns = [
                // Pattern 1: Variable as first parameter
                '/\b(?:__|_n|_x|_nx)\s*\(\s*\$\w+/',

                // Pattern 2: Concatenation with variables
                '/\b(?:__|_n|_x|_nx)\s*\([^)]*\$\w+\s*\.\s*[\'"]/',
                '/\b(?:__|_n|_x|_nx)\s*\([^)]*[\'"][^\'\"]*[\'"]\s*\.\s*\$\w+/',
                '/\b(?:__|_n|_x|_nx)\s*\([^)]*\w+\s*\([^)]*\)\s*\./',

                // Pattern 3: Function return value as parameter (excluding wrapper functions)
                '/\b(?:__|_n|_x|_nx)\s*\(\s*\w+\s*\([^)]*\)\s*[,)]/',

                // Pattern 4: Array access
                '/\b(?:__|_n|_x|_nx)\s*\(\s*\$\w+\[/',

                // Pattern 5: Object property or method access
                '/\b(?:__|_n|_x|_nx)\s*\(\s*\$\w+->\w+/',

                // Pattern 6: Constants or class constants
                '/\b(?:__|_n|_x|_nx)\s*\(\s*[A-Z_][A-Z0-9_]*::/',

                // Pattern 7: Ternary operators or null coalescing
                '/\b(?:__|_n|_x|_nx)\s*\([^)]*\?\s*[^:)]*\s*:/',
                '/\b(?:__|_n|_x|_nx)\s*\([^)]*\?\?\s*/',
            ];

            foreach ($dynamicPatterns as $pattern) {
                if (\Safe\preg_match($pattern, $codeLine)) {
                    // Additional check for Pattern 3: exclude wrapper functions
                    if (str_contains($pattern, '\w+\s*\([^)]*\)\s*[,)]')) {
                        if (\Safe\preg_match('/\w+\s*\(\s*(?:__|_n|_x|_nx)\s*\(/', $codeLine)) {
                            continue; // Skip wrapper functions like sprintf(__())
                        }
                    }

                    $result = true;

                    break;
                }
            }

            // Pattern 8: Multi-line call - check for variables on next line
            if (!$result && \Safe\preg_match('/\b(?:__|_n|_x|_nx)\s*\(\s*$/', $codeLine)) {
                if (isset($lines[$line]) && \Safe\preg_match('/^\s*\$\w+/', $lines[$line])) {
                    $result = true;
                }
            }
        } catch (\Throwable $e) {
            $this->logger->debugInternal('Error analyzing source code for dynamic patterns', [
                'file' => $file,
                'line' => $line,
                'error' => $e->getMessage(),
            ]);

            // If we can't analyze the source, assume it's not dynamic
            $result = false;
        }

        // Cache the result
        $this->dynamicAnalysisCache[$cacheKey] = $result;

        return $result;
    }
}
