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

use App\Factory\Session\SessionInterface;
use App\Helper\HelperInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

return static function (ContainerInterface $container): void {
    $container->get(EventDispatcherInterface::class)->addListener(basename(__FILE__, '.php'), function (GenericEvent $event) use ($container): void {
        $env = 'front';

        if (empty($container->get(SessionInterface::class)->get($env.'.redirectAfterLogin'))) {
            if (empty($event['result']->getIdentity()['country_id'])) {
                $container->get(SessionInterface::class)->set($env.'.redirectAfterLogin', $container->get(HelperInterface::class)->Url()->urlFor([
                    'routeName' => $env.'.member.params',
                    'data' => [
                        'action' => 'edit',
                        'params' => $event['result']->getIdentity()['id'],
                        'catform_id' => 0,
                    ],
                ]));
            } elseif (!empty($event['result']->getIdentity()['catmember_main'])) {
                $container->get(SessionInterface::class)->set($env.'.redirectAfterLogin', $container->get(HelperInterface::class)->Url()->urlFor([
                    'routeName' => $env.'.formvalue',
                    'data' => [
                        'action' => 'index',
                        'catform_id' => 0,
                    ],
                ]));
            }
        }
    });
};
