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

trait SettingAlterBeforeTrait
{
    public function _alterBeforeOptionInputFile($key, $langId = null): void
    {
        $this->alterBeforeUpload($key, $langId, [
            'subKey' => 'value',
        ]);
    }

    public function _alterBeforeOptionInputFileImg($key, $langId = null): void
    {
        $this->_alterBeforeOptionInputFile($key, $langId);
    }

    public function _alterBeforeOptionLangMultilangInputFile($key, $langId = null): void
    {
        $this->_alterBeforeOptionInputFile($key, $langId);
    }

    public function _alterBeforeOptionLangMultilangInputFileImg($key, $langId = null): void
    {
        $this->_alterBeforeOptionInputFileImg($key, $langId);
    }

    public function alterBeforeUnlinkOption($key, $langId = null): void
    {
        $this->alterBeforeUnlinkUpload($key, $langId, [
            'subKey' => 'value',
        ]);
    }

    public function alterBeforeUnlinkOptionLang($key, $langId = null): void
    {
        $this->alterBeforeUnlinkOption($key, $langId);
    }
}
