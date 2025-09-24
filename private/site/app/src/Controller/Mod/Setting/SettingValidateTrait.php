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

namespace App\Controller\Mod\Setting;

use Slim\Psr7\UploadedFile;

trait SettingValidateTrait
{
    public function __validateOptionRequired($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $field['label'] = _n('Value', 'Values', 1);

        if (!empty($this->postData['required']) || !empty($this->required)) {
            if (empty($this->postData[$postDataKey]['value'])) {
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }

    public function _validateOptionInputEmail($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $field['label'] = _n('Value', 'Values', 1);

        $this->__validateOptionRequired($key, $langId, $field);

        if (!empty($this->postData[$postDataKey]['value'])) {
            $check_dns = true;

            if (!empty($this->config['mail.noDnsCheck'])) {
                if (\Safe\preg_match('/'.implode('|', array_map('preg_quote', $this->config['mail.noDnsCheck'], array_fill(0, \count($this->config['mail.noDnsCheck']), '/'))).'/i', (string) $this->postData[$postDataKey]['value'])) {
                    $check_dns = false;
                }
            }

            if ($check_dns) {
                if (true !== ($response = $this->helper->Validator()->isValidEmail($this->postData[$postDataKey]['value']))) {
                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                }
            // FIXED - avoid previous filterValue->sanitize() alterations in value
            // https://github.com/PHP-DI/PHP-DI/issues/725#issuecomment-667505170
            } elseif (!$this->container->make('filterValue')->validate($this->postData[$postDataKey]['value'], 'email')) { // <--
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }

    // TODO
    public function _validateOptionInputTel($key, $langId = null, $field = null): void
    {
        $this->__validateOptionRequired($key, $langId, $field);
    }

    public function _validateOptionInputText($key, $langId = null, $field = null): void
    {
        $this->__validateOptionRequired($key, $langId, $field);
    }

    // TODO
    public function _validateOptionInputTextNin($key, $langId = null, $field = null): void
    {
        $this->__validateOptionRequired($key, $langId, $field);
    }

    // TODO
    public function _validateOptionInputTextVat($key, $langId = null, $field = null): void
    {
        $this->__validateOptionRequired($key, $langId, $field);
    }

    public function _validateOptionInputUrl($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $field['label'] = _n('Value', 'Values', 1);

        $this->__validateOptionRequired($key, $langId, $field);

        if (!empty($this->postData[$postDataKey]['value'])) {
            // FIXED - avoid previous filterValue->sanitize() alterations in value
            // https://github.com/PHP-DI/PHP-DI/issues/725#issuecomment-667505170
            if (!$this->container->make('filterValue')->validate($this->postData[$postDataKey]['value'], 'urlStrict')) { // <--
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }

    // TODO - Aura.Filter 4.x https://github.com/auraphp/Aura.Filter/blob/4.x/docs/validate.md#uploadedfile
    // https://www.slimframework.com/docs/v4/cookbook/uploading-files.html
    public function _validateOptionInputFile($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $field['label'] = _n('Value', 'Values', 1);

        if (empty($field['attr']['accept'])) {
            $field['attr']['accept'] = implode(',', $this->config['mod.'.static::$env.'.'.$this->modName.'value.mime.file.allowedTypes'] ?? $this->config['mod.'.$this->modName.'value.mime.file.allowedTypes'] ?? $this->config['mime.'.static::$env.'.file.allowedTypes'] ?? $this->config['mime.file.allowedTypes']);
        }

        if (empty($field['attr']['data-maxFileSize'])) {
            $field['attr']['data-maxFileSize'] = $this->helper->File()->getBytes($this->config['mod.'.static::$env.'.'.$this->modName.'value.media.file.uploadMaxFilesize'] ?? $this->config['mod.'.$this->modName.'value.media.file.uploadMaxFilesize'] ?? $this->config['media.'.static::$env.'.file.uploadMaxFilesize'] ?? $this->config['media.file.uploadMaxFilesize'] ?? \Safe\ini_get('upload_max_filesize'));
        }

        if (!empty($this->postData[$postDataKey]['value'])) {
            $files = !\is_array($this->postData[$postDataKey]['value']) ? [$this->postData[$postDataKey]['value']] : $this->postData[$postDataKey]['value'];
            foreach ($files as $fileObj) {
                // FIXED - not working with Slim..
                // $this->filterSubject->validate($postDataKey)->is('upload')->setMessage(sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));

                if ($fileObj instanceof UploadedFile) {
                    if (($uploadError = $fileObj->getError()) !== UPLOAD_ERR_OK) {
                        $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                            'error' => $uploadError,
                        ]);

                        $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                    } elseif (UPLOAD_ERR_OK === $fileObj->getError()) {
                        // FIXED
                        // https://stackoverflow.com/a/12350083/3929620
                        // https://muffinman.io/blog/uploading-files-using-fetch-multipart-form-data/
                        // $fileObj->getClientMediaType() use client's mimeType
                        // \Safe\mime_content_type($fileObj->getFilePath()) needs formData.append()
                        if (!empty($mimeType = \Safe\mime_content_type($fileObj->getFilePath()))) {
                            if (\array_key_exists('accept', $field['attr'])) {
                                $accept = explode(',', (string) $field['attr']['accept']);

                                // https://stackoverflow.com/a/3432266
                                array_walk(
                                    $accept,
                                    function (&$item): void {
                                        $item = trim($item);
                                        $item = preg_quote($item, '/');
                                        $item = str_replace('\*', '.*', $item);
                                        $item = str_replace('\.', '.*\.', $item);
                                    }
                                );

                                // FIXED - avoid previous filterValue->sanitize() alterations in value
                                // https://github.com/PHP-DI/PHP-DI/issues/725#issuecomment-667505170
                                if (!$this->container->make('filterValue')->validate($mimeType, 'regex', '/(^'.implode('|', $accept).'$)/i')) {
                                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                                }
                            }
                        }

                        if (\array_key_exists('data-minWidth', $field['attr']) || \array_key_exists('data-minHeight', $field['attr'])) {
                            [$width, $height, $type, $attr] = \Safe\getimagesize($fileObj->getFilePath());

                            if (\array_key_exists('data-minWidth', $field['attr'])) {
                                if ($width < $field['attr']['data-minWidth']) {
                                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The image selected for the %1$s field does not have the minimum width required.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                                }
                            }

                            if (\array_key_exists('data-minHeight', $field['attr'])) {
                                if ($height < $field['attr']['data-minHeight']) {
                                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The image selected for the %1$s field does not have the minimum height required.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                                }
                            }
                        }
                    }

                    if (!empty($fileObj->getSize())) {
                        if (\array_key_exists('data-maxFileSize', $field['attr'])) {
                            if ($fileObj->getSize() > $field['attr']['data-maxFileSize']) {
                                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                            }
                        }
                    }
                }
            }
        }
    }

    public function _validateOptionInputFileImg($key, $langId = null, $field = null): void
    {
        $field['attr']['accept'] = implode(',', $this->config['mod.'.static::$env.'.'.$this->modName.'value.mime.img.allowedTypes'] ?? $this->config['mod.'.$this->modName.'value.mime.img.allowedTypes'] ?? $this->config['mime.'.static::$env.'.img.allowedTypes'] ?? $this->config['mime.img.allowedTypes']);

        $field['attr']['data-maxFileSize'] = $this->helper->File()->getBytes($this->config['mod.'.static::$env.'.'.$this->modName.'value.media.img.uploadMaxFilesize'] ?? $this->config['mod.'.$this->modName.'value.media.img.uploadMaxFilesize'] ?? $this->config['media.'.static::$env.'.img.uploadMaxFilesize'] ?? $this->config['media.img.uploadMaxFilesize'] ?? \Safe\ini_get('upload_max_filesize'));

        $field['attr']['data-minWidth'] = $this->config['mod.'.$this->modName.'.img.minWidth'] ?? $this->config['media.img.minWidth'];

        $field['attr']['data-minHeight'] = $this->config['mod.'.$this->modName.'.img.minHeight'] ?? $this->config['media.img.minHeight'];

        $this->_validateOptionInputFile($key, $langId, $field);
    }

    public function _validateOptionTextarea($key, $langId = null, $field = null): void
    {
        $this->__validateOptionRequired($key, $langId, $field);
    }

    public function _validateOptionTextareaRicheditSimple($key, $langId = null, $field = null): void
    {
        $this->__validateOptionRequired($key, $langId, $field);
    }

    public function _validateOptionLangMultilangInputEmail($key, $langId = null, $field = null): void
    {
        $this->_validateOptionInputEmail($key, $langId, $field);
    }

    public function _validateOptionLangMultilangInputTel($key, $langId = null, $field = null): void
    {
        $this->_validateOptionInputTel($key, $langId, $field);
    }

    public function _validateOptionLangMultilangInputText($key, $langId = null, $field = null): void
    {
        $this->_validateOptionInputText($key, $langId, $field);
    }

    public function _validateOptionLangMultilangInputTextNin($key, $langId = null, $field = null): void
    {
        $this->_validateOptionInputTextNin($key, $langId, $field);
    }

    public function _validateOptionLangMultilangInputTextVat($key, $langId = null, $field = null): void
    {
        $this->_validateOptionInputTextVat($key, $langId, $field);
    }

    public function _validateOptionLangMultilangInputUrl($key, $langId = null, $field = null): void
    {
        $this->_validateOptionInputUrl($key, $langId, $field);
    }

    public function _validateOptionLangMultilangInputFile($key, $langId = null, $field = null): void
    {
        $this->_validateOptionInputFile($key, $langId, $field);
    }

    public function _validateOptionLangMultilangInputFileImg($key, $langId = null, $field = null): void
    {
        $this->_validateOptionInputFileImg($key, $langId, $field);
    }

    public function _validateOptionLangMultilangTextarea($key, $langId = null, $field = null): void
    {
        $this->_validateOptionTextarea($key, $langId, $field);
    }

    public function _validateOptionLangMultilangTextareaRicheditSimple($key, $langId = null, $field = null): void
    {
        $this->_validateOptionTextareaRicheditSimple($key, $langId, $field);
    }
}
