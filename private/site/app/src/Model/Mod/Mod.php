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

namespace App\Model\Mod;

use App\Model\Controller;
use Laminas\Stdlib\ArrayUtils;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

#[\AllowDynamicProperties]
class Mod extends Controller
{
    use ModActionTrait;
    use ModAlterAfterTrait;
    use ModAlterBeforeTrait;
    use ModAuthAdapterTrait;
    use ModDbTrait;
    use ModEventTrait;
    use ModFilterTrait;
    use ModSanitizeTrait;
    use ModSearchTrait;
    use ModValidateTrait;

    public string $modName;

    public string $controller;

    public string $singularName;

    public string $pluralName;

    public string $singularNameWithParams;

    public string $pluralNameWithParams;

    public int $id = -1;

    public string $mdate;

    public ?int $active = null;

    public array $fields;

    public array $fieldsMonolang = [];

    public array $fieldsMultilang = [];

    public array $fieldsSortable = [];

    public array $multilang = [];

    public array $additionalTables = [];

    public array $replaceKeysMap = [];

    public array $allowedPerms = ['.api.', '.back.'];

    public array $additionalPerms = [
        /*'delete.api' => [
            'delete-bulk',
        ],
        'delete.back' => [
            'delete-bulk',
        ],*/
    ];

    public array $allowedApis = ['/'];

