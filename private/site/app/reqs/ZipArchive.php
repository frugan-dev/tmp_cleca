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
    $class = basename(__FILE__, '.php');

    if (!class_exists($class)) {
        $response = new Response();

        $response
            ->withStatus(500)
            ->getBody()
            ->write(sprintf(
                __('Missing %1$s class.'),
                $class
            ))
        ;

        return $response;
    }
};
