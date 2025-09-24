<?php declare(strict_types=1);

if ($this->rbac->isGranted($this->controller.'.'.$this->env.'.'.$val) && empty($row['cat'.$this->controller.'_main'])) {
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
        <i class="fas fa-download fa-fw ms-1 ms-sm-0"></i>
    </a>
<?php
}
