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

if (!$this->hasSection('privacy')) {
    $this->beginSection('privacy');
    ?>
<br>
<br>
<hr>

<small>
    <?php printf($this->escape()->html(__('You receive this email because you have consented to the processing of personal data pursuant to art. 13 of the legislative decrete n. 196/2003 and 14 of the legislative (UE) 2016/679 (GDPR) as per the %1$s statement on the website.')), '<a'.$this->escapeAttr([
        'href' => $this->lang->arr[$this->lang->id]['privacyUrl'],
        'title' => __('Privacy Policy'),
    ]).'>'.$this->escape()->html(__('Privacy Policy')).'</a>'); ?>
</small>
<?php
        $this->endSection();
}
echo $this->getSection('privacy');
?>
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
