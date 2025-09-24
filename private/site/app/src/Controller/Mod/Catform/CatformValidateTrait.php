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

namespace App\Controller\Mod\Catform;

trait CatformValidateTrait
{
    public function validateCdate($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $this->validateDateTime($key, $langId, $field);

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $cdateObj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $this->postData[$postDataKey], $this->config->get('db.1.timeZone'));

            if (isset($this->postData['sdate']) && !isBlank($this->postData['sdate'])) {
                $field2 = $this->fields['sdate'][static::$env];

                $sdateObj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $this->postData['sdate'], $this->config->get('db.1.timeZone'));

                if ($cdateObj->lessThanOrEqualTo($sdateObj)) {
                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field must be after the %2$s field.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>', '<i>'.$field2['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                }
            }

            if (isset($this->postData['edate']) && !isBlank($this->postData['edate'])) {
                $field3 = $this->fields['edate'][static::$env];

                $edateObj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $this->postData['edate'], $this->config->get('db.1.timeZone'));

                if ($edateObj->lessThanOrEqualTo($cdateObj)) {
                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field must be after the %2$s field.'), '<i>'.$field3['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>', '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                }
            }
        }
    }
}
