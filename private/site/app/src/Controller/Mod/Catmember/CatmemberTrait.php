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

namespace App\Controller\Mod\Catmember;

trait CatmemberTrait
{
    public string $name;

    public ?int $preselected = null;

    public ?int $main = null;

    public $perms;

    public array $mods = [];

    public function init(): void
    {
        $this->singularName = \sprintf(_n('%1$s group', '%1$s groups', 1), _n('Member', 'Members', 1));
        $this->pluralName = \sprintf(_n('%1$s group', '%1$s groups', 2), _n('Member', 'Members', 1));
        $this->singularNameWithParams = \sprintf(_n('%1$s group', '%1$s groups', 1, $this->context, $this->config->get('logger.locale')), _n('Member', 'Members', 1));
        $this->pluralNameWithParams = \sprintf(_n('%1$s group', '%1$s groups', 2, $this->context, $this->config->get('logger.locale')), _n('Member', 'Members', 1));

        $this->groupId = 60000;
        $this->weight = 60020;
        $this->faClass = 'fa-users-line';

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

            $this->config->get('env.default') => [
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
            'dbDefault' => $this->helper->Carbon()->now($this->config->get('db.1.timeZone'))->toDateTimeString(),

            $this->config->get('env.default') => [
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

            $this->config->get('env.default') => [
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

            $this->config->get('env.default') => [
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

        $this->fields['preselected'] = [
            'dbDefault' => 0,

            $this->config->get('env.default') => [
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

            $this->config->get('env.default') => [
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

            $this->config->get('env.default') => [
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
