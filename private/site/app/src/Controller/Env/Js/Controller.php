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

namespace App\Controller\Env\Js;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpMethodNotAllowedException;

class Controller extends \App\Model\Controller
{
    use ActionTrait;

    public static string $env = 'js';

    public string $mimetype = 'application/javascript';

    #[\Override]
    public function __invoke(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->serverData = (array) $request->getServerParams();

        $this->cookieData = (array) $request->getCookieParams();

        $this->getData = (array) $request->getQueryParams();

        $this->controller ??= 'index';
        $this->actionCamelCase = $this->action ??= $args['slug'] ?? 'index';

        $this->filterValue->sanitize($this->actionCamelCase, 'string', ['_', '-'], ' ');
        $this->filterValue->sanitize($this->actionCamelCase, 'titlecase');
        $this->filterValue->sanitize($this->actionCamelCase, 'lowercaseFirst');
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

        if (method_exists($this, 'action'.ucfirst((string) $this->actionCamelCase)) && \is_callable([$this, 'action'.ucfirst((string) $this->actionCamelCase)])) {
            $return = \call_user_func_array([$this, 'action'.ucfirst((string) $this->actionCamelCase)], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        } else {
            throw new HttpMethodNotAllowedException($request);
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
