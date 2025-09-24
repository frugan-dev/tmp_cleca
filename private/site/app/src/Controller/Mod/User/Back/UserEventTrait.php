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

namespace App\Controller\Mod\User\Back;

use Symfony\Component\EventDispatcher\GenericEvent;

trait UserEventTrait
{
    public function eventExistStrictWhere(GenericEvent $event): void
    {
        parent::eventExistStrictWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            $this->_eventWhere($event);
        }
    }

    public function eventGetCountWhere(GenericEvent $event): void
    {
        parent::eventGetCountWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            $this->_eventWhere($event);
        }
    }

    public function eventGetOneWhere(GenericEvent $event): void
    {
        parent::eventGetOneWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            $this->_eventWhere($event);
        }
    }

    public function eventGetAllSelect(GenericEvent $event): void
    {
        parent::eventGetAllSelect($event);

        $this->dbData['sql'] .= ', c.name AS cat'.$this->modName.'_name';
    }

    public function eventGetAllJoin(GenericEvent $event): void
    {
        parent::eventGetAllJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'cat'.$this->modName.' AS c
        ON a.cat'.$this->modName.'_id = c.id';
    }

    public function eventGetAllWhere(GenericEvent $event): void
    {
        parent::eventGetAllWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            $this->_eventWhere($event);
        }
    }

    public function _eventActionEditAfter(GenericEvent $event): void
    {
        if ($this->auth->getIdentity()['id'] === $this->id) {
            $this->auth->forceAuthenticate($this->postData[$this->authUsernameField]);
        }
    }

    public function _eventWhere(GenericEvent $event): void
    {
        if ($this->auth->hasIdentity() && \in_array($this->auth->getIdentity()['_type'], [$this->modName], true)) {
            if (!$this->rbac->isGranted('cat'.$this->modName.'.'.static::$env.'.add')) {
                $routeName = $this->routeParsingService->getRouteName();

                if (str_contains((string) $routeName, '.')) {
                    [, $controller] = explode('.', (string) $routeName);
                    if ($controller === $this->modName && ($action = $this->routeParsingService->getAction()) !== null) {
                        if ('switch' !== $action) {
                            // FIXME
                            $this->dbData['sql'] .= ' AND a.cat'.$this->modName.'_id >= :cat'.$this->modName.'_id_1';
                            $this->dbData['args']['cat'.$this->modName.'_id_1'] = (int) $this->auth->getIdentity()['cat'.$this->modName.'_id'];
                        }
                    }
                }
            }

            if (($paramId = array_search('cat'.$this->modName.'_id', $this->routeParamsArr, true)) !== false) {
                if (isset($this->routeParamsArr[$paramId + 1])) {
                    $this->dbData['sql'] .= ' AND a.cat'.$this->modName.'_id = :cat'.$this->modName.'_id';
                    $this->dbData['args']['cat'.$this->modName.'_id'] = (int) $this->routeParamsArr[$paramId + 1];
                }
            }
        }
    }
}
