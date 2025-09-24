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

namespace App\Controller\Mod\Catform;

trait CatformTrait
{
    public string $code;

    public string $sdate;

    public ?string $cdate;

    public string $edate;

    public ?int $hierarchy = null;

    public ?int $maintenance = null;

    public string $name;

    public string $subname;

    public string $label;

    public string $richtext;

    public ?string $text = null;

    public ?int $status = null;

    public function init(): void
    {
        $this->context = 'female';

        $this->singularName = \sprintf(_n('%1$s category', '%1$s categories', 1), _n('Form', 'Forms', 1));
        $this->pluralName = \sprintf(_n('%1$s category', '%1$s categories', 2), _n('Form', 'Forms', 1));
        $this->singularNameWithParams = \sprintf(_n('%1$s category', '%1$s categories', 1, $this->context, $this->config->get('logger.locale')), _n('Form', 'Forms', 1));
        $this->pluralNameWithParams = \sprintf(_n('%1$s category', '%1$s categories', 2, $this->context, $this->config->get('logger.locale')), _n('Form', 'Forms', 1));

        $this->groupId = 50000;
        $this->weight = 50010;
        $this->faClass = 'fab fa-wpforms';

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

        $this->addWidget([
            'env' => 'back',
        ]);

        __('status-10');
        __('status-20');
        __('status-30');
        __('status-40');
        __('status-50');
        __('status-60');
    }

    public function setCustomFields(array $row = []): void
    {
        $this->status = $this->getStatusValue($row);
    }

    public function getStatusValue(array $row = [])
    {
        if ($this->auth->hasIdentity() && !empty($row['maintenance'])) {
            if (\in_array($this->auth->getIdentity()['_type'], ['user'], true) || !empty($this->auth->getIdentity()['maintainer'])) {
                $status = self::MAINTENANCE;
            }
        }

        if (empty($status) && !empty($row['sdate']) && !empty($row['edate'])) {
            $nowObj = $this->helper->Carbon()->now();

            $sdateObj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $row['sdate'], $this->config->get('db.1.timeZone'))->setTimezone(date_default_timezone_get());
            $edateObj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $row['edate'], $this->config->get('db.1.timeZone'))->setTimezone(date_default_timezone_get());

