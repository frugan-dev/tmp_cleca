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

use App\Factory\Mailer\Provider\OAuth2\Mock\FakeOAuthTokenProvider;
use App\Factory\Mailer\Provider\OAuth2\Mock\MockOAuthTokenProvider;

return [
    // https://symfony.com/doc/current/mailer.html
    'transports' => [
        'types' => [
            'oauth2-smtp',

            // 'oauth2-graph',

            // 'file',

            // if 'command' isn't specified, it will fallback to '/usr/sbin/sendmail -bs' (no ini_get() detection)
            // 'sendmail',

            // it uses sendmail or smtp transports with ini_get() detection
            // 'native',

            // it requires proc_*() functions
            // 'smtp',

            // only if proc_*() functions are not available...
            // 'mail',
            // 'mail+api',
        ],
    ],

    // https://github.com/axllent/mailpit
    'smtp.host' => 'mailpit',
    'smtp.port' => 1025, // 25, 465, 587, 1025

    'oauth2' => [
        'providers' => [
            'mock' => [
                'class' => MockOAuthTokenProvider::class,
                'config' => [
                    'server_url' => 'http://mock-oauth2:8080',

                    'smtp' => [
                        'host' => 'smtp-server',
                        'port' => 587,
                        // IMPORTANT: username must be present for OAuth2
                        'username' => $_ENV['SMTP_USERNAME'] ?: 'mock-user',
                        'options' => [
                            'verify_peer' => false,
                        ],
                    ],
                ],
            ],
            'fake' => [
                'class' => FakeOAuthTokenProvider::class,
                'config' => [
                    // Simulates OAuth2 token generation failure (not SMTP connection failure).
                    // When true, the provider will fail during OAuth2 token acquisition,
                    // before any SMTP connection is attempted.
                    'simulate_fetch_token_failure' => false,

                    // Simulates health check failure (not SMTP connection failure).
                    // When true, the provider will fail during health check,
                    // before any SMTP connection is attempted.
                    'simulate_health_check_failure' => false,

                    'smtp' => [
                        'host' => 'smtp-server',
                        'port' => 587,
                        // IMPORTANT: username must be present for OAuth2
                        'username' => $_ENV['SMTP_USERNAME'] ?: 'mock-user',
                        'options' => [
                            'verify_peer' => false,
                        ],
                    ],
                ],
            ],
        ],

        'force_only' => true,

        'skip_health_check' => false,
    ],

    // https://github.com/mailhog/MailHog/issues/27
    'embeddedMode' => 'base64',
];
