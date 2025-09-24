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

namespace App\Controller\Mod\Member\Back;

use Symfony\Component\EventDispatcher\GenericEvent;

trait MemberEventTrait
{
    public function eventGetCountSelect(GenericEvent $event): void
    {
        parent::eventGetCountSelect($event);

        $this->dbData['sql'] .= ', GROUP_CONCAT(DISTINCT g.form_id) AS form_ids';
    }

    public function eventGetCountJoin(GenericEvent $event): void
    {
        parent::eventGetCountJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'formvalue AS g
        ON a.id = g.'.$this->modName.'_id';
    }

    public function eventGetCountGroup(GenericEvent $event): void
    {
        parent::eventGetCountGroup($event);

        $this->dbData['sql'] .= ' GROUP BY a.id';
    }

    public function eventGetAllSelect(GenericEvent $event): void
    {
        parent::eventGetAllSelect($event);

        $this->dbData['sql'] .= ', c.name AS cat'.$this->modName.'_name';
        $this->dbData['sql'] .= ', c.main AS cat'.$this->modName.'_main';
        $this->dbData['sql'] .= ', f.name AS country_name';
        $this->dbData['sql'] .= ', g.catform_id';
        $this->dbData['sql'] .= ', GROUP_CONCAT(DISTINCT g.form_id) AS form_ids';
    }

    public function eventGetAllJoin(GenericEvent $event): void
    {
        parent::eventGetAllJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'cat'.$this->modName.' AS c
        ON a.cat'.$this->modName.'_id = c.id';
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'country_lang AS f
        ON a.country_id = f.item_id';
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'formvalue AS g
        ON a.id = g.'.$this->modName.'_id';
    }

