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

<div class="hstack gap-1 flex-column flex-sm-row">
    <div class="fs-4 ff-2">
        <?php echo __('Share on'); ?>&hellip;
    </div>
    <div>
        <ul class="share list-inline mb-0">
            <li class="list-inline-item">
                <a class="share-facebook brand-facebook d-inline-block" data-bs-toggle="tooltip" href="javascript:;"<?php echo $this->escapeAttr([
                    'title' => sprintf(__('Share on %1$s'), 'Facebook'),
                ]); ?>>
                    <i class="fab fa-facebook-square fa-lg fa-fw"></i>
                </a>
            </li>
            <li class="list-inline-item">
                <a class="share-twitter brand-twitter d-inline-block" data-bs-toggle="tooltip" href="javascript:;"<?php echo $this->escapeAttr([
                    'title' => sprintf(__('Share on %1$s'), 'Twitter'),
                ]); ?>>
                    <i class="fab fa-twitter fa-lg fa-fw"></i>
                </a>
            </li>
            <li class="list-inline-item">
                <a class="share-linkedin brand-linkedin d-inline-block" data-bs-toggle="tooltip" href="javascript:;"<?php echo $this->escapeAttr([
                    'title' => sprintf(__('Share on %1$s'), 'Linkedin'),
                ]); ?>>
                    <i class="fab fa-linkedin fa-lg fa-fw"></i>
                </a>
            </li>
            <li class="list-inline-item">
                <a class="share-pinterest brand-pinterest d-inline-block" data-bs-toggle="tooltip" href="javascript:;"<?php echo $this->escapeAttr([
                    'title' => sprintf(__('Share on %1$s'), 'Pinterest'),
                ]); ?>>
                    <i class="fab fa-pinterest fa-lg fa-fw"></i>
                </a>
            </li>
            <li class="list-inline-item">
                <a class="share-whatsapp brand-whatsapp d-inline-block" data-bs-toggle="tooltip" href="javascript:;"<?php echo $this->escapeAttr([
                    'title' => sprintf(__('Share on %1$s'), 'WhatsApp'),
                ]); ?>>
                    <i class="fab fa-whatsapp fa-lg fa-fw"></i>
                </a>
            </li>
            <li class="list-inline-item">
                <a class="share-telegram brand-telegram d-inline-block" data-bs-toggle="tooltip" href="javascript:;"<?php echo $this->escapeAttr([
                    'title' => sprintf(__('Share on %1$s'), 'Telegram'),
                ]); ?>>
                    <i class="fab fa-telegram fa-lg fa-fw"></i>
                </a>
            </li>
        </ul>
    </div>
</div>

<?php
$this->scriptsFoot()->beginInternal();
echo '(() => {
    document.querySelector(".share-facebook").addEventListener("click", function() {
      VanillaSharing.fbButton({
        url: "'.$this->escape()->js($this->fullUrl).'",
      })
    })

    document.querySelector(".share-twitter").addEventListener("click", function() {
      VanillaSharing.tw({
        url: "'.$this->escape()->js($this->fullUrl).'",
        title: "'.$this->escape()->js($this->title).'",
        hashtags: ["'.$this->escape()->js($this->helper->Nette()->Strings()->webalize(settingOrConfig('company.name'))).'"],
      })
    })

    document.querySelector(".share-linkedin").addEventListener("click", function() {
      VanillaSharing.linkedin({
        url: "'.$this->escape()->js($this->fullUrl).'",
        title: "'.$this->escape()->js($this->title).'",
        description: "'.$this->escape()->js($this->metaDescription).'",
      })
    })

    document.querySelector(".share-pinterest").addEventListener("click", function() {
      VanillaSharing.pinterest({
        url: "'.$this->escape()->js($this->fullUrl).'",
        description: "'.$this->escape()->js($this->title).'",
        media: "'.$this->escape()->js($this->metaImage).'",
      })
    })

    document.querySelector(".share-whatsapp").addEventListener("click", function() {
      VanillaSharing.whatsapp({
        url: "'.$this->escape()->js($this->fullUrl).'",
        title: "'.$this->escape()->js($this->title).'",
      })
    })

    document.querySelector(".share-telegram").addEventListener("click", function() {
      VanillaSharing.telegram({
        url: "'.$this->escape()->js($this->fullUrl).'",
        title: "'.$this->escape()->js($this->title).'",
      })
    })
})();';
$this->scriptsFoot()->endInternal();
