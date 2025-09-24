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
<h1>
    <?php echo $this->escape()->html($this->title); ?>
</h1>
<?php if (!empty($this->subTitle)) { ?>
    <h3>
        <?php echo $this->escape()->html($this->subTitle); ?>
    </h3>
<?php
}
