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
use Slim\Exception\HttpBadRequestException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Symfony\Component\EventDispatcher\GenericEvent;

trait V1ActionDeleteTrait
{
    protected function v1ActionDelete(RequestInterface $request, ResponseInterface $response, $args)
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

    protected function v1ActionDeleteDelete(RequestInterface $request, ResponseInterface $response, $args)
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

    protected function v1ActionDeleteDeleteBulk(RequestInterface $request, ResponseInterface $response, $args)
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

    protected function _v1ActionDeleteDelete(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->setId();

        if ($this->existStrict([
            'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
        ])) {
            $this->setFields();

            $this->checkDeps($request);
            if (0 === \count($this->errorDeps)) {
                $this->check($request);

                if (0 === \count($this->errors)) {
                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                    $return = $this->dbDelete();

                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                    if (0 === \count($this->errors)) {
                        $this->responseData['response'] = $return;
                    }
                }
            } else {
                $this->errors[] = __('To be able to perform this operation, the dependencies must be resolved first.');

                $error = [];
                foreach ($this->errorDeps as $modName => $rows) {
                    $error[$modName] = [];
                    foreach ($rows as $rowKey => $row) {
                        if (!empty($row['id'])) {
                            $error[$modName][] = $row['id'];
                        }
                    }
                }

                $this->errors[] = $error;
            }
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    protected function _v1ActionDeleteDeleteBulk(RequestInterface $request, ResponseInterface $response, $args): void
    {
        if (!empty($bulkIds = (array) $this->postData['bulk_ids'] ?? [])) {
            $errorDeps = $errorBulksIds = [];

            foreach ($bulkIds as $bulkId) {
                $this->setId($bulkId);

                $this->checkDeps($request);

                if (\count($this->errorDeps) > 0) {
                    foreach ($this->errorDeps as $modName => $rows) {
                        if (isset($errorDeps[$modName])) {
                            if (isset($errorDeps[$modName]['totRows'])) {
                                $rows['totRows'] += $errorDeps[$modName]['totRows'];
                            }

                            $errorDeps[$modName] = \array_slice(array_merge($errorDeps[$modName], $rows), 0, $this->config['mod.'.static::$env.'.rowLast'] ?? $this->config['mod.rowLast'] ?? 1);

                            $errorDeps[$modName]['totRows'] = $rows['totRows'];
                        } else {
                            $errorDeps[$modName] = $rows;
                        }
                    }
                }
            }

            $this->errorDeps = $errorDeps;

            if (0 === \count($this->errorDeps)) {
                $errors = [];

                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                foreach ($bulkIds as $bulkId) {
                    $this->errors = [];

                    $this->setId($bulkId);

                    if ($this->existStrict([
                        'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
                    ])) {
                        $this->setFields();

                        $this->check($request);

                        if (0 === \count($this->errors)) {
                            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.delete.before');

                            $return = $this->dbDelete();

                            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.delete.after');

                            if (\count($this->errors) > 0) {
                                $errorBulksIds[] = $bulkId;
                            }
                        } else {
                            $errorBulksIds[] = $bulkId;
                        }
                    } else {
                        $errorBulksIds[] = $bulkId;

                        $this->errors[] = \sprintf(__('%1$s #%2$d not found.'), $this->singularNameWithParams, $bulkId);
                    }

                    $errors = array_merge($errors, $this->errors);
                }

                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                $this->errors = $errors;

                $deletedBulkIds = array_diff($bulkIds, $errorBulksIds);

                if (empty($deletedBulkIds)) {
                    $this->statusCode = 404;
                } else {
                    $this->responseData['response'] = $deletedBulkIds;
                }
            } else {
                $this->errors[] = __('To be able to perform this operation, the dependencies must be resolved first.');

                $error = [];
                foreach ($this->errorDeps as $modName => $rows) {
                    $error[$modName] = [];
                    foreach ($rows as $rowKey => $row) {
                        if (!empty($row['id'])) {
                            $error[$modName][] = $row['id'];
                        }
                    }
                }

                $this->errors[] = $error;
            }
        } else {
            throw new HttpBadRequestException($request);
        }
    }
}
