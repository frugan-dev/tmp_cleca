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

/**
 * Wrapper for illuminate/config that maintains compatibility with array syntax.
 * Allows both $config['foo.bar'] and $config->get('foo.bar').
 */
class ConfigArrayWrapper implements \ArrayAccess, \Countable, \IteratorAggregate
{
    private array $flattenedConfig = [];
    private array $specialKeys = []; // not-flattened keys

    public function __construct(private readonly Repository $repository)
    {
        $this->buildFlattenedConfig();
    }

    /**
     * Rebuilds the flat array when the repository changes.
     */
    public function refresh(): void
    {
        $this->specialKeys = [];
        $this->buildFlattenedConfig();
    }

    // ArrayAccess implementation to maintain compatibility with $config['key']
    public function offsetExists($offset): bool
    {
        // First check if it exists as a special key (preserved array)
        if (isset($this->specialKeys[$offset])) {
            return isset($this->flattenedConfig[$offset]);
        }

        // Then check in the flatten array
        if (isset($this->flattenedConfig[$offset])) {
            return true;
        }

        // Finally check in the original repository
        return $this->repository->has($offset);
    }

    public function offsetGet($offset): mixed
    {
        // Prima controlla se esiste come chiave speciale (array preservato)
        if (isset($this->specialKeys[$offset], $this->flattenedConfig[$offset])) {
            return $this->flattenedConfig[$offset];
        }

        // Finally use the repository for access with dot notation
        return $this->flattenedConfig[$offset] ?? $this->repository->get($offset);
    }

    public function offsetSet($offset, $value): void
    {
        $this->flattenedConfig[$offset] = $value;
        $this->repository->set($offset, $value);

        // If the value is an array with numeric keys, mark it as special
        if (\is_array($value) && $this->hasNumericKeys($value)) {
            $this->specialKeys[$offset] = true;
        }
    }

    public function offsetUnset($offset): void
    {
        unset($this->flattenedConfig[$offset], $this->specialKeys[$offset]);

        $this->repository->set($offset, null);
    }

    // Countable Implementation
    public function count(): int
    {
        return \count($this->flattenedConfig);
    }

    // IteratorAggregate Implementation
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->flattenedConfig);
    }

    // Methods for direct access to illuminate/config repository
    public function get(string $key, $default = null): mixed
    {
        return $this->repository->get($key, $default);
    }

    public function set(string $key, $value): void
    {
        $this->repository->set($key, $value);
        $this->refresh(); // Update flat array
    }

    public function has(string $key): bool
    {
        return $this->repository->has($key);
    }

    public function all(): array
    {
        return $this->repository->all();
    }

    public function getRepository(): Repository
    {
        return $this->repository;
    }

    // Method to get flat array (for full compatibility)
    public function toArray(): array
    {
        return $this->flattenedConfig;
    }

    /**
     * Debug helper - show preserved special keys.
     */
    public function getSpecialKeys(): array
    {
        return array_keys($this->specialKeys);
    }

    /**
     * Builds a flat array with dot keys to maintain compatibility.
     */
    private function buildFlattenedConfig(): void
    {
        $allConfig = $this->repository->all();
        $this->flattenedConfig = $this->flatten($allConfig);

        // Also keep original keys for numeric arrays
        $this->preserveOriginalArrays($allConfig);
    }

    /**
     * Preserve original arrays with numeric keys.
     * Add keys in "section.arraykey" format to maintain compatibility.
     */
    private function preserveOriginalArrays(array $config, string $prefix = ''): void
    {
        foreach ($config as $key => $value) {
            $currentKey = $prefix ? $prefix.'.'.$key : $key;

            if (\is_array($value)) {
                // If the array has numeric keys, preserve it as such
                if ($this->hasNumericKeys($value)) {
                    $this->specialKeys[$currentKey] = true;
                    $this->flattenedConfig[$currentKey] = $value;
                }

                // Continue recursively for nested arrays
                $this->preserveOriginalArrays($value, $currentKey);
            }
        }
    }

    /**
     * Checks if an array has numeric keys.
     */
    private function hasNumericKeys(array $array): bool
    {
        if (empty($array)) {
            return false;
        }

        $keys = array_keys($array);

        return array_any($keys, fn ($key) => \is_int($key));
    }

    /**
     * Flattens a multidimensional array using dot notation,
     * but skips arrays with numeric keys.
     */
    private function flatten(array $array, string $prepend = ''): array
    {
        $results = [];

        foreach ($array as $key => $value) {
            $newKey = '' !== $prepend ? $prepend.'.'.$key : $key;

            if (\is_array($value) && !empty($value)) {
                // If the array has numeric keys, do not flatten it
                if ($this->hasNumericKeys($value)) {
                    $results[$newKey] = $value;
                } else {
                    $results = array_merge($results, $this->flatten($value, $newKey));
                }
            } else {
                $results[$newKey] = $value;
            }
        }

        return $results;
    }
}
