<?php declare(strict_types=1);

/*
 * This file is part of the Slim 4 PHP application.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 license that is bundled
 * with this source code in the file COPYING.
 */
?>
<p>
    <b><?php echo $this->escape()->html(__('NOTA BENE')); ?>:</b>

    <br><?php echo $this->escape()->html(__('Recommendation letters must be written on a headed paper of the University concerned and signed. All recommendation letters without a headed paper and not signed will not be considered and the student’s application will be incomplete.')); ?>
    <br><?php echo $this->escape()->html(__('Please, inform your student when you have uploaded the letter, as the student needs to complete and submit the application after all documents have been provided.')); ?>

    <?php if (!empty($value = settingOrConfig('company.email'))) { ?>
        <br><?php printf($this->escape()->html(__('In case of technical problems, you can contact the following address: %1$s')), $this->obfuscate('<a'.$this->escapeAttr([
            'href' => isset($this->config['page.'.$this->lang->code.'.arr']['contact']) ? $this->uri([
                // 'routeName' => $this->env.'.page',
                'routeName' => 'front.page', // <--
                'data' => [
                    'slug' => $this->helper->Nette()->Strings()->webalize(
                        $this->config['page.'.$this->lang->code.'.arr']['contact']['slug'] ?? $this->config['page.'.$this->lang->code.'.arr']['contact']['metaTitle'],
                        $this->config['url.front.nette.webalize.charlist'] ?? $this->config['url.nette.webalize.charlist'] ?? null // <--
                    ),
                ],
                'full' => ('front' !== $this->env),
            ]) : 'mailto:'.$value,
        ]).'>'.$this->escape()->html($value).'</a>', $obfuscate ?? null)); ?>
    <?php } ?>
</p>

<p>
    <?php echo $this->escape()->html(__('Thank you for your attention')); ?>,
    <br><i><?php echo $this->escape()->html(sprintf(__('The %1$s staff'), settingOrConfig('company.name'))); ?></i>
</p>
