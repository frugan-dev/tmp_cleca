<?php declare(strict_types=1); ?>

<p>
    <?php echo $this->escape()->html(sprintf(__('Hi %1$s'), $this->Mod->firstname.' '.$this->Mod->lastname)); ?>,
</p>

<p>
    <?php printf($this->escape()->html(__('your email address %1$s is still not confirmed.')), '<a'.$this->escapeAttr([
        'href' => 'mailto:'.$this->Mod->email,
    ]).'>'.$this->escape()->html($this->Mod->email).'</a>'); ?>
</p>

<p>
    <?php echo $this->escape()->html(__('Click the following link to confirm your email')); ?>:
</p>

<blockquote>
    <b><a<?php echo $this->escapeAttr([
        'href' => $this->uri([
            'routeName' => $this->env.'.'.$this->controller.'.params',
            'data' => [
                'action' => 'confirm',
                'params' => implode(
                    '/',
                    [
                        'private_key',
                        $this->Mod->private_key,
                    ]
                ),
            ],
            'full' => true,
        ]),
    ]); ?>>
            <?php echo $this->escape()->html($this->uri([
                'routeName' => $this->env.'.'.$this->controller.'.params',
                'data' => [
                    'action' => 'confirm',
                    'params' => implode(
                        '/',
                        [
                            'private_key',
                            $this->Mod->private_key,
                        ]
                    ),
                ],
                'full' => true,
            ])); ?>
        </a></b>
</blockquote>

<p>
    <?php echo $this->escape()->html(__('Below is a summary of your personal data')); ?>:
</p>

<blockquote>
    <b><?php echo $this->escape()->html($this->Mod->fields['firstname'][$this->env]['label']); ?>:</b> <?php echo $this->escape()->html($this->Mod->firstname); ?><br>
    <b><?php echo $this->escape()->html($this->Mod->fields['lastname'][$this->env]['label']); ?>:</b> <?php echo $this->escape()->html($this->Mod->lastname); ?><br>
    <b><?php echo $this->escape()->html($this->Mod->fields['email'][$this->env]['label']); ?>:</b> <a<?php echo $this->escapeAttr([
        'href' => 'mailto:'.$this->Mod->email,
    ]); ?>>
        <?php echo $this->escape()->html($this->Mod->email); ?>
    </a><br>
    <b><?php echo $this->escape()->html($this->Mod->fields['password'][$this->env]['label']); ?>:</b> <i><?php echo $this->escape()->html(_x('selected during registration', 'female')); ?></i><br>
    <b><?php echo $this->escape()->html($this->Mod->fields['private_key'][$this->env]['label']); ?>:</b> <?php echo $this->escape()->html($this->Mod->private_key); ?>
</blockquote>

<?php
__('selected during registration', 'default');
__('selected during registration', 'male');
__('selected during registration', 'female');
?>

<p>
    <?php echo $this->escape()->html(__('If you do not confirm your email, your account will automatically be canceled in the future.')); ?>
</p>

<?php
if (!$this->hasSection('footer')) {
    $this->setSection('footer', $this->render('footer'));
}
echo $this->getSection('footer');
