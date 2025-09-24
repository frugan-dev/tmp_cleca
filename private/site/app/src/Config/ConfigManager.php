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

namespace App\Config;

use Illuminate\Config\Repository;
use Laminas\Stdlib\ArrayUtils;

class ConfigManager extends Repository
{
    private string $configPath;
    private string $mergeStrategy = 'merge'; // 'arrayutils', 'union', 'merge', 'recursive'

    /**
     * Override the get method to support literal dot keys.
     *
     * @param mixed      $key
     * @param null|mixed $default
     */
    #[\Override]
    public function get($key, $default = null)
    {
        // First try with the standard parent method
        $result = parent::get($key, null);

        if (null !== $result) {
            return $result;
        }

        // If it doesn't find anything, try searching for keys with literal dots
        $searchResult = $this->searchWithLiteralDots($key, $this->items, false);

        return $searchResult['found'] ? $searchResult['value'] : $default;
    }

    /**
     * Override the has method to support literal dot keys.
     *
     * @param mixed $key
     */
    #[\Override]
    public function has($key): bool
    {
        // First try with the standard parent method
        if (parent::has($key)) {
            return true;
        }

        // If it doesn't find anything, try searching for keys with literal dots
        $searchResult = $this->searchWithLiteralDots($key, $this->items, true);

        return $searchResult['found'];
    }

    public function loadConfigurationFiles(string $path): void
    {
        $this->configPath = $path;

        // Load the basic configuration files first
        foreach ($this->getConfigurationFiles() as $fileKey => $filePath) {
            $config = $this->loadConfigFile($filePath);
            $this->set($fileKey, $config);
        }

        // Load configurations for defined environment
        if (\defined('_APP_ENV')) {
            $this->loadEnvironmentConfigs(_APP_ENV);
        }

        // Load environment configurations from $_SERVER
        if (!empty($_SERVER['APP_ENV'])) {
            $this->loadEnvironmentConfigs($_SERVER['APP_ENV']);

            // Also load combination _APP_ENV + $_SERVER['APP_ENV']
            if (\defined('_APP_ENV')) {
                $combinedEnv = _APP_ENV.'.'.$_SERVER['APP_ENV'];
                $this->loadEnvironmentConfigs($combinedEnv);
            }
        }

        // Load HTTP_HOST based configurations
        if (!empty($_SERVER['HTTP_HOST'])) {
            $this->loadEnvironmentConfigs($_SERVER['HTTP_HOST']);
        }

        // Load configurations based on REMOTE_ADDR
        if (!empty($_SERVER['REMOTE_ADDR'])) {
            $this->loadEnvironmentConfigs($_SERVER['REMOTE_ADDR']);
        }
    }

    /**
     * Set the merge strategy for configurations.
     *
     * http://codelegance.com/array-merging-in-php/
     *
     * ArrayUtils::merge:
     * If an integer key exists in both arrays and preserveNumericKeys is false,
     * the value from the second array will be appended to the first array.
     * If both values are arrays, they are merged together,
     * else the value of the second array overwrites the one of the first array.
     */
    public function setMergeStrategy(string $strategy): void
    {
        $validStrategies = ['arrayutils', 'union', 'merge', 'recursive'];
        if (!\in_array($strategy, $validStrategies, true)) {
            throw new \InvalidArgumentException("Invalid merge strategy: {$strategy}");
        }
        $this->mergeStrategy = $strategy;
    }

    /**
     * Find configuration value using a list of prefixes as fallback.
     * Tries each prefix + separator + suffix combination until a value is found.
     *
     * https://www.php.net/manual/en/language.operators.comparison.php#language.operators.comparison.coalesce
     * https://stackoverflow.com/a/3803347/3929620
     * https://stackoverflow.com/a/18646568/3929620
     *
     * @param array      $prefixes List of prefixes to try
     * @param string     $suffix   The suffix to append
     * @param null|mixed $default
     *
     * @return mixed The first found value or null
     */
    public function getWithFallback(array $prefixes, string $suffix = '', $default = null): mixed
    {
        foreach ($prefixes as $prefix) {
            $key = $prefix.'.'.$suffix;
            if ($this->has($key)) {
                return $this->get($key, $default);
            }
        }

        return $default;
    }

