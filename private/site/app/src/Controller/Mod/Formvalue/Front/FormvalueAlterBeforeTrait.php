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

namespace App\Controller\Mod\Formvalue\Front;

use Slim\Psr7\UploadedFile;

trait FormvalueAlterBeforeTrait
{
    public function alterBeforeEditData($key, $langId = null): void
    {
        $this->alterBeforeUpload($key, $langId);

        $dest = _ROOT.'/var/upload/catform-'.$this->catform_id.'/formfield-'.$this->formfield_id.'/'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'];

        if (!empty($data = $this->helper->Nette()->Json()->decode((string) $this->{$key}, forceArrays: true))) {
            if (($teacherKey = $this->helper->Arrays()->recursiveArraySearch('id', $this->auth->getIdentity()['id'], $data['teachers'], true)) !== false) {
                if (is_dir($dest) && !empty($data['teachers'][$teacherKey]['files'])) {
                    // in() searches only the current directory, while from() searches its subdirectories too (recursively)
                    foreach ($this->helper->Nette()->Finder()->findFiles('*')->in($dest)->sortByName() as $fileObj) {
                        $crc32 = $this->helper->Strings()->crc32($fileObj->getRealPath());

                        if (\array_key_exists($crc32, $data['teachers'][$teacherKey]['files'])) {
                            $this->postData[$key][] = new UploadedFile(
                                $fileObj->getRealPath(),
                                $fileObj->getBasename(),
                                \Safe\mime_content_type($fileObj->getRealPath()),
                                \Safe\filesize($fileObj->getRealPath())
                            );

                            if (\count($this->postData[$key]) === \count($data['teachers'][$teacherKey]['files'])) {
                                break;
                            }
                        }
                    }
                } else {
                    $this->errors[] = __('A technical problem has occurred, try again later.');
                }
            } else {
                $this->errors[] = __('A technical problem has occurred, try again later.');
            }
        } else {
            $this->errors[] = __('A technical problem has occurred, try again later.');
        }
    }
}
