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

trait FormvalueSanitizeTrait
{
    public function sanitizeFormfieldId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeUploadData($key, $langId = null): void
    {
        $this->sanitizeUpload($key, $langId);
    }

    public function sanitizeEditData($key, $langId = null): void
    {
        $this->sanitizeUpload($key, $langId);
    }

    public function sanitizeMemberNotMainIn($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizeMemberNotMainOut($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizeMemberMain($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }
}
