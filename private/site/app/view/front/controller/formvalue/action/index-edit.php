<?php declare(strict_types=1);

if ($this->rbac->isGranted($this->controller.'.'.$this->env.'.'.$val)) {
    $ModCatform = $this->container->get('Mod\Catform\\'.ucfirst((string) $this->env));

    if (empty($row['active']) && !empty($row['catform_status']) && in_array($row['catform_status'], [$ModCatform::MAINTENANCE, $ModCatform::OPEN, $ModCatform::CLOSING], true)) {
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
        <i class="fas fa-edit fa-fw ms-1 ms-sm-0"></i>
    </a>
<?php
    }
}
