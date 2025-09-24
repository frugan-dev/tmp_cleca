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

trait FormvalueAlterAfterTrait
{
    public function alterAfterEditData($key, $langId = null): void
    {
        $this->postData['catform_id'] = $this->catform_id;
        $this->postData['formfield_id'] = $this->formfield_id;

        $this->alterAfterUploadData($key, $langId);

        if (!empty($data = $this->helper->Nette()->Json()->decode((string) $this->{$key}, forceArrays: true))) {
            if (($teacherKey = $this->helper->Arrays()->recursiveArraySearch('id', $this->auth->getIdentity()['id'], $data['teachers'], true)) !== false) {
                if (!empty($data['teachers'][$teacherKey]['files'])) {
                    $mergeSubArr = $this->postData['_'.$key] + $data['teachers'][$teacherKey]['files'];
                    $mergeSubArr = $this->helper->Arrays()->uasortBy($mergeSubArr, 'name');
                } else {
                    $mergeSubArr = $this->postData['_'.$key];
                }

                $mergeArr = $data;
                $mergeArr['teachers'][$teacherKey]['files'] = $mergeSubArr;
                $mergeArr['teachers'][$teacherKey]['mdate'] = $this->helper->Carbon()->now($this->config['db.1.timeZone'])->toDateTimeString();
                $mergeArr['teachers'][$teacherKey]['status'] = 1;

                $this->postData[$key] = $this->helper->Nette()->Json()->encode($mergeArr);
            } else {
                $this->errors[] = __('A technical problem has occurred, try again later.');
            }
        } else {
            $this->errors[] = __('A technical problem has occurred, try again later.');
        }
    }
}
