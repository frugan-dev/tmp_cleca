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

use Psr\Http\Message\ServerRequestInterface;

/**
 * Interface for route parsing services.
 * Defines common methods for extracting route information from requests.
 */
interface RouteParsingInterface
{
    /**
     * Get language from request.
     */
    public function getLanguage(?ServerRequestInterface $request = null): ?string;

    /**
     * Get route name from request.
     */
    public function getRouteName(?ServerRequestInterface $request = null): ?string;

    /**
     * Get specific parameter from request.
     *
     * @param null|mixed $default
     */
    public function getParam(?ServerRequestInterface $request, string $paramName, $default = null);

    /**
     * Get all parameters from request.
     */
    public function getParams(?ServerRequestInterface $request = null): array;

    /**
     * Get the module name from the current request.
     */
    public function getModuleName(?ServerRequestInterface $request = null): ?string;

    /**
     * Get the environment (front, back, api) from the current request.
     */
    public function getEnvironment(?ServerRequestInterface $request = null): ?string;

    /**
     * Check if request matches a specific route pattern.
     */
    public function matchesPattern(?ServerRequestInterface $request, string $pattern): bool;

    /**
     * Check if the current request is for a specific module.
     */
    public function isModuleRequest(?ServerRequestInterface $request, string $moduleName): bool;

    /**
     * Check if this is an index route.
     */
    public function isIndexRoute(?ServerRequestInterface $request = null): bool;

    /**
     * Helper method to extract action from request.
     */
    public function getAction(?ServerRequestInterface $request = null): ?string;

    /**
     * Helper method to extract params string from request.
     */
    public function getParamsString(?ServerRequestInterface $request = null): ?string;

    /**
     * Helper method to extract numeric ID parameter from request.
     */
    public function getNumericId(?ServerRequestInterface $request, string $paramName): ?int;
}
