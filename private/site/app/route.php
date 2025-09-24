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

use App\Controller\Env;
use App\Helper\HelperInterface;
use App\Middleware\BreadcrumbMiddleware;
use App\Middleware\BrowscapMiddleware;
use App\Middleware\CacheHttpMiddleware;
use App\Middleware\Env\Api\RateLimitMiddleware as ApiRateLimitMiddleware;
use App\Middleware\GoogleAnalyticsMiddleware;
use Slim\App;
use Slim\Routing\RouteCollectorProxy;

// https://github.com/juliangut/slim-routing
// https://stackoverflow.com/a/48908814/3929620
// Inside route closure, $this is bound to the instance of Psr\Container\ContainerInterface.
// Optional segments can only occur at the end of a route.
return static function (App $app): void {
    $container = $app->getContainer();

    $app->group('/api', function (RouteCollectorProxy $group): void {
        foreach (\Safe\glob(_ROOT.'/app/src/Controller/Mod/*') as $dir) {
            if (is_dir($dir)) {
                $controller = basename($dir);

                if (is_dir($dir.'/Api')) {
                    $group->map(['GET', 'POST', 'PUT', 'DELETE'], '/v{v:[0-9]+}/'.mb_strtolower($controller, 'UTF-8').'[/{action}[/{params:.*}]]', '\\'._NAMESPACE_BASE.'\Controller\Mod\\'.$controller.'\Api\\'.$controller)->setName('api.'.$this->get(HelperInterface::class)->Nette()->Strings()->lower($controller));
                }
            }
        }

        $group->map(['GET', 'POST'], '[/v{v:[0-9]+}[/{action}[/{params:.*}]]]', '\\'._NAMESPACE_BASE.'\Controller\Env\Api\Controller')->setName('api');
    })->add(ApiRateLimitMiddleware::class);

    $app->group('/'.$container->get('config')['auth.path'], function (RouteCollectorProxy $group): void {
        $group->map(['GET', 'POST'], '', Env\Back\Controller::class)->setName('back.index');
        $group->map(['GET', 'POST'], '/{lang:[a-z]{2}}', Env\Back\Controller::class)->setName('back.index.lang');
        $group->map(['GET', 'POST'], '/{lang:[a-z]{2}}/index/{params:.*}', Env\Back\Controller::class)->setName('back.index.params');
        $group->map(['GET', 'POST'], '/{action:[^\/]+}', Env\Back\Controller::class)->setName('back.action');

        foreach (\Safe\glob(_ROOT.'/app/src/Controller/Mod/*') as $dir) {
            if (is_dir($dir)) {
                $controller = basename($dir);

                if (is_dir($dir.'/Back')) {
                    $group->map(['GET', 'POST'], '/{lang:[a-z]{2}}/'.mb_strtolower($controller, 'UTF-8').'[/{action:[^\/]+}]', '\\'._NAMESPACE_BASE.'\Controller\Mod\\'.$controller.'\Back\\'.$controller)->setName('back.'.$this->get(HelperInterface::class)->Nette()->Strings()->lower($controller));
                    $group->map(['GET', 'POST'], '/{lang:[a-z]{2}}/'.mb_strtolower($controller, 'UTF-8').'/{action:[^\/]+}/{params:.*}', '\\'._NAMESPACE_BASE.'\Controller\Mod\\'.$controller.'\Back\\'.$controller)->setName('back.'.$this->get(HelperInterface::class)->Nette()->Strings()->lower($controller).'.params');
                }
            }
        }
    })->add(BrowscapMiddleware::class)
        ->add(BreadcrumbMiddleware::class)
        ->add(CacheHttpMiddleware::class)
    ;

    $app->group('/', function (RouteCollectorProxy $group): void {
        $group->get('{slug:[^\/]+}.js', Env\Js\Controller::class)->setName('js'); // @phpstan-ignore-line
    });

    /*$app->group('/', function (RouteCollectorProxy $group): void {
        $group->get('{lang:[a-z]{2}}/{slug:[^\.\/]+}.xml', Env\Xml\Controller::class)->setName('xml'); // @phpstan-ignore-line
        $group->get('{slug:[^\.\/]+}.xml', Env\Xml\Controller::class)->setName('xml.index'); // @phpstan-ignore-line
    });*/

    $app->group('/', function (RouteCollectorProxy $group): void {
        $group->get('', '\\'._NAMESPACE_BASE.'\Controller\Env\Front\Controller')->setName('front.index');
        $group->get('{lang:[a-z]{2}}'.$this->get('config')['url.extension'], '\\'._NAMESPACE_BASE.'\Controller\Env\Front\Controller')->setName('front.index.lang'); // @phpstan-ignore-line
        $group->get('{lang:[a-z]{2}}/index/{params:.*}'.$this->get('config')['url.extension'], '\\'._NAMESPACE_BASE.'\Controller\Env\Front\Controller')->setName('front.index.params');

        foreach (\Safe\glob(_ROOT.'/app/src/Controller/Mod/*') as $dir) {
            if (is_dir($dir)) {
                $controller = basename($dir);

                if (is_dir($dir.'/Front')) {
                    $group->map(['GET', 'POST'], '{lang:[a-z]{2}}/{catform_id:\d+}/'.mb_strtolower($controller, 'UTF-8').'[/{action:[^\/]+}]'.$this->get('config')['url.extension'], '\\'._NAMESPACE_BASE.'\Controller\Mod\\'.$controller.'\Front\\'.$controller)->setName('front.'.$this->get(HelperInterface::class)->Nette()->Strings()->lower($controller));
                    $group->map(['GET', 'POST'], '{lang:[a-z]{2}}/{catform_id:\d+}/'.mb_strtolower($controller, 'UTF-8').'/{action:[^\/]+}/{params:.*}'.$this->get('config')['url.extension'], '\\'._NAMESPACE_BASE.'\Controller\Mod\\'.$controller.'\Front\\'.$controller)->setName('front.'.$this->get(HelperInterface::class)->Nette()->Strings()->lower($controller).'.params');
                }
            }
        }

        $group->map(['GET', 'POST'], '{lang:[a-z]{2}}/{catform_id:\d+}/{action:[^\/]+}'.$this->get('config')['url.extension'], '\\'._NAMESPACE_BASE.'\Controller\Env\Front\Controller')->setName('front.action');
        $group->map(['GET', 'POST'], '{lang:[a-z]{2}}/{catform_id:\d+}/{action:[^\/]+}/{params:.*}'.$this->get('config')['url.extension'], '\\'._NAMESPACE_BASE.'\Controller\Env\Front\Controller')->setName('front.action.params');
    })->add(BrowscapMiddleware::class)
        ->add(BreadcrumbMiddleware::class)
        ->add(CacheHttpMiddleware::class)
        ->add(GoogleAnalyticsMiddleware::class)
    ;
};
