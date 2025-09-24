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

trait FormvalueAlterAfterTrait
{
    public function alterAfterUploadData($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $dest = _ROOT.'/var/upload/catform-'.$this->postData['catform_id'].'/formfield-'.$this->postData['formfield_id'].'/'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'];

            $this->helper->Nette()->FileSystem()->createDir($dest);

            $this->alterAfterUpload($key, $langId, [
                'dest' => $dest,
            ]);
        }
    }
}
