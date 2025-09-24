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

namespace App\Controller\Mod\Log;

use Monolog\Level;
use Symfony\Component\EventDispatcher\GenericEvent;

trait LogEventTrait
{
    public function eventExistWhere(GenericEvent $event): void
    {
        parent::eventExistWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            \call_user_func_array([$this, '_eventWhere'], [$event]);
        }
    }

    public function eventGetCountWhere(GenericEvent $event): void
    {
        parent::eventGetCountWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            \call_user_func_array([$this, '_eventWhere'], [$event]);
        }
    }

    public function eventGetOneWhere(GenericEvent $event): void
    {
        parent::eventGetOneWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            \call_user_func_array([$this, '_eventWhere'], [$event]);
        }
    }

    public function eventGetAllWhere(GenericEvent $event): void
    {
        parent::eventGetAllWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            \call_user_func_array([$this, '_eventWhere'], [$event]);
        }
    }

    public function _eventWhere(GenericEvent $event): void
    {
        if ($this->auth->hasIdentity()) {
            if (!$this->rbac->isGranted($this->auth->getIdentity()['_role_type'].'.'.static::$env.'.add')) {
                // no 'a' prefix
                $this->dbData['sql'] .= ' AND level <= :level';
                $this->dbData['sql'] .= ' AND auth_type = :auth_type';
                $this->dbData['sql'] .= ' AND auth_id = :auth_id';
                $this->dbData['args']['level'] = Level::Notice->value;
                $this->dbData['args']['auth_type'] = $this->auth->getIdentity()['_role_type'];
                $this->dbData['args']['auth_id'] = $this->auth->getIdentity()['id'];
            }
        }
    }
}
