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
use Slim\Routing\RoutingResults;

trait V1ActionGetTrait
{
    protected function v1ActionGet(RequestInterface $request, ResponseInterface $response, $args)
    {
        try {
            $this->getData = (array) $request->getQueryParams();

            if (!empty($this->getData['fancybox'])) {
                return null;
            }

            $this->serverData = (array) $request->getServerParams();

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
            if ($this->config->get('debug.enabled')) {
                throw $e;
            }
        }
    }

    // https://html.spec.whatwg.org/multipage/server-sent-events.html
    // https://javascript.info/long-polling
    // https://javascript.info/websocket
    // https://javascript.info/server-sent-events
    // https://github.com/mdn/dom-examples/tree/main/server-sent-events
    // https://discourse.slimframework.com/t/flush-response-early-and-post-process-operations/2787/4
    // https://developer.mozilla.org/en-US/docs/Web/API/Server-sent_events/Using_server-sent_events#fields
    // https://stackoverflow.com/a/2832179/3929620
    // https://stackoverflow.com/a/29893054/3929620
    // https://www.jeffgeerling.com/blog/2016/streaming-php-disabling-output-buffering-php-apache-nginx-and-varnish
    // https://kevinchoppin.dev/blog/server-sent-events-in-php
    // https://github.com/EventSource/eventsource/
    // https://gist.github.com/k4ml/781dbf0c9c78a20eabbb
    // https://github.com/slimphp/Slim/issues/1959#issuecomment-250998762
    protected function v1ActionGetSse(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (!empty($this->getData['url'])) {
            // https://github.com/nikic/FastRoute
            $uri = $this->getData['url'];
            if (false !== $pos = strpos((string) $uri, '?')) {
                $uri = substr((string) $uri, 0, $pos);
            }
            $uri = rawurldecode((string) $uri);

            $routingResults = $this->app->getRouteResolver()->computeRoutingResults(
                $uri,
                $request->getMethod()
            );
            $routeStatus = $routingResults->getRouteStatus();

            if (RoutingResults::FOUND === $routeStatus) {
                $routeArguments = $routingResults->getRouteArguments();
                $routeIdentifier = $routingResults->getRouteIdentifier() ?? '';
                $route = $this->app->getRouteResolver()
                    ->resolveRoute($routeIdentifier)
                    ->prepare($routeArguments)
                ;

                $controller = 'index';
                if (str_contains((string) $route->getName(), '.')) {
                    [, $controller] = explode('.', $route->getName());
                }

                $args['_routeParams'] = $route->getArgument('params');

                // https://stackoverflow.com/a/70219693/3929620
                \Safe\session_write_close();

                // https://stackoverflow.com/a/45161919/3929620
                // The function apache_getenv() is not available with PHP-FPM
                // @apache_setenv('no-gzip', 1);

                \Safe\ini_set('zlib.output_compression', '0');
                \Safe\ini_set('max_execution_time', '0');

                while (ob_get_level()) {
                    \Safe\ob_end_clean();
                }

                // https://www.php.net/manual/en/function.ob-implicit-flush.php#116748
                // https://www.php.net/manual/en/function.flush.php#124861
                ob_implicit_flush();
                gc_enable();

                header('Content-Type: text/event-stream');
                header('Cache-Control: no-cache'); // recommended to prevent caching of event data
                // header('Access-Control-Allow-Credentials: true');
                // header('Access-Control-Allow-Methods: '.$request->getMethod());
                // header('Access-Control-Expose-Headers: X-Events');

                // Get the Last Event ID - Check the Header or Get Method
                // in the event of client reconnect, it will send Last-Event-ID in the headers
                // this only evaluated during the first request and subsequent reconnect from client
                if (empty($args['_lastEventId'] = (int) ($this->serverData['HTTP_LAST_EVENT_ID'] ?? 0))) {
                    $args['_lastEventId'] = (int) ($this->getData['lastEventId'] ?? 0);
                }

                $i = 0;
                while (true) {
                    // https://www.php.net/manual/en/features.connection-handling.php
                    $connectionStatus = connection_status();
                    if (CONNECTION_NORMAL !== $connectionStatus) {
                        $this->logger->debug('Client connection lost, terminating loop', [
                            'connection_status' => $connectionStatus,
                        ]);

                        break;
                    }

                    $this->responseData = [];

                    foreach ($this->container->get('mods') as $modName) {
                        if ($this->container->has('Mod\\'.ucfirst((string) $modName).'\\'.ucfirst(static::$env))) {
                            if (method_exists($this->container->get('Mod\\'.ucfirst((string) $modName).'\\'.ucfirst(static::$env)), '_'.__FUNCTION__) && \is_callable([$this->container->get('Mod\\'.ucfirst((string) $modName).'\\'.ucfirst(static::$env)), '_'.__FUNCTION__])) {
                                $this->container->get('Mod\\'.ucfirst((string) $modName).'\\'.ucfirst(static::$env))->controller = $controller;
                                $this->container->get('Mod\\'.ucfirst((string) $modName).'\\'.ucfirst(static::$env))->action = $route->getArgument('action') ?? 'index';
                                $this->container->get('Mod\\'.ucfirst((string) $modName).'\\'.ucfirst(static::$env))->responseData = $this->responseData;

                                $return = \call_user_func_array([$this->container->get('Mod\\'.ucfirst((string) $modName).'\\'.ucfirst(static::$env)), '_'.__FUNCTION__], [$request, $response, &$args]); // <--

                                if (null !== $return) {
                                    return $return;
                                }

                                $this->responseData = $this->container->get('Mod\\'.ucfirst((string) $modName).'\\'.ucfirst(static::$env))->responseData;

                                ++$args['_lastEventId'];

                                if (!$this->auth->hasIdentity()) {
                                    break;
                                }
                            }
                        }
                    }

                    // this can be useful as a keep-alive mechanism if messages might not be sent regularly
                    // 2 kB padding for IE
                    // https://stackoverflow.com/a/29970623/3929620
                    $buffer = ':'.str_repeat(' ', 1024 * 64).PHP_EOL;
                    $buffer .= PHP_EOL;

                    if (\count($this->responseData) > 0) {
                        foreach ($this->responseData as $lastEventId => $row) {
                            $buffer .= 'id: '.$lastEventId.PHP_EOL;

                            // https://stackoverflow.com/a/74497710/3929620
                            // The reconnection time. If the connection to the server is lost,
                            // the browser will wait for the specified time before attempting to reconnect.
                            // This must be an integer, specifying the reconnection time in milliseconds.
                            // If a non-integer value is specified, the field is ignored.
                            // $buffer .= 'retry: 2000'.PHP_EOL;

                            foreach ($row as $key => $val) {
                                $buffer .= $key.': ';

                                if (\is_array($val)) {
                                    $buffer .= $this->helper->Nette()->Json()->encode($val);
                                } else {
                                    $buffer .= $this->helper->Strings()->linearize($val);
                                }

                                $buffer .= PHP_EOL;
                            }

                            $buffer .= PHP_EOL;
                        }
                    }

                    echo $buffer;

                    ++$i;

                    // try to reduce memory leaks..
                    if (0 === $i % 1000) {
                        gc_collect_cycles();
                        $i = 1;
                    }

                    if (!$this->auth->hasIdentity()) {
                        break;
                    }

                    sleep(2);
                }

                // https://github.com/phpro/grumphp/blob/master/doc/tasks/phpparser.md#no_exit_statements
                exit;

                /*$response->getBody()->write($buffer);

                return $response
                    ->withHeader('Content-Type', 'text/event-stream')
                    ->withAddedHeader('Cache-Control', 'no-store,no-cache')
                    // ->withAddedHeader('Access-Control-Allow-Origin', '*')
                    ->withStatus($this->statusCode)
                    ;*/
            }
        }
    }
}
