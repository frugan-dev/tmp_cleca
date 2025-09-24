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

use Laminas\Stdlib\ArrayUtils;

$params = [
    'type' => 'input',
    'attr' => [
        // TODO - datetime-local
        // https://stackoverflow.com/a/52616194
        'type' => 'text',
        'name' => $origKey,
        'class' => ['form-control'],
        'form' => $this->controller.'-form-filter', // https://stackoverflow.com/a/21900324/3929620
    ],
];

if (isset($this->Mod->filterData[$origKey]['value'])) {
    $params['attr']['value'] = $this->Mod->filterData[$origKey]['value'];
} else {
    $params['attr']['value'] = '';
}

$params = ArrayUtils::merge($val[$this->env], $params);

echo $this->helper->Html()->getFormField($params);
