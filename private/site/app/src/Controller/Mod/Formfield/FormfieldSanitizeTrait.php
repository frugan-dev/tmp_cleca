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

trait FormfieldSanitizeTrait
{
    public function _sanitizeOptionInputFileMultiple($key, $langId = null): void
    {
        if (isset($this->postData[$key]['max_files'])) {
            $this->filterValue->sanitize($this->postData[$key]['max_files'], 'int');
        }
    }

    public function _sanitizeOptionLangCheckbox($key, $langId = null): void
    {
        if (isset($this->postData[$key]['values'])) {
            $this->filterValue->sanitize($this->postData[$key]['values'], 'trim');
            $this->filterValue->sanitize($this->postData[$key]['values'], 'stripTags');
            $this->filterValue->sanitize($this->postData[$key]['values'], 'linesKeyValue');
        }
    }

    public function _sanitizeOptionLangRadio($key, $langId = null): void
    {
        $this->_sanitizeOptionLangCheckbox($key, $langId);
    }

    public function _sanitizeOptionLangSelect($key, $langId = null): void
    {
        $this->_sanitizeOptionLangCheckbox($key, $langId);
    }

    public function _sanitizeOptionRecommendation($key, $langId = null): void
    {
        if (isset($this->postData[$key]['max_teachers'])) {
            $this->filterValue->sanitize($this->postData[$key]['max_teachers'], 'int');
        }

        if (isset($this->postData[$key]['max_files'])) {
            $this->filterValue->sanitize($this->postData[$key]['max_files'], 'int');
        }
    }
}
