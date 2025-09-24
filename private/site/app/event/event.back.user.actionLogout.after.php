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
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

return static function (ContainerInterface $container): void {
    $container->get(EventDispatcherInterface::class)->addListener(basename(__FILE__, '.php'), function (GenericEvent $event) use ($container): void {
        $env = 'back';
        $modName = 'user';

        $container->get(SessionInterface::class)->remove($env.'.userIds');
        $container->get(SessionInterface::class)->deleteFlash('alert', $env.'.'.$modName.'.checkSwitchUser');
    });
};
