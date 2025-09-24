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

namespace App\Controller\Mod\Catuser\Api;

trait CatuserValidateTrait
{
    public function validateToggleActive($key, $langId = null, $field = null): void
    {
        parent::validateToggleActive($key, $langId, $field);

        if (!empty($this->postData['field'])) {
            if ($this->auth->getIdentity()[$this->modName.'_id'] === $this->id) {
                $this->filterSubject->validate('id')->is('error')->setMessage(\sprintf(_x('You cannot disable your %1$s.', $this->context), '<i>'.$this->singularName.'</i>'));

                __('You cannot disable your %1$s.', 'default');
                __('You cannot disable your %1$s.', 'male');
                __('You cannot disable your %1$s.', 'female');
            }
        }
    }
}
