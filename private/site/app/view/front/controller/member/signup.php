<?php declare(strict_types=1);

$this->beginSection('content');
?>
<form data-sync class="needs-validation" novalidate method="POST" autocomplete="off" role="form" action="">
    <input type="hidden" name="timestamp"<?php echo $this->escapeAttr([
        'value' => $this->helper->Carbon()->now()->getTimestamp(),
    ]); ?>>
    <input type="hidden" name="g-recaptcha-response">

    <?php $firstname = $this->postData['firstname'] ?? false; ?>
    <div class="row mb-3">
        <label for="firstname"<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
        ]); ?>>
            <?php echo $this->escape()->html($this->Mod->fields['firstname'][$this->env]['label']); ?> *
        </label>
        <div<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
        ]); ?>>
            <input required autofocus id="firstname" class="form-control" name="firstname" type="text"<?php echo $this->escapeAttr([
                'value' => $firstname,
                'maxlength' => $this->Mod->fields['firstname'][$this->env]['attr']['maxlength'] ?? false,
                'pattern' => $this->Mod->fields['firstname'][$this->env]['attr']['pattern'] ?? false,
            ]); ?>>
            <div class="invalid-feedback"></div>
        </div>
    </div>

    <?php $lastname = $this->postData['lastname'] ?? false; ?>
    <div class="row mb-3">
        <label for="lastname"<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
        ]); ?>>
            <?php echo $this->escape()->html($this->Mod->fields['lastname'][$this->env]['label']); ?> *
        </label>
        <div<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
        ]); ?>>
            <input required id="lastname" class="form-control" name="lastname" type="text"<?php echo $this->escapeAttr([
                'value' => $lastname,
                'maxlength' => $this->Mod->fields['lastname'][$this->env]['attr']['maxlength'] ?? false,
                'pattern' => $this->Mod->fields['lastname'][$this->env]['attr']['pattern'] ?? false,
            ]); ?>>
            <div class="invalid-feedback"></div>
        </div>
    </div>

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
            <?php
            // https://goo.gl/9p2vKq
            // https://html.spec.whatwg.org/multipage/form-control-infrastructure.html#autofilling-form-controls%3A-the-autocomplete-attribute
?>
            <input required id="email" class="form-control" name="email" type="email" autocomplete="username"<?php echo $this->escapeAttr([
                'value' => $email,
                'maxlength' => $this->Mod->fields['email'][$this->env]['attr']['maxlength'] ?? false,
            ]); ?>>
            <div class="invalid-feedback"></div>
        </div>
    </div>

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

    <?php $privacy = $this->postData['privacy'] ?? false; ?>
    <div class="row mb-3">
        <div<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.offset.class'] ?? $this->config['theme.'.$this->env.'.value.offset.class'] ?? $this->config['theme.'.$this->action.'.value.offset.class'] ?? $this->config['theme.value.offset.class'] ?? false,
        ]); ?>>
            <div class="form-check small">
                <input required class="form-check-input" name="privacy" type="checkbox" value="1"<?php echo $this->escapeAttr([
                    'id' => 'privacy-'.$this->controller,
                    'checked' => (bool) $privacy,
                ]); ?>>
                <label class="form-check-label"<?php echo $this->escapeAttr([
                    'for' => 'privacy-'.$this->controller,
                ]); ?>>
                    <?php printf(
                        $this->escape()->html(_nx('I have read the %1$s and agree', 'I have read the %1$s and agree', 1, 'female')),
                        '<a target="_blank"'.$this->escapeAttr([
                            'href' => $this->lang->arr[$this->lang->id]['privacyUrl'] ?? 'javascript:;',
                            'title' => __('Privacy Policy'),
                        ]).'>'.$this->escape()->html(__('Privacy Policy')).'</a>'
                    );
_n('I have read the %1$s and agree', 'I have read the %1$s and agree', 1, 'default');
_n('I have read the %1$s and agree', 'I have read the %1$s and agree', 1, 'male');
_n('I have read the %1$s and agree', 'I have read the %1$s and agree', 1, 'female');
?>
                </label>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>

    <div class="d-none">
        <input type="text" name="more">
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
                <?php echo $this->escape()->html(__('Signup')); ?>
                <i class="fas fa-user-plus ms-1"></i>
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
