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

namespace App\Controller\Mod\User;

use App\Factory\Html\ViewHelperInterface;

trait UserTrait
{
    public int $catuser_id;

    public int $lang_id;

    public string $name;

    public string $username;

    public string $password;

    public string $email;

    public string $timezone;

    public string $private_key;

    public string $api_key;

    public string $catuser_name;

    public array $catuser_perms;

    public ?int $catuser_api_rl_hour = null;

    public ?int $catuser_api_rl_day = null;

    public ?int $catuser_api_log_level = null;

    public ?int $catuser_main = null;

    public function init(): void
    {
        $this->singularName = _n('User', 'Users', 1);
        $this->pluralName = _n('User', 'Users', 2);
        $this->singularNameWithParams = _n('User', 'Users', 1, $this->context, $this->config['logger.locale']);
        $this->pluralNameWithParams = _n('User', 'Users', 2, $this->context, $this->config['logger.locale']);

        $this->groupId = 80000;
        $this->weight = 80000;
        $this->faClass = 'fa-user';

        if ('api' === static::$env) {
            $this->authPasswordField = 'api_key';
        }

        array_unshift($this->actions, 'switch');

        $this->additionalPerms = array_merge_recursive($this->additionalPerms, [
            'delete.api' => [
                'delete-cache',
            ],
            'delete.back' => [
                'delete-cache',
            ],
        ]);

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

        $this->replaceKeysMap = [
            'cat'.$this->modName.'_id' => 'cat'.$this->modName.'_name',
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

        $this->checkSwitchUser();
    }

    public function setCustomFields(array $row = []): void
    {
        $this->{'cat'.$this->modName.'_name'} = $row['cat'.$this->modName.'_name'];
        $this->{'cat'.$this->modName.'_perms'} = !empty($row['cat'.$this->modName.'_perms']) ? $this->helper->Nette()->Json()->decode((string) $row['cat'.$this->modName.'_perms'], forceArrays: true) : [];
    }

    public function checkSwitchUser(): void
    {
        if ($this->auth->hasIdentity() && !$this->session->hasFlash('alert', static::$env.'.'.$this->modName.'.'.__FUNCTION__)) {
            if (\in_array($this->modName, [$this->auth->getIdentity()['_type']], true)) {
                $userIds = $this->session->get(static::$env.'.userIds', []);
                $lastUserId = !empty($userIds) ? end($userIds) : null;

                if (!empty($lastUserId) && $lastUserId !== (int) $this->auth->getIdentity()['id']) {
                    // FIXME - circular dependencies
                    $this->viewHelper = $this->container->get(ViewHelperInterface::class);

                    $this->session->addFlash([
                        'type' => 'alert',
                        'options' => [
                            'env' => static::$env, // <-
                            'type' => 'info',
                            'message' => \sprintf(__('To return to the previous user %1$s.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                                'href' => $this->helper->Url()->urlFor([
                                    'routeName' => static::$env.'.'.$this->modName.'.params',
                                    'data' => [
                                        'action' => 'switch',
                                        'params' => $lastUserId,
                                    ],
                                ]),
                            ]).'>'.__('click here').'</a>'),
                            'dismissible' => false,
                            'fixedBottom' => true,
                            'uniqueKey' => static::$env.'.'.$this->modName.'.'.__FUNCTION__,
                        ],
                    ]);
                }
            }
        }
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

        $this->fields['cat'.$this->modName.'_id'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => $this->container->get('Mod\Cat'.$this->modName.'\\'.ucfirst((string) static::$env))->singularName,

                'type' => 'select',
                'attr' => [
                    'id' => 'cat'.$this->modName.'_id',
                    'class' => ['form-select'],
                    'required' => true,
                ],

                'skip' => ['search'],
            ],
        ];

        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['hidden'][] = 'index';
            $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['hidden'][] = 'view';
            $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['hidden'][] = 'edit';
            $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['hidden'][] = 'delete';
            $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['skip'][] = 'edit';
        }

        if (!$this->rbac->isGranted('cat'.$this->modName.'.'.static::$env.'.add') && $this->auth->hasIdentity()) {
            $routeName = $this->routeParsingService->getRouteName();

            if (str_contains((string) $routeName, '.')) {
                [, $controller] = explode('.', (string) $routeName);
                if ($controller === $this->modName && ($action = $this->routeParsingService->getAction()) !== null) {
                    if ('edit' === $action) {
                        if (($id = $this->routeParsingService->getNumericId(null, 'params')) !== null) {
                            if ($this->auth->getIdentity()['id'] === (int) $id) {
                                $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['skip'][] = 'edit';
                                $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['attr']['disabled'] = true;
                            }
                        }
                    }
                }
            }
        }

        $this->fields['username'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => __('Username'),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'username',
                    'minlength' => $this->config['mod.'.$this->modName.'.auth.username.minLength'] ?? $this->config['auth.username.minLength'],
                    'maxlength' => 128,
                    'class' => ['form-control'],

                    // http://html5pattern.com/Names
                    // https://stackoverflow.com/a/76287241/3929620
                    // anchors ^ and $ are implicit, you don't have to put them
                    'pattern' => '[a-z0-9]{'.($this->config['mod.'.$this->modName.'.auth.username.minLength'] ?? $this->config['auth.username.minLength']).',}',
                ],

                'help' => \sprintf(__('Minimum %1$d characters lowercase and alphanumeric (%2$s).'), $this->config['mod.'.$this->modName.'.auth.username.minLength'] ?? $this->config['auth.username.minLength'], 'a-z0-9'),
            ],
        ];

        if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['username'][$this->config['env.default']]['attr']['required'] = true;
        } else {
            $this->fields['username'][$this->config['env.default']]['attr']['readonly'] = true;
            $this->fields['username'][$this->config['env.default']]['skip'][] = 'edit';
            $this->fields['username'][$this->config['env.default']]['help'] = false;

            // https://github.com/twbs/bootstrap/pull/36499
            $this->fields['username'][$this->config['env.default']]['attr']['class'] = ['form-control-plaintext'];
        }

        $this->fields['password'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => __('Password'),

                'type' => 'input',
                'attr' => [
                    'type' => 'password',
                    'id' => 'password',
                    'minlength' => $this->config['mod.'.$this->modName.'.auth.password.minLength'] ?? $this->config['auth.password.minLength'],
                    'maxlength' => 128,
                    'class' => ['form-control'],

                    // http://html5pattern.com/Passwords
                    // https://stackoverflow.com/a/2973495/3929620
                    // https://stackoverflow.com/a/40038780/3929620
                    // https://stackoverflow.com/a/41796955/3929620
                    // https://stackoverflow.com/a/45696119/3929620
                    // https://stackoverflow.com/a/41410006/3929620
                    // https://web.archive.org/web/20190130105445/https://blog.xenokore.com/a-safe-html5-password-regex/
                    // https://jsfiddle.net/z8dvhe06/
                    // https://www.the-art-of-web.com/javascript/validate-password/
                    // https://github.com/jbafford/PasswordStrengthBundle/blob/master/Validator/Constraints/PasswordStrengthValidator.php
                    // https://www.regular-expressions.info/posixbrackets.html
                    // https://stackoverflow.com/a/76287241/3929620
                    // anchors ^ and $ are implicit, you don't have to put them
                    // 'pattern' => '(?=^.{' . ($this->config['mod.'.$this->modName.'.auth.password.minLength'] ??  $this->config['auth.password.minLength']) . ',}$)((?=.*\d)|(?=.*\W+))(?![.\n])(?=.*[A-Z])(?=.*[a-z]).*$',
                    'pattern' => '(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W+)\S{'.($this->config['mod.'.$this->modName.'.auth.password.minLength'] ?? $this->config['auth.password.minLength']).',}',
                    'required' => true,
                ],

                'help' => [
                    \sprintf(
                        __('Minimum %1$d characters lowercase (%2$s), uppercase (%3$s), numbers (%4$s) and special (%5$s).'),
                        $this->config['mod.'.$this->modName.'.auth.password.minLength'] ?? $this->config['auth.password.minLength'],
                        'a-z',
                        'A-Z',
                        '0-9',
                        '.*#@!…'
                    ),
                    __('Spaces are not allowed.'),
                ],

                'skip' => ['search'],
                'hidden' => ['index', 'view', 'delete', 'delete-bulk'],
            ],
        ];

        $routeName = $this->routeParsingService->getRouteName();

        $controller = 'index';
        if (str_contains((string) $routeName, '.')) {
            [, $controller] = explode('.', (string) $routeName);
        }

        if ($controller === $this->modName && ($action = $this->routeParsingService->getAction()) !== null) {
            if ('add' === $action) {
                $this->fields['password'][$this->config['env.default']]['attr']['required'] = true;

                // https://goo.gl/9p2vKq
                // https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofilling-form-controls%3A-the-autocomplete-attribute
                $this->fields['username'][$this->config['env.default']]['attr']['autocomplete'] = 'username';
                $this->fields['password'][$this->config['env.default']]['attr']['autocomplete'] = 'new-password';
            }
        }

        /*if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['password'][$this->config['env.default']]['attr']['required'] = true;
        } else {
            $this->fields['password'][$this->config['env.default']]['hidden'][] = 'edit';
            $this->fields['password'][$this->config['env.default']]['skip'][] = 'edit';
        }*/

        if (!$this->rbac->isGranted($this->modName.'.api.add')) {
            unset($this->additionalApis['put']['/edit/{id}']['requestBody']['content']['application/json']['schema']['properties']['password'], $this->additionalApis['put']['/edit/{id}']['requestBody']['content']['application/json']['example']['password']);
        }

        $this->fields['email'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => __('Email'),

                'type' => 'input',
                'attr' => [
                    'type' => 'email',
                    'id' => 'email',
                    'maxlength' => 128,
                    'class' => ['form-control'],
                    'required' => true,
                ],

                'hidden' => ['delete-bulk'],
            ],
        ];

        $this->fields['lang_id'] = [
            'dbDefault' => $this->lang->id,

            $this->config['env.default'] => [
                'label' => __('Language'),

                'type' => 'select',
                'attr' => [
                    'id' => 'lang_id',
                    'class' => ['form-select'],
                    'required' => true,
                ],
                'value' => $this->lang->id,

                'skip' => ['search'],
                'hidden' => ['index', 'delete-bulk'],
            ],
        ];

        $this->fields['timezone'] = [
            'dbDefault' => date_default_timezone_get(),

            $this->config['env.default'] => [
                'label' => __('Timezone'),

                'type' => 'select',
                'attr' => [
                    'id' => 'timezone',
                    'class' => ['form-select'],
                    'required' => true,
                ],
                'value' => date_default_timezone_get(),

                'hidden' => ['index', 'delete-bulk'],
            ],
        ];

        $this->fields['private_key'] = [
            'dbDefault' => $this->helper->Nette()->Random()->generate($this->config['mod.'.$this->modName.'.auth.privateKey.minLength'] ?? $this->config['auth.privateKey.minLength']),

            $this->config['env.default'] => [
                'label' => __('Private key'),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'private_key',
                    // https://github.com/twbs/bootstrap/pull/36499
                    'class' => ['form-control-plaintext'],
                    'readonly' => true,
                ],

                'skip' => ['edit', 'search'],
                'hidden' => ['index', 'add', 'delete-bulk'],
                'default' => ['add'],
            ],
        ];

        if (!$this->rbac->isGranted('cat'.$this->modName.'.'.static::$env.'.add')) {
            $this->fields['private_key'][$this->config['env.default']]['hidden'][] = 'edit';
            $this->fields['private_key'][$this->config['env.default']]['hidden'][] = 'view';
            $this->fields['private_key'][$this->config['env.default']]['hidden'][] = 'delete';
        }

        $this->fields['api_key'] = [
            'dbDefault' => $this->helper->Nette()->Random()->generate($this->config['mod.'.$this->modName.'.api.key.length'] ?? $this->config['api.key.length'], $this->config['mod.'.$this->modName.'.api.key.charlist'] ?? $this->config['api.key.charlist']),

            $this->config['env.default'] => [
                'label' => __('API key'),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'api_key',
                    'class' => ['form-control'],
                    'minlength' => $this->config['mod.'.$this->modName.'.api.key.length'] ?? $this->config['api.key.length'],
                    'maxlength' => $this->config['mod.'.$this->modName.'.api.key.length'] ?? $this->config['api.key.length'],

                    // http://html5pattern.com/Passwords
                    // https://stackoverflow.com/a/2973495/3929620
                    // https://stackoverflow.com/a/40038780/3929620
                    // https://stackoverflow.com/a/41796955/3929620
                    // https://stackoverflow.com/a/45696119/3929620
                    // https://stackoverflow.com/a/41410006/3929620
                    // https://web.archive.org/web/20190130105445/https://blog.xenokore.com/a-safe-html5-password-regex/
                    // https://jsfiddle.net/z8dvhe06/
                    // https://www.the-art-of-web.com/javascript/validate-password/
                    // https://github.com/jbafford/PasswordStrengthBundle/blob/master/Validator/Constraints/PasswordStrengthValidator.php
                    // https://www.regular-expressions.info/posixbrackets.html
                    // https://stackoverflow.com/a/76287241/3929620
                    // anchors ^ and $ are implicit, you don't have to put them
                    'pattern' => '(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*\W+)(?!.*[\'"\\\]+)\S{'.($this->config['mod.'.$this->modName.'.api.key.length'] ?? $this->config['api.key.length']).'}',

                    'value' => $this->helper->Nette()->Random()->generate($this->config['mod.'.$this->modName.'.api.key.length'] ?? $this->config['api.key.length'], $this->config['mod.'.$this->modName.'.api.key.charlist'] ?? $this->config['api.key.charlist']),
                ],

                'help' => [
                    \sprintf(
                        __('%1$d characters lowercase (%2$s), uppercase (%3$s), numbers (%4$s) and special (%5$s).'),
                        $this->config['mod.'.$this->modName.'.api.key.length'] ?? $this->config['api.key.length'],
                        'a-z',
                        'A-Z',
                        '0-9',
                        '.*#@!…'
                    ),
                    \sprintf(
                        __('These characters are not allowed: %1$s.'),
                        '<code>\'</code>, <code>"</code>, <code>\</code>'
                    ),
                    __('Spaces are not allowed.'),
                ],

                'skip' => ['search'],
                'hidden' => ['index', 'delete-bulk'],
            ],

            'api' => [
                'hidden' => [],
                'default' => ['add'],
            ],
        ];

        if ($this->rbac->isGranted('cat'.$this->modName.'.'.static::$env.'.add')) {
            $this->fields['api_key'][$this->config['env.default']]['attr']['required'] = true;

            $this->fields['api_key']['api']['attr'] = $this->fields['api_key'][$this->config['env.default']]['attr'];
            $this->fields['api_key']['api']['attr']['required'] = false;
        } elseif ($this->rbac->isGranted('cat'.$this->modName.'.'.static::$env.'.view-api')) {
            $this->fields['api_key'][$this->config['env.default']]['default'][] = 'add';
            $this->fields['api_key'][$this->config['env.default']]['hidden'][] = 'add';
            $this->fields['api_key'][$this->config['env.default']]['skip'][] = 'edit';
            $this->fields['api_key'][$this->config['env.default']]['attr']['readonly'] = true;
            $this->fields['api_key'][$this->config['env.default']]['help'] = false;

            // https://github.com/twbs/bootstrap/pull/36499
            $this->fields['api_key'][$this->config['env.default']]['attr']['class'] = ['form-control-plaintext'];
        } else {
            $this->fields['api_key'][$this->config['env.default']]['default'][] = 'add';
            $this->fields['api_key'][$this->config['env.default']]['hidden'][] = 'add';
            $this->fields['api_key'][$this->config['env.default']]['hidden'][] = 'edit';
            $this->fields['api_key'][$this->config['env.default']]['hidden'][] = 'view';
            $this->fields['api_key'][$this->config['env.default']]['hidden'][] = 'delete';
            $this->fields['api_key'][$this->config['env.default']]['skip'][] = 'edit';
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

        if (!$this->rbac->isGranted('cat'.$this->modName.'.'.static::$env.'.add')) {
            $this->fields['active'][$this->config['env.default']]['hidden'][] = 'edit';
            $this->fields['active'][$this->config['env.default']]['skip'][] = 'edit';
        }
    }
}
