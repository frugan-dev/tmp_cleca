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

namespace App\Controller\Mod\Page;

use Symfony\Component\EventDispatcher\GenericEvent;

trait PageEventTrait
{
    public function eventGetAllSelect(GenericEvent $event): void
    {
        parent::eventGetAllSelect($event);

        $this->dbData['sql'] .= ', GROUP_CONCAT(DISTINCT e.catform_id) AS catform_ids';
        $this->dbData['sql'] .= ', GROUP_CONCAT(DISTINCT f.menu_id) AS menu_ids';

        if (method_exists($this, '_eventSelect') && \is_callable([$this, '_eventSelect'])) {
            $this->_eventSelect($event);
        }
    }

    public function eventGetAllJoin(GenericEvent $event): void
    {
        parent::eventGetAllJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].$this->modName.'2catform AS e
        ON a.id = e.item_id';
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].$this->modName.'2menu AS f
        ON a.id = f.item_id';

        if (method_exists($this, '_eventJoin') && \is_callable([$this, '_eventJoin'])) {
            $this->_eventJoin($event);
        }
    }

    public function eventGetAllWhere(GenericEvent $event): void
    {
        parent::eventGetAllWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            $this->_eventWhere($event);
        }
    }

    public function eventGetAllGroup(GenericEvent $event): void
    {
        parent::eventGetAllGroup($event);

        $this->dbData['sql'] .= ' GROUP BY a.id';
    }

    public function eventGetAllHaving(GenericEvent $event): void
    {
        parent::eventGetAllHaving($event);

        if (method_exists($this, '_eventHaving') && \is_callable([$this, '_eventHaving'])) {
            $this->_eventHaving($event);
        }
    }

    public function eventActionAddAfter(GenericEvent $event): void
    {
        parent::eventActionAddAfter($event);

        $this->_handleCatformIds($event);
        $this->_handleMenuIds($event);

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

        $this->_handleCatformIds($event);
        $this->_handleMenuIds($event);

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

        if (\in_array($this->modName.'2catform', $this->additionalTables, true)) {
            $table = $this->modName.'2catform';

            try {
                $this->db->begin();

                $this->db->exec('DELETE FROM '.$this->config['db.1.prefix'].$table.' WHERE item_id = :item_id', ['item_id' => $this->id]);
                $this->db->commit();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'text' => $e->getTraceAsString(),
                ]);

                $this->errors[] = __('A technical problem has occurred, try again later.');

                $this->db->rollback();
            }
        }

        if (\in_array($this->modName.'2menu', $this->additionalTables, true)) {
            $table = $this->modName.'2menu';

            try {
                $this->db->begin();

                $this->db->exec('DELETE FROM '.$this->config['db.1.prefix'].$table.' WHERE item_id = :item_id', ['item_id' => $this->id]);
                $this->db->commit();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'text' => $e->getTraceAsString(),
                ]);

                $this->errors[] = __('A technical problem has occurred, try again later.');

                $this->db->rollback();
            }
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

    public function _handleCatformIds(GenericEvent $event): void
    {
        if (\in_array($this->modName.'2catform', $this->additionalTables, true)) {
            $table = $this->modName.'2catform';

            try {
                $this->db->begin();

                if (!\in_array($this->action, ['add'], true)) {
                    $this->db->exec('DELETE FROM '.$this->config['db.1.prefix'].$table.' WHERE item_id = :item_id', ['item_id' => $event['id'] ?? $this->id]);
                }

                if (!empty($this->postData['catform_ids'])) {
                    foreach ($this->postData['catform_ids'] as $catformId) {
                        $row = $this->db->xdispense($this->config['db.1.prefix'].$table);

                        $row->item_id = $event['id'] ?? $this->id;
                        $row->catform_id = $catformId;

                        $this->db->store($row);
                    }
                }

                $this->db->commit();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'text' => $e->getTraceAsString(),
                ]);

                $this->errors[] = __('A technical problem has occurred, try again later.');

                $this->db->rollback();
            }
        }
    }

    public function _handleMenuIds(GenericEvent $event): void
    {
        if (\in_array($this->modName.'2menu', $this->additionalTables, true)) {
            $table = $this->modName.'2menu';

            try {
                $this->db->begin();

                if (!\in_array($this->action, ['add'], true)) {
                    $this->db->exec('DELETE FROM '.$this->config['db.1.prefix'].$table.' WHERE item_id = :item_id', ['item_id' => $event['id'] ?? $this->id]);
                }

                if (!empty($this->postData['menu_ids'])) {
                    foreach ($this->postData['menu_ids'] as $menuId) {
                        $row = $this->db->xdispense($this->config['db.1.prefix'].$table);

                        $row->item_id = $event['id'] ?? $this->id;
                        $row->menu_id = $menuId;

                        $this->db->store($row);
                    }
                }

                $this->db->commit();
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage(), [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'text' => $e->getTraceAsString(),
                ]);

                $this->errors[] = __('A technical problem has occurred, try again later.');

                $this->db->rollback();
            }
        }
    }
}
