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

use App\Model\Model;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class GoogleAnalyticsMiddleware extends Model implements MiddlewareInterface
{
    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('loadGA', $this->loadGA());

        return $handler->handle($request);
    }

    public function loadGA()
    {
        $env = $this->container->get('env');

        if (!$this->config->get('debug.enabled') && !empty($this->config->getRepository()->getWithFallback([
            "service.{$env}",
            'service',
        ], 'google.analytics.code'))) {
            $htaccess = _PUBLIC.'/.htaccess';

            if (file_exists($htaccess)) {
                $htaccessContent = \Safe\file_get_contents($htaccess);

                if (\Safe\preg_match_all('/^(\s*)ModPagespeed(\s+)off/m', $htaccessContent)
                    || !\Safe\preg_match_all('/^(\s*)ModPagespeedAnalyticsID/m', $htaccessContent)) {
                    return true;
                }
            // maybe you are using nginx?
            } else {
                return true;
            }
        }

        return false;
    }
}
