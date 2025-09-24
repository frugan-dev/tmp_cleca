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

namespace App\Model;

use App\Factory\Auth\AuthInterface;
use App\Factory\Breadcrumb\BreadcrumbInterface;
use App\Factory\Cache\CacheInterface;
use App\Factory\Db\DbInterface;
use App\Factory\Debugbar\DebugbarInterface;
use App\Factory\Html\ViewHelperInterface;
use App\Factory\Logger\LoggerInterface;
use App\Factory\Mailer\MailerInterface;
use App\Factory\Pager\PagerInterface;
use App\Factory\Rbac\RbacInterface;
use App\Factory\Session\SessionInterface;
use App\Factory\Translator\TranslatorInterface;
use App\Factory\Tree\TreeInterface;
use App\Helper\HelperInterface;
use App\Service\Route\RouteParsingService;
use Laminas\Stdlib\ArrayUtils;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\App;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpUnauthorizedException;

class Controller extends Model
{
    public static string $env = 'default';

    public string $context = 'default';

    public string $controller;

    public ?string $controllerCamelCase = null;

    public ?string $action = null;

    public ?string $actionCamelCase = null;

    public array $serverData = [];

    public array $cookieData = [];

    public array $getData = [];

    public array $postData = [];

    public array $filesData = [];

    public array|string $responseData = [];

    public array $rowData = [];

    public array $viewData = [];

    public array $viewLayoutRegistryPaths = [];

    public array $viewRegistryPaths = [];

    public string $viewLayout = 'blank';

    public $routeArgs;

    public array $routeArgsArr = [];

    public array $routeParamsArr = [];

    public array $routeParamsArrWithoutPg = [];

    public $acceptRouteArgs;

    public ?string $methodCamelCase = null;

    public string $mimetype = 'text/html';

    public bool $isXhr;

    public ?string $xhrCamelCase = null;

    public int $statusCode = 200;

    public array $headers = [];

    public array $appendHeaders = [];

    public array $deleteHeadersKeys = [];

    public array $errors = [];

    public ?string $title = null;

    public ?string $supTitle = null;

    public ?string $subTitle = null;

    public $metaTitle;

    public $metaDescription;

    public $metaKeywords;

    public ?string $metaImage = null;

    public ?string $fullUrl = null;

    public ?string $slug = null;

    protected ViewHelperInterface $viewHelper;

    protected object $filterValue;

    public function __construct(
        protected ContainerInterface $container,
        protected AuthInterface $auth,
        protected RbacInterface $rbac,
        protected LoggerInterface $logger,
        protected SessionInterface $session,
        protected CacheInterface $cache,
        protected EventDispatcherInterface $dispatcher,
        protected DbInterface $db,
        protected TranslatorInterface $translator,
        protected MailerInterface $mailer,
        protected PagerInterface $pager,
        protected BreadcrumbInterface $breadcrumb,
        protected TreeInterface $tree,
        protected HelperInterface $helper,
        protected RouteParsingService $routeParsingService,
        protected DebugbarInterface $debugbar,
    ) {
        $this->app = $this->container->get(App::class);
        $this->filterValue = $this->container->get('filterValue');

        // This will always work, even when debugbar is disabled
        // $this->debugbar->addMessage($this->getName(), 'debug');

        // or using the syntax via __get magic method
        // $this->debugbar->messages->addMessage($this->getName(), 'debug');

        // or using the original syntax via ArrayAccess
        $this->debugbar['messages']->addMessage($this->getName(), 'debug');

        $this->init();
    }

