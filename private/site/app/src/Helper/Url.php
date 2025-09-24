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

namespace App\Helper;

use Laminas\Stdlib\ArrayUtils;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UriInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

class Url extends Helper
{
    public static array $params = [];

    public function urlFor($params)
    {
        if (!\is_array($params)) {
            $params = [
                'routeName' => (string) $params,
            ];
        }

        static::$params = ArrayUtils::merge(
            [
                'routeName' => null,
                // Associative array of route pattern placeholders and replacement values.
                'data' => [],
                // Associative array of query parameters to be appended to the generated url.
                'queryParams' => [],
                'fragment' => null,
                'full' => false,
            ],
            $params
        );

        if (empty(static::$params['data']['lang'])) {
            if ($this->container->has('lang')) {
                static::$params['data']['lang'] = $this->lang->code;
            }
        }

        if (empty(static::$params['data']['v'])) {
            static::$params['data']['v'] = $this->config->get('api.version');
        }

        $prefix = str_contains((string) static::$params['routeName'], '.') ? substr((string) static::$params['routeName'], 0, strpos((string) static::$params['routeName'], '.')) : static::$params['routeName'];

        $this->container->get(EventDispatcherInterface::class)->dispatch(new GenericEvent(), 'event.'.$prefix.'.'.$this->getShortName().'.'.__FUNCTION__.'.after');

        // FIXED - fallback check
        // EventMiddleware depends on RoutingMiddleware, so EventMiddleware is not loaded
        // when requests are not referenced in the routing rules
        if (empty(static::$params['data']['catform_id'])) {
            static::$params['data']['catform_id'] = 0;
        }

        $routeParser = $this->app->getRouteCollector()->getRouteParser();

        return (static::$params['full'] ? $this->getBaseUrl() : '').$routeParser->urlFor(static::$params['routeName'], static::$params['data'], static::$params['queryParams']).static::$params['fragment'];
    }

    // https://github.com/slimphp/Slim/pull/2638
    // https://discourse.slimframework.com/t/slim-4-get-base-url/3406/9
    // https://www.slimframework.com/docs/v4/objects/request.html#obtain-base-path-from-within-route
    // https://github.com/slimphp/Slim/pull/2398/files/897958f4e6efb6d297b098ada9d9cdc01013fe92#r170448029
    public function getBaseUrl()
    {
        if ($this->container->has('request')) {
            $uri = $this->request->getUri();

            if ($uri instanceof UriInterface) {
                $scheme = $uri->getScheme();
                $host = $uri->getHost();
                $port = $uri->getPort();
            }
        }

        if (empty($port) && isset($_SERVER['SERVER_PORT'])) {
            $port = $_SERVER['SERVER_PORT'];
        }

        if (empty($scheme) && isset($_SERVER['HTTPS'], $port)) {
            $scheme = (!empty($_SERVER['HTTPS']) && 'off' !== $_SERVER['HTTPS'] || \Safe\preg_match('~443$~', (string) $port)) ? 'https' : 'http';
        }

        if (empty($host) && isset($_SERVER['HTTP_HOST'])) {
            $host = $_SERVER['HTTP_HOST'];
        }

        return (!empty($scheme) ? $scheme.':' : '')
            .(!empty($host) ? '//'.$host : '')
            .(!empty($port) && !\in_array((int) $port, [80, 443], true) ? ':'.$port : '')
            .$this->app->getBasePath();
    }

    public function getPathUrl()
    {
        if ($this->container->has('request')) {
            $uri = $this->request->getUri();

            if ($uri instanceof UriInterface) {
                $path = $uri->getPath();
                $query = $uri->getQuery();
                $fragment = $uri->getFragment();
            }
        }

        return ($path ?? '').(!empty($query) ? '?'.$query : '').(!empty($fragment) ? '#'.$fragment : '');
    }

    // https://developer.wordpress.org/reference/functions/set_url_scheme/
    // http://stackoverflow.com/a/2762083/3929620
    public function addScheme($url, $scheme = 'http://')
    {
        // return parse_url($url, PHP_URL_SCHEME) === null ? $scheme . $url : $url;
        return (!\Safe\preg_match('~^(?:f|ht)tps?://~i', (string) $url)) ? $scheme.$url : $url;
    }

    public function removeScheme($url)
    {
        return (\Safe\preg_match('~^(?:f|ht)tps?://~i', (string) $url, $matches)) ? str_replace($matches[0], '', (string) $url) : $url;
    }

    public function httpBuildQuery()
    {
        $query = \call_user_func_array('http_build_query', \func_get_args());

        // https://stackoverflow.com/a/8171667/3929620
        return \Safe\preg_replace([
            '~%5B\d+%5D(?==)~',
            '~%2B~',
        ], [
            '',
            '+',
        ], (string) $query);
    }

    // https://gist.github.com/leogopal/b429f9700d473a55f70819dc6e5195f0
    // https://gist.github.com/ghalusa/6c7f3a00fd2383e5ef33
    // https://www.expertsphp.com/how-to-get-youtube-video-id-from-url-in-php/
    public function getYoutubeVideoId($url)
    {
        /**
         * http://youtu.be/ID
         * http://www.youtube.com/embed/ID
         * http://www.youtube.com/watch?v=ID
         * http://www.youtube.com/?v=ID
         * http://www.youtube.com/v/ID
         * http://www.youtube.com/e/ID
         * http://www.youtube.com/user/username#p/u/11/ID
         * http://www.youtube.com/leogopal#p/c/playlistID/0/ID
         * http://www.youtube.com/watch?feature=player_embedded&v=ID
         * http://www.youtube.com/?feature=player_embedded&v=ID.
         */
        $pattern = '%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i';

        if (\Safe\preg_match($pattern, (string) $url, $match)) {
            return $match[1];
        }

        return false;
    }
}
