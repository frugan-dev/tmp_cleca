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

use Slim\Factory\ServerRequestCreatorFactory;

if (!defined('_ROOT')) {
    \Safe\define('_ROOT', dirname(__DIR__));
}
if (!defined('_BOOT')) {
    \Safe\define('_BOOT', __DIR__);
}
if (!defined('_PUBLIC')) {
    \Safe\define('_PUBLIC', dirname(__DIR__, 3).'/public');
}

$_SERVER['APP_ENV'] = 'test';

// Define test-friendly getContainerInstance BEFORE loading the app
if (!function_exists('getContainerInstance')) {
    function getContainerInstance()
    {
        global $container;

        return $container;
    }
}

$app = require _ROOT.'/bootstrap.php';

// Initialize environment like CLI does - this triggers middleware including TranslatorFactory
$container = $app->getContainer();

// Create a fake request to trigger middleware initialization
$serverRequestCreator = ServerRequestCreatorFactory::create();
$fakeRequest = $serverRequestCreator->createServerRequestFromGlobals()
    ->withMethod('GET')
    ->withRequestTarget('/test-dummy')
    ->withHeader('CONTENT_TYPE', 'application/x-www-form-urlencoded')
;

// Process through middleware without emitting output (like CLI does)
try {
    // Use handle() instead of run() to process middleware without automatic output emission
    $response = $app->handle($fakeRequest);
    // Response is returned but not emitted - middleware initialization is complete
} catch (Exception) {
    // If routing fails (expected for dummy route), middleware still ran
    // which is what we want for test initialization
}

// Set the request in container (like CLI does)
$container->set('request', $fakeRequest);
