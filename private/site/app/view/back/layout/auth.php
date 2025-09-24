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

$this->appendData([
    'bodyAttr' => [
        'class' => ['bg-body-secondary', 'text-center'],
    ],
]);

$this->setSection('header', $this->render('header-auth'));
$this->setSection('footer', $this->render('footer-auth'));

$this->beginSection('sse');
$this->endSection();

echo $this->render('master');
