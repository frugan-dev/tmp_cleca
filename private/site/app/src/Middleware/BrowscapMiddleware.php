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
use App\Factory\Logger\LoggerInterface;
use App\Model\Model;
use BrowscapPHP\Browscap;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BrowscapMiddleware extends Model implements MiddlewareInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger,
        protected CacheInterface $cache
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            // http://mobiledetect.net
            $browscap = new Browscap($this->cache->psr16Cache(), $this->logger->channel('internal'));

            $request = $request->withAttribute('browscapInfo', $browscap->getBrowser());
        } catch (\Exception $e) {
            $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                'error' => $e->getMessage(),
            ]);
        }

        // https://stackoverflow.com/a/6662635
        // https://stackoverflow.com/a/60199374
        // https://stackoverflow.com/a/65836060
        if (isset($_SERVER['HTTP_SEC_FETCH_DEST'])) {
            $inIframe = \in_array($_SERVER['HTTP_SEC_FETCH_DEST'], ['iframe', 'object'], true);
        }

        // https://stackoverflow.com/a/76347238/3929620
        if (empty($inIframe) && $request->hasHeader('Sec-Fetch-Dest')) {
            $inIframe = \in_array($request->getHeaderLine('Sec-Fetch-Dest'), ['iframe', 'object'], true);
        }

        if (!empty($inIframe)) {
            $request = $request->withAttribute('inIframe', $inIframe);
        }

        $response = $handler->handle($request);

        return $response;
    }
}
