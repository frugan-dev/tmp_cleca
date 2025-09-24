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

$this->beginSection('content');
?>
<form data-sync class="needs-validation" novalidate method="POST" autocomplete="off" role="form" action="">
    <?php if (!empty($this->privateKey)) { ?>
        <div class="form-floating mb-3">
            <?php
            // https://goo.gl/9p2vKq
            // https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofilling-form-controls%3A-the-autocomplete-attribute
        ?>
            <input required autofocus class="form-control" name="password" type="password" autocomplete="new-password"<?php echo $this->escapeAttr([
                'placeholder' => __('Password'),
                'minlength' => $this->Mod->fields['password'][$this->env]['attr']['minlength'] ?? false,
                'maxlength' => $this->Mod->fields['password'][$this->env]['attr']['maxlength'] ?? false,
                'pattern' => $this->Mod->fields['password'][$this->env]['attr']['pattern'] ?? false,
                'aria-labelledby' => !empty($this->Mod->fields['password'][$this->env]['help']) ? 'help-password' : false,
            ]); ?>>
            <label for="password">
                <?php echo $this->escape()->html(__('Password')); ?>
            </label>
            <div class="invalid-feedback"></div>
            <div id="help-password" class="form-text">
                <?php echo nl2br(is_array($this->Mod->fields['password'][$this->env]['help']) ? implode(PHP_EOL, $this->Mod->fields['password'][$this->env]['help']) : $this->Mod->fields['password'][$this->env]['help']); ?>
            </div>
        </div>
<?php } else { ?>
        <input type="hidden" name="timestamp"<?php echo $this->escapeAttr([
            'value' => $this->helper->Carbon()->now()->getTimestamp(),
        ]); ?>>
        <input type="hidden" name="g-recaptcha-response">

        <?php $email = $this->postData['email'] ?? false; ?>
        <div class="form-floating mb-3">
            <input required autofocus class="form-control" name="email" type="email"<?php echo $this->escapeAttr([
                'placeholder' => __('Email'),
                'value' => $email,
            ]); ?>>
            <label for="email">
                <?php echo $this->escape()->html(__('Email')); ?>
            </label>
            <div class="invalid-feedback"></div>
        </div>

        <div class="d-none">
            <input type="text" name="more">
        </div>
    <?php } ?>

    <button class="btn btn-primary w-100 mb-3" type="submit" data-loading-text="<span class='spinner-border spinner-border-sm align-middle me-1' role='status' aria-hidden='true'></span> <?php echo $this->escapeAttr(__('Please wait')); ?>&hellip;">
        <?php echo $this->escape()->html(__('Submit')); ?>
        <?php if (!empty($this->privateKey)) { ?>
            <i class="fas fa-chevron-right ms-1"></i>
        <?php } else { ?>
            <i class="fas fa-paper-plane ms-1"></i>
        <?php } ?>
    </button>

    <nav>
        <ol class="breadcrumb justify-content-center">
            <li class="breadcrumb-item small">
                <a<?php echo $this->escapeAttr([
                    'href' => $this->uri([
                        'routeName' => $this->env.'.user',
                        'data' => [
                            'action' => 'login',
                        ],
                    ]),
                    'title' => __('Login'),
                ]); ?>>
                    <?php echo $this->escape()->html(__('Login')); ?>
                </a>
            </li>
        </ol>
    </nav>
</form>
<?php
$this->endSection();
