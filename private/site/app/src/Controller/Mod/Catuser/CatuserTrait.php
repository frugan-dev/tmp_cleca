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

namespace App\Controller\Mod\Catuser;

trait CatuserTrait
{
    public string $name;

    public ?int $api_rl_hour = null;

    public ?int $api_rl_day = null;

    public ?int $api_log_level = null;

    public ?int $preselected = null;

    public ?int $main = null;

    public $perms;

    public array $mods = [];

    public function init(): void
    {
        $this->singularName = _n('Group', 'Groups', 1);
        $this->pluralName = _n('Group', 'Groups', 2);
        $this->singularNameWithParams = _n('Group', 'Groups', 1, $this->context, $this->config['logger.locale']);
        $this->pluralNameWithParams = _n('Group', 'Groups', 2, $this->context, $this->config['logger.locale']);

        $this->groupId = 80000;
        $this->weight = 80010;
        $this->faClass = 'fa-users';

        /*$this->allowedPerms = array_merge($this->allowedPerms, [
            '.view',
        ]);*/

        /*$this->additionalPerms = array_merge_recursive($this->additionalPerms, [
            'view.back' => [
                'view-api',
            ],
        ]);*/

        $this->additionalApis = array_merge_recursive($this->additionalApis, [
            'post' => [
                '/add' => [
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'required' => true,
                                        ],
                                        'perms' => [
                                            'type' => 'array',
                                            'items' => [],
                                        ],
                                        'preselected' => [
                                            'type' => 'boolean',
                                        ],
                                        'main' => [
                                            'type' => 'boolean',
                                        ],
                                        'active' => [
                                            'type' => 'boolean',
                                        ],
                                    ],
                                ],
                                'example' => [
                                    'name' => 'My group',
                                    'perms' => [
                                        'log.back.index',
                                        'log.back.view',
                                        'user.back.delete-cache',
                                    ],
                                    'active' => true,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'put' => [
                '/edit/{id}' => [
                    'requestBody' => [
                        'content' => [
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'name' => [
                                            'type' => 'string',
                                            'required' => true,
                                        ],
                                        'perms' => [
                                            'type' => 'array',
                                            'items' => [
                                                'type' => 'string',
                                            ],
                                        ],
                                        'preselected' => [
                                            'type' => 'boolean',
                                        ],
                                        'main' => [
                                            'type' => 'boolean',
                                        ],
                                        'active' => [
                                            'type' => 'boolean',
                                        ],
                                    ],
                                ],
                                'example' => [
                                    'perms' => [
                                        'log.api.index',
                                        'log.api.view',
                                        'log.back.index',
                                        'log.back.view',
                                        'user.api.delete-cache',
                                        'user.back.delete-cache',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        parent::init();
    }

    public function setCustomFields(array $row = []): void
    {
        $this->perms = !empty($row['perms']) ? $this->helper->Nette()->Json()->decode((string) $row['perms'], forceArrays: true) : [];
    }

    public function setDefaultFields(): void
    {
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
            'dbDefault' => false,

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

        $this->fields['perms'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => __('Permissions'),

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'class' => ['form-check-input'],
                ],

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
                'defaultIfNotExists' => ['add', 'edit'],
            ],
        ];

        $this->fields['api_rl_hour'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => \sprintf(__('API rate limit per %1$s'), __('hour')),

                'type' => 'input',
                'attr' => [
                    'type' => 'number',
                    'id' => 'api_rl_hour',
                    'min' => 1,
                    'max' => 99999,
                    'step' => 1,
                    'class' => ['form-control'],
                ],

                'skip' => ['search'],
                'hidden' => ['index', 'delete-bulk'],
            ],

            'api' => [
                'hidden' => [],
            ],
        ];

        $this->fields['api_rl_day'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => \sprintf(__('API rate limit per %1$s'), __('day')),

                'type' => 'input',
                'attr' => [
                    'type' => 'number',
                    'id' => 'api_rl_day',
                    'min' => 1,
                    'max' => 99999,
                    'step' => 1,
                    'class' => ['form-control'],
                ],

                'skip' => ['search'],
                'hidden' => ['index', 'delete-bulk'],
            ],

            'api' => [
                'hidden' => [],
            ],
        ];

        $this->fields['api_log_level'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => __('API log level'),

                'type' => 'select',
                'attr' => [
                    'id' => 'api_log_level',
                    'class' => ['form-select'],
                ],

                'skip' => ['search'],
                'hidden' => ['index', 'delete-bulk'],
            ],

            'api' => [
                'hidden' => [],
            ],
        ];

        $this->fields['preselected'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => __('Preselected'),

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'value' => 1,
                    'id' => 'preselected',
                    'class' => ['form-check-input'],
                ],

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
                'defaultIfNotExists' => ['add', 'edit'],
            ],
        ];

        $this->fields['main'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => __('Main'),

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'value' => 1,
                    'id' => 'main',
                    'class' => ['form-check-input'],
                ],

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
                'defaultIfNotExists' => ['add', 'edit'],
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