    public function eventGetAllWhere(GenericEvent $event): void
    {
        parent::eventGetAllWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            $this->_eventWhere($event);
        }
    }

    public function eventGetAllGroup(GenericEvent $event): void
    {
        parent::eventGetAllGroup($event);

        $this->dbData['sql'] .= ' GROUP BY a.id';
    }

    public function eventGetAllHaving(GenericEvent $event): void
    {
        parent::eventGetAllHaving($event);

        if (method_exists($this, '_eventHaving') && \is_callable([$this, '_eventHaving'])) {
            $this->_eventHaving($event);
        }
    }

    public function _eventActionEditAfter(GenericEvent $event): void
    {
        if ($this->email !== $this->postData['email']) {
            if (!empty($this->{'cat'.$this->modName.'_main'})) {
                $ModFormvalue = $this->container->get('Mod\Formvalue\\'.ucfirst(static::$env));

                $eventName = 'event.'.static::$env.'.'.$ModFormvalue->modName.'.getAll.select';
                $callback = function (GenericEvent $event) use ($ModFormvalue): void {
                    $ModFormvalue->dbData['sql'] .= ', a.data';
                };

                // https://stackoverflow.com/a/70088964/3929620
                $eventName2 = 'event.'.static::$env.'.'.$ModFormvalue->modName.'.getAll.where';
                $callback2 = function (GenericEvent $event) use ($ModFormvalue): void {
                    $ModFormvalue->dbData['sql'] .= ' AND CASE WHEN JSON_VALID(a.data)
                THEN JSON_CONTAINS(JSON_EXTRACT(a.data, "$.teachers.*.id"), :teacher_id, "$")
                ELSE 0
            END';
                    $ModFormvalue->dbData['args']['teacher_id'] = $this->id;
                };

                $this->dispatcher->addListener($eventName, $callback);
                $this->dispatcher->addListener($eventName2, $callback2);

                $result = $ModFormvalue->getAll();

                $this->dispatcher->removeListener($eventName, $callback);
                $this->dispatcher->removeListener($eventName2, $callback2);

                if (\count($result) > 0) {
                    foreach ($result as $row) {
                        if (!empty($data = $this->helper->Nette()->Json()->decode((string) $row['data'], forceArrays: true))) {
                            if (($key = $this->helper->Arrays()->recursiveArraySearch('id', $this->id, $data['teachers'], true)) !== false) {
                                $data['teachers'][$key]['email'] = $this->postData['email'];

                                $this->db->exec('UPDATE '.$this->config['db.1.prefix'].$ModFormvalue->modName.' SET data = :data WHERE id = :id', [
                                    'data' => $this->helper->Nette()->Json()->encode($data),
                                    'id' => $row['id'],
                                ]);
                            }
                        }
                    }
                }
            }
        }
    }

    protected function _eventWhere(GenericEvent $event): void
    {
        if (($paramId = array_search('cat'.$this->modName.'_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $this->dbData['sql'] .= ' AND a.cat'.$this->modName.'_id = :cat'.$this->modName.'_id';
                $this->dbData['args']['cat'.$this->modName.'_id'] = (int) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (($paramId = array_search('country_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $this->dbData['sql'] .= ' AND a.country_id = :country_id';
                $this->dbData['args']['country_id'] = (int) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (($paramId = array_search('catform_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $this->dbData['sql'] .= ' AND g.catform_id = :catform_id';
                $this->dbData['args']['catform_id'] = (int) $this->routeParamsArr[$paramId + 1];
            }
        }
    }

    protected function _eventHaving(GenericEvent $event): void
    {
        if (($paramId = array_search('catform_id', $this->routeParamsArr, true)) !== false
            && ($subParamId = array_search('status_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1], $this->routeParamsArr[$subParamId + 1])
            ) {
                $ModForm = $this->container->get('Mod\Form\Back');

                $eventName = 'event.'.static::$env.'.'.$ModForm->modName.'.getAll.where';
                $callback = function (GenericEvent $event) use ($ModForm, $paramId): void {
                    $ModForm->dbData['sql'] .= ' AND a.cat'.$ModForm->modName.'_id = :cat'.$ModForm->modName.'_id';
                    $ModForm->dbData['args']['cat'.$ModForm->modName.'_id'] = $this->routeParamsArr[$paramId + 1];
                };

                $this->dispatcher->addListener($eventName, $callback);

                // https://stackoverflow.com/a/7604926/3929620
                $result = $ModForm->getAll([
                    'order' => 'a.hierarchy DESC',
                    'nRows' => 1,
                    'active' => true,
                ]);

                $this->dispatcher->removeListener($eventName, $callback);

                if (\count($result) > 0) {
                    $this->container->set('cat'.$ModForm->modName.'Id'.$this->routeParamsArr[$paramId + 1].'LastFormId', $result[0]['id']);
                }

                if ($this->container->has('cat'.$ModForm->modName.'Id'.$this->routeParamsArr[$paramId + 1].'LastFormId')) {
                    $this->dbData['sql'] .= \Safe\preg_match('/\sHAVING\s/', (string) $this->dbData['sql']) ? ' AND' : ' HAVING';

                    // https://stackoverflow.com/a/54688059/3929620
                    // https://stackoverflow.com/a/54690032/3929620
                    // https://stackoverflow.com/a/37849547/3929620
                    switch ($this->routeParamsArr[$subParamId + 1]) {
                        case 1:
                            $this->dbData['sql'] .= ' FIND_IN_SET(:'.$ModForm->modName.'_id, '.$ModForm->modName.'_ids)';
                            $this->dbData['args'][$ModForm->modName.'_id'] = (int) $this->container->get('cat'.$ModForm->modName.'Id'.$this->routeParamsArr[$paramId + 1].'LastFormId');

                            break;

                        case 2:
                            $this->dbData['sql'] .= ' NOT FIND_IN_SET(:'.$ModForm->modName.'_id, '.$ModForm->modName.'_ids) AND '.$ModForm->modName.'_ids IS NOT NULL';
                            $this->dbData['args'][$ModForm->modName.'_id'] = (int) $this->container->get('cat'.$ModForm->modName.'Id'.$this->routeParamsArr[$paramId + 1].'LastFormId');

                            break;
                    }
                }
            }
        }
    }
}
