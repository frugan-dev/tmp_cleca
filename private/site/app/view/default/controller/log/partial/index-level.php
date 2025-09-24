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

use App\Factory\Logger\LoggerInterface;

?>
<td>
    <span<?php echo $this->escapeAttr([
        'class' => array_merge(['badge', 'text-uppercase'], $this->helper->Color()->contrast($this->container->get(LoggerInterface::class)->getLevelColor($row[$key]), $this->config['theme.color.contrast.yiq.threshold']) ? ['text-white'] : ['text-body']),
        'style' => 'background-color:'.$this->container->get(LoggerInterface::class)->getLevelColor($row[$key]),
    ]); ?>>
        <?php echo $this->escape()->html($this->container->get(LoggerInterface::class)::toMonologLevel($row[$key])->getName()); ?>
    </span>
</td>