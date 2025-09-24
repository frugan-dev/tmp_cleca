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
?>
<td>
    <?php if ('' !== trim((string) $row[$key])) {
        if (method_exists($this->Mod, 'getFieldTypes') && is_callable([$this->Mod, 'getFieldTypes'])) {
            if (str_starts_with((string) $row[$key], 'multilang_')) {
                $string = str_replace('multilang_', '', (string) $row[$key]);
                echo ($this->Mod->getFieldTypes()[$string] ?? $string).' ('.__('Multilingual').')';
            } else {
                echo $this->Mod->getFieldTypes()[$row[$key]] ?? $row[$key];
            }
        } else {
            echo $row[$key];
        }
    } ?>
</td>
