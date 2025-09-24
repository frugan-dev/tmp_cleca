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

use App\Config\ConfigArrayWrapper;
use App\Factory\ArraySiblings\ArraySiblingsFactory;
use App\Factory\ArraySiblings\ArraySiblingsInterface;
use App\Factory\Auth\AuthFactory;
use App\Factory\Auth\AuthInterface;
use App\Factory\Breadcrumb\BreadcrumbFactory;
use App\Factory\Breadcrumb\BreadcrumbInterface;
use App\Factory\Cache\CacheFactory;
use App\Factory\Cache\CacheInterface;
use App\Factory\Db\DbFactory;
use App\Factory\Db\DbInterface;
use App\Factory\Debugbar\DebugbarFactory;
use App\Factory\Debugbar\DebugbarInterface;
use App\Factory\Filter\Sanitize\AddScheme;
use App\Factory\Filter\Sanitize\Arrays;
use App\Factory\Filter\Sanitize\ArraysMixed;
use App\Factory\Filter\Sanitize\EscapeHtml;
use App\Factory\Filter\Sanitize\FixEncoding;
use App\Factory\Filter\Sanitize\FloatArray;
use App\Factory\Filter\Sanitize\Floats;
use App\Factory\Filter\Sanitize\Hex as SanitizeHex;
use App\Factory\Filter\Sanitize\IntvalArray;
use App\Factory\Filter\Sanitize\Linearize;
use App\Factory\Filter\Sanitize\Lines;
use App\Factory\Filter\Sanitize\LinesKeyValue as SanitizeLinesKeyValue;
use App\Factory\Filter\Sanitize\PhoneUri;
use App\Factory\Filter\Sanitize\PurifyHtml;
use App\Factory\Filter\Sanitize\StripEmoji;
use App\Factory\Filter\Sanitize\StripTags;
use App\Factory\Filter\Sanitize\TrimArray;
use App\Factory\Filter\Sanitize\Webalize;
use App\Factory\Filter\Validate\Error;
use App\Factory\Filter\Validate\Hex as ValidateHex;
use App\Factory\Filter\Validate\Ini;
use App\Factory\Filter\Validate\LinesKeyValue as ValidateLinesKeyValue;
use App\Factory\Filter\Validate\RecaptchaV2;
use App\Factory\Filter\Validate\RecaptchaV3;
use App\Factory\Filter\Validate\UrlStrict;
use App\Factory\Html\ViewHelperFactory;
use App\Factory\Html\ViewHelperInterface;
use App\Factory\Logger\LoggerFactory;
use App\Factory\Logger\LoggerInterface;
use App\Factory\Mailer\MailerFactory;
use App\Factory\Mailer\MailerInterface;
use App\Factory\Mailer\OAuth2\Authenticator\XOAuth2Authenticator;
use App\Factory\Mailer\OAuth2\TokenProvider\TokenProviderFactory;
use App\Factory\Mailer\OAuth2\TokenProvider\TokenProviderInterface;
use App\Factory\Mailer\OAuth2\Transport\OAuthEsmtpTransportFactoryDecorator;
use App\Factory\Mailer\Provider\OAuth2\Microsoft\Office365OAuthTokenProvider;
use App\Factory\Mailer\Provider\OAuth2\Mock\FakeOAuthTokenProvider;
use App\Factory\Mailer\Provider\OAuth2\Mock\MockOAuthTokenProvider;
use App\Factory\Mailer\Provider\ProviderRegistry;
use App\Factory\Mailer\Transport\TransportFactoryRegistry;
use App\Factory\Pager\PagerFactory;
use App\Factory\Pager\PagerInterface;
use App\Factory\Rbac\RbacFactory;
use App\Factory\Rbac\RbacInterface;
use App\Factory\Session\SessionFactory;
use App\Factory\Session\SessionInterface;
use App\Factory\Translator\EventListener\DynamicTranslationListener;
use App\Factory\Translator\EventListener\MissingTranslationListener;
use App\Factory\Translator\TranslatorFactory;
use App\Factory\Translator\TranslatorInterface;
use App\Factory\Tree\TreeFactory;
use App\Factory\Tree\TreeInterface;
use App\Helper\Helper;
use App\Helper\HelperInterface;
use App\Service\HtmlMinifyService;
use App\Service\Route\PreRouteParsingService;
use App\Service\Route\RouteParsingInterface;
use App\Service\Route\RouteParsingService;
use App\Service\TranslationMigrationService;
use Aura\Filter\FilterFactory;
use Aura\View\ViewFactory;
use Cohensive\OEmbed\Factory as OEmbedFactory;
use Illuminate\Config\Repository;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\HttpCache\CacheProvider;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransportFactory;
use Symfony\Component\Mailer\Transport\TransportFactoryInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/*
 * https://getinstance.com/php-dependency-injection/
 * https://stackoverflow.com/a/49507209/3929620
 *
 * Differences between object instantiation and retrieval methods in PHP-DI:
 *
 * get():
 * - Retrieves an existing instance from the dependency injection container.
 * - Does not create a new instance if one already exists.
 * - Commonly used for singleton services or shared instances.
 * - Example: $service = $container->get(MyService::class); // Always returns the same instance.
 *
 * create():
 * - Creates a new instance of the object every time it's called.
 * - Does not depend on container definitions (e.g., alias or factory).
 * - Useful when you need a fresh instance every time.
 * - Allows passing custom parameters to the constructor.
 * - Example: $service = $container->create(MyService::class, ['param' => 'value']);
 *
 * make():
 * - Similar to `get()`, but resolves the entry every time it is called.
 * - If the entry is an alias, it is resolved every time (but the dependencies of the alias are resolved only once).
 * - It should be used for objects that are not stored in the container (e.g., non-services or stateful objects).
 * - For type safety and avoiding container coupling, use `DI\FactoryInterface` where possible.
 * - Useful for creating non-singleton objects or overriding constructor parameters.
 * - Example: $service = $container->make(MyService::class, ['param' => 'value']); // New instance with custom parameters.
 *
 * autowire():
 * - Automatically resolves and injects class dependencies.
 * - Very convenient for automatic dependency injection without manual configuration.
 * - Example: $service = $container->autowire(MyService::class);
 *
 * factory():
 * - Creates a factory method or object for generating multiple instances.
 * - Useful for objects with complex initialization logic or varying configurations.
 * - Example: $service = $container->factory(MyService::class)->create();
 */
