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

use App\Service\HtmlMinifyService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\StreamFactory;

/**
 * Enhanced HTML Minification Middleware.
 *
 * This middleware provides intelligent HTML minification for responses by:
 * - Using smart content detection that works with or without proper Content-Type headers
 * - Handling edge cases like redirect responses that contain HTML
 * - Preserving non-HTML content unchanged
 * - Providing performance metrics and error handling
 * - Supporting configuration-based enabling/disabling
 *
 * The middleware uses the HtmlMinifyService which provides robust HTML detection
 * and minification capabilities.
 *
 * https://html.spec.whatwg.org/multipage/syntax.html#syntax-tag-omission
 * https://github.com/middlewares/minifier
 * https://discourse.slimframework.com/t/how-get-di-container-into-middleware-in-slim-4/3752/2
 * https://github.com/voku/HtmlMin/blob/8e72ae1b99de1e616093c6fd2f99b82d27fde81a/src/voku/helper/HtmlMin.php#L745
 */
class HtmlMinifyMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly HtmlMinifyService $minifyService
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Skip minification if disabled or if response is empty
        if (!$this->minifyService->shouldEnable() || $this->isEmptyResponse($response)) {
            return $response;
        }

        // Skip minification for certain status codes
        if ($this->shouldSkipMinification($response)) {
            return $response;
        }

        try {
            return $this->minifyResponse($response);
        } catch (\Throwable) {
            // Return original response on any error - minification is not critical
            return $response;
        }
    }

    /**
     * Minify the response content if it's HTML.
     */
    private function minifyResponse(ResponseInterface $response): ResponseInterface
    {
        $body = $response->getBody();
        $content = (string) $body;

        // Use smart minification that automatically detects HTML content
        $minifiedContent = $this->minifyService->smartMinify($content, $response);

        // Only create new response if content was actually modified
        if ($minifiedContent !== $content) {
            $streamFactory = new StreamFactory();
            $newBody = $streamFactory->createStream($minifiedContent);

            return $response->withBody($newBody);
        }

        return $response;
    }

    /**
     * Check if response is empty or has no content.
     */
    private function isEmptyResponse(ResponseInterface $response): bool
    {
        $body = $response->getBody();

        return 0 === $body->getSize() || empty((string) $body);
    }

    /**
     * Determine if minification should be skipped for this response.
     *
     * Skip minification for:
     * - 1xx Informational responses (shouldn't have content)
     * - 204 No Content responses
     * - 304 Not Modified responses
     */
    private function shouldSkipMinification(ResponseInterface $response): bool
    {
        $statusCode = $response->getStatusCode();

        // Skip informational responses (1xx)
        if ($statusCode >= 100 && $statusCode < 200) {
            return true;
        }

        // Skip specific status codes that shouldn't have content or don't need minification
        $skipStatusCodes = [
            204, // No Content
            304, // Not Modified
        ];

        return \in_array($statusCode, $skipStatusCodes, true);
    }
}
