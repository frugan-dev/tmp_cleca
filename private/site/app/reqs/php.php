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
use Slim\Psr7\Response;

return static function (ContainerInterface $container) {
    if (!empty($container->get('config')['app.php.minVersion'])) {
        if (true === version_compare(PHP_VERSION, (string) $container->get('config')['app.php.minVersion'], '<')) {
            $response = new Response();

            $response
                ->withStatus(500)
                ->getBody()
                ->write(sprintf(
                    __('PHP %.1f+ required.'),
                    $container->get('config')['app.php.minVersion']
                ))
            ;

            return $response;
        }
    }
};
