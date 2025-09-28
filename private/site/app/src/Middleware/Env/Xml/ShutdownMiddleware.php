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

namespace App\Middleware\Env\Xml;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class ShutdownMiddleware extends \App\Middleware\ShutdownMiddleware implements MiddlewareInterface
{
    protected string $mimeType = 'application/xml';

    #[\Override]
    public function renderCallback(ServerRequestInterface $request): string
    {
        $message = $this->getConfigWithFallback('message', __('System under maintenance, try again later.'));
        $retryAfter = $this->getConfigWithFallback('retryAfter');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL;
        $xml .= '<maintenance>'.PHP_EOL;
        $xml .= '  <status>enabled</status>'.PHP_EOL;
        $xml .= '  <message>'.htmlspecialchars((string) $message, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</message>'.PHP_EOL;
        $xml .= '  <timestamp>'.time().'</timestamp>'.PHP_EOL;

        if (!empty($retryAfter)) {
            try {
                $retryDate = $this->helper->Carbon()->create($retryAfter);
                $xml .= '  <retry_after>'.htmlspecialchars((string) $retryDate->toISOString(), ENT_XML1, 'UTF-8').'</retry_after>'.PHP_EOL;
                $xml .= '  <retry_after_seconds>'.max(0, $retryDate->timestamp - time()).'</retry_after_seconds>'.PHP_EOL;
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to parse retry after date for XML response', [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'text' => $e->getTraceAsString(),
                    'retryAfter' => $retryAfter,
                ]);
            }
        }

        $xml .= '</maintenance>';

        return $xml;
    }
}
