<?php declare(strict_types=1);

if ($this->rbac->isGranted($this->controller.'.'.$this->env.'.'.$val)) {
    ?>
    <a class="btn btn-outline-primary btn-sm" data-bs-toggle="tooltip"<?php echo $this->escapeAttr([
        'href' => $this->uri([
            'routeName' => $this->env.'.'.$this->controller,
            'data' => [
                'action' => $val,
            ],
        ]),
        'title' => __($val),
    ]); ?>>
        <span class="d-sm-none">
            <?php echo $this->escape()->html(__($val)); ?>
        </span>
        <i class="fas fa-file-arrow-down fa-fw ms-1 ms-sm-0"></i>
    </a>
<?php
}
