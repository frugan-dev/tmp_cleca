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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpUnauthorizedException;

class Controller extends \App\Model\Controller
{
    use V1ActionPostXhrTrait;

    public static string $env = 'api';

    public string $version;

    public string $mimetype = 'application/json';

    #[\Override]
    public function __invoke(RequestInterface $request, ResponseInterface $response, $args)
    {
        try {
            if (empty($request->getAttribute('hasIdentity'))) {
                throw new HttpUnauthorizedException($request);
            }

            $this->serverData = (array) $request->getServerParams();

            $this->cookieData = (array) $request->getCookieParams();

            $this->getData = (array) $request->getQueryParams();

            // https://heera.it/detect-ajax-request-php-frameworks
            // TODO - use https://github.com/slimphp/Slim-Http/#serverrequestisxhr
            $this->isXhr = (bool) ('XMLHttpRequest' === $request->getHeaderLine('X-Requested-With'));
            $this->xhrCamelCase = $this->isXhr ? 'Xhr' : null;

            $this->methodCamelCase = $request->getMethod();

            $this->filterValue->sanitize($this->methodCamelCase, 'lowercase');
            $this->filterValue->sanitize($this->methodCamelCase, 'uppercaseFirst');

            $this->version = $args['v'] ?? $this->config->get('api.version');

            $this->controllerCamelCase = $this->controller ??= 'index';

            $this->filterValue->sanitize($this->controllerCamelCase, 'string', ['_', '-'], ' ');
            $this->filterValue->sanitize($this->controllerCamelCase, 'titlecase');
            $this->filterValue->sanitize($this->controllerCamelCase, 'lowercaseFirst');
            $this->filterValue->sanitize($this->controllerCamelCase, 'string', ' ', '');

            $this->actionCamelCase = $this->action ??= $args['action'] ?? 'index';

            $this->filterValue->sanitize($this->actionCamelCase, 'string', ['_', '-'], ' ');
            $this->filterValue->sanitize($this->actionCamelCase, 'titlecase');
            $this->filterValue->sanitize($this->actionCamelCase, 'lowercaseFirst');
            $this->filterValue->sanitize($this->actionCamelCase, 'string', ' ', '');

            if (method_exists($this, 'v'.$this->version.'Action'.$this->methodCamelCase.$this->xhrCamelCase) && \is_callable([$this, 'v'.$this->version.'Action'.$this->methodCamelCase.$this->xhrCamelCase])) {
                if (!empty($request->getAttribute('hasIdentity'))) {
                    if (!empty($request->getHeaderLine($this->config->get('api.headers.key'))) && !empty($this->auth->getIdentity()[$this->auth->getIdentity()['_role_type'].'_api_log_level'])) {
                        $logLevel = !empty($request->getHeaderLine($this->config->get('api.headers.key'))) ? $this->logger::toMonologLevel($this->auth->getIdentity()[$this->auth->getIdentity()['_role_type'].'_api_log_level'])->toPsrLogLevel() : 'debug';

                        \call_user_func_array(
                            [$this->logger, $logLevel],
                            [
                                \sprintf(_x('Rest API %1$s %2$s', $this->context, $this->config->get('logger.locale')), $request->getMethod(), $this->helper->Url()->getPathUrl()),
                            ]
                        );

                        __('Rest API %1$s %2$s', 'default');
                        __('Rest API %1$s %2$s', 'male');
                        __('Rest API %1$s %2$s', 'female');
                    }
                }

                $return = \call_user_func_array([$this, 'v'.$this->version.'Action'.$this->methodCamelCase.$this->xhrCamelCase], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            } else {
                throw new HttpMethodNotAllowedException($request);
            }
        } catch (\Exception $e) {
            $this->errors[] = $e->getMessage();

            // rethrow it
            if ($this->config->get('debug.enabled')) {
                throw $e;
            }
        } catch (\Throwable $e) {
            $this->logger->debug($e->getMessage(), [
                'exception' => $e,
            ]);

            $this->errors[] = __('A technical problem has occurred, try again later.');

            // rethrow it
            if ($this->config->get('debug.enabled')) {
                throw $e;
            }
        }

        if (\count($this->errors) > 0) {
            if (200 === $response->getStatusCode()) {
                $this->statusCode = 403;
            }

            $this->logger->debug($e->getMessage(), [
                'exception' => $e,
                'errors' => $this->errors,
            ]);

            $this->responseData['errors'] = $this->errors;
        }

        // The Accept request-header field can be used to specify certain media types which are acceptable for the response.
        // The Content-Type entity-header field indicates the media type of the entity-body sent to the recipient or,
        // in the case of the HEAD method, the media type that would have been sent had the request been a GET.
        if ($request->hasHeader('Accept')) {
            if (!empty($accepts = array_intersect($request->getHeader('Accept'), [
                'application/json',
                'application/xml',
            ]))) {
                $this->mimetype = current($accepts);
            }
        }

        return $this->render($request, $response, $args);
    }

    #[\Override]
    public function renderBody(?RequestInterface $request = null, ?ResponseInterface $response = null, $args = null)
    {
        return match ($this->mimetype) {
            'application/xml' => $this->helper->Arrays()->toXml($this->responseData),
            'text/html' => (string) ($this->responseData ?? ''),
            default => $this->helper->Nette()->Json()->encode($this->responseData),
        };
    }
}
