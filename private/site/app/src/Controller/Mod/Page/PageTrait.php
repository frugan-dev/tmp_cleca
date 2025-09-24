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

namespace App\Controller\Mod\Page;

trait PageTrait
{
    public ?int $parent_id = null;

    public ?int $hierarchy = null;

    public string $name;

    public ?string $subname = null;

    public string $label;

    public string $richtext;

    public ?string $parent_name = null;

    public array|string|null $catform_ids = null;

    public array|string|null $catform_codes;

    public array|string|null $menu_ids = null;

    public function init(): void
    {
        $this->context = 'female';

        $this->singularName = _n('Page', 'Pages', 1);
        $this->pluralName = _n('Page', 'Pages', 2);
        $this->singularNameWithParams = _n('Page', 'Pages', 1, $this->context, $this->config['logger.locale']);
        $this->pluralNameWithParams = _n('Page', 'Pages', 2, $this->context, $this->config['logger.locale']);

        $this->groupId = 50000;
        $this->weight = 50050;
        $this->faClass = 'fa-sitemap';

        parent::init();

        $routeName = $this->routeParsingService->getRouteName();

        if (str_contains((string) $routeName, '.')) {
            [, $controller] = explode('.', (string) $routeName);

            if (\in_array($controller, ['catmember'], true)) {
                $this->allowedPerms = [];

                $this->additionalPerms = array_merge_recursive($this->additionalPerms, [
                    'view.front' => [
                        'print',
                    ],
                ]);
            }
        }

        $this->addDeps([
            'catform',
        ]);

        if (!empty($this->config['mod.'.$this->modName.'.tree.maxLevel'] ?? $this->config['mod.tree.maxLevel'] ?? 0)) {
            $this->controls = array_merge([
                'parent_id',
            ], $this->controls);

            $this->replaceKeysMap['parent_id'] = 'parent_name';
        }

        $this->controls = array_merge([
            'catform_id',
        ], $this->controls);

        $this->replaceKeysMap['catform_ids'] = 'catform_codes';

        $this->fieldsSortable = $this->helper->Arrays()->replaceKeys(
            $this->fieldsSortable,
            $this->replaceKeysMap
        );

        $this->additionalTables = [
            $this->modName.'2catform',
            $this->modName.'2menu',
        ];
    }

    public function setCustomFields(array $row = []): void
    {
        if (!empty($this->config['mod.'.$this->modName.'.tree.maxLevel'] ?? $this->config['mod.tree.maxLevel'] ?? 0)) {
            $this->parent_name = $row['parent_name'] ?? null;
        }

        $this->catform_ids = !empty($row['catform_ids']) ? explode(',', (string) $row['catform_ids']) : [];
        $this->filterValue->sanitize($this->catform_ids, 'intvalArray');

        $this->catform_codes = $row['catform_codes'] ?? null;
        if (str_contains((string) $this->catform_codes, '|')) {
            $this->catform_codes = explode('|', $this->catform_codes);
        }

        $this->menu_ids = !empty($row['menu_ids']) ? explode(',', (string) $row['menu_ids']) : [];
        $this->filterValue->sanitize($this->menu_ids, 'intvalArray');
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

        $this->fields['subname'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

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
        ];

        $this->fields['label'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

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

        $this->fields['richtext'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            'multilang' => true,

            $this->config['env.default'] => [
                'label' => __('Text'),

                'type' => 'textarea',
                'attr' => [
                    'id' => 'richtext',
                    // 'cols' => 10,
                    'rows' => 15,
                    'class' => ['form-control', 'richedit-simple'],
                    'required' => true,
                ],

                'hidden' => ['index'],
            ],
        ];

        $this->fields['parent_id'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('Parent %1$s'), $this->helper->Nette()->Strings()->lower($this->singularName))),

                'type' => 'select',
                'attr' => [
                    'id' => 'parent_id',
                    'class' => ['form-select'],
                ],

                'skip' => ['search'],
            ],
        ];

        if (empty($this->config['mod.'.$this->modName.'.tree.maxLevel'] ?? $this->config['mod.tree.maxLevel'] ?? 0)) {
            $this->fields['parent_id'][$this->config['env.default']]['hidden'][] = 'index';
            $this->fields['parent_id'][$this->config['env.default']]['hidden'][] = 'add';
            $this->fields['parent_id'][$this->config['env.default']]['hidden'][] = 'edit';
            $this->fields['parent_id'][$this->config['env.default']]['hidden'][] = 'view';
            $this->fields['parent_id'][$this->config['env.default']]['hidden'][] = 'delete';

            $this->fields['parent_id'][$this->config['env.default']]['default'][] = 'add';
            $this->fields['parent_id'][$this->config['env.default']]['default'][] = 'edit';
        }

        $this->fields['catform_ids'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => $this->container->get('Mod\Catform\\'.ucfirst(static::$env))->pluralName,

                'type' => 'select',
                'attr' => [
                    'id' => 'catform_ids',
                    'class' => ['form-select'],
                    'size' => 5,
                    'multiple' => true,
                ],

                'help' => \sprintf(
                    __('Hold %1$s and click on the items to select more than one.'),
                    '<kbd>Ctrl</kbd>'
                ),

                'skip' => ['add', 'edit', 'search'],
            ],
        ];

        $this->fields['menu_ids'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => __('Menu'),

                'type' => 'select',
                'attr' => [
                    'id' => 'menu_ids',
                    'class' => ['form-select'],
                    'size' => 5,
                    'multiple' => true,
                ],
                // https://stackoverflow.com/a/3432266
                'options' => array_map('__', $this->config['mod.'.$this->modName.'.menu.arr']),

                'help' => \sprintf(
                    __('Hold %1$s and click on the items to select more than one.'),
                    '<kbd>Ctrl</kbd>'
                ),

                'skip' => ['add', 'edit', 'search'],
                'hidden' => ['index'],
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
