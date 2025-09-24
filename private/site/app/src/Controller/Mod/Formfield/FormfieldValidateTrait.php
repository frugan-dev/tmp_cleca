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

trait FormfieldValidateTrait
{
    public function _validateOptionInputFileMultiple($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        // FIXED - avoid previous filterValue->sanitize() alterations in value
        // https://github.com/PHP-DI/PHP-DI/issues/725#issuecomment-667505170
        if (!$this->container->make('filterValue')->validate($this->postData[$postDataKey]['max_files'], 'min', 1)) { // <--
            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.__('Max. files').'</i>'));
        } elseif (!$this->container->make('filterValue')->validate($this->postData[$postDataKey]['max_files'], 'max', \Safe\ini_get('max_file_uploads'))) { // <--
            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.__('Max. files').'</i>'));
        }
    }

    public function _validateOptionLangCheckbox($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (empty($this->postData[$postDataKey]['values'])) {
            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.__('Values').($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        // FIXED - avoid previous filterValue->sanitize() alterations in value
        // https://github.com/PHP-DI/PHP-DI/issues/725#issuecomment-667505170
        } elseif (!$this->container->make('filterValue')->validate($this->postData[$postDataKey]['values'], 'linesKeyValue')) { // <--
            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.__('Values').($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        }
    }

    public function _validateOptionLangRadio($key, $langId = null, $field = null): void
    {
        $this->_validateOptionLangCheckbox($key, $langId, $field);
    }

    public function _validateOptionLangSelect($key, $langId = null, $field = null): void
    {
        $this->_validateOptionLangCheckbox($key, $langId, $field);
    }

    public function _validateOptionRecommendation($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        // FIXED - avoid previous filterValue->sanitize() alterations in value
        // https://github.com/PHP-DI/PHP-DI/issues/725#issuecomment-667505170
        if (!$this->container->make('filterValue')->validate($this->postData[$postDataKey]['max_teachers'], 'min', 1)) { // <--
            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.__('Max. teachers').'</i>'));
        }

        // FIXED - avoid previous filterValue->sanitize() alterations in value
        // https://github.com/PHP-DI/PHP-DI/issues/725#issuecomment-667505170
        if (!$this->container->make('filterValue')->validate($this->postData[$postDataKey]['max_files'], 'min', 1)) { // <--
            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.__('Max. files').'</i>'));
        } elseif (!$this->container->make('filterValue')->validate($this->postData[$postDataKey]['max_files'], 'max', \Safe\ini_get('max_file_uploads'))) { // <--
            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.__('Max. files').'</i>'));
        }
    }
}
