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

namespace App\Controller\Mod\Form\Front;

use Symfony\Component\EventDispatcher\GenericEvent;

trait FormEventTrait
{
    public function eventExistStrictWhere(GenericEvent $event): void
    {
        parent::eventExistStrictWhere($event);

        if (!empty($this->view->getData()->{'cat'.$this->modName.'Row'})) { // <--
            $this->dbData['sql'] .= ' AND cat'.$this->modName.'_id = :cat'.$this->modName.'_id';

            $ModCat = $this->container->get('Mod\Cat'.$this->modName.'\\'.ucfirst(static::$env));
            if (\in_array($this->action, ['print'], true) || \in_array($this->view->getData()->{'cat'.$this->modName.'Row'}['status'], [$ModCat::MAINTENANCE, $ModCat::OPEN, $ModCat::CLOSING], true)) {
                $this->dbData['args']['cat'.$this->modName.'_id'] = (int) $this->view->getData()->{'cat'.$this->modName.'Row'}['id'];
            } else {
                $this->dbData['args']['cat'.$this->modName.'_id'] = -1;
            }
        }
    }
}
