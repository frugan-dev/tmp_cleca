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

namespace App\Model\Mod\Front;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpUnauthorizedException;

class Mod extends \App\Model\Mod\Mod
{
    use ModActionTrait;
    use ModEventTrait;

    public static string $env = 'front';

    public string $viewLayout = 'master';

    #[\Override]
    public function __invoke(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                __('Home'),
                $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
            ),
            $this->helper->Url()->urlFor(static::$env.'.index')
        );

        $this->viewData = [...$this->viewData, 'Mod' => $this, 'backButton' => $this->rbac->isGranted($this->controller.'.'.static::$env.'.index') ? true : false];

        return parent::__invoke($request, $response, $args);
    }

    #[\Override]
    protected function _authCheck(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (\in_array($this->controller, ['catform', 'page'], true)
            && \in_array($this->action, ['view'], true)) {
        } elseif (\in_array($this->controller, ['user'], true)
            && \in_array($this->action, ['logout'], true)) {
        } elseif (\in_array($this->controller, ['member'], true)
            && \in_array($this->action, ['login', 'logout', 'confirm'], true)) {
        } elseif (\in_array($this->controller, ['member'], true)
            && \in_array($this->action, ['reset', 'signup'], true)) {
            if (!empty($request->getAttribute('hasIdentity'))) {
                $this->session->set(static::$env.'.redirectAfterLogout', $this->helper->Url()->getPathUrl());
            }
        } elseif (empty($request->getAttribute('hasIdentity'))) {
            $this->session->set(static::$env.'.redirectAfterLogin', $this->helper->Url()->getPathUrl());

            return $response
                ->withHeader('Location', $this->helper->Url()->urlFor([
                    'routeName' => static::$env.'.action',
                    'data' => [
                        'action' => 'login',
                    ],
                ]))
                ->withStatus(302)
            ;
        } elseif (\in_array($this->controller, [$this->auth->getIdentity()['_type']], true)
            && \in_array($this->action, ['edit', 'setting'], true)
            && !empty($args['params'])
            && $this->auth->getIdentity()['id'] === (int) $args['params']) {
        } elseif (!$this->rbac->isGranted($this->controller.'.'.static::$env.'.'.$this->action)) {
            throw new HttpUnauthorizedException($request);
        }
    }
}
