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

namespace App\Controller\Mod\Form\Back;

use Symfony\Component\EventDispatcher\GenericEvent;

trait FormEventTrait
{
    public function eventGetAllSelect(GenericEvent $event): void
    {
        parent::eventGetAllSelect($event);

        $this->dbData['sql'] .= ', c.code AS cat'.$this->modName.'_code';
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

    protected function _eventWhere(GenericEvent $event): void
    {
        if (($paramId = array_search('cat'.$this->modName.'_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $this->dbData['sql'] .= ' AND a.cat'.$this->modName.'_id = :cat'.$this->modName.'_id';
                $this->dbData['args']['cat'.$this->modName.'_id'] = (int) $this->routeParamsArr[$paramId + 1];
            }
        }
    }
}
