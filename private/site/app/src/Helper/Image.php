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

class Image extends Helper
{
    final public const int GRAYSCALE = 0;
    final public const int RGB = 2;
    final public const int PALETTE = 3;
    final public const int GRAYSCALE_ALPHA = 4;
    final public const int RGBA = 6;

    // Bit offsets
    final public const int ColorTypeOffset = 25;

    // https://christianwood.net/posts/png-files-are-complicate/
    // https://ourcodeworld.com/articles/read/473/how-to-check-if-an-image-has-transparency-using-imagick-in-php
    public function hasAlphaChannel(string $file)
    {
        if (\extension_loaded('imagick')) {
            $Imagick = new \Imagick();
            $Imagick->readImage($file);

            // https://www.php.net/manual/en/imagick.getimagealphachannel.php#126859
            return method_exists($Imagick, 'getImageAlphaChannel') && (\Imagick::ALPHACHANNEL_ACTIVATE === $Imagick->getImageAlphaChannel() || true === $Imagick->getImageAlphaChannel());
        }
        if ($colorTypeByte = \Safe\file_get_contents($file, false, null, self::ColorTypeOffset, 1)) {
            $type = \ord($colorTypeByte);
            $image = \Safe\imagecreatefrompng($file);

            // Palette-based PNGs may have one or more values that correspond to the color to use as transparent
            // PHP returns the first fully transparent color for palette-based images
            $transparentColor = imagecolortransparent($image);

            // Grayscale, RGB, and Palette-based images must define a color that will be used for transparency
            // if none is set, we can bail early because we know it is a fully opaque image
            if (-1 === $transparentColor && \in_array($type, [self::GRAYSCALE, self::RGB, self::PALETTE], true)) {
                return false;
            }

            $xs = \Safe\imagesx($image);
            $ys = \Safe\imagesy($image);

            for ($x = 0; $x < $xs; ++$x) {
                for ($y = 0; $y < $ys; ++$y) {
                    $color = \Safe\imagecolorat($image, $x, $y);

                    if (-1 === $transparentColor) {
                        $shift = self::RGBA === $type ? 3 : 1;
                        $transparency = ($color >> ($shift * 8)) & 0x7F;

                        if (
                            (self::RGBA === $type && 0 !== $transparency)
                            || (self::GRAYSCALE_ALPHA === $type && 0 === $transparency)
                        ) {
                            return true;
                        }
                    } elseif ($color === $transparentColor) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
