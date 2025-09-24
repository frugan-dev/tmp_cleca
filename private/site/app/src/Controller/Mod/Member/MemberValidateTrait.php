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

namespace App\Controller\Mod\Member;

trait MemberValidateTrait
{
    public function validateFirstname($key, $langId = null, $field = null): void
    {
        $this->validateText($key, $langId, $field);
    }

    public function validateLastname($key, $langId = null, $field = null): void
    {
        $this->validateText($key, $langId, $field);
    }

    public function validatePrivateKey($key, $langId = null, $field = null): void
    {
        $this->_validateFieldIfAlreadyExists($key, $langId, $field);
    }

    public function _validateFieldIfAlreadyExists($key, $langId = null, $field = null): void
    {
        if (!\in_array($this->action, ['reset'], true)) {
            parent::_validateFieldIfAlreadyExists($key, $langId, $field);
        }
    }
}
