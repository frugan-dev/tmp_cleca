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

namespace App\Controller\Mod\Member;

use App\Factory\Html\ViewHelperInterface;

trait MemberTrait
{
    public int $catmember_id;

    public ?int $country_id = null;

    public int $lang_id;

    public string $lastname;

    public string $firstname;

    public string $email;

    public string $password;

    public string $timezone;

    public string $private_key;

    public ?int $confirmed = null;

    public ?int $maintenance = null;

    public string $catmember_name;

    public array $catmember_perms;

    public ?int $catmember_main = null;

    public ?string $country_name = null;

    public ?int $catform_id = null;

    public array $form_ids;

    public function init(): void
    {
        $this->singularName = _n('Member', 'Members', 1);
        $this->pluralName = _n('Member', 'Members', 2);
        $this->singularNameWithParams = _n('Member', 'Members', 1, $this->context, $this->config['logger.locale']);
        $this->pluralNameWithParams = _n('Member', 'Members', 2, $this->context, $this->config['logger.locale']);

        $this->groupId = 60000;
        $this->weight = 60010;
        $this->faClass = 'fa-user-graduate';

        $this->authUsernameField = $this->authCheckField = 'email';
        $this->authNameFields = ['firstname', 'lastname'];

        $this->additionalPerms = array_merge_recursive($this->additionalPerms, [
            'index.back' => [
                'download',
                'download-bulk',
            ],
        ]);

        array_unshift($this->controls, 'reset');
        array_unshift($this->controls, 'export');

        array_unshift($this->bulkActions, 'download-bulk');

        array_unshift($this->actions, 'download');

        parent::init();

        $this->replaceKeysMap = [
            'cat'.$this->modName.'_id' => 'cat'.$this->modName.'_name',
            'country_id' => 'country_name',
        ];

        $this->fieldsSortable = $this->helper->Arrays()->replaceKeys(
            $this->fieldsSortable,
            $this->replaceKeysMap
        );

        $this->addDeps([
            'cat'.$this->modName,
            'country',
        ]);

        $this->controls = array_merge([
            'cat'.$this->modName.'_id',
            'country_id',
            'catform_id',
            'status_id',
        ], $this->controls);

        $this->addWidget([
            'env' => 'back',
        ]);
    }

    public function setCustomFields(array $row = []): void
    {
        $this->{'cat'.$this->modName.'_name'} = $row['cat'.$this->modName.'_name'];
        $this->{'cat'.$this->modName.'_perms'} = !empty($row['cat'.$this->modName.'_perms']) ? $this->helper->Nette()->Json()->decode((string) $row['cat'.$this->modName.'_perms'], forceArrays: true) : [];
        $this->country_name = $row['country_name'] ?? null;
        $this->{'cat'.$this->modName.'_main'} = $row['cat'.$this->modName.'_main'] ?? null;
        $this->catform_id = $row['catform_id'] ?? null;
        $this->form_ids = !empty($row['form_ids']) ? explode(',', (string) $row['form_ids']) : [];

        $this->filterValue->sanitize($this->form_ids, 'intvalArray');
    }

