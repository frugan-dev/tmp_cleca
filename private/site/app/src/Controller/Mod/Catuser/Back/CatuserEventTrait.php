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

namespace App\Controller\Mod\Catuser\Back;

use Symfony\Component\EventDispatcher\GenericEvent;

trait CatuserEventTrait
{
    public function eventActionEditAfter(GenericEvent $event): void
    {
        parent::eventActionEditAfter($event);

        if ($this->auth->getIdentity()[$this->modName.'_id'] === $this->id) {
            $this->auth->forceAuthenticate($this->auth->getIdentity()[$this->container->get('Mod\User\\'.ucfirst(static::$env))->authUsernameField]);
        }
    }
}
