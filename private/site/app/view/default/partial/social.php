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

$this->beginSection('buffer');
?>
    <?php if (!empty($value = settingOrConfig('company.social.facebook.url'))) { ?>
        <li class="list-inline-item">
            <a class="d-inline-block text-reset" data-bs-toggle="tooltip" title="Facebook" target="_blank"<?php echo $this->escapeAttr([
                'href' => $value,
                'data-bs-placement' => $tooltipPlacement ?? false,
            ]); ?>>
                <i<?php echo $this->escapeAttr([
                    'class' => array_merge(['fab', 'fa-facebook-square', 'fa-fw'], ($faLg ?? true) ? ['fa-lg'] : []),
                ]); ?>></i>
            </a>
        </li>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.social.twitter.url'))) { ?>
        <li class="list-inline-item">
            <a class="d-inline-block text-reset" data-bs-toggle="tooltip" title="X / Twitter" target="_blank"<?php echo $this->escapeAttr([
                'href' => $value,
                'data-bs-placement' => $tooltipPlacement ?? false,
            ]); ?>>
                <i<?php echo $this->escapeAttr([
                    'class' => array_merge(['fab', 'fa-square-x-twitter', 'fa-fw'], ($faLg ?? true) ? ['fa-lg'] : []),
                ]); ?>></i>
            </a>
        </li>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.social.linkedin.url'))) { ?>
        <li class="list-inline-item">
            <a class="d-inline-block text-reset" data-bs-toggle="tooltip" title="Linkedin" target="_blank"<?php echo $this->escapeAttr([
                'href' => $value,
                'data-bs-placement' => $tooltipPlacement ?? false,
            ]); ?>>
                <i<?php echo $this->escapeAttr([
                    'class' => array_merge(['fab', 'fa-linkedin', 'fa-fw'], ($faLg ?? true) ? ['fa-lg'] : []),
                ]); ?>></i>
            </a>
        </li>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.social.instagram.url'))) { ?>
        <li class="list-inline-item">
            <a class="d-inline-block text-reset" data-bs-toggle="tooltip" title="Instagram" target="_blank"<?php echo $this->escapeAttr([
                'href' => $value,
                'data-bs-placement' => $tooltipPlacement ?? false,
            ]); ?>>
                <i<?php echo $this->escapeAttr([
                    'class' => array_merge(['fab', 'fa-instagram', 'fa-fw'], ($faLg ?? true) ? ['fa-lg'] : []),
                ]); ?>></i>
            </a>
        </li>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.social.pinterest.url'))) { ?>
        <li class="list-inline-item">
            <a class="d-inline-block text-reset" data-bs-toggle="tooltip" title="Pinterest" target="_blank"<?php echo $this->escapeAttr([
                'href' => $value,
                'data-bs-placement' => $tooltipPlacement ?? false,
            ]); ?>>
                <i<?php echo $this->escapeAttr([
                    'class' => array_merge(['fab', 'fa-pinterest', 'fa-fw'], ($faLg ?? true) ? ['fa-lg'] : []),
                ]); ?>></i>
            </a>
        </li>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.social.youtube.url'))) { ?>
        <li class="list-inline-item">
            <a class="d-inline-block text-reset" data-bs-toggle="tooltip" title="YouTube" target="_blank"<?php echo $this->escapeAttr([
                'href' => $value,
                'data-bs-placement' => $tooltipPlacement ?? false,
            ]); ?>>
                <i<?php echo $this->escapeAttr([
                    'class' => array_merge(['fab', 'fa-youtube-square', 'fa-fw'], ($faLg ?? true) ? ['fa-lg'] : []),
                ]); ?>></i>
            </a>
        </li>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.social.twitch.url'))) { ?>
        <li class="list-inline-item">
            <a class="d-inline-block text-reset" data-bs-toggle="tooltip" title="Twitch" target="_blank"<?php echo $this->escapeAttr([
                'href' => $value,
                'data-bs-placement' => $tooltipPlacement ?? false,
            ]); ?>>
                <i<?php echo $this->escapeAttr([
                    'class' => array_merge(['fab', 'fa-twitch', 'fa-fw'], ($faLg ?? true) ? ['fa-lg'] : []),
                ]); ?>></i>
            </a>
        </li>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.social.skype.url'))) { ?>
        <li class="list-inline-item">
            <a class="d-inline-block text-reset" data-bs-toggle="tooltip" title="Skype"<?php echo $this->escapeAttr([
                'href' => $value,
                'data-bs-placement' => $tooltipPlacement ?? false,
            ]); ?>>
                <i<?php echo $this->escapeAttr([
                    'class' => array_merge(['fab', 'fa-skype', 'fa-fw'], ($faLg ?? true) ? ['fa-lg'] : []),
                ]); ?>></i>
            </a>
        </li>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.social.whatsapp.url'))) { ?>
        <li class="list-inline-item">
            <a class="d-inline-block text-reset" data-bs-toggle="tooltip" title="WhatsApp"<?php echo $this->escapeAttr([
                'href' => $value,
                'data-bs-placement' => $tooltipPlacement ?? false,
            ]); ?>>
                <i<?php echo $this->escapeAttr([
                    'class' => array_merge(['fab', 'fa-whatsapp', 'fa-fw'], ($faLg ?? true) ? ['fa-lg'] : []),
                ]); ?>></i>
            </a>
        </li>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.social.telegram.url'))) { ?>
        <li class="list-inline-item">
            <a class="d-inline-block text-reset" data-bs-toggle="tooltip" title="Telegram"<?php echo $this->escapeAttr([
                'href' => $value,
                'data-bs-placement' => $tooltipPlacement ?? false,
            ]); ?>>
                <i<?php echo $this->escapeAttr([
                    'class' => array_merge(['fab', 'fa-telegram', 'fa-fw'], ($faLg ?? true) ? ['fa-lg'] : []),
                ]); ?>></i>
            </a>
        </li>
    <?php } ?>
<?php
$this->endSection('buffer');

if (!empty($buffer = trim((string) $this->getSection('buffer')))) {
    ?>
    <ul class="social d-flex list-inline mb-0">
        <?php echo $buffer; ?>
    </ul>
    <?php
}
