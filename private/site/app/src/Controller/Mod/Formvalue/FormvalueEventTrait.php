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

namespace App\Controller\Mod\Formvalue;

use Slim\Psr7\UploadedFile;
use Symfony\Component\EventDispatcher\GenericEvent;

trait FormvalueEventTrait
{
    public function eventGetCountWhere(GenericEvent $event): void
    {
        parent::eventGetCountWhere($event);

        if (method_exists($this, '_eventWhere') && \is_callable([$this, '_eventWhere'])) {
            $this->_eventWhere($event);
        }
    }

    public function eventActionAddAfter(GenericEvent $event): void
    {
        parent::eventActionAddAfter($event);

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            \call_user_func_array([$this, '_'.__FUNCTION__], [$event]);
        }
    }

    public function eventActionEditAfter(GenericEvent $event): void
    {
        parent::eventActionEditAfter($event);

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            \call_user_func_array([$this, '_'.__FUNCTION__], [$event]);
        }

        if ($this->auth->hasIdentity()) {
            if (!empty($this->cache->taggable)) {
                $tags = [
                    'local-'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'],
                    'global-1',
                ];

                if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                    $tags[] = 'local-'.$this->auth->getIdentity()['_type'].'-'.$this->member_id;
                }

                $this->cache->invalidateTags($tags);
            } else {
                $this->cache->clear();
            }
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
        $this->dbData['sql'] .= ', CONCAT(e.lastname, " ", e.firstname) AS member_lastname_firstname';
        $this->dbData['sql'] .= ', g.code AS catform_code';
        $this->dbData['sql'] .= ', l.name AS form_name';
        $this->dbData['sql'] .= ', m.type AS formfield_type';
        $this->dbData['sql'] .= ', n.name AS formfield_name';
        $this->dbData['sql'] .= ', n.richtext AS formfield_richtext';
    }

    protected function _eventJoin(GenericEvent $event): void
    {
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'member AS e
        ON a.member_id = e.id';
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'catform AS g
        ON a.catform_id = g.id';
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'form_lang AS l
        ON a.form_id = l.item_id';

        // https://stackoverflow.com/a/20123337/3929620
        // https://stackoverflow.com/a/22499259/3929620
        // https://stackoverflow.com/a/40682033/3929620
        foreach (['l'] as $letter) {
            if (empty($this->db->getPDO()->getAttribute(\PDO::ATTR_EMULATE_PREPARES))) {
                $this->dbData['sql'] .= ' AND '.$letter.'.lang_id = :'.$letter.'_lang_id';
                $this->dbData['args'][$letter.'_lang_id'] = $this->lang->id;
            } else {
                $this->dbData['sql'] .= ' AND '.$letter.'.lang_id = :lang_id';
            }
        }

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'formfield AS m
        ON a.formfield_id = m.id';
        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'formfield_lang AS n
        ON a.formfield_id = n.item_id';

        // https://stackoverflow.com/a/20123337/3929620
        // https://stackoverflow.com/a/22499259/3929620
        // https://stackoverflow.com/a/40682033/3929620
        foreach (['n'] as $letter) {
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

    protected function _handleUpload(GenericEvent $event, array $params = []): void
    {
        if (!empty($this->filesData)) {
            foreach ($this->filesData as $key => $value) {
                if (!empty($this->postData['_'.$key])) {
                    $dest = _ROOT.'/var/upload/catform-'.$this->postData['catform_id'].'/formfield-'.$this->postData['formfield_id'].'/'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'];

                    $files = !\is_array($value) ? [$value] : $value;
                    foreach ($files as $fileObj) {
                        if ($fileObj instanceof UploadedFile) {
                            if (UPLOAD_ERR_OK === $fileObj->getError()) {
                                if (($crc32 = $this->helper->Arrays()->recursiveArraySearch('path', $fileObj->getFilePath(), $this->postData['_'.$key], true)) !== false) {
                                    $fileDest = $dest.'/'.$this->postData['_'.$key][$crc32]['name'];

                                    // https://stackoverflow.com/a/61182259/3929620
                                    $fileObj->moveTo($fileDest);
                                }
                            }
                        }
                    }

                    break;
                }
            }
        }
    }
}
