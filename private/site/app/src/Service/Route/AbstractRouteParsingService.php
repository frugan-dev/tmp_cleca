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

/**
 * Abstract base class for route parsing services.
 * Provides common implementation for route information extraction.
 */
abstract class AbstractRouteParsingService implements RouteParsingInterface
{
    public function __construct(
        protected readonly ContainerInterface $container
    ) {}

    /**
     * Abstract method to get route name - must be implemented by concrete classes.
     */
    abstract public function getRouteName(?ServerRequestInterface $request = null): ?string;

    /**
     * Abstract method to get route parameter - must be implemented by concrete classes.
     *
     * @param null|mixed $default
     */
    abstract public function getParam(?ServerRequestInterface $request, string $paramName, $default = null);

    /**
     * Abstract method to get all route parameters - must be implemented by concrete classes.
     */
    abstract public function getParams(?ServerRequestInterface $request = null): array;

    /**
     * Get language from request.
     * Uses the concrete implementation's getParam method.
     */
    public function getLanguage(?ServerRequestInterface $request = null): ?string
    {
        return $this->getParam($request, 'lang');
    }

    /**
     * Get the module name from the current request.
     * Common logic based on route name patterns.
     */
    public function getModuleName(?ServerRequestInterface $request = null): ?string
    {
        $routeName = $this->getRouteName($request);

        if (!$routeName) {
            return null;
        }

        // Parse route name patterns like 'front.catform', 'back.user', etc.
        $parts = explode('.', $routeName);

        if (\count($parts) >= 2) {
            $availableModules = $this->container->get('mods');
            $moduleCandidate = ucfirst($parts[1]);

            if (\in_array($moduleCandidate, $availableModules, true)) {
                return $moduleCandidate;
            }
        }

        return null;
    }

    /**
     * Get the environment (front, back, api) from the current request.
     * Common logic based on route name patterns.
     */
    public function getEnvironment(?ServerRequestInterface $request = null): ?string
    {
        $routeName = $this->getRouteName($request);

        if (!$routeName) {
            return null;
        }

        $parts = explode('.', $routeName);

        return $parts[0] ?? null;
    }

    /**
     * Check if request matches a specific route pattern.
     * Common logic using fnmatch.
     */
    public function matchesPattern(?ServerRequestInterface $request, string $pattern): bool
    {
        $routeName = $this->getRouteName($request);

        return $routeName && fnmatch($pattern, $routeName);
    }

    /**
     * Check if the current request is for a specific module.
     * Common logic using pattern matching.
     */
    public function isModuleRequest(?ServerRequestInterface $request, string $moduleName): bool
    {
        $routeName = $this->getRouteName($request);
        $modulePattern = "*{$moduleName}*";

        return $routeName && fnmatch($modulePattern, $routeName);
    }

    /**
     * Check if this is an index route.
     * Common logic using regex pattern matching.
     */
    public function isIndexRoute(?ServerRequestInterface $request = null): bool
    {
        $routeName = $this->getRouteName($request);

        if (!$routeName) {
            return false;
        }

        // Match patterns like "front.index", "back.index", "front.index.lang", etc.
        return (bool) \Safe\preg_match('/^[^.]+\.index(\.|$)/', $routeName);
    }

    /**
     * Check if this is an API route.
     * Common logic based on environment detection.
     */
    public function isApiRoute(?ServerRequestInterface $request = null): bool
    {
        return 'api' === $this->getEnvironment($request);
    }

    /**
     * Check if this is a front-office route.
     * Common logic based on environment detection.
     */
    public function isFrontRoute(?ServerRequestInterface $request = null): bool
    {
        return 'front' === $this->getEnvironment($request);
    }

    /**
     * Helper method to extract action from request.
     * Common logic using parameter extraction.
     */
    public function getAction(?ServerRequestInterface $request = null): ?string
    {
        return $this->getParam($request, 'action');
    }

    /**
     * Helper method to extract params string from request.
     * Common logic using parameter extraction.
     */
    public function getParamsString(?ServerRequestInterface $request = null): ?string
    {
        return $this->getParam($request, 'params');
    }

    /**
     * Helper method to extract numeric ID parameter from request.
     * Common logic with type conversion.
     */
    public function getNumericId(?ServerRequestInterface $request, string $paramName): ?int
    {
        $value = $this->getParam($request, $paramName);

        return $value && is_numeric($value) ? (int) $value : null;
    }
}
