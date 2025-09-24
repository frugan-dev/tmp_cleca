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
<hr>

<h3 class="mb-4">
    <?php echo $this->escape()->html(__('Dependencies')); ?>
</h3>

<div class="row">
<?php
$i = 1;

foreach ($this->Mod->errorDeps as $controller => $rows) {
    $Mod = $this->container->get('Mod\\'.ucfirst((string) $controller).'\\'.ucfirst((string) $this->env));
    $file = 'panel-mod';

    if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$controller.'/partial/'.$file.'.php')) {
        include _ROOT.'/app/view/'.$this->env.'/controller/'.$controller.'/partial/'.$file.'.php';
    } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/partial/'.$file.'.php')) {
        include _ROOT.'/app/view/'.$this->env.'/partial/'.$file.'.php';
    } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$controller.'/partial/'.$file.'.php')) {
        include _ROOT.'/app/view/default/controller/'.$controller.'/partial/'.$file.'.php';
    } elseif (file_exists(_ROOT.'/app/view/default/partial/'.$file.'.php')) {
        include _ROOT.'/app/view/default/partial/'.$file.'.php';
    }
}
?>
</div>
