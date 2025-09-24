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

use App\Helper\HelperInterface;
use App\Service\Route\RouteParsingInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

return static function (ContainerInterface $container): void {
    $container->get(EventDispatcherInterface::class)->addListener(basename(__FILE__, '.php'), function (GenericEvent $event) use ($container): void {
        $env = 'front';
        $modName = 'catform';

        $helper = $container->get(HelperInterface::class);

        if (!isset($helper->Url()::$params['data'][$modName.'_id'])) {
            if ($container->has(RouteParsingInterface::class)) {
                $routeParsingService = $container->get(RouteParsingInterface::class);

                if ($container->has('error'.ucfirst($modName).'Id') || empty(${$modName.'Id'} = $routeParsingService->getNumericId(null, $modName.'_id'))) {
                    ${$modName.'Id'} = 0;
                }

                $helper->Url()::$params['data'][$modName.'_id'] = ${$modName.'Id'};

                if (!empty(${$modName.'Id'}) && in_array($helper->Url()::$params['routeName'], [$env.'.index', $env.'.index.lang'], true)) {
                    $helper->Url()::$params['routeName'] = $env.'.'.$modName.'.params';
                    $helper->Url()::$params['data']['action'] = 'view';
                    $helper->Url()::$params['data']['params'] = ${$modName.'Id'};
                }
            }
        }
    });
};
