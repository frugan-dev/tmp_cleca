<?php declare(strict_types=1);

$ModCatform = $this->container->get('Mod\Cat'.$this->Mod->modName.'\\'.ucfirst((string) $this->env));
?>

<p>
    <?php echo $this->escape()->html(sprintf(__('Congratulations %1$s'), $this->auth->getIdentity()['firstname'].' '.$this->auth->getIdentity()['lastname'])); ?>!
</p>

<p>
    <?php printf($this->escape()->html(__('You have successfully completed all application forms in category %1$s.')), '<a'.$this->escapeAttr([
        'href' => $this->uri([
            'routeName' => $this->env.'.'.$ModCatform->modName.'.params',
            'data' => [
                'action' => 'view',
                'params' => $this->{$ModCatform->modName.'Row'}['id'],
                $ModCatform->modName.'_id' => $this->{$ModCatform->modName.'Row'}['id'],
            ],
            'full' => true,
        ]),
        'title' => $this->{$ModCatform->modName.'Row'}['name'],
    ]).'>'.$this->escape()->html($this->{$ModCatform->modName.'Row'}['name']).'</a>'); ?>
</p>

<p>
    <?php printf($this->escape()->html(__('Now you are allowed to print all application forms you have filled going to %1$s page.')), '<a'.$this->escapeAttr([
        'href' => $this->uri([
            'routeName' => $this->env.'.'.$this->Mod->modName,
            'data' => [
                'action' => 'index',
            ],
            'full' => true,
        ]),
        'title' => sprintf(__('Print %1$s'), $this->helper->Nette()->Strings()->lower($this->Mod->pluralName)),
    ]).'>'.$this->escape()->html(sprintf(__('Print %1$s'), $this->helper->Nette()->Strings()->lower($this->Mod->pluralName))).'</a>'); ?>
</p>

<p>
    <?php echo $this->escape()->html(__('See you soon!')); ?>
</p>

<?php
if (!$this->hasSection('footer')) {
    $this->setSection('footer', $this->render('footer'));
}
echo $this->getSection('footer');
