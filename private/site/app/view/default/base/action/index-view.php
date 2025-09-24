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

if ($this->rbac->isGranted($this->controller.'.'.$this->env.'.'.$val)) {
    ?>
    <a class="btn btn-secondary btn-sm text-nowrap" data-bs-toggle="tooltip"<?php echo $this->escapeAttr([
        'href' => $this->uri([
            'routeName' => $this->env.'.'.$this->controller.'.params',
            'data' => [
                'action' => $val,
                'params' => $row['id'],
            ],
        ]),
        'title' => __($val),
    ]); ?>>
        <span class="d-sm-none">
            <?php echo $this->escape()->html(__($val)); ?>
        </span>
        <i class="fas fa-eye fa-fw ms-1 ms-sm-0"></i>
    </a>
<?php
}
