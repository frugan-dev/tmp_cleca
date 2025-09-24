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
    <?php if ('' !== trim((string) $row[$key])) { ?>
        <a data-fancybox title=""<?php echo $this->escapeAttr([
            'href' => $this->asset('media/img/'.$this->controller.'/lg/'.$row[$key]),
        ]); ?>>
            <img class="img-thumbnail" alt=""<?php echo $this->escapeAttr([
                'src' => $this->asset('media/img/'.$this->controller.'/xs/'.$row[$key]),
            ]); ?>>
        </a>
    <?php } ?>
</td>