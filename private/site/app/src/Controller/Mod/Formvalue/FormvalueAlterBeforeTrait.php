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

trait FormvalueAlterBeforeTrait
{
    public function alterBeforeUploadData($key, $langId = null): void
    {
        $this->postData['catmember_id'] = $this->auth->getIdentity()['catmember_id'];
        $this->postData['member_id'] = $this->auth->getIdentity()['id'];
        $this->postData['active'] = 1;

        $this->alterBeforeUpload($key, $langId);
    }
}
