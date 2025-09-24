<?php declare(strict_types=1); ?>

<p>
    <?php echo $this->escape()->html(sprintf(__('Hi %1$s'), $this->Mod->firstname.' '.$this->Mod->lastname)); ?>,
</p>

<p>
    <?php printf($this->escape()->html(__('your recommendation request has been accepted at %1$s')), '<a'.$this->escapeAttr([
        'href' => $this->helper->Url()->getBaseUrl(),
    ]).'>'.$this->helper->Url()->removeScheme($this->helper->Url()->getBaseUrl()).'</a>'); ?>:
</p>

<blockquote>
    <b><?php echo $this->escape()->html($this->Mod->fields['firstname'][$this->env]['label']); ?>:</b> <?php echo $this->escape()->html($this->auth->getIdentity()['firstname']); ?><br>
    <b><?php echo $this->escape()->html($this->Mod->fields['lastname'][$this->env]['label']); ?>:</b> <?php echo $this->escape()->html($this->auth->getIdentity()['lastname']); ?>
</blockquote>

<p>
    <?php printf(__('If all recommendation requests made have been accepted, you can complete all application forms.')); ?>
</p>

<p>
    <?php echo $this->escape()->html(__('See you soon!')); ?>
</p>

<?php
if (!$this->hasSection('footer')) {
    $this->setSection('footer', $this->render('footer'));
}
echo $this->getSection('footer');
