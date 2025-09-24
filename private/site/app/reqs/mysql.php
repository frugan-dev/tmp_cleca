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
    if (!empty($container->get('config')['app.minMysqlVersion'])) {
        // https://stackoverflow.com/a/66511987/3929620
        // https://github.com/SimpleMachines/SMF/issues/7070
        if (true === version_compare(mysqli_get_client_info(), (string) $container->get('config')['app.minMysqlVersion'], '<')) {
            $response = new Response();

            $response
                ->withStatus(500)
                ->getBody()
                ->write(sprintf(
                    __('MySQL %.1f+ required.'),
                    $container->get('config')['app.minMysqlVersion']
                ))
            ;

            return $response;
        }
    }
};
