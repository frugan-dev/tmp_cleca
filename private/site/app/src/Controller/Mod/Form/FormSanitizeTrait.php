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

namespace App\Controller\Mod\Form;

trait FormSanitizeTrait
{
    public function sanitizeFillCheckbox($key, $langId = null): void
    {
        $this->sanitizeArrayMixed($key, $langId);
    }

    public function sanitizeFillCountry($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeFillInputEmail($key, $langId = null): void
    {
        $this->sanitizeTextLowercase($key, $langId);
    }

    public function sanitizeFillInputFileMultiple($key, $langId = null): void
    {
        $this->sanitizeUpload($key, $langId);
    }

    public function sanitizeFillInputNumberIntegerGt0($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeFillInputNumberIntegerGte0($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeFillInputUrl($key, $langId = null): void
    {
        $this->sanitizeUrl($key, $langId);
    }

    public function sanitizeFillRadio($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            if (ctype_digit((string) $this->postData[$key])) {
                $this->sanitizeInteger($key, $langId);
            } else {
                $this->filterSubject->sanitize($key)->toBlankOr('trim');
            }
        }
    }

    public function sanitizeFillSelect($key, $langId = null): void
    {
        $this->sanitizeFillRadio($key, $langId);
    }

    public function sanitizeFillTextarea($key, $langId = null): void
    {
        $this->sanitizeTextarea($key, $langId);
    }

    public function sanitizeFillRecommendation($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->sanitizeInteger($key, $langId);

            if (isset($this->postData[$key.'_teachers'])) {
                $this->sanitizeArray($key.'_teachers', $langId);

                foreach ($this->postData[$key.'_teachers'] as $k => $v) {
                    $this->filterValue->sanitize($this->postData[$key.'_teachers'][$k]['firstname'], 'trim');
                    $this->filterValue->sanitize($this->postData[$key.'_teachers'][$k]['firstname'], 'lowercase');
                    $this->filterValue->sanitize($this->postData[$key.'_teachers'][$k]['firstname'], 'titlecase');

                    $this->filterValue->sanitize($this->postData[$key.'_teachers'][$k]['lastname'], 'trim');
                    $this->filterValue->sanitize($this->postData[$key.'_teachers'][$k]['lastname'], 'lowercase');
                    $this->filterValue->sanitize($this->postData[$key.'_teachers'][$k]['lastname'], 'titlecase');

                    $this->filterValue->sanitize($this->postData[$key.'_teachers'][$k]['email'], 'trim');
                    $this->filterValue->sanitize($this->postData[$key.'_teachers'][$k]['email'], 'lowercase');
                }
            }
        }
    }
}
