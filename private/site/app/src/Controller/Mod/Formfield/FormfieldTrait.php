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

namespace App\Controller\Mod\Formfield;

trait FormfieldTrait
{
    public int $catform_id;

    public int $form_id;

    public string $type;

    public array|string|null $option = null;

    public ?int $hierarchy = null;

    public ?int $required = null;

    public ?string $name = null;

    public ?string $richtext = null;

    public array|string|null $option_lang = null;

    public string $catform_code;

    public string $form_name;

    public function init(): void
    {
        $this->singularName = \sprintf(_n('%1$s field', '%1$s fields', 1), _n('Form', 'Forms', 1));
        $this->pluralName = \sprintf(_n('%1$s field', '%1$s fields', 2), _n('Form', 'Forms', 1));
        $this->singularNameWithParams = \sprintf(_n('%1$s field', '%1$s fields', 1, $this->context, $this->config['logger.locale']), _n('Form', 'Forms', 1));
        $this->pluralNameWithParams = \sprintf(_n('%1$s field', '%1$s fields', 2, $this->context, $this->config['logger.locale']), _n('Form', 'Forms', 1));

        $this->groupId = 50000;
        $this->weight = 50030;
        $this->faClass = 'far fa-rectangle-list';

        parent::init();

        $this->replaceKeysMap = [
            'catform_id' => 'catform_code',
            'form_id' => 'form_name',
        ];

        $this->fieldsSortable = $this->helper->Arrays()->replaceKeys(
            $this->fieldsSortable,
            $this->replaceKeysMap
        );

        $this->addDeps([
            'catform',
            'form',
        ]);

        $this->controls = array_merge([
            'catform_id',
            'form_id',
        ], $this->controls);
    }

    public function setCustomFields(array $row = []): void
    {
        $this->catform_code = $row['catform_code'];
        $this->form_name = $row['form_name'];

        $this->option = !empty($row['option']) ? $this->helper->Nette()->Json()->decode((string) $row['option'], forceArrays: true) : [];
        $this->option_lang = !empty($row['option_lang']) ? $this->helper->Nette()->Json()->decode((string) $row['option_lang'], forceArrays: true) : [];

        foreach ($this->lang->arr as $langId => $langRow) {
            if (!empty($this->multilang[$langId]['option_lang'])) {
                $this->multilang[$langId]['option_lang'] = $this->helper->Nette()->Json()->decode((string) $this->multilang[$langId]['option_lang'], forceArrays: true);
            }
        }
    }

    public function getFieldTypes(?string $type = null)
    {
        return match ($type) {
            'block' => [
                'block_text' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('block %1$s'), __('text'))),
                'block_separator' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('block %1$s'), __('separator'))),
            ],
            'form' => [
                // 'input_color' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('color')) ),
                'input_date' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('date'))),
                // 'input_datetime' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('datetime')) ),
                // 'input_datetime-local' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('date and time')) ),
                'input_email' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('email'))),
                // 'input_month' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('month')) ),
                'input_number_integer_gte0' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('integer number greater than or equal to zero'))),
                'input_number_integer_gt0' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('integer number greater than zero'))),
                // 'input_password' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('password')) ),
                // 'input_range' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('range')) ),
                // 'input_search' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('search')) ),
                'input_tel' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('telephone'))),
                'input_text' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('text'))),
                // 'input_text_nin' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('fiscal code or NIN (National Identification Number)')) ),
                // 'input_text_vat' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('VAT')) ),
                // 'input_time' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('time')) ),
                'input_url' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('url'))),
                // 'input_weeek' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('weeek')) ),
                // 'input_checkbox' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('checkbox')) ),
                // 'input_radio' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('radio')) ),
                'input_file_multiple' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('multiple files attachment'))),
                // 'input_file_img' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('file image')) ),
                // 'input_hidden' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('hidden')) ),

                'textarea' => $this->helper->Nette()->Strings()->firstUpper(__('textarea')),

                'select' => $this->helper->Nette()->Strings()->firstUpper(__('select')),
                'checkbox' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('checkbox'))),
                'radio' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('radio'))),

                // 'recaptcha' => $this->helper->Nette()->Strings()->firstUpper( __('recaptcha') ),

                'country' => $this->container->get('Mod\Country\\'.ucfirst(static::$env))->singularName,
            ],
            'special' => [
                'recommendation' => __('Recommendation letters'),
            ],
            default => array_merge(
                $this->getFieldTypes('form'),
                $this->getFieldTypes('block'),
                $this->getFieldTypes('special'),
            )
        };
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
                'hidden' => ['index'],
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
                ],
            ],
        ];

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

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
            ],

            'front' => [
                'hidden' => ['index'],
            ],
        ];

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

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
            ],

            'front' => [
                'hidden' => ['index'],
            ],
        ];

        $this->fields['type'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => __('Type'),

                'type' => 'select',
                'attr' => [
                    'id' => 'type',
                    'class' => ['form-select'],
                    'required' => true,
                ],

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
            ],

            'front' => [
                'hidden' => [],
            ],
        ];

        $this->fields['option'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'hidden' => ['index'],
            ],

            'front' => [
                'hidden' => [],
            ],
        ];

        $this->fields['option_lang'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            'multilang' => true,

            $this->config['env.default'] => [
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
                'hidden' => ['index'],
            ],
        ];

        $this->fields['required'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => __('Required'),

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'value' => 1,
                    'id' => 'required',
                    'class' => ['form-check-input'],
                ],

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
                'defaultIfNotExists' => ['add', 'edit'],
            ],

            'front' => [
                'hidden' => [],
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
                'hidden' => ['index'],
            ],
        ];
    }
}