    public function __invoke(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->serverData = (array) $request->getServerParams();

        $this->cookieData = (array) $request->getCookieParams();

        $this->getData = (array) $request->getQueryParams();

        if (\in_array($request->getMethod(), ['POST'], true)) {
            // https://discourse.slimframework.com/t/how-to-upload-multiple-files-with-getuploadedfiles/1055/3
            // https://www.slimframework.com/docs/v4/cookbook/uploading-files.html
            // https://www.php.net/manual/en/features.file-upload.post-method.php#100187
            // $_FILES will be empty if a user attempts to upload a file greater than post_max_size in your php.ini
            // post_max_size should be >= upload_max_filesize in your php.ini.
            $this->filesData = (array) $request->getUploadedFiles();

            // see addBodyParsingMiddleware()
            $this->postData = ArrayUtils::merge((array) $request->getParsedBody(), $this->filesData, true); // <-- with preserveNumericKeys
        }

        // https://heera.it/detect-ajax-request-php-frameworks
        // TODO - use https://github.com/slimphp/Slim-Http/#serverrequestisxhr
        $this->isXhr = (bool) ('XMLHttpRequest' === $request->getHeaderLine('X-Requested-With'));
        $this->xhrCamelCase = $this->isXhr ? 'Xhr' : null;

        $this->controllerCamelCase = $this->controller ??= 'index';

        $this->filterValue->sanitize($this->controllerCamelCase, 'string', ['_', '-'], ' ');
        $this->filterValue->sanitize($this->controllerCamelCase, 'titlecase');
        $this->filterValue->sanitize($this->controllerCamelCase, 'lowercaseFirst');
        $this->filterValue->sanitize($this->controllerCamelCase, 'string', ' ', '');

        $this->actionCamelCase = $this->action ??= $args['action'] ?? 'index';

        $this->filterValue->sanitize($this->actionCamelCase, 'string', ['_', '-'], ' ');
        $this->filterValue->sanitize($this->actionCamelCase, 'titlecase');
        $this->filterValue->sanitize($this->actionCamelCase, 'lowercaseFirst');
        $this->filterValue->sanitize($this->actionCamelCase, 'string', ' ', '');

        $return = $this->_authCheck($request, $response, $args);
        if (null !== $return) {
            return $return;
        }

        $response = $this->_cacheHttp($request, $response, $args);

        $response = $this->_cacheStorage($request, $response, $args);
        if (!empty($response->getBody()->getContents())) {
            return $response;
        }

        if (method_exists($this, 'action'.ucfirst((string) $this->actionCamelCase)) && \is_callable([$this, 'action'.ucfirst((string) $this->actionCamelCase)])) {
            $return = \call_user_func_array([$this, 'action'.ucfirst((string) $this->actionCamelCase)], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        } else {
            throw new HttpMethodNotAllowedException($request);
        }

        if (empty($this->acceptRouteArgs)) {
            if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
                $this->acceptRouteArgs = [
                    'routeName' => static::$env.'.index.lang',
                    'data' => [
                        'lang' => $this->lang->acceptCode,
                    ],
                    'full' => true,
                ];
            }
        }

        if (!empty($this->acceptRouteArgs)) {
            $contentNegotiationRedirect = $this->config['lang.'.static::$env.'.contentNegotiation.redirect'] ?? $this->config['lang.contentNegotiation.redirect'];

            if (!empty($contentNegotiationRedirect) && !$this->session->get('contentNegotiation')) {
                $this->session->set('contentNegotiation', true);

                return $response
                    ->withHeader('Location', $this->helper->Url()->urlFor($this->acceptRouteArgs))
                    ->withStatus(302)
                ;
            }
        }

        if (empty($this->routeArgsArr)) {
            foreach ($this->lang->codeArr as $langId => $langCode) {
                $this->routeArgsArr[$langId] = [
                    'routeName' => static::$env.'.index.lang',
                    'data' => [
                        'lang' => $langCode,
                    ],
                ];
            }
        }

        if (empty($this->routeParamsArr) && !empty($args['params'])) {
            $this->routeParamsArr = explode('/', (string) $args['params']);
        }

        $this->routeArgs ??= $this->routeArgsArr[$this->lang->id] ?? [
            'routeName' => static::$env.'.index',
        ];

        $this->fullUrl ??= $this->helper->Url()->urlFor(array_merge(
            $this->routeArgs,
            [
                'full' => true,
            ]
        ));

        $this->title ??= settingOrConfig(['brand.shortName', 'brand.name', 'company.shortName', 'company.name']);

        if (\is_string($this->metaTitle)) {
            $this->metaTitle = [$this->metaTitle];
        } elseif (empty($this->metaTitle)) {
            $this->metaTitle = [];
        }
        if (!empty($this->pager->pg) && $this->pager->pg > 1) {
            $this->metaTitle[] = \sprintf(__('Page %1$d'), $this->pager->pg);
        }
        $this->metaTitle[] = settingOrConfig(['brand.shortName', 'brand.name', 'company.shortName', 'company.name']);
        $this->metaTitle = implode($this->config['meta.title.separator'] ?? ' - ', $this->metaTitle);

        if (\is_string($this->metaDescription)) {
            $this->metaDescription = [$this->metaDescription];
        } elseif (empty($this->metaDescription)) {
            $this->metaDescription = [];
        }
        if (!empty($this->pager->pg) && $this->pager->pg > 1) {
            $this->metaDescription[] = \sprintf(__('Page %1$d'), $this->pager->pg);
        }
        $this->metaDescription = implode($this->config['meta.description.separator'] ?? ', ', array_filter($this->metaDescription));

        if (\is_string($this->metaKeywords)) {
            $this->metaKeywords = [$this->metaKeywords];
        } elseif (empty($this->metaKeywords)) {
            $this->metaKeywords = [];
        }
        $this->metaKeywords = implode(', ', array_filter($this->metaKeywords));

        $browscapInfo = $request->getAttribute('browscapInfo');
        $inIframe = $request->getAttribute('inIframe');

        array_push(
            $this->viewLayoutRegistryPaths,
            _ROOT.'/app/view/'.static::$env.'/layout',
            _ROOT.'/app/view/'.static::$env.'/partial'
        );

        array_push(
            $this->viewRegistryPaths,
            _ROOT.'/app/view/'.static::$env.'/controller/'.$this->controller,
            _ROOT.'/app/view/'.static::$env.'/base',
            _ROOT.'/app/view/'.static::$env.'/partial'
        );

        $this->viewData = [...$this->viewData, 'title' => $this->title, 'supTitle' => $this->supTitle, 'subTitle' => $this->subTitle, 'metaTitle' => $this->metaTitle, 'metaDescription' => $this->metaDescription, 'metaKeywords' => $this->metaKeywords, 'metaImage' => $this->metaImage, 'postData' => $this->postData, 'isXhr' => $this->isXhr, ...compact( // https://stackoverflow.com/a/30266377/3929620
            'browscapInfo',
            'inIframe'
        )];

        return $this->render($request, $response, $args);
    }

