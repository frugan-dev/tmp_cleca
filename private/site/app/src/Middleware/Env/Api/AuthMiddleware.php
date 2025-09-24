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

namespace App\Middleware\Env\Api;

use Laminas\Authentication\Storage\NonPersistent;
use Laminas\Authentication\Storage\Session;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware extends \App\Middleware\AuthMiddleware implements MiddlewareInterface
{
    public static string $env = 'api';

    public static string $adapter = 'Env\Api\AuthAdapter';

    public static array $identityTypes = ['member', 'user'];

    // https://docs.laminas.dev/laminas-authentication/storage/
    // ...it will also populate all storage adapters with higher priority with the contents
    public static array $storages = [
        NonPersistent::class => [],
    ];

    protected function _processBeforeCreate(ServerRequestInterface $request, RequestHandlerInterface $handler): ServerRequestInterface
    {
        if (empty($request->getHeaderLine($this->config['api.headers.key']))) {
            self::$storages[Session::class] = [
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
            ];
        }

        return $request;
    }

    protected function _processAfterCreate(ServerRequestInterface $request, RequestHandlerInterface $handler): ServerRequestInterface
    {
        if (!empty($apiKey = $request->getHeaderLine($this->config['api.headers.key']))) {
            $this->auth->authenticate($apiKey, '');
        }

        return $request;
    }

    protected function _processAfterIdentity(ServerRequestInterface $request, RequestHandlerInterface $handler): ServerRequestInterface
    {
        if (!empty($request->getHeaderLine($this->config['api.headers.key']))) {
            if (!empty(static::$identityTypes)) {
                foreach (static::$identityTypes as $identityType) {
                    $namespace = 'Mod\\'.ucfirst((string) $identityType).'\\'.ucfirst((string) static::$env);

                    if ($this->container->has($namespace)) {
                        $Mod = $this->container->get($namespace);

                        // reload default fields by rbac
                        $Mod->reInit();
                    }
                }
            }
        }

        return $request;
    }
}
