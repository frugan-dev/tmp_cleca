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

namespace App\Controller\Mod\Country;

use App\Factory\Html\ViewHelperInterface;

trait CountryTrait
{
    public $iso_code;

    public $phone_code;

    public $regex_nin;

    public $regex_vat;

    public $name;

    public function init(): void
    {
        $this->context = 'female';

        $this->singularName = _n('Country', 'Countries', 1);
        $this->pluralName = _n('Country', 'Countries', 2);
        $this->singularNameWithParams = _n('Country', 'Countries', 1, $this->context, $this->config['logger.locale']);
        $this->pluralNameWithParams = _n('Country', 'Countries', 2, $this->context, $this->config['logger.locale']);

        $this->groupId = 70000;
        $this->weight = 70030;
        $this->faClass = 'fa-globe-europe';

        $this->additionalApis = array_merge_recursive($this->additionalApis, [
            'put' => [
                '/edit/{id}' => [
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'active' => [
                                            'type' => 'boolean',
                                        ],
                                    ],
                                ],
                                'example' => [
                                    'active' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        parent::init();
    }

    public function setDefaultFields(): void
    {
        // FIXME - circular dependencies
        $this->viewHelper = $this->container->get(ViewHelperInterface::class);

        $this->fields['id'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => __('ID'),

                'type' => 'input',
                'attr' => [
                    'type' => 'hidden',
                ],

                'skip' => ['add', 'edit'],
                'hidden' => ['add'],
            ],
        ];

        $this->fields['mdate'] = [
            'dbDefault' => $this->helper->Carbon()->now($this->config['db.1.timeZone'])->toDateTimeString(),

            $this->config['env.default'] => [
                'label' => __('Modification date'),

                'attr' => [],

                'skip' => ['search'],
                'hidden' => ['index', 'add', 'delete-bulk'],
                'default' => ['add', 'edit'],
            ],

            'api' => [
                'hidden' => [],
            ],
        ];

        $this->fields['name'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            'multilang' => true,

            $this->config['env.default'] => [
                'label' => __('Name'),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'name',
                    'maxlength' => 128,
                    'class' => ['form-control'],
                    'required' => true,
                ],
            ],
        ];

        $this->fields['iso_code'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => __('ISO code'),

                'type' => 'select',
                'attr' => [
                    'id' => 'iso_code',
                    'class' => ['form-select'],
                    'required' => true,
                ],
            ],
        ];

        $this->fields['phone_code'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => __('Phone code'),

                'type' => 'input',
                'attr' => [
                    'type' => 'number',
                    'id' => 'phone_code',
                    'min' => 1, // no code start with 0
                    'max' => 999,
                    'step' => 1,
                    'class' => ['form-control'],
                    'required' => true,
                ],
            ],
        ];

        $this->fields['regex_nin'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('regex %1$s'), \sprintf(__('fiscal code or %1$s'), '<abbr class="initialism"'.$this->viewHelper->escapeAttr(['title' => __('National Identification Number')]).'>'.__('NIN').'</abbr>'))),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'regex_nin',
                    'class' => ['form-control'],
                ],

                'hidden' => ['index'],
            ],
        ];

        // https://github.com/ronanguilloux/IsoCodes/blob/master/src/IsoCodes/Vat.php
        $this->fields['regex_vat'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('regex %1$s'), __('VAT'))),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'regex_vat',
                    'class' => ['form-control'],
                ],

                'hidden' => ['index'],
            ],
        ];

        $this->fields['active'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => __('State'),

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'value' => 1,
                    'id' => 'active',
                    'class' => ['form-check-input'],
                ],
                'value' => 1,

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
                'defaultIfNotExists' => ['add', 'edit'],
            ],
        ];
    }
}
