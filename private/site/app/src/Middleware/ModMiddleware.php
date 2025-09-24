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
use App\Service\Route\RouteParsingService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ModMiddleware extends Model implements MiddlewareInterface
{
    public static string $env = 'default';

    public function __construct(
        protected ContainerInterface $container,
        protected RouteParsingService $routeParsingService,
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Since we're running BEFORE routing, we need to analyze the request URI
        // to determine if modules should be loaded
        if ($this->shouldLoadModules($request)) {
            $this->loadMods();
        }

        return $handler->handle($request);
    }

    public function loadMods(): void
    {
        foreach ($this->container->get('mods') as $controller) {
            if ($this->container->has('Mod\\'.$controller.'\\'.ucfirst((string) static::$env))) {
                $this->container->get('Mod\\'.$controller.'\\'.ucfirst((string) static::$env));
            }
        }
    }

    /**
     * Determine if modules should be loaded based on request URI patterns.
     * This runs before routing, so we analyze the raw URI.
     */
    protected function shouldLoadModules(ServerRequestInterface $request): bool
    {
        // Load for main frontend pages (index routes)
        if ($this->isIndexRoute($request)) {
            return true;
        }

        // Load for specific actions that need all modules
        if ($this->isDeleteAction($request)) {
            return true;
        }

        return false;
    }

    /**
     * Check if this is an index route (home page, language index, etc.).
     */
    protected function isIndexRoute(ServerRequestInterface $request): bool
    {
        // First try to use RouteParsingService for proper route matching
        if ($this->routeParsingService->isIndexRoute($request)) {
            return true;
        }

        // Fallback to URI analysis for cases where route matching fails
        $uri = $request->getUri()->getPath();
        $config = $this->container->get('config');
        $extension = $config->get('url.extension', '');

        // Root path
        if ('/' === $uri || '' === $uri) {
            return true;
        }

        // Language index: /en, /it, etc.
        if (\Safe\preg_match('/^\/[a-z]{2}'.preg_quote((string) $extension, '/').'$/', $uri)) {
            return true;
        }

        // Language index with trailing slash
        if (\Safe\preg_match('/^\/[a-z]{2}\/$/', $uri)) {
            return true;
        }

        return false;
    }

    /**
     * Check if this is a delete action that needs all modules loaded.
     */
    protected function isDeleteAction(ServerRequestInterface $request): bool
    {
        // First try to get action from route parsing
        $action = $this->routeParsingService->getAction($request);
        if ($action && \in_array($action, ['delete', 'delete-bulk'], true)) {
            return true;
        }

        // Fallback to URI analysis
        $uri = $request->getUri()->getPath();

        return (bool) \Safe\preg_match('/\/(delete|delete-bulk)(?:\/|$)/', $uri);
    }
}
