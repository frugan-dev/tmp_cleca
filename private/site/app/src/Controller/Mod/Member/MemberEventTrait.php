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

namespace App\Controller\Mod\Member;

use RedBeanPHP\Facade as R;
use Symfony\Component\EventDispatcher\GenericEvent;

trait MemberEventTrait
{
    public function eventGetCountWhere(GenericEvent $event): void
    {
        parent::eventGetCountWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            $this->_eventWhere($event);
        }
    }

    public function eventGetCountHaving(GenericEvent $event): void
    {
        parent::eventGetCountHaving($event);

        if (method_exists($this, '_eventHaving') && \is_callable([$this, '_eventHaving'])) {
            $this->_eventHaving($event);
        }
    }

    public function eventGetOneSelect(GenericEvent $event): void
    {
        parent::eventGetOneSelect($event);

        $this->dbData['sql'] .= ', c.name AS cat'.$this->modName.'_name';
        $this->dbData['sql'] .= ', c.perms AS cat'.$this->modName.'_perms';
        $this->dbData['sql'] .= ', c.main AS cat'.$this->modName.'_main';
        $this->dbData['sql'] .= ', f.name AS country_name';
    }

    public function eventGetOneJoin(GenericEvent $event): void
    {
        parent::eventGetOneJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'cat'.$this->modName.' AS c
        ON a.cat'.$this->modName.'_id = c.id';
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'country_lang AS f
        ON a.country_id = f.item_id';
    }

    /*public function eventActionAddAfter(GenericEvent $event): void
    {
        parent::eventActionAddAfter($event);

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            call_user_func_array([$this, '_'.__FUNCTION__], [$event]);
        }

        if (!empty($this->cache->taggable)) {
            $tags = [
                'global-1',
            ];

            $this->cache->invalidateTags($tags);
        } else {
            $this->cache->clear();
        }
    }*/

    public function eventActionEditAfter(GenericEvent $event): void
    {
        parent::eventActionEditAfter($event);

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            \call_user_func_array([$this, '_'.__FUNCTION__], [$event]);
        }

        if (!empty($this->cache->taggable)) {
            $tags = [
                'local-'.$this->modName.'-'.$this->id,
                'global-1',
            ];

            $this->cache->invalidateTags($tags);
        } else {
            $this->cache->clear();
        }
    }

    public function eventActionDeleteAfter(GenericEvent $event): void
    {
        parent::eventActionDeleteAfter($event);

        R::exec(
            'DELETE FROM '.$this->config['db.1.prefix'].'log WHERE auth_type = :auth_type AND auth_id = :auth_id',
            [
                'auth_type' => 'cat'.$this->modName,
                'auth_id' => $this->id,
            ]
        );

        R::exec(
            'DELETE FROM '.$this->config['db.1.prefix'].'formvalue WHERE '.$this->modName.'_id = :'.$this->modName.'_id',
            [
                $this->modName.'_id' => $this->id,
            ]
        );

        if (empty($this->{'cat'.$this->modName.'_main'})) {
            $dest = _ROOT.'/var/upload/catform-*/formfield-*/'.$this->modName.'-'.$this->id;

            if (is_dir($dest)) {
                foreach ($this->helper->Nette()->Finder()->findDirectories($dest) as $dirObj) {
                    $this->helper->Nette()->FileSystem()->delete($dirObj->getRealPath());
                }
            }
        }

        if (0 === \count($this->errors)) {
            if (empty($this->container->get('Mod\Member\\'.ucfirst(static::$env))->getCount())) {
                // https://stackoverflow.com/a/10727590
                R::wipe($this->config['db.1.prefix'].'member');
            }
        }

        if (!empty($this->cache->taggable)) {
            $tags = [
                'local-'.$this->modName.'-'.$this->id,
                'global-1',
            ];

            $this->cache->invalidateTags($tags);
        } else {
            $this->cache->clear();
        }
    }
}
