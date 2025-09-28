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

trait V1ActionGetXhrTrait
{
    protected function v1ActionGetXhr(RequestInterface $request, ResponseInterface $response, $args)
    {
        try {
            if (method_exists($this, __FUNCTION__.$this->actionCamelCase) && \is_callable([$this, __FUNCTION__.$this->actionCamelCase])) {
                $return = \call_user_func_array([$this, __FUNCTION__.$this->actionCamelCase], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            } else {
                throw new HttpMethodNotAllowedException($request);
            }
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage(), [
                'exception' => $e,
            ]);

            $this->errors[] = __('A technical problem has occurred, try again later.');

            // rethrow it
            if ($this->config['debug.enabled']) {
                throw $e;
            }
        }
    }

    protected function v1ActionGetXhrIndex(RequestInterface $request, ResponseInterface $response, $args)
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

    protected function v1ActionGetXhrIndexFull(RequestInterface $request, ResponseInterface $response, $args)
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

    protected function v1ActionGetXhrIndexFullByFieldId(RequestInterface $request, ResponseInterface $response, $args)
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

    protected function v1ActionGetXhrView(RequestInterface $request, ResponseInterface $response, $args)
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
