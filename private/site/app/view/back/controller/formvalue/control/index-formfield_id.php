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

use Symfony\Component\EventDispatcher\GenericEvent;

$Mod = $this->container->get('Mod\Formfield\\'.ucfirst((string) $this->env));

$params = [
    'type' => 'select',
    'attr' => [
        'name' => $val,
        'id' => $val,
        'class' => ['form-select', 'form-select-sm', 'select-location'],
    ],
];

$routeParamsArr = $this->Mod->routeParamsArrWithoutPg;

if (($paramId = array_search($val, $routeParamsArr, true)) !== false) {
    if (isset($routeParamsArr[$paramId + 1])) {
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

if (($paramId = array_search('form_id', $routeParamsArr, true)) !== false) {
    if (isset($routeParamsArr[$paramId + 1])) {
        $formId = $routeParamsArr[$paramId + 1];
    }
}

$params['options'] = [];

$params['options'][$firstOptionKey] = '- '.__($val).' -';

if (!empty($formId)) {
    $eventName = 'event.'.$this->env.'.'.$Mod->modName.'.getAll.where';
    $callback = function (GenericEvent $event) use ($Mod, $formId): void {
        $Mod->dbData['sql'] .= ' AND a.form_id = :form_id';
        $Mod->dbData['sql'] .= ' AND a.type NOT LIKE :like_type';
        $Mod->dbData['args']['form_id'] = (int) $formId;
        $Mod->dbData['args']['like_type'] = 'block_%';
    };

    $this->dispatcher->addListener($eventName, $callback);

    $result = $Mod->getAll([
        'order' => (!empty($Mod->fields['hierarchy']) ? 'a.hierarchy ASC, ' : '').(!empty($Mod->fields['name']['multilang']) ? 'b' : 'a').'.name ASC',
    ]);

    $this->dispatcher->removeListener($eventName, $callback);

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

            if (method_exists($Mod, 'getFieldTypes') && is_callable([$Mod, 'getFieldTypes'])) {
                $type = $Mod->getFieldTypes()[$row['type']] ?? $row['type'];
            } else {
                $type = $row['type'];
            }

            $nameRichtext = $this->helper->Nette()->Strings()->truncate(trim(strip_tags((string) ($row['name'] ?? '').' '.($row['richtext'] ?? ''))), 30);

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
                'value' => $this->escape()->html($this->helper->Nette()->Strings()->truncate($row['id'].' - '.(!empty($nameRichtext) ? $nameRichtext : '').' ('.$type.')', 50)),
            ];
        }
    }
} else {
    $params['attr']['disabled'] = true;
}

echo $this->helper->Html()->getFormField($params);
