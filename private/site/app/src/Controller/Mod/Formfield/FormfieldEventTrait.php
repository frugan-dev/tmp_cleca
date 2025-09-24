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

namespace App\Controller\Mod\Formfield;

use Symfony\Component\EventDispatcher\GenericEvent;

trait FormfieldEventTrait
{
    public function eventGetCountWhere(GenericEvent $event): void
    {
        parent::eventGetCountWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            $this->_eventWhere($event);
        }
    }

    public function eventGetOneSelect(GenericEvent $event): void
    {
        parent::eventGetOneSelect($event);

        if (method_exists($this, '_eventSelect') && \is_callable([$this, '_eventSelect'])) {
            $this->_eventSelect($event);
        }
    }

    public function eventGetOneJoin(GenericEvent $event): void
    {
        parent::eventGetOneJoin($event);

        if (method_exists($this, '_eventJoin') && \is_callable([$this, '_eventJoin'])) {
            $this->_eventJoin($event);
        }
    }

    public function eventActionAddAfter(GenericEvent $event): void
    {
        parent::eventActionAddAfter($event, $insertId);

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            \call_user_func_array([$this, '_'.__FUNCTION__], [$event]);
        }

        if (!empty($this->cache->taggable)) {
            $tags = [
                'global-1',
            ];

            $this->cache->invalidateTags($tags);
        } else {
            $this->cache->clear();
        }
    }

    public function eventActionEditAfter(GenericEvent $event): void
    {
        parent::eventActionEditAfter($event);

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            \call_user_func_array([$this, '_'.__FUNCTION__], [$event]);
        }

        if (!empty($this->cache->taggable)) {
            $tags = [
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

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            \call_user_func_array([$this, '_'.__FUNCTION__], [$event]);
        }

        if (!empty($this->cache->taggable)) {
            $tags = [
                'global-1',
            ];

            $this->cache->invalidateTags($tags);
        } else {
            $this->cache->clear();
        }
    }

    protected function _eventSelect(GenericEvent $event): void
    {
        $this->dbData['sql'] .= ', c.code AS catform_code';
        $this->dbData['sql'] .= ', f.name AS form_name';
    }

    protected function _eventJoin(GenericEvent $event): void
    {
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'catform AS c
        ON a.catform_id = c.id';
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'form_lang AS f
        ON a.form_id = f.item_id';

        // https://stackoverflow.com/a/20123337/3929620
        // https://stackoverflow.com/a/22499259/3929620
        // https://stackoverflow.com/a/40682033/3929620
        foreach (['f'] as $letter) {
            if (empty($this->db->getPDO()->getAttribute(\PDO::ATTR_EMULATE_PREPARES))) {
                $this->dbData['sql'] .= ' AND '.$letter.'.lang_id = :'.$letter.'_lang_id';
                $this->dbData['args'][$letter.'_lang_id'] = $this->lang->id;
            } else {
                $this->dbData['sql'] .= ' AND '.$letter.'.lang_id = :lang_id';
            }
        }

        if (!empty($this->db->getPDO()->getAttribute(\PDO::ATTR_EMULATE_PREPARES))) {
            $this->dbData['args']['lang_id'] = $this->lang->id;
        }
    }
}
