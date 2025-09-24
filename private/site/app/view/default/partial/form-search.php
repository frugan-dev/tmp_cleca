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
?>

<form data-async class="needs-validation" novalidate method="POST" action="" autocomplete="off" role="form">
    <div class="input-group input-group-sm">
        <input required type="text" class="form-control" placeholder="<?php echo $this->escapeAttr(__('Search')); ?>&hellip;" aria-label="<?php echo $this->escapeAttr(__('Search')); ?>&hellip;">
        <div class="input-group-append">
            <button type="submit" class="btn btn-primary" data-loading-text="<span class='spinner-border spinner-border-sm align-middle' role='status' aria-hidden='true'></span>">
                <i class="fas fa-search fa-lg fa-fw align-middle"></i>
            </button>
        </div>
    </div>
</form>