    public function init(): void {}

    public function reInit(): void {}

    public function renderBody(?RequestInterface $request = null, ?ResponseInterface $response = null, $args = null)
    {
        array_push(
            $this->viewLayoutRegistryPaths,
            _ROOT.'/app/view/'.self::$env.'/layout',
            _ROOT.'/app/view/'.self::$env.'/partial'
        );

        $this->view->getLayoutRegistry()->setPaths($this->viewLayoutRegistryPaths);

        array_push(
            $this->viewRegistryPaths,
            _ROOT.'/app/view/'.self::$env.'/controller/'.$this->controller,
            _ROOT.'/app/view/'.self::$env.'/base',
            _ROOT.'/app/view/'.self::$env.'/partial'
        );

        $this->view->getViewRegistry()->setPaths($this->viewRegistryPaths);

        $this->view->setLayout($this->viewLayout);
        $this->view->setView($this->action);

        $clientIp = $request->getAttribute('client-ip');

        $this->viewData = [
            // https://stackoverflow.com/a/11710168/3929620
            'env' => static::$env,
            'container' => $this->container,
            'config' => $this->config,
            'auth' => $this->auth,
            'rbac' => $this->rbac,
            'helper' => $this->helper,
            'session' => $this->session,
            'lang' => $this->lang,
            'pager' => $this->pager,
            'breadcrumb' => $this->breadcrumb,
            'dispatcher' => $this->dispatcher,
            'controller' => $this->controller,
            'controllerCamelCase' => $this->controllerCamelCase,
            'action' => $this->action,
            'actionCamelCase' => $this->actionCamelCase,
            'routeArgs' => $this->routeArgs,
            'routeArgsArr' => $this->routeArgsArr,
            'routeParamsArr' => $this->routeParamsArr,
            'routeParamsArrWithoutPg' => $this->routeParamsArrWithoutPg,
            'fullUrl' => $this->fullUrl,
            'referer' => $this->session->get('referer'),
            'httpUserAgent' => $this->serverData['HTTP_USER_AGENT'] ?? null,
            'httpAcceptLanguage' => $this->serverData['HTTP_ACCEPT_LANGUAGE'] ?? null,
            'hostByAddr' => !empty($clientIp) ? @gethostbyaddr((string) $clientIp) : null,
            'serverData' => $this->serverData,
            'cookieData' => $this->cookieData,
            ...compact( // https://stackoverflow.com/a/30266377/3929620
                'clientIp'
            ),
            ...$this->viewData,
        ];

        $this->view->addData($this->viewData);

        return $this->view->__invoke();
    }

