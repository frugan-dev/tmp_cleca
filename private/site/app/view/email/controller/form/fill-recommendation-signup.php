<?php declare(strict_types=1); ?>

<p>
    <?php echo $this->escape()->html(sprintf(__('Dear %1$s'), $this->Mod->firstname.' '.$this->Mod->lastname)); ?>,
</p>

<p>
    <?php printf($this->escape()->html(__('We proceeded to create a new account for you at %1$s.')), '<a'.$this->escapeAttr([
        'href' => $this->uri([
            'routeName' => $this->env.'.index',
            'data' => [
                'catform_id' => 0,
            ],
            'full' => true,
        ]),
        'title' => settingOrConfig('company.supName'),
    ]).'>'.$this->escape()->html(settingOrConfig('company.supName')).'</a>'); ?>
</p>

<p>
    <?php echo $this->escape()->html(__('Click the following link to confirm your email')); ?>:
</p>

<blockquote>
    <b><a<?php echo $this->escapeAttr([
        'href' => $this->uri([
            'routeName' => $this->env.'.'.$this->Mod->controller.'.params',
            'data' => [
                'action' => 'confirm',
                'params' => implode(
                    '/',
                    [
                        'private_key',
                        $this->Mod->private_key,
                    ]
                ),
                'catform_id' => 0,
            ],
            'full' => true,
        ]),
    ]); ?>>
            <?php echo $this->escape()->html($this->uri([
                'routeName' => $this->env.'.'.$this->Mod->controller.'.params',
                'data' => [
                    'action' => 'confirm',
                    'params' => implode(
                        '/',
                        [
                            'private_key',
                            $this->Mod->private_key,
                        ]
                    ),
                    'catform_id' => 0,
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
    <b><?php echo $this->escape()->html($this->Mod->fields['password'][$this->env]['label']); ?>:</b> <?php echo $this->escape()->html($this->password); ?><br>
    <b><?php echo $this->escape()->html($this->Mod->fields['private_key'][$this->env]['label']); ?>:</b> <?php echo $this->escape()->html($this->Mod->private_key); ?>
</blockquote>

<p>
    <?php printf($this->escape()->html(__('Access now the site %1$s logging in with your credentials!')), '<a'.$this->escapeAttr([
        'href' => $this->uri([
            'routeName' => $this->env.'.formvalue',
            'data' => [
                'action' => 'index',
                'catform_id' => 0,
            ],
            'full' => true,
        ]),
        'title' => __('Login'),
    ]).'>'.$this->escape()->html(settingOrConfig('company.supName')).'</a>'); ?>
</p>

<?php
if (!$this->hasSection('nota-bene')) {
    $this->setSection('nota-bene', $this->render('nota-bene'));
}
echo $this->getSection('nota-bene');

$this->beginSection('privacy');
$this->endSection();

if (!$this->hasSection('footer')) {
    $this->setSection('footer', $this->render('footer'));
}
echo $this->getSection('footer');
