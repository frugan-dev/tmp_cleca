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

namespace App\Controller\Mod\Page\Back;

use Symfony\Component\EventDispatcher\GenericEvent;

trait PageEventTrait
{
    public function eventGetCountSelect(GenericEvent $event): void
    {
        parent::eventGetCountSelect($event);

        $this->dbData['sql'] .= ', GROUP_CONCAT(DISTINCT e.catform_id) AS catform_ids';
    }

    public function eventGetCountJoin(GenericEvent $event): void
    {
        parent::eventGetCountJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].$this->modName.'2catform AS e
        ON a.id = e.item_id';
    }

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

        $this->dbData['sql'] .= ', GROUP_CONCAT(DISTINCT e.catform_id) AS catform_ids';
        $this->dbData['sql'] .= ', GROUP_CONCAT(DISTINCT f.menu_id) AS menu_ids';

        if (method_exists($this, '_eventSelect') && \is_callable([$this, '_eventSelect'])) {
            $this->_eventSelect($event);
        }
    }

    public function eventGetOneJoin(GenericEvent $event): void
    {
        parent::eventGetOneJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].$this->modName.'2catform AS e
        ON a.id = e.item_id';
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].$this->modName.'2menu AS f
        ON a.id = f.item_id';

        if (method_exists($this, '_eventJoin') && \is_callable([$this, '_eventJoin'])) {
            $this->_eventJoin($event);
        }
    }

    protected function _eventSelect(GenericEvent $event): void
    {
        if (!empty($this->config['mod.'.$this->modName.'.tree.maxLevel'] ?? $this->config['mod.tree.maxLevel'] ?? 0)) {
            $this->dbData['sql'] .= ', d.name AS parent_name';
        }

        $this->dbData['sql'] .= ', GROUP_CONCAT(DISTINCT g.code ORDER BY g.code ASC SEPARATOR ", ") AS catform_codes';
    }

    protected function _eventJoin(GenericEvent $event): void
    {
        if (!empty($this->config['mod.'.$this->modName.'.tree.maxLevel'] ?? $this->config['mod.tree.maxLevel'] ?? 0)) {
            $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].$this->modName.'_lang AS d
            ON a.parent_id = d.item_id';

            // https://stackoverflow.com/a/20123337/3929620
            // https://stackoverflow.com/a/22499259/3929620
            // https://stackoverflow.com/a/40682033/3929620
            foreach (['d'] as $letter) {
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

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'catform AS g
        ON e.catform_id = g.id';
    }

    protected function _eventWhere(GenericEvent $event): void
    {
        if (!empty($this->config['mod.'.$this->modName.'.tree.maxLevel'] ?? $this->config['mod.tree.maxLevel'] ?? 0)) {
            if (\in_array($this->action, ['index'], true)) {
                $parentId = 0;

                if (($paramId = array_search('parent_id', $this->routeParamsArr, true)) !== false) {
                    if (isset($this->routeParamsArr[$paramId + 1])) {
                        $parentId = (int) $this->routeParamsArr[$paramId + 1];
                    }
                }

                $this->dbData['sql'] .= ' AND a.parent_id = :parent_id';
                $this->dbData['args']['parent_id'] = $parentId;
            }
        }
    }

    protected function _eventHaving(GenericEvent $event): void
    {
        if (($paramId = array_search('catform_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $this->dbData['sql'] .= \Safe\preg_match('/\sHAVING\s/', (string) $this->dbData['sql']) ? ' AND' : ' HAVING';

                // https://stackoverflow.com/a/54688059/3929620
                // https://stackoverflow.com/a/54690032/3929620
                // https://stackoverflow.com/a/37849547/3929620
                $this->dbData['sql'] .= ' FIND_IN_SET(:catform_id, catform_ids)';
                $this->dbData['args']['catform_id'] = (int) $this->routeParamsArr[$paramId + 1];
            }
        }
    }
}