    /**
     * Detects conflicts between scalar keys and dot notation paths.
     * Also detects conflicts between dot notation keys where one is a prefix of another.
     *
     * Example:
     * - ['cache' => true, 'cache.file' => '/path'] → conflict for 'cache' root key
     * - ['storage.adapter' => 'value', 'storage.adapter.filesystem.path' => '/path'] → conflict for 'storage.adapter'
     *
     * @param array $config The configuration array to analyze
     *
     * @return array Array of keys that have conflicts (both root keys and full dot notation keys)
     */
    protected function detectKeyConflicts(array $config): array
    {
        $conflicts = [];
        $keys = array_keys($config);

        foreach ($keys as $key) {
            if (!\is_string($key)) {
                continue;
            }

            // For each key, check if any other key starts with this key followed by a dot
            foreach ($keys as $otherKey) {
                if (!\is_string($otherKey) || $key === $otherKey) {
                    continue;
                }

                // Check if otherKey starts with key followed by a dot
                if (str_starts_with($otherKey, $key.'.')) {
                    // There's a conflict: $key should remain as literal because $otherKey extends it
                    $conflicts[] = $key;

                    // If $key contains dots, also add its root for conflict detection
                    if (str_contains($key, '.')) {
                        $rootKey = explode('.', $key)[0];
                        $conflicts[] = $rootKey;
                    }

                    break; // No need to check further for this key
                }
            }
        }

        return array_unique($conflicts);
    }

    /**
     * Detects if an array contains dot notation keys.
     *
     * @param array $config The configuration array to check
     *
     * @return array Returns ['hasDotNotation' => bool, 'dotKeys' => array]
     */
    protected function analyzeDotNotation(array $config): array
    {
        $dotKeys = [];
        $hasDotNotation = false;

        foreach ($config as $key => $value) {
            if (\is_string($key) && str_contains($key, '.')) {
                $dotKeys[] = $key;
                $hasDotNotation = true;
            }
        }

        return [
            'hasDotNotation' => $hasDotNotation,
            'dotKeys' => $dotKeys,
        ];
    }

    /**
     * Expands dot notation keys in configuration arrays to nested arrays.
     * Handles conflicts intelligently: if a scalar key conflicts with a dot notation path,
     * the scalar key is preserved and dot notation keys are kept as literals.
     *
     * Example:
     * ['cache' => true, 'cache.file' => '/path'] stays as is (no expansion due to conflict)
     * ['handlers.file.level' => 'debug'] becomes ['handlers' => ['file' => ['level' => 'debug']]]
     *
     * @param array $config The configuration array with potential dot notation keys
     *
     * @return array The expanded configuration array
     */
    protected function expandDotNotationKeys(array $config): array
    {
        // First, detect conflicts between scalar keys and dot notation paths
        $conflicts = $this->detectKeyConflicts($config);

        $expanded = [];

        foreach ($config as $key => $value) {
            if (\is_string($key) && str_contains($key, '.')) {
                // Check if this dot notation key conflicts with existing scalar keys
                $rootKey = explode('.', $key)[0];

                if (\in_array($rootKey, $conflicts, true)) {
                    // There's a conflict, keep the dot notation key as literal
                    $expanded[$key] = $value;
                } else {
                    // No conflict, expand it
                    $this->setNestedValue($expanded, $key, $value);
                }
            } else {
                // Regular key, just set it directly
                $expanded[$key] = $value;
            }
        }

        return $expanded;
    }

    /**
     * Sets a nested value in an array using dot notation.
     *
     * @param array  $array The array to modify (passed by reference)
     * @param string $key   The dot notation key
     * @param mixed  $value The value to set
     */
    protected function setNestedValue(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        foreach ($keys as $k) {
            if (!isset($current[$k]) || !\is_array($current[$k])) {
                $current[$k] = [];
            }
            $current = &$current[$k];
        }

        $current = $value;
    }

