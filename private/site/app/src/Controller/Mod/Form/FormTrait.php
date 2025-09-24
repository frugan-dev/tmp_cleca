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

namespace App\Controller\Mod\Form;

trait FormTrait
{
    public int $catform_id;

    public ?int $hierarchy = null;

    public ?int $printable = null;

    public string $name;

    public ?string $subname = null;

    public string $label;

    public string $catform_code;

    public function init(): void
    {
        $this->singularName = _n('Form', 'Forms', 1);
        $this->pluralName = _n('Form', 'Forms', 2);
        $this->singularNameWithParams = _n('Form', 'Forms', 1, $this->context, $this->config['logger.locale']);
        $this->pluralNameWithParams = _n('Form', 'Forms', 2, $this->context, $this->config['logger.locale']);

        $this->groupId = 50000;
        $this->weight = 50020;
        $this->faClass = 'fa-list-ol';

        $this->additionalPerms = [
            'edit.front' => [
                'fill',
            ],
        ];

        parent::init();

        $routeName = $this->routeParsingService->getRouteName();

        if (str_contains((string) $routeName, '.')) {
            [, $controller] = explode('.', (string) $routeName);

            if (\in_array($controller, ['catmember'], true)) {
                $this->allowedPerms = [
                    '.front.index',
                ];

                $this->additionalPerms = array_merge_recursive($this->additionalPerms, [
                    'view.front' => [
                        'print',
                    ],
                ]);
            }
        }

        $this->replaceKeysMap = [
            'cat'.$this->modName.'_id' => 'cat'.$this->modName.'_code',
        ];

        $this->fieldsSortable = $this->helper->Arrays()->replaceKeys(
            $this->fieldsSortable,
            $this->replaceKeysMap
        );

        $this->addDeps([
            'cat'.$this->modName,
        ]);

        $this->controls = array_merge([
            'cat'.$this->modName.'_id',
        ], $this->controls);
    }

    public function setCustomFields(array $row = []): void
    {
        $this->{'cat'.$this->modName.'_code'} = $row['cat'.$this->modName.'_code'];
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

            'front' => [
                'hidden' => ['index', 'view'],
            ],

            'api' => [
                'hidden' => [],
            ],
        ];

        $this->fields['name'] = [
            'dbDefault' => false,

            'multilang' => true,

            $this->config['env.default'] => [
                'label' => __('Title'),

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

        $this->fields['cat'.$this->modName.'_id'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => $this->container->get('Mod\Cat'.$this->modName.'\\'.ucfirst(static::$env))->singularName,

                'type' => 'select',
                'attr' => [
                    'id' => 'cat'.$this->modName.'_id',
                    'class' => ['form-select'],
                    'required' => true,
                ],

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
            ],
        ];

        $this->fields['subname'] = [
            'dbDefault' => false,

            'multilang' => true,

            $this->config['env.default'] => [
                'label' => __('Subtitle'),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'subname',
                    'maxlength' => 128,
                    'class' => ['form-control'],
                ],

                'hidden' => ['index'],
            ],

            'front' => [
                'hidden' => [],
            ],
        ];

        $this->fields['label'] = [
            'dbDefault' => false,

            'multilang' => true,

            $this->config['env.default'] => [
                'label' => __('Menu label'),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'label',
                    'maxlength' => 128,
                    'class' => ['form-control'],
                    'required' => true,
                ],

                'hidden' => ['index'],
            ],

            'front' => [
                'hidden' => [],
            ],
        ];

        $this->fields['hierarchy'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => __('Sorting'),

                'type' => 'input',
                'attr' => [
                    'type' => 'number',
                    'id' => 'hierarchy',
                    'step' => 1,
                    'class' => ['form-control'],
                    'value' => 0,
                ],

                'defaultIfNotExists' => ['add', 'edit'],
            ],

            'front' => [
                'hidden' => ['index', 'view'],
            ],
        ];

        $this->fields['printable'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => __('Printable'),

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'value' => 1,
                    'id' => 'printable',
                    'class' => ['form-check-input'],
                ],
                'value' => 1,

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

            'front' => [
                'hidden' => ['index', 'view'],
            ],
        ];
    }
}
