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

namespace App\Middleware\Env\Back;

use App\Helper\HelperInterface;
use App\Service\Route\RouteParsingService;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ModMiddleware extends \App\Middleware\ModMiddleware implements MiddlewareInterface
{
    public static string $env = 'back';

    public function __construct(
        protected ContainerInterface $container,
        protected HelperInterface $helper,
        protected RouteParsingService $routeParsingService,
    ) {
        parent::__construct($container, $routeParsingService);
    }

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($request->getAttribute('hasIdentity')) {
            $mods = [];

            foreach ($this->container->get('mods') as $controller) {
                if ($this->container->has('Mod\\'.ucfirst((string) $controller).'\\'.ucfirst((string) static::$env))) {
                    $mods[] = [
                        'controller' => $controller,
                        'weight' => $this->container->get('Mod\\'.ucfirst((string) $controller).'\\'.ucfirst((string) static::$env))->weight,
                    ];
                }
            }

            $this->container->set('modsSortedByWeight', $this->helper->Arrays()->uasortBy($mods, 'weight'));
        } elseif ($this->container->has('Mod\User\\'.ucfirst((string) static::$env))) {
            $this->container->get('Mod\User\\'.ucfirst((string) static::$env));
        }

        return parent::process($request, $handler);
    }
}
