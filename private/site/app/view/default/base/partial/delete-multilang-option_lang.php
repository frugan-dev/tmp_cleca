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

foreach ([
    _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/partial/type_multilang/'.$this->Mod->type,
    _ROOT.'/app/view/'.$this->env.'/base/partial/type_multilang/'.$this->Mod->type,
    _ROOT.'/app/view/default/controller/'.$this->controller.'/partial/type_multilang/'.$this->Mod->type,
    _ROOT.'/app/view/default/base/partial/type_multilang/'.$this->Mod->type,
] as $dir) {
    if (is_dir($dir)) {
        // in() searches only the current directory, while from() searches its subdirectories too (recursively)
        foreach ($this->helper->Nette()->Finder()->findFiles($this->action.'-*.php')->in($dir)->sortByName() as $fileObj) {
            include $fileObj->getPathname();
        }

        break;
    }
}