    public function checkConfirmed(): void
    {
        if ($this->auth->hasIdentity() && !$this->session->hasFlash('toast', static::$env.'.'.$this->modName.'.'.__FUNCTION__)) {
            if (\in_array($this->auth->getIdentity()['_type'], [$this->modName], true)) {
                if (empty($this->auth->getIdentity()['confirmed'])) {
                    // may have been confirmed with another browser..
                    $row = $this->getOne([
                        'id' => $this->auth->getIdentity()['id'],
                        'active' => true,
                    ]);
                    if (empty($row['confirmed'])) {
                        if ((int) $this->helper->Carbon()->now()->getTimestamp() >= ($timestamp = $this->session->get('timestamp.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__, 0))) {
                            $this->session->set('timestamp.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__, $this->helper->Carbon()->now()->addMinutes(1)->getTimestamp());

                            if (!empty($timestamp)) {
                                // FIXME - circular dependencies
                                $this->viewHelper = $this->container->get(ViewHelperInterface::class);

                                $this->session->addFlash([
                                    'type' => 'toast',
                                    'options' => [
                                        'env' => static::$env, // <-
                                        'type' => 'info',
                                        'message' => __('We sent you a confirmation email, didn\'t you get it?')
                                            .'<br>'.\sprintf(__('%1$s to send it again.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                                                'href' => $this->helper->Url()->urlFor([
                                                    'routeName' => static::$env.'.'.$this->modName.'.params',
                                                    'data' => [
                                                        'action' => 'setting',
                                                        'params' => $this->auth->getIdentity()['id'],
                                                    ],
                                                ]),
                                            ]).'>'.$this->helper->Nette()->Strings()->firstUpper(__('click here')).'</a>'),
                                        'autohide' => false,
                                        'uniqueKey' => static::$env.'.'.$this->modName.'.'.__FUNCTION__,
                                    ],
                                ]);
                            }
                        }
                    } else {
                        $this->auth->forceAuthenticate($row[$this->authUsernameField]);
                    }
                }
            }
        }
    }

