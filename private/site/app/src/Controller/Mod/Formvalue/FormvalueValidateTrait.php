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

trait FormvalueValidateTrait
{
    public function validateUploadData($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $field['attr']['accept'] = implode(',', $this->config['mod.'.static::$env.'.'.$this->modName.'.mime.file.allowedTypes'] ?? $this->config['mod.'.$this->modName.'.mime.file.allowedTypes'] ?? $this->config['mime.'.static::$env.'.file.allowedTypes'] ?? $this->config['mime.file.allowedTypes']);
        $field['attr']['data-maxFileSize'] = $this->helper->File()->getBytes($this->config['mod.'.static::$env.'.'.$this->modName.'.media.file.uploadMaxFilesize'] ?? $this->config['mod.'.$this->modName.'.media.file.uploadMaxFilesize'] ?? $this->config['media.'.static::$env.'.file.uploadMaxFilesize'] ?? $this->config['media.file.uploadMaxFilesize'] ?? \Safe\ini_get('upload_max_filesize'));

        $this->validateUpload($key, $langId, $field);
    }

    public function validateEditData($key, $langId = null, $field = null): void
    {
        $this->validateUploadData($key, $langId, $field);
    }

    public function validateMemberNotMainIn($key, $langId = null, $field = null): void
    {
        $this->validateBoolean($key, $langId, $field);
    }

    public function validateMemberNotMainOut($key, $langId = null, $field = null): void
    {
        $this->validateBoolean($key, $langId, $field);
    }

    public function validateMemberMain($key, $langId = null, $field = null): void
    {
        $this->validateBoolean($key, $langId, $field);
    }
}
