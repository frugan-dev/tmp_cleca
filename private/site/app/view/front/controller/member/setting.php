<?php declare(strict_types=1);

/*
 * This file is part of the Slim 4 PHP application.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 license that is bundled
 * with this source code in the file COPYING.
 */

if (empty($this->Mod->confirmed)) {
    ?>
    <form data-sync class="needs-validation" novalidate method="POST" autocomplete="off" role="form" action="">
        <input type="hidden" name="action" value="sendkey">
<?php } ?>
    <div class="row mb-3">
        <div<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.btn.col.class'] ?? $this->config['theme.'.$this->env.'.btn.col.class'] ?? $this->config['theme.'.$this->action.'.btn.col.class'] ?? $this->config['theme.btn.col.class'] ?? false,
        ]); ?>>
            <p>
                <?php echo $this->escape()->html(__('Didn\'t receive the confirmation email?')); ?>
            </p>
            <button data-loading-text="<span class='spinner-border spinner-border-sm align-middle me-1' role='status' aria-hidden='true'></span> <?php echo $this->escapeAttr(__('Please wait')); ?>&hellip;"<?php echo $this->escapeAttr([
                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.btn.class'] ?? $this->config['theme.'.$this->env.'.btn.class'] ?? $this->config['theme.'.$this->action.'.btn.class'] ?? $this->config['theme.btn.class'] ?? false,
                'type' => !empty($this->Mod->confirmed) ? 'button' : 'submit',
                'disabled' => !empty($this->Mod->confirmed) ? true : false,
            ]); ?>>
                <?php echo $this->escape()->html(__('Send again')); ?>
                <i class="fas fa-paper-plane ms-1"></i>
            </button>
        </div>
    </div>
<?php if (empty($this->Mod->confirmed)) { ?>
    </form>
<?php } ?>

<hr>

<form data-sync id="form-delete" class="needs-validation" novalidate method="POST" autocomplete="off" role="form" action="">
    <input type="hidden" name="action" value="delete">

    <div class="row mb-3">
        <div<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.btn.col.class'] ?? $this->config['theme.'.$this->env.'.btn.col.class'] ?? $this->config['theme.'.$this->action.'.btn.col.class'] ?? $this->config['theme.btn.col.class'] ?? false,
        ]); ?>>
            <p class="text-danger">
                <?php echo $this->escape()->html(__('Do you want to delete your account?')); ?>
            </p>
            <a data-bs-toggle="modal" href="javascript:;" role="button"<?php echo $this->escapeAttr([
                'class' => array_merge($this->config['theme.'.$this->env.'.'.$this->action.'.btn.class'] ?? $this->config['theme.'.$this->env.'.btn.class'] ?? $this->config['theme.'.$this->action.'.btn.class'] ?? $this->config['theme.btn.class'] ?? [], ['btn-danger']),
            ]); ?>>
                <?php echo $this->escape()->html(__('Delete account')); ?>
                <i class="fas fa-trash-alt ms-1"></i>
            </a>
        </div>
    </div>
</form>

<?php
    $this->scriptsFoot()->beginInternal();
echo '(() => {
    const formDeleteElement = document.getElementById("form-delete");
    if (formDeleteElement) {
        const btnModalElement = formDeleteElement.querySelector("[data-bs-toggle=\"modal\"]");
        if (btnModalElement) {
            const modalDeleteInstance = App.Mod.Modal.add(
                "'.$this->escape()->js(__('Do you confirm the operation?')).'",
                "'.$this->escape()->js(__('Warning')).'",
                "<button type=\"submit\" form=\"form-delete\" data-loading-text=\"<span class=\'spinner-border spinner-border-sm align-middle me-1\' role=\'status\' aria-hidden=\'true\'></span> '.$this->escapeAttr(__('Please wait')).'&hellip;\"'.addcslashes((string) $this->escapeAttr([
    'class' => array_merge($this->config['theme.'.$this->env.'.'.$this->action.'.btn.class'] ?? $this->config['theme.'.$this->env.'.btn.class'] ?? $this->config['theme.'.$this->action.'.btn.class'] ?? $this->config['theme.btn.class'] ?? [], ['btn-danger']),
]), '"').'>'.$this->escape()->js(__('Confirm')).' <i class=\"fas fa-triangle-exclamation ms-1\"></i></button>",
                "sm",
                "danger"
            );
            btnModalElement.setAttribute("data-bs-target", "#modal-" + modalDeleteInstance.getId());

            btnModalElement.addEventListener("click", () => {
                //FIXME - double backdrop
                const modalBackdropList = Array.prototype.slice.call(
                    document.querySelectorAll(".modal-backdrop")
                );
                if (modalBackdropList.length > 1) {
                    for (const modalBackdropElement of modalBackdropList) {
                        modalBackdropElement.parentNode.removeChild(modalBackdropElement);
                        break;
                    }
                }
            });
        }
    }
})();';
$this->scriptsFoot()->endInternal();
