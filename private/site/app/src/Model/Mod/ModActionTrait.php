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

namespace App\Model\Mod;

use App\Factory\Html\ViewHelperInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Psr7\Factory\StreamFactory;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\Cache\ItemInterface;
use WhiteHat101\Crypt\APR1_MD5;

trait ModActionTrait
{
    public function actionIndex(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->title = $this->metaTitle = \sprintf(__('List %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName));

        if ('POST' === $request->getMethod()) {
            $this->check(
                $request,
                function (): void {
                    $this->filterSubject->sanitize('action')->toBlankOr('trim');
                },
                function (): void {
                    $this->filterSubject->validate('action')->isNotBlank()->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.__('Action').'</i>'));
                }
            );

            if (0 === \count($this->errors)) {
                $actionCamelCase = $this->postData['action'];

                $this->filterValue->sanitize($actionCamelCase, 'string', ['_', '-'], ' ');
                $this->filterValue->sanitize($actionCamelCase, 'titlecase');
                $this->filterValue->sanitize($actionCamelCase, 'lowercaseFirst');
                $this->filterValue->sanitize($actionCamelCase, 'string', ' ', '');

                if (method_exists($this, '_actionPost'.ucfirst((string) $actionCamelCase)) && \is_callable([$this, '_actionPost'.ucfirst((string) $actionCamelCase)])) {
                    $return = \call_user_func_array([$this, '_actionPost'.ucfirst((string) $actionCamelCase)], [$request, $response, $args]);

                    if (null !== $return) {
                        return $return;
                    }
                } else {
                    $this->errors[] = __('A technical problem has occurred, try again later.');
                }
            }

            if (0 === \count($this->errors)) {
                return $response
                    ->withHeader('Location', $this->helper->Url()->urlFor([
                        'routeName' => static::$env.'.'.$this->controller.'.params',
                        'data' => [
                            'action' => 'index',
                            'params' => implode(
                                '/',
                                array_merge(
                                    $this->session->get('routeParamsArrWithoutPg', []),
                                    [$this->session->get('pg', $this->pager->pg)]
                                )
                            ),
                        ],
                    ]))
                    // https://stackoverflow.com/a/6788439/3929620
                    // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                    ->withStatus(303)
                ;
            }

            if (\count($this->errors) > 0) {
                $this->session->addFlash([
                    'type' => 'toast',
                    'options' => [
                        'type' => 'danger',
                        'message' => current($this->errors),
                    ],
                ]);

                $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                    'error' => var_export($this->errors, true),
                ]);
            }
        }

        $this->setRouteParams();

        $this->pager->create($this->getCount([
            'active' => $this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete') ? null : true,
        ]), $this->config['mod.'.static::$env.'.'.$this->modName.'.pagination.rowPerPage'] ?? $this->config['mod.'.$this->modName.'.pagination.rowPerPage'] ?? null);

        $this->setPagerAndOrder();

        $routeParamsArr = $this->routeParamsArr;

        if (($paramId = array_search('parent_id', $routeParamsArr, true)) !== false) {
            unset($routeParamsArr[$paramId]);
            if (isset($routeParamsArr[$paramId + 1])) {
                unset($routeParamsArr[$paramId + 1]);
            }
        }

        foreach ($this->lang->codeArr as $langId => $langCode) {
            $this->routeArgsArr[$langId] = [
                'routeName' => static::$env.'.'.$this->modName.'.params',
                'data' => [
                    'lang' => $langCode,
                    'action' => $this->action,
                    'params' => implode('/', $this->routeParamsArr),
                ],
            ];
        }

        $this->routeArgs = $this->routeArgsArr[$this->lang->id];

        if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
            $this->acceptRouteArgs = [
                'routeName' => static::$env.'.'.$this->modName.'.params',
                'data' => [
                    'lang' => $this->lang->acceptCode,
                    'action' => $this->action,
                    'params' => implode('/', $this->routeParamsArr),
                ],
                'full' => true,
            ];
        }

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        }

        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                $this->title,
                $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
            ),
            $this->helper->Url()->urlFor([
                'routeName' => static::$env.'.'.$this->controller.'.params',
                'data' => [
                    'action' => 'index',
                    'params' => implode('/', $routeParamsArr),
                ],
            ])
        );

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.breadcrumb');

        if (($filterData = $this->session->get(static::$env.'.'.$this->auth->getIdentity()['id'].'.filterData')) !== null) {
            if (!empty($filterData[$this->controller][$this->action])) {
                $this->filterData = $filterData[$this->controller][$this->action];
            }
        }

        if (!empty($sessionData = $this->session->get($this->auth->getIdentity()['id'].'.sessionData'))) {
            if (!empty($sessionData[$this->controller][$this->action])) {
                $this->sessionData = $sessionData[$this->controller][$this->action];

                $this->session->addFlash([
                    'type' => 'alert',
                    'options' => [
                        'env' => static::$env, // <-
                        'type' => 'info',
                        'message' => \sprintf(__('Search text: %1$s'), '<i>'.($sessionData[$this->controller][$this->action]['_search'] ?? '').'</i>'),
                        'postXhrDismissible' => [
                            [
                                'type' => 'input',
                                'attr' => [
                                    'type' => 'hidden',
                                    'name' => 'controller',
                                    'value' => $this->controller,
                                ],
                            ],
                            [
                                'type' => 'input',
                                'attr' => [
                                    'type' => 'hidden',
                                    'name' => 'action',
                                    'value' => 'reset',
                                ],
                            ],
                        ],
                    ],
                ]);
            }
        }

        if ($this->pager->totRows > 0) {
            $bulkActions = [];

            foreach ($this->bulkActions as $key => $val) {
                if ($this->rbac->isGranted($this->controller.'.'.static::$env.'.'.$val)) {
                    $bulkActions[] = $val;
                }
            }

            $result = $this->getAll(
                [
                    'order' => $this->internalOrder,
                    'offset' => $this->offset,
                    'nRows' => $this->pager->rowPerPage,
                    'active' => $this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete') ? null : true,
                ]
            );

            if (\count($result) > 0) {
                $this->viewData = array_merge(
                    $this->viewData,
                    ['pagination' => $this->pager->build(), ...compact( // https://stackoverflow.com/a/30266377/3929620
                        'result',
                        'bulkActions'
                    )]
                );
            }
        }
    }

    public function actionView(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->exist()) {
            $this->setFields();

            $this->title = $this->metaTitle = \sprintf(__('Detail %1$s'), $this->helper->Nette()->Strings()->lower($this->singularName));

            $routeParamsArr = $this->session->get('routeParamsArrWithoutPg', []);

            if (($paramId = array_search('parent_id', $routeParamsArr, true)) !== false) {
                unset($routeParamsArr[$paramId]);
                if (isset($routeParamsArr[$paramId + 1])) {
                    unset($routeParamsArr[$paramId + 1]);
                }
            }

            foreach ($this->lang->codeArr as $langId => $langCode) {
                $this->routeArgsArr[$langId] = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $langCode,
                        'action' => $this->action,
                        'params' => $this->id,
                    ],
                ];
            }

            $this->routeArgs = $this->routeArgsArr[$this->lang->id];

            if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
                $this->acceptRouteArgs = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $this->lang->acceptCode,
                        'action' => $this->action,
                        'params' => $this->id,
                    ],
                    'full' => true,
                ];
            }

            if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.index')) {
                $this->breadcrumb->add(
                    $this->helper->Nette()->Strings()->truncate(
                        \sprintf(__('List %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName)),
                        $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                    ),
                    $this->helper->Url()->urlFor([
                        'routeName' => static::$env.'.'.$this->controller.'.params',
                        'data' => [
                            'action' => 'index',
                            'params' => implode(
                                '/',
                                array_merge(
                                    $routeParamsArr,
                                    [$this->session->get('pg', $this->pager->pg)]
                                )
                            ),
                        ],
                    ])
                );
            }

            $this->dispatcher->dispatch(new GenericEvent(arguments: [
                'id' => $this->id,
            ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.breadcrumb');

            if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            }

            $this->breadcrumb->add(
                $this->helper->Nette()->Strings()->truncate(
                    $this->title,
                    $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                ),
                $this->helper->Url()->urlFor($this->routeArgs)
            );
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    public function actionAdd(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->title = $this->metaTitle = \sprintf(_x('New %1$s', $this->context), $this->helper->Nette()->Strings()->lower($this->singularName));

        __('New %1$s', 'default');
        __('New %1$s', 'male');
        __('New %1$s', 'female');

        foreach ($this->lang->codeArr as $langId => $langCode) {
            $this->routeArgsArr[$langId] = [
                'routeName' => static::$env.'.'.$this->modName,
                'data' => [
                    'lang' => $langCode,
                    'action' => $this->action,
                ],
            ];
        }

        $this->routeArgs = $this->routeArgsArr[$this->lang->id];

        if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
            $this->acceptRouteArgs = [
                'routeName' => static::$env.'.'.$this->modName,
                'data' => [
                    'lang' => $this->lang->acceptCode,
                    'action' => $this->action,
                ],
                'full' => true,
            ];
        }

        if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.index')) {
            $this->breadcrumb->add(
                $this->helper->Nette()->Strings()->truncate(
                    \sprintf(__('List %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName)),
                    $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                ),
                $this->helper->Url()->urlFor([
                    'routeName' => static::$env.'.'.$this->controller.'.params',
                    'data' => [
                        'action' => 'index',
                        'params' => implode(
                            '/',
                            array_merge(
                                $this->session->get('routeParamsArrWithoutPg', []),
                                [$this->session->get('pg', $this->pager->pg)]
                            )
                        ),
                    ],
                ])
            );
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.breadcrumb');

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        }

        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                $this->title,
                $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
            ),
            $this->helper->Url()->urlFor($this->routeArgs)
        );

        if ('POST' === $request->getMethod()) {
            $this->check($request);

            if (0 === \count($this->errors)) {
                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                $insertId = $this->dbAdd();

                $this->dispatcher->dispatch(new GenericEvent(arguments: [
                    'id' => $insertId,
                ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                if (0 === \count($this->errors)) {
                    $this->logger->info(\sprintf(_x('Added %1$s #%2$d', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $insertId));

                    __('Added %1$s #%2$d', 'default');
                    __('Added %1$s #%2$d', 'male');
                    __('Added %1$s #%2$d', 'female');

                    $this->session->addFlash([
                        'type' => 'toast',
                        'options' => [
                            'type' => 'success',
                            'message' => \sprintf(_x('%1$s successfully added.', $this->context), $this->singularName),
                        ],
                    ]);

                    __('%1$s successfully added.', 'default');
                    __('%1$s successfully added.', 'male');
                    __('%1$s successfully added.', 'female');

                    return $response
                        ->withHeader('Location', $this->helper->Url()->urlFor([
                            'routeName' => static::$env.'.'.$this->controller.'.params',
                            'data' => [
                                'action' => 'index',
                                'params' => implode(
                                    '/',
                                    array_merge(
                                        $this->session->get('routeParamsArrWithoutPg', []),
                                        [$this->session->get('pg', $this->pager->pg)]
                                    )
                                ),
                            ],
                        ]))
                        // https://stackoverflow.com/a/6788439/3929620
                        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                        ->withStatus(303)
                    ;
                }
            }

            if (\count($this->errors) > 0) {
                $this->session->addFlash([
                    'type' => 'toast',
                    'options' => [
                        'type' => 'danger',
                        'message' => current($this->errors),
                    ],
                ]);

                $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                    'error' => var_export($this->errors, true),
                ]);
            }
        }
    }

    public function actionEdit(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->exist()) {
            $this->setFields();

            if (\in_array($this->controller, [$this->auth->getIdentity()['_type']], true) && $this->auth->getIdentity()['id'] === $this->id) {
                $this->title = $this->metaTitle = \sprintf(__('Edit %1$s'), $this->helper->Nette()->Strings()->lower(__('Account')));

                $message = \sprintf(__('%1$s successfully modified.', 'default'), __('Account'));

                $logMessage = \sprintf(_x('Modified %1$s #%2$d', 'default', $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower(__('Account')), $this->id);

                $redirect = $this->helper->Url()->getPathUrl();
            } else {
                $this->title = $this->metaTitle = \sprintf(__('Edit %1$s'), $this->helper->Nette()->Strings()->lower($this->singularName));

                $message = \sprintf(_x('%1$s successfully modified.', $this->context), $this->singularName);

                $logMessage = \sprintf(_x('Modified %1$s #%2$d', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $this->id);

                $redirect = $this->helper->Url()->urlFor([
                    'routeName' => static::$env.'.'.$this->controller.'.params',
                    'data' => [
                        'action' => 'index',
                        'params' => implode(
                            '/',
                            array_merge(
                                $this->session->get('routeParamsArrWithoutPg', []),
                                [$this->session->get('pg', $this->pager->pg)]
                            )
                        ),
                    ],
                ]);
            }

            __('%1$s successfully modified.', 'default');
            __('%1$s successfully modified.', 'male');
            __('%1$s successfully modified.', 'female');

            __('Modified %1$s #%2$d', 'default');
            __('Modified %1$s #%2$d', 'male');
            __('Modified %1$s #%2$d', 'female');

            $backButton = $this->rbac->isGranted($this->modName.'.'.static::$env.'.index') ? true : false;

            $this->viewData = array_merge(
                $this->viewData,
                compact( // https://stackoverflow.com/a/30266377/3929620
                    'backButton'
                )
            );

            foreach ($this->lang->codeArr as $langId => $langCode) {
                $this->routeArgsArr[$langId] = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $langCode,
                        'action' => $this->action,
                        'params' => $this->id,
                    ],
                ];
            }

            $this->routeArgs = $this->routeArgsArr[$this->lang->id];

            if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
                $this->acceptRouteArgs = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $this->lang->acceptCode,
                        'action' => $this->action,
                        'params' => $this->id,
                    ],
                    'full' => true,
                ];
            }

            if (!empty($backButton)) {
                $routeParamsArr = $this->session->get('routeParamsArrWithoutPg', []);

                if (($paramId = array_search('parent_id', $routeParamsArr, true)) !== false) {
                    unset($routeParamsArr[$paramId]);
                    if (isset($routeParamsArr[$paramId + 1])) {
                        unset($routeParamsArr[$paramId + 1]);
                    }
                }

                $this->breadcrumb->add(
                    $this->helper->Nette()->Strings()->truncate(
                        \sprintf(__('List %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName)),
                        $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                    ),
                    $this->helper->Url()->urlFor([
                        'routeName' => static::$env.'.'.$this->controller.'.params',
                        'data' => [
                            'action' => 'index',
                            'params' => implode(
                                '/',
                                array_merge(
                                    $routeParamsArr,
                                    [$this->session->get('pg', $this->pager->pg)]
                                )
                            ),
                        ],
                    ])
                );

                $this->dispatcher->dispatch(new GenericEvent(arguments: [
                    'id' => $this->id,
                ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.breadcrumb');
            }

            if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            }

            $this->breadcrumb->add(
                $this->helper->Nette()->Strings()->truncate(
                    $this->title,
                    $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                ),
                $this->helper->Url()->urlFor($this->routeArgs)
            );

            if ('POST' === $request->getMethod()) {
                $this->check($request);

                if (0 === \count($this->errors)) {
                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                    $return = $this->dbEdit();

                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                    if (0 === \count($this->errors)) {
                        if (\in_array($this->controller, [$this->auth->getIdentity()['_type']], true) && $this->auth->getIdentity()['id'] === $this->id) {
                            if ($this->postData['lang_id'] !== $this->lang->id) {
                                $redirect = \Safe\preg_replace(
                                    '~\/('.$this->lang->code.')(\/?$|\/*)~',
                                    '/'.$this->lang->arr[$this->postData['lang_id']]['isoCode'].'$2',
                                    (string) $redirect
                                );

                                $this->translator->prepare($this->postData['lang_id']);

                                $message = \sprintf(__('%1$s successfully modified.', 'default'), __('Account'));
                            }
                        }

                        $this->logger->info($logMessage);

                        $this->session->addFlash([
                            'type' => 'toast',
                            'options' => [
                                'type' => 'success',
                                'message' => $message,
                            ],
                        ]);

                        return $response
                            ->withHeader('Location', $redirect)
                            // https://stackoverflow.com/a/6788439/3929620
                            // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                            ->withStatus(303)
                        ;
                    }
                }

                if (\count($this->errors) > 0) {
                    $this->session->addFlash([
                        'type' => 'toast',
                        'options' => [
                            'type' => 'danger',
                            'message' => current($this->errors),
                        ],
                    ]);

                    $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                        'error' => var_export($this->errors, true),
                    ]);
                }
            }
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    public function actionDelete(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->exist()) {
            $this->setFields();

            $this->title = $this->metaTitle = \sprintf(__('Deletion %1$s'), $this->helper->Nette()->Strings()->lower($this->singularName));

            $routeParamsArr = $this->session->get('routeParamsArrWithoutPg', []);

            if (($paramId = array_search('parent_id', $routeParamsArr, true)) !== false) {
                unset($routeParamsArr[$paramId]);
                if (isset($routeParamsArr[$paramId + 1])) {
                    unset($routeParamsArr[$paramId + 1]);
                }
            }

            foreach ($this->lang->codeArr as $langId => $langCode) {
                $this->routeArgsArr[$langId] = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $langCode,
                        'action' => $this->action,
                        'params' => $this->id,
                    ],
                ];
            }

            $this->routeArgs = $this->routeArgsArr[$this->lang->id];

            if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
                $this->acceptRouteArgs = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $this->lang->acceptCode,
                        'action' => $this->action,
                        'params' => $this->id,
                    ],
                    'full' => true,
                ];
            }

            if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.index')) {
                $this->breadcrumb->add(
                    $this->helper->Nette()->Strings()->truncate(
                        \sprintf(__('List %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName)),
                        $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                    ),
                    $this->helper->Url()->urlFor([
                        'routeName' => static::$env.'.'.$this->controller.'.params',
                        'data' => [
                            'action' => 'index',
                            'params' => implode(
                                '/',
                                array_merge(
                                    $routeParamsArr,
                                    [$this->session->get('pg', $this->pager->pg)]
                                )
                            ),
                        ],
                    ])
                );
            }

            $this->dispatcher->dispatch(new GenericEvent(arguments: [
                'id' => $this->id,
            ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.breadcrumb');

            if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            }

            $this->breadcrumb->add(
                $this->helper->Nette()->Strings()->truncate(
                    $this->title,
                    $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                ),
                $this->helper->Url()->urlFor($this->routeArgs)
            );

            $this->checkDeps($request);

            if (0 === \count($this->errorDeps)) {
                if ('POST' === $request->getMethod()) {
                    $this->check($request);

                    if (0 === \count($this->errors)) {
                        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                        $return = $this->dbDelete();

                        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                        if (0 === \count($this->errors)) {
                            $this->logger->notice(\sprintf(_x('Deleted %1$s #%2$d', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $this->id));

                            __('Deleted %1$s #%2$d', 'default');
                            __('Deleted %1$s #%2$d', 'male');
                            __('Deleted %1$s #%2$d', 'female');

                            $this->session->addFlash([
                                'type' => 'toast',
                                'options' => [
                                    'type' => 'success',
                                    'message' => \sprintf(_nx('%1$s successfully deleted.', '%1$s successfully deleted.', 1, $this->context), $this->singularName),
                                ],
                            ]);

                            _n('%1$s successfully deleted.', '%1$s successfully deleted.', 1, 'default');
                            _n('%1$s successfully deleted.', '%1$s successfully deleted.', 1, 'male');
                            _n('%1$s successfully deleted.', '%1$s successfully deleted.', 1, 'female');

                            return $response
                                ->withHeader('Location', $this->helper->Url()->urlFor([
                                    'routeName' => static::$env.'.'.$this->controller.'.params',
                                    'data' => [
                                        'action' => 'index',
                                        'params' => implode(
                                            '/',
                                            array_merge(
                                                $this->session->get('routeParamsArrWithoutPg', []),
                                                [$this->session->get('pg', $this->pager->pg)]
                                            )
                                        ),
                                    ],
                                ]))
                                // https://stackoverflow.com/a/6788439/3929620
                                // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                                ->withStatus(303)
                            ;
                        }
                    }

                    if (\count($this->errors) > 0) {
                        $this->session->addFlash([
                            'type' => 'toast',
                            'options' => [
                                'type' => 'danger',
                                'message' => current($this->errors),
                            ],
                        ]);

                        $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                            'error' => var_export($this->errors, true),
                        ]);
                    }
                }

                $message = \sprintf(_nx('Do you confirm the deletion of the following %1$s?', 'Do you confirm the deletion of the following %1$s?', 1, $this->context), $this->helper->Nette()->Strings()->lower($this->singularName));

                _n('Do you confirm the deletion of the following %1$s?', 'Do you confirm the deletion of the following %1$s?', 1, 'default');
                _n('Do you confirm the deletion of the following %1$s?', 'Do you confirm the deletion of the following %1$s?', 1, 'male');
                _n('Do you confirm the deletion of the following %1$s?', 'Do you confirm the deletion of the following %1$s?', 1, 'female');
            } else {
                $message = __('To be able to perform this operation, the dependencies must be resolved first.');
            }

            $this->session->addFlash([
                'type' => 'alert',
                'options' => [
                    'env' => static::$env, // <-
                    'type' => 'warning',
                    'message' => $message,
                    'dismissible' => false,
                ],
            ]);
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    public function actionCopy(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->exist()) {
            $this->setFields();

            $this->title = $this->metaTitle = \sprintf(__('Copy %1$s'), $this->helper->Nette()->Strings()->lower($this->singularName));

            $routeParamsArr = $this->session->get('routeParamsArrWithoutPg', []);

            if (($paramId = array_search('parent_id', $routeParamsArr, true)) !== false) {
                unset($routeParamsArr[$paramId]);
                if (isset($routeParamsArr[$paramId + 1])) {
                    unset($routeParamsArr[$paramId + 1]);
                }
            }

            foreach ($this->lang->codeArr as $langId => $langCode) {
                $this->routeArgsArr[$langId] = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $langCode,
                        'action' => $this->action,
                        'params' => $this->id,
                    ],
                ];
            }

            $this->routeArgs = $this->routeArgsArr[$this->lang->id];

            if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
                $this->acceptRouteArgs = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $this->lang->acceptCode,
                        'action' => $this->action,
                        'params' => $this->id,
                    ],
                    'full' => true,
                ];
            }

            if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.index')) {
                $this->breadcrumb->add(
                    $this->helper->Nette()->Strings()->truncate(
                        \sprintf(__('List %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName)),
                        $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                    ),
                    $this->helper->Url()->urlFor([
                        'routeName' => static::$env.'.'.$this->controller.'.params',
                        'data' => [
                            'action' => 'index',
                            'params' => implode(
                                '/',
                                array_merge(
                                    $routeParamsArr,
                                    [$this->session->get('pg', $this->pager->pg)]
                                )
                            ),
                        ],
                    ])
                );
            }

            $this->dispatcher->dispatch(new GenericEvent(arguments: [
                'id' => $this->id,
            ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.breadcrumb');

            if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            }

            $this->breadcrumb->add(
                $this->helper->Nette()->Strings()->truncate(
                    $this->title,
                    $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                ),
                $this->helper->Url()->urlFor($this->routeArgs)
            );

            if ('POST' === $request->getMethod()) {
                $this->check($request);

                if (0 === \count($this->errors)) {
                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                    $return = $this->dbCopy();

                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                    if (0 === \count($this->errors)) {
                        $this->logger->notice(\sprintf(_x('Copied %1$s #%2$d', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $this->id));

                        __('Copied %1$s #%2$d', 'default');
                        __('Copied %1$s #%2$d', 'male');
                        __('Copied %1$s #%2$d', 'female');

                        $this->session->addFlash([
                            'type' => 'toast',
                            'options' => [
                                'type' => 'success',
                                'message' => \sprintf(_nx('%1$s successfully copied.', '%1$s successfully copied.', 1, $this->context), $this->singularName),
                            ],
                        ]);

                        _n('%1$s successfully copied.', '%1$s successfully copied.', 1, 'default');
                        _n('%1$s successfully copied.', '%1$s successfully copied.', 1, 'male');
                        _n('%1$s successfully copied.', '%1$s successfully copied.', 1, 'female');

                        return $response
                            ->withHeader('Location', $this->helper->Url()->urlFor([
                                'routeName' => static::$env.'.'.$this->controller.'.params',
                                'data' => [
                                    'action' => 'index',
                                    'params' => implode(
                                        '/',
                                        array_merge(
                                            $this->session->get('routeParamsArrWithoutPg', []),
                                            [$this->session->get('pg', $this->pager->pg)]
                                        )
                                    ),
                                ],
                            ]))
                            // https://stackoverflow.com/a/6788439/3929620
                            // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                            ->withStatus(303)
                        ;
                    }
                }

                if (\count($this->errors) > 0) {
                    $this->session->addFlash([
                        'type' => 'toast',
                        'options' => [
                            'type' => 'danger',
                            'message' => current($this->errors),
                        ],
                    ]);

                    $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                        'error' => var_export($this->errors, true),
                    ]);
                }
            }

            $message = \sprintf(_nx('Do you confirm the copy of the following %1$s?', 'Do you confirm the copy of the following %1$s?', 1, $this->context), $this->helper->Nette()->Strings()->lower($this->singularName));

            _n('Do you confirm the copy of the following %1$s?', 'Do you confirm the copy of the following %1$s?', 1, 'default');
            _n('Do you confirm the copy of the following %1$s?', 'Do you confirm the copy of the following %1$s?', 1, 'male');
            _n('Do you confirm the copy of the following %1$s?', 'Do you confirm the copy of the following %1$s?', 1, 'female');

            $this->session->addFlash([
                'type' => 'alert',
                'options' => [
                    'env' => static::$env, // <-
                    'type' => 'warning',
                    'message' => $message,
                    'dismissible' => false,
                ],
            ]);
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    public function actionExport(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->title = $this->metaTitle = \sprintf(__('Export %1$s', $this->context), $this->helper->Nette()->Strings()->lower($this->pluralName));

        foreach ($this->lang->codeArr as $langId => $langCode) {
            $this->routeArgsArr[$langId] = [
                'routeName' => static::$env.'.'.$this->modName,
                'data' => [
                    'lang' => $langCode,
                    'action' => $this->action,
                ],
            ];
        }

        $this->routeArgs = $this->routeArgsArr[$this->lang->id];

        if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
            $this->acceptRouteArgs = [
                'routeName' => static::$env.'.'.$this->modName,
                'data' => [
                    'lang' => $this->lang->acceptCode,
                    'action' => $this->action,
                ],
                'full' => true,
            ];
        }

        if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.index')) {
            $this->breadcrumb->add(
                $this->helper->Nette()->Strings()->truncate(
                    \sprintf(__('List %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName)),
                    $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                ),
                $this->helper->Url()->urlFor([
                    'routeName' => static::$env.'.'.$this->controller.'.params',
                    'data' => [
                        'action' => 'index',
                        'params' => implode(
                            '/',
                            array_merge(
                                $this->session->get('routeParamsArrWithoutPg', []),
                                [$this->session->get('pg', $this->pager->pg)]
                            )
                        ),
                    ],
                ])
            );
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.breadcrumb');

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        }

        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                $this->title,
                $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
            ),
            $this->helper->Url()->urlFor($this->routeArgs)
        );

        if ('POST' === $request->getMethod()) {
            $this->check($request);

            if (0 === \count($this->errors)) {
                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                if (method_exists($this, '_actionPost'.ucfirst((string) $this->actionCamelCase)) && \is_callable([$this, '_actionPost'.ucfirst((string) $this->actionCamelCase)])) {
                    $return = \call_user_func_array([$this, '_actionPost'.ucfirst((string) $this->actionCamelCase)], [$request, $response, $args]);

                    if (null !== $return) {
                        return $return;
                    }
                } else {
                    $this->errors[] = __('A technical problem has occurred, try again later.');
                }

                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                if (0 === \count($this->errors)) {
                    return $response
                        ->withHeader('Location', $this->helper->Url()->urlFor([
                            'routeName' => static::$env.'.'.$this->controller.'.params',
                            'data' => [
                                'action' => 'index',
                                'params' => implode(
                                    '/',
                                    array_merge(
                                        $this->session->get('routeParamsArrWithoutPg', []),
                                        [$this->session->get('pg', $this->pager->pg)]
                                    )
                                ),
                            ],
                        ]))
                        // https://stackoverflow.com/a/6788439/3929620
                        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                        ->withStatus(303)
                    ;
                }
            }

            if (\count($this->errors) > 0) {
                $this->session->addFlash([
                    'type' => 'toast',
                    'options' => [
                        'type' => 'danger',
                        'message' => current($this->errors),
                    ],
                ]);

                $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                    'error' => var_export($this->errors, true),
                ]);
            }
        }
    }

    public function actionDeleteBulk(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (!empty($sessionData = $this->session->get($this->auth->getIdentity()['id'].'.sessionData'))) {
            if (!empty($bulkIds = $sessionData[$this->controller][$this->action]['bulk_ids'])) {
                $totBulkIds = \count($bulkIds);

                $this->title = $this->metaTitle = \sprintf(__('Deletion %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName));

                foreach ($this->lang->codeArr as $langId => $langCode) {
                    $this->routeArgsArr[$langId] = [
                        'routeName' => static::$env.'.'.$this->modName,
                        'data' => [
                            'lang' => $langCode,
                            'action' => $this->action,
                        ],
                    ];
                }

                $this->routeArgs = $this->routeArgsArr[$this->lang->id];

                if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
                    $this->acceptRouteArgs = [
                        'routeName' => static::$env.'.'.$this->modName,
                        'data' => [
                            'lang' => $this->lang->acceptCode,
                            'action' => $this->action,
                        ],
                        'full' => true,
                    ];
                }

                if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.index')) {
                    $this->breadcrumb->add(
                        $this->helper->Nette()->Strings()->truncate(
                            \sprintf(__('List %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName)),
                            $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                        ),
                        $this->helper->Url()->urlFor([
                            'routeName' => static::$env.'.'.$this->controller.'.params',
                            'data' => [
                                'action' => 'index',
                                'params' => implode(
                                    '/',
                                    array_merge(
                                        $this->session->get('routeParamsArrWithoutPg', []),
                                        [$this->session->get('pg', $this->pager->pg)]
                                    )
                                ),
                            ],
                        ])
                    );
                }

                $this->dispatcher->dispatch(new GenericEvent(arguments: [
                    'ids' => $bulkIds,
                ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.breadcrumb');

                if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                    $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                    if (null !== $return) {
                        return $return;
                    }
                }

                $this->breadcrumb->add(
                    $this->helper->Nette()->Strings()->truncate(
                        $this->title,
                        $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                    ),
                    $this->helper->Url()->urlFor($this->routeArgs)
                );

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
                    if ('POST' === $request->getMethod()) {
                        $errors = [];

                        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                        foreach ($bulkIds as $bulkId) {
                            $this->errors = [];

                            $this->setId($bulkId);

                            if ($this->exist()) {
                                $this->setFields();

                                $this->check($request);

                                if (0 === \count($this->errors)) {
                                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionDelete.before');

                                    $return = $this->dbDelete();

                                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionDelete.after');

                                    if (\count($this->errors) > 0) {
                                        $errorBulksIds[] = $bulkId;
                                    }
                                } else {
                                    $errorBulksIds[] = $bulkId;
                                }
                            } else {
                                $errorBulksIds[] = $bulkId;

                                $this->errors[] = __('A technical problem has occurred, try again later.');
                            }

                            $errors = array_merge($errors, $this->errors);
                        }

                        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                        $this->errors = $errors;

                        $deletedBulkIds = array_diff($bulkIds, $errorBulksIds);

                        $totDeletedBulkIds = \count($deletedBulkIds);

                        if ($totDeletedBulkIds > 0) {
                            $this->logger->notice(\sprintf(__('Bulk %1$s of %2$d %3$s', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower(__('Deletion')), $totDeletedBulkIds, $this->helper->Nette()->Strings()->lower(1 === $totDeletedBulkIds ? $this->singularNameWithParams : $this->pluralNameWithParams)), [
                                'text' => implode(PHP_EOL, array_map(fn ($item) => '#'.$item, $deletedBulkIds)),
                            ]);
                        }

                        if (0 === \count($this->errors)) {
                            $this->session->addFlash([
                                'type' => 'toast',
                                'options' => [
                                    'type' => 'success',
                                    'message' => \sprintf(_nx('%1$s successfully deleted.', '%1$s successfully deleted.', $totBulkIds, $this->context), 1 === $totBulkIds ? $this->singularName : $this->pluralName),
                                ],
                            ]);

                            _n('%1$s successfully deleted.', '%1$s successfully deleted.', 1, 'default');
                            _n('%1$s successfully deleted.', '%1$s successfully deleted.', 1, 'male');
                            _n('%1$s successfully deleted.', '%1$s successfully deleted.', 1, 'female');

                            return $response
                                ->withHeader('Location', $this->helper->Url()->urlFor([
                                    'routeName' => static::$env.'.'.$this->controller.'.params',
                                    'data' => [
                                        'action' => 'index',
                                        'params' => implode(
                                            '/',
                                            array_merge(
                                                $this->session->get('routeParamsArrWithoutPg', []),
                                                [$this->session->get('pg', $this->pager->pg)]
                                            )
                                        ),
                                    ],
                                ]))
                                // https://stackoverflow.com/a/6788439/3929620
                                // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                                ->withStatus(303)
                            ;
                        }

                        $totBulkIds = \count($errorBulksIds);

                        $sessionData[$this->controller][$this->action]['bulk_ids'] = $errorBulksIds;

                        $this->session->set($this->auth->getIdentity()['id'].'.sessionData', $sessionData);

                        $this->session->addFlash([
                            'type' => 'toast',
                            'options' => [
                                'type' => 'danger',
                                'message' => current($this->errors),
                            ],
                        ]);

                        $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                            'error' => var_export($this->errors, true),
                        ]);
                    }

                    $message = \sprintf(_nx('Do you confirm the deletion of the following %1$s?', 'Do you confirm the deletion of the following %1$s?', $totBulkIds, $this->context), $this->helper->Nette()->Strings()->lower(1 === $totBulkIds ? $this->singularName : $this->pluralName));

                    _n('Do you confirm the deletion of the following %1$s?', 'Do you confirm the deletion of the following %1$s?', 1, 'default');
                    _n('Do you confirm the deletion of the following %1$s?', 'Do you confirm the deletion of the following %1$s?', 1, 'male');
                    _n('Do you confirm the deletion of the following %1$s?', 'Do you confirm the deletion of the following %1$s?', 1, 'female');
                } else {
                    $message = __('To be able to perform this operation, the dependencies must be resolved first.');
                }

                $this->session->addFlash([
                    'type' => 'alert',
                    'options' => [
                        'env' => static::$env, // <-
                        'type' => 'warning',
                        'message' => $message,
                        'dismissible' => false,
                    ],
                ]);
            } else {
                throw new HttpNotFoundException($request);
            }
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    // TODO - https://paragonie.com/blog/2015/04/secure-authentication-php-with-long-term-persistence
    public function actionLogin(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (!empty($request->getAttribute('hasIdentity'))) {
            return $response
                ->withHeader('Location', $this->helper->Url()->urlFor(static::$env.'.index'))
                ->withStatus(302)
            ;
        }

        $this->title = $this->metaTitle = __('Login');

        foreach ($this->lang->codeArr as $langId => $langCode) {
            $this->routeArgsArr[$langId] = [
                'routeName' => static::$env.'.'.$this->modName,
                'data' => [
                    'lang' => $langCode,
                    'action' => $this->action,
                ],
            ];
        }

        $this->routeArgs = $this->routeArgsArr[$this->lang->id];

        if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
            $this->acceptRouteArgs = [
                'routeName' => static::$env.'.'.$this->modName,
                'data' => [
                    'lang' => $this->lang->acceptCode,
                    'action' => $this->action,
                ],
                'full' => true,
            ];
        }

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        }

        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                $this->title,
                $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
            ),
            $this->helper->Url()->urlFor($this->routeArgs)
        );

        if ('POST' === $request->getMethod()) {
            $this->check(
                $request,
                function (): void {
                    $this->filterSubject->sanitize('username')->toBlankOr('trim');

                    $this->filterSubject->sanitize('password')->toBlankOr('trim');
                },
                function (): void {
                    $this->filterSubject->validate('username')->isNotBlank()/* ->setMessage(__('The entered credentials do not seem correct.')) */;

                    $this->filterSubject->validate('password')->isNotBlank()/* ->setMessage(__('The entered credentials do not seem correct.')) */;
                },
                function (): void {},
                function (): void {}
            );

            if (0 === \count($this->errors)) {
                $result = $this->auth->authenticate($this->postData['username'], $this->postData['password']);

                if ($result->isValid()) {
                    $this->dispatcher->dispatch(new GenericEvent(arguments: [
                        'result' => $result,
                    ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                    $redirect = $this->session->get(static::$env.'.redirectAfterLogin', $this->helper->Url()->urlFor(static::$env.'.index'));

                    $this->session->remove(static::$env.'.redirectAfterLogin');

                    if (!empty($langId = $result->getIdentity()['lang_id'] ?? null)) {
                        if ($langId !== $this->lang->id) {
                            if (isset($this->lang->arr[$langId])) {
                                $redirect = \Safe\preg_replace(
                                    '~\/('.$this->lang->code.')(\/?$|\/*)~',
                                    '/'.$this->lang->arr[$langId]['isoCode'].'$2',
                                    (string) $redirect
                                );

                                $this->translator->prepare($langId);
                            }
                        }
                    }

                    $this->session->addFlash([
                        'type' => 'toast',
                        'options' => [
                            'type' => 'success',
                            'message' => \sprintf(__('Logged in as %1$s.'), '<i>'.$this->helper->Nette()->Strings()->truncate($result->getIdentity()['_name'], 30).'</i>'),
                        ],
                    ]);

                    $this->logger->info(\sprintf(__('%1$s %2$s #%3$d', $this->context, $this->config['logger.locale']), ucfirst((string) $this->action), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $result->getIdentity()['id']));

                    $this->dispatcher->dispatch(new GenericEvent(arguments: [
                        'result' => $result,
                    ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                    return $response
                        ->withHeader('Location', $redirect)
                        // https://stackoverflow.com/a/6788439/3929620
                        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                        ->withStatus(303)
                    ;
                }
                $this->errors = $result->getMessages();
            }

            if (\count($this->errors) > 0) {
                $this->session->addFlash([
                    'type' => 'alert',
                    'options' => [
                        'env' => static::$env, // <-
                        'type' => 'danger',
                        'message' => current($this->errors),
                    ],
                ]);

                $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                    'error' => var_export($this->errors, true),
                ]);
            }
        }
    }

    public function actionLogout(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

        $redirect = $this->session->get(static::$env.'.redirectAfterLogout', $this->helper->Url()->urlFor(static::$env.'.index'));

        $this->session->remove(static::$env.'.redirectAfterLogout');

        if (!empty($request->getAttribute('hasIdentity'))) {
            $this->session->addFlash([
                'type' => 'toast',
                'options' => [
                    'type' => 'success',
                    'message' => __('Successfully logged out.'),
                ],
            ]);

            $this->logger->info(\sprintf(__('%1$s %2$s #%3$d', $this->context, $this->config['logger.locale']), ucfirst((string) $this->action), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $this->auth->getIdentity()['id']));

            $this->auth->clearIdentity();

            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');
        }

        return $response
            ->withHeader('Location', $redirect)
            ->withStatus(302)
        ;
    }

    public function actionReset(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->title = $this->metaTitle = __('Password reset');

        foreach ($this->lang->codeArr as $langId => $langCode) {
            $this->routeArgsArr[$langId] = [
                'routeName' => static::$env.'.'.$this->modName,
                'data' => [
                    'lang' => $langCode,
                    'action' => $this->action,
                ],
            ];
        }

        $this->routeArgs = $this->routeArgsArr[$this->lang->id];

        if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
            $this->acceptRouteArgs = [
                'routeName' => static::$env.'.'.$this->modName,
                'data' => [
                    'lang' => $this->lang->acceptCode,
                    'action' => $this->action,
                ],
                'full' => true,
            ];
        }

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        }

        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                $this->title,
                $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
            ),
            $this->helper->Url()->urlFor($this->routeArgs)
        );

        // FIXME - circular dependencies
        $this->viewHelper = $this->container->get(ViewHelperInterface::class);

        if (!empty($request->getAttribute('hasIdentity'))) {
            $this->session->addFlash([
                'type' => 'toast',
                'options' => [
                    'type' => 'warning',
                    'message' => \sprintf(__('To view this content, you must first %1$s.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                        'href' => $this->helper->Url()->urlFor([
                            'routeName' => static::$env.'.'.$this->modName,
                            'data' => [
                                'action' => 'logout',
                            ],
                        ]),
                    ]).'>'.$this->helper->Nette()->Strings()->lower(__('Logout')).'</a>'),
                    'autohide' => false,
                ],
            ]);

            return $response
                ->withHeader('Location', $this->helper->Url()->urlFor(static::$env.'.index'))
                ->withStatus(302)
            ;
        }

        $this->setRouteParams();

        if (($paramId = array_search('private_key', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $privateKey = (string) $this->routeParamsArr[$paramId + 1];

                $eventName = 'event.'.static::$env.'.'.$this->modName.'.getOne.where';
                $callback = function (GenericEvent $event) use ($privateKey): void {
                    $this->dbData['sql'] .= ' AND a.private_key = :private_key';
                    $this->dbData['sql'] .= ' AND c.active = :c_active';
                    $this->dbData['args']['private_key'] = $privateKey;
                    $this->dbData['args']['c_active'] = 1;
                };

                $this->dispatcher->addListener($eventName, $callback);

                $row = $this->getOne([
                    'id' => null,
                    'active' => true,
                ]);

                $this->dispatcher->removeListener($eventName, $callback);

                if (!empty($row['id'])) {
                    $this->viewData = array_merge(
                        $this->viewData,
                        [...compact( // https://stackoverflow.com/a/30266377/3929620
                            'privateKey'
                        )]
                    );
                } else {
                    $this->session->addFlash([
                        'type' => 'alert',
                        'options' => [
                            'env' => static::$env, // <-
                            'type' => 'danger',
                            'message' => __('The url address you requested seems to be out of date or incorrect.'),
                        ],
                    ]);

                    return $response
                        ->withHeader('Location', $this->helper->Url()->urlFor(!empty($this->loginRouteArgs) ? $this->loginRouteArgs : [
                            'routeName' => static::$env.'.'.$this->modName,
                            'data' => [
                                'action' => 'login',
                            ],
                        ]))
                        ->withStatus(302)
                    ;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $this->serverData = (array) $request->getServerParams();

            if (!empty($privateKey)) {
                $this->check(
                    $request,
                    function (): void {
                        $this->filterSubject->sanitize('password')->toBlankOr('trim');
                    },
                    function (): void {
                        $this->validatePassword('password');
                        $this->filterSubject->validate('password')->isNotBlank()->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$this->fields['password'][static::$env]['label'].'</i>'));
                    },
                    function (): void {},
                    function (): void {}
                );
            } else {
                $this->check(
                    $request,
                    function (): void {
                        $this->sanitizeEmail('email');

                        $this->filterSubject->sanitize('more')->toBlankOr('trim');
                    },
                    function (): void {
                        $this->validateEmail('email');
                        $this->filterSubject->validate('email')->isNotBlank()->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$this->fields['email'][static::$env]['label'].'</i>'));

                        $this->validateGRecaptchaResponse('g-recaptcha-response');

                        if (!$this->session->get('timestamp_'.$this->controller.'_time')) {
                            $this->session->set('timestamp_'.$this->controller.'_time', $this->helper->Carbon()->now()->subSeconds(2)->getTimestamp());

                            if ((int) ($this->postData['timestamp'] ?? $this->helper->Carbon()->now()->getTimestamp()) > $this->session->get('timestamp_'.$this->controller.'_time')) {
                                $this->session->set('timestamp_'.$this->controller.'_error', true);
                            }
                        }

                        if ($this->session->get('timestamp_'.$this->controller.'_error')) {
                            $this->filterSubject->validate('timestamp')->is('error')->setMessage(__('SPAM attempt detected.').($this->config['debug.enabled'] ? ' (timestamp)' : ''));
                        }

                        $this->filterSubject->validate('more')->isBlank()->setMessage(__('SPAM attempt detected.').($this->config['debug.enabled'] ? ' (more)' : ''));
                    },
                    function (): void {},
                    function (): void {}
                );
            }

            if (0 === \count($this->errors)) {
                if (!empty($privateKey)) {
                    $fieldKey = 'password';

                    $algorithm = $this->config['mod.'.$this->modName.'.'.$fieldKey.'.auth.password.hash.algorithm'] ?? $this->config['mod.'.$this->modName.'.auth.password.hash.algorithm'] ?? $this->config['auth.password.hash.algorithm'];
                    $options = \array_key_exists('mod.'.$this->modName.'.'.$fieldKey.'.auth.password.hash.options', $this->config->toArray()) ? $this->config['mod.'.$this->modName.'.'.$fieldKey.'.auth.password.hash.options'] : (\array_key_exists('mod.'.$this->modName.'.auth.password.hash.options', $this->config->toArray()) ? $this->config['mod.'.$this->modName.'.auth.password.hash.options'] : $this->config['auth.password.hash.options']); // <--

                    $hash = match ($algorithm) {
                        'APR1_MD5' => APR1_MD5::hash($this->postData['password'], $options),
                        default => password_hash((string) $this->postData['password'], $algorithm, $options),
                    };

                    try {
                        $this->removeAllListeners();

                        do {
                            $privateKey = $this->helper->Nette()->Random()->generate($this->config['mod.'.$this->modName.'.auth.privateKey.minLength'] ?? $this->config['auth.privateKey.minLength']);

                            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getOne.where';
                            $callback = function (GenericEvent $event) use ($privateKey): void {
                                $this->dbData['sql'] .= ' AND a.private_key = :private_key';
                                $this->dbData['args']['private_key'] = $privateKey;
                            };

                            $this->dispatcher->addListener($eventName, $callback);

                            $_row = $this->getOne(
                                [
                                    'id' => false,
                                ]
                            );

                            $this->dispatcher->removeListener($eventName, $callback);
                        } while (!empty($_row['id']));

                        $this->addAllListeners();

                        $this->db->exec('UPDATE '.$this->config['db.1.prefix'].$this->modName.' SET mdate = :mdate, private_key = :private_key, password = :password WHERE id = :id', [
                            'mdate' => $this->helper->Carbon()->now($this->config['db.1.timeZone'])->toDateTimeString(),
                            'private_key' => $privateKey,
                            'password' => $hash,
                            'id' => $row['id'],
                        ]);
                    } catch (\Exception $e) {
                        $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                            'error' => $e->getMessage(),
                        ]);

                        $this->errors[] = __('A technical problem has occurred, try again later.');
                    }

                    if (0 === \count($this->errors)) {
                        $this->logger->info(\sprintf(__('%1$s %2$s by %3$s', $this->context, $this->config['logger.locale']), __('Password'), $this->helper->Nette()->Strings()->lower($this->action), $row[$this->authUsernameField]));

                        $this->session->addFlash([
                            'type' => 'alert',
                            'options' => [
                                'env' => static::$env, // <-
                                'type' => 'success',
                                'message' => \sprintf(__('%1$s successfully modified.', 'female'), __('Password'))
                                    .'<br>'.__('You can now log in with your new password.'),
                            ],
                        ]);
                    }
                } else {
                    $this->session->remove('timestamp_'.$this->controller.'_time');
                    $this->session->remove('timestamp_'.$this->controller.'_error');

                    $eventName = 'event.'.static::$env.'.'.$this->modName.'.getOne.where';
                    $callback = function (GenericEvent $event): void {
                        $this->dbData['sql'] .= ' AND a.email = :email';
                        $this->dbData['sql'] .= ' AND c.active = :c_active';
                        $this->dbData['args']['email'] = $this->postData['email'];
                        $this->dbData['args']['c_active'] = 1;
                    };

                    $this->dispatcher->addListener($eventName, $callback);

                    $row = $this->getOne([
                        'id' => null,
                        'active' => true,
                    ]);

                    $this->dispatcher->removeListener($eventName, $callback);

                    $oldViewLayoutRegistryPaths = $this->viewLayoutRegistryPaths;
                    $oldViewRegistryPaths = $this->viewRegistryPaths;
                    $oldViewLayout = $this->viewLayout;

                    if (!empty($row['id'])) {
                        $env = 'email';

                        array_push(
                            $this->viewLayoutRegistryPaths,
                            _ROOT.'/app/view/'.$env.'/layout',
                            _ROOT.'/app/view/'.$env.'/partial'
                        );

                        array_push(
                            $this->viewRegistryPaths,
                            _ROOT.'/app/view/'.$env.'/controller/'.$this->controller,
                            _ROOT.'/app/view/'.$env.'/base',
                            _ROOT.'/app/view/'.$env.'/partial'
                        );

                        $subject = $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('%1$s %2$s request from %3$s'), $this->helper->Nette()->Strings()->lower(__('Password')), $this->helper->Nette()->Strings()->lower($this->action), $this->helper->Url()->removeScheme($this->helper->Url()->getBaseUrl())));

                        $this->viewData = array_merge(
                            $this->viewData,
                            [
                                'Mod' => $this,
                                'subject' => $subject,
                                'email' => $this->postData['email'],
                                'row' => $row,
                            ]
                        );

                        $this->viewLayout = 'blank';

                        $html = $this->renderBody($request, $response, $args);

                        $params = [
                            'to' => $this->postData['email'],
                            'sender' => $this->config['mail.'.$this->controller.'.sender'] ?? null,
                            'from' => $this->config['mail.'.$this->controller.'.from'] ?? null,
                            'replyTo' => $this->config['mail.'.$this->controller.'.replyTo'] ?? null,
                            'cc' => $this->config['mail.'.$this->controller.'.cc'] ?? null,
                            'bcc' => $this->config['mail.'.$this->controller.'.bcc'] ?? null,
                            'returnPath' => $this->config['mail.'.$this->controller.'.returnPath'] ?? null,
                            'subject' => $subject,
                            'html' => $html,
                            'text' => $this->helper->Html()->html2Text($html),
                        ];

                        $this->mailer->prepare($params);

                        if (!$this->mailer->send()) {
                            $this->errors[] = __('A technical problem has occurred, try again later.');
                        }
                    }

                    $this->viewLayoutRegistryPaths = $oldViewLayoutRegistryPaths;
                    $this->viewRegistryPaths = $oldViewRegistryPaths;
                    $this->viewLayout = $oldViewLayout;

                    if (0 === \count($this->errors)) {
                        $this->logger->info(\sprintf(__('%1$s %2$s request for %3$s', $this->context, $this->config['logger.locale']), __('Password'), $this->helper->Nette()->Strings()->lower($this->action), $this->postData['email']));

                        $this->session->addFlash([
                            'type' => 'alert',
                            'options' => [
                                'env' => static::$env, // <-
                                'type' => 'success',
                                'message' => __('If the email entered is correct, you will receive an email with a link to reset your password.'),
                            ],
                        ]);
                    }
                }

                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');
            }

            if (0 === \count($this->errors)) {
                return $response
                    ->withHeader('Location', $this->helper->Url()->urlFor(!empty($this->loginRouteArgs) ? $this->loginRouteArgs : [
                        'routeName' => static::$env.'.'.$this->modName,
                        'data' => [
                            'action' => 'login',
                        ],
                    ]))
                    // https://stackoverflow.com/a/6788439/3929620
                    // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                    ->withStatus(303)
                ;
            }
            $this->session->addFlash([
                'type' => 'alert',
                'options' => [
                    'env' => static::$env, // <-
                    'type' => 'danger',
                    'message' => current($this->errors),
                ],
            ]);

            $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                'error' => var_export($this->errors, true),
            ]);
        }
    }

    public function actionConfirm(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        }

        $this->setRouteParams();

        if (($paramId = array_search('private_key', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $privateKey = (string) $this->routeParamsArr[$paramId + 1];

                $eventName = 'event.'.static::$env.'.'.$this->modName.'.getOne.where';
                $callback = function (GenericEvent $event) use ($privateKey): void {
                    $this->dbData['sql'] .= ' AND a.private_key = :private_key';
                    $this->dbData['sql'] .= ' AND c.active = :c_active';
                    $this->dbData['args']['private_key'] = $privateKey;
                    $this->dbData['args']['c_active'] = 1;
                };

                $this->dispatcher->addListener($eventName, $callback);

                $row = $this->getOne([
                    'id' => null,
                    'active' => true,
                ]);

                $this->dispatcher->removeListener($eventName, $callback);

                if (!empty($row['id'])) {
                    try {
                        do {
                            $privateKey = $this->helper->Nette()->Random()->generate($this->config['mod.'.$this->modName.'.auth.privateKey.minLength'] ?? $this->config['auth.privateKey.minLength']);

                            $this->removeAllListeners();

                            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getOne.where';
                            $callback = function (GenericEvent $event) use ($privateKey): void {
                                $this->dbData['sql'] .= ' AND a.private_key = :private_key';
                                $this->dbData['args']['private_key'] = $privateKey;
                            };

                            $this->dispatcher->addListener($eventName, $callback);

                            $_row = $this->getOne(
                                [
                                    'id' => false,
                                ]
                            );

                            $this->dispatcher->removeListener($eventName, $callback);

                            $this->addAllListeners();
                        } while (!empty($_row['id']));

                        $this->db->exec('UPDATE '.$this->config['db.1.prefix'].$this->modName.' SET mdate = :mdate, private_key = :private_key, confirmed = :confirmed WHERE id = :id', [
                            'mdate' => $this->helper->Carbon()->now($this->config['db.1.timeZone'])->toDateTimeString(),
                            'private_key' => $privateKey,
                            'confirmed' => 1,
                            'id' => $row['id'],
                        ]);
                    } catch (\Exception $e) {
                        $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                            'error' => $e->getMessage(),
                        ]);

                        $this->errors[] = __('A technical problem has occurred, try again later.');
                    }

                    if (0 === \count($this->errors)) {
                        $this->logger->info(\sprintf(__('%1$s %2$s by %3$s', $this->context, $this->config['logger.locale']), __('Email'), $this->helper->Nette()->Strings()->lower($this->action), $row[$this->authUsernameField]));

                        $this->session->addFlash([
                            'type' => 'alert',
                            'options' => [
                                'env' => static::$env, // <-
                                'type' => 'success',
                                'message' => \sprintf(_x('%1$s successfully confirmed.', 'female'), __('Email')),
                            ],
                        ]);

                        __('%1$s successfully confirmed.', 'default');
                        __('%1$s successfully confirmed.', 'male');
                        __('%1$s successfully confirmed.', 'female');

                        if (!empty($request->getAttribute('hasIdentity'))) {
                            if (\in_array($this->auth->getIdentity()['_type'], [$this->modName], true)) {
                                if ($this->auth->getIdentity()['id'] === $row['id']) {
                                    $this->auth->forceAuthenticate($row[$this->authUsernameField]);
                                }
                            }
                        }

                        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');
                    }
                } else {
                    $this->session->addFlash([
                        'type' => 'alert',
                        'options' => [
                            'env' => static::$env, // <-
                            'type' => 'danger',
                            'message' => __('The url address you requested seems to be expired or incorrect.'),
                        ],
                    ]);
                }

                $redirect = !empty($request->getAttribute('hasIdentity')) ? $this->helper->Url()->urlFor(!empty($this->profileRouteArgs) ? $this->profileRouteArgs : [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'action' => 'edit',
                        'params' => $this->auth->getIdentity()['id'],
                    ],
                ]) : $this->helper->Url()->urlFor(!empty($this->loginRouteArgs) ? $this->loginRouteArgs : [
                    'routeName' => static::$env.'.'.$this->modName,
                    'data' => [
                        'action' => 'login',
                    ],
                ]);

                return $response
                    ->withHeader('Location', $redirect)
                    ->withStatus(302)
                ;
            }

            throw new HttpNotFoundException($request);
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    public function _actionWidgetIndex(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->pager->create($this->getCount([
            'active' => $this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete') ? null : true,
        ]), $this->config['mod.'.$this->modName.'.pagination.'.static::$env.'.rowLast'] ?? $this->config['mod.'.$this->modName.'.pagination.rowLast'] ?? $this->config['mod.rowLast'] ?? null);

        $this->setPagerAndOrder();

        if (!empty($request->getAttribute('hasIdentity'))) {
            if (($filterData = $this->session->get(static::$env.'.'.$this->auth->getIdentity()['id'].'.filterData')) !== null) {
                if (!empty($filterData[$this->controller][$this->action])) {
                    $this->filterData = $filterData[$this->controller][$this->action];
                }
            }
        }

        if ($this->pager->totRows > 0) {
            $result = $this->getAll(
                [
                    'order' => $this->internalOrder,
                    'offset' => $this->offset,
                    'nRows' => $this->pager->rowPerPage,
                    'active' => $this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete') ? null : true,
                ]
            );

            if (\count($result) > 0) {
                $this->widgetData[$this->controller][$this->action] = [
                    'Mod' => $this,
                    'title' => $this->pluralName,
                    'result' => $result,
                    'totRows' => $this->pager->totRows,
                ];
            }
        }
    }

    protected function _actionPostFilter(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->filter($request);

        $filterData = $this->session->get(static::$env.'.'.$this->auth->getIdentity()['id'].'.filterData', []);

        $filterData[$this->controller][$this->action] = $this->filterData;

        $this->session->set(static::$env.'.'.$this->auth->getIdentity()['id'].'.filterData', $filterData);
    }

    protected function _actionPostSearch(RequestInterface $request, ResponseInterface $response, $args): void
    {
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

        $sessionData = $this->session->get($this->auth->getIdentity()['id'].'.sessionData', []);

        $sessionData[$this->controller][$this->action] = $this->postData;

        $this->session->set($this->auth->getIdentity()['id'].'.sessionData', $sessionData);

        $this->logger->debug(\sprintf('search text -> %1$s', $this->postData['_search'] ?? null));

        $this->search($request);

        $searchData = $this->session->get(static::$env.'.'.$this->auth->getIdentity()['id'].'.searchData', []);

        $searchData[$this->controller][$this->action] = $this->searchData;

        $this->session->set(static::$env.'.'.$this->auth->getIdentity()['id'].'.searchData', $searchData);
    }

    // https://odan.github.io/2017/12/16/creating-and-downloading-excel-files-with-slim.html
    protected function _actionPostExport(RequestInterface $request, ResponseInterface $response, $args): void
    {
        // FIXME - circular dependencies
        $this->viewHelper = $this->container->get(ViewHelperInterface::class);

        if (!empty($redirect = $this->cache->get($this->cache->getItemKey([
            $this->getShortName(),
            __FUNCTION__,
            __LINE__,
            $this->postData,
        ]), function (ItemInterface $cacheItem) {
            \Safe\ini_set('max_execution_time', '60');
            \Safe\ini_set('memory_limit', '256M');

            // $cacheItem->expiresAt($this->helper->Carbon()->parse('yesterday'));

            if (!empty($this->cache->taggable)) {
                $tags = [
                    'local-'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'],
                    'global-1',
                ];

                $cacheItem->tag($tags);
            }

            $result = $this->getAll();

            if (\count($result) > 0) {
                $stream = $this->helper->Nette()->FileSystem()->open('php://memory', 'w+');

                $rows = [];
                $i = 0;

                foreach ($this->fieldsSortable as $key => $val) {
                    $rows[$i][] = $val[static::$env]['label'];
                }

                ++$i;

                foreach ($result as $row) {
                    foreach ($this->fieldsSortable as $key => $val) {
                        $rows[$i][] = $row[$key];
                    }

                    ++$i;
                }

                foreach ($rows as $row) {
                    \Safe\fputcsv($stream, $row, ';');
                }

                \Safe\rewind($stream);

                $fileName = $this->modName.'-'.$this->action.'-'.$this->helper->Carbon()->now(date_default_timezone_get())->toDateString().'.csv';
                $src = \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/tmp/'.$fileName);

                $this->helper->Nette()->FileSystem()->write($src, new StreamFactory()->createStreamFromResource($stream)->__toString());

                $random = $this->helper->Nette()->Random()->generate();
                $dest = _PUBLIC.'/symlink/'.$random.'/'.$fileName;

                $this->helper->Nette()->FileSystem()->createDir(\dirname($dest));

                if (false !== $this->helper->File()->symlink($src, $dest)) {
                    $this->logger->info(\sprintf(_nx('Exported %1$s', 'Exported %1$s', 2, $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->pluralNameWithParams)));

                    _n('Exported %1$s', 'Exported %1$s', 1, 'default');
                    _n('Exported %1$s', 'Exported %1$s', 1, 'male');
                    _n('Exported %1$s', 'Exported %1$s', 1, 'female');

                    $redirect = $this->helper->Url()->getBaseUrl().'/symlink/'.$random.'/'.$fileName;

                    $cacheItem->expiresAfter($this->helper->CarbonInterval()->createFromDateString('12 hours'));
                } else {
                    $this->errors[] = __('A technical problem has occurred, try again later.');
                }

                return $redirect ?? false;
            } else {
                $this->errors[] = __('No results found.');
            }
        }))) {
            $uniqid = uniqid();

            $this->session->addFlash([
                'type' => 'alert',
                'options' => [
                    'type' => 'info',
                    'message' => [
                        __('Download in progress').'&hellip;',
                        \sprintf(__('%1$s if the download doesn\'t work.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                            'href' => $redirect,
                        ]).'>'.$this->helper->Nette()->Strings()->firstUpper(__('click here')).'</a>'),
                    ],
                    'attr' => [
                        'id' => $uniqid,
                    ],
                    // https://stackoverflow.com/a/77215205/3929620
                    // https://stackoverflow.com/a/12539054/3929620
                    // https://stackoverflow.com/a/11804706/3929620
                    'scriptsFoot' => '(() => {
    window.addEventListener("load", (event) => {
        window.location.href = "'.$this->viewHelper->escape()->js($redirect).'";

        if( typeof Alert !== \'undefined\' ) {
            const alert = Alert.getOrCreateInstance("#'.$uniqid.'");
            if (alert) {
                alert.close();
            }
        }
    });
})();',
                ],
            ]);
        } else {
            $this->errors[] = __('No results found.');
        }
    }

    protected function _actionPostDeleteBulk(RequestInterface $request, ResponseInterface $response, $args)
    {
        $oldAction = $this->action;

        $this->action = $this->postData['action'];

        $this->check(
            $request,
            null,
            function (): void {
                $this->filterSubject->validate('bulk_ids')->isNotBlank()->setMessage(\sprintf(_x('No %1$s selected.', 'default'), $this->helper->Nette()->Strings()->lower(__('Item'))));
            }
        );

        __('No %1$s selected.', 'default');
        __('No %1$s selected.', 'male');
        __('No %1$s selected.', 'female');

        if (0 === \count($this->errors)) {
            if (\count($this->postData['bulk_ids']) > ($this->config['mod.'.static::$env.'.'.$this->controller.'.'.$this->action.'.maxBulk'] ?? $this->config['mod.'.static::$env.'.'.$this->controller.'.maxBulk'] ?? $this->config['mod.'.static::$env.'.maxBulk'] ?? $this->config['mod.maxBulk'] ?? $this->config['pagination.'.static::$env.'.rowPerPage'] ?? $this->config['pagination.rowPerPage'])) {
                $Mod = $this->container->get('Mod\Queue\\'.ucfirst((string) static::$env));
                $insertIds = $skippedIds = [];

                $Mod->removeAllListeners();

                foreach ($this->postData['bulk_ids'] as $bulkId) {
                    $itemAction = 'delete';

                    $eventName = 'event.'.static::$env.'.'.$Mod->modName.'.existStrict.where';
                    $callback = function (GenericEvent $event) use ($Mod, $bulkId, $itemAction): void {
                        $Mod->dbData['sql'] .= ' AND a.item_id = :item_id';
                        $Mod->dbData['sql'] .= ' AND a.item_controller = :item_controller';
                        $Mod->dbData['sql'] .= ' AND a.item_action = :item_action';

                        $Mod->dbData['args']['item_id'] = $bulkId;
                        $Mod->dbData['args']['item_controller'] = $this->controller;
                        $Mod->dbData['args']['item_action'] = $itemAction;
                    };

                    $this->dispatcher->addListener($eventName, $callback);

                    $result = $Mod->existStrict([
                        'id' => false,
                    ]);

                    $this->dispatcher->removeListener($eventName, $callback);

                    if ($result) {
                        $skippedIds[] = $bulkId;

                        continue;
                    }

                    $Mod->action = 'add';
                    $Mod->postData = [
                        'item_id' => $bulkId,
                        'item_controller' => $this->controller,
                        'item_action' => $itemAction,
                    ];

                    $Mod->check($request->withParsedBody($Mod->postData));

                    if (0 === \count($Mod->errors)) {
                        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$Mod->modName.'.'.__FUNCTION__.'.before');

                        $insertId = $Mod->dbAdd();

                        $insertIds[] = $insertId;

                        $this->dispatcher->dispatch(new GenericEvent(arguments: [
                            'id' => $insertId,
                        ]), 'event.'.static::$env.'.'.$Mod->modName.'.'.__FUNCTION__.'.after');
                    }
                }

                $Mod->addAllListeners();

                $this->errors = $Mod->errors;

                if (0 === \count($this->errors)) {
                    if (\count($insertIds) > 0) {
                        $this->logger->info(\sprintf(_nx('Added %1$d %2$s -> %3$s -> %4$s', 'Added %1$d %2$s -> %3$s -> %4$s', \count($insertIds), $Mod->context, $this->config['logger.locale']), \count($insertIds), $this->helper->Nette()->Strings()->lower(1 === \count($insertIds) ? $Mod->singularNameWithParams : $Mod->pluralNameWithParams), $this->controller, $itemAction));

                        _n('Added %1$d %2$s -> %3$s -> %4$s', 'Added %1$d %2$s -> %3$s -> %4$s', 1, 'default');
                        _n('Added %1$d %2$s -> %3$s -> %4$s', 'Added %1$d %2$s -> %3$s -> %4$s', 1, 'male');
                        _n('Added %1$d %2$s -> %3$s -> %4$s', 'Added %1$d %2$s -> %3$s -> %4$s', 1, 'female');

                        $message = \sprintf(_x('%1$s successfully added.', $Mod->context), $Mod->singularName);

                        __('%1$s successfully added.', 'default');
                        __('%1$s successfully added.', 'male');
                        __('%1$s successfully added.', 'female');

                        $type = 'success';
                    } else {
                        $message = __('Operation not performed.');

                        $type = 'error';
                    }

                    if (\count($skippedIds) > 0) {
                        $message .= '<br>'.\sprintf(_nx('%1$d item was already present.', '%1$d items were already present.', \count($skippedIds), $Mod->context), \count($skippedIds));

                        _n('%1$d item was already present.', '%1$d items were already present.', 1, 'default');
                        _n('%1$d item was already present.', '%1$d items were already present.', 1, 'male');
                        _n('%1$d item was already present.', '%1$d items were already present.', 1, 'female');
                    }

                    $this->session->addFlash([
                        'type' => 'toast',
                        'options' => [
                            'type' => $type,
                            'message' => $message,
                        ],
                    ]);
                }
            } else {
                $sessionData = $this->session->get($this->auth->getIdentity()['id'].'.sessionData', []);

                $sessionData[$this->controller][$this->action] = $this->postData;

                $this->session->set($this->auth->getIdentity()['id'].'.sessionData', $sessionData);

                $redirect = $this->helper->Url()->urlFor([
                    'routeName' => static::$env.'.'.$this->controller,
                    'data' => [
                        'action' => $this->action,
                    ],
                ]);

                return $response
                    ->withHeader('Location', $redirect)
                    // https://stackoverflow.com/a/6788439/3929620
                    // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                    ->withStatus(303)
                ;
            }
        }

        $this->action = $oldAction;
    }
}
