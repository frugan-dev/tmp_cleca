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

namespace App\Service;

use App\Factory\Translator\TranslatorInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Translation\Extractor\PhpExtractor;
use Symfony\Component\Translation\Loader\CsvFileLoader;
use Symfony\Component\Translation\Loader\IniFileLoader;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Loader\MoFileLoader;
use Symfony\Component\Translation\Loader\PhpFileLoader;
use Symfony\Component\Translation\Loader\PoFileLoader;
use Symfony\Component\Translation\Loader\XliffFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\MessageCatalogue;
use Symfony\Component\Translation\Writer\CsvFileWriter;
use Symfony\Component\Translation\Writer\IniFileWriter;
use Symfony\Component\Translation\Writer\JsonFileWriter;
use Symfony\Component\Translation\Writer\MoFileWriter;
use Symfony\Component\Translation\Writer\PhpFileWriter;
use Symfony\Component\Translation\Writer\PoFileWriter;
use Symfony\Component\Translation\Writer\XliffFileWriter;
use Symfony\Component\Translation\Writer\YamlFileWriter;
use Symfony\Component\Yaml\Yaml;

/**
 * Service for migrating translation files between different formats.
 * Supports conversion between MO, PO, PHP, YAML, JSON, XLIFF, CSV, and INI formats.
 */
