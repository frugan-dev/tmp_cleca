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

use Laminas\Stdlib\ArrayUtils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Symfony\Component\EventDispatcher\GenericEvent;

trait V1ActionPutTrait
{
    protected function v1ActionPut(RequestInterface $request, ResponseInterface $response, $args)
    {
        // https://discourse.slimframework.com/t/how-to-upload-multiple-files-with-getuploadedfiles/1055/3
        // https://www.slimframework.com/docs/v4/cookbook/uploading-files.html
        // https://www.php.net/manual/en/features.file-upload.post-method.php#100187
        // $_FILES will be empty if a user attempts to upload a file greater than post_max_size in your php.ini
        // post_max_size should be >= upload_max_filesize in your php.ini.
        $this->filesData = (array) $request->getUploadedFiles();

        // see addBodyParsingMiddleware()
        $this->postData = ArrayUtils::merge((array) $request->getParsedBody(), $this->filesData, true); // <-- with preserveNumericKeys

        if (method_exists($this, __FUNCTION__.ucfirst((string) $this->actionCamelCase)) && \is_callable([$this, __FUNCTION__.ucfirst((string) $this->actionCamelCase)])) {
            $return = \call_user_func_array([$this, __FUNCTION__.ucfirst((string) $this->actionCamelCase)], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        } else {
            throw new HttpMethodNotAllowedException($request);
        }
    }

    protected function v1ActionPutEdit(RequestInterface $request, ResponseInterface $response, $args)
    {
        if ($this->rbac->isGranted($this->controller.'.'.static::$env.'.'.$this->action)) {
            if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            } else {
                throw new HttpMethodNotAllowedException($request);
            }
        } else {
            throw new HttpUnauthorizedException($request);
        }
    }

    protected function _v1ActionPutEdit(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->setId();

        if ($this->existStrict([
            'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
        ])) {
            $this->setFields();

            $this->check($request);

            if (0 === \count($this->errors)) {
                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                $return = $this->dbEdit();

                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                if (0 === \count($this->errors)) {
                    $this->responseData['response'] = $return;
                }
            }
        } else {
            throw new HttpNotFoundException($request);
        }
    }
}
