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

namespace App\Controller\Mod\Page\Front;

use Symfony\Component\EventDispatcher\GenericEvent;

trait PageEventTrait
{
    public function eventExistStrictSelect(GenericEvent $event): void
    {
        parent::eventExistStrictSelect($event);

        $this->dbData['sql'] .= ', GROUP_CONCAT(DISTINCT e.catform_id) AS catform_ids';
    }

    public function eventExistStrictJoin(GenericEvent $event): void
    {
        parent::eventExistStrictJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].$this->modName.'2catform AS e
        ON a.id = e.item_id';
    }

    public function eventExistStrictGroup(GenericEvent $event): void
    {
        parent::eventExistStrictGroup($event);

        $this->dbData['sql'] .= ' GROUP BY a.id';
    }

    public function eventExistStrictHaving(GenericEvent $event): void
    {
        parent::eventExistStrictHaving($event);

        if (!empty($this->view->getData()->catformRow)) { // <--
            $this->dbData['sql'] .= \Safe\preg_match('/\sHAVING\s/', (string) $this->dbData['sql']) ? ' AND' : ' HAVING';

            // https://stackoverflow.com/a/54688059/3929620
            // https://stackoverflow.com/a/54690032/3929620
            // https://stackoverflow.com/a/37849547/3929620
            $this->dbData['sql'] .= ' (FIND_IN_SET(:catform_id, catform_ids) OR catform_ids IS NULL)';
            $this->dbData['args']['catform_id'] = (int) $this->view->getData()->catformRow['id'];
        }
    }

    public function eventGetOneSelect(GenericEvent $event): void
    {
        parent::eventGetOneSelect($event);

        if (!empty($this->config['mod.'.$this->modName.'.tree.maxLevel'] ?? $this->config['mod.tree.maxLevel'] ?? 0)) {
            $this->dbData['sql'] .= ', d.name AS parent_name';
        }
    }

    public function eventGetOneJoin(GenericEvent $event): void
    {
        parent::eventGetOneJoin($event);

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
    }

    protected function _eventSelect(GenericEvent $event): void
    {
        if (!empty($this->config['mod.'.$this->modName.'.tree.maxLevel'] ?? $this->config['mod.tree.maxLevel'] ?? 0)) {
            $this->dbData['sql'] .= ', a.parent_id';
        }
    }

    protected function _eventHaving(GenericEvent $event): void
    {
        $this->dbData['sql'] .= \Safe\preg_match('/\sHAVING\s/', (string) $this->dbData['sql']) ? ' AND' : ' HAVING';

        $this->dbData['sql'] .= ' menu_ids IS NOT NULL';
    }
}
