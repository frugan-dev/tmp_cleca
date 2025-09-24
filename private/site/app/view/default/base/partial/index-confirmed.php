<?php declare(strict_types=1); ?>
<td>
    <?php if (!empty($row[$key])) { ?>
        <i class="fas fa-check fa-lg fa-fw text-success"></i>
    <?php } else { ?>
        <i class="fas fa-times fa-lg fa-fw text-danger"></i>
    <?php } ?>
</td>
