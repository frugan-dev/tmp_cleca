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

return [
    'google.api.browserKey' => $_ENV['GOOGLE_API_BROWSER_KEY'],
    'google.api.serverKey' => $_ENV['GOOGLE_API_SERVER_KEY'],

    'google.recaptcha.publicKey' => $_ENV['GOOGLE_RECAPTCHA_PUBLIC_KEY'],
    'google.recaptcha.privateKey' => $_ENV['GOOGLE_RECAPTCHA_PRIVATE_KEY'],

    // e.g. float number between 0 and 1 (1.0 is very likely a good interaction, 0.0 is very likely a bot)
    // https://recaptcha-demo.appspot.com
    'google.recaptcha.scoreThreshold' => 0.7,

    'google.analytics.code' => $_ENV['GOOGLE_ANALYTICS_CODE'],
    'back.google.analytics.code' => false,

    'shinystat.user' => $_ENV['SHINYSTAT_USER'],

    'unsplash.accessKey' => $_ENV['UNSPLASH_ACCESS_KEY'],
    'unsplash.secretKey' => $_ENV['UNSPLASH_SECRET_KEY'],

    'tinymce.apiKey' => $_ENV['TINYMCE_API_KEY'],

    'sentry.dsn' => $_ENV['APP_SENTRY_DSN'],
    'sentry.release' => null,
    'sentry.environment' => ($_SERVER['APP_ENV'] ?? null),
    'sentry.sampleRate' => null,
    'sentry.maxBreadcrumbs' => null,
    'sentry.attachStacktrace' => null,
    'sentry.maxValueLength' => null,
    'sentry.beforeSend' => null,
    'sentry.beforeBreadcrumb' => null,
    'sentry.transport' => null,
    'sentry.tracesSampleRate' => 0.5,
    'sentry.tracesSampler' => null,

    // https://docs.sentry.io/platforms/php/configuration/options/
    'sentry.errorTypes' => null,
    'sentry.sendDefaultPii' => null,
    'sentry.serverName' => null,
    'sentry.inAppInclude' => null,
    'sentry.inAppExclude' => null,
    'sentry.maxRequestBodySize' => null,
    'sentry.httpProxy' => null,
    'sentry.ignoreExceptions' => null, // >= v4.x

    // https://docs.sentry.io/platforms/javascript/configuration/options/
    'sentry.tunnel' => null,
    'sentry.denyUrls' => null,
    'sentry.allowUrls' => null,
    'sentry.autoSessionTracking' => null,
    'sentry.initialScope' => null,
    'sentry.normalizeDepth' => null,
    'sentry.integrations' => null,
    'sentry.defaultIntegrations' => null,
];
