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
    <?php if ($this->rbac->isGranted($this->controller.'.api.edit')) { // <--?>
        <a class="edit-toggle" href="javascript:;"<?php echo $this->escapeAttr([
            'data-field' => $key,
            'data-id' => $row['id'],
        ]); ?>><!--
            --><i class="fas fa-check-circle fa-lg fa-fw text-success"<?php echo $this->escapeAttr([
                'style' => empty($row[$key]) ? 'display:none' : false,
            ]); ?>></i><!--
            --><i class="fas fa-times-circle fa-lg fa-fw text-danger"<?php echo $this->escapeAttr([
                'style' => !empty($row[$key]) ? 'display:none' : false,
            ]); ?>></i><!--
            --><i class="fas fa-spinner fa-lg fa-fw fa-pulse text-muted" style="display:none"></i><!--
        --></a>
<?php } elseif (!empty($row[$key])) { ?>
        <i class="fas fa-check fa-lg fa-fw text-success"></i>
    <?php } else { ?>
        <i class="fas fa-times fa-lg fa-fw text-danger"></i>
    <?php } ?>
</td>
