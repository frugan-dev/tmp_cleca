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

<br>
<br>
<hr>

<h4>
    <?php echo $this->escape()->html(__('Technical data')); ?>
</h4>

<small>
    <?php if (!empty($this->fullUrl)) { ?>
        <b><?php echo $this->escape()->html(__('Submit page')); ?>:</b> <a href="<?php echo $this->escapeAttr($this->fullUrl); ?>"><?php echo $this->escape()->html($this->fullUrl); ?></a><br>
    <?php } ?>
    <b><?php echo $this->escape()->html(__('Referer page')); ?>:</b> <a href="<?php echo $this->escapeAttr($this->referer); ?>"><?php echo $this->escape()->html($this->referer); ?></a><br>
    <b><?php echo $this->escape()->html(__('Language')); ?>:</b> <?php echo $this->escape()->html($this->lang->code); ?><br>
    <b><?php echo $this->escape()->html(__('Browser languages')); ?>:</b> <?php echo $this->escape()->html($this->httpAcceptLanguage); ?><br>
    <b><?php echo $this->escape()->html(__('IP')); ?>:</b> <?php echo $this->escape()->html($this->clientIp); ?><br>
    <b><?php echo $this->escape()->html(__('Hostname ISP')); ?>:</b> <?php echo $this->escape()->html($this->hostByAddr); ?><br>
    <b><?php echo $this->escape()->html(__('Browser')); ?>:</b> <?php echo $this->escape()->html($this->httpUserAgent); ?><br>
</small>

<br>
<br>
<hr>

<small>
    <?php echo $this->render('address-company', [
        'titleTag' => 'h4',
        'obfuscate' => false,
        'short' => true,
    ]); ?>

    <br>

    <?php printf(
        $this->escape()->html(__('Powered by %1$s')),
        '<a target="_blank"'.$this->escapeAttr([
            'href' => $this->config['credits.url'],
            'title' => $this->config['credits.url.title'],
        ]).'>'.$this->escape()->html($this->config['credits.name']).'</a>'
    ); ?>
</small>
