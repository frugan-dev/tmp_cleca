<?php declare(strict_types=1); ?>

<p>
    <?php echo $this->escape()->html(sprintf(__('Dear %1$s'), $this->Mod->firstname.' '.$this->Mod->lastname)); ?>,
</p>

<p>
    <?php printf($this->escape()->html(__('You\'ve received a new recommendation request at %1$s from')), '<a'.$this->escapeAttr([
        'href' => $this->helper->Url()->getBaseUrl(),
    ]).'>'.$this->helper->Url()->removeScheme($this->helper->Url()->getBaseUrl()).'</a>'); ?>:
</p>

<blockquote>
    <b><?php echo $this->escape()->html($this->Mod->fields['firstname'][$this->env]['label']); ?>:</b> <?php echo $this->escape()->html($this->firstname); ?><br>
    <b><?php echo $this->escape()->html($this->Mod->fields['lastname'][$this->env]['label']); ?>:</b> <?php echo $this->escape()->html($this->lastname); ?>
</blockquote>

<p>
    <?php printf($this->escape()->html(__('Access now the site %1$s logging in with your credentials you received in a separate message.')), '<a'.$this->escapeAttr([
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

<p>
    <?php echo $this->escape()->html(__('The aforementioned student is waiting for your letter of recommendation to be uploaded!')); ?>
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
