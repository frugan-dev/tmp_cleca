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

namespace App\Controller\Env\Front;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait ActionTrait
{
    public function actionIndex(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->title = $this->metaTitle = __('Home');
    }

    public function actionLogin(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (!empty($request->getAttribute('hasIdentity'))) {
            return $response
                ->withHeader('Location', $this->helper->Url()->urlFor(static::$env.'.index'))
                ->withStatus(302)
            ;
        }

        if ('POST' === $request->getMethod()) {
            if (!str_contains($this->postData['username'] ?? '', '@')) {
                $namespace = 'Mod\User\\'.ucfirst(static::$env);
            } else {
                $namespace = 'Mod\Member\\'.ucfirst(static::$env);
            }

            if ($this->container->has($namespace)) {
                $this->container->get($namespace)->controller = $this->controller;
                $this->container->get($namespace)->controllerCamelCase = $this->controllerCamelCase;
                $this->container->get($namespace)->action = $this->action;
                $this->container->get($namespace)->actionCamelCase = $this->actionCamelCase;
                $this->container->get($namespace)->postData = $this->postData;

                if (method_exists($this->container->get($namespace), __FUNCTION__) && \is_callable([$this->container->get($namespace), __FUNCTION__])) {
                    $return = \call_user_func_array([$this->container->get($namespace), __FUNCTION__], [$request, $response, $args]);

                    if (null !== $return) {
                        return $return;
                    }
                }
            }
        }

        $this->title = $this->metaTitle = __('Login');

        foreach ($this->lang->codeArr as $langId => $langCode) {
            $this->routeArgsArr[$langId] = [
                'routeName' => static::$env.'.action',
                'data' => [
                    'lang' => $langCode,
                    'action' => $this->action,
                ],
            ];
        }

        $this->routeArgs = $this->routeArgsArr[$this->lang->id];

        if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
            $this->acceptRouteArgs = [
                'routeName' => static::$env.'.action',
                'data' => [
                    'lang' => $this->lang->acceptCode,
                    'action' => $this->action,
                ],
                'full' => true,
            ];
        }

        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                $this->title,
                $this->config->get('breadcrumb.'.static::$env.'.textTruncate') ?? $this->config->get('breadcrumb.textTruncate')
            ),
            $this->helper->Url()->urlFor($this->routeArgs)
        );
    }

    public function actionOffline(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->title = $this->metaTitle = __('Offline');
    }

    public function action401(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->title = $this->metaTitle = __('Error');

        $this->breadcrumb->removeAll();

        $this->viewData = array_merge(
            $this->viewData,
            [
                'backButtonUrl' => 'javascript:history.back()',
            ]
        );
    }

    public function action404(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->title = $this->metaTitle = __('Error');

        $this->breadcrumb->removeAll();

        $this->viewData = array_merge(
            $this->viewData,
            [
                'backButtonUrl' => 'javascript:history.back()',
            ]
        );
    }
}
