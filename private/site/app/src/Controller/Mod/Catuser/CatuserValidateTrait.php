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

namespace App\Controller\Mod\Catuser;

trait CatuserValidateTrait
{
    public function validatePerms($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->main) || !empty($this->postData['main'])) {
            $reqs = [];

            if (!empty($this->mods[$this->modName]['perms'])) {
                foreach ($this->config['mod.perms.action.arr'] as $action) {
                    if (\array_key_exists($action, $this->mods[$this->modName]['perms'])) {
                        if (\array_key_exists(static::$env, $this->mods[$this->modName]['perms'][$action])) {
                            foreach ($this->mods[$this->modName]['perms'][$action][static::$env] as $perm) {
                                if ($perm === $action) {
                                    if (!\in_array($this->modName.'.'.static::$env.'.'.$perm, $this->postData[$postDataKey] ?? [], true)) {
                                        $reqs[] = $this->pluralName.' -> '.__(static::$env).': '.__($perm);
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if (\count($reqs) > 0) {
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The following %1$s must be active on a %2$s element: %3$s'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>', '<i>'.__('Main').'</i>', '<ul><li>'.implode('</li><li>', $reqs).'</li></ul>'));
            }
        }
    }

    public function validateDeleteId($key, $langId = null, $field = null): void
    {
        parent::validateDeleteId($key, $langId, $field);

        if ($this->auth->getIdentity()[$this->modName.'_id'] === $this->id) {
            $this->filterSubject->validate($key)->is('error')->setMessage(\sprintf(_x('You cannot delete your %1$s.', $this->context), '<i>'.$this->singularName.'</i>'));

            __('You cannot delete your %1$s.', 'default');
            __('You cannot delete your %1$s.', 'male');
            __('You cannot delete your %1$s.', 'female');
        }
    }

    public function validateApiLogLevel($key, $langId = null, $field = null): void
    {
        $this->validateLogLevel($key, $langId, $field);
    }

    public function validateActive($key, $langId = null, $field = null): void
    {
        parent::validateActive($key, $langId, $field);

        if (empty($this->postData[$key])) {
            if ($this->auth->getIdentity()[$this->modName.'_id'] === $this->id) {
                $this->filterSubject->validate($key)->is('error')->setMessage(\sprintf(_x('You cannot disable your %1$s.', $this->context), '<i>'.$this->singularName.'</i>'));

                __('You cannot disable your %1$s.', 'default');
                __('You cannot disable your %1$s.', 'male');
                __('You cannot disable your %1$s.', 'female');
            }
        }
    }
}
