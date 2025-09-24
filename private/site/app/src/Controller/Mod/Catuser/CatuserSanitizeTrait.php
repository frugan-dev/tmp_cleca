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

trait CatuserSanitizeTrait
{
    public function sanitizeApiRlHour($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeApiRlDay($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeApiLogLevel($key, $langId = null): void
    {
        $this->sanitizeLogLevel($key, $langId);
    }
}
