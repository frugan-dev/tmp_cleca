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

namespace App\Controller\Mod\Catuser\Back;

use Laminas\Stdlib\ArrayUtils;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Shudrum\Component\ArrayFinder\ArrayFinder;

trait CatuserActionTrait
{
    public function actionView(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->_actionGlobal($request, $response, $args);

        return parent::actionView($request, $response, $args);
    }

    public function actionAdd(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->_actionGlobal($request, $response, $args);

        return parent::actionAdd($request, $response, $args);
    }

    public function actionEdit(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->_actionGlobal($request, $response, $args);

        return parent::actionEdit($request, $response, $args);
    }

    public function actionDelete(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->_actionGlobal($request, $response, $args);

        return parent::actionDelete($request, $response, $args);
    }

    public function actionViewApi(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->_actionGlobal($request, $response, $args);

        ksort($this->mods);

        $this->metaTitle = __('API Swagger');

        $this->breadcrumb->removeAll();

        $result = [];

        $result['openapi'] = '3.0.3';

        $result['info'] = [
            'version' => 'v'.$this->config['api.version'],
            'title' => __('API Swagger'),
        ];

        if (!empty($this->lang->arr[$this->lang->id]['helpUrl'])) {
            // $result['externalDocs']['description'] = __('Documentation');
            $result['externalDocs']['url'] = $this->lang->arr[$this->lang->id]['helpUrl'];
        }

        $result['servers'] = [
            [
                'url' => $this->helper->Url()->urlFor([
                    'routeName' => 'api',
                    'full' => true,
                ]),
            ],
            /*[
                'url' => $this->helper->Url()->urlFor([
                    'routeName' => 'api',
                    'data' => [
                        'v' => 1,
                    ],
                    'full' => true,
                ]),
            ],*/
        ];

        // https://github.com/swagger-api/swagger-ui/issues/4833#issuecomment-415717449
        $result['security'] = [
            [
                'ApiKeyAuth' => [],
            ],
        ];

        $result['components'] = [
            'securitySchemes' => [
                'ApiKeyAuth' => [
                    'type' => 'apiKey',
                    'name' => $this->config['api.headers.key'],
                    'in' => 'header',
                ],
            ],
            // https://swagger.io/docs/specification/describing-responses/
            'responses' => [
                'OK' => [
                    'description' => 'OK',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/OK',
                            ],
                        ],
                        'application/xml' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/OK',
                            ],
                        ],
                    ],
                ],
                'Download' => [
                    'description' => 'Download',
                    'content' => [
                        '*/*' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Download',
                            ],
                        ],
                    ],
                ],
                'BadRequest' => [
                    'description' => 'Bad request',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Error',
                            ],
                        ],
                    ],
                ],
                'Gone' => [
                    'description' => 'Gone',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Error',
                            ],
                        ],
                    ],
                ],
                'MethodNotAllowed' => [
                    'description' => 'Method not allowed',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Error',
                            ],
                        ],
                    ],
                ],
                'Forbidden' => [
                    'description' => 'Forbidden',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Error',
                            ],
                        ],
                    ],
                ],
                'NotFound' => [
                    'description' => 'Not found',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Error',
                            ],
                        ],
                    ],
                ],
                'NotImplemented' => [
                    'description' => 'Not implemented',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Error',
                            ],
                        ],
                    ],
                ],
                'Unauthorized' => [
                    'description' => 'Unauthorized',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Error',
                            ],
                        ],
                    ],
                ],
                'InternalServerError' => [
                    'description' => 'Internal server error',
                    'content' => [
                        'application/json' => [
                            'schema' => [
                                '$ref' => '#/components/schemas/Exception',
                            ],
                        ],
                    ],
                ],
            ],
            'schemas' => [
                'OK' => [
                    'type' => 'object',
                    'properties' => [
                        'response' => [
                            // https://stackoverflow.com/a/43936151/3929620
                            'anyOf' => [
                                [
                                    'type' => 'boolean',
                                ],
                                [
                                    'type' => 'integer',
                                ],
                                [
                                    'type' => 'string',
                                ],
                                [
                                    'type' => 'array',
                                    'items' => [
                                        // https://stackoverflow.com/a/43936151/3929620
                                        'anyOf' => [
                                            [
                                                'type' => 'boolean',
                                            ],
                                            [
                                                'type' => 'integer',
                                            ],
                                            [
                                                'type' => 'string',
                                            ],
                                            [
                                                'type' => 'array',
                                                'items' => [],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'errors' => [
                            'type' => 'array',
                            'items' => [
                                // https://stackoverflow.com/a/43936151/3929620
                                'anyOf' => [
                                    [
                                        'type' => 'string',
                                    ],
                                    [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'integer',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'Download' => [
                    'type' => 'string',
                    'format' => 'binary',
                ],
                'Error' => [
                    'type' => 'object',
                    'properties' => [
                        'errors' => [
                            'type' => 'array',
                            'required' => true,
                            'items' => [
                                // https://stackoverflow.com/a/43936151/3929620
                                'anyOf' => [
                                    [
                                        'type' => 'string',
                                    ],
                                    [
                                        'type' => 'array',
                                        'items' => [
                                            'type' => 'integer',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        if ($this->config['debug.enabled']
            && $this->config['debug.whoops.enabled']) {
            $result['components']['schemas']['Exception'] = [
                'type' => 'object',
                'properties' => [
                    'error' => [
                        'type' => 'array',
                        'required' => true,
                        'items' => [
                            // https://stackoverflow.com/a/43936151/3929620
                            'anyOf' => [
                                [
                                    'type' => 'string',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        } else {
            $result['components']['schemas']['Exception'] = [
                'type' => 'object',
                'properties' => [
                    'message' => [
                        'type' => 'string',
                        'required' => true,
                    ],
                    'exception' => [
                        'type' => 'array',
                        'required' => $this->config['debug.errorDetails'],
                        'items' => [
                            // https://stackoverflow.com/a/43936151/3929620
                            'anyOf' => [
                                [
                                    'type' => 'string',
                                ],
                                [
                                    'type' => 'integer',
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }

        $result['tags'] = [];
        $result['paths'] = [];

        if (!empty($browscapInfo = $request->getAttribute('browscapInfo'))) {
            if (!empty($browscapInfo->ismobiledevice)) {
                $ismobiledevice = true;
            }
        }

        $resultArrayFinder = new ArrayFinder($result);
        $resultArrayFinder->changeSeparator('/');

        foreach ($this->mods as $controller => $row) {
            $allow = false;

            foreach ($row['apis'] as $method => $specs) {
                if (\is_array($specs)) {
                    ksort($specs);
                    foreach ($specs as $endpoint => $spec) {
                        if (!empty($spec['_perms']) && \is_array($spec['_perms'])) {
                            foreach ($spec['_perms'] as $perm) {
                                if ($this->rbac->isGranted($controller.'.api.'.$perm)) {
                                    $allow = true;

                                    // https://editor.swagger.io
                                    // should NOT have additional properties
                                    unset($spec['_perms']);

                                    $spec['tags'][] = $row['pluralName'];

                                    if (!empty($spec['summary'])) {
                                        $spec['summary'] = __($spec['summary']);

                                        if (!empty($ismobiledevice)) {
                                            $spec['description'] = $spec['summary'].(!empty($spec['description']) ? nl2br(PHP_EOL.PHP_EOL).$spec['description'] : '');
                                            unset($spec['summary']);
                                        }
                                    }

                                    // https://swagger.io/docs/specification/describing-responses/
                                    // https://stackoverflow.com/a/50476201/3929620
                                    // https://github.com/OAI/OpenAPI-Specification/issues/690
                                    // Note that, currently, OpenAPI Specification does not permit to define common response headers
                                    // for different response codes or different API operations.
                                    // You need to define the headers for each response individually.
                                    if (!empty($spec['responses'])) {
                                        foreach ($spec['responses'] as $statuCode => $resp) {
                                            if (isset($resp['$ref']) && \in_array($resp['$ref'], [
                                                '#/components/responses/OK',
                                                '#/components/responses/Forbidden',
                                            ], true)) {
                                                unset($spec['responses'][$statuCode]['$ref']);
                                                $spec['responses'][$statuCode] = $resultArrayFinder->get(ltrim($resp['$ref'], '#/'));

                                                $spec['responses'][$statuCode]['headers'] = ArrayUtils::merge($resp['headers'] ?? [], [
                                                    'X-RateLimit-Daily-Limit' => [
                                                        'schema' => [
                                                            'type' => 'integer',
                                                        ],
                                                        'description' => 'Request limit per day',
                                                    ],
                                                    'X-RateLimit-Daily-Used' => [
                                                        'schema' => [
                                                            'type' => 'integer',
                                                        ],
                                                        'description' => 'Request used per day',
                                                    ],
                                                    'X-RateLimit-Hourly-Limit' => [
                                                        'schema' => [
                                                            'type' => 'integer',
                                                        ],
                                                        'description' => 'Request limit per hour',
                                                    ],
                                                    'X-RateLimit-Hourly-Used' => [
                                                        'schema' => [
                                                            'type' => 'integer',
                                                        ],
                                                        'description' => 'Request used per hour',
                                                    ],
                                                ]/* , true */); // <-- with preserveNumericKeys
                                            }
                                        }
                                    }

                                    if (!empty($spec['parameters']) && false !== ($parentKey = $this->helper->Arrays()->recursiveArraySearch('name', 'lang', $spec['parameters'], true))) {
                                        if (!empty($this->container->get('Mod\\'.ucfirst((string) $controller.'\\'.ucfirst((string) static::$env)))->fieldsMultilang)) {
                                            if (str_contains((string) $endpoint, '/{pg}')) {
                                                $endpoint = str_replace('/{pg}', '', $endpoint).'/lang/{lang}/{pg}';
                                            } else {
                                                $endpoint .= '/lang/{lang}';
                                            }
                                        } else {
                                            unset($spec['parameters'][$parentKey]);
                                            $spec['parameters'] = array_values($spec['parameters']);
                                        }
                                    }

                                    $result['paths']['/'.$controller.$endpoint][$method] = $spec;

                                    break;
                                }
                            }
                        }
                    }
                }
            }

            if (!empty($allow)) {
                $result['tags'][] = [
                    'name' => $row['pluralName'],
                ];
            }
        }

        $this->viewData = array_merge(
            $this->viewData,
            ['backButtonUrl' => 'javascript:history.back()', ...compact( // https://stackoverflow.com/a/30266377/3929620
                'result',
            )]
        );
    }
}
