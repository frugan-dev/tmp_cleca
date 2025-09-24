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
    <div class="row mb-3">
        <label for="username"<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
        ]); ?>>
            <?php echo $this->escape()->html(__('Email').'/'.__('Username')); ?> *
        </label>
        <div<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
        ]); ?>>
            <?php
            // https://goo.gl/9p2vKq
            // https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofilling-form-controls%3A-the-autocomplete-attribute
?>
            <input required autofocus id="username" class="form-control" name="username" type="text" autocomplete="username"<?php echo $this->escapeAttr([
                'value' => $username,
            ]); ?>>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="row mb-3">
        <label for="password"<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
        ]); ?>>
            <?php echo $this->escape()->html(__('Password')); ?> *
        </label>
        <div<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
        ]); ?>>
            <?php
// https://goo.gl/9p2vKq
// https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofilling-form-controls%3A-the-autocomplete-attribute
?>
            <input required id="password" class="form-control" name="password" type="password" autocomplete="current-password">
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <div class="row mb-3">
        <div<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.offset.class'] ?? $this->config['theme.'.$this->env.'.value.offset.class'] ?? $this->config['theme.'.$this->action.'.value.offset.class'] ?? $this->config['theme.value.offset.class'] ?? false,
        ]); ?>>
            <ul class="list-unstyled text-muted fs-xs">
                <li>* <?php echo $this->escape()->html(__('Required fields')); ?></li>
            </ul>
        </div>
    </div>

    <div class="row mb-3">
        <div<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.btn.col.class'] ?? $this->config['theme.'.$this->env.'.btn.col.class'] ?? $this->config['theme.'.$this->action.'.btn.col.class'] ?? $this->config['theme.btn.col.class'] ?? false,
        ]); ?>>
            <button type="submit" data-loading-text="<span class='spinner-border spinner-border-sm align-middle me-1' role='status' aria-hidden='true'></span> <?php echo $this->escapeAttr(__('Please wait')); ?>&hellip;"<?php echo $this->escapeAttr([
                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.btn.class'] ?? $this->config['theme.'.$this->env.'.btn.class'] ?? $this->config['theme.'.$this->action.'.btn.class'] ?? $this->config['theme.btn.class'] ?? false,
            ]); ?>>
                <?php echo $this->escape()->html(__('Login')); ?>
                <i class="fas fa-sign-in-alt ms-1"></i>
            </button>
        </div>
    </div>

    <br>

    <div class="row mb-3">
        <div<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.offset.class'] ?? $this->config['theme.'.$this->env.'.value.offset.class'] ?? $this->config['theme.'.$this->action.'.value.offset.class'] ?? $this->config['theme.value.offset.class'] ?? false,
        ]); ?>>
            <ul>
                <li>
                    <a<?php echo $this->escapeAttr([
                        'href' => $this->uri([
                            'routeName' => $this->env.'.member',
                            'data' => [
                                'action' => 'signup',
                            ],
                        ]),
                        'title' => __('Signup'),
                    ]); ?>>
                        <?php echo $this->escape()->html(__('Signup')); ?>
                    </a>
                </li>
                <li>
                    <a<?php echo $this->escapeAttr([
                        'href' => $this->uri([
                            'routeName' => $this->env.'.member',
                            'data' => [
                                'action' => 'reset',
                            ],
                        ]),
                        'title' => __('Password reset'),
                    ]); ?>>
                        <?php echo $this->escape()->html(__('Password reset')); ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</form>
<?php
$this->endSection();

$this->beginSection('back-button');
$this->endSection();
