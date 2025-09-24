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

namespace App\Helper;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Laminas\Stdlib\ArrayUtils;

class Remote extends Helper
{
    public function request($params = [])
    {
        $params = ArrayUtils::merge(
            [
                'method' => 'GET',
                'url' => '',
                'options' => [
                    'debug' => $this->config->get('debug.enabled'),
                ],
                'return_type' => null,
            ],
            $params
        );

        $client = new Client();

        try {
            $resp = $client->request(
                $params['method'],
                $params['url'],
                $params['options']
            );

            if (empty($params['return_type'])) {
                $params['return_type'] = \in_array($params['method'], ['HEAD'], true) ? 'headers' : 'body';
            }

            switch ($params['return_type']) {
                case 'body':
                    if (200 === $resp->getStatusCode()) {
                        return $resp->getBody()->getContents();
                    }

                    break;

                case 'headers':
                    return $resp->getHeaders();

                    break;

                default:
                    throw new \InvalidArgumentException("Invalid return_type: {$params['return_type']}");
            }
        } catch (BadResponseException $e) {
            $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__.' -> '.$e::class, [
                'error' => $e->getMessage(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__.' -> '.$e::class, [
                'error' => $e->getMessage(),
            ]);
        }

        return false;
    }
}
