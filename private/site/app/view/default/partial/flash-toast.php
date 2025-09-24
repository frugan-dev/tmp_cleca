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

if (false !== ($buffer = $this->session->getFlash('toast'))) {
    echo $buffer;
}
// Note that the live region needs to be present in the markup before the toast is generated or updated.
// If you dynamically generate both at the same time and inject them into the page,
// they will generally not be announced by assistive technologies.
?>
<div class="toast-container position-fixed bottom-0 start-0 p-3" aria-live="polite" aria-atomic="true"></div>

<script class="template-toast-header" type="text/x-handlebars-template">
    <div{{{headerAttr}}}>
        <strong class="me-auto">
            {{title}}
        </strong>
        <small>
            {{subTitle}}
        </small>
        {{{btnClose}}}
    </div>
</script>

<script class="template-toast-body" type="text/x-handlebars-template">
    <div{{{bodyAttr}}}>
        {{{body}}}
    </div>
</script>

<script class="template-toast-btn-close" type="text/x-handlebars-template">
    <button type="button"{{{btnCloseAttr}}}<?php echo $this->escapeAttr([
        'aria-label' => __('Close'),
    ]); ?>></button>
</script>

<script class="template-toast" type="text/x-handlebars-template">
    <div role="alert" aria-live="assertive" aria-atomic="true"{{{attr}}}>
        <div{{{childAttr}}}>
            {{{content}}}
        </div>
    </div>
</script>
