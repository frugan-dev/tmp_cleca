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

<<?php echo !empty($titleTag) ? $titleTag : 'h6'; ?>>
    <?php echo $this->escape()->html($this->config['credits.name']); ?>
</<?php echo !empty($titleTag) ? $titleTag : 'h6'; ?>>

<?php // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/address?>
<address>
    <?php if (!empty($this->config['credits.address'])) { ?>
        <div>
            <?php echo nl2br((string) $this->config['credits.address']); ?>
        </div>
    <?php } ?>

    <?php if (!empty($this->config['credits.vat'])) { ?>
        <div>
            <?php echo $this->escape()->html(__('VAT')); ?>: <?php echo $this->escape()->html($this->config['credits.vat']); ?>
        </div>
    <?php } ?>

    <?php if (!empty($this->config['credits.rea'])) { ?>
        <div>
            <?php echo $this->escape()->html(__('REA')); ?>: <?php echo $this->escape()->html($this->config['credits.rea']); ?>
        </div>
    <?php } ?>

    <?php if (!empty($this->config['credits.tel1'])) { ?>
        <div>
            <?php echo $this->escape()->html(__('Tel')); ?>: <a class="text-reset text-underline-hover"<?php echo $this->escapeAttr(['href' => 'tel:'.$this->helper->Strings()->phoneUri($this->config['credits.tel1'])]); ?>><?php echo $this->escape()->html($this->config['credits.tel1']); ?></a>
        </div>
    <?php } ?>

    <?php if (!empty($this->config['credits.email'])) { ?>
        <div>
            <?php echo $this->escape()->html(__('Email')); ?>: <?php echo $this->obfuscate('<a class="text-reset text-underline-hover"'.$this->escapeAttr(['href' => 'mailto:'.$this->config['credits.email']]).'>'.$this->escape()->html($this->config['credits.email']).'</a>', $obfuscate ?? null); ?>
        </div>
    <?php } ?>

    <?php if (!empty($this->config['credits.url'])) { ?>
        <div>
            <?php echo $this->escape()->html(__('Website')); ?>: <a class="text-reset text-underline-hover"<?php echo $this->escapeAttr([
                'href' => $this->config['credits.url'],
                'title' => $this->config['credits.url.title'],
            ]); ?>><?php echo $this->escape()->html($this->config['credits.url']); ?></a>
        </div>
    <?php } ?>
</address>
