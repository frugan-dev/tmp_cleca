<?php declare(strict_types=1);

$this->beginSection('content');
?>
<form data-sync class="needs-validation" novalidate method="POST" autocomplete="off" role="form" action="">
    <?php if (!empty($this->privateKey)) { ?>
        <div class="row mb-3">
            <label for="password"<?php echo $this->escapeAttr([
                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
            ]); ?>>
                <?php echo $this->escape()->html($this->Mod->fields['password'][$this->env]['label']); ?> *
            </label>
            <div<?php echo $this->escapeAttr([
                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
            ]); ?>>
                <?php
                // https://goo.gl/9p2vKq
                // https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofilling-form-controls%3A-the-autocomplete-attribute
        ?>
                <input required autofocus id="password" class="form-control" name="password" type="password" autocomplete="new-password"<?php echo $this->escapeAttr([
                    'minlength' => $this->Mod->fields['password'][$this->env]['attr']['minlength'] ?? false,
                    'maxlength' => $this->Mod->fields['password'][$this->env]['attr']['maxlength'] ?? false,
                    'pattern' => $this->Mod->fields['password'][$this->env]['attr']['pattern'] ?? false,
                    'aria-labelledby' => !empty($this->Mod->fields['password'][$this->env]['help']) ? 'help-password' : false,
                ]); ?>>
                <div class="invalid-feedback"></div>
                <div id="help-password" class="form-text">
                    <?php echo nl2br(is_array($this->Mod->fields['password'][$this->env]['help']) ? implode(PHP_EOL, $this->Mod->fields['password'][$this->env]['help']) : $this->Mod->fields['password'][$this->env]['help']); ?>
                </div>
            </div>
        </div>
<?php } else { ?>
        <input type="hidden" name="timestamp"<?php echo $this->escapeAttr([
            'value' => $this->helper->Carbon()->now()->getTimestamp(),
        ]); ?>>
        <input type="hidden" name="g-recaptcha-response">

        <?php $email = $this->postData['email'] ?? false; ?>
        <div class="row mb-3">
            <label for="email"<?php echo $this->escapeAttr([
                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
            ]); ?>>
                <?php echo $this->escape()->html($this->Mod->fields['email'][$this->env]['label']); ?> *
            </label>
            <div<?php echo $this->escapeAttr([
                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
            ]); ?>>
                <input required autofocus id="email" class="form-control" name="email" type="email"<?php echo $this->escapeAttr([
                    'value' => $email,
                ]); ?>>
                <div class="invalid-feedback"></div>
            </div>
        </div>

        <div class="d-none">
            <input type="text" name="more">
        </div>
    <?php } ?>

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
                <?php echo $this->escape()->html(__('Submit')); ?>
                <?php if (!empty($this->privateKey)) { ?>
                    <i class="fas fa-chevron-right ms-1"></i>
                <?php } else { ?>
                    <i class="fas fa-paper-plane ms-1"></i>
                <?php } ?>
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
                            'routeName' => $this->env.'.action',
                            'data' => [
                                'action' => 'login',
                            ],
                        ]),
                        'title' => __('Login'),
                    ]); ?>>
                        <?php echo $this->escape()->html(__('Login')); ?>
                    </a>
                </li>
                <li>
                    <a<?php echo $this->escapeAttr([
                        'href' => $this->uri([
                            'routeName' => $this->env.'.'.$this->controller,
                            'data' => [
                                'action' => 'signup',
                            ],
                        ]),
                        'title' => __('Signup'),
                    ]); ?>>
                        <?php echo $this->escape()->html(__('Signup')); ?>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</form>
<?php
$this->endSection();
