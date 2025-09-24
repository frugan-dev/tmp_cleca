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

use Slim\Psr7\UploadedFile;

trait FormAlterBeforeTrait
{
    public function alterBeforeFillInputFileMultiple($key, $langId = null): void
    {
        $this->alterBeforeUpload($key, $langId);

        ${$this->modName.'fieldId'} = (int) str_replace($this->modName.'field_', '', (string) $key);
        $dest = _ROOT.'/var/upload/catform-'.$this->{'cat'.$this->modName.'_id'}.'/formfield-'.${$this->modName.'fieldId'}.'/'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'];

        if (is_dir($dest)) {
            // in() searches only the current directory, while from() searches its subdirectories too (recursively)
            foreach ($this->helper->Nette()->Finder()->findFiles('*')->in($dest)->sortByName() as $fileObj) {
                $this->postData[$key][] = new UploadedFile(
                    $fileObj->getRealPath(),
                    $fileObj->getBasename(),
                    \Safe\mime_content_type($fileObj->getRealPath()),
                    \Safe\filesize($fileObj->getRealPath())
                );
            }
        }
    }
}
