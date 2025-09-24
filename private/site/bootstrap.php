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
use App\Config\ConfigManager;
use Composer\InstalledVersions;
use DI\ContainerBuilder;
use Slim\App;

require _ROOT.'/vendor/autoload.php';

ini_set('short_open_tag', 'Off');

try {
    // https://stackoverflow.com/a/42888475/3929620
    // https://github.com/nadar/php-composer-reader
    // https://mixable.blog/php-get-version-details-from-composer-json/
    $content = file_get_contents(_ROOT.'/composer.json');
    $content = json_decode($content, true);

    if (isset($content['autoload']['psr-4'])) {
        $psr4 = array_key_first($content['autoload']['psr-4']);
        define('_NAMESPACE_BASE', substr((string) $psr4, 0, strpos((string) $psr4, '\\')));
    } else {
        throw new Exception(sprintf(
            'Invalid %1$s.',
            'composer.json'
        ));
    }

    // https://mixable.blog/php-get-version-details-from-composer-json/
    if (defined('_SF_PACKAGE') && InstalledVersions::isInstalled(_SF_PACKAGE)) {
        define('_APP_VERSION', InstalledVersions::getPrettyVersion(_SF_PACKAGE));
    } elseif (isset($content['version'])) {
        define('_APP_VERSION', InstalledVersions::getRootPackage()['pretty_version']);
    }
} catch (Exception $e) {
    // https://github.com/phpro/grumphp/blob/master/doc/tasks/phpparser.md#no_exit_statements
    exit($e->getMessage());
}

if (file_exists(_ROOT.'/app/function.php')) {
    require _ROOT.'/app/function.php';
}

$suffix = '';
$env = '.env';
$envs = [$env];

if (defined('_APP_ENV')) {
    $suffix .= '.'._APP_ENV;
    $envs[] = $env.$suffix;
}

// docker -> minideb
if (!empty($_SERVER['APP_ENV'])) {
    $suffix .= '.'.$_SERVER['APP_ENV'];
    $envs[] = $env.$suffix;
}

$envs = array_unique(array_filter($envs));

try {
    $dotenv = Dotenv\Dotenv::createImmutable(_ROOT.'/app/config', $envs, false);
    $dotenv->load();
    $dotenv->required(['DB_1_HOST', 'DB_1_NAME', 'DB_1_USER', 'DB_1_PASS']);

    // https://github.com/vlucas/phpdotenv/issues/231#issuecomment-663879815
    foreach ($_ENV as $key => $val) {
        if (ctype_digit((string) $val)) {
            $dotenv->required($key)->isInteger();
            $_ENV[$key] = (int) $val;
        } elseif (!empty($val) && !is_numeric($val) && ($newVal = filter_var($_ENV[$key], \FILTER_VALIDATE_BOOLEAN, \FILTER_NULL_ON_FAILURE)) !== null) {
            $dotenv->required($key)->isBoolean();
            $_ENV[$key] = $newVal;
        } elseif (empty($val) || 'null' === mb_strtolower((string) $val, 'UTF-8')) {
            $_ENV[$key] = null;
        }
    }
} catch (Exception $e) {
    // https://github.com/phpro/grumphp/blob/master/doc/tasks/phpparser.md#no_exit_statements
    exit($e->getMessage());
}

$configManager = new ConfigManager();
// $configManager->setMergeStrategy('arrayutils');

$configManager->loadConfigurationFiles(_ROOT.'/app/config');

// Wrapper to maintain array syntax compatibility (backward compatibility)
$config = new ConfigArrayWrapper($configManager);

Kint::$enabled_mode = $config->get('debug.enabled');

if ($config->get('debug.enabled')) {
    @Kint::trace();
}

$ContainerBuilder = new ContainerBuilder();

// https://php-di.org/doc/autowiring.html
// Autowiring is enabled by default
$ContainerBuilder->useAutowiring(true);

// https://php-di.org/doc/attributes.html
// Attributes are disabled by default
$ContainerBuilder->useAttributes($config->get('builder.useAttributes'));

if ($config->get('builder.cache')) {
    // Uncaught LogicException: You cannot set a definition at runtime on a compiled container.
    // You can either put your definitions in a file, disable compilation
    // or ->set() a raw value directly (PHP object, string, int, ...) instead of a PHP-DI definition.
    $ContainerBuilder->enableCompilation(dirname((string) $config->get('builder.cache.file')));
} elseif (file_exists($config->get('builder.cache.file'))) {
    @\Safe\unlink($config->get('builder.cache.file'));
}

// https://github.com/PHP-DI/PHP-DI/issues/674
$ContainerBuilder->addDefinitions((require _ROOT.'/app/container.php')($config));

$container = $ContainerBuilder->build();

$app = $container->get(App::class);

if ($config->get('route.cache')) {
    $routeCollector = $app->getRouteCollector();
    $routeCollector->setCacheFile($config->get('route.cache.file'));
} elseif (file_exists($config->get('route.cache.file'))) {
    @\Safe\unlink($config->get('route.cache.file'));
}

(require _ROOT.'/app/helper.php')($app);
(require _ROOT.'/app/middleware.php')($app);
(require _ROOT.'/app/route.php')($app);

return $app;
