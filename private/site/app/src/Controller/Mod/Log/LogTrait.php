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

namespace App\Controller\Mod\Log;

trait LogTrait
{
    public ?string $environment;

    public ?string $lang_code;

    public ?string $auth_type = null;

    public ?string $auth_id = null;

    public ?string $username = null;

    public ?string $uri = null;

    public ?string $method = null;

    public ?string $remote_addr = null;

    public ?string $hostname = null;

    public ?string $hostbyaddr = null;

    public ?string $error = null;

    public ?string $text = null;

    public function init(): void
    {
        $this->singularName = _n('Log', 'Logs', 1);
        $this->pluralName = _n('Log', 'Logs', 2);
        $this->singularNameWithParams = _n('Log', 'Logs', 1, $this->context, $this->config['logger.locale']);
        $this->pluralNameWithParams = _n('Log', 'Logs', 2, $this->context, $this->config['logger.locale']);

        $this->groupId = 90000;
        $this->weight = 90080;
        $this->faClass = 'fa-book';

        $this->allowedPerms = [
            '.api.index',
            '.back.index',
            '.api.view',
            '.back.view',
            '.api.delete',
            '.back.delete',
        ];

        parent::init();

        $routeName = $this->routeParsingService->getRouteName();

        if (str_contains((string) $routeName, '.')) {
            [, $controller] = explode('.', (string) $routeName);

            if (\in_array($controller, ['catmember'], true)) {
                $this->allowedPerms = [
                    '.front.index',
                    '.front.view',
                ];
            }
        }

        $this->addWidget([
            'env' => 'back',
            'weight' => 999,
        ]);
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

        $string = \in_array($this->lang->code, ['it'], true) ? '%2$s %1$s' : '%1$s %2$s';

        $labels = [
            'id' => __('ID'),
            'message' => __('Message'),
            'channel' => __('Channel'),
            'level' => __('Level'),
            'time' => __('Modification date'),
            'environment' => __('Environment'),
            'lang_code' => __('Language'),
            'auth_type' => $this->helper->Nette()->Strings()->firstUpper(\sprintf($string, $this->helper->Nette()->Strings()->lower(__('Authentication')), $this->helper->Nette()->Strings()->lower(__('type')))),
            'auth_id' => $this->helper->Nette()->Strings()->firstUpper(\sprintf($string, $this->helper->Nette()->Strings()->lower(__('Authentication')), __('ID'))),
            'username' => __('Username'),
            'uri' => __('URI'),
            'method' => __('Method'),
            'remote_addr' => __('IP'),
            'hostname' => __('Hostname local'),
            'hostbyaddr' => __('Hostname ISP'),
            'error' => __('Error'),
            'text' => __('Text'),
            // 'query' => __('Query'),
        ];

        $visibleIndex = [
            'id',
            'message',
            // 'channel',
            'level',
            'time',
        ];

        $visibleDeleteBulk = [
            'id',
            'message',
        ];

        $visibleSearch = [
        ];

        $visibleBackView = [
        ];

        $visibleFrontView = [
            'level',
            'message',
            'time',
            // 'environment',
            // 'lang_code',
            'auth_type',
            'auth_id',
            'username',
            // 'uri',
            // 'method',
            'remote_addr',
            // 'hostname',
            'hostbyaddr',
        ];

        if ($this->auth->hasIdentity()) {
            if ($this->rbac->isGranted($this->auth->getIdentity()['_role_type'].'.'.static::$env.'.add')) {
                array_push(
                    $visibleIndex,
                    'environment',
                    'username'
                );
            }
        }

        foreach (\array_slice($this->getDbFields(), 1) as $field => $type) {
            $hidden = $hiddenApi = [];

            if (!\in_array($field, $visibleIndex, true)) {
                $hidden[] = 'index';
            }

            if (!\in_array($field, $visibleDeleteBulk, true)) {
                $hidden[] = 'delete-bulk';
            }

            if (!\in_array($field, $visibleSearch, true)) {
                $hidden[] = 'search';
            }

            if (!empty($visibleBackView)) {
                if (!\in_array($field, $visibleBackView, true)) {
                    $hidden[] = 'view';

                    $hiddenApi[] = 'index';
                    $hiddenApi[] = 'view';
                }
            }

            $this->fields[$field] = [
                $this->config['env.default'] => [
                    'label' => (\array_key_exists($field, $labels) ? $labels[$field] : __($field)),

                    'type' => 'input',
                    'attr' => [
                        'type' => 'text',
                        'id' => $field,
                        'class' => ['form-control'],
                    ],

                    // 'skip' => ['edit'],
                    'hidden' => $hidden,
                ],
            ];

            if (!empty($visibleFrontView)) {
                if (!\in_array($field, $visibleFrontView, true)) {
                    $hidden[] = 'view';

                    $this->fields[$field]['front']['hidden'] = $hidden;
                }
            }

            $this->fields[$field]['api']['hidden'] = $hiddenApi;
        }

        // http://stackoverflow.com/a/11276338
        $this->fields = ['message' => $this->fields['message']] + $this->fields;
        $this->fields = ['id' => $this->fields['id']] + $this->fields;
    }
}
