<?php declare(strict_types=1); ?>
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

    <?php if (!empty($row['status'])) { ?>
        <span<?php echo $this->escapeAttr([
            'class' => array_merge(['badge', 'text-uppercase', 'ms-1'], $this->helper->Color()->contrast($this->Mod->getStatusColor($row['status']), $this->config['theme.color.contrast.yiq.threshold']) ? ['text-white'] : ['text-body']),
            'style' => 'background-color:'.$this->Mod->getStatusColor($row['status']),
        ]); ?>>
            <?php echo $this->escape()->html(_x('status-'.$row['status'])); ?>
        </span>
    <?php } ?>
</td>
