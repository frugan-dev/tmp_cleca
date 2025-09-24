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

namespace App\Helper;

use Carbon\Exceptions\InvalidFormatException;
use Spatie\ArrayToXml\ArrayToXml;

class Arrays extends Helper
{
    // https://stackoverflow.com/a/39637749/3929620
    // https://craftytechie.com/array_map-multidimensional-array/
    public function arrayMapRecursive($callback, array $array)
    {
        $func = function ($item) use (&$func, &$callback) {
            return \is_array($item) ? array_map($func, $item) : \call_user_func($callback, $item);
        };

        return array_map($func, $array);
    }

    // https://stackoverflow.com/a/43635394/3929620
    // https://www.nicesnippets.com/blog/php-array-get-duplicate-values
    public function hasDuplicates(array $array)
    {
        return \count($array) !== \count(array_flip($array));
    }

    // https://stackoverflow.com/a/12236744
    // https://arjunphp.com/php-multidimensional-array-searching/
    public function recursiveArraySearch($needle_key, $needle_value, array $haystack, $returnParentKey = false)
    {
        $RecursiveArrayIterator = new \RecursiveArrayIterator($haystack);
        $RecursiveIteratorIterator = new \RecursiveIteratorIterator($RecursiveArrayIterator, \RecursiveIteratorIterator::SELF_FIRST);

        while ($RecursiveIteratorIterator->valid()) {
            if (!isset($parentKey) || \is_array($RecursiveIteratorIterator->current())) {
                $parentKey = $RecursiveIteratorIterator->key();
            }

            if (null !== $needle_key && null !== $needle_value) {
                if ($RecursiveIteratorIterator->key() === $needle_key && $RecursiveIteratorIterator->current() === $needle_value) {
                    return ($returnParentKey) ? $parentKey : true;
                }
            } elseif (null !== $needle_key) {
                if ($RecursiveIteratorIterator->key() === $needle_key) {
                    return $RecursiveIteratorIterator->current();
                }
            } elseif (null !== $needle_value) {
                if ($RecursiveIteratorIterator->current() === $needle_value) {
                    return ($returnParentKey) ? $parentKey : $RecursiveIteratorIterator->key();
                }
            }

            $RecursiveIteratorIterator->next();
        }

        return false;
    }

    // https://stackoverflow.com/a/40725952/3929620
    public function getArrayDepth($array)
    {
        $depth = 0;
        $iteIte = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($array));

        foreach ($iteIte as $ite) {
            $d = $iteIte->getDepth();
            $depth = $d > $depth ? $d : $depth;
        }

