<?php declare(strict_types=1);

$ModCatform = $this->container->get('Mod\Cat'.$this->Mod->modName.'\\'.ucfirst((string) $this->env));
$ModMember = $this->container->get('Mod\Member\\'.ucfirst((string) $this->env));
?>
<p>
    <?php echo $this->escape()->html($this->subject); ?>:
</p>

<blockquote>
    <?php // TODO - ordine traduzione in ita?>
    <b><?php echo $this->escape()->html($this->helper->Nette()->Strings()->firstUpper(sprintf('%1$s %2$s', $this->helper->Nette()->Strings()->lower($ModMember->singularName), __('ID')))); ?>:</b> #<?php echo $this->escape()->html($this->auth->getIdentity()['id']); ?><br>
    <b><?php echo $this->escape()->html($this->helper->Nette()->Strings()->firstUpper(sprintf('%1$s %2$s', $this->helper->Nette()->Strings()->lower($ModMember->singularName), $this->helper->Nette()->Strings()->lower(__('Firstname'))))); ?>:</b> <?php echo $this->escape()->html($this->auth->getIdentity()['firstname']); ?><br>
    <b><?php echo $this->escape()->html($this->helper->Nette()->Strings()->firstUpper(sprintf('%1$s %2$s', $this->helper->Nette()->Strings()->lower($ModMember->singularName), $this->helper->Nette()->Strings()->lower(__('Lastname'))))); ?>:</b> <?php echo $this->escape()->html($this->auth->getIdentity()['lastname']); ?><br>
    <b><?php echo $this->escape()->html($this->helper->Nette()->Strings()->firstUpper(sprintf('%1$s %2$s', $this->helper->Nette()->Strings()->lower($ModMember->singularName), $this->helper->Nette()->Strings()->lower(__('Email'))))); ?>:</b> <a<?php echo $this->escapeAttr([
        'href' => 'mailto:'.$this->auth->getIdentity()['email'],
    ]); ?>>
        <?php echo $this->escape()->html($this->auth->getIdentity()['email']); ?>
    </a><br>
    <b><?php echo $this->escape()->html($this->helper->Nette()->Strings()->firstUpper(sprintf('%1$s %2$s', $this->helper->Nette()->Strings()->lower($ModCatform->singularName), $this->helper->Nette()->Strings()->lower(__('Name'))))); ?>:</b> <?php echo $this->escape()->html($this->{$ModCatform->modName.'Row'}['name']); ?><br>
    <b><?php echo $this->escape()->html($this->helper->Nette()->Strings()->firstUpper(sprintf('%1$s %2$s', $this->helper->Nette()->Strings()->lower($ModCatform->singularName), $this->helper->Nette()->Strings()->lower(__('Code'))))); ?>:</b> <?php echo $this->escape()->html($this->{$ModCatform->modName.'Row'}['code']); ?><br>
    <?php if (!empty($this->lang->arr[$this->lang->id]['privacyUrl'])) { ?>
        <b><?php echo $this->escape()->html(__('Privacy Policy')); ?>:</b> <?php printf($this->escape()->html(__('The user has expressed consent to the processing of personal data pursuant to art. 13 of the legislative decrete n. 196/2003 and 14 of the legislative (UE) 2016/679 (GDPR) as per the %1$s statement on the website.')), '<a'.$this->escapeAttr([
            'href' => $this->lang->arr[$this->lang->id]['privacyUrl'],
            'title' => __('Privacy Policy'),
        ]).'>'.$this->escape()->html(__('Privacy Policy')).'</a>'); ?>
    <?php } ?>
</blockquote>

<?php
if (!$this->hasSection('footer-admin')) {
    $this->setSection('footer-admin', $this->render('footer-admin'));
}
echo $this->getSection('footer-admin');
