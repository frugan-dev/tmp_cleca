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

trait SettingAlterAfterTrait
{
    public function _alterAfterOptionInputFile($key, $langId = null): void
    {
        $this->alterAfterUpload($key, $langId, [
            'subKey' => 'value',
            'type' => $this->postData['type'] ?? null,
        ]);
    }

    public function _alterAfterOptionInputFileImg($key, $langId = null): void
    {
        $this->_alterAfterOptionInputFile($key, $langId);
    }

    public function _alterAfterOptionLangMultilangInputFile($key, $langId = null): void
    {
        $this->_alterAfterOptionInputFile($key, $langId);
    }

    public function _alterAfterOptionLangMultilangInputFileImg($key, $langId = null): void
    {
        $this->_alterAfterOptionInputFileImg($key, $langId);
    }
}
