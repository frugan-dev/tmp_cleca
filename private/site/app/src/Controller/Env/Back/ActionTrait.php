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

namespace App\Controller\Env\Back;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait ActionTrait
{
    public function actionIndex(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (!empty($this->config->get('mod.'.$this->controller.'.'.static::$env.'.redirect', false))) {
            $redirect = $this->helper->Url()->urlFor(static::$env.'.'.$this->config->get('mod.'.$this->controller.'.'.static::$env.'.redirect'));

            return $response
                ->withHeader('Location', $redirect)
                ->withStatus(302)
            ;
        }

        $this->title = $this->metaTitle = __('Dashboard');

        if (\count($this->widgets) > 0) {
            $widgets = $this->helper->Arrays()->uasortBy($this->widgets, 'weight');
            $widgetData = [];

            foreach ($widgets as $key => $widget) {
                if (!$this->rbac->isGranted($widget['perm']) || static::$env !== $widget['env']) {
                    unset($widgets[$key]);

                    continue;
                }

                $widgets[$key]['id'] = $key;

                if ($this->container->has('Mod\\'.ucfirst((string) $widget['controller'].'\\'.ucfirst(static::$env)))) {
                    $this->actionCamelCase = $widget['action'];

                    $this->filterValue->sanitize($this->actionCamelCase, 'string', ['_', '-'], ' ');
                    $this->filterValue->sanitize($this->actionCamelCase, 'titlecase');
                    $this->filterValue->sanitize($this->actionCamelCase, 'string', ' ', '');

                    $widgets[$key]['actionCamelCase'] = $this->actionCamelCase;

                    $Mod = $this->container->get('Mod\\'.ucfirst((string) $widget['controller'].'\\'.ucfirst(static::$env)));

                    if (method_exists($Mod, '_actionWidget'.$this->actionCamelCase) && \is_callable([$Mod, '_actionWidget'.$this->actionCamelCase])) {
                        $Mod->action = $widget['action'];
                        $Mod->actionCamelCase = $this->actionCamelCase;

                        $oldInternalOrder = $Mod->internalOrder;
                        $Mod->internalOrder = $widget['internalOrder'] ?? $oldInternalOrder;

                        $return = \call_user_func_array([$Mod, '_actionWidget'.$this->actionCamelCase], [$request, $response, $args]);

                        if (null !== $return) {
                            return $return;
                        }

                        $this->viewData = array_merge(
                            $this->viewData,
                            $Mod->viewData
                        );

                        $widgetData[$widget['controller']][$widget['action']] = $Mod->widgetData[$widget['controller']][$widget['action']] ?? [];

                        $Mod->internalOrder = $oldInternalOrder;
                    }
                }
            }

            $this->viewData = array_merge(
                $this->viewData,
                compact( // https://stackoverflow.com/a/30266377/3929620
                    'widgets',
                    'widgetData'
                )
            );
        }
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
