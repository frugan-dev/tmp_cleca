<?php declare(strict_types=1);

if ($this->rbac->isGranted($this->controller.'.'.$this->env.'.'.$val)) {
    ?>
    <form data-sync class="needs-validation" novalidate method="POST" action="" autocomplete="off" role="form">
        <input type="hidden" name="action" value="search">
        <div class="input-group input-group-sm">
            <input required class="form-control" type="text" name="_search" placeholder="<?php echo $this->escapeAttr(__($val)); ?>&hellip;" aria-label="<?php echo $this->escapeAttr(__($val)); ?>&hellip;"<?php echo $this->escapeAttr([
                'value' => !empty($sessionData = $this->session->get($this->auth->getIdentity()['id'].'.sessionData')) ? ($sessionData[$this->controller][$this->action]['_search'] ?? '') : '',
            ]); ?>>
            <button type="submit" class="btn btn-primary" data-loading-text="<span class='spinner-border spinner-border-sm align-middle' role='status' aria-hidden='true'></span>">
                <i class="fas fa-search fa-fw align-middle"></i>
            </button>
        </div>
    </form>
<?php
}
