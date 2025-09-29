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

use App\Factory\Mailer\Provider\OAuth2\Microsoft\Office365OAuthTokenProvider;

/*
 * This file is part of the Slim 4 PHP application.
 *
 * (ɔ) Frugan <dev@frugan.it>
 *
 * This source file is subject to the GNU GPLv3 license that is bundled
 * with this source code in the file COPYING.
 */

return [
    'to' => [
        'programmazione@websync.it' => 'WebSync',
        'lucia.manservisi@unibo.it' => 'Dott.ssa Lucia Manservisi',
    ],

    'user.notify.to' => [
        'devnull@localhost.localdomain' => 'devnull',
    ],

    // https://github.com/symfony/symfony/issues/41322
    // https://stackoverflow.com/a/14253556/3929620
    // https://stackoverflow.com/a/25873119/3929620
    // You must not set more than one sender address on a message,
    // because it's not possible for more than one person to send a single message.
    'sender' => [
        'lilec.erasmusmunduscle@unibo.it' => 'ERASMUS MUNDUS Master Course in European Literary Cultures - CLE', // ALMA MASTER STUDIORUM - Università di Bologna
    ],

    /*'member.replyTo' => [
        'lucia.manservisi@unibo.it' => 'Dott.ssa Lucia Manservisi',
    ],*/

    // https://postmaster.free.fr/index_en.html
    'free.fr.bcc' => [
        'nospam@nospam.proxad.net' => 'Free.fr',
        'dev@frugan.it' => 'Frugan',
    ],

    // overwritten by the Sender
    // no array
    'returnPath' => 'webmaster@frugan.it',

    // https://symfony.com/doc/current/mailer.html
    'transports' => [
        'oauth2',

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

    // If a transport fails, the round-robin transport will retry the delivery using the next available transport,
    // similar to failover, until a transport succeeds or all fail.
    'transports.technique' => 'failover', // failover, roundrobin

    'smtp' => [
        'host' => 'smtp.mailgun.org',
        'port' => 587, // 25, 465, 587
        'username' => $_ENV['SMTP_USERNAME'],
        'password' => $_ENV['SMTP_PASSWORD'],
        'options' => [
            'verify_peer' => null,
            'local_domain' => null,
            'restart_threshold' => null,
            'restart_threshold_sleep' => null,
            'ping_threshold' => null,
        ],
    ],

    'oauth2' => [
        'providers' => [
            'microsoft-office365' => [
                'class' => Office365OAuthTokenProvider::class,
                'config' => [
                    'tenant' => $_ENV['OAUTH2_MICROSOFT_OFFICE365_TENANT'],
                    'client_id' => $_ENV['OAUTH2_MICROSOFT_OFFICE365_CLIENT_ID'],
                    'client_secret' => $_ENV['OAUTH2_MICROSOFT_OFFICE365_CLIENT_SECRET'],
                    'scope' => 'https://outlook.office365.com/.default',

                    'smtp' => [
                        'host' => 'smtp.office365.com',
                        'port' => 587,
                        'username' => $_ENV['OAUTH2_MICROSOFT_OFFICE365_USERNAME'],
                        'options' => [
                            //'verify_peer' => true,
                        ],
                    ],
                ],
            ],
        ],

        // Force OAuth2-only authentication by removing all non-XOAUTH2 authenticators.
        //
        // PRODUCTION USE CASE:
        // When false (default), Symfony's EsmtpTransport tries ALL available authenticators
        // in sequence: CRAM-MD5 -> LOGIN -> PLAIN -> XOAUTH2. Each failed attempt against
        // production SMTP servers (e.g., Microsoft Office365) that don't support those methods
        // causes a ~3 seconds timeout, resulting in ~10 seconds of latency before XOAUTH2
        // is attempted. Set to true in production when using oauth2-smtp transport to make
        // authentication immediate.
        //
        // DEVELOPMENT USE CASE:
        // Required to enforce OAuth2 with mock servers that don't support it. This ensures
        // OAuth2 implementation works correctly without silently falling back to other methods
        // (e.g., Mailpit supports PLAIN/LOGIN but not OAuth2, causing authentication to succeed
        // with wrong method).
        'force_only' => true,
    ],

    // https://symfony.com/doc/current/mailer.html
    // Supported modes are -bs and -t, with any additional flags desired.
    // The recommended mode is "-bs" since it is interactive and failure notifications are hence possible.
    // Note that the -t mode does not support error reporting and does not support Bcc properly (the Bcc headers are not removed).
    // If using -t mode, you are strongly advised to include -oi or -i in the flags (like /usr/sbin/sendmail -oi -t)
    // -f<sender> flag will be appended automatically if one is not present.
    // 'sendmail.command' => '/usr/sbin/sendmail -bs',

    'embeddedMode' => 'cid', // base64, cid, false

    'obfuscate.type' => null, // rot13, hex, null

    'noDnsCheck' => [
        '@localhost.localdomain',
        '@cle.unibo.it',
        '@cleca.unibo.it',
    ],

    // https://help.yahoo.com/kb/SLN24050.html
    // https://www.emailonacid.com/blog/article/industry-news/could_yahoo_and_aols_dmarc_policies_destroy_your_deliverability/
    // AOL and Yahoo! changed their DMARC policy to p=reject if the “from address” domain and “sender domain” do not match
    'dmarcRejectSender' => [
        '@yahoo.com',
        '@rocketmail.com',
        '@ymail.com',
        '@y7mail.com',
        '@yahoo.at',
        '@yahoo.be',
        '@yahoo.bg',
        '@yahoo.cl',
        '@yahoo.co.hu',
        '@yahoo.co.id',
        '@yahoo.co.il',
        '@yahoo.co.kr',
        '@yahoo.co.th',
        '@yahoo.co.za',
        '@yahoo.com.co',
        '@yahoo.com.hr',
        '@yahoo.com.my',
        '@yahoo.com.pe',
        '@yahoo.com.ph',
        '@yahoo.com.sg',
        '@yahoo.com.tr',
        '@yahoo.com.tw',
        '@yahoo.com.ua',
        '@yahoo.com.ve',
        '@yahoo.com.vn',
        '@yahoo.cz',
        '@yahoo.dk',
        '@yahoo.ee',
        '@yahoo.fi',
        '@yahoo.hr',
        '@yahoo.hu',
        '@yahoo.ie',
        '@yahoo.lt',
        '@yahoo.lv',
        '@yahoo.nl',
        '@yahoo.no',
        '@yahoo.pl',
        '@yahoo.pt',
        '@yahoo.rs',
        '@yahoo.se',
        '@yahoo.si',
        '@yahoo.sk',
        '@yahoogroups.co.kr',
        '@yahoogroups.com.cn',
        '@yahoogroups.com.sg',
        '@yahoogroups.com.tw',
        '@yahoogrupper.dk',
        '@yahoogruppi.it',
        '@yahooxtra.co.nz',
        '@yahoo.ca',
        '@yahoo.co.in',
        '@yahoo.co.nz',
        '@yahoo.co.uk',
        '@yahoo.com.ar',
        '@yahoo.com.au',
        '@yahoo.com.br',
        '@yahoo.com.hk',
        '@yahoo.com.mx',
        '@yahoo.de',
        '@yahoo.es',
        '@yahoo.fr',
        '@yahoo.gr',
        '@yahoo.in',
        '@yahoo.it',
        '@yahoo.ro',
    ],
];
