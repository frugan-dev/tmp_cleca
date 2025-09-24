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

// https://stackoverflow.com/a/20123337/3929620
// https://stackoverflow.com/a/22499259/3929620
// https://stackoverflow.com/a/40682033/3929620
return static function (ContainerInterface $container) {
    $func = basename(__FILE__, '.php');

    if (!function_exists($func)) {
        $response = new Response();

        $response
            ->withStatus(500)
            ->getBody()
            ->write(sprintf(
                __('Missing %1$s function.'),
                $func
            ))
        ;

        return $response;
    }
};
