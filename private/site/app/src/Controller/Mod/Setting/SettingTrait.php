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

namespace App\Controller\Mod\Setting;

trait SettingTrait
{
    public string $code;

    public string $type;

    public ?int $required = null;

    public array|string|null $option = null;

    public array|string|null $option_lang = null;

    public function init(): void
    {
        $this->context = 'female';

        $this->singularName = _n('Setting', 'Settings', 1);
        $this->pluralName = _n('Setting', 'Settings', 2);
        $this->singularNameWithParams = _n('Setting', 'Settings', 1, $this->context, $this->config['logger.locale']);
        $this->pluralNameWithParams = _n('Setting', 'Settings', 2, $this->context, $this->config['logger.locale']);

        $this->groupId = 90000;
        $this->weight = 90090;
        $this->faClass = 'fa-gear';

        if (!isDev()) {
            $this->allowedPerms = [
                '.api.index',
                '.back.index',
                '.api.view',
                '.back.view',
                '.api.edit',
                '.back.edit',
            ];
        }

        parent::init();
    }

    public function setCustomFields(array $row = []): void
    {
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
            'form' => [
                // 'input_color' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('color')) ),
                // 'input_date' => $this->helper->Nette()->Strings()->firstUpper(sprintf(__('input %1$s'), __('date'))),
                // 'input_datetime' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('datetime')) ),
                // 'input_datetime-local' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('date and time')) ),
                'input_email' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('email'))),
                // 'input_month' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('month')) ),
                // 'input_number_integer_gte0' => $this->helper->Nette()->Strings()->firstUpper(sprintf(__('input %1$s'), __('integer number greater than or equal to zero'))),
                // 'input_number_integer_gt0' => $this->helper->Nette()->Strings()->firstUpper(sprintf(__('input %1$s'), __('integer number greater than zero'))),
                // 'input_password' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('password')) ),
                // 'input_range' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('range')) ),
                // 'input_search' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('search')) ),
                'input_tel' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('telephone'))),
                'input_text' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('text'))),
                'input_text_nin' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('fiscal code or NIN (National Identification Number)'))),
                'input_text_vat' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('VAT'))),
                // 'input_time' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('time')) ),
                'input_url' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('url'))),
                // 'input_weeek' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('weeek')) ),
                // 'input_checkbox' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('checkbox')) ),
                // 'input_radio' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('radio')) ),
                'input_file' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('file attachment'))),
                // 'input_file_multiple' => $this->helper->Nette()->Strings()->firstUpper(sprintf(__('input %1$s'), __('multiple files attachment'))),
                'input_file_img' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('input %1$s'), __('file image'))),
                // 'input_hidden' => $this->helper->Nette()->Strings()->firstUpper( sprintf(__('input %1$s'), __('hidden')) ),

                'textarea' => $this->helper->Nette()->Strings()->firstUpper(__('textarea')),
                'textarea_richedit_simple' => $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('textarea %1$s'), \sprintf(__('with %1$s editor'), __('simple')))),

                // 'select' => $this->helper->Nette()->Strings()->firstUpper(__('select')),
                // 'checkbox' => $this->helper->Nette()->Strings()->firstUpper(sprintf(__('input %1$s'), __('checkbox'))),
                // 'radio' => $this->helper->Nette()->Strings()->firstUpper(sprintf(__('input %1$s'), __('radio'))),

                // 'country' => $this->container->get('Mod\Country\\'.ucfirst(static::$env))->singularName,
            ],
            default => array_merge(
                $this->getFieldTypes('form'),
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

            'api' => [
                'hidden' => [],
            ],
        ];

        $this->fields['code'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => __('Code'),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'code',
                    'maxlength' => 128,
                    'class' => ['form-control'],
                    'required' => true,
                ],
            ],
        ];

        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['code'][$this->config['env.default']]['attr']['required'] = false;
            $this->fields['code'][$this->config['env.default']]['attr']['disabled'] = true;
            $this->fields['code'][$this->config['env.default']]['skip'][] = 'edit';
        }

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
        ];

        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['type'][$this->config['env.default']]['attr']['required'] = false;
            $this->fields['type'][$this->config['env.default']]['attr']['disabled'] = true;
            $this->fields['type'][$this->config['env.default']]['skip'][] = 'edit';
        }

        $this->fields['option'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config['env.default'] => [
                'label' => _n('Value', 'Values', 2).' ('.__('Monolingual').')',
                'hidden' => [],
            ],
        ];

        $this->fields['option_lang'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            'multilang' => true,

            $this->config['env.default'] => [
                'label' => _n('Value', 'Values', 2).' ('.__('Multilingual').')',
                'hidden' => [],
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
                'value' => 1,

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
                'defaultIfNotExists' => ['add', 'edit'],
            ],
        ];

        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['required'][$this->config['env.default']]['attr']['disabled'] = true;
            $this->fields['required'][$this->config['env.default']]['skip'][] = 'edit';
        }

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

        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['active'][$this->config['env.default']]['hidden'][] = 'index';
            $this->fields['active'][$this->config['env.default']]['hidden'][] = 'view';
            $this->fields['active'][$this->config['env.default']]['hidden'][] = 'edit';
            $this->fields['active'][$this->config['env.default']]['hidden'][] = 'delete';
            $this->fields['active'][$this->config['env.default']]['skip'][] = 'edit';
        }
    }
}
