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

namespace App\Service\Route;

use FastRoute\Dispatcher;
use FastRoute\RouteCollector;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\App;
use Slim\Interfaces\RouteCollectorInterface;

/**
 * Service for parsing and matching routes before the routing middleware is executed.
 * Uses FastRoute dispatcher to parse route patterns manually.
 * Extends AbstractRouteParsingService for common functionality.
 */
class PreRouteParsingService extends AbstractRouteParsingService
{
    private ?Dispatcher $dispatcher = null;
    private array $routeMap = [];

    public function __construct(
        ContainerInterface $container,
        private readonly App $app
    ) {
        parent::__construct($container);
    }

    /**
     * Parse the current request and return route information.
     */
    public function parseRequest(ServerRequestInterface $request): array
    {
        $method = $request->getMethod();
        $uri = $request->getUri()->getPath();

        return $this->matchRoute($method, $uri);
    }

    /**
     * Match a route against method and URI.
     */
    public function matchRoute(string $method, string $uri): array
    {
        $dispatcher = $this->getDispatcher();
        $routeInfo = $dispatcher->dispatch($method, $uri);

        switch ($routeInfo[0]) {
            case Dispatcher::FOUND:
                $routeIdentifier = $routeInfo[1];
                $vars = $routeInfo[2];

                return [
                    'found' => true,
                    'route_identifier' => $routeIdentifier,
                    'params' => $vars,
                    'route_info' => $this->getRouteDetails($routeIdentifier, $vars),
                ];

            case Dispatcher::METHOD_NOT_ALLOWED:
                return [
                    'found' => false,
                    'error' => 'method_not_allowed',
                    'allowed_methods' => $routeInfo[1],
                ];

            case Dispatcher::NOT_FOUND:
            default:
                return [
                    'found' => false,
                    'error' => 'not_found',
                ];
        }
    }

    /**
     * Get all routes that match a pattern.
     */
    public function getRoutesByPattern(string $pattern): array
    {
        $routes = [];
        $routeCollector = $this->app->getRouteCollector();

        foreach ($routeCollector->getRoutes() as $route) {
            if (fnmatch($pattern, $route->getName())) {
                $routes[] = [
                    'name' => $route->getName(),
                    'pattern' => $route->getPattern(),
                    'methods' => $route->getMethods(),
                    'callable' => $route->getCallable(),
                ];
            }
        }

        return $routes;
    }

    /**
     * Implementation of abstract method: Extract specific parameter from request.
     *
     * @param null|mixed $default
     */
    public function getParam(?ServerRequestInterface $request, string $paramName, $default = null)
    {
        if (!$request) {
            return $default;
        }

        $routeInfo = $this->parseRequest($request);

        return $routeInfo['found'] ? ($routeInfo['params'][$paramName] ?? $default) : $default;
    }

    /**
     * Implementation of abstract method: Get all parameters from request.
     */
    public function getParams(?ServerRequestInterface $request = null): array
    {
        if (!$request) {
            return [];
        }

        $routeInfo = $this->parseRequest($request);

        return $routeInfo['found'] ? $routeInfo['params'] : [];
    }

    /**
     * Implementation of abstract method: Get route name from request.
     */
    public function getRouteName(?ServerRequestInterface $request = null): ?string
    {
        if (!$request) {
            return null;
        }

        $routeInfo = $this->parseRequest($request);

        return $routeInfo['found'] ? $routeInfo['route_info']['name'] : null;
    }

    /**
     * Get FastRoute dispatcher with all application routes.
     */
    private function getDispatcher(): Dispatcher
    {
        if (null === $this->dispatcher) {
            $routeCollector = $this->app->getRouteCollector();
            $this->buildRouteMap($routeCollector);

            $this->dispatcher = \FastRoute\simpleDispatcher(function (RouteCollector $r) use ($routeCollector): void {
                foreach ($routeCollector->getRoutes() as $route) {
                    $r->addRoute(
                        $route->getMethods(),
                        $route->getPattern(),
                        $route->getIdentifier()
                    );
                }
            });
        }

        return $this->dispatcher;
    }

    /**
     * Build internal route mapping for quick lookups.
     */
    private function buildRouteMap(RouteCollectorInterface $routeCollector): void
    {
        foreach ($routeCollector->getRoutes() as $route) {
            $this->routeMap[$route->getIdentifier()] = [
                'name' => $route->getName(),
                'pattern' => $route->getPattern(),
                'methods' => $route->getMethods(),
                'callable' => $route->getCallable(),
                'groups' => $route->getGroups(),
            ];
        }
    }

    /**
     * Get detailed route information for a matched route.
     */
    private function getRouteDetails(string $routeIdentifier, array $params): array
    {
        $routeData = $this->routeMap[$routeIdentifier] ?? [];

        return [
            'identifier' => $routeIdentifier,
            'name' => $routeData['name'] ?? null,
            'pattern' => $routeData['pattern'] ?? null,
            'methods' => $routeData['methods'] ?? [],
            'callable' => $routeData['callable'] ?? null,
            'groups' => $routeData['groups'] ?? [],
            'params' => $params,
        ];
    }
}
