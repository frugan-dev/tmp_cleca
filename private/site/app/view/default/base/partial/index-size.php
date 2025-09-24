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
<td><?php
    if ('' !== trim((string) $row[$key])) {
        echo $this->helper->File()->formatSize($row[$key]);
    }
?></td>
