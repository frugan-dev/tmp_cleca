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

namespace App\Model\Mod\Api;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Symfony\Component\EventDispatcher\GenericEvent;

trait V1ActionGetTrait
{
    protected function v1ActionGet(RequestInterface $request, ResponseInterface $response, $args)
    {
        try {
            if (!empty($this->getData['fancybox'])) {
                return null;
            }

            if (method_exists($this, __FUNCTION__.$this->actionCamelCase) && \is_callable([$this, __FUNCTION__.$this->actionCamelCase])) {
                $return = \call_user_func_array([$this, __FUNCTION__.$this->actionCamelCase], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            } else {
                throw new HttpMethodNotAllowedException($request);
            }
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage(), [
                'exception' => $e,
            ]);

            $this->errors[] = __('A technical problem has occurred, try again later.');

            // rethrow it
            if ($this->config['debug.enabled']) {
                throw $e;
            }
        }
    }

    protected function v1ActionGetIndex(RequestInterface $request, ResponseInterface $response, $args)
    {
        if ($this->rbac->isGranted($this->controller.'.'.static::$env.'.'.$this->action)) {
            if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            } else {
                throw new HttpMethodNotAllowedException($request);
            }
        } else {
            throw new HttpUnauthorizedException($request);
        }
    }

    protected function v1ActionGetIndexByFieldId(RequestInterface $request, ResponseInterface $response, $args)
    {
        if ($this->rbac->isGranted($this->controller.'.'.static::$env.'.index')) {
            if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            } else {
                throw new HttpMethodNotAllowedException($request);
            }
        } else {
            throw new HttpUnauthorizedException($request);
        }
    }

    protected function v1ActionGetIndexFull(RequestInterface $request, ResponseInterface $response, $args)
    {
        if ($this->rbac->isGranted($this->controller.'.'.static::$env.'.index')) {
            if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            } else {
                throw new HttpMethodNotAllowedException($request);
            }
        } else {
            throw new HttpUnauthorizedException($request);
        }
    }

    protected function v1ActionGetIndexFullByFieldId(RequestInterface $request, ResponseInterface $response, $args)
    {
        if ($this->rbac->isGranted($this->controller.'.'.static::$env.'.index')) {
            if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            } else {
                throw new HttpMethodNotAllowedException($request);
            }
        } else {
            throw new HttpUnauthorizedException($request);
        }
    }

    protected function v1ActionGetView(RequestInterface $request, ResponseInterface $response, $args)
    {
        if ($this->rbac->isGranted($this->controller.'.'.static::$env.'.'.$this->action)) {
            if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            } else {
                throw new HttpMethodNotAllowedException($request);
            }
        } else {
            throw new HttpUnauthorizedException($request);
        }
    }

    protected function _v1ActionGetIndex(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->responseData['response'] = [];

        $this->setRouteParams();

        if (($paramId = array_search('multilang', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $multilang = (string) $this->routeParamsArr[$paramId + 1];
            }
        }

        $this->pager->create($this->getCount([
            'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
        ]), $this->config['mod.'.static::$env.'.'.$this->modName.'.pagination.rowPerPage'] ?? $this->config['mod.'.$this->modName.'.pagination.rowPerPage'] ?? null);

        $this->setPagerAndOrder();

        if ($this->pager->totRows > 0) {
            $result = $this->getAll([
                'order' => $this->internalOrder,
                'offset' => $this->offset,
                'nRows' => $this->pager->rowPerPage,
                'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
            ]);

            if (\count($result) > 0) {
                foreach ($result as $row) {
                    $this->rowData = [];

                    foreach ($this->fieldsMonolang as $key => $val) {
                        $filteredKey = $key;

                        $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                        $this->filterValue->sanitize($filteredKey, 'titlecase');
                        $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                        if (isset($val[static::$env]['hidden']) && \in_array($this->action, $val[static::$env]['hidden'], true)) {
                            continue;
                        }

                        $this->rowData[$key] = $row[$key] ?? '';

                        if (method_exists($this, __FUNCTION__.$filteredKey) && \is_callable([$this, __FUNCTION__.$filteredKey])) {
                            \call_user_func_array([$this, __FUNCTION__.$filteredKey], [$key, $row]);
                        }
                    }

                    if (\count($this->fieldsMultilang) > 0) {
                        $multiRows = !empty($multilang) ? $this->getOneMultilang([
                            'id' => $row['id'],
                        ]) : false;

                        foreach ($this->fieldsMultilang as $key => $val) {
                            $filteredKey = $key;

                            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                            $this->filterValue->sanitize($filteredKey, 'titlecase');
                            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                            if (isset($val[static::$env]['hidden']) && \in_array($this->action, $val[static::$env]['hidden'], true)) {
                                continue;
                            }

                            if (!empty($multiRows)) {
                                foreach ($multiRows as $multiRow) {
                                    $this->rowData['_'.$this->config['lang.arr'][$multiRow['lang_id']]['isoCode']][$key] = $multiRow[$key] ?? '';

                                    if (method_exists($this, __FUNCTION__.'Multilang'.$filteredKey) && \is_callable([$this, __FUNCTION__.'Multilang'.$filteredKey])) {
                                        \call_user_func_array([$this, __FUNCTION__.'Multilang'.$filteredKey], [$key, $row, $multiRow['lang_id']]);
                                    }
                                }
                            } else {
                                $this->rowData[$key] = $row[$key] ?? '';

                                if (method_exists($this, __FUNCTION__.'Multilang'.$filteredKey) && \is_callable([$this, __FUNCTION__.'Multilang'.$filteredKey])) {
                                    \call_user_func_array([$this, __FUNCTION__.'Multilang'.$filteredKey], [$key, $row]);
                                }
                            }
                        }
                    }

                    $this->responseData['response'][] = $this->rowData;
                }
            }
        }
    }

    protected function _v1ActionGetIndexByFieldId(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->responseData['response'] = [];

        $this->setRouteParams();

        if (($paramId = array_search('multilang', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $multilang = (string) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (($paramId = array_search('field_name', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $fieldName = (string) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (($paramId = array_search('field_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $fieldId = (int) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (!empty($fieldName) && !empty($fieldId)) {
            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getCount.where';
            $callback = function (GenericEvent $event) use ($fieldName, $fieldId): void {
                $this->dbData['sql'] .= ' AND a.'.$fieldName.' = :'.$fieldName;
                $this->dbData['args'][$fieldName] = (int) $fieldId;
            };

            $this->dispatcher->addListener($eventName, $callback);

            $this->pager->create($this->getCount([
                'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
            ]), $this->config['mod.'.static::$env.'.'.$this->modName.'.pagination.rowPerPage'] ?? $this->config['mod.'.$this->modName.'.pagination.rowPerPage'] ?? null);

            $this->dispatcher->removeListener($eventName, $callback);

            $this->setPagerAndOrder();

            if ($this->pager->totRows > 0) {
                $eventName = 'event.'.static::$env.'.'.$this->modName.'.getAll.where';
                $callback = function (GenericEvent $event) use ($fieldName, $fieldId): void {
                    $this->dbData['sql'] .= ' AND a.'.$fieldName.' = :'.$fieldName;
                    $this->dbData['args'][$fieldName] = (int) $fieldId;
                };

                $this->dispatcher->addListener($eventName, $callback);

                $result = $this->getAll([
                    'order' => $this->internalOrder,
                    'offset' => $this->offset,
                    'nRows' => $this->pager->rowPerPage,
                    'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
                ]);

                $this->dispatcher->removeListener($eventName, $callback);

                if (\count($result) > 0) {
                    foreach ($result as $row) {
                        $this->rowData = [];

                        foreach ($this->fieldsMonolang as $key => $val) {
                            $filteredKey = $key;

                            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                            $this->filterValue->sanitize($filteredKey, 'titlecase');
                            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                            if (isset($val[static::$env]['hidden']) && \in_array($this->action, $val[static::$env]['hidden'], true)) {
                                continue;
                            }

                            $this->rowData[$key] = $row[$key] ?? '';

                            if (method_exists($this, __FUNCTION__.$filteredKey) && \is_callable([$this, __FUNCTION__.$filteredKey])) {
                                \call_user_func_array([$this, __FUNCTION__.$filteredKey], [$key, $row]);
                            }
                        }

                        if (\count($this->fieldsMultilang) > 0) {
                            $multiRows = !empty($multilang) ? $this->getOneMultilang([
                                'id' => $row['id'],
                            ]) : false;

                            foreach ($this->fieldsMultilang as $key => $val) {
                                $filteredKey = $key;

                                $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                                $this->filterValue->sanitize($filteredKey, 'titlecase');
                                $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                                if (isset($val[static::$env]['hidden']) && \in_array($this->action, $val[static::$env]['hidden'], true)) {
                                    continue;
                                }

                                if (!empty($multiRows)) {
                                    foreach ($multiRows as $multiRow) {
                                        $this->rowData['_'.$this->config['lang.arr'][$multiRow['lang_id']]['isoCode']][$key] = $multiRow[$key] ?? '';

                                        if (method_exists($this, __FUNCTION__.'Multilang'.$filteredKey) && \is_callable([$this, __FUNCTION__.'Multilang'.$filteredKey])) {
                                            \call_user_func_array([$this, __FUNCTION__.'Multilang'.$filteredKey], [$key, $row, $multiRow['lang_id']]);
                                        }
                                    }
                                } else {
                                    $this->rowData[$key] = $row[$key] ?? '';

                                    if (method_exists($this, __FUNCTION__.'Multilang'.$filteredKey) && \is_callable([$this, __FUNCTION__.'Multilang'.$filteredKey])) {
                                        \call_user_func_array([$this, __FUNCTION__.'Multilang'.$filteredKey], [$key, $row]);
                                    }
                                }
                            }
                        }

                        $this->responseData['response'][] = $this->rowData;
                    }
                }
            }
        }
    }

    protected function _v1ActionGetIndexFull(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->responseData['response'] = [];

        $this->setRouteParams();

        if (($paramId = array_search('multilang', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $multilang = (string) $this->routeParamsArr[$paramId + 1];
            }
        }

        $this->pager->create();

        $this->setPagerAndOrder();

        $result = $this->getAll([
            'order' => $this->internalOrder,
            'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
        ]);

        if (\count($result) > 0) {
            foreach ($result as $row) {
                $this->rowData = [];

                foreach ($this->fieldsMonolang as $key => $val) {
                    $filteredKey = $key;

                    $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                    $this->filterValue->sanitize($filteredKey, 'titlecase');
                    $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                    if (isset($val[static::$env]['hidden']) && \in_array($this->action, $val[static::$env]['hidden'], true)) {
                        continue;
                    }

                    $this->rowData[$key] = $row[$key] ?? '';

                    if (method_exists($this, __FUNCTION__.$filteredKey) && \is_callable([$this, __FUNCTION__.$filteredKey])) {
                        \call_user_func_array([$this, __FUNCTION__.$filteredKey], [$key, $row]);
                    }
                }

                if (\count($this->fieldsMultilang) > 0) {
                    $multiRows = !empty($multilang) ? $this->getOneMultilang([
                        'id' => $row['id'],
                    ]) : false;

                    foreach ($this->fieldsMultilang as $key => $val) {
                        $filteredKey = $key;

                        $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                        $this->filterValue->sanitize($filteredKey, 'titlecase');
                        $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                        if (isset($val[static::$env]['hidden']) && \in_array($this->action, $val[static::$env]['hidden'], true)) {
                            continue;
                        }

                        if (!empty($multiRows)) {
                            foreach ($multiRows as $multiRow) {
                                $this->rowData['_'.$this->config['lang.arr'][$multiRow['lang_id']]['isoCode']][$key] = $multiRow[$key] ?? '';

                                if (method_exists($this, __FUNCTION__.'Multilang'.$filteredKey) && \is_callable([$this, __FUNCTION__.'Multilang'.$filteredKey])) {
                                    \call_user_func_array([$this, __FUNCTION__.'Multilang'.$filteredKey], [$key, $row, $multiRow['lang_id']]);
                                }
                            }
                        } else {
                            $this->rowData[$key] = $row[$key] ?? '';

                            if (method_exists($this, __FUNCTION__.'Multilang'.$filteredKey) && \is_callable([$this, __FUNCTION__.'Multilang'.$filteredKey])) {
                                \call_user_func_array([$this, __FUNCTION__.'Multilang'.$filteredKey], [$key, $row]);
                            }
                        }
                    }
                }

                $this->responseData['response'][] = $this->rowData;
            }
        }
    }

    protected function _v1ActionGetIndexFullByFieldId(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->responseData['response'] = [];

        $this->setRouteParams();

        if (($paramId = array_search('multilang', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $multilang = (string) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (($paramId = array_search('field_name', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $fieldName = (string) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (($paramId = array_search('field_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $fieldId = (int) $this->routeParamsArr[$paramId + 1];
            }
        }

        $this->pager->create();

        $this->setPagerAndOrder();

        if (!empty($fieldName) && !empty($fieldId)) {
            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getAll.where';
            $callback = function (GenericEvent $event) use ($fieldName, $fieldId): void {
                $this->dbData['sql'] .= ' AND a.'.$fieldName.' = :'.$fieldName;
                $this->dbData['args'][$fieldName] = (int) $fieldId;
            };

            $this->dispatcher->addListener($eventName, $callback);

            $result = $this->getAll([
                'order' => $this->internalOrder,
                'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
            ]);

            $this->dispatcher->removeListener($eventName, $callback);

            if (\count($result) > 0) {
                foreach ($result as $row) {
                    $this->rowData = [];

                    foreach ($this->fieldsMonolang as $key => $val) {
                        $filteredKey = $key;

                        $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                        $this->filterValue->sanitize($filteredKey, 'titlecase');
                        $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                        if (isset($val[static::$env]['hidden']) && \in_array($this->action, $val[static::$env]['hidden'], true)) {
                            continue;
                        }

                        $this->rowData[$key] = $row[$key] ?? '';

                        if (method_exists($this, __FUNCTION__.$filteredKey) && \is_callable([$this, __FUNCTION__.$filteredKey])) {
                            \call_user_func_array([$this, __FUNCTION__.$filteredKey], [$key, $row]);
                        }
                    }

                    if (\count($this->fieldsMultilang) > 0) {
                        $multiRows = !empty($multilang) ? $this->getOneMultilang([
                            'id' => $row['id'],
                        ]) : false;

                        foreach ($this->fieldsMultilang as $key => $val) {
                            $filteredKey = $key;

                            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                            $this->filterValue->sanitize($filteredKey, 'titlecase');
                            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                            if (isset($val[static::$env]['hidden']) && \in_array($this->action, $val[static::$env]['hidden'], true)) {
                                continue;
                            }

                            if (!empty($multiRows)) {
                                foreach ($multiRows as $multiRow) {
                                    $this->rowData['_'.$this->config['lang.arr'][$multiRow['lang_id']]['isoCode']][$key] = $multiRow[$key] ?? '';

                                    if (method_exists($this, __FUNCTION__.'Multilang'.$filteredKey) && \is_callable([$this, __FUNCTION__.'Multilang'.$filteredKey])) {
                                        \call_user_func_array([$this, __FUNCTION__.'Multilang'.$filteredKey], [$key, $row, $multiRow['lang_id']]);
                                    }
                                }
                            } else {
                                $this->rowData[$key] = $row[$key] ?? '';

                                if (method_exists($this, __FUNCTION__.'Multilang'.$filteredKey) && \is_callable([$this, __FUNCTION__.'Multilang'.$filteredKey])) {
                                    \call_user_func_array([$this, __FUNCTION__.'Multilang'.$filteredKey], [$key, $row]);
                                }
                            }
                        }
                    }

                    $this->responseData['response'][] = $this->rowData;
                }
            }
        }
    }

    protected function _v1ActionGetView(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->setId();

        if ($this->existStrict([
            'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
        ])) {
            $this->setFields();

            foreach ($this->fieldsMonolang as $key => $val) {
                $filteredKey = $key;

                $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                $this->filterValue->sanitize($filteredKey, 'titlecase');
                $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                if (isset($val[static::$env]['hidden']) && \in_array($this->action, $val[static::$env]['hidden'], true)) {
                    continue;
                }

                $this->rowData[$key] = $this->{$key} ?? '';

                if (method_exists($this, __FUNCTION__.$filteredKey) && \is_callable([$this, __FUNCTION__.$filteredKey])) {
                    \call_user_func_array([$this, __FUNCTION__.$filteredKey], [$key]);
                }
            }

            if (\count($this->fieldsMultilang) > 0) {
                foreach ($this->lang->arr as $langId => $langRow) {
                    foreach ($this->fieldsMultilang as $key => $val) {
                        $filteredKey = $key;

                        $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                        $this->filterValue->sanitize($filteredKey, 'titlecase');
                        $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                        if (isset($val[static::$env]['hidden']) && \in_array($this->action, $val[static::$env]['hidden'], true)) {
                            continue;
                        }

                        $this->rowData['_'.$langRow['isoCode']][$key] = $this->multilang[$langId][$key] ?? '';

                        if (method_exists($this, __FUNCTION__.'Multilang'.$filteredKey) && \is_callable([$this, __FUNCTION__.'Multilang'.$filteredKey])) {
                            \call_user_func_array([$this, __FUNCTION__.'Multilang'.$filteredKey], [$key]);
                        }
                    }
                }
            }

            $this->responseData['response'] = $this->rowData;
        } else {
            throw new HttpNotFoundException($request);
        }
    }
}
