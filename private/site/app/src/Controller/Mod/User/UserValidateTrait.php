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

trait UserValidateTrait
{
    public function validateDeleteId($key, $langId = null, $field = null): void
    {
        parent::validateDeleteId($key, $langId, $field);

        if ($this->auth->getIdentity()['id'] === $this->id) {
            $this->filterSubject->validate($key)->is('error')->setMessage(\sprintf(_x('You cannot delete your %1$s.', $this->context), '<i>'.$this->singularName.'</i>'));

            __('You cannot delete your %1$s.', 'default');
            __('You cannot delete your %1$s.', 'male');
            __('You cannot delete your %1$s.', 'female');
        }
    }

    public function validateCatuserId($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $Mod = $this->container->get('Mod\Cat'.$this->modName.'\\'.ucfirst((string) static::$env));

            if (!$Mod->exist([
                'id' => $this->postData[$postDataKey],
            ])) {
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }

            if ($this->auth->hasIdentity()) {
                if ($this->auth->getIdentity()['id'] === $this->id) {
                    if (!empty($this->auth->getIdentity()['cat'.$this->modName.'_main'])) {
                        if ($this->auth->getIdentity()['cat'.$this->modName.'_id'] !== (int) $this->postData[$postDataKey]) {
                            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(_x('You cannot change your %1$s.', $Mod->context), '<i>'.$Mod->singularName.'</i>'));

                            __('You cannot change your %1$s.', 'default');
                            __('You cannot change your %1$s.', 'male');
                            __('You cannot change your %1$s.', 'female');
                        }
                    }
                }
            }
        }
    }

    public function validatePrivateKey($key, $langId = null, $field = null): void
    {
        $this->_validateFieldIfAlreadyExists($key, $langId, $field);
    }

    public function validateActive($key, $langId = null, $field = null): void
    {
        parent::validateActive($key, $langId, $field);

        $field ??= $this->fields[$key][static::$env];

        if (empty($this->postData[$key]) && !\in_array($this->action, $field['skip'] ?? [], true)) {
            if ($this->auth->getIdentity()['id'] === $this->id) {
                $this->filterSubject->validate($key)->is('error')->setMessage(\sprintf(_x('You cannot disable your %1$s.', $this->context), '<i>'.$this->singularName.'</i>'));

                __('You cannot disable your %1$s.', 'default');
                __('You cannot disable your %1$s.', 'male');
                __('You cannot disable your %1$s.', 'female');
            }
        }
    }

    public function validateApiKey($key, $langId = null, $field = null): void
    {
        $this->validateText($key, $langId, $field);
        $this->validatePattern($key, $langId, $field);
        $this->_validateFieldIfAlreadyExists($key, $langId, $field);
    }

    public function _validateFieldIfAlreadyExists($key, $langId = null, $field = null): void
    {
        if (!\in_array($this->action, ['reset'], true)) {
            parent::_validateFieldIfAlreadyExists($key, $langId, $field);
        }
    }
}
