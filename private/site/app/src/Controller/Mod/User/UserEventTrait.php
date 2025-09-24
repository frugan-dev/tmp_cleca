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

namespace App\Controller\Mod\User;

use Symfony\Component\EventDispatcher\GenericEvent;

trait UserEventTrait
{
    public function eventGetOneSelect(GenericEvent $event): void
    {
        parent::eventGetOneSelect($event);

        $this->dbData['sql'] .= ', c.name AS cat'.$this->modName.'_name';
        $this->dbData['sql'] .= ', c.perms AS cat'.$this->modName.'_perms';
        $this->dbData['sql'] .= ', c.api_rl_hour AS cat'.$this->modName.'_api_rl_hour';
        $this->dbData['sql'] .= ', c.api_rl_day AS cat'.$this->modName.'_api_rl_day';
        $this->dbData['sql'] .= ', c.api_log_level AS cat'.$this->modName.'_api_log_level';
        $this->dbData['sql'] .= ', c.main AS cat'.$this->modName.'_main';
    }

    public function eventGetOneJoin(GenericEvent $event): void
    {
        parent::eventGetOneJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'cat'.$this->modName.' AS c
        ON a.cat'.$this->modName.'_id = c.id';
    }

    public function eventActionEditAfter(GenericEvent $event): void
    {
        parent::eventActionEditAfter($event);

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            \call_user_func_array([$this, '_'.__FUNCTION__], [$event]);
        }
    }

    public function eventActionDeleteAfter(GenericEvent $event): void
    {
        parent::eventActionDeleteAfter($event);

        $this->db->exec(
            'DELETE FROM '.$this->config['db.1.prefix'].'log WHERE auth_type = :auth_type AND auth_id = :auth_id',
            [
                'auth_type' => 'cat'.$this->modName,
                'auth_id' => $this->id,
            ]
        );
    }
}
