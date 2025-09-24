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

namespace App\Controller\Env\Api;

use Laminas\Stdlib\ArrayUtils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpMethodNotAllowedException;

trait V1ActionPostXhrTrait
{
    public function v1ActionPostXhr(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (empty($this->getData['fancybox'])) {
            ob_implicit_flush(true);
            sleep(1);

            try {
                // https://discourse.slimframework.com/t/how-to-upload-multiple-files-with-getuploadedfiles/1055/3
                // https://www.slimframework.com/docs/v4/cookbook/uploading-files.html
                // https://www.php.net/manual/en/features.file-upload.post-method.php#100187
                // $_FILES will be empty if a user attempts to upload a file greater than post_max_size in your php.ini
                // post_max_size should be >= upload_max_filesize in your php.ini.
                $this->filesData = (array) $request->getUploadedFiles();

                // see addBodyParsingMiddleware()
                $this->postData = ArrayUtils::merge((array) $request->getParsedBody(), $this->filesData, true); // <-- with preserveNumericKeys

                if (!empty($this->postData['controller'])) {
                    // https://stackoverflow.com/a/47413279/3929620
                    // https://stackoverflow.com/a/3432266
                    \Safe\array_walk_recursive(
                        $this->postData,
                        function (&$v): void {
                            if (\is_string($v)) {
                                $v = trim($v);
                            }
                        }
                    );

                    $this->controllerCamelCase = $this->controller = $this->postData['controller'];

                    $this->filterValue->sanitize($this->controllerCamelCase, 'string', ['_', '-'], ' ');
                    $this->filterValue->sanitize($this->controllerCamelCase, 'titlecase');
                    $this->filterValue->sanitize($this->controllerCamelCase, 'lowercaseFirst');
                    $this->filterValue->sanitize($this->controllerCamelCase, 'string', ' ', '');

                    $this->actionCamelCase = null;
                    $this->action = $this->controller;

                    if (!empty($this->postData['action'])) {
                        $this->actionCamelCase = $this->action = $this->postData['action'];

                        $this->filterValue->sanitize($this->actionCamelCase, 'string', ['_', '-'], ' ');
                        $this->filterValue->sanitize($this->actionCamelCase, 'titlecase');
                        $this->filterValue->sanitize($this->actionCamelCase, 'lowercaseFirst');
                        $this->filterValue->sanitize($this->actionCamelCase, 'string', ' ', '');
                    }

                    if (!empty($this->postData['action']) && $this->container->has('Mod\\'.ucfirst((string) $this->controllerCamelCase).'\\'.ucfirst((string) static::$env))) {
                        $this->container->get('Mod\\'.ucfirst((string) $this->controllerCamelCase).'\\'.ucfirst((string) static::$env))->serverData = $this->serverData;
                        $this->container->get('Mod\\'.ucfirst((string) $this->controllerCamelCase).'\\'.ucfirst((string) static::$env))->postData = $this->postData;

                        $this->container->get('Mod\\'.ucfirst((string) $this->controllerCamelCase).'\\'.ucfirst((string) static::$env))->controller = $this->controller;
                        $this->container->get('Mod\\'.ucfirst((string) $this->controllerCamelCase).'\\'.ucfirst((string) static::$env))->action = $this->action;
                        $this->container->get('Mod\\'.ucfirst((string) $this->controllerCamelCase).'\\'.ucfirst((string) static::$env))->actionCamelCase = $this->actionCamelCase;

                        if (method_exists($this->container->get('Mod\\'.ucfirst((string) $this->controllerCamelCase).'\\'.ucfirst((string) static::$env)), __FUNCTION__.ucfirst((string) $this->actionCamelCase)) && \is_callable([$this->container->get('Mod\\'.ucfirst((string) $this->controllerCamelCase).'\\'.ucfirst((string) static::$env)), __FUNCTION__.ucfirst((string) $this->actionCamelCase)])) {
                            $return = \call_user_func_array([$this->container->get('Mod\\'.ucfirst((string) $this->controllerCamelCase).'\\'.ucfirst((string) static::$env)), __FUNCTION__.ucfirst((string) $this->actionCamelCase)], [$request, $response, $args]);

                            if (null !== $return) {
                                return $return;
                            }

                            $return = true;

                            $this->errors = $this->container->get('Mod\\'.ucfirst((string) $this->controllerCamelCase).'\\'.ucfirst((string) static::$env))->errors;
                            $this->responseData = $this->container->get('Mod\\'.ucfirst((string) $this->controllerCamelCase).'\\'.ucfirst((string) static::$env))->responseData;
                        }
                    }

                    if (!isset($return)) {
                        if (method_exists($this, __FUNCTION__.ucfirst((string) $this->controllerCamelCase).ucfirst((string) $this->actionCamelCase)) && \is_callable([$this, __FUNCTION__.ucfirst((string) $this->controllerCamelCase).ucfirst((string) $this->actionCamelCase)])) {
                            $return = \call_user_func_array([$this, __FUNCTION__.ucfirst((string) $this->controllerCamelCase).ucfirst((string) $this->actionCamelCase)], [$request, $response, $args]);

                            if (null !== $return) {
                                return $return;
                            }
                        } else {
                            throw new HttpMethodNotAllowedException($request);
                        }
                    }
                } else {
                    $this->errors[] = __('The data sent does not seem correct.');
                }
            } catch (\Exception $e) {
                $this->logger->debug($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                    'error' => $e->getMessage(),
                ]);

                $this->errors[] = __('A technical problem has occurred, try again later.');

                // rethrow it
                /*if ($this->config->get('debug.enabled')) {
                    throw $e;
                }*/
            }

            if (\count($this->errors) > 0) {
                $this->responseData['message'] = current($this->errors);

                if (empty($this->responseData['status'])) {
                    $this->responseData['status'] = 'danger';
                }
            }
        }
    }
}
