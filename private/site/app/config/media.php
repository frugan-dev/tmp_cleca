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

use Nette\Utils\Image;

/*
 * Image::OrSmaller (default)
 * - Resizes to fit into a specified width and height and preserves aspect ratio
 * - resulting dimensions will be less or equal as specified
 *
 * Image::OrBigger
 * - Resizes while bounding the smaller dimension to the specified width or height and preserves aspect ratio
 * - fills the target area and possibly extends it in one direction
 *
 * Image::Cover
 * - Resizes to the smallest possible size to completely cover specified width and height and reserves aspect ratio
 * - fills the whole area and cuts what exceeds
 *
 * Image::ShrinkOnly
 * - Prevent from getting resized to a bigger size than the original
 * - just scales down (does not extend a small image)
 *
 * Image::Stretch
 * - Resizes to a specified width and height without keeping aspect ratio
 * - does not keep the aspect ratio
 *
 * https://caniuse.com/webp
 */

return [
    'img.uploadMaxFilesize' => (0.5 * 1024 * 1024), // MB

    'img.minWidth' => 800,
    'img.minHeight' => 600,

    'img.sizeArr' => [
        'xs' => [ // <-- needed in backoffice
            'width' => 120,
            'height' => 90,
            'flag' => Image::Cover,
            'type' => [Image::JPEG, Image::WEBP],
        ],
        'sm' => [
            'width' => 200,
            'height' => 150,
            'flag' => Image::Cover,
            'type' => [Image::JPEG, Image::WEBP],
        ],
        'th' => [
            'width' => 400,
            'height' => 150,
            'flag' => Image::Cover,
            'type' => [Image::JPEG, Image::WEBP],
        ],
        'md' => [
            'width' => 400,
            'height' => 300,
            'flag' => Image::Cover,
            'type' => [Image::JPEG, Image::WEBP],
        ],
        'lg' => [ // <-- needed in backoffice
            'width' => 1000,
            'height' => 800,
            'flag' => Image::ShrinkOnly,
            'type' => [Image::JPEG, Image::WEBP],
        ],
        /*'xl' => [
            'width' => 1200,
            'height' => 1000,
            'type' => [Image::JPEG, Image::WEBP],
        ],*/
    ],

    // 'img.watermark.path' => _PUBLIC . '/asset/back/img/watermark/xs.png',

    'img.watermark.positions' => [
        'TL' => 'Top Left',
        'TC' => 'Top Center',
        'TR' => 'Top Right',
        'CL' => 'Center Left',
        'CC' => 'Center Center',
        'CR' => 'Center Right',
        'BL' => 'Bottom Left',
        'BC' => 'Bottom Center',
        'BR' => 'Bottom Right',
    ],

    'img.watermark.position' => false,

    'img.watermark.opacity' => 100,

    // https://forum.nette.org/cs/34396-utils-image-auto-orientace-obrazku
    // https://github.com/recurser/exif-orientation-examples
    // https://www.daveperrett.com/articles/2012/07/28/exif-orientation-handling-is-a-ghetto/
    // https://www.impulseadventure.com/photo/exif-orientation.html
    // https://stackoverflow.com/a/33031994
    // https://stackoverflow.com/a/16761966
    // http://www.php.net/manual/en/function.exif-read-data.php#76964
    // https://stackoverflow.com/a/3615106
    'img.exif.orientation' => true,

    'file.uploadMaxFilesize' => (2 * 1024 * 1024), // MB

    'db.values' => true,

    'upload.rename' => true,
    'file.upload.rename' => false,
];
