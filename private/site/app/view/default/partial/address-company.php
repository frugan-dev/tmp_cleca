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

<?php if (!empty($name ?? true)) { ?>
    <<?php echo !empty($titleTag) ? $titleTag : 'h6'; ?> class="fw-bold border-bottom border-dark-subtle d-inline-block pb-2 pe-2 mb-3">
        <?php if (!empty($value = settingOrConfig(['brand.name', 'company.name']))) { ?>
            <?php echo $this->escape()->html($value); ?>
        <?php } ?>

        <?php if (!empty($value = settingOrConfig(['brand.subName', 'company.subName']))) { ?>
            <div class="small fw-normal">
                <?php echo $this->escape()->html($value); ?>
            </div>
        <?php } ?>
    </<?php echo !empty($titleTag) ? $titleTag : 'h6'; ?>>
<?php } ?>

<?php // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/address?>
<address>
    <?php if (!empty($value = settingOrConfig('company.address'))) { ?>
        <div class="d-flex">
            <div class="flex-shrink-0 me-3">
                <i class="fas fa-map-marker-alt fa-fw"></i>
            </div>
            <div class="flex-grow-1">
                <?php echo nl2br((string) $value); ?>
            </div>
        </div>
    <?php } ?>

    <?php if (empty($short ?? false)) { ?>
    <div class="d-flex">
        <div class="flex-shrink-0 me-3">
            <i class="fas fa-id-card fa-fw"></i>
        </div>
        <div class="flex-grow-1">
            <?php if (!empty($value = settingOrConfig('company.vat'))) { ?>
                <div>
                    <?php echo $this->escape()->html(__('VAT')); ?>: <?php echo $this->escape()->html($value); ?>
                </div>
            <?php } ?>
            <?php if (!empty($value = settingOrConfig('company.nin'))) { ?>
                <div>
                    <?php echo $this->escape()->html(__('NIN')); ?>: <?php echo $this->escape()->html($value); ?>
                </div>
            <?php } ?>
            <?php if (!empty($value = settingOrConfig('company.reg'))) { ?>
                <div>
                    <?php echo $this->escape()->html(__('Reg. n.')); ?>: <?php echo $this->escape()->html($value); ?>
                </div>
            <?php } ?>
            <?php if (!empty($value = settingOrConfig('company.rea'))) { ?>
                <div>
                    <?php echo $this->escape()->html(__('REA')); ?>: <?php echo $this->escape()->html($value); ?>
                </div>
            <?php } ?>
            <?php if (!empty($value = settingOrConfig('company.capSoc'))) { ?>
                <div>
                    <?php echo $this->escape()->html(__('Social cap.')); ?>: <?php echo $this->escape()->html($value); ?>
                </div>
            <?php } ?>
        </div>
    </div>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.tel1'))) { ?>
        <div class="d-flex">
            <div class="flex-shrink-0 me-3">
                <i class="fas fa-phone-alt fa-fw"></i>
            </div>
            <div class="flex-grow-1">
                <?php echo $this->escape()->html(__('Tel')); ?>: <a<?php echo $this->escapeAttr([
                    'href' => 'tel:'.$this->helper->Strings()->phoneUri($value),
                    'class' => !empty($dark) ? false : ['text-underline-hover'],
                ]); ?>><?php echo $this->escape()->html($value); ?></a>
            </div>
        </div>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.mobile1'))) { ?>
        <div class="d-flex">
            <div class="flex-shrink-0 me-3">
                <i class="fas fa-mobile-screen fa-fw"></i>
            </div>
            <div class="flex-grow-1">
                <?php echo $this->escape()->html(__('Mobile')); ?>: <a<?php echo $this->escapeAttr([
                    'href' => 'tel:'.$this->helper->Strings()->phoneUri($value),
                    'class' => !empty($dark) ? false : ['text-underline-hover'],
                ]); ?>><?php echo $this->escape()->html($value); ?></a>
            </div>
        </div>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.fax'))) { ?>
        <div class="d-flex">
            <div class="flex-shrink-0 me-3">
                <i class="fas fa-fax fa-fw"></i>
            </div>
            <div class="flex-grow-1">
                <?php echo $this->escape()->html(__('Fax')); ?>: <a<?php echo $this->escapeAttr([
                    'href' => 'tel:'.$this->helper->Strings()->phoneUri($value),
                    'class' => !empty($dark) ? false : ['text-underline-hover'],
                ]); ?>><?php echo $this->escape()->html($value); ?></a>
            </div>
        </div>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.email'))) { ?>
        <div class="d-flex">
            <div class="flex-shrink-0 me-3">
                <i class="fas fa-envelope fa-fw"></i>
            </div>
            <div class="flex-grow-1">
                <?php echo $this->escape()->html(__('Email')); ?>: <?php echo $this->obfuscate('<a'.$this->escapeAttr([
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
                    'class' => !empty($dark) ? false : ['text-underline-hover'],
                ]).'>'.$this->escape()->html($value).'</a>', $obfuscate ?? null); ?>
            </div>
        </div>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.pec'))) { ?>
        <div class="d-flex">
            <div class="flex-shrink-0 me-3">
                <i class="fas fa-envelope-circle-check fa-fw"></i>
            </div>
            <div class="flex-grow-1">
                <?php echo $this->escape()->html(__('PEC')); ?>: <a<?php echo $this->escapeAttr([
                    'href' => 'mailto:'.$value,
                    'class' => !empty($dark) ? false : ['text-underline-hover'],
                ]); ?>><?php echo $this->escape()->html($value); ?></a>
            </div>
        </div>
    <?php } ?>

    <?php if (!empty($value = settingOrConfig('company.website'))) { ?>
        <div class="d-flex">
            <div class="flex-shrink-0 me-3">
                <i class="fas fa-link fa-fw"></i>
            </div>
            <div class="flex-grow-1">
                <?php echo $this->escape()->html(__('Website')); ?>: <a target="_blank"<?php echo $this->escapeAttr([
                    'href' => $value,
                    'class' => !empty($dark) ? false : ['text-underline-hover'],
                ]); ?>><?php echo $this->escape()->html($value); ?></a>
            </div>
        </div>
    <?php } ?>
</address>
