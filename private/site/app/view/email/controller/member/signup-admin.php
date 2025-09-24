<?php declare(strict_types=1); ?>

<p>
    <?php echo $this->escape()->html($this->subject); ?>:
</p>

<blockquote>
    <b><?php echo $this->escape()->html($this->Mod->fields['id'][$this->env]['label']); ?>:</b> #<?php echo $this->escape()->html($this->Mod->id); ?><br>
    <b><?php echo $this->escape()->html($this->Mod->fields['firstname'][$this->env]['label']); ?>:</b> <?php echo $this->escape()->html($this->Mod->firstname); ?><br>
    <b><?php echo $this->escape()->html($this->Mod->fields['lastname'][$this->env]['label']); ?>:</b> <?php echo $this->escape()->html($this->Mod->lastname); ?><br>
    <b><?php echo $this->escape()->html($this->Mod->fields['email'][$this->env]['label']); ?>:</b> <a<?php echo $this->escapeAttr([
        'href' => 'mailto:'.$this->Mod->email,
    ]); ?>>
        <?php echo $this->escape()->html($this->Mod->email); ?>
    </a><br>
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
