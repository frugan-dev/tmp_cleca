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

namespace App\Middleware\Env\Front;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ModMiddleware extends \App\Middleware\ModMiddleware implements MiddlewareInterface
{
    public static string $env = 'front';

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->container->has('Mod\Setting\\'.ucfirst(static::$env))) {
            if (method_exists($this->container->get('Mod\Setting\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global') && \is_callable([$this->container->get('Mod\Setting\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global'])) {
                $request = \call_user_func_array([$this->container->get('Mod\Setting\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global'], [$request, $handler]);
            }
        }

        if ($this->container->has('Mod\Catform\\'.ucfirst(static::$env))) {
            if (method_exists($this->container->get('Mod\Catform\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global') && \is_callable([$this->container->get('Mod\Catform\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global'])) {
                $request = \call_user_func_array([$this->container->get('Mod\Catform\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global'], [$request, $handler]);
            }
        }

        if ($this->container->has('Mod\Page\\'.ucfirst(static::$env))) {
            if (method_exists($this->container->get('Mod\Page\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global') && \is_callable([$this->container->get('Mod\Page\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global'])) {
                $request = \call_user_func_array([$this->container->get('Mod\Page\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global'], [$request, $handler]);
            }
        }

        if ($this->container->has('Mod\Formvalue\\'.ucfirst(static::$env))) {
            if (method_exists($this->container->get('Mod\Formvalue\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global') && \is_callable([$this->container->get('Mod\Formvalue\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global'])) {
                $request = \call_user_func_array([$this->container->get('Mod\Formvalue\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global'], [$request, $handler]);
            }
        }

        if ($this->container->has('Mod\Form\\'.ucfirst(static::$env))) {
            if (method_exists($this->container->get('Mod\Form\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global') && \is_callable([$this->container->get('Mod\Form\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global'])) {
                $request = \call_user_func_array([$this->container->get('Mod\Form\\'.ucfirst(static::$env)), '_'.__FUNCTION__.'Global'], [$request, $handler]);
            }
        }

        $response = $handler->handle($request);

        if ($this->container->has('Mod\Member\\'.ucfirst(static::$env))) {
            $this->container->get('Mod\Member\\'.ucfirst(static::$env))->checkConfirmed();
            $this->container->get('Mod\Member\\'.ucfirst(static::$env))->checkProfile();
        }

        return $response;
    }
}
