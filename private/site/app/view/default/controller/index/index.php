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

if (!empty($this->widgets)) {
    ?>
    <div class="row">
        <?php
        foreach ($this->widgets as $n => $widget) {
            if (!empty($this->widgetData[$widget['controller']][$widget['action']]['Mod'])) {
                if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$widget['controller'].'/widget/'.$widget['action'].'.php')) {
                    include _ROOT.'/app/view/'.$this->env.'/controller/'.$widget['controller'].'/widget/'.$widget['action'].'.php';
                } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/base/widget/'.$widget['action'].'.php')) {
                    include _ROOT.'/app/view/'.$this->env.'/base/widget/'.$widget['action'].'.php';
                } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$widget['controller'].'/widget/'.$widget['action'].'.php')) {
                    include _ROOT.'/app/view/default/controller/'.$widget['controller'].'/widget/'.$widget['action'].'.php';
                } elseif (file_exists(_ROOT.'/app/view/default/base/widget/'.$widget['action'].'.php')) {
                    include _ROOT.'/app/view/default/base/widget/'.$widget['action'].'.php';
                }
            }
        } ?>
    </div>
<?php
}
$this->endSection();

$this->beginSection('breadcrumb');
$this->endSection();

$this->beginSection('back-button');
$this->endSection();
