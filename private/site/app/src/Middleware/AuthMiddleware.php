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

namespace App\Middleware;

use App\Factory\Auth\AuthInterface;
use App\Model\Model;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware extends Model implements MiddlewareInterface
{
    public static string $env = 'default';

    public static string $adapter;

    public static array $storages = [];

    public static array $identityTypes = [];

    public function __construct(
        protected ContainerInterface $container,
        protected AuthInterface $auth
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (method_exists($this, '_'.__FUNCTION__.'BeforeCreate') && \is_callable([$this, '_'.__FUNCTION__.'BeforeCreate'])) {
            $request = \call_user_func_array([$this, '_'.__FUNCTION__.'BeforeCreate'], [$request, $handler]);
        }

        $this->auth->create($this->container->get(static::$adapter), static::$storages, static::$identityTypes);

        if (method_exists($this, '_'.__FUNCTION__.'AfterCreate') && \is_callable([$this, '_'.__FUNCTION__.'AfterCreate'])) {
            $request = \call_user_func_array([$this, '_'.__FUNCTION__.'AfterCreate'], [$request, $handler]);
        }

        if ($this->auth->hasIdentity()) {
            if (\in_array($this->auth->getIdentity()['_type'] ?? null, static::$identityTypes, true)) {
                $request = $request->withAttribute('hasIdentity', $this->auth->hasIdentity());

                if (!empty($timeZone = $this->auth->getIdentity()['timezone'] ?? null)) {
                    // https://stackoverflow.com/a/37722990/3929620
                    date_default_timezone_set($timeZone);
                }

                if (method_exists($this, '_'.__FUNCTION__.'AfterIdentity') && \is_callable([$this, '_'.__FUNCTION__.'AfterIdentity'])) {
                    $request = \call_user_func_array([$this, '_'.__FUNCTION__.'AfterIdentity'], [$request, $handler]);
                }
            }
        }

        return $handler->handle($request);
    }
}
