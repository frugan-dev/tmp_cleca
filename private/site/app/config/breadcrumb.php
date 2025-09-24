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
    // array or comma separated strings
    'cssClasses' => [
        'breadcrumb', // TWBS
        'bg-light',
    ],

    // space separated strings
    'listItemCssClass' => 'breadcrumb-item', // TWBS

    // true, null or string
    'divider' => null,
    // 'divider' => '<i class="fa fa-angle-double-right"></i>',

    'textTruncate' => 30,
];
