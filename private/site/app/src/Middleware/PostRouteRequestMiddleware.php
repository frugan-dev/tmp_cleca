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

use App\Helper\HelperInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;

/**
 * Middleware for request enhancement (after routing).
 * Updates the request in container with routing information when available.
 */
class PostRouteRequestMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected HelperInterface $helper,
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Update request in container with routing attributes (post-routing)
        // Check if routing information is available
        // https://github.com/slimphp/Slim/pull/2398/files/897958f4e6efb6d297b098ada9d9cdc01013fe92#r170448029
        if (!$this->helper->Env()->isCli()
            && $request->getAttribute(RouteContext::ROUTE_PARSER)
            && $request->getAttribute(RouteContext::ROUTING_RESULTS)) {
            // Update the container with the enriched request
            $this->container->set('request', $request);
        }

        return $handler->handle($request);
    }
}
