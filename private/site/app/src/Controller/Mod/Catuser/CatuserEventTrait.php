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

namespace App\Controller\Mod\Catuser;

use Symfony\Component\EventDispatcher\GenericEvent;

trait CatuserEventTrait
{
    public function eventExistWhere(GenericEvent $event): void
    {
        parent::eventExistWhere($event);

        $this->_eventWhere($event);
    }

    public function eventExistStrictWhere(GenericEvent $event): void
    {
        parent::eventExistStrictWhere($event);

        $this->_eventWhere($event);
    }

    public function eventGetCountWhere(GenericEvent $event): void
    {
        parent::eventGetCountWhere($event);

        $this->_eventWhere($event);
    }

    public function eventGetOneWhere(GenericEvent $event): void
    {
        parent::eventGetOneWhere($event);

        $this->_eventWhere($event);
    }

    public function eventGetAllWhere(GenericEvent $event): void
    {
        parent::eventGetAllWhere($event);

        $this->_eventWhere($event);
    }

    public function _eventWhere(GenericEvent $event): void
    {
        if (!$this->rbac->isGranted($this->modName.'.'.static::$env.'.add')) {
            // senza prefisso 'a' per funzionare anche con exist()
            $this->dbData['sql'] .= ' AND main != :main';
            $this->dbData['args']['main'] = 1;

            // FIXME
            $this->dbData['sql'] .= ' AND id >= :id_1';
            $this->dbData['args']['id_1'] = (int) $this->auth->getIdentity()[$this->modName.'_id'];
        }
    }
}
