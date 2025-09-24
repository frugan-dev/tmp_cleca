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
    // twbs3, twbs4, twbs5, etc.
    'type' => 'twbs5',

    // twbs 5 + bootswatch flatly
    'color.primary' => '2c3e50',
    'color.secondary' => '95a5a6',
    'color.success' => '18bc9c',
    'color.info' => '3498db',
    'color.warning' => 'f39c12',
    'color.danger' => 'e74c3c',

    // twbs 5 + bootswatch flatly
    'logger' => [
        'levels' => [
            'debug' => [
                'color' => '95a5a6',
            ],
            'info' => [
                'color' => '18bc9c',
            ],
            'notice' => [
                'color' => '3498db',
            ],
            'warning' => [
                'color' => 'f39c12',
            ],
            'error' => [
                'color' => 'e74c3c',
            ],
            'critical' => [
                'color' => 'e74c3c',
            ],
            'alert' => [
                'color' => 'e74c3c',
            ],
            'emergency' => [
                'color' => 'e74c3c',
            ],
        ],
    ],

    // twbs 5 + bootswatch flatly
    'mod.catform.status.10.color' => '95a5a6', // UNDEFINED
    'mod.catform.status.20.color' => 'f39c12', // MAINTENANCE
    'mod.catform.status.30.color' => '3498db', // OPENING
    'mod.catform.status.40.color' => '18bc9c', // OPEN
    'mod.catform.status.50.color' => 'f39c12', // CLOSING
    'mod.catform.status.60.color' => 'e74c3c', // CLOSED

    // https://www.w3.org/WAI/WCAG21/Understanding/contrast-minimum.html
    // from 1 to 21, default = 4.5
    'color.contrast.wcag.ratio' => 2.2,

    // https://en.wikipedia.org/wiki/YIQ
    // from 0 to 255, default = 128
    'color.contrast.yiq.threshold' => 170,

    'switcher' => true,

    // https://getbootstrap.com/docs/5.3/forms/checks-radios/#switches
    'checkbox.switches' => true,

    'label.class' => ['col-md-2', 'text-md-end'],
    'add.label.class' => ['col-sm-3', 'col-md-2', 'col-form-label', 'text-sm-end'],
    'edit.label.class' => ['col-sm-3', 'col-md-2', 'col-form-label', 'text-sm-end'],
    'export.label.class' => ['col-sm-3', 'col-md-2', 'col-form-label', 'text-sm-end'],
    'reset.label.class' => ['col-sm-3', 'col-md-2', 'col-form-label', 'text-sm-end'],
    'front.label.class' => ['col-sm-4', 'text-sm-end'],
    'front.edit.label.class' => ['col-sm-4', 'col-form-label', 'text-sm-end'],
    'front.fill.label.class' => ['col-sm-5', 'col-form-label', 'text-sm-end'],

    'value.class' => ['col-md-10'],
    'add.value.class' => ['col-sm-9', 'col-md-10', 'col-lg-5'],
    'edit.value.class' => ['col-sm-9', 'col-md-10', 'col-lg-5'],
    'export.value.class' => ['col-sm-9', 'col-md-10', 'col-lg-5'],
    'reset.value.class' => ['col-sm-9', 'col-md-10', 'col-lg-5'],
    'front.value.class' => ['col-sm-6'],

    'value.offset.class' => ['col-md-10', 'offset-md-2'],
    'add.value.offset.class' => ['col-sm-9', 'col-md-10', 'col-lg-5', 'offset-sm-3', 'offset-md-2'],
    'edit.value.offset.class' => ['col-sm-9', 'col-md-10', 'col-lg-5', 'offset-sm-3', 'offset-md-2'],
    'export.value.offset.class' => ['col-sm-9', 'col-md-10', 'col-lg-5', 'offset-sm-3', 'offset-md-2'],
    'reset.value.offset.class' => ['col-sm-9', 'col-md-10', 'col-lg-5', 'offset-sm-3', 'offset-md-2'],
    'front.value.offset.class' => ['col-sm-6', 'offset-sm-4'],
    'front.fill.value.offset.class' => ['col-sm-6', 'offset-sm-5'],

    'btn.col.class' => ['col', 'offset-sm-3', 'offset-md-2', 'd-grid'],
    'front.btn.col.class' => ['col-sm-6', 'offset-sm-4', 'd-grid'],
    'front.fill.btn.col.class' => ['col-sm-6', 'offset-sm-5', 'd-grid'],

    'btn.class' => ['btn', 'btn-primary', 'me-sm-auto'],
    'front.btn.class' => ['btn', 'btn-primary'],
];
