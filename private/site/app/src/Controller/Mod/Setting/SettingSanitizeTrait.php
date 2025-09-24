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

trait SettingSanitizeTrait
{
    public function sanitizeCode($key, $langId = null): void {}

    public function sanitizeUnlinkOption($key, $langId = null): void
    {
        if (isset($this->postData[$key]['value'])) {
            $this->filterValue->sanitize($this->postData[$key]['value'], 'bool');
        }
    }

    public function sanitizeUnlinkOptionLang($key, $langId = null): void
    {
        $this->sanitizeUnlinkOption($key, $langId);
    }

    public function _sanitizeOptionInputEmail($key, $langId = null): void
    {
        if (isset($this->postData[$key]['value'])) {
            $this->filterValue->sanitize($this->postData[$key]['value'], 'trim');
            $this->filterValue->sanitize($this->postData[$key]['value'], 'lowercase');
        }
    }

    public function _sanitizeOptionInputTel($key, $langId = null): void
    {
        if (isset($this->postData[$key]['value'])) {
            $this->filterValue->sanitize($this->postData[$key]['value'], 'trim');
        }
    }

    public function _sanitizeOptionInputText($key, $langId = null): void
    {
        if (isset($this->postData[$key]['value'])) {
            $this->filterValue->sanitize($this->postData[$key]['value'], 'trim');
            $this->filterValue->sanitize($this->postData[$key]['value'], 'linearize', ' ');
            $this->filterValue->sanitize($this->postData[$key]['value'], 'stripTags');
            $this->filterValue->sanitize($this->postData[$key]['value'], 'stripEmoji');
        }
    }

    // TODO
    public function _sanitizeOptionInputTextNin($key, $langId = null): void
    {
        if (isset($this->postData[$key]['value'])) {
            $this->_sanitizeOptionInputText($this->postData[$key]['value'], $langId);
        }
    }

    // TODO
    public function _sanitizeOptionInputTextVat($key, $langId = null): void
    {
        if (isset($this->postData[$key]['value'])) {
            $this->_sanitizeOptionInputText($this->postData[$key]['value'], $langId);
        }
    }

    public function _sanitizeOptionInputUrl($key, $langId = null): void
    {
        if (!empty($this->postData[$key]['value'])) { // <--
            $this->filterValue->sanitize($this->postData[$key]['value'], 'trim');
            $this->filterValue->sanitize($this->postData[$key]['value'], 'lowercase');
            $this->filterValue->sanitize($this->postData[$key]['value'], 'addScheme');
        }
    }

    // TODO - Aura.Filter 4.x https://github.com/auraphp/Aura.Filter/blob/4.x/docs/sanitize.md#uploadedfileornull
    public function _sanitizeOptionInputFile($key, $langId = null): void {}

    public function _sanitizeOptionInputFileImg($key, $langId = null): void
    {
        $this->_sanitizeOptionInputFile($key, $langId);
    }

    public function _sanitizeOptionTextarea($key, $langId = null): void
    {
        if (isset($this->postData[$key]['value'])) {
            $this->filterValue->sanitize($this->postData[$key]['value'], 'trim');
            $this->filterValue->sanitize($this->postData[$key]['value'], 'stripTags');
        }
    }

    public function _sanitizeOptionTextareaRicheditSimple($key, $langId = null): void
    {
        if (isset($this->postData[$key]['value'])) {
            $this->filterValue->sanitize($this->postData[$key]['value'], 'trim');
            $this->filterValue->sanitize($this->postData[$key]['value'], 'purifyHtml');
        }
    }

    public function _sanitizeOptionLangMultilangInputEmail($key, $langId = null): void
    {
        $this->_sanitizeOptionInputEmail($key, $langId);
    }

    public function _sanitizeOptionLangMultilangInputTel($key, $langId = null): void
    {
        $this->_sanitizeOptionInputTel($key, $langId);
    }

    public function _sanitizeOptionLangMultilangInputText($key, $langId = null): void
    {
        $this->_sanitizeOptionInputText($key, $langId);
    }

    public function _sanitizeOptionLangMultilangInputTextNin($key, $langId = null): void
    {
        $this->_sanitizeOptionInputTextNin($key, $langId);
    }

    public function _sanitizeOptionLangMultilangInputTextVat($key, $langId = null): void
    {
        $this->_sanitizeOptionInputTextVat($key, $langId);
    }

    public function _sanitizeOptionLangMultilangInputUrl($key, $langId = null): void
    {
        $this->_sanitizeOptionInputUrl($key, $langId);
    }

    public function _sanitizeOptionLangMultilangInputFile($key, $langId = null): void
    {
        $this->_sanitizeOptionInputFile($key, $langId);
    }

    public function _sanitizeOptionLangMultilangInputFileImg($key, $langId = null): void
    {
        $this->_sanitizeOptionInputFileImg($key, $langId);
    }

    public function _sanitizeOptionLangMultilangTextarea($key, $langId = null): void
    {
        $this->_sanitizeOptionTextarea($key, $langId);
    }

    public function _sanitizeOptionLangMultilangTextareaRicheditSimple($key, $langId = null): void
    {
        $this->_sanitizeOptionTextareaRicheditSimple($key, $langId);
    }
}
