<?php declare(strict_types=1);

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