    /**
     * Applies dot notation overrides to a base array.
     * Only the specific paths defined in dot notation are overridden.
     * Handles conflicts intelligently to preserve scalar values.
     *
     * @param array $base         The base configuration array
     * @param array $dotOverrides Array with dot notation keys and their values
     *
     * @return array The modified base array
     */
    protected function applyDotNotationOverrides(array $base, array $dotOverrides): array
    {
        $result = $base;

        foreach ($dotOverrides as $dotKey => $value) {
            $this->setNestedValueSafely($result, $dotKey, $value);
        }

        return $result;
    }

    /**
     * Sets a nested value in an array using dot notation, preserving existing scalar values.
     *
     * @param array  $array The array to modify (passed by reference)
     * @param string $key   The dot notation key
     * @param mixed  $value The value to set
     */
    protected function setNestedValueSafely(array &$array, string $key, $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;

        // Navigate through all keys except the last one
        for ($i = 0; $i < \count($keys) - 1; ++$i) {
            $k = $keys[$i];

            // If the current key doesn't exist, create an array
            if (!isset($current[$k])) {
                $current[$k] = [];
            }
            // If it exists but is not an array, we have a conflict
            // In this case, we cannot set the nested value, so we return early
            elseif (!\is_array($current[$k])) {
                // Cannot override a scalar value with a nested path
                return;
            }

            $current = &$current[$k];
        }

        // Set the final value
        $finalKey = end($keys);
        $current[$finalKey] = $value;
    }

    /**
     * Merge configurations using different strategies.
     * Handles dot notation intelligently based on the strategy and presence of dot keys.
     */
    protected function mergeConfigs(array $base, array $override): array
    {
        // Analyze both arrays for dot notation
        $baseAnalysis = $this->analyzeDotNotation($base);
        $overrideAnalysis = $this->analyzeDotNotation($override);

        // Expand dot notation in both arrays
        $expandedBase = $baseAnalysis['hasDotNotation']
            ? $this->expandDotNotationKeys($base) : $base;

        // If override has dot notation, we need special handling
        if ($overrideAnalysis['hasDotNotation']) {
            return $this->handleDotNotationMerge($expandedBase, $override, $overrideAnalysis['dotKeys']);
        }

        // If no dot notation in override, expand override and use normal strategy
        $expandedOverride = $this->expandDotNotationKeys($override);

        switch ($this->mergeStrategy) {
            case 'union':
                return $expandedOverride + $expandedBase;

            case 'arrayutils':
                if (class_exists(ArrayUtils::class)) {
                    return ArrayUtils::merge($expandedBase, $expandedOverride, true);
                }
                // lack of break or return, hence fall-through to the next case 'recursive'

                // no break
            case 'recursive':
                return array_merge_recursive($expandedBase, $expandedOverride);

            case 'merge':
            default:
                return array_merge($expandedBase, $expandedOverride);
        }
    }

    /**
     * Handles merging when override contains dot notation keys.
     * Applies different logic based on the merge strategy.
     * Handles conflicts between scalar keys and dot notation intelligently.
     *
     * @param array $expandedBase The base array (already expanded)
     * @param array $override     The original override array (with dot notation)
     * @param array $dotKeys      List of dot notation keys in override
     *
     * @return array The merged result
     */
    protected function handleDotNotationMerge(array $expandedBase, array $override, array $dotKeys): array
    {
        // Detect conflicts in the override array
        $overrideConflicts = $this->detectKeyConflicts($override);

        // Separate dot notation keys from regular keys, considering conflicts
        $dotNotationOverrides = [];
        $regularOverrides = [];

        foreach ($override as $key => $value) {
            if (\in_array($key, $dotKeys, true)) {
                // This is a dot notation key
                $rootKey = explode('.', (string) $key)[0];

                // If there's a conflict, treat it as a literal key (regular override)
                if (\in_array($rootKey, $overrideConflicts, true)) {
                    $regularOverrides[$key] = $value;
                } else {
                    $dotNotationOverrides[$key] = $value;
                }
            } else {
                $regularOverrides[$key] = $value;
            }
        }

        // Start with the base
        $result = $expandedBase;

        // Apply regular overrides first using the normal strategy
        if (!empty($regularOverrides)) {
            // For regular overrides, we need to expand only non-conflicting keys
            $expandedRegularOverrides = $this->expandDotNotationKeys($regularOverrides);

            switch ($this->mergeStrategy) {
                case 'union':
                    $result = $expandedRegularOverrides + $result;

                    break;

                case 'arrayutils':
                    if (class_exists(ArrayUtils::class)) {
                        $result = ArrayUtils::merge($result, $expandedRegularOverrides, true);
                    } else {
                        $result = array_merge_recursive($result, $expandedRegularOverrides);
                    }

                    break;

                case 'recursive':
                    $result = array_merge_recursive($result, $expandedRegularOverrides);

                    break;

                case 'merge':
                default:
                    $result = array_merge($result, $expandedRegularOverrides);

                    break;
            }
        }

        // Apply dot notation overrides (these always do deep merge regardless of strategy)
        if (!empty($dotNotationOverrides)) {
            $result = $this->applyDotNotationOverrides($result, $dotNotationOverrides);
        }

        return $result;
    }

