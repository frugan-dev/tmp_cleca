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

namespace App\Middleware\Env\Back;

use Laminas\Authentication\Storage\Session;
use Psr\Http\Server\MiddlewareInterface;

class AuthMiddleware extends \App\Middleware\AuthMiddleware implements MiddlewareInterface
{
    public static string $env = 'back';

    public static string $adapter = 'Env\Back\AuthAdapter';

    public static array $identityTypes = ['user'];

    // https://docs.laminas.dev/laminas-authentication/storage/
    // ...it will also populate all storage adapters with higher priority with the contents
    public static array $storages = [
        Session::class => [
            [
                null, // namespace
                'back', // member
                null, // manager
            ],
            [
                null, // namespace
                'front', // member
                null, // manager
            ],
        ],
    ];
}
