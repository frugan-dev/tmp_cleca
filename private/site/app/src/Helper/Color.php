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

use ColorContrast\ColorContrast;

// https://www.hashbangcode.com/article/color-sorting-php
// https://gist.github.com/alexkingorg/2158428
// https://stackoverflow.com/a/15202130/3929620
class Color extends Helper
{
    #[\Override]
    public function __call($name, $args)
    {
        $namespace = ucwords('\SSNepenthe\ColorUtils\\'.$name, '\\');

        if (\function_exists($namespace)) {
            return \call_user_func_array(
                $namespace,
                $args
            );
        }
    }

    public function contrast(string $color, int $threshold = 128)
    {
        // https://stackoverflow.com/a/71534708/3929620
        // FIXME - Error [E_DEPRECATED=8192] -> Implicit conversion from float 19.5 to int loses precision on line 95 in file /vendor/mischiefcollective/colorjizz/src/MischiefCollective/ColorJizz/Formats/RGB.php
        set_error_handler(
            fn ($errno, $errstr, $errfile, $errline) => true, // Don't execute PHP internal error handler
            E_ALL & ~E_DEPRECATED & ~E_STRICT & ~E_WARNING
        );

        $complimentary = new ColorContrast()->complimentaryTheme($color, $threshold);

        restore_error_handler();

        return ColorContrast::LIGHT === $complimentary;
    }

    public function hexToRgb(string $hex)
    {
        return array_map(
            fn ($c) => hexdec(str_pad((string) $c, 2, $c)),
            str_split(ltrim($hex, '#'), \strlen($hex) > 4 ? 2 : 1)
        );
    }

    // DEPRECATED - use $this->Color()->hue($color) https://github.com/ssnepenthe/color-utils
    public function hueByRgb(int ...$rgb): float|int
    {
        $red = $rgb[0] / 255;
        $green = $rgb[1] / 255;
        $blue = $rgb[2] / 255;

        $min = min($red, $green, $blue);
        $max = max($red, $green, $blue);

        switch ($max) {
            case 0:
                // If the max value is 0.
                $hue = 0;

                break;

            case $min:
                // If the maximum and minimum values are the same.
                $hue = 0;

                break;

            default:
                $delta = $max - $min;
                if ($red === $max) {
                    $hue = 0 + ($green - $blue) / $delta;
                } elseif ($green === $max) {
                    $hue = 2 + ($blue - $red) / $delta;
                } else {
                    $hue = 4 + ($red - $green) / $delta;
                }
                $hue *= 60;
                if ($hue < 0) {
                    $hue += 360;
                }
        }

        return $hue;
    }
}
