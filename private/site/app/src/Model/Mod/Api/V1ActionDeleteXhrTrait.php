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

namespace App\Model\Mod\Api;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpMethodNotAllowedException;

trait V1ActionDeleteXhrTrait
{
    protected function v1ActionDeleteXhr(RequestInterface $request, ResponseInterface $response, $args)
    {
        // HTTP DELETE requests, like GET and HEAD requests, should not contain a body,
        // as this may cause some servers to work incorrectly.
        // But you can still send data to the server with an HTTP DELETE request using URL parameters.
        // see addBodyParsingMiddleware()
        $this->postData = (array) $request->getParsedBody();

        if (method_exists($this, __FUNCTION__.ucfirst((string) $this->actionCamelCase)) && \is_callable([$this, __FUNCTION__.ucfirst((string) $this->actionCamelCase)])) {
            $return = \call_user_func_array([$this, __FUNCTION__.ucfirst((string) $this->actionCamelCase)], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        } else {
            throw new HttpMethodNotAllowedException($request);
        }
    }
}
