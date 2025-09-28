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

use Nette\Utils\Strings as NetteStrings;
use Opis\Closure\SerializableClosure;

class Strings extends Helper
{
    // https://stackoverflow.com/a/59095783/3929620
    // https://stackoverflow.com/a/5957835
    // https://www.php.net/manual/en/function.empty.php#103756
    // No warning is generated if the variable does not exist.
    // That means empty() is essentially the concise equivalent to !isset($var) || $var == false
    public function isBlank(mixed $value)
    {
        return empty($value) && !ctype_digit((string) $value);
    }

    public function linearize(
        $data,
        $replacement = '',
        $pattern = [
            // "/(&nbsp;)+/",
            /* "/<br(\s+)?\/?>/i", */
        ]
    ) {
        // include also NetteStrings::normalizeNewLines()
        $data = NetteStrings::normalize((string) $data);

        return NetteStrings::replace($data, array_merge(
            [
                "/\r?\n/",
                '/\s\s+/',
                "/(\r\n|\n\r|\n|\r|\t)/",
            ],
            $pattern
        ), $replacement);
    }

    // DEPRECATED - use $phoneUtil->format($swissNumberProto, PhoneNumberFormat::E164)
    public function phoneUri($data, $prefix = '+')
    {
        $data = $this->linearize($data);

        $data = \Safe\preg_replace('/[^0-9]/', '', (string) $data);

        return $prefix.$data;
    }

    public function serialize($data)
    {
        try {
            return serialize(new SerializableClosure($data));
        } catch (\Exception $e) {
            $this->logger->warning($e->getMessage(), [
                'exception' => $e,
                'error' => $e->getMessage(),
                'text' => $e->getTraceAsString(),
            ]);
        }

        return false;
    }

    public function unserialize($data)
    {
        return unserialize($data);
    }

    // https://stackoverflow.com/a/26612761
    // https://stackoverflow.com/a/40741985
    // https://stackoverflow.com/a/15861106
    // https://github.com/google/php-crc32
    // Because PHP's integer type is signed many crc32 checksums will result in negative integers on 32bit platforms.
    // On 64bit installations all crc32() results will be positive integers though.
    // Note that the CRC32 algorithm should NOT be used for cryptographic purposes.
    public function crc32($value)
    {
        return (int) \sprintf('%u', crc32((string) $value));
    }

    public function unaccent(string $string)
    {
        /*if (extension_loaded('intl') === true) {
            $string = Normalizer::normalize($string, Normalizer::FORM_KD);
        }*/

        if (str_contains($string = htmlentities((string) $string, ENT_QUOTES, 'UTF-8'), '&')) {
            $string = html_entity_decode(\Safe\preg_replace('~&([a-z]{1,2})(?:acute|caron|cedil|circ|grave|lig|orn|ring|slash|tilde|uml);~i', '$1', $string), ENT_QUOTES, 'UTF-8');
        }

        return $string;
    }

    public function webalize(string $string, ?string $regex = null)
    {
        $string = $this->unaccent($string);
        $string = mb_strtolower((string) $string, 'UTF-8');
        $string = \Safe\preg_replace('~[^0-9a-z'.($regex ?? '').']+~iu', '-', $string);

        return trim($string, '-');
    }

    // https://medium.com/coding-cheatsheet/remove-emoji-characters-in-php-236034946f51
    // https://stackoverflow.com/a/1176923/3929620
    public function stripEmoji(string $string)
    {
        foreach ([
            '/[\x{1F600}-\x{1F64F}]/u', // Match Emoticons
            '/[\x{1F300}-\x{1F5FF}]/u', // Match Miscellaneous Symbols and Pictographs
            '/[\x{1F680}-\x{1F6FF}]/u', // Match Transport And Map Symbols
            '/[\x{2600}-\x{26FF}]/u',   // Match Miscellaneous Symbols
            '/[\x{2700}-\x{27BF}]/u',   // Match Dingbats
        ] as $regex) {
            $string = \Safe\preg_replace($regex, '', (string) $string);
        }

        return $string;
    }

    // https://stackoverflow.com/a/29488999/3929620
    // https://stackoverflow.com/a/10762667/3929620
    // https://stackoverflow.com/a/3567218/3929620
    // PHP follows Perl's convention when dealing with arithmetic operations on character variables and not C's.
    // For example, in PHP and Perl $a = 'Z'; $a++; turns $a into 'AA', while in C a = 'Z'; a++; turns a into '[' (ASCII value of 'Z' is 90, ASCII value of '[' is 91).
    // Note that character variables can be incremented but not decremented and even so only plain ASCII letters and digits (a-z, A-Z and 0-9) are supported.
    // Incrementing/decrementing other character variables has no effect, the original string is unchanged.
    public function decrementString(string $string)
    {
        $len = \strlen($string);
        // last character is A or a
        if (\in_array(\ord($string[$len - 1]), [65, 97], true)) {
            if (1 === $len) {
                // one character left
                return null;
            } else {
                // 'ABA'--;  => 'AAZ'; recursive call
                $string = $this->decrementString(substr($string, 0, -1)).'Z';
            }
        } else {
            $string[$len - 1] = \chr(\ord($string[$len - 1]) - 1);
        }

        return $string;
    }
}
