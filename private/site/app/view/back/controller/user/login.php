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
    <?php $username = $this->postData['username'] ?? false; ?>
    <div class="form-floating">
        <?php
        // https://goo.gl/9p2vKq
        // https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofilling-form-controls%3A-the-autocomplete-attribute
?>
        <input style="margin-bottom:-1px;" required autofocus class="form-control rounded-0 rounded-top" name="username" type="text" autocomplete="username"<?php echo $this->escapeAttr([
            'placeholder' => __('Username'),
            'value' => $username,
        ]); ?>>
        <label for="username">
            <?php echo $this->escape()->html(__('Username')); ?>
        </label>
    </div>

    <div class="form-floating mb-3">
        <?php
// https://goo.gl/9p2vKq
// https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofilling-form-controls%3A-the-autocomplete-attribute
?>
        <input required class="form-control rounded-0 rounded-bottom" name="password" type="password" autocomplete="current-password"<?php echo $this->escapeAttr([
            'placeholder' => __('Password'),
        ]); ?>>
        <label for="password">
            <?php echo $this->escape()->html(__('Password')); ?>
        </label>
    </div>

    <?php
    // TODO - https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
    /*
    <div<?php echo $this->escapeAttr([
'class' => array_merge(['form-check', 'd-flex', 'justify-content-center', 'small', 'mb-3'], !empty($this->config['theme.checkbox.switches']) ? ['form-switch'] : [])
    ])?>>
<input id="remember" class="form-check-input cursor-pointer" name="remember" type="checkbox" value="1">
<label class="form-check-label ms-1 cursor-pointer" for="remember">
    <?php echo $this->escape()->html(__('Remember me')) ?>
</label>
    </div>
    */ ?>

    <button class="btn btn-primary w-100 mb-3" type="submit" data-loading-text="<span class='spinner-border spinner-border-sm align-middle me-1' role='status' aria-hidden='true'></span> <?php echo $this->escapeAttr(__('Please wait')); ?>&hellip;">
        <?php echo $this->escape()->html(__('Login')); ?> <i class="fas fa-right-to-bracket ms-1"></i>
    </button>

    <nav>
        <ol class="breadcrumb justify-content-center">
            <li class="breadcrumb-item small">
                <a<?php echo $this->escapeAttr([
                    'href' => $this->uri([
                        'routeName' => $this->env.'.user',
                        'data' => [
                            'action' => 'reset',
                        ],
                    ]),
                    'title' => __('Password reset'),
                ]); ?>>
                    <?php echo $this->escape()->html(__('Password reset')); ?>
                </a>
            </li>
        </ol>
    </nav>
</form>
<?php
$this->endSection();
