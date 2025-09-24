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

$params = [
    'type' => 'select',
    'attr' => [
        'name' => $val,
        'id' => $val,
        'class' => ['form-select', 'form-select-sm', 'select-location'],
    ],
];

$routeParamsArr = $this->Mod->routeParamsArrWithoutPg;

if (($paramId = array_search($val, $this->Mod->routeParamsArr, true)) !== false) {
    if (isset($this->Mod->routeParamsArr[$paramId + 1])) {
        $params['value'] = $this->uri([
            'routeName' => $this->env.'.'.$this->controller.'.params',
            'data' => [
                'action' => $this->action,
                'params' => implode('/', [
                    $this->Mod->orderBy,
                    $this->Mod->orderDir,
                ] + $routeParamsArr),
            ],
        ]);

        unset($routeParamsArr[$paramId], $routeParamsArr[$paramId + 1]);
    }
}

$firstOptionKey = $this->uri([
    'routeName' => $this->env.'.'.$this->controller.'.params',
    'data' => [
        'action' => $this->action,
        'params' => implode('/', [
            $this->Mod->orderBy,
            $this->Mod->orderDir,
        ] + $routeParamsArr),
    ],
]);

$params['options'] = [];

$params['options'][$firstOptionKey] = '- '.__($val).' -';

$result = $this->container->get('Mod\Catmember\\'.ucfirst((string) $this->env))->getAll([
    'order' => (!empty($this->container->get('Mod\Catmember\\'.ucfirst((string) $this->env))->fields['hierarchy']) ? 'a.hierarchy ASC, ' : '').(!empty($this->container->get('Mod\Catmember\\'.ucfirst((string) $this->env))->fields['name']['multilang']) ? 'b' : 'a').'.name ASC',
]);

if ((is_countable($result) ? count($result) : 0) > 0) {
    foreach ($result as $row) {
        $routeParamsArr = $this->Mod->routeParamsArrWithoutPg;

        if (!isset($routeParamsArr[0])) {
            $routeParamsArr[0] = $this->Mod->orderBy;
        }

        if (!isset($routeParamsArr[1])) {
            $routeParamsArr[1] = $this->Mod->orderDir;
        }

        if (($paramId = array_search($val, $routeParamsArr, true)) !== false) {
            $routeParamsArr[$paramId] = $val;
            $routeParamsArr[$paramId + 1] = $row['id'];
        } else {
            $routeParamsArr[] = $val;
            $routeParamsArr[] = $row['id'];
        }

        $params['options'][$this->uri([
            'routeName' => $this->env.'.'.$this->controller.'.params',
            'data' => [
                'action' => $this->action,
                'params' => implode('/', $routeParamsArr),
            ],
        ])] = [
            'attr' => [
                'value' => $this->uri([
                    'routeName' => $this->env.'.'.$this->controller.'.params',
                    'data' => [
                        'action' => $this->action,
                        'params' => implode('/', $routeParamsArr),
                    ],
                ]),
            ],
            'value' => $this->escape()->html($this->helper->Nette()->Strings()->truncate($row['name'], 50)),
        ];
    }
}

echo $this->helper->Html()->getFormField($params);
