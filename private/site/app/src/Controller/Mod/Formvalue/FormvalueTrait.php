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

namespace App\Controller\Mod\Formvalue;

trait FormvalueTrait
{
    public int $catmember_id;

    public int $member_id;

    public int $catform_id;

    public int $form_id;

    public int $formfield_id;

    public array|string|null $data = null;

    public ?string $catmember_name = null;

    public ?string $member_lastname_firstname = null;

    public ?string $catform_code = null;
    public ?string $catform_sdate = null;
    public ?string $catform_edate = null;
    public ?int $catform_maintenance = null;

    public ?string $form_name = null;

    public ?string $formfield_type = null;
    public ?string $formfield_name = null;
    public ?string $formfield_richtext = null;
    public array|string|null $formfield_option = null;

    public function init(): void
    {
        $this->singularName = \sprintf(_n('%1$s value', '%1$s values', 1), _n('Form', 'Forms', 1));
        $this->pluralName = \sprintf(_n('%1$s value', '%1$s values', 2), _n('Form', 'Forms', 1));
        $this->singularNameWithParams = \sprintf(_n('%1$s value', '%1$s values', 1, $this->context, $this->config['logger.locale']), _n('Form', 'Forms', 1));
        $this->pluralNameWithParams = \sprintf(_n('%1$s value', '%1$s values', 2, $this->context, $this->config['logger.locale']), _n('Form', 'Forms', 1));

        $this->groupId = 50000;
        $this->weight = 50040;
        $this->faClass = 'fa-rectangle-list';

        $this->additionalPerms = array_merge_recursive($this->additionalPerms, [
            'index.back' => [
                'export',
            ],
            'delete.back' => [
                'reset',
            ],
            'index.front' => [
                'index',
            ],
            'view.front' => [
                'view',
            ],
        ]);

        array_unshift($this->controls, 'reset');
        array_unshift($this->controls, 'export');

        parent::init();

        $routeName = $this->routeParsingService->getRouteName();

        if (str_contains((string) $routeName, '.')) {
            [, $controller] = explode('.', (string) $routeName);

            if (\in_array($controller, ['catmember'], true)) {
                $this->allowedPerms = [
                    '.front.index',
                    '.front.view',
                    '.front.edit',
                ];

                $this->additionalPerms = [
                    'add.api' => [
                        'upload',
                    ],
                    'delete.api' => [
                        'delete-file',
                    ],
                ];
            }
        }

        $this->replaceKeysMap = [
            'member_id' => 'member_lastname_firstname',
            'catform_id' => 'catform_code',
            'form_id' => 'form_name',
            'formfield_id' => 'formfield_type',
        ];

        $this->fieldsSortable = $this->helper->Arrays()->replaceKeys(
            $this->fieldsSortable,
            $this->replaceKeysMap
        );

        $this->addDeps([
            'catmember',
            'member',
            'catform',
            'form',
            'formfield',
        ]);

        $this->controls = array_merge([
            'member_id',
            'catform_id',
            'form_id',
            'formfield_id',
        ], $this->controls);
    }

