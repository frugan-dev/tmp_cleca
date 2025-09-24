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
<div class="wrapper flex-grow-1">
    <header class="bg-primary bg-gradient bg-opacity-25 py-3">
        <div class="container">
            <?php
            if (!$this->hasSection('nav-top')) {
                $this->setSection('nav-top', $this->render('nav-top'));
            }
echo $this->getSection('nav-top');
?>
        </div>
    </header>

    <section>
        <div class="container my-3">
            <div class="row">
                <?php
                if (!$this->hasSection('nav-aside')) {
                    $this->setSection('nav-aside', $this->render('nav-aside'));
                }
?>
                <div<?php echo $this->escapeAttr([
                    'class' => array_merge(['order-md-last'], !empty($this->getSection('nav-aside')) ? ['col-md-8', 'col-lg-9'] : ['col']),
                ]); ?>>
                    <main<?php echo $this->escapeAttr($this->mainAttr ?? []); ?>>
                        <?php
            if (!$this->hasSection('breadcrumb')) {
                $this->setSection('breadcrumb', $this->render('breadcrumb'));
            }
echo $this->getSection('breadcrumb');

if (!$this->hasSection('section-header')) {
    $this->setSection('section-header', $this->render('section-header'));
}
echo $this->getSection('section-header');

if (!$this->hasSection('flash-alert')) {
    $this->setSection('flash-alert', $this->render('flash-alert'));
}
echo $this->getSection('flash-alert');
