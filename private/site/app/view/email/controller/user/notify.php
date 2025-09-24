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
?>

<h4>
    <?php echo $this->escape()->html($this->subject); ?>
</h4>

<b><?php echo $this->escape()->html(__('Date')); ?>:</b> <?php echo $this->helper->Carbon()->now()->toDateTimeString(); ?> <small>(<?php echo date_default_timezone_get(); ?>)</small><br>
<b><?php echo $this->escape()->html(__('ID')); ?>:</b> <?php echo $this->escape()->html($this->authIdentity['id']); ?><br>
<b><?php echo $this->escape()->html($this->container->get('Mod\\'.ucfirst((string) $this->authIdentity['_role_type']).'\\'.ucfirst((string) $this->env))->singularName); ?>:</b> <?php echo $this->escape()->html($this->authIdentity[$this->authIdentity['_role_type'].'_name']); ?><br>
<b><?php echo $this->escape()->html(__('Name')); ?>:</b> <?php echo $this->escape()->html($this->authIdentity['_name']); ?><br>
<b><?php echo $this->escape()->html(__('Username')); ?>:</b> <?php echo $this->escape()->html($this->authIdentity['_username']); ?><br>
<b><?php echo $this->escape()->html(__('Email')); ?>:</b> <?php echo $this->escape()->html($this->authIdentity['email']); ?><br>

<?php
if (!$this->hasSection('footer-admin')) {
    $this->setSection('footer-admin', $this->render('footer-admin'));
}
echo $this->getSection('footer-admin');
