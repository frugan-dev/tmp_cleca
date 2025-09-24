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

if (!empty($this->siblings)) {
    ?>
    <div class="d-flex justify-content-between gap-3 mb-3 mb-md-0">
        <?php if (!empty($this->siblings['previous'])) { ?>
            <a class="btn btn-outline-secondary flex-grow-1 flex-sm-grow-0" rel="prev"<?php echo $this->escapeAttr([
                'href' => $this->uri($this->siblings['previous']['_routeArgs']),
                'title' => $this->siblings['previous']['name'],
            ]); ?>>
                <i class="fas fa-arrow-left-long fa-fw me-1"></i>
                <?php echo $this->escape()->html(__('Previous')); ?>
            </a>
        <?php } else { ?>
            <div></div>
        <?php } ?>

        <?php if (!empty($this->siblings['next'])) { ?>
            <a class="btn btn-outline-secondary flex-grow-1 flex-sm-grow-0" rel="next"<?php echo $this->escapeAttr([
                'href' => $this->uri($this->siblings['next']['_routeArgs']),
                'title' => $this->siblings['next']['name'],
            ]); ?>>
                <?php echo $this->escape()->html(__('Next')); ?>
                <i class="fas fa-arrow-right-long fa-fw ms-1"></i>
            </a>
        <?php } else { ?>
            <div></div>
        <?php } ?>
    </div>
    <?php
}
