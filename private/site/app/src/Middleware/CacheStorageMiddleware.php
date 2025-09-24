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

use App\Factory\Cache\CacheInterface;
use App\Helper\HelperInterface;
use App\Model\Model;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class CacheStorageMiddleware extends Model implements MiddlewareInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected HelperInterface $helper,
        protected CacheInterface $cache
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $env = $this->container->get('env');

        $cacheItemKey = $this->cache->getItemKey([
            $this->helper->Url()->getPathUrl(),
        ]);
        $cacheItem = $this->cache->getItem($cacheItemKey);

        $response = $handler->handle($request);

        if (!empty($this->getConfigWithFallback('enabled'))) {
            if ($response->hasHeader($this->getConfigWithFallback('body.header'))) {
                $this->cache->saveItem($cacheItem, $response->getBody()->__toString());
            }
        } elseif ($this->cache->hasItem($cacheItemKey)) {
            $this->cache->deleteItem($cacheItemKey);
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
            "cache.{$env}.storage",
            'cache.storage',
        ];

        return $this->config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }
}