            if ($sdateObj->greaterThan($nowObj)) {
                $status = self::OPENING;
            } elseif ($edateObj->greaterThan($nowObj)) {
                if ($edateObj->copy()->subDay()->lessThanOrEqualTo($nowObj)) {
                    $status = self::CLOSING;
                } else {
                    $status = self::OPEN;
                }
            } elseif ($edateObj->lessThanOrEqualTo($nowObj)) {
                $status = self::CLOSED;
            }
        }

        return $status ?? self::UNDEFINED;
    }

    public function getStatusColor(int $status)
    {
        return '#'.($this->config->get('theme.mod.'.$this->modName.'.status.'.$status.'.color') ?? $this->config->get('mod.'.$this->modName.'.status.'.$status.'.color', 'ffffff'));
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

            $this->config->get('env.default') => [
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

        $this->fields['code'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config->get('env.default') => [
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

            'front' => [
                'hidden' => ['view'],
            ],
        ];

        $this->fields['sdate'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config->get('env.default') => [
                'label' => __('Opening date'),

                'type' => 'input',
                'attr' => [
                    'type' => 'datetime-local',
                    'id' => 'sdate',
                    'class' => ['form-control', 'datetimepicker'],
                    // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local
                    // the displayed date and time formats differ from the actual value;
                    // the displayed date and time are formatted according to the user's locale as reported by their operating system,
                    // whereas the date/time value is always formatted YYYY-MM-DDThh:mm.
                    // When the above value submitted to the server, for example, it will look like partydate=2017-06-01T08:30.
                    'pattern' => '[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}',
                    'required' => true,
                ],

                'help' => \sprintf(
                    _x('Relative to time zone %1$s.', 'female'),
                    '<i>'.date_default_timezone_get().'</i>'
                ),
            ],
        ];

        $this->fields['cdate'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config->get('env.default') => [
                'label' => __('Start closing date'),

                'type' => 'input',
                'attr' => [
                    'type' => 'datetime-local',
                    'id' => 'cdate',
                    'class' => ['form-control', 'datetimepicker'],
                    // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local
                    // the displayed date and time formats differ from the actual value;
                    // the displayed date and time are formatted according to the user's locale as reported by their operating system,
                    // whereas the date/time value is always formatted YYYY-MM-DDThh:mm.
                    // When the above value submitted to the server, for example, it will look like partydate=2017-06-01T08:30.
                    'pattern' => '[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}',
                ],

                'help' => \sprintf(
                    _x('Relative to time zone %1$s.', 'female'),
                    '<i>'.date_default_timezone_get().'</i>'
                ),
            ],
        ];

        $this->fields['edate'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            $this->config->get('env.default') => [
                'label' => __('Closing date'),

                'type' => 'input',
                'attr' => [
                    'type' => 'datetime-local',
                    'id' => 'edate',
                    'class' => ['form-control', 'datetimepicker'],
                    // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/datetime-local
                    // the displayed date and time formats differ from the actual value;
                    // the displayed date and time are formatted according to the user's locale as reported by their operating system,
                    // whereas the date/time value is always formatted YYYY-MM-DDThh:mm.
                    // When the above value submitted to the server, for example, it will look like partydate=2017-06-01T08:30.
                    'pattern' => '[0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}',
                    'required' => true,
                ],

                'help' => \sprintf(
                    _x('Relative to time zone %1$s.', 'female'),
                    '<i>'.date_default_timezone_get().'</i>'
                ),
            ],
        ];

        __('Relative to time zone %1$s.', 'default');
        __('Relative to time zone %1$s.', 'male');
        __('Relative to time zone %1$s.', 'female');

        $this->fields['subname'] = [
            'dbDefault' => false,

            'multilang' => true,

            $this->config->get('env.default') => [
                'label' => __('Subtitle'),

                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'id' => 'subname',
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

        $this->fields['label'] = [
            'dbDefault' => false,

            'multilang' => true,

            $this->config->get('env.default') => [
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

            $this->config->get('env.default') => [
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

            'front' => [
                'hidden' => ['index'],
            ],
        ];

        $this->fields['text'] = [
            // RedBeanPHP converte a 0 i valori FALSE
            'dbDefault' => null, // <--

            'multilang' => true,

            $this->config->get('env.default') => [
                'label' => __('Excerpt'),

                'type' => 'textarea',
                'attr' => [
                    'id' => 'text',
                    // 'cols' => 10,
                    'rows' => 5,
                    'class' => ['form-control'],
                ],

                'hidden' => ['index'],
            ],

            'front' => [
                'hidden' => [],
            ],
        ];

        $this->fields['hierarchy'] = [
            'dbDefault' => 0,

            $this->config->get('env.default') => [
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

        $this->fields['maintenance'] = [
            'dbDefault' => 0,

            $this->config->get('env.default') => [
                'label' => __('Under maintenance'),

                'type' => 'input',
                'attr' => [
                    'type' => 'checkbox',
                    'value' => 1,
                    'id' => 'maintenance',
                    'class' => ['form-check-input'],
                ],

                'help' => _x('It only allows administrators to view it even when it is closed.', $this->context),

                'skip' => ['search'],
                'hidden' => ['delete-bulk'],
                'defaultIfNotExists' => ['add', 'edit'],
            ],
        ];

        __('It only allows administrators to view it even when it is closed.', 'default');
        __('It only allows administrators to view it even when it is closed.', 'male');
        __('It only allows administrators to view it even when it is closed.', 'female');

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

            'front' => [
                'hidden' => ['index', 'view'],
            ],
        ];
    }
}