return static function (ConfigArrayWrapper|Repository $config) {
    $return = [
        'config' => $config,

        'deps' => [],

        'widgets' => [],

        'env' => fn (ContainerInterface $container) => getEnvironment($container),

        'envs' => function () {
            $envs = [];

            foreach (\Safe\glob(_ROOT.'/app/src/Controller/Env/*') as $dir) {
                if (is_dir($dir)) {
                    $envs[] = mb_strtolower(basename($dir), 'UTF-8');
                }
            }

            /*foreach (glob(_ROOT . '/app/view/*') as $dir) {
                if (is_dir($dir)) {
                    $envs[] = mb_strtolower(basename($dir), 'UTF-8');
                }
            }*/

            // http://stackoverflow.com/a/8321709
            // return array_flip(array_flip($envs));
            return $envs;
        },

        'mods' => function () {
            $mods = [];

            foreach (\Safe\glob(_ROOT.'/app/src/Controller/Mod/*') as $dir) {
                if (is_dir($dir)) {
                    $mods[] = mb_strtolower(basename($dir), 'UTF-8');
                }
            }

            return $mods;
        },

        'cacheHttpProvider' => fn () => new CacheProvider(),

        // https://github.com/laminas/laminas-view
        // https://stackoverflow.com/a/50644153/3929620
        'view' => fn (ViewHelperInterface $ViewHelperInterface) => new ViewFactory()->newInstance($ViewHelperInterface),

        'filter' => function (ContainerInterface $container) {
            $validateFactories = [
                'error' => fn () => $container->get(Error::class),
                'hex' => fn () => $container->get(ValidateHex::class),
                'ini' => fn () => $container->get(Ini::class),
                'linesKeyValue' => fn () => $container->get(ValidateLinesKeyValue::class),
                'urlStrict' => fn () => $container->get(UrlStrict::class),
                'recaptchaV2' => fn () => $container->get(RecaptchaV2::class),
                'recaptchaV3' => fn () => $container->get(RecaptchaV3::class),
            ];

            $sanitizeFactories = [
                'addScheme' => fn () => $container->get(AddScheme::class),
                'array' => fn () => $container->get(Arrays::class),
                'arrayMixed' => fn () => $container->get(ArraysMixed::class),
                'escapeHtml' => fn () => $container->get(EscapeHtml::class),
                'fixEncoding' => fn () => $container->get(FixEncoding::class),
                'float' => fn () => $container->get(Floats::class),
                'hex' => fn () => $container->get(SanitizeHex::class),
                'intvalArray' => fn () => $container->get(IntvalArray::class),
                'lines' => fn () => $container->get(Lines::class),
                'linesKeyValue' => fn () => $container->get(SanitizeLinesKeyValue::class),
                'floatArray' => fn () => $container->get(FloatArray::class),
                'linearize' => fn () => $container->get(Linearize::class),
                'phoneUri' => fn () => $container->get(PhoneUri::class),
                'purifyHtml' => fn () => $container->get(PurifyHtml::class),
                'stripEmoji' => fn () => $container->get(StripEmoji::class),
                'stripTags' => fn () => $container->get(StripTags::class),
                'trimArray' => fn () => $container->get(TrimArray::class),
                'webalize' => fn () => $container->get(Webalize::class),
            ];

            return new FilterFactory(
                $validateFactories,
                $sanitizeFactories
            );
        },

        'filterSubject' => fn (ContainerInterface $container) => $container->get('filter')->newSubjectFilter(),

        'filterValue' => fn (ContainerInterface $container) => $container->get('filter')->newValueFilter(),

        App::class => function (ContainerInterface $container) {
            AppFactory::setContainer($container);

            return AppFactory::create();
        },

        // Services
        HtmlMinifyService::class => DI\autowire(),
        PreRouteParsingService::class => DI\autowire(),
        RouteParsingService::class => DI\autowire(),
        TranslationMigrationService::class => DI\autowire(),

        RouteParsingInterface::class => DI\get(RouteParsingService::class),

        // Listeners
        DynamicTranslationListener::class => DI\autowire(),
        MissingTranslationListener::class => DI\autowire(),

        // Factories
        TreeInterface::class => DI\autowire(TreeFactory::class),
        HelperInterface::class => DI\autowire(Helper::class),
        AuthInterface::class => DI\autowire(AuthFactory::class),
        PagerInterface::class => DI\autowire(PagerFactory::class),
        ArraySiblingsInterface::class => DI\autowire(ArraySiblingsFactory::class),

        LoggerInterface::class => DI\factory([LoggerFactory::class, 'create']),
        RbacInterface::class => DI\factory([RbacFactory::class, 'create']),
        DbInterface::class => DI\factory([DbFactory::class, 'create']),
        ViewHelperInterface::class => DI\factory([ViewHelperFactory::class, 'create']),
        MailerInterface::class => DI\factory([MailerFactory::class, 'create']),
        SessionInterface::class => DI\factory([SessionFactory::class, 'create']),
        BreadcrumbInterface::class => DI\factory([BreadcrumbFactory::class, 'create']),
        DebugbarInterface::class => DI\factory([DebugbarFactory::class, 'create']),

        /*
         * PHP-DI Factory Method Parameter Injection Notes:
         *
         * When using DI\factory([ClassName::class, 'methodName']), PHP-DI automatically injects:
         * 1. First argument: ContainerInterface $container (always passed by default)
         * 2. Second argument: FactoryDefinition $definition (internal PHP-DI object)
         * 3. Additional arguments: Any dependencies resolved by type-hinting
         *
         * To override this behavior and pass custom parameters, use the ->parameter() method:
         *
         * Example:
         * SomeInterface::class => DI\factory([SomeFactory::class, 'create'])
         *     ->parameter('customParam', 'customValue')
         *     ->parameter('anotherParam', DI\get('some.service')),
         *
         * Best Practice: Factory methods should NOT expect ContainerInterface as parameter.
         * Instead, inject the container via constructor dependency injection.
         * This keeps factory methods clean and focused on their specific parameters.
         */
        TranslatorInterface::class => DI\factory([TranslatorFactory::class, 'create'])
            ->parameter('langId', null),

        'lang' => DI\get(TranslatorInterface::class),

        CacheInterface::class => DI\factory([CacheFactory::class, 'create'])
            ->parameter('adapter', null),

        // Validators
        Error::class => DI\autowire(),
        ValidateHex::class => DI\autowire(),
        Ini::class => DI\autowire(),
        ValidateLinesKeyValue::class => DI\autowire(),
        UrlStrict::class => DI\autowire(),
        RecaptchaV2::class => DI\autowire(),
        RecaptchaV3::class => DI\autowire(),

        // Sanitizers
        AddScheme::class => DI\autowire(),
        Arrays::class => DI\autowire(),
        ArraysMixed::class => DI\autowire(),
        EscapeHtml::class => DI\autowire(),
        FixEncoding::class => DI\autowire(),
        Floats::class => DI\autowire(),
        SanitizeHex::class => DI\autowire(),
        IntvalArray::class => DI\autowire(),
        Lines::class => DI\autowire(),
        SanitizeLinesKeyValue::class => DI\autowire(),
        FloatArray::class => DI\autowire(),
        Linearize::class => DI\autowire(),
        PhoneUri::class => DI\autowire(),
        PurifyHtml::class => DI\autowire(),
        StripEmoji::class => DI\autowire(),
        StripTags::class => DI\autowire(),
        TrimArray::class => DI\autowire(),
        Webalize::class => DI\autowire(),

        // Mailer, OAuth2
        ProviderRegistry::class => function (ContainerInterface $container) {
            $registry = new ProviderRegistry(
                $container,
                $container->get(LoggerInterface::class)
            );

            $config = $container->get('config');
            $registry->autoRegister($config);

            return $registry;
        },

        Office365OAuthTokenProvider::class => function (ContainerInterface $container) {
            $config = $container->get('config');
            $providerConfig = $config->get('mail.oauth2.providers.microsoft-office365.config', []);

            if (empty($providerConfig['tenant']) || empty($providerConfig['client_id']) || empty($providerConfig['client_secret'])) {
                throw new RuntimeException('Microsoft Office365 OAuth2 provider not properly configured (missing tenant, client_id, or client_secret)');
            }

            return Office365OAuthTokenProvider::fromConfig(
                $container,
                $container->get(LoggerInterface::class),
                $container->get(HelperInterface::class),
                $providerConfig,
                $container->has(CacheInterface::class) && !$config->get('debug.enabled') ? $container->get(CacheInterface::class) : null
            );
        },

        FakeOAuthTokenProvider::class => function (ContainerInterface $container) {
            $config = $container->get('config');
            $providerConfig = $config->get('mail.oauth2.providers.fake.config', []);

            return FakeOAuthTokenProvider::fromConfig(
                $container,
                $container->get(LoggerInterface::class),
                $container->get(HelperInterface::class),
                $providerConfig,
                $container->has(CacheInterface::class) && !$config->get('debug.enabled') ? $container->get(CacheInterface::class) : null
            );
        },

        MockOAuthTokenProvider::class => function (ContainerInterface $container) {
            $config = $container->get('config');
            $providerConfig = $config->get('mail.oauth2.providers.mock.config', []);

            return MockOAuthTokenProvider::fromConfig(
                $container,
                $container->get(LoggerInterface::class),
                $container->get(HelperInterface::class),
                $providerConfig,
                $container->has(CacheInterface::class) && !$config->get('debug.enabled') ? $container->get(CacheInterface::class) : null
            );
        },

        TokenProviderInterface::class => function (ContainerInterface $container) {
            $factory = $container->get(TokenProviderFactory::class);

            try {
                return $factory->createWithFallback();
            } catch (Exception $e) {
                $container->get(LoggerInterface::class)->warning(
                    'No OAuth2 providers available in container',
                    ['error' => $e->getMessage()]
                );

                // Return null to allow fallback to SMTP
                return null;
            }
        },

        'transportFactoryInterface' => DI\create(EsmtpTransportFactory::class),

        TransportFactoryInterface::class => fn (ContainerInterface $container, TokenProviderFactory $tokenProviderFactory, LoggerInterface $logger) => new OAuthEsmtpTransportFactoryDecorator($container, $container->get('transportFactoryInterface'), $tokenProviderFactory, $logger),

        TokenProviderFactory::class => DI\autowire(TokenProviderFactory::class),
        XOAuth2Authenticator::class => DI\autowire(XOAuth2Authenticator::class),
        TransportFactoryRegistry::class => DI\autowire(TransportFactoryRegistry::class),

        EventDispatcherInterface::class => DI\autowire(EventDispatcher::class),

        PsrLoggerInterface::class => fn (ContainerInterface $container) => $container->get(LoggerInterface::class),

        HttpClientInterface::class => fn () => HttpClient::create(),

        OEmbedFactory::class => DI\autowire(),
    ];

    foreach (\Safe\glob(_ROOT.'/app/src/Controller/Mod/*') as $dir) {
        if (is_dir($dir)) {
            $controller = basename((string) $dir);

            foreach (\Safe\glob(_ROOT.'/app/src/Controller/Mod/'.$controller.'/*') as $dir) {
                if (is_dir($dir)) {
                    $env = basename((string) $dir);

                    $namespace = ucwords('\\'._NAMESPACE_BASE.'\Controller\Mod\\'.$controller.'\\'.$env.'\\'.$controller, '\\');

                    if (class_exists($namespace)) {
                        $return[ucwords('Mod\\'.$controller.'\\'.$env, '\\')] = DI\get($namespace);
                    }
                }
            }
        }
    }

    foreach (\Safe\glob(_ROOT.'/app/src/Controller/Env/*') as $dir) {
        if (is_dir($dir)) {
            $env = basename((string) $dir);

            $namespace = ucwords('\\'._NAMESPACE_BASE.'\Controller\Env\\'.$env.'\AuthAdapter', '\\');

            if (class_exists($namespace)) {
                $return[ucwords('Env\\'.$env.'\AuthAdapter', '\\')] = DI\get($namespace);
            }
        }
    }

    return $return;
};
