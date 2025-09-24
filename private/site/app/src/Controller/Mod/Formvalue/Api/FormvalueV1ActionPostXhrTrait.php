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

namespace App\Controller\Mod\Formvalue\Api;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait FormvalueV1ActionPostXhrTrait
{
    protected function v1ActionPostXhrUpload(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (method_exists($this, str_replace($this->xhrCamelCase, '', __FUNCTION__)) && \is_callable([$this, str_replace($this->xhrCamelCase, '', __FUNCTION__)])) {
            $return = \call_user_func_array([$this, str_replace($this->xhrCamelCase, '', __FUNCTION__)], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        } else {
            throw new HttpMethodNotAllowedException($request);
        }
    }
}
