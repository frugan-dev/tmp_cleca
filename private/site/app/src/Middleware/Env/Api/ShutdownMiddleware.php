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

namespace App\Middleware\Env\Api;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class ShutdownMiddleware extends \App\Middleware\ShutdownMiddleware implements MiddlewareInterface
{
    protected string $mimeType = 'application/json';

    #[\Override]
    public function renderCallback(ServerRequestInterface $request): string
    {
        $message = $this->getConfigWithFallback('message', __('System under maintenance, try again later.'));
        $retryAfter = $this->getConfigWithFallback('retryAfter');

        $response = [
            'error' => true,
            'message' => $message,
            'status' => 'maintenance',
            'timestamp' => time(),
        ];

        // Add retry-after information if configured
        if (!empty($retryAfter)) {
            try {
                $retryDate = $this->helper->Carbon()->create($retryAfter);
                $response['retry_after'] = $retryDate->toISOString();
                $response['retry_after_seconds'] = max(0, $retryDate->timestamp - time());
            } catch (\Throwable $e) {
                $this->logger->warning('Failed to parse retry after date for API response', [
                    'retryAfter' => $retryAfter,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Add additional custom fields from config
        $customFields = $this->getConfigWithFallback('response.fields');
        if (\is_array($customFields) && !empty($customFields)) {
            $response = array_merge($response, $customFields);
        }

        try {
            return $this->helper->Nette()->Json()->encode($response);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to encode API shutdown response', [
                'error' => $e->getMessage(),
                'response' => $response,
            ]);

            // Fallback to basic JSON response
            return $this->getBasicMaintenanceMessage();
        }
    }
}