    public function render(RequestInterface $request, ResponseInterface $response, $args)
    {
        $response->getBody()
            ->write($this->renderBody($request, $response, $args))
        ;

        if (method_exists($this, '_actionAfter'.ucfirst((string) $this->actionCamelCase)) && \is_callable([$this, '_actionAfter'.ucfirst((string) $this->actionCamelCase)])) {
            $return = \call_user_func_array([$this, '_actionAfter'.ucfirst((string) $this->actionCamelCase)], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        }

        // The Response object is immutable.
        // This method returns a copy of the Response object that has the new header value.
        // This method is destructive, and it replaces existing header values already associated with the same header name.
        $response = $response->withHeader('Content-type', $this->mimetype);

        if (!empty($this->headers)) {
            foreach ($this->headers as $key => $val) {
                $response = $response->withHeader($key, $val);
            }
        }

        if (!empty($this->appendHeaders)) {
            foreach ($this->appendHeaders as $key => $val) {
                $response = $response->withAddedHeader($key, $val);
            }
        }

        if (!empty($this->deleteHeadersKeys)) {
            foreach ($this->deleteHeadersKeys as $val) {
                $response = $response->withoutHeader($val);
            }
        }

        if (($statusCode = $response->getStatusCode()) !== 200) {
            $this->statusCode = $statusCode;
        }

        return $response->withStatus($this->statusCode);
    }

    protected function _authCheck(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (\in_array($this->controller, ['user'], true)
            && \in_array($this->action, ['login', 'logout'], true)) {
        } elseif (\in_array($this->controller, ['user'], true)
            && \in_array($this->action, ['reset'], true)) {
            if (!empty($request->getAttribute('hasIdentity'))) {
                $this->session->set(static::$env.'.redirectAfterLogout', $this->helper->Url()->getPathUrl());
            }
        } elseif (\in_array($this->controller, ['index'], true)
            && \in_array($this->action, ['offline'], true)) {
        } elseif (empty($request->getAttribute('hasIdentity'))) {
            $this->session->set(static::$env.'.redirectAfterLogin', $this->helper->Url()->getPathUrl());

            return $response
                ->withHeader('Location', $this->helper->Url()->urlFor([
                    'routeName' => static::$env.'.user',
                    'data' => [
                        'action' => 'login',
                    ],
                ]))
                ->withStatus(302)
            ;
        } elseif (\in_array($this->controller, ['error'], true)
            && \in_array($this->action, ['401', '404'], true)) {
        } elseif (\in_array($this->controller, ['index'], true)
            && \in_array($this->action, ['index'], true)) {
        } elseif (\in_array($this->controller, ['user'], true)
            && \in_array($this->action, ['switch'], true)) {
        } elseif (\in_array($this->controller, [$this->auth->getIdentity()['_type']], true)
            && \in_array($this->action, ['edit'], true)
            && !empty($args['params'])
            && $this->auth->getIdentity()['id'] === (int) $args['params']) {
        } elseif (!$this->rbac->isGranted($this->controller.'.'.static::$env.'.'.$this->action)) {
            throw new HttpUnauthorizedException($request);
        }
    }

    protected function _cacheHttp(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (!empty($this->config['cache.'.static::$env.'.http.provider.enabled'] ?? $this->config['cache.http.provider.enabled'])) {
            if (!empty($this->config['cache.'.static::$env.'.'.$this->controller.'.'.$this->action.'.http.provider.denyCache'] ?? $this->config['cache.'.static::$env.'.'.$this->controller.'.http.provider.denyCache'] ?? $this->config['cache.'.static::$env.'.http.provider.denyCache'] ?? $this->config['cache.http.provider.denyCache'])) {
                $response = $this->cacheHttpProvider->denyCache($response);
            } elseif (!empty($this->config['cache.'.static::$env.'.'.$this->controller.'.'.$this->action.'.http.provider.allowCache'] ?? $this->config['cache.'.static::$env.'.'.$this->controller.'.http.provider.allowCache'] ?? $this->config['cache.'.static::$env.'.http.provider.allowCache'] ?? $this->config['cache.http.provider.allowCache'])) {
                $response = $this->cacheHttpProvider->allowCache(
                    $response,
                    $this->config['cache.'.static::$env.'.http.type'] ?? $this->config['cache.http.type'],
                    $this->config['cache.'.static::$env.'.http.maxAge'] ?? $this->config['cache.http.maxAge'],
                    $this->config['cache.'.static::$env.'.http.mustRevalidate'] ?? $this->config['cache.http.mustRevalidate']
                );
            }

            if (!empty($this->config['cache.'.static::$env.'.'.$this->controller.'.'.$this->action.'.http.provider.expires'] ?? $this->config['cache.'.static::$env.'.'.$this->controller.'.http.provider.expires'] ?? $this->config['cache.'.static::$env.'.http.provider.expires'] ?? $this->config['cache.http.provider.expires'])) {
                $response = $this->cacheHttpProvider->withExpires(
                    $response,
                    $this->config['cache.'.static::$env.'.'.$this->controller.'.'.$this->action.'.http.provider.expires'] ?? $this->config['cache.'.static::$env.'.'.$this->controller.'.http.provider.expires'] ?? $this->config['cache.'.static::$env.'.http.provider.expires'] ?? $this->config['cache.http.provider.expires']
                );
            }

            if (!empty($this->config['cache.'.static::$env.'.'.$this->controller.'.'.$this->action.'.http.provider.lastModified'] ?? $this->config['cache.'.static::$env.'.'.$this->controller.'.http.provider.lastModified'] ?? $this->config['cache.'.static::$env.'.http.provider.lastModified'] ?? $this->config['cache.http.provider.lastModified'])) {
                $response = $this->cacheHttpProvider->withLastModified(
                    $response,
                    $this->config['cache.'.static::$env.'.'.$this->controller.'.'.$this->action.'.http.provider.lastModified'] ?? $this->config['cache.'.static::$env.'.'.$this->controller.'.http.provider.lastModified'] ?? $this->config['cache.'.static::$env.'.http.provider.lastModified'] ?? $this->config['cache.http.provider.lastModified']
                );
            }
        }

        return $response;
    }

    protected function _cacheStorage(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (!empty($this->config['cache.'.static::$env.'.'.$this->controller.'.'.$this->action.'.storage.enabled'] ?? $this->config['cache.'.static::$env.'.'.$this->controller.'.storage.enabled'] ?? $this->config['cache.'.static::$env.'.storage.enabled'] ?? $this->config['cache.storage.enabled'])) {
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

            $response = $response->withHeader($this->config['cache.'.static::$env.'.storage.body.header'] ?? $this->config['cache.storage.body.header'], 'OK');
        }

        return $response;
    }
}
