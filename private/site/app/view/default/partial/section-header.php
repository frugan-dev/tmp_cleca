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
<div class="row align-items-center justify-content-end">
    <?php
    $this->beginSection('controls');

if (!empty($this->Mod->controls)) {
    foreach ($this->Mod->controls as $key => $val) {
        if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/control/'.$this->action.'-'.$val.'.php')) {
            include _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/control/'.$this->action.'-'.$val.'.php';
        } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/base/control/'.$this->action.'-'.$val.'.php')) {
            include _ROOT.'/app/view/'.$this->env.'/base/control/'.$this->action.'-'.$val.'.php';
        } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$this->controller.'/control/'.$this->action.'-'.$val.'.php')) {
            include _ROOT.'/app/view/default/controller/'.$this->controller.'/control/'.$this->action.'-'.$val.'.php';
        } elseif (file_exists(_ROOT.'/app/view/default/base/control/'.$this->action.'-'.$val.'.php')) {
            include _ROOT.'/app/view/default/base/control/'.$this->action.'-'.$val.'.php';
        }
    }
}
$this->endSection('controls');

$controls = $this->getSection('controls');
?>

    <div class="col-md">
    <?php
if (!$this->hasSection('title')) {
    $this->setSection('title', $this->render('title'));
}
echo $this->getSection('title');
?>
    </div>

    <?php if (!empty($controls)) { ?>
        <div class="col-md-auto d-grid d-sm-flex justify-content-sm-end gap-2">
            <?php echo $controls; ?>
        </div>
    <?php } ?>
</div>

<hr>
