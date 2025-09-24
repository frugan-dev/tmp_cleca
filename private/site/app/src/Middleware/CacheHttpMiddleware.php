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
use App\Model\Model;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Route-Specific HTTP Cache Middleware.
 *
 * Provides advanced caching logic for specific routes and content types.
 * Generates dynamic ETags based on response body content and environment context.
 * Uses CacheProvider for custom cache headers and validation.
 *
 * This differs from the global CacheMiddleware which applies standard cache policies
 * application-wide using Slim\HttpCache\Cache for basic Cache-Control headers.
 *
 * Use this middleware on route groups that need content-aware caching logic.
 */
class CacheHttpMiddleware extends Model implements MiddlewareInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected HelperInterface $helper,
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        $env = $this->container->get('env');

        if (!empty($this->getConfigWithFallback('provider.enabled'))) {
            if (!empty($this->getConfigWithFallback('provider.etag.enabled'))) {
                $response = $this->cacheHttpProvider->withEtag(
                    $response,
                    // FIXME - use https://github.com/google/php-crc32
                    // https://stackoverflow.com/a/15848955/3929620
                    (string) $this->helper->Strings()->crc32($env.$response->getBody()->__toString()),
                    $this->getConfigWithFallback('provider.etag.type')
                );
            }
        }

        return $response;
    }

    /**
     * Get configuration value with environment fallback using existing ConfigManager.
     *
     * @param null|mixed $default
     */
    protected function getConfigWithFallback(string $suffix = '', $default = null, ?array $prefixes = null)
    {
        $env = $this->container->get('env');

        $prefixes ??= [
            "cache.{$env}.http",
            'cache.http',
        ];

        return $this->config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }
}
