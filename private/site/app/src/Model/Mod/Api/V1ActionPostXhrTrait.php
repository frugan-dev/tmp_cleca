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

trait V1ActionPostXhrTrait
{
    public function v1ActionPostXhrReset(RequestInterface $request, ResponseInterface $response, $args): void
    { // <--
        $env = 'back';

        if ($this->rbac->isGranted($this->controller.'.'.$env.'.index')) {
            $sessionData = $this->session->get($env.'.'.$this->auth->getIdentity()['id'].'.sessionData', []);

            if (isset($sessionData[$this->controller])) {
                unset($sessionData[$this->controller]);
            }

            $this->session->set($env.'.'.$this->auth->getIdentity()['id'].'.sessionData', $sessionData);

            $searchData = $this->session->get($env.'.'.$this->auth->getIdentity()['id'].'.searchData', []);

            if (isset($searchData[$this->controller])) {
                unset($searchData[$this->controller]);
            }

            $this->session->set($env.'.'.$this->auth->getIdentity()['id'].'.searchData', $searchData);

            $this->responseData['redirect'] = $this->helper->Url()->UrlFor([
                'routeName' => $env.'.'.$this->controller.'.params',
                'data' => [
                    'action' => 'index',
                    'params' => implode(
                        '/',
                        array_merge(
                            $this->session->get('routeParamsArr', []),
                            [$this->session->get('pg', $this->pager->pg)]
                        )
                    ),
                ],
            ]);
        } else {
            throw new HttpUnauthorizedException($request);
        }
    }

    protected function v1ActionPostXhr(RequestInterface $request, ResponseInterface $response, $args)
    {
        try {
            if (!empty($this->getData['fancybox'])) {
                return null;
            }

            // https://discourse.slimframework.com/t/how-to-upload-multiple-files-with-getuploadedfiles/1055/3
            // https://www.slimframework.com/docs/v4/cookbook/uploading-files.html
            // https://www.php.net/manual/en/features.file-upload.post-method.php#100187
            // $_FILES will be empty if a user attempts to upload a file greater than post_max_size in your php.ini
            // post_max_size should be >= upload_max_filesize in your php.ini.
            $this->filesData = (array) $request->getUploadedFiles();

            // see addBodyParsingMiddleware()
            $this->postData = ArrayUtils::merge((array) $request->getParsedBody(), $this->filesData, true); // <-- with preserveNumericKeys

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

        if (\count($this->errors) > 0) {
            $this->responseData['message'] = current($this->errors);
            $this->responseData['reload'] = true;

            $this->session->addFlash([
                'type' => 'toast',
                'options' => [
                    'type' => 'danger',
                    'message' => current($this->errors),
                ],
            ]);
        }
    }

    protected function v1ActionPostXhrToggle(RequestInterface $request, ResponseInterface $response, $args)
    {
        if ($this->rbac->isGranted($this->controller.'.'.static::$env.'.edit')) {
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

    protected function _v1ActionPostXhrToggle(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->setId((int) $this->postData['id']);

        if ($this->exist()) {
            $this->setFields();

            $this->check(
                $request,
                function (): void {
                    $this->filterSubject->sanitize('field')->toBlankOr('trim');
                    $this->filterSubject->sanitize('id')->to('int');

                    if (method_exists($this, 'sanitize'.$this->actionCamelCase.ucfirst((string) $this->postData['field'])) && \is_callable([$this, 'sanitize'.$this->actionCamelCase.ucfirst((string) $this->postData['field'])])) {
                        \call_user_func_array([$this, 'sanitize'.$this->actionCamelCase.ucfirst((string) $this->postData['field'])], [$this->postData['field']]);
                    }
                },
                function (): void {
                    $this->filterSubject->validate('field')->isNotBlank();
                    $this->filterSubject->validate('id')->isNotBlank();

                    if (method_exists($this, 'validate'.$this->actionCamelCase.ucfirst((string) $this->postData['field'])) && \is_callable([$this, 'validate'.$this->actionCamelCase.ucfirst((string) $this->postData['field'])])) {
                        \call_user_func_array([$this, 'validate'.$this->actionCamelCase.ucfirst((string) $this->postData['field'])], [$this->postData['field']]);
                    }
                }
            );

            if (0 === \count($this->errors)) {
                if (method_exists($this, 'db'.$this->actionCamelCase.ucfirst((string) $this->postData['field'])) && \is_callable([$this, 'db'.$this->actionCamelCase.ucfirst((string) $this->postData['field'])])) {
                    $callback = 'db'.$this->actionCamelCase.ucfirst((string) $this->postData['field']);
                } elseif (method_exists($this, 'db'.$this->actionCamelCase) && \is_callable([$this, 'db'.$this->actionCamelCase])) {
                    $callback = 'db'.$this->actionCamelCase;
                } else {
                    $this->errors[] = __('A technical problem has occurred, try again later.');
                }
            }

            if (0 === \count($this->errors)) {
                $this->logger->info(\sprintf(_x('Modified %1$s #%2$d', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $this->id));

                __('Modified %1$s #%2$d', 'default');
                __('Modified %1$s #%2$d', 'male');
                __('Modified %1$s #%2$d', 'female');

                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->getShortName().'.'.__FUNCTION__.'.'.$this->actionCamelCase.'.'.ucfirst((string) $this->postData['field']).'.before');

                if (($return = \call_user_func([$this, $callback])) !== false) {
                    $this->responseData['checked'] = $return;
                } else {
                    $this->errors[] = __('A technical problem has occurred, try again later.');
                }

                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->getShortName().'.'.__FUNCTION__.'.'.$this->actionCamelCase.'.'.ucfirst((string) $this->postData['field']).'.after');

                if (0 === \count($this->errors)) {
                    $this->responseData['response'] = 'success';
                    $this->responseData['message'] = __('Operation performed successfully.');

                    /*if (array_key_exists($data['field'], $this->get('Vendor\Session\Segment')->get('filters['.$this->getShortName().']', []))) {
                        $this->responseData['reload'] = true;
                    }*/
                }
            }
        } else {
            throw new HttpNotFoundException($request);
        }
    }
}
