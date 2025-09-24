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

include _ROOT.'/app/view/default/base/'.basename(__FILE__);

$this->beginSection('content');
echo $this->getSection('content');

if ($this->rbac->isGranted($this->controller.'.api.upload')) {
    echo $this->render('form-delete', [
        'inputFileId' => 'data',
    ]);
}
$this->endSection();