    protected function getConfigurationFiles(?string $environment = null): array
    {
        $path = $this->configPath;

        if ($environment) {
            $path .= '/'.$environment;
        }

        if (!is_dir($path)) {
            return [];
        }

        $files = [];
        $extensions = ['php', 'ini', 'json', 'xml', 'yaml', 'yml', 'properties'];

        foreach ($extensions as $ext) {
            $pattern = $path.'/*.'.$ext;
            $matchedFiles = \Safe\glob($pattern);

            if ($matchedFiles) {
                foreach ($matchedFiles as $file) {
                    $fileKey = pathinfo((string) $file, PATHINFO_FILENAME);
                    $files[$fileKey] = $file;
                }
            }
        }

        return $files;
    }

    protected function loadConfigFile(string $filePath): array
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);

        switch (strtolower($extension)) {
            case 'php':
                return require $filePath;

            case 'json':
                $content = \Safe\file_get_contents($filePath);

                return \Safe\json_decode($content, true) ?: [];

            case 'ini':
            case 'properties':
                return \Safe\parse_ini_file($filePath, true) ?: [];

            case 'yaml':
            case 'yml':
                if (\function_exists('yaml_parse_file')) {
                    return \Safe\yaml_parse_file($filePath) ?: [];
                }

                throw new \RuntimeException('YAML extension not installed');

            case 'xml':
                $xml = \Safe\simplexml_load_file($filePath);

                return $xml ? \Safe\json_decode(\Safe\json_encode($xml), true) : [];

            default:
                return [];
        }
    }

    private function loadEnvironmentConfigs(string $environment): void
    {
        $envPath = $this->configPath.'/'.$environment;

        if (!is_dir($envPath)) {
            return;
        }

        foreach ($this->getConfigurationFiles($environment) as $fileKey => $filePath) {
            $envConfig = $this->loadConfigFile($filePath);
            $baseConfig = $this->get($fileKey, []);

            $mergedConfig = $this->mergeConfigs($baseConfig, $envConfig);
            $this->set($fileKey, $mergedConfig);
        }
    }

    /**
     * Unified method to search for keys containing literal dots.
     * Prioritizes exact literal key matches before attempting nested searches.
     *
     * @param string $key           The dot-separated key to search for
     * @param array  $data          The array to search in
     * @param bool   $existenceOnly If true, only check existence; if false, return the actual value
     *
     * @return array Returns ['found' => bool, 'value' => mixed]
     */
    private function searchWithLiteralDots(string $key, array $data, bool $existenceOnly = false): array
    {
        // First, try to find an exact match for the complete key as literal
        if (\array_key_exists($key, $data)) {
            return [
                'found' => true,
                'value' => $existenceOnly ? null : $data[$key],
            ];
        }

        $segments = explode('.', $key);
        $numSegments = \count($segments);

        // Try all possible combinations of segments for nested search
        for ($i = 0; $i < $numSegments; ++$i) {
            // Constructs the current key by joining the first $i+1 segments
            $currentKey = implode('.', \array_slice($segments, 0, $i + 1));
            $remainingSegments = \array_slice($segments, $i + 1);

            // Check if this key exists in the current array
            if (\array_key_exists($currentKey, $data)) {
                $currentData = $data[$currentKey];

                // If there are no more segments, we have found what we're looking for
                if (empty($remainingSegments)) {
                    return [
                        'found' => true,
                        'value' => $existenceOnly ? null : $currentData,
                    ];
                }

                // If there are segments remaining, continue searching recursively
                if (\is_array($currentData)) {
                    $nestedResult = $this->searchInNestedArray($currentData, $remainingSegments, $existenceOnly);
                    if ($nestedResult['found']) {
                        return $nestedResult;
                    }
                }
                // If currentData is not an array but we have remaining segments,
                // check if there are literal keys that extend this path
                else {
                    // Look for literal keys that match the full remaining path
                    $fullRemainingPath = $currentKey.'.'.implode('.', $remainingSegments);
                    if (\array_key_exists($fullRemainingPath, $data)) {
                        return [
                            'found' => true,
                            'value' => $existenceOnly ? null : $data[$fullRemainingPath],
                        ];
                    }
                }
            }
        }

        // Only try prefix matching if we haven't found an exact match through nested search
        return $this->searchWithPrefixMatch($key, $data, $existenceOnly);
    }

    /**
     * Search for keys that match as a prefix (for partial dot notation access).
     * This handles cases like searching for 'mail.smtp' when we have 'mail.smtp.host'.
     *
     * @param string $key           The dot-separated key to search for
     * @param array  $data          The array to search in
     * @param bool   $existenceOnly If true, only check existence; if false, return matching values
     *
     * @return array Returns ['found' => bool, 'value' => mixed]
     */
    private function searchWithPrefixMatch(string $key, array $data, bool $existenceOnly = false): array
    {
        $segments = explode('.', $key);
        $numSegments = \count($segments);

        // Try all possible combinations of segments
        for ($i = 0; $i < $numSegments; ++$i) {
            $currentKey = implode('.', \array_slice($segments, 0, $i + 1));
            $remainingSegments = \array_slice($segments, $i + 1);

            // Check if this key exists in the current array
            if (\array_key_exists($currentKey, $data) && \is_array($data[$currentKey])) {
                $currentData = $data[$currentKey];

                // If there are no remaining segments, return the whole array
                if (empty($remainingSegments)) {
                    return [
                        'found' => true,
                        'value' => $existenceOnly ? null : $currentData,
                    ];
                }

                // Look for keys that start with the remaining path
                $searchPrefix = implode('.', $remainingSegments);
                $matches = $this->findKeysWithPrefix($currentData, $searchPrefix);

                if (!empty($matches)) {
                    if ($existenceOnly) {
                        return ['found' => true, 'value' => null];
                    }

                    // If we found exactly one match and it's a complete match, return its value
                    if (1 === \count($matches) && \array_key_exists($searchPrefix, $currentData)) {
                        return ['found' => true, 'value' => $currentData[$searchPrefix]];
                    }

                    // Transform the matches to remove the prefix and create a clean nested structure
                    $cleanMatches = $this->cleanPrefixFromMatches($matches, $searchPrefix);

                    return ['found' => true, 'value' => $cleanMatches];
                }

                // Continue searching recursively in nested arrays
                $nestedResult = $this->searchInNestedArray($currentData, $remainingSegments, $existenceOnly);
                if ($nestedResult['found']) {
                    return $nestedResult;
                }
            }
        }

        return ['found' => false, 'value' => null];
    }

    /**
     * Clean the prefix from matching keys to create a proper nested structure.
     *
     * @param array  $matches The array of matching key-value pairs
     * @param string $prefix  The prefix to remove from keys
     *
     * @return array Clean array with prefix removed from keys
     */
    private function cleanPrefixFromMatches(array $matches, string $prefix): array
    {
        $cleaned = [];
        $prefixLength = \strlen($prefix);

        foreach ($matches as $key => $value) {
            if (0 === strncmp((string) $key, $prefix, $prefixLength)) {
                if ($key === $prefix) {
                    // Exact match - this shouldn't happen in prefix matching, but handle it
                    return $value;
                }
                if (\strlen((string) $key) > $prefixLength && '.' === $key[$prefixLength]) {
                    // Remove the prefix and the following dot
                    $cleanKey = substr((string) $key, $prefixLength + 1);
                    $cleaned[$cleanKey] = $value;
                }
            }
        }

        return $cleaned;
    }

    /**
     * Find all keys in an array that start with a given prefix.
     *
     * @param array  $data   The array to search in
     * @param string $prefix The prefix to search for
     *
     * @return array Array of matching key-value pairs
     */
    private function findKeysWithPrefix(array $data, string $prefix): array
    {
        $matches = [];
        $prefixLength = \strlen($prefix);

        foreach ($data as $key => $value) {
            // Check if key starts with our prefix
            if (0 === strncmp((string) $key, $prefix, $prefixLength)) {
                // Check if it's an exact match or starts with prefix followed by a dot
                if ($key === $prefix || (\strlen((string) $key) > $prefixLength && '.' === $key[$prefixLength])) {
                    $matches[$key] = $value;
                }
            }
        }

        return $matches;
    }

    /**
     * Unified recursive search in a nested array that may contain keys with literal dots.
     * Prioritizes exact literal key matches before attempting nested searches.
     *
     * @param array $data          The array to search in
     * @param array $segments      The remaining key segments to search for
     * @param bool  $existenceOnly If true, only check existence; if false, return the actual value
     *
     * @return array Returns ['found' => bool, 'value' => mixed]
     */
    private function searchInNestedArray(array $data, array $segments, bool $existenceOnly = false): array
    {
        $searchKey = implode('.', $segments);

        // First, try to find an exact match for the complete remaining key as literal
        if (\array_key_exists($searchKey, $data)) {
            return [
                'found' => true,
                'value' => $existenceOnly ? null : $data[$searchKey],
            ];
        }

        $numSegments = \count($segments);

        // Try all possible combinations of remaining segments
        for ($i = 0; $i < $numSegments; ++$i) {
            // Constructs the current key by joining the first $i+1 remaining segments
            $currentKey = implode('.', \array_slice($segments, 0, $i + 1));
            $remainingSegments = \array_slice($segments, $i + 1);

            // Check if this key exists in the current array
            if (\array_key_exists($currentKey, $data)) {
                $currentData = $data[$currentKey];

                // If there are no more segments, we have found what we're looking for
                if (empty($remainingSegments)) {
                    return [
                        'found' => true,
                        'value' => $existenceOnly ? null : $currentData,
                    ];
                }

                // If there are any remaining segments, continue searching recursively
                if (\is_array($currentData)) {
                    $result = $this->searchInNestedArray($currentData, $remainingSegments, $existenceOnly);
                    if ($result['found']) {
                        return $result;
                    }
                }
                // If currentData is not an array but we have remaining segments,
                // check if there are literal keys that extend this path in the current data
                else {
                    $fullRemainingPath = $currentKey.'.'.implode('.', $remainingSegments);
                    if (\array_key_exists($fullRemainingPath, $data)) {
                        return [
                            'found' => true,
                            'value' => $existenceOnly ? null : $data[$fullRemainingPath],
                        ];
                    }
                }
            }
        }

        // Try prefix matching in nested context as well, but only if no exact match found
        if (!empty($segments)) {
            $searchPrefix = implode('.', $segments);

            // Only do prefix matching if there's no exact key match
            if (!\array_key_exists($searchPrefix, $data)) {
                $matches = $this->findKeysWithPrefix($data, $searchPrefix);

                if (!empty($matches)) {
                    if ($existenceOnly) {
                        return ['found' => true, 'value' => null];
                    }

                    // Clean the matches to remove prefix
                    $cleanMatches = $this->cleanPrefixFromMatches($matches, $searchPrefix);

                    return ['found' => true, 'value' => $cleanMatches];
                }
            }
        }

        return ['found' => false, 'value' => null];
    }
}