    public array $additionalApis = [
        'delete' => [
            '/delete-bulk' => [
                '_perms' => [
                    'delete',
                ],
                // 'description' => '',
                'summary' => 'Bulk delete',
                'requestBody' => [
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                'type' => 'object',
                                'properties' => [
                                    'bulk_ids' => [
                                        'type' => 'array',
                                        'required' => true,
                                        'items' => [],
                                    ],
                                ],
                            ],
                            'example' => [
                                'bulk_ids' => [
                                    1,
                                    2,
                                    3,
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    200 => [
                        '$ref' => '#/components/responses/OK',
                    ],
                    400 => [
                        '$ref' => '#/components/responses/BadRequest',
                    ],
                    401 => [
                        '$ref' => '#/components/responses/Unauthorized',
                    ],
                    403 => [
                        '$ref' => '#/components/responses/Forbidden',
                    ],
                    404 => [
                        '$ref' => '#/components/responses/NotFound',
                    ],
                    405 => [
                        '$ref' => '#/components/responses/MethodNotAllowed',
                    ],
                    500 => [
                        '$ref' => '#/components/responses/InternalServerError',
                    ],
                ],
            ],
        ],
    ];

    public int $offset = 0;

    public string $orderBy = 'id';

    public string $orderDir = 'desc'; // <-- lower

    public string $internalOrder = 'a.id DESC';

    public bool $hasFilters = false;

    public int $groupId = 0;

    public int $weight = 0;

    public string $faClass = 'fa-missing';

    public array $errorDeps = [];

    // RedBeanPHP sql & args
    public array $dbData = [];

    public array $filterData = [];

    public array $searchData = [];

    public array $widgetData = [];

    public array $controls = ['search', 'add'];

    public array $skipRequiredValidationActions = ['delete', 'delete-bulk'];

    public array $actions = ['view', 'edit', 'delete', 'bulk'];

    public array $bulkActions = ['delete-bulk'];

    #[\Override]
    public function init(): void
    {
        $this->modName = $this->controller = $this->helper->Nette()->Strings()->lower($this->getShortName());

        $this->addAllListeners();
        $this->setDefaultFields();
        $this->setFieldsArray();
        $this->custom();
    }

    #[\Override]
    public function reInit(): void
    {
        $this->removeAllListeners();

        $this->init();
    }

    public function setDefaultFields(): void {}

    public function setFieldsArray(): void
    {
        if (!empty($this->fields)) {
            foreach ($this->fields as $key => $val) {
                foreach ($this->envs as $env) {
                    if (!isset($val[$env])) {
                        $val[$env] = [];
                    }

                    if ($env !== $this->config['env.default']) {
                        // http://codelegance.com/array-merging-in-php/
                        // $val[$env] = ArrayUtils::merge($val[$this->config['env.default']], $val[$env], true); // <-- with preserveNumericKeys
                        $val[$env] = array_merge($val[$this->config['env.default']], $val[$env]);
                    }

                    $this->fields[$key][$env] = $val[$env];
                }
            }

            foreach ($this->fields as $key => $val) {
                if (empty($val['multilang'])) {
                    $this->fieldsMonolang[$key] = $val;
                }
                if (!empty($val['multilang'])) {
                    $this->fieldsMultilang[$key] = $val;
                }
                if (!isset($val[static::$env]['hidden']) || (isset($val[static::$env]['hidden']) && !\in_array('index', $val[static::$env]['hidden'], true))) {
                    $this->fieldsSortable[$key] = $val;
                }
            }
        }
    }

    public function setId(int $id = -1): void
    {
        if ($id <= 0 && ($params = $this->routeParsingService->getParamsString()) !== null) {
            $params = explode('/', $params);

            if (!empty($params[0])) {
                $id = (int) $params[0];
            }
        }

        if ($id > 0) {
            $this->id = $id;
        }
    }

    public function setFields(): void
    {
        $row = $this->getOne();

        if (!empty($row['id'])) {
            foreach ($this->fields as $key => $val) {
                // https://php.watch/versions/8.2/dynamic-properties-deprecated#exempt
                $this->{$key} = $row[$key] ?? null; // <--
            }
        }

        $this->setMultilang();
        $this->setCustomFields($row);
    }

    public function setMultilang(): void
    {
        if (\count($this->fieldsMultilang) > 0) {
            $rows = $this->getOneMultilang();

            foreach ($this->fieldsMultilang as $key => $val) {
                foreach ($rows as $row) {
                    $this->multilang[$row['lang_id']][$key] = $row[$key];
                }
            }
        }
    }

    public function setCustomFields(array $row = []): void {}

    public function normaliseRow(array $row = [])
    {
        return array_merge(
            array_fill_keys(array_keys($this->fields), null),
            $row
        );
    }

    public function getPermLabel($perm)
    {
        return __($perm);
    }

    public function addDeps(array $deps = []): void
    {
        $this->container->set('deps', [$this->modName => $deps] + $this->deps);
    }

    public function removeDeps(string $modName): void
    {
        if (isset($this->deps[$modName])) {
            $deps = $this->deps;
            unset($deps[$modName]);

            $this->container->set('deps', $deps);
        }
    }

    public function addWidget(array $widget = []): void
    {
        $widget = ArrayUtils::merge(
            [
                'env' => static::$env,
                'controller' => $this->modName,
                'action' => 'index',
                'perm' => $this->modName.'.'.static::$env.'.index',
                'weight' => 0,
            ],
            $widget
        );

        $this->container->set('widgets', array_merge($this->widgets, [$widget]));
    }

    public function removeWidget(array $widget = []): void
    {
        $widget = ArrayUtils::merge(
            [
                'controller' => $this->modName,
                'action' => 'index',
            ],
            $widget
        );

        if (false !== ($key = $this->getWidgetId($widget))) {
            $widgets = $this->widgets;
            unset($widgets[$key]);

            $this->container->set('widgets', $widgets);
        }
    }

    public function getWidgetId(array $widget = [])
    {
        $widget = ArrayUtils::merge(
            [
                'controller' => $this->modName,
                'action' => 'index',
            ],
            $widget
        );

        return $this->helper->Arrays()->recursiveArraySearch('action', $widget['action'], array_filter($this->widgets, fn ($v) => $v['controller'] === $widget['controller']), true);
    }

    public function setRouteParams(): void
    {
        if (($params = $this->routeParsingService->getParamsString()) !== null) {
            $this->routeParamsArr = $this->routeParamsArrWithoutPg = explode('/', $params);

            // https://www.php.net/manual/en/language.operators.arithmetic.php#120654
            // https://stackoverflow.com/a/9153969/3929620
            // $number % 2 === 0    -----> true -> even (pari), false -> odd (dispari)
            // $number & 1          -----> true -> odd (dispari), false -> even (pari)
            if (\in_array($this->action, ['index'], true) && \count($this->routeParamsArr) & 1) {
                $this->routeParamsArrWithoutPg = \array_slice($this->routeParamsArr, 0, -1);
            }
        }

        if (empty($this->isXhr)) {
            $this->session->set('routeParamsArr', $this->routeParamsArr);
            $this->session->set('routeParamsArrWithoutPg', $this->routeParamsArrWithoutPg);
        }
    }

    public function setPagerAndOrder(): void
    {
        $this->offset = (($this->pager->pg - 1) * $this->pager->rowPerPage);

        if (\count($this->routeParamsArr) > 0) {
            if (!empty($this->routeParamsArr[1])) {
                if (\in_array($this->routeParamsArr[1], ['asc', 'desc'], true)) {
                    $this->orderDir = $this->routeParamsArr[1];
                }
            }

            if (!empty($this->routeParamsArr[0])) {
                if (\array_key_exists($this->routeParamsArr[0], $this->fieldsMultilang)) {
                    $this->orderBy = $this->routeParamsArr[0];

                    $this->internalOrder = 'b.'.$this->orderBy.' '.$this->orderDir;

                    // avoid left join duplicate rows
                    $this->internalOrder .= ', a.id asc';
                } elseif (\array_key_exists($this->routeParamsArr[0], $this->fieldsSortable)) {
                    $this->orderBy = $this->routeParamsArr[0];

                    $this->internalOrder = '';

                    if (\in_array($this->orderBy, array_keys($this->fields), true)) {
                        $this->internalOrder .= 'a.';
                    }

                    $this->internalOrder .= $this->orderBy.' '.$this->orderDir;

                    // avoid left join duplicate rows
                    if (!\in_array($this->orderBy, ['id'], true) && \array_key_exists('id', $this->fieldsSortable)) {
                        $this->internalOrder .= ', a.id asc';
                    }
                }
            }
        }

        if ($this->container->has('request')) {
            if (!empty($this->isXhr) && 'text/html' === $this->request->getHeaderLine('Accept')) {
                $found = true;
            }
        }

        // FIXED - SSE or Xhr fragment
        if (empty($this->isXhr) || !empty($found)) {
            $this->session->set('orderDir', $this->orderDir);
            $this->session->set('orderBy', $this->orderBy);
            $this->session->set('pg', $this->pager->pg);
        }
    }

    // https://stackoverflow.com/a/29730810/3929620
    // https://stackoverflow.com/a/55600915/3929620
    public function check(
        RequestInterface $request,
        ?callable $callbackSanitize = null,
        ?callable $callbackValidate = null,
        ?callable $callbackAlterBefore = null,
        ?callable $callbackAlterAfter = null
    ): void {
        $this->filterSubject = $this->container->make('filterSubject');

        if (\is_callable($callbackAlterBefore)) {
            \call_user_func($callbackAlterBefore, [$request]);
        } else {
            $this->alterBefore($request);
        }

        if (\is_callable($callbackSanitize)) {
            \call_user_func_array($callbackSanitize, [$request]);
        } else {
            $this->sanitize($request);
        }

        $this->filterSubject->apply($this->postData);

        if (\is_callable($callbackValidate)) {
            \call_user_func($callbackValidate, [$request]);
        } else {
            $this->validate($request);
        }

        if (!$this->filterSubject->apply($this->postData)) {
            $this->errors = array_column($this->filterSubject->getFailures()->getMessages(), 0);
        }

        if (0 === \count($this->errors)) {
            if (\is_callable($callbackAlterAfter)) {
                \call_user_func($callbackAlterAfter, [$request]);
            } else {
                $this->alterAfter($request);
            }
        }
    }

    public function checkDeps(
        RequestInterface $request
    ): void {
        $rowLast = $this->config['mod.'.static::$env.'.rowLast'] ?? $this->config['mod.rowLast'] ?? 1;

        if ((is_countable($this->deps) ? \count($this->deps) : 0) > 0) {
            $deps = $this->deps;
            while (false !== ($modName = $this->helper->Arrays()->recursiveArraySearch(null, $this->modName, $deps, true))) {
                // prevent while loop
                unset($deps[$modName]);

                $Mod = ($this->container->has('Mod\\'.ucfirst((string) $modName).'\\'.ucfirst((string) static::$env))) ? $this->container->get('Mod\\'.ucfirst((string) $modName).'\\'.ucfirst((string) static::$env)) : null;

                if (!empty($Mod)) {
                    if (method_exists($Mod, __FUNCTION__.$this->getShortName()) && \is_callable([$Mod, __FUNCTION__.$this->getShortName()])) {
                        \call_user_func_array([$Mod, __FUNCTION__.$this->getShortName()], [$request]);

                        if (!empty($Mod->errorDeps[$modName])) {
                            $this->errorDeps[$modName] = $Mod->errorDeps[$modName];
                        }
                    } elseif (\array_key_exists($this->modName.'_id', $Mod->fields)) {
                        // $Mod->removeAllListeners();

                        $eventName = 'event.'.static::$env.'.'.$modName.'.getCount.where';
                        $callback = function (GenericEvent $event) use ($Mod): void {
                            $Mod->dbData['sql'] .= ' AND a.'.$this->modName.'_id = :'.$this->modName.'_id';
                            $Mod->dbData['args'][$this->modName.'_id'] = $this->id;
                        };

                        $this->dispatcher->addListener($eventName, $callback);

                        $totRows = $Mod->getCount();

                        $this->dispatcher->removeListener($eventName, $callback);

                        // $Mod->addAllListeners();

                        if (!empty($totRows)) {
                            // $Mod->removeAllListeners();

                            $eventName = 'event.'.static::$env.'.'.$modName.'.getAll.where';
                            $callback = function (GenericEvent $event) use ($Mod): void {
                                $Mod->dbData['sql'] .= ' AND a.'.$this->modName.'_id = :'.$this->modName.'_id';
                                $Mod->dbData['args'][$this->modName.'_id'] = $this->id;
                            };

                            $this->dispatcher->addListener($eventName, $callback);

                            $rows = $Mod->getAll([
                                'nRows' => $rowLast,
                            ]);

                            $this->dispatcher->removeListener($eventName, $callback);

                            // $Mod->addAllListeners();

                            if ((is_countable($rows) ? \count($rows) : 0) > 0) {
                                $rows['totRows'] = $totRows;

                                $this->errorDeps[$modName] = $rows;
                            }
                        }
                    } elseif (\in_array($this->modName.'2'.$modName, $Mod->additionalTables, true)
                        || \in_array($modName.'2'.$this->modName, $Mod->additionalTables, true)
                    ) {
                        $table = (\in_array($this->modName.'2'.$modName, $Mod->additionalTables, true)) ? $this->modName.'2'.$modName : $modName.'2'.$this->modName;

                        $itemIds = $this->db->getCol('SELECT item_id FROM '.$this->config['db.1.prefix'].$table.' WHERE '.$this->modName.'_id = :'.$this->modName.'_id', [$this->modName.'_id' => $this->id]);

                        $totRows = is_countable($itemIds) ? \count($itemIds) : 0;

                        if (!empty($totRows)) {
                            // $Mod->removeAllListeners();

                            $eventName = 'event.'.static::$env.'.'.$modName.'.getAll.where';
                            $callback = function (GenericEvent $event) use ($Mod, $itemIds): void {
                                // https://stackoverflow.com/a/3108650
                                // Invalid parameter number: mixed named and positional parameters
                                // $this->dbData['sql'] .= ' AND a.id '.$this->db->genSlots( $itemIds, ' IN( %s ) ');
                                // $this->dbData['sql'] .= ' AND a.id IN('.$this->db->genSlots( $itemIds ).')';
                                $Mod->dbData['sql'] .= ' AND a.id IN('.implode(',', $itemIds).')';
                            };

                            $this->dispatcher->addListener($eventName, $callback);

                            $rows = $Mod->getAll([
                                'nRows' => $rowLast,
                            ]);

                            $this->dispatcher->removeListener($eventName, $callback);

                            // $Mod->addAllListeners();

                            if ((is_countable($rows) ? \count($rows) : 0) > 0) {
                                $rows['totRows'] = $totRows;

                                $this->errorDeps[$modName] = $rows;
                            }
                        }
                    }
                }
            }
        }

        if (\array_key_exists('parent_id', $this->fields)) {
            // $this->removeAllListeners();

            $eventName = 'event.'.static::$env.'.'.$modName.'.getCount.where';
            $callback = function (GenericEvent $event): void {
                $this->dbData['sql'] .= ' AND a.parent_id = :parent_id';
                $this->dbData['args']['parent_id'] = $this->id;
            };

            $this->dispatcher->addListener($eventName, $callback);

            $totRows = $this->getCount();

            $this->dispatcher->removeListener($eventName, $callback);

            // $this->addAllListeners();

            if (!empty($totRows)) {
                // $this->removeAllListeners();

                $eventName = 'event.'.static::$env.'.'.$modName.'.getAll.where';
                $callback = function (GenericEvent $event): void {
                    $this->dbData['sql'] .= ' AND a.parent_id = :parent_id';
                    $this->dbData['args']['parent_id'] = $this->id;
                };

                $this->dispatcher->addListener($eventName, $callback);

                $rows = $this->getAll([
                    'nRows' => $rowLast,
                ]);

                $this->dispatcher->removeListener($eventName, $callback);

                // $this->addAllListeners();

                if ((is_countable($rows) ? \count($rows) : 0) > 0) {
                    $rows['totRows'] = $totRows;

                    $this->errorDeps[$this->modName] = $rows;
                }
            }
        }
    }

    public function custom($keys = []): void
    {
        if ($this->_customCondition()) {
            if (!empty($this->config['mod.'.$this->modName.'.lang.arr'])
                && !empty($this->config['mod.'.$this->modName.'.lang.fallbackId'])) {
                $keys[] = 'lang';
            }

            if (!empty($this->config['mod.'.$this->modName.'.db.id'])) {
                $keys[] = 'db';
            }
        }

        if (\in_array('lang', $keys, true)) {
            if (!empty($this->config['mod.'.$this->modName.'.lang.arr'])
                && !empty($this->config['mod.'.$this->modName.'.lang.fallbackId'])) {
                $config = $this->config;

                if (empty($config['_lang.arr'])) {
                    $config['_lang.arr'] = $config['lang.arr'];
                }

                $config['lang.arr'] = $this->config['mod.'.$this->modName.'.lang.arr'];

                if (empty($config['_lang.fallbackId'])) {
                    $config['_lang.fallbackId'] = $config['lang.fallbackId'];
                }

                $config['lang.fallbackId'] = $this->config['mod.'.$this->modName.'.lang.fallbackId'];

                $this->container->set('config', $config);

                $this->translator->prepare();
            }
        }

        if (\in_array('db', $keys, true)) {
            if (!empty($this->config['mod.'.$this->modName.'.db.id'])) {
                $this->db->selectDatabase($this->config['mod.'.$this->modName.'.db.id']);
            }
        }
    }

    public function customRevert($keys = []): void
    {
        if ($this->_customCondition()) {
            if (!empty($this->config['mod.'.$this->modName.'.lang.arr'])
                && !empty($this->config['mod.'.$this->modName.'.lang.fallbackId'])) {
                $keys[] = 'lang';
            }

            if (!empty($this->config['mod.'.$this->modName.'.db.id'])) {
                $keys[] = 'db';
            }
        }

        if (\in_array('lang', $keys, true)) {
            if (!empty($this->config['mod.'.$this->modName.'.lang.arr'])
                && !empty($this->config['mod.'.$this->modName.'.lang.fallbackId'])) {
                $config = $this->config;

                if (!empty($config['_lang.arr'])) {
                    $config['lang.arr'] = $config['_lang.arr'];
                }

                if (!empty($config['_lang.fallbackId'])) {
                    $config['lang.fallbackId'] = $config['_lang.fallbackId'];
                }

                $this->container->set('config', $config);

                $this->translator->prepare();
            }
        }

        if (\in_array('db', $keys, true)) {
            if (!empty($this->config['mod.'.$this->modName.'.db.id'])) {
                $firstDbKey = current($this->db->dbKeys);

                $this->db->selectDatabase($firstDbKey);
            }
        }
    }

    protected function _customCondition()
    {
        if (\Safe\preg_match('~\.'.$this->modName.'~', (string) $this->routeParsingService->getRouteName())) {
            return true;
        }

        return false;
    }
}
