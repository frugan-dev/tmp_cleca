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

if (false !== ($buffer = $this->session->getFlash('modal'))) {
    echo $buffer;
}
?>
<div class="modal-container"></div>

<script class="template-modal-header" type="text/x-handlebars-template">
    <div{{{headerAttr}}}>
        <h5{{{titleAttr}}}>
            {{title}}
        </h5>
        {{{headerBtnClose}}}
    </div>
</script>

<script class="template-modal-body" type="text/x-handlebars-template">
    <div{{{bodyAttr}}}>
        {{{body}}}
    </div>
</script>

<script class="template-modal-footer" type="text/x-handlebars-template">
    <div{{{footerAttr}}}>
        {{{footerBtnClose}}}
        {{{footer}}}
    </div>
</script>

<script class="template-modal-header-btn-close" type="text/x-handlebars-template">
    <button type="button"{{{headerBtnCloseAttr}}}<?php echo $this->escapeAttr([
        'aria-label' => __('Close'),
    ]); ?>></button>
</script>

<script class="template-modal-footer-btn-close" type="text/x-handlebars-template">
    <button type="button"{{{footerBtnCloseAttr}}}>
        <?php echo $this->escapeHtml(__('Reset')); ?>
        <i class="fas fa-xmark ms-1"></i>
    </button>
</script>

<script class="template-modal" type="text/x-handlebars-template">
    <div tabindex="-1"{{{attr}}}>
        <div{{{dialogAttr}}}>
            <div{{{contentAttr}}}>
                {{{content}}}
            </div>
        </div>
    </div>
</script>
