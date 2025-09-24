<?php declare(strict_types=1);

$ModCatform = $this->container->get('Mod\Catform\\'.ucfirst((string) $this->env));
?>
<td>
    <?php echo $row[$key] ?? ''; ?>

    <?php if (!empty($row['catform_status'])) { ?>
        <span<?php echo $this->escapeAttr([
            'class' => array_merge(['badge', 'text-uppercase', 'ms-1'], $this->helper->Color()->contrast($ModCatform->getStatusColor($row['catform_status']), $this->config['theme.color.contrast.yiq.threshold']) ? ['text-white'] : ['text-body']),
            'style' => 'background-color:'.$ModCatform->getStatusColor($row['catform_status']),
        ]); ?>>
            <?php echo $this->escape()->html(_x('status-'.$row['catform_status'])); ?>
        </span>
    <?php } ?>
</td>
