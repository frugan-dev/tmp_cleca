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

namespace App\Controller\Env\Xml;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;

class Controller extends \App\Model\Controller
{
    use ActionTrait;

    public static string $env = 'xml';

    public string $mimetype = 'application/xml';

    #[\Override]
    public function __invoke(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (empty($this->controller) && !empty($args['slug'])) {
            $this->controller = $args['slug'];
        }

        $this->controllerCamelCase = $this->controller ??= 'index';

        $this->filterValue->sanitize($this->controllerCamelCase, 'string', ['_', '-'], ' ');
        $this->filterValue->sanitize($this->controllerCamelCase, 'titlecase');
        $this->filterValue->sanitize($this->controllerCamelCase, 'string', ' ', '');

        if ($this->container->has('Mod\\'.$this->controllerCamelCase.'\\'.ucfirst(static::$env))) {
            $this->actionCamelCase = $this->action ??= 'index';
        } else {
            $this->actionCamelCase = $this->action ??= $this->controller;
        }

        $this->filterValue->sanitize($this->actionCamelCase, 'string', ['_', '-'], ' ');
        $this->filterValue->sanitize($this->actionCamelCase, 'titlecase');
        $this->filterValue->sanitize($this->actionCamelCase, 'string', ' ', '');

        if (!empty($this->config->get('cache.'.static::$env.'.storage.'.$this->controller.'.'.$this->action.'.enabled') ?? $this->config->get('cache.'.static::$env.'.storage.'.$this->controller.'.enabled') ?? $this->config->get('cache.'.static::$env.'.storage.enabled') ?? $this->config->get('cache.storage.enabled'))) {
            $cacheItemKey = $this->cache->getItemKey([
                $this->helper->Url()->getPathUrl(),
            ]);
            $cacheItem = $this->cache->getItem($cacheItemKey);

            if ($cacheItem->isHit()) {
                // The Response object is immutable.
                // This method returns a copy of the Response object that has the new header value.
                // This method is destructive, and it replaces existing header values already associated with the same header name.
                $response = $response->withHeader('Content-type', $this->mimetype);

                $response->getBody()
                    ->write($cacheItem->get())
                ;

                return $response;
            }
            $response = $response->withHeader($this->config->get('cache.'.static::$env.'.storage.body.header') ?? $this->config->get('cache.storage.body.header'), 'OK');
        }

        if (method_exists($this, '_actionGlobal') && \is_callable([$this, '_actionGlobal'])) {
            $return = \call_user_func_array([$this, '_actionGlobal'], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        }

        if (method_exists($this, '_action'.$this->controllerCamelCase) && \is_callable([$this, '_action'.$this->controllerCamelCase])) {
            $return = \call_user_func_array([$this, '_action'.$this->controllerCamelCase], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        }

        if (method_exists($this, '_action'.$this->controllerCamelCase.$this->actionCamelCase) && \is_callable([$this, '_action'.$this->controllerCamelCase.$this->actionCamelCase])) {
            $return = \call_user_func_array([$this, '_action'.$this->controllerCamelCase.$this->actionCamelCase], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        }

        if ($this->container->has('Mod\\'.$this->controllerCamelCase.'\\'.ucfirst(static::$env))) {
            $this->container->get('Mod\\'.$this->controllerCamelCase.'\\'.ucfirst(static::$env))->controller = $this->controller;
            $this->container->get('Mod\\'.$this->controllerCamelCase.'\\'.ucfirst(static::$env))->action = $this->action;

            if (method_exists($this->container->get('Mod\\'.$this->controllerCamelCase.'\\'.ucfirst(static::$env)), '_action'.$this->actionCamelCase) && \is_callable([$this->container->get('Mod\\'.$this->controllerCamelCase.'\\'.ucfirst(static::$env)), '_action'.$this->actionCamelCase])) {
                $return = \call_user_func_array([$this->container->get('Mod\\'.$this->controllerCamelCase.'\\'.ucfirst(static::$env)), '_action'.$this->actionCamelCase], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }

                $this->viewData = array_merge(
                    $this->viewData,
                    [
                        'Mod' => $this->container->get('Mod\\'.$this->controllerCamelCase.'\\'.ucfirst(static::$env)),
                    ],
                    $this->container->get('Mod\\'.$this->controllerCamelCase.'\\'.ucfirst(static::$env))->viewData
                );
            } else {
                throw new HttpNotFoundException($request);
            }
        }

        array_push(
            $this->viewLayoutRegistryPaths,
            _ROOT.'/app/view/'.static::$env.'/layout',
            _ROOT.'/app/view/'.static::$env.'/partial/'.$this->lang->code,
            _ROOT.'/app/view/'.static::$env.'/partial'
        );

        array_push(
            $this->viewRegistryPaths,
            _ROOT.'/app/view/'.static::$env.'/controller/'.$this->controller.'/'.$this->lang->code,
            _ROOT.'/app/view/'.static::$env.'/controller/'.$this->controller,
            _ROOT.'/app/view/'.static::$env.'/base/'.$this->lang->code,
            _ROOT.'/app/view/'.static::$env.'/base',
            _ROOT.'/app/view/'.static::$env.'/partial/'.$this->lang->code,
            _ROOT.'/app/view/'.static::$env.'/partial'
        );

        return $this->render($request, $response, $args);
    }
}
