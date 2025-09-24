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

if (!empty($row['name'])) {
    ?>
    <h4>
        <?php echo $this->escape()->html($row['name']); ?>
    </h4>
<?php
}

if (!empty($row['richtext'])) {
    echo !\Safe\preg_match('~<[^<]+>~', $row['richtext']) ? '<p>'.$row['richtext'].'</p>'.PHP_EOL : $row['richtext'];
}
