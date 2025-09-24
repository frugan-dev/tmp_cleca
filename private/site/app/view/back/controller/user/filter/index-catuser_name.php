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
    'type' => 'select',
    'attr' => [
        'name' => $origKey,
        'class' => 'form-select', // <-- no array
        'form' => $this->controller.'-form-filter', // https://stackoverflow.com/a/21900324/3929620
    ],
    'options' => [
        '' => '',
    ],
];

if (isset($this->Mod->filterData[$origKey]['value'])) {
    $params['value'] = $this->Mod->filterData[$origKey]['value'];
} else {
    $params['value'] = '';
}

$this->container->get('Mod\Catuser\\'.ucfirst((string) $this->env))->removeAllListeners();

$result = $this->container->get('Mod\Catuser\\'.ucfirst((string) $this->env))->getAll([
    'order' => (!empty($this->container->get('Mod\Catuser\\'.ucfirst((string) $this->env))->fields['name']['multilang']) ? 'b' : 'a').'.name ASC',
]);

$this->container->get('Mod\Catuser\\'.ucfirst((string) $this->env))->addAllListeners();

if ((is_countable($result) ? count($result) : 0) > 0) {
    foreach ($result as $row) {
        $params['options'][$row['id']] = $this->escape()->html($row['name']);
    }
}

$params = ArrayUtils::merge($val[$this->env], $params);

echo $this->helper->Html()->getFormField($params);
