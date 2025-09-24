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

trait FormAlterAfterTrait
{
    public function alterAfterFillCheckbox($key, $langId = null): void
    {
        $this->alterAfterJson($key, $langId);
    }

    public function alterAfterFillInputFileMultiple($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            ${$this->modName.'fieldId'} = (int) str_replace($this->modName.'field_', '', (string) $key);
            $dest = _ROOT.'/var/upload/catform-'.$this->{'cat'.$this->modName.'_id'}.'/formfield-'.${$this->modName.'fieldId'}.'/'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'];

            $this->helper->Nette()->FileSystem()->createDir($dest);

            $this->alterAfterUpload($key, $langId, [
                'dest' => $dest,
            ]);
        }
    }

    public function alterAfterFillRecommendation($key, $langId = null): void
    {
        if (!empty($this->postData[$key])) {
            $value = $this->postData[$key];
            $this->postData[$key] = [];
            $this->postData[$key][] = $value;
            $this->postData[$key]['teachers'] = $this->postData[$key.'_teachers'];

            ${$this->modName.'fieldId'} = (int) str_replace($this->modName.'field_', '', (string) $key);

            if (($fieldKey = $this->helper->Arrays()->recursiveArraySearch('id', ${$this->modName.'fieldId'}, $this->viewData[$this->modName.'fieldResult'], true)) !== false) {
                if (\is_array($this->viewData[$this->modName.'fieldResult'][$fieldKey][$this->modName.'value_data'])) {
                    foreach ($this->viewData[$this->modName.'fieldResult'][$fieldKey][$this->modName.'value_data']['teachers'] as $k => $v) {
                        if (($teacherKey = $this->helper->Arrays()->recursiveArraySearch('email', $v['email'], $this->postData[$key]['teachers'], true)) !== false) { // <--
                            if (!empty($v['id'])) {
                                $this->postData[$key]['teachers'][$teacherKey]['id'] = $v['id'];
                            }
                            if (!empty($v['mdate'])) {
                                $this->postData[$key]['teachers'][$teacherKey]['mdate'] = $v['mdate'];
                            }
                            if (!empty($v['ldate'])) {
                                $this->postData[$key]['teachers'][$teacherKey]['ldate'] = $v['ldate'];
                            }
                            if (!empty($v['files'])) {
                                $this->postData[$key]['teachers'][$teacherKey]['files'] = $v['files'];
                            }
                            if (!empty($v['status'])) {
                                $this->postData[$key]['teachers'][$teacherKey]['status'] = $v['status'];
                            }
                        }
                    }
                }
            }

            foreach ($this->postData[$key]['teachers'] as $k => $v) {
                if (!empty($v['files'])) {
                    continue;
                }

                $this->postData[$key]['teachers'][$k]['ldate'] = $this->helper->Carbon()->now($this->config['db.1.timeZone'])->toDateTimeString();
            }

            $this->postData['_'.$key] = $this->postData[$key];
            $this->postData[$key.'_teachers'] = $this->postData[$key]['teachers'];
        }

        $this->alterAfterJson($key, $langId);
    }
}
