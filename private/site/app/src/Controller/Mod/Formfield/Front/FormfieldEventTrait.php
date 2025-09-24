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

namespace App\Controller\Mod\Formfield\Front;

use Symfony\Component\EventDispatcher\GenericEvent;

trait FormfieldEventTrait
{
    public function eventGetAllSelect(GenericEvent $event): void
    {
        parent::eventGetAllSelect($event);

        if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
            $this->dbData['sql'] .= ', g.id AS formvalue_id';
            $this->dbData['sql'] .= ', g.data AS formvalue_data';
        }
    }

    public function eventGetAllJoin(GenericEvent $event): void
    {
        parent::eventGetAllJoin($event);

        if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
            $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'formvalue AS g
            ON a.id = g.'.$this->modName.'_id
            AND g.member_id = :member_id';

            $this->dbData['args']['member_id'] = $this->auth->getIdentity()['id'];
        }
    }
}
