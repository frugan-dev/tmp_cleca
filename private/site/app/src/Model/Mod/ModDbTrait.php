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

use Laminas\Stdlib\ArrayUtils;
use Symfony\Component\EventDispatcher\GenericEvent;

trait ModDbTrait
{
    public function exist($params = [])
    {
        $params = ArrayUtils::merge(
            [
                'id' => $this->id,
                'active' => null,
                // TODO
                'dbKey' => $this->db->getPrimaryDbKey(),
            ],
            $params
        );

        // TODO
        $prefix = $this->db->getPrefix($this->modName, $params['dbKey']);
        $tableName = $prefix.$this->modName;

        $this->dbData = [];

        $this->dbData['sql'] = ' '.$tableName.'.id = :id ';

        $this->dbData['args']['id'] = $params['id'];

        if (\array_key_exists('active', $this->fields) && \is_bool($params['active'])) {
            $this->dbData['sql'] .= ' AND '.$tableName.'.active = :active ';

            $this->dbData['args']['active'] = $params['active'];
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.where');

        // TODO
        $primaryDbKey = $this->db->getPrimaryDbKey();
        if ($params['dbKey'] !== $primaryDbKey) {
            $this->db->selectDatabase($params['dbKey']);
        }

        $result = $this->db->findOne($tableName, $this->dbData['sql'], $this->dbData['args']);

        if ($params['dbKey'] !== $primaryDbKey) {
            $this->db->selectDatabase($primaryDbKey);
        }

        return $result;
    }

    public function existStrict($params = [])
    {
        $params = ArrayUtils::merge(
            [
                'id' => $this->id,
                'active' => null,
            ],
            $params
        );

        $this->dbData = [];
        $this->dbData['args'] = [];

        $this->dbData['sql'] = 'SELECT';

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.preSelect');

        $this->dbData['sql'] .= ' a.id';

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.select');

        $this->dbData['sql'] .= ' FROM '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.' AS a';

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.join');

        $this->dbData['sql'] .= ' WHERE 1';

        if (is_numeric($params['id'])) { // <--
            $this->dbData['sql'] .= ' AND a.id = :id '; // <--

            $this->dbData['args']['id'] = $params['id'];
        }

        if (\array_key_exists('active', $this->fields) && \is_bool($params['active'])) {
            $this->dbData['sql'] .= ' AND a.active = :active';

            $this->dbData['args']['active'] = $params['active'];
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.where');
        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.group');
        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.having');

        return $this->db->getRow($this->dbData['sql'], $this->dbData['args']);
    }

    public function getCount($params = [])
    {
        $params = ArrayUtils::merge(
            [
                'offset' => 0,
                'nRows' => PHP_INT_MAX,
                'active' => null,
            ],
            $params
        );

        $this->dbData = [];
        $this->dbData['args'] = [];

        $this->dbData['sql'] = 'SELECT';

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.preSelect');

        $this->dbData['sql'] .= ' a.id';

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.select');

        $this->dbData['sql'] .= ' FROM '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.' AS a';

        if (\count($this->fieldsMultilang) > 0) {
            $this->dbData['sql'] .= ' JOIN '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.'_lang AS b';
            $this->dbData['sql'] .= ' ON a.id = b.item_id';
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.join');

        $this->dbData['sql'] .= ' WHERE 1';

        if (\count($this->fieldsMultilang) > 0) {
            $this->dbData['sql'] .= ' AND b.lang_id = :lang_id';

            $this->dbData['args']['lang_id'] = $this->lang->id;
        }

        if (\array_key_exists('active', $this->fields) && \is_bool($params['active'])) {
            $this->dbData['sql'] .= ' AND a.active = :active';

            $this->dbData['args']['active'] = $params['active'];
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.where');
        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.group');
        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.having');

        // FIXME
        return (int) $this->db->exec($this->dbData['sql'], $this->dbData['args']);
    }

    public function getOne($params = [])
    {
        $params = ArrayUtils::merge(
            [
                'id' => $this->id,
                'active' => null,
            ],
            $params
        );

        $this->dbData = [];
        $this->dbData['args'] = [];

        $this->dbData['sql'] = 'SELECT';

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.preSelect');

        $this->dbData['sql'] .= ' a.*';

        if (\count($this->fieldsMultilang) > 0) {
            foreach ($this->fieldsMultilang as $key => $val) {
                $this->dbData['sql'] .= ', b.'.$key.' AS '.$key;
            }
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.select');

        $this->dbData['sql'] .= ' FROM '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.' AS a';

        if (\count($this->fieldsMultilang) > 0) {
            $this->dbData['sql'] .= ' JOIN '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.'_lang AS b';
            $this->dbData['sql'] .= ' ON a.id = b.item_id';
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.join');

        $this->dbData['sql'] .= ' WHERE 1';

        if (is_numeric($params['id'])) { // <--
            $this->dbData['sql'] .= ' AND a.id = :id '; // <--

            $this->dbData['args']['id'] = $params['id'];
        }

        if (\count($this->fieldsMultilang) > 0) {
            $this->dbData['sql'] .= ' AND b.lang_id = :lang_id';

            $this->dbData['args']['lang_id'] = $this->lang->id;
        }

        if (\array_key_exists('active', $this->fields) && \is_bool($params['active'])) {
            $this->dbData['sql'] .= ' AND a.active = :active';

            $this->dbData['args']['active'] = $params['active'];
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.where');
        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.group');
        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.having');

        return $this->db->getRow($this->dbData['sql'], $this->dbData['args']);
    }

    public function getOneMultilang($params = [])
    {
        $params = ArrayUtils::merge(
            [
                'id' => $this->id,
            ],
            $params
        );

        return $this->db->find(($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.'_lang', ' item_id = :item_id ', ['item_id' => $params['id']]);
    }

    public function getAll($params = [])
    {
        $params = ArrayUtils::merge(
            [
                'order' => $this->internalOrder,
                'offset' => 0,
                'nRows' => PHP_INT_MAX,
                'active' => null,
            ],
            $params
        );

        $this->dbData = [];
        $this->dbData['args'] = [];

        $this->dbData['sql'] = 'SELECT';

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.preSelect');

        $this->dbData['sql'] .= ' a.*';

        if (\count($this->fieldsMultilang) > 0) {
            foreach ($this->fieldsMultilang as $key => $val) {
                $this->dbData['sql'] .= ', b.'.$key.' AS '.$key;
            }
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.select');

        $this->dbData['sql'] .= ' FROM '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.' AS a';

        if (\count($this->fieldsMultilang) > 0) {
            $this->dbData['sql'] .= ' JOIN '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.'_lang AS b';
            $this->dbData['sql'] .= ' ON a.id = b.item_id';
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.join');

        $this->dbData['sql'] .= ' WHERE 1';

        if (\count($this->fieldsMultilang) > 0) {
            $this->dbData['sql'] .= ' AND b.lang_id = :lang_id';

            $this->dbData['args']['lang_id'] = $this->lang->id;
        }

        if (\array_key_exists('active', $this->fields) && \is_bool($params['active'])) {
            $this->dbData['sql'] .= ' AND a.active = :active';

            $this->dbData['args']['active'] = $params['active'];
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.where');
        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.group');
        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.having');

        $this->dbData['sql'] .= ' ORDER BY '.$params['order'];
        $this->dbData['sql'] .= ' LIMIT '.$params['offset'].', '.$params['nRows'];

        $this->dispatcher->dispatch(new GenericEvent(arguments: [
            'params' => $params,
        ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

        return $this->db->getAll($this->dbData['sql'], $this->dbData['args']);
    }

    public function getDbFields()
    {
        return $this->db->inspect(($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName);
    }

    public function dbAdd()
    {
        try {
            $this->db->begin();

            $row = $this->db->xdispense(($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName);

            foreach ($this->fieldsMonolang as $key => $val) {
                if (isset($val[static::$env]['skip']) && \in_array($this->action, $val[static::$env]['skip'], true)) {
                    continue;
                }

                // array_key_exists() anziché isset() in modo da passare anche i valori NULL
                // isset — Determine if a variable is set and is not NULL
                // RedBeanPHP converte a 0 i valori FALSE
                if (\array_key_exists($key, $this->postData)
                    && (!isset($val[static::$env]['default']) || !\in_array($this->action, $val[static::$env]['default'], true))) {
                    $row->{$key} = $this->postData[$key];
                } elseif (\array_key_exists('dbDefault', $val)
                    && (
                        (isset($val[static::$env]['default']) && \in_array($this->action, $val[static::$env]['default'], true))
                        || (isset($val[static::$env]['defaultIfNotExists']) && \in_array($this->action, $val[static::$env]['defaultIfNotExists'], true))
                    )) {
                    $row->{$key} = $val['dbDefault'];
                }

                if (isset($val['dbCast'])) {
                    $row->setMeta('cast.'.$key, $val['dbCast']);
                }
            }

            $insertId = $this->db->store($row);

            if (\count($this->fieldsMultilang) > 0/* && !isset($this->postData['_skipMultiLang']) */) {
                foreach ($this->lang->arr as $langId => $langRow) {
                    $row = $this->db->xdispense(($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.'_lang');

                    $row->item_id = $insertId;
                    $row->lang_id = $langId;

                    foreach ($this->fieldsMultilang as $key => $val) {
                        if (isset($val[static::$env]['skip']) && \in_array($this->action, $val[static::$env]['skip'], true)) {
                            continue;
                        }

                        if (\array_key_exists('multilang|'.$langId.'|'.$key, $this->postData)
                            && (!isset($val[static::$env]['default']) || !\in_array($this->action, $val[static::$env]['default'], true))) {
                            $row->{$key} = $this->postData['multilang|'.$langId.'|'.$key];
                        } elseif (\array_key_exists('dbDefault', $val)
                            && (
                                (isset($val[static::$env]['default']) && \in_array($this->action, $val[static::$env]['default'], true))
                                || (isset($val[static::$env]['defaultIfNotExists']) && \in_array($this->action, $val[static::$env]['defaultIfNotExists'], true))
                            )) {
                            $row->{$key} = $val['dbDefault'];
                        }

                        if (isset($val['dbCast'])) {
                            $row->setMeta('cast.'.$key, $val['dbCast']);
                        }
                    }

                    $multilangInsertId = $this->db->store($row);
                }
            }

            $this->db->commit();

            return $insertId;
        } catch (\Exception $e) {
            $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                'error' => $e->getMessage(),
            ]);

            $this->errors[] = __('A technical problem has occurred, try again later.');

            $this->db->rollback();
        }

        return false;
    }

    public function dbEdit()
    {
        try {
            $this->db->begin();

            $row = $this->db->load(($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName, $this->id);

            foreach ($this->fieldsMonolang as $key => $val) {
                if (isset($val[static::$env]['skip']) && \in_array($this->action, $val[static::$env]['skip'], true)) {
                    continue;
                }

                // array_key_exists() anziché isset() in modo da passare anche i valori NULL
                // isset — Determine if a variable is set and is not NULL
                // RedBeanPHP converte a 0 i valori FALSE
                if (\array_key_exists($key, $this->postData)
                    && (!isset($val[static::$env]['default']) || !\in_array($this->action, $val[static::$env]['default'], true))) {
                    $row->{$key} = $this->postData[$key];
                } elseif (\array_key_exists('dbDefault', $val)
                    && (
                        (isset($val[static::$env]['default']) && \in_array($this->action, $val[static::$env]['default'], true))
                        || (isset($val[static::$env]['defaultIfNotExists']) && \in_array($this->action, $val[static::$env]['defaultIfNotExists'], true))
                    )) {
                    $row->{$key} = $val['dbDefault'];
                }

                if (isset($val['dbCast'])) {
                    $row->setMeta('cast.'.$key, $val['dbCast']);
                }
            }

            $this->db->store($row);

            if (\count($this->fieldsMultilang) > 0/* && !isset($this->postData['_skipMultiLang']) */) {
                $this->db->exec('DELETE FROM '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.'_lang WHERE item_id = :item_id', ['item_id' => $this->id]);

                foreach ($this->lang->arr as $langId => $langRow) {
                    $row = $this->db->xdispense(($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.'_lang');

                    $row->item_id = $this->id;
                    $row->lang_id = $langId;

                    foreach ($this->fieldsMultilang as $key => $val) {
                        if (isset($val[static::$env]['skip']) && \in_array($this->action, $val[static::$env]['skip'], true)) {
                            continue;
                        }

                        if (\array_key_exists('multilang|'.$langId.'|'.$key, $this->postData)
                            && (!isset($val[static::$env]['default']) || !\in_array($this->action, $val[static::$env]['default'], true))) {
                            $row->{$key} = $this->postData['multilang|'.$langId.'|'.$key];
                        } elseif (\array_key_exists('dbDefault', $val)
                            && (
                                (isset($val[static::$env]['default']) && \in_array($this->action, $val[static::$env]['default'], true))
                                || (isset($val[static::$env]['defaultIfNotExists']) && \in_array($this->action, $val[static::$env]['defaultIfNotExists'], true))
                            )) {
                            $row->{$key} = $val['dbDefault'];
                        } elseif (isset($this->multilang[$langId][$key]) && !isBlank($this->multilang[$langId][$key])) {
                            $row->{$key} = \is_array($this->multilang[$langId][$key]) ? $this->helper->Nette()->Json()->encode($this->multilang[$langId][$key]) : $this->multilang[$langId][$key];
                        }

                        if (isset($val['dbCast'])) {
                            $row->setMeta('cast.'.$key, $val['dbCast']);
                        }
                    }

                    $multilang_insertId = $this->db->store($row);
                }
            }

            $this->db->commit();

            return true;
        } catch (\Exception $e) {
            $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                'error' => $e->getMessage(),
            ]);

            $this->errors[] = __('A technical problem has occurred, try again later.');

            $this->db->rollback();
        }

        return false;
    }

    public function dbDelete()
    {
        try {
            $this->db->begin();

            $row = $this->db->load(($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName, $this->id);

            $this->db->trash($row);

            if (\count($this->fieldsMultilang) > 0) {
                $this->db->exec('DELETE FROM '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.'_lang WHERE item_id = :item_id', ['item_id' => $this->id]);
            }

            $this->db->commit();

            return true;
        } catch (\Exception $e) {
            $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                'error' => $e->getMessage(),
            ]);

            $this->errors[] = __('A technical problem has occurred, try again later.');

            $this->db->rollback();
        }

        return false;
    }

    // TODO
    public function dbCopy()
    {
        return false;
    }

    public function dbToggle()
    {
        try {
            $this->db->begin();

            // $row = $this->db->xdispense( ($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName );
            $row = $this->db->load(($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName, $this->postData['id']);

            $row->{$this->postData['field']} = $this->{$this->postData['field']} ? 0 : 1;

            $this->db->store($row);

            $this->db->commit();

            return $row->{$this->postData['field']};
        } catch (\Exception $e) {
            $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                'error' => $e->getMessage(),
            ]);

            $this->errors[] = __('A technical problem has occurred, try again later.');

            $this->db->rollback();
        }

        return false;
    }

    public function loadAll($params = [])
    {
        $arr = [];

        $result = $this->getAll($params);

        if (\count($result) > 0) {
            foreach ($result as $row) {
                $arr[$row['code'] ?? $row['id']] = $row;
            }
        }

        return $arr;
    }

    public function getParentIds($params = [])
    {
        if (\array_key_exists('parent_id', $this->fields)) {
            $params = ArrayUtils::merge(
                [
                    'parent_id' => $this->parent_id,
                    'parentIds' => [],
                ],
                $params
            );

            if (!empty($params['parent_id'])) {
                $this->removeAllListeners();

                $row = $this->getOne([
                    'id' => $params['parent_id'],
                ]);

                $this->addAllListeners();

                if (!empty($row['id'])) {
                    $params['parent_id'] = $row['parent_id'];
                    $params['parentIds'][] = $row['id'];

                    return $this->getParentIds($params);
                }
            }

            return $params['parentIds'];
        }

        return [];
    }

    public function getRootId($params = [])
    {
        if (\array_key_exists('parent_id', $this->fields)) {
            $params = ArrayUtils::merge(
                [
                    'parent_id' => $this->parent_id,
                ],
                $params
            );

            $parentIds = $this->getParentIds([
                'parent_id' => $params['parent_id'],
            ]);

            return end($parentIds);
        }

        return false;
    }

    public function getLevel($params = [])
    {
        if (\array_key_exists('parent_id', $this->fields)) {
            $params = ArrayUtils::merge(
                [
                    'parent_id' => $this->parent_id,
                ],
                $params
            );

            return \count($this->getParentIds([
                'parent_id' => $params['parent_id'],
            ]));
        }

        return 0;
    }

    public function getMaxLevel($params = [])
    {
        if (\array_key_exists('parent_id', $this->fields)) {
            $params = ArrayUtils::merge(
                [
                    'parent_id' => $this->id,
                    'max_level' => 0,
                ],
                $params
            );

            $this->removeAllListeners();

            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getAll.where';
            $callback = function (GenericEvent $event) use ($params): void {
                $this->dbData['sql'] .= ' AND a.parent_id = :parent_id';
                $this->dbData['args']['parent_id'] = $params['parent_id'];
            };

            $this->dispatcher->addListener($eventName, $callback);

            $result = $this->getAll($params);

            $this->dispatcher->removeListener($eventName, $callback);

            $this->addAllListeners();

            if (\count($result) > 0) {
                foreach ($result as $row) {
                    $row['parentIds'] = $this->getParentIds($row);

                    $row['level'] = \count($row['parentIds']);

                    if ($row['level'] > $params['max_level']) {
                        ++$params['max_level'];
                    }

                    $params['max_level'] = $this->getMaxLevel([
                        'parent_id' => $row['id'],
                    ] + $params);
                }
            }

            return $params['max_level'];
        }

        return 0;
    }

    public function getTree($params = [])
    {
        // http://codelegance.com/array-merging-in-php/
        // $params = ArrayUtils::merge(
        $params = array_merge(
            [
                'order' => 'a.id desc',
                'offset' => 0,
                'nRows' => PHP_INT_MAX,
                'active' => null,
                'items' => [], // <-- ArrayUtils merge arrays
                'field' => 'id',
                'parent_id' => 0,
                'removeAllListeners' => true,
            ],
            $params
        );

        if (\array_key_exists('parent_id', $this->fields)) {
            if (!empty($params['removeAllListeners'])) {
                $this->removeAllListeners();
            }

            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getAll.where';
            $callback = function (GenericEvent $event) use ($params): void {
                $this->dbData['sql'] .= ' AND a.parent_id = :parent_id';
                $this->dbData['args']['parent_id'] = $params['parent_id'];
            };

            $this->dispatcher->addListener($eventName, $callback);

            $result = $this->getAll($params);

            $this->dispatcher->removeListener($eventName, $callback);

            if (!empty($params['removeAllListeners'])) {
                $this->addAllListeners();
            }

            if (\count($result) > 0) {
                foreach ($result as $row) {
                    $row['parentIds'] = $this->getParentIds($row);

                    $row['level'] = \count($row['parentIds']);

                    $fieldPrefix = (!empty($row['level'])) ? str_repeat('-', $row['level']).' ' : '';

                    $row['field'] = $fieldPrefix.$row[$params['field']];

                    $params['items'][] = $row;

                    $params['items'] = $this->getTree([
                        'parent_id' => $row['id'],
                    ] + $params);
                }
            }
        }

        return $params['items'];
    }
}
