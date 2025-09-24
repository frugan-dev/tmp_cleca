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

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

return static function (ContainerInterface $container): void {
    $container->get(EventDispatcherInterface::class)->addListener(basename(__FILE__, '.php'), function (GenericEvent $eventMain) use ($container): void {
        $env = 'back';
        if ($container->has('Mod\Form\\'.ucfirst($env))) {
            $ModForm = $container->get('Mod\Form\\'.ucfirst($env));

            if (!empty($eventMain['row']['cat'.$ModForm->modName.'_id']) && !empty($eventMain['row'][$ModForm->modName.'_ids'])) {
                $formIds = explode(',', (string) $eventMain['row'][$ModForm->modName.'_ids']);
                $container->get('filterValue')->sanitize($formIds, 'intvalArray');

                if (!$container->has('cat'.$ModForm->modName.'Id'.$eventMain['row']['cat'.$ModForm->modName.'_id'].'LastFormId')) {
                    $eventName = 'event.'.$ModForm::$env.'.'.$ModForm->modName.'.getAll.where';
                    $callback = function (GenericEvent $event) use ($eventMain, $ModForm): void {
                        $ModForm->dbData['sql'] .= ' AND a.cat'.$ModForm->modName.'_id = :cat'.$ModForm->modName.'_id';
                        $ModForm->dbData['args']['cat'.$ModForm->modName.'_id'] = $eventMain['row']['cat'.$ModForm->modName.'_id'];
                    };

                    $container->get(EventDispatcherInterface::class)->addListener($eventName, $callback);

                    // https://stackoverflow.com/a/7604926/3929620
                    $result = $ModForm->getAll([
                        'order' => 'a.hierarchy DESC',
                        'nRows' => 1,
                        'active' => true,
                    ]);

                    $container->get(EventDispatcherInterface::class)->removeListener($eventName, $callback);

                    if ((is_countable($result) ? count($result) : 0) > 0) {
                        $container->set('cat'.$ModForm->modName.'Id'.$eventMain['row']['cat'.$ModForm->modName.'_id'].'LastFormId', $result[0]['id']);
                    }
                }

                if ($container->has('cat'.$ModForm->modName.'Id'.$eventMain['row']['cat'.$ModForm->modName.'_id'].'LastFormId')) {
                    if (in_array($container->get('cat'.$ModForm->modName.'Id'.$eventMain['row']['cat'.$ModForm->modName.'_id'].'LastFormId'), $formIds, true)) {
                        $container->get('view')->getData()->trAttr['class'][] = 'table-success';
                    } else {
                        $container->get('view')->getData()->trAttr['class'][] = 'table-warning';
                    }
                }
            }
        }
    });
};