    public function setCustomFields(array $row = []): void
    {
        $this->catmember_name = $row['catmember_name'] ?? null;
        $this->member_lastname_firstname = $row['member_lastname_firstname'] ?? null;
        $this->catform_code = $row['catform_code'] ?? null;
        $this->catform_sdate = $row['catform_sdate'] ?? null;
        $this->catform_edate = $row['catform_edate'] ?? null;
        $this->catform_maintenance = $row['catform_maintenance'] ?? null;
        $this->form_name = $row['form_name'] ?? null;
        $this->formfield_type = $row['formfield_type'] ?? null;
        $this->formfield_name = $row['formfield_name'] ?? null;
        $this->formfield_richtext = $row['formfield_richtext'] ?? null;
        $this->formfield_option = !empty($row['formfield_option']) ? $this->helper->Nette()->Json()->decode((string) $row['formfield_option'], forceArrays: true) : [];
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

                'skip' => ['add', 'edit', 'delete-file'],
                'hidden' => ['add', 'export', 'reset'],
            ],
        ];

        $this->fields['mdate'] = [
            'dbDefault' => $this->helper->Carbon()->now($this->config['db.1.timeZone'])->toDateTimeString(),

            $this->config['env.default'] => [
                'label' => __('Modification date'),

                'attr' => [],

                'skip' => ['search'],
                'hidden' => ['index', 'add', 'delete-bulk', 'export', 'reset'],
                'default' => ['add', 'edit', 'fill', 'upload', 'delete-file'],
            ],

            'front' => [
                'hidden' => [],
            ],

            'api' => [
                'hidden' => [],
            ],
        ];

        $this->fields['catmember_id'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => $this->container->get('Mod\Catmember\\'.ucfirst(static::$env))->singularName,

                'type' => 'select',
                'attr' => [
                    'id' => 'catmember_id',
                    'class' => ['form-select'],
                    'required' => true,
                ],

                'skip' => ['search', 'delete-file', 'export', 'reset'],
                'hidden' => ['index', 'delete-bulk', 'export', 'reset'],
            ],

            'front' => [
                'hidden' => ['index', 'view', 'edit'],
                'skip' => ['edit'],
            ],
        ];

        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['catmember_id'][$this->config['env.default']]['skip'][] = 'edit';
        }
        if ($this->rbac->isGranted($this->modName.'.front.edit')) { // <--
            $this->fields['catmember_id']['api']['skip'] = $this->fields['catmember_id'][$this->config['env.default']]['skip'];
            $this->fields['catmember_id']['api']['skip'][] = 'upload';
        }

        $this->fields['member_id'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => $this->container->get('Mod\Member\\'.ucfirst(static::$env))->singularName,

                'type' => 'select',
                'attr' => [
                    'id' => 'member_id',
                    'class' => ['form-select'],
                    'required' => true,
                ],

                'skip' => ['search', 'delete-file', 'export', 'reset'],
                'hidden' => ['delete-bulk', 'export', 'reset'],
            ],

            'front' => [
                'skip' => ['edit'],
            ],
        ];

        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['member_id'][$this->config['env.default']]['skip'][] = 'edit';
        }

        $this->fields['member_id']['front']['attr'] = $this->fields['member_id'][$this->config['env.default']]['attr'];
        $this->fields['member_id']['front']['attr']['required'] = false;

        if ($this->rbac->isGranted($this->modName.'.front.edit')) { // <--
            $this->fields['member_id']['api']['skip'] = $this->fields['member_id'][$this->config['env.default']]['skip'];
            $this->fields['member_id']['api']['skip'][] = 'upload';
        }

        $this->fields['catform_id'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => $this->container->get('Mod\Catform\\'.ucfirst(static::$env))->singularName,

                'type' => 'select',
                'attr' => [
                    'id' => 'catform_id',
                    'class' => ['form-select'],
                    'required' => true,
                ],

                'skip' => ['search', 'delete-file'],
                'hidden' => ['delete-bulk'],
            ],

            'front' => [
                'skip' => ['edit'],
            ],
        ];

        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['catform_id'][$this->config['env.default']]['skip'][] = 'edit';
        }

        $this->fields['catform_id']['front']['attr'] = $this->fields['catform_id'][$this->config['env.default']]['attr'];
        $this->fields['catform_id']['front']['attr']['required'] = false;

        $this->fields['form_id'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => $this->container->get('Mod\Form\\'.ucfirst(static::$env))->singularName,

                'type' => 'select',
                'attr' => [
                    'id' => 'form_id',
                    'class' => ['form-select'],
                    'required' => true,
                ],

                'skip' => ['search', 'delete-file', 'export', 'reset'],
                'hidden' => ['delete-bulk', 'export', 'reset'],
            ],

            'front' => [
                'hidden' => ['index', 'view', 'edit'],
                'skip' => ['edit'],
            ],
        ];

        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['form_id'][$this->config['env.default']]['skip'][] = 'edit';
        }

        $this->fields['formfield_id'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => $this->container->get('Mod\Formfield\\'.ucfirst(static::$env))->singularName,

                'type' => 'select',
                'attr' => [
                    'id' => 'formfield_id',
                    'class' => ['form-select'],
                    'required' => true,
                ],

                'skip' => ['search', 'delete-file', 'export', 'reset'],
                'hidden' => ['delete-bulk', 'export', 'reset'],
            ],

            'front' => [
                'hidden' => ['index', 'view', 'edit'],
                'skip' => ['edit'],
            ],
        ];

        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['formfield_id'][$this->config['env.default']]['skip'][] = 'edit';
        }

        $this->fields['data'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => __('Data'),

                'type' => 'textarea',
                'attr' => [
                    'id' => 'data',
                    // 'cols' => 10,
                    'rows' => 10,
                    'class' => ['form-control'],
                    'readonly' => true,
                ],

                'skip' => ['edit', 'search', 'export', 'reset'],
                'hidden' => ['index', 'export', 'reset'],
            ],

            'front' => [
                'label' => __('Recommendation letters'),
                'skip' => [],
            ],

            'api' => [
                'skip' => [],
            ],
        ];

        $this->fields['data']['front']['attr'] = $this->fields['data'][$this->config['env.default']]['attr'];
        $this->fields['data']['front']['attr']['readonly'] = false;
        $this->fields['data']['front']['attr']['required'] = true;

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

                'skip' => ['search', 'delete-file', 'export', 'reset'],
                'hidden' => ['delete-bulk', 'export', 'reset'],
                'defaultIfNotExists' => ['add', 'edit'],
            ],

            'front' => [
                'hidden' => ['view'],
                'skip' => ['edit'],
            ],
        ];

        if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['active']['front']['hidden'][] = 'index';
            $this->fields['active']['front']['hidden'][] = 'edit';
        }

        $this->fields['member_not_main_in'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => '',

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'value' => 1,
                    'id' => 'member_not_main_in',
                    'class' => ['form-check-input'],
                ],
                'value' => 1,

                'skip' => ['add', 'edit', 'search', 'delete-file', 'export'],
                'hidden' => ['index', 'add', 'edit', 'view', 'delete', 'delete-bulk', 'export'],
                'defaultIfNotExists' => ['reset'],
            ],
        ];

        $this->fields['member_not_main_out'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => '',

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'value' => 1,
                    'id' => 'member_not_main_out',
                    'class' => ['form-check-input'],
                ],

                'skip' => ['add', 'edit', 'search', 'delete-file', 'export'],
                'hidden' => ['index', 'add', 'edit', 'view', 'delete', 'delete-bulk', 'export'],
                'defaultIfNotExists' => ['reset'],
            ],
        ];

        $this->fields['member_main'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => '',

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'value' => 1,
                    'id' => 'member_main',
                    'class' => ['form-check-input'],
                ],

                'skip' => ['add', 'edit', 'search', 'delete-file', 'export'],
                'hidden' => ['index', 'add', 'edit', 'view', 'delete', 'delete-bulk', 'export'],
                'defaultIfNotExists' => ['reset'],
            ],
        ];
    }
}
