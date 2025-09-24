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

$this->beginSection('content');
echo $this->render('main-catform');
$this->endSection();

$this->beginSection('breadcrumb');
$this->endSection();

$this->beginSection('section-header');
$this->endSection();

$this->beginSection('section-footer');
$this->endSection();

$this->beginSection('nav-aside');
$this->endSection();
