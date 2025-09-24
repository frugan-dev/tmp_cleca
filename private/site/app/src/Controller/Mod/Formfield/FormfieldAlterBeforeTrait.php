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

namespace App\Controller\Mod\Formfield;

trait FormfieldAlterBeforeTrait
{
    public function alterBeforeRequired($key, $langId = null): void
    {
        if (!empty($this->postData[$key])) {
            if (isset($this->postData['type']) && \in_array($this->postData['type'], ['block_text', 'block_separator'], true)) {
                unset($this->postData[$key]);
            }
        }
    }

    public function alterBeforeName($key, $langId = null): void
    {
        if (!empty($this->postData[$key])) {
            if (isset($this->postData['type']) && \in_array($this->postData['type'], ['block_separator'], true)) {
                $this->postData[$key] = null;
            }
        }
    }

    public function alterBeforeRichtext($key, $langId = null): void
    {
        if (!empty($this->postData[$key])) {
            if (isset($this->postData['type']) && \in_array($this->postData['type'], ['block_separator'], true)) {
                $this->postData[$key] = null;
            }
        }
    }
}
