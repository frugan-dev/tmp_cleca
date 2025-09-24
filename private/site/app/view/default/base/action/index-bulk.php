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

if (!empty($this->bulkActions)) {
    ?>
    <?php echo $this->helper->Html()->getFormField([
        'type' => 'input',
        'attr' => [
            'name' => 'bulk_ids[]',
            'type' => 'checkbox',
            'value' => $row['id'],
            'id' => 'bulk-'.$row['id'],
            'class' => ['btn-check'],
            'form' => $this->controller.'-form-bulk', // https://stackoverflow.com/a/21900324/3929620
        ],
    ]); ?>
    <label class="btn btn-secondary btn-sm text-nowrap"<?php /* data-bs-toggle="tooltip" */ ?><?php echo $this->escapeAttr([
        'for' => 'bulk-'.$row['id'],
        // 'data-bs-title' => $this->helper->Nette()->Strings()->firstUpper(__('select')),
    ]); ?>>
        <span class="d-sm-none">
            <?php echo $this->escape()->html($this->helper->Nette()->Strings()->firstUpper(__('select'))); ?>
        </span>
        <i class="fas fa-square fa-fw fa-lg ms-1 ms-sm-0"></i>
        <i class="fas fa-square-check fa-fw fa-lg ms-1 ms-sm-0 d-none"></i>
    </label>
<?php
}
