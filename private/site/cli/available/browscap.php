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

use App\Factory\Cache\CacheInterface;
use App\Factory\Logger\LoggerInterface;
use BrowscapPHP\BrowscapUpdater;

try {
    // https://github.com/matomo-org/device-detector
    // https://github.com/browscap/browscap-php/issues/319
    $browscapUpdater = new BrowscapUpdater($container->get(CacheInterface::class)->psr16Cache(), $container->get(LoggerInterface::class)->channel('internal'));
    $browscapUpdater->update(
        // PHP_BrowscapINI (20,134 KB)
        // This is a special version of browscap.ini for PHP users only!
        // \BrowscapPHP\Helper\IniLoaderInterface::PHP_INI // default

        // Full_PHP_BrowscapINI (98,287 KB)
        // This is a larger version of php_browscap.ini with all the new properties.
        // \BrowscapPHP\Helper\IniLoaderInterface::PHP_INI_FULL

        // Lite_PHP_BrowscapINI (764 KB)
        // This is a smaller version of php_browscap.ini file containing major browsers & search engines.
        // This file is adequate for most websites.
        // \BrowscapPHP\Helper\IniLoaderInterface::PHP_INI_LITE
    );
} catch (Exception $e) {
    $container->errors[] = $e->getMessage();
    $container->errors[] = sprintf('%1$s: %2$s', '__LINE__', __LINE__);
}

if ((is_countable($container->get('errors')) ? count($container->get('errors')) : 0) > 0) {
    se(implode(PHP_EOL, $container->get('errors')));

    return -1;
}

return 0;