class TranslationMigrationService
{
    protected array $loaders = [];
    protected array $writers = [];
    protected array $supportedFormats = ['mo', 'po', 'php', 'yaml', 'json', 'xliff', 'csv', 'ini'];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly TranslatorInterface $translator
    ) {
        $this->initializeLoaders();
        $this->initializeWriters();
    }

    /**
     * Convert translation files from one format to another.
     */
    public function convertFormat(
        string $sourceDir,
        string $targetDir,
        string $sourceFormat,
        string $targetFormat,
        ?array $locales = null,
        ?array $domains = null
    ): array {
        $this->validateFormat($sourceFormat);
        $this->validateFormat($targetFormat);

        if (!is_dir($sourceDir)) {
            throw new \InvalidArgumentException("Source directory does not exist: {$sourceDir}");
        }

        if (!is_dir($targetDir)) {
            \Safe\mkdir($targetDir, 0o755, true);
        }

        $results = [];
        $config = $this->container->get('config');

        // Get locales and domains from config if not provided
        $locales ??= array_column($config->get('lang.arr', []), 'localeCharset');
        $domains ??= $config->get('lang.domains', ['default']);

        foreach ($locales as $locale) {
            foreach ($domains as $domain) {
                $result = $this->convertFile(
                    $sourceDir,
                    $targetDir,
                    $locale,
                    $domain,
                    $sourceFormat,
                    $targetFormat
                );

                if ($result['success']) {
                    $results['converted'][] = $result;
                } else {
                    $results['failed'][] = $result;
                }
            }
        }

        return $results;
    }

    /**
     * Convert a single translation file.
     */
    public function convertFile(
        string $sourceDir,
        string $targetDir,
        string $locale,
        string $domain,
        string $sourceFormat,
        string $targetFormat
    ): array {
        $sourceFile = $this->buildFileName($sourceDir, $locale, $domain, $sourceFormat);
        $targetFile = $this->buildFileName($targetDir, $locale, $domain, $targetFormat);

        $result = [
            'locale' => $locale,
            'domain' => $domain,
            'source' => $sourceFile,
            'target' => $targetFile,
            'success' => false,
            'messages_count' => 0,
            'error' => null,
        ];

        try {
            if (!file_exists($sourceFile)) {
                $result['error'] = 'Source file does not exist';

                return $result;
            }

            // Load messages from source file
            $messages = $this->loadMessages($sourceFile, $sourceFormat, $locale, $domain);

            if (empty($messages)) {
                $result['error'] = 'No messages found in source file';

                return $result;
            }

            // Create message catalogue
            $catalogue = new MessageCatalogue($locale, [$domain => $messages]);

            // Write to target format
            $this->writeMessages($catalogue, $targetDir, $targetFormat);

            $result['success'] = true;
            $result['messages_count'] = \count($messages);
        } catch (\Throwable $e) {
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Extract all translatable strings from PHP files.
     * Useful for creating initial translation files from source code.
     */
    public function extractFromCode(
        array $sourceDirs,
        string $outputDir,
        string $format = 'php',
        ?array $domains = null
    ): array {
        $this->validateFormat($format);

        $extractor = new PhpExtractor();
        $catalogue = new MessageCatalogue('en'); // Template locale

        foreach ($sourceDirs as $sourceDir) {
            if (is_dir($sourceDir)) {
                $extractor->extract($sourceDir, $catalogue);
            }
        }

        $results = [];
        $domains ??= $catalogue->getDomains();

        foreach ($domains as $domain) {
            $messages = $catalogue->all($domain);
            if (!empty($messages)) {
                $targetCatalogue = new MessageCatalogue('template', [$domain => $messages]);
                $this->writeMessages($targetCatalogue, $outputDir, $format);

                $results[] = [
                    'domain' => $domain,
                    'messages_count' => \count($messages),
                    'file' => $this->buildFileName($outputDir, 'template', $domain, $format),
                ];
            }
        }

        return $results;
    }

    /**
     * Validate translation files for completeness and consistency.
     */
    public function validateTranslations(string $localeDir, ?array $locales = null, ?array $domains = null): array
    {
        $config = $this->container->get('config');
        $locales ??= array_column($config->get('lang.arr', []), 'localeCharset');
        $domains ??= $config->get('lang.domains', ['default']);

        $results = [];
        $referenceLocale = $locales[0] ?? 'en_US.UTF-8';
        $referenceMessages = [];

        // Load reference messages (first locale)
        foreach ($domains as $domain) {
            $file = $this->findTranslationFile($localeDir, $referenceLocale, $domain);
            if ($file) {
                $messages = $this->loadMessagesFromFile($file);
                $referenceMessages[$domain] = array_keys($messages);
            }
        }

        // Validate other locales against reference
        foreach ($locales as $locale) {
            if ($locale === $referenceLocale) {
                continue;
            }

            foreach ($domains as $domain) {
                $file = $this->findTranslationFile($localeDir, $locale, $domain);
                $validation = [
                    'locale' => $locale,
                    'domain' => $domain,
                    'file' => $file,
                    'exists' => (bool) $file,
                    'missing_keys' => [],
                    'extra_keys' => [],
                    'empty_translations' => [],
                ];

                if ($file) {
                    $messages = $this->loadMessagesFromFile($file);
                    $currentKeys = array_keys($messages);
                    $referenceKeys = $referenceMessages[$domain] ?? [];

                    $validation['missing_keys'] = array_diff($referenceKeys, $currentKeys);
                    $validation['extra_keys'] = array_diff($currentKeys, $referenceKeys);
                    $validation['empty_translations'] = array_keys(array_filter($messages, fn ($v) => empty($v)));
                }

                $results[] = $validation;
            }
        }

        return $results;
    }

    /**
     * Generate statistics about translation files.
     */
    public function getStatistics(string $localeDir): array
    {
        $config = $this->container->get('config');
        $locales = array_column($config->get('lang.arr', []), 'localeCharset');
        $domains = $config->get('lang.domains', ['default']);

        $stats = [
            'total_files' => 0,
            'total_messages' => 0,
            'by_locale' => [],
            'by_domain' => [],
            'by_format' => [],
        ];

        foreach ($locales as $locale) {
            $stats['by_locale'][$locale] = ['files' => 0, 'messages' => 0];

            foreach ($domains as $domain) {
                if (!isset($stats['by_domain'][$domain])) {
                    $stats['by_domain'][$domain] = ['files' => 0, 'messages' => 0];
                }

                $file = $this->findTranslationFile($localeDir, $locale, $domain);
                if ($file) {
                    $format = pathinfo($file, PATHINFO_EXTENSION);
                    $messages = $this->loadMessagesFromFile($file);
                    $messageCount = \count($messages);

                    ++$stats['total_files'];
                    $stats['total_messages'] += $messageCount;
                    ++$stats['by_locale'][$locale]['files'];
                    $stats['by_locale'][$locale]['messages'] += $messageCount;
                    ++$stats['by_domain'][$domain]['files'];
                    $stats['by_domain'][$domain]['messages'] += $messageCount;

                    if (!isset($stats['by_format'][$format])) {
                        $stats['by_format'][$format] = ['files' => 0, 'messages' => 0];
                    }
                    ++$stats['by_format'][$format]['files'];
                    $stats['by_format'][$format]['messages'] += $messageCount;
                }
            }
        }

        return $stats;
    }

    /**
     * Initialize translation loaders.
     */
    protected function initializeLoaders(): void
    {
        $this->loaders = [
            'mo' => new MoFileLoader(),
            'po' => new PoFileLoader(),
            'php' => new PhpFileLoader(),
            'json' => new JsonFileLoader(),
            'csv' => new CsvFileLoader(),
            'ini' => new IniFileLoader(),
            'xliff' => new XliffFileLoader(),
        ];

        if (class_exists(YamlFileLoader::class)) {
            $this->loaders['yaml'] = new YamlFileLoader();
        }
    }

    /**
     * Initialize translation writers.
     */
    protected function initializeWriters(): void
    {
        $this->writers = [
            'mo' => new MoFileWriter(),
            'po' => new PoFileWriter(),
            'php' => new PhpFileWriter(),
            'json' => new JsonFileWriter(),
            'csv' => new CsvFileWriter(),
            'ini' => new IniFileWriter(),
            'xliff' => new XliffFileWriter(),
        ];

        if (class_exists(YamlFileWriter::class)) {
            $this->writers['yaml'] = new YamlFileWriter();
        }
    }

    /**
     * Load messages from a file.
     */
    protected function loadMessages(string $file, string $format, string $locale, string $domain): array
    {
        if (!isset($this->loaders[$format])) {
            throw new \InvalidArgumentException("Unsupported source format: {$format}");
        }

        $catalogue = $this->loaders[$format]->load($file, $locale, $domain);

        return $catalogue->all($domain);
    }

    /**
     * Load messages from any supported file format.
     */
    protected function loadMessagesFromFile(string $file): array
    {
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        if (!isset($this->loaders[$extension])) {
            return [];
        }

        try {
            $catalogue = $this->loaders[$extension]->load($file, 'temp', 'temp');

            return $catalogue->all('temp');
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Write messages to target format.
     */
    protected function writeMessages(MessageCatalogue $catalogue, string $targetDir, string $format): void
    {
        if (!isset($this->writers[$format])) {
            throw new \InvalidArgumentException("Unsupported target format: {$format}");
        }

        $this->writers[$format]->write($catalogue, $format, ['path' => $targetDir]);
    }

    /**
     * Build file name based on locale, domain, and format.
     */
    protected function buildFileName(string $dir, string $locale, string $domain, string $format): string
    {
        $config = $this->container->get('config');
        $pattern = $config->get('lang.filename_pattern', '{locale}.{extension}');

        // Check for domain-specific patterns
        $domainPattern = $config->get("lang.filename_patterns.{$domain}");
        if ($domainPattern) {
            $pattern = $domainPattern;
        }

        $fileName = str_replace(
            ['{locale}', '{domain}', '{extension}'],
            [$locale, $domain, $format],
            $pattern
        );

        return rtrim($dir, '/').'/'.$fileName;
    }

    /**
     * Find an existing translation file for locale and domain.
     */
    protected function findTranslationFile(string $dir, string $locale, string $domain): ?string
    {
        foreach ($this->supportedFormats as $format) {
            $file = $this->buildFileName($dir, $locale, $domain, $format);
            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * Validate if format is supported.
     */
    protected function validateFormat(string $format): void
    {
        if (!\in_array($format, $this->supportedFormats, true)) {
            throw new \InvalidArgumentException(
                "Unsupported format: {$format}. Supported formats: ".implode(', ', $this->supportedFormats)
            );
        }
    }
}