    public function checkProfile(): void
    {
        if ($this->auth->hasIdentity() && !$this->session->hasFlash('toast', static::$env.'.'.$this->modName.'.'.__FUNCTION__)) {
            if (\in_array($this->auth->getIdentity()['_type'], [$this->modName], true)) {
                if (empty($this->auth->getIdentity()['country_id'])) {
                    if ((int) $this->helper->Carbon()->now()->getTimestamp() >= ($timestamp = $this->session->get('timestamp.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__, 0))) {
                        $this->session->set('timestamp.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__, $this->helper->Carbon()->now()->addMinutes(1)->getTimestamp());

                        if (!empty($timestamp)) {
                            // FIXME - circular dependencies
                            $this->viewHelper = $this->container->get(ViewHelperInterface::class);

                            $this->session->addFlash([
                                'type' => 'toast',
                                'options' => [
                                    'env' => static::$env, // <-
                                    'type' => 'info',
                                    'message' => __('Some personal data are missing.')
                                        .'<br>'.\sprintf(__('%1$s to complete them.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                                            'href' => $this->helper->Url()->urlFor([
                                                'routeName' => static::$env.'.'.$this->modName.'.params',
                                                'data' => [
                                                    'action' => 'edit',
                                                    'params' => $this->auth->getIdentity()['id'],
                                                ],
                                            ]),
                                        ]).'>'.$this->helper->Nette()->Strings()->firstUpper(__('click here')).'</a>'),
                                    'autohide' => false,
                                    'uniqueKey' => static::$env.'.'.$this->modName.'.'.__FUNCTION__,
                                ],
                            ]);
                        }
                    }
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

            'front' => [
                'hidden' => ['edit'],
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

            'front' => [
                'hidden' => ['edit'],
                'default' => ['edit', 'signup', 'fill'],
            ],
        ];

        $this->fields['lastname'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => __('Lastname'),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'lastname',
                    'maxlength' => 128,
                    'class' => ['form-control'],
                    // http://stackoverflow.com/a/35197389/3929620
                    // http://www.regextester.com/3319
                    // http://stackoverflow.com/a/41410006/3929620
                    // anchors ^ and $ are implicit, you don't have to put them
                    'pattern' => '([^\d\/\\\_!#$%&\(\)*+,.:=@\[\]^-`\{\}~]+)',
                    'required' => true,
                ],
            ],
        ];

        $this->fields['firstname'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => __('Firstname'),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'firstname',
                    'maxlength' => 128,
                    'class' => ['form-control'],
                    // http://stackoverflow.com/a/35197389/3929620
                    // http://www.regextester.com/3319
                    // http://stackoverflow.com/a/41410006/3929620
                    // anchors ^ and $ are implicit, you don't have to put them
                    'pattern' => '([^\d\/\\\_!#$%&\(\)*+,.:=@\[\]^-`\{\}~]+)',
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

        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['hidden'][] = 'index';
            $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['hidden'][] = 'view';
            $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['hidden'][] = 'edit';
            $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['hidden'][] = 'delete';
            $this->fields['cat'.$this->modName.'_id'][$this->config['env.default']]['skip'][] = 'edit';
        }

        $this->fields['country_id'] = [
            'dbDefault' => false,

            $this->config['env.default'] => [
                'label' => $this->container->get('Mod\Country\\'.ucfirst(static::$env))->singularName,

                'type' => 'select',
                'attr' => [
                    'id' => 'country_id',
                    'class' => ['form-select'],
                    'required' => true,
                ],

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
            ],

            'front' => [
                'skip' => ['signup'],
            ],
        ];

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
                $this->fields['email'][$this->config['env.default']]['attr']['autocomplete'] = 'username';
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

            'front' => [
                'default' => ['signup', 'fill'],
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

            'front' => [
                'default' => ['signup', 'fill'],
            ],
        ];

        $this->fields['private_key'] = [
            'dbDefault' => $this->helper->Nette()->Random()->generate($this->config['mod.'.$this->modName.'.auth.privateKey.minLength'] ?? $this->config['auth.privateKey.minLength']),

            $this->config['env.default'] => [
                'label' => __('Confirmation code'),

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

            'front' => [
                'default' => ['signup', 'fill'],
            ],
        ];

        if (!$this->rbac->isGranted('cat'.$this->modName.'.'.static::$env.'.add')) {
            $this->fields['private_key'][$this->config['env.default']]['hidden'][] = 'edit';
            $this->fields['private_key'][$this->config['env.default']]['hidden'][] = 'view';
            $this->fields['private_key'][$this->config['env.default']]['hidden'][] = 'delete';
        }

        $this->fields['confirmed'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => _x('Confirmed', $this->context),

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'value' => 1,
                    'id' => 'confirmed',
                    'class' => ['form-check-input'],
                ],

                'skip' => ['edit', 'search'],
                'hidden' => ['delete-bulk'],
                'defaultIfNotExists' => ['add'],
            ],

            'front' => [
                'default' => ['signup', 'fill'],
            ],
        ];

        __('Confirmed', 'default');
        __('Confirmed', 'male');
        __('Confirmed', 'female');

        $this->fields['maintainer'] = [
            'dbDefault' => 0,

            $this->config['env.default'] => [
                'label' => __('Maintainer'),

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'value' => 1,
                    'id' => 'maintainer',
                    'class' => ['form-check-input'],
                ],

                'help' => \sprintf(_nx('It allows you to work even when the %1$s is under maintenance.', 'It allows you to work even when the %1$s are under maintenance.', 2, $this->container->get('Mod\Catform\\'.ucfirst(static::$env))->context), $this->helper->Nette()->Strings()->lower($this->container->get('Mod\Catform\\'.ucfirst(static::$env))->pluralName)),

                'skip' => ['search'],
                'hidden' => ['index', 'delete-bulk'],
                'defaultIfNotExists' => ['add', 'edit'],
            ],
        ];

        if (!$this->rbac->isGranted('cat'.$this->modName.'.'.static::$env.'.add')) {
            $this->fields['maintainer'][$this->config['env.default']]['hidden'][] = 'edit';
            $this->fields['maintainer'][$this->config['env.default']]['skip'][] = 'edit';
        }

        _n('It allows you to work even when the %1$s is under maintenance.', 'It allows you to work even when the %1$s are under maintenance.', 1, 'default');
        _n('It allows you to work even when the %1$s is under maintenance.', 'It allows you to work even when the %1$s are under maintenance.', 1, 'male');
        _n('It allows you to work even when the %1$s is under maintenance.', 'It allows you to work even when the %1$s are under maintenance.', 1, 'female');

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
