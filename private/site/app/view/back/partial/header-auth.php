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

if (!empty($this->config['theme.switcher'])) {
    ?>
    <header class="py-3">
        <div class="container">
            <div class="btn-group btn-group-sm" role="group"<?php echo $this->escapeAttr([
                'aria-label' => sprintf(__('Toggle theme (%1$s)'), $this->helper->Nette()->Strings()->firstUpper(__('light'))),
            ]); ?>>
                <a class="btn p-0" data-bs-theme-value="light" data-bs-toggle="tooltip" data-bs-placement="bottom"<?php echo $this->escapeAttr([
                    'title' => $this->helper->Nette()->Strings()->firstUpper(__('light')),
                ]); ?>>
                    <i class="fa-regular fa-sun fa-fw me-1"></i>
                </a>
                <a class="btn p-0" data-bs-theme-value="dark" data-bs-toggle="tooltip" data-bs-placement="bottom"<?php echo $this->escapeAttr([
                    'title' => $this->helper->Nette()->Strings()->firstUpper(__('dark')),
                ]); ?>>
                    <i class="fas fa-moon fa-fw me-1"></i>
                </a>
            </div>
        </div>
    </header>
<?php } ?>

<div class="wrapper d-flex align-items-center flex-grow-1">
    <div class="container h-100">
        <div class="col-sm-6 col-md-5 col-lg-4 col-xl-3 mx-auto d-flex flex-column justify-content-center h-100">

            <header class="mt-3 mb-5">
                <img class="img-fluid" alt=""<?php echo $this->escapeAttr([
                    'src' => !empty($this->config['credits.asset.url']) ? $this->config['credits.asset.url'].'/img/logo/3square/md.png' : $this->asset('asset/'.$this->env.'/img/logo/sm.png'),
                ]); ?>>
            </header>

            <section>
                <main<?php echo $this->escapeAttr($this->mainAttr ?? []); ?>>
                    <?php
                    if (!$this->hasSection('flash-alert')) {
                        $this->setSection('flash-alert', $this->render('flash-alert'));
                    }
echo $this->getSection('flash-alert');

if (!$this->hasSection('theme-switcher')) {
    $this->setSection('theme-switcher', $this->render('theme-switcher'));
}
echo $this->getSection('theme-switcher');
