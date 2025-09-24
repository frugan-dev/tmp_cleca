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
<p>
    <?php echo $this->escape()->html(sprintf(__('Hi %1$s'), implode(' ', array_filter($this->row, fn ($k) => in_array($k, $this->Mod->authNameFields, true), ARRAY_FILTER_USE_KEY)))); ?>,
</p>

<p>
    <?php echo sprintf($this->escape()->html(__('someone has requested a password reset for the following account at %1$s')), '<a'.$this->escapeAttr([
        'href' => $this->helper->Url()->getBaseUrl(),
    ]).'>'.$this->helper->Url()->removeScheme($this->helper->Url()->getBaseUrl()).'</a>'); ?>:
</p>

<blockquote>
    <b><?php echo $this->escape()->html(__('Email')); ?>:</b> <a<?php echo $this->escapeAttr([
        'href' => 'mailto:'.$this->email,
    ]); ?>>
        <?php echo $this->escape()->html($this->email); ?>
    </a>
</blockquote>

<p>
    <?php echo $this->escape()->html(__('To reset your password, click the following link')); ?>:
</p>

<blockquote>
    <b><a<?php echo $this->escapeAttr([
        'href' => $this->uri([
            'routeName' => $this->env.'.'.$this->controller.'.params',
            'data' => [
                'action' => 'reset',
                'params' => implode(
                    '/',
                    [
                        'private_key',
                        $this->row['private_key'],
                    ]
                ),
            ],
            'full' => true,
        ]),
    ]); ?>>
        <?php echo $this->escape()->html($this->uri([
            'routeName' => $this->env.'.'.$this->controller.'.params',
            'data' => [
                'action' => 'reset',
                'params' => implode(
                    '/',
                    [
                        'private_key',
                        $this->row['private_key'],
                    ]
                ),
            ],
            'full' => true,
        ])); ?>
    </a></b>
</blockquote>

<p>
    <?php echo $this->escape()->html(__('If it was an error, ignore this email and nothing will happen.')); ?>
</p>

<p>
    <?php echo $this->escape()->html(__('On this occasion, we send our best regards.')); ?>
</p>

<?php
if (!$this->hasSection('footer')) {
    $this->setSection('footer', $this->render('footer'));
}
echo $this->getSection('footer');
