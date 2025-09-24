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

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Routing\RouteContext;

/**
 * Unified route parsing service that intelligently handles route information
 * both before and after routing middleware execution.
 * Provides unified access to route data regardless of execution phase.
 * Extends AbstractRouteParsingService for common functionality.
 */
class RouteParsingService extends AbstractRouteParsingService
{
    public function __construct(
        ContainerInterface $container,
        private readonly PreRouteParsingService $preRouteParsingService
    ) {
        parent::__construct($container);
    }

    /**
     * Implementation of abstract method: Get route name, automatically choosing the best method.
     */
    public function getRouteName(?ServerRequestInterface $request = null): ?string
    {
        $request ??= $this->getRequest();

        if (!$request) {
            return null;
        }

        // Try native Slim routing first (post-routing)
        if ($this->hasNativeRouting($request)) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();

            if ($route) {
                return $route->getName();
            }
        }

        // Fallback to pre-route parsing service
        return $this->preRouteParsingService->getRouteName($request);
    }

    /**
     * Implementation of abstract method: Get route parameter, automatically choosing the best method.
     *
     * @param null|mixed $default
     */
    public function getParam(?ServerRequestInterface $request, string $paramName, $default = null)
    {
        $request ??= $this->getRequest();

        if (!$request) {
            return $default;
        }

        // Try native Slim routing first (post-routing)
        if ($this->hasNativeRouting($request)) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();

            if ($route) {
                $argument = $route->getArgument($paramName);

                return $argument ?? $default;
            }
        }

        // Fallback to pre-route parsing service
        return $this->preRouteParsingService->getParam($request, $paramName, $default);
    }

    /**
     * Implementation of abstract method: Get all route parameters, automatically choosing the best method.
     */
    public function getParams(?ServerRequestInterface $request = null): array
    {
        $request ??= $this->getRequest();

        if (!$request) {
            return [];
        }

        // Try native Slim routing first (post-routing)
        if ($this->hasNativeRouting($request)) {
            $routeContext = RouteContext::fromRequest($request);
            $route = $routeContext->getRoute();

            if ($route) {
                return $route->getArguments();
            }
        }

        // Fallback to pre-route parsing service
        return $this->preRouteParsingService->getParams($request);
    }

    /**
     * Check if request has native Slim routing information available.
     */
    public function hasNativeRouting(ServerRequestInterface $request): bool
    {
        return null !== $request->getAttribute(RouteContext::ROUTE_PARSER)
            && null !== $request->getAttribute(RouteContext::ROUTING_RESULTS);
    }

    /**
     * Check if we're in pre-routing or post-routing phase.
     */
    public function isPostRouting(?ServerRequestInterface $request = null): bool
    {
        $request ??= $this->getRequest();

        return $request && $this->hasNativeRouting($request);
    }

    // Delegate specific methods to PreRouteParsingService for consistency
    public function parseRequest(ServerRequestInterface $request): array
    {
        return $this->preRouteParsingService->parseRequest($request);
    }

    public function matchRoute(string $method, string $uri): array
    {
        return $this->preRouteParsingService->matchRoute($method, $uri);
    }

    public function getRoutesByPattern(string $pattern): array
    {
        return $this->preRouteParsingService->getRoutesByPattern($pattern);
    }

    /**
     * Enhanced pattern matching that works with both pre and post routing.
     */
    #[\Override]
    public function matchesPattern(?ServerRequestInterface $request, string $pattern): bool
    {
        $request ??= $this->getRequest();

        return $request ? parent::matchesPattern($request, $pattern) : false;
    }

    /**
     * Enhanced module detection that works with both pre and post routing.
     */
    #[\Override]
    public function isModuleRequest(?ServerRequestInterface $request, string $moduleName): bool
    {
        $request ??= $this->getRequest();

        return $request ? parent::isModuleRequest($request, $moduleName) : false;
    }

    /**
     * Get current request from container.
     */
    private function getRequest(): ?ServerRequestInterface
    {
        return $this->container->has('request') ? $this->container->get('request') : null;
    }
}
