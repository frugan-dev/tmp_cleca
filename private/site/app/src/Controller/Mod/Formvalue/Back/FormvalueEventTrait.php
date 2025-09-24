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

namespace App\Controller\Mod\Formvalue\Back;

use Symfony\Component\EventDispatcher\GenericEvent;

trait FormvalueEventTrait
{
    public function eventGetOneSelect(GenericEvent $event): void
    {
        parent::eventGetOneSelect($event);

        $this->dbData['sql'] .= ', c.name AS catmember_name';

        if (method_exists($this, '_eventSelect') && \is_callable([$this, '_eventSelect'])) {
            $this->_eventSelect($event);
        }
    }

    public function eventGetOneJoin(GenericEvent $event): void
    {
        parent::eventGetOneJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'catmember AS c
        ON a.catmember_id = c.id';

        if (method_exists($this, '_eventJoin') && \is_callable([$this, '_eventJoin'])) {
            $this->_eventJoin($event);
        }
    }

    public function eventGetAllSelect(GenericEvent $event): void
    {
        parent::eventGetAllSelect($event);

        $this->dbData['sql'] .= ', a.formfield_id';

        if (method_exists($this, '_eventSelect') && \is_callable([$this, '_eventSelect'])) {
            $this->_eventSelect($event);
        }
    }

    public function eventGetAllJoin(GenericEvent $event): void
    {
        parent::eventGetAllJoin($event);

        if (method_exists($this, '_eventJoin') && \is_callable([$this, '_eventJoin'])) {
            $this->_eventJoin($event);
        }
    }

    public function eventGetAllWhere(GenericEvent $event): void
    {
        parent::eventGetAllWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            $this->_eventWhere($event);
        }
    }

    protected function _eventWhere(GenericEvent $event): void
    {
        if (($paramId = array_search('member_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $this->dbData['sql'] .= ' AND a.member_id = :member_id';
                $this->dbData['args']['member_id'] = (int) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (($paramId = array_search('catform_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $this->dbData['sql'] .= ' AND a.catform_id = :catform_id';
                $this->dbData['args']['catform_id'] = (int) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (($paramId = array_search('form_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $this->dbData['sql'] .= ' AND a.form_id = :form_id';
                $this->dbData['args']['form_id'] = (int) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (($paramId = array_search('formfield_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $this->dbData['sql'] .= ' AND a.formfield_id = :formfield_id';
                $this->dbData['args']['formfield_id'] = (int) $this->routeParamsArr[$paramId + 1];
            }
        }
    }
}