        return $depth;
    }

    // http://stackoverflow.com/a/28181221/3929620
    public function replaceKeys(array $source, array $keyMapping)
    {
        $target = [];

        // https://stackoverflow.com/a/3432266
        array_walk(
            $source,
            function ($v, $k, $keyMapping) use (&$target): void {
                $mappedKey = $keyMapping[$k] ?? $k;
                $target[$mappedKey] = $v;
            },
            $keyMapping
        );

        return $target;
    }

    public function splitStringToArray($string, $regex = '/\r\n|[\r\n]/', $filter = true)
    {
        $array = \Safe\preg_split($regex, (string) $string);

        if ($filter) {
            $array = array_filter($array);
        }

        // http://stackoverflow.com/a/8321709
        // https://www.php.net/manual/en/function.array-unique.php#122226
        return array_keys(array_flip($array));
    }

    // TODO - move to ConfigManager
    public function getExtFromConfig(array $config)
    {
        $array = [];

        foreach (array_keys($config) as $key => $val) {
            if (strpos((string) $val, '|')) {
                $_val = explode('|', (string) $val);
                $array = [...$array, ...$_val];
            } else {
                $array[] = $val;
            }
        }

        $array = array_unique(array_filter($array));

        sort($array);

        return $array;
    }

    /**
     * $data = [
     *         ["firstname" => "Mary", "lastname" => "Johnson", "age" => 25],
     *         ["firstname" => "Amanda", "lastname" => "Miller", "age" => 18],
     *         ...
     * ];.
     *
     * @param unknown_type $data
     * @param unknown_type $field
     *
     * @return number|unknown
     */
    public function usortBy($data, $field)
    {
        if (!\is_array($field)) {
            $field = [$field];
        }
        usort(
            $data,
            function ($a, $b) use ($field) {
                $retval = 0;
                foreach ($field as $fieldname) {
                    if (0 === $retval) {
                        $retval = strnatcmp((string) $a[$fieldname], (string) $b[$fieldname]);
                    }
                }

                return $retval;
            }
        );

        return $data;
    }

    /**
     * $data = [
     *         ["firstname" => "Mary", "lastname" => "Johnson", "age" => 25],
     *         ["firstname" => "Amanda", "lastname" => "Miller", "age" => 18],
     *         ...
     * ];.
     *
     * @param unknown_type $data
     * @param unknown_type $field
     *
     * @return number|unknown
     */
    public function uasortBy($data, $field)
    {
        if (!\is_array($field)) {
            $field = [$field];
        }
        uasort(
            $data,
            function ($a, $b) use ($field) {
                $retval = 0;
                foreach ($field as $fieldname) {
                    if (0 === $retval) {
                        $retval = strnatcmp((string) $a[$fieldname], (string) $b[$fieldname]);
                    }
                }

                return $retval;
            }
        );

        return $data;
    }

    /**
     * https://stackoverflow.com/a/33812174.
     *
     * Sort an array by keys, and additionall sort its array values by keys
     *
     * Does not try to sort an object, but does iterate its properties to
     * sort arrays in properties
     */
    public function deepKsort(mixed $input, mixed $sort_flags = SORT_REGULAR, mixed $reverse = false)
    {
        if (!\is_object($input) && !\is_array($input)) {
            return $input;
        }

        foreach ($input as $k => $v) {
            if (\is_object($v) || \is_array($v)) {
                $input[$k] = $this->deepKsort($v, $sort_flags);
            }
        }

        if (\is_array($input)) {
            if ($reverse) {
                krsort($input, $sort_flags);
            } else {
                ksort($input, $sort_flags);
            }
        }

        // Do not sort objects

        return $input;
    }

    /**
     * Check value to find if it was serialized.
     *
     * If $data is not an string, then returned value will always be false.
     * Serialized data is always a string.
     *
     * @since 2.0.5
     *
     *                      Value to check to see if was serialized
     *
     * @param bool $strict
     *                     Optional. Whether to be strict about the end of the string. Defaults true.
     *
     * @return bool false if not serialized and true if it was
     */
    public function isSerialized(mixed $data, $strict = true)
    {
        // if it isn't a string, it isn't serialized
        if (!\is_string($data)) {
            return false;
        }
        $data = trim($data);
        if ('N;' === $data) {
            return true;
        }
        $length = \strlen($data);
        if ($length < 4) {
            return false;
        }
        if (':' !== $data[1]) {
            return false;
        }
        if ($strict) {
            $lastc = $data[$length - 1];
            if (';' !== $lastc && '}' !== $lastc) {
                return false;
            }
        } else {
            $semicolon = strpos($data, ';');
            $brace = strpos($data, '}');
            // Either ; or } must exist.
            if (false === $semicolon && false === $brace) {
                return false;
            }
            // But neither must be in the first X characters.
            if (false !== $semicolon && $semicolon < 3) {
                return false;
            }
            if (false !== $brace && $brace < 4) {
                return false;
            }
        }
        $token = $data[0];

        switch ($token) {
            case 's':
                if ($strict) {
                    if ('"' !== $data[$length - 2]) {
                        return false;
                    }
                } elseif (!str_contains($data, '"')) {
                    return false;
                }

                // or else fall through
                // no break
            case 'a':
            case 'O':
                return (bool) \Safe\preg_match("/^{$token}:[0-9]+:/s", $data);

            case 'b':
            case 'i':
            case 'd':
                $end = $strict ? '$' : '';

                return (bool) \Safe\preg_match("/^{$token}:[0-9.E-]+;{$end}/", $data);
        }

        return false;
    }

    /**
     * https://github.com/rogervila/array-diff-multidimensional.
     *
     * Returns an array with the differences between $array1 and $array2
     *
     * @return array
     */
    public static function arrayCompare(mixed $array1, mixed $array2)
    {
        $result = [];
        foreach ($array1 as $key => $value) {
            if (!\is_array($array2) || !\array_key_exists($key, $array2)) {
                $result[$key] = $value;

                continue;
            }
            if (\is_array($value)) {
                $recursiveArrayDiff = static::arrayCompare($value, $array2[$key]);
                if (\count($recursiveArrayDiff)) {
                    $result[$key] = $recursiveArrayDiff;
                }

                continue;
            }
            if ($value !== $array2[$key]) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    // https://www.geeksforgeeks.org/how-to-convert-array-to-simplexml-in-php/
    public function toXml($array)
    {
        // https://stackoverflow.com/a/3432266
        \Safe\array_walk_recursive(
            $array,
            function (&$v): void {
                if (\is_string($v)) {
                    try {
                        // https://github.com/briannesbitt/Carbon/issues/450
                        $isValid = $this->Carbon()->parse($v);
                    } catch (InvalidFormatException) {
                    }

                    if (empty($isValid)) {
                        $v = ['_cdata' => $v];
                    }
                }
            }
        );

        return new ArrayToXml(['__numeric' => $array])
            // https://github.com/spatie/array-to-xml/discussions/176
            // ->setNumericTagNamePrefix('row')
            ->prettify()
            ->toXml()
        ;
    }

    // https://www.decodingweb.dev/flatten-array-php
    // https://gist.github.com/SeanCannon/6585889
    public function flatten(array $array)
    {
        $results = [];

        foreach ($array as $key => $value) {
            if (\is_array($value) && !empty($value)) {
                $results = array_merge($results, $this->flatten($value));
            } else {
                $results[$key] = $value;
            }
        }

        return $results;
    }
}
