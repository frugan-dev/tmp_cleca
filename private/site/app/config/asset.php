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

return [
    // https://symfony.com/doc/current/components/html
    'package.type' => 'path', // path, url
    'print.package.type' => 'url',

    // PHP built-in server doesn't rewrite
    'filenameMethod.enabled' => true,

    // regex allowed
    'filenameMethod.arr' => [
        'bmp',
        'css',
        'cur',
        'gif',
        'ico',
        'jpe?g',
        'm?js',
        'a?png',
        'svgz?',
        'webp',
        'webmanifest',
    ],

    // regex allowed
    'css.weight' => [
        'main' => 91,
    ],

    // voku/html-min remove "media="all" from all links and styles
    // regex allowed
    'css.attr' => [
        'main' => [
            'media' => 'all',
        ],
        'vendor' => [
            'media' => 'all',
        ],
    ],

    // regex allowed
    'js.weight' => [
        'main' => 91,
    ],

    // regex allowed
    /*'front.js.attr' => [
        'fortawesome' => [
            //https://fontawesome.com/docs/web/add-icons/pseudo-elements
            //https://stackoverflow.com/a/47723704/3929620
            'data-search-pseudo-elements' => true,
        ],
    ],*/
];
