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
    'debug.enabled' => $_ENV['DB_DEBUG_ENABLED'] ?? false,

    // 0 Log and write to STDOUT classic style (default)
    // 1 Log only, class style
    // 2 Log and write to STDOUT fancy style
    // 3 Log only, fancy style
    'debug.mode' => (int) ($_ENV['DB_DEBUG_MODE'] ?? 0),

    'debug.appendLogs' => $_ENV['DB_APPEND_LOGS'] ?? false,

    // Toggles fluid (false) or frozen (true) mode.
    // In fluid mode the database structure is adjusted to accomodate your objects.
    // In frozen mode this is not the case.
    'frozen.enabled' => $_ENV['DB_FROZEN_ENABLED'] ?? false,

    // You can also pass an array containing a selection of frozen types.
    // Let's call this chilly mode, it's just like fluid mode except that certain types (i.e. tables) aren't touched.
    'frozen.types' => [],

    1 => [
        'driver' => 'mysql', // mysql, mariadb, postgresql, sqlite, cubrid

        'host' => $_ENV['DB_1_HOST'],
        'dbname' => $_ENV['DB_1_NAME'],
        'user' => $_ENV['DB_1_USER'],
        'password' => $_ENV['DB_1_PASS'],

        'port' => (int) ($_ENV['DB_1_PORT'] ?? 3306), // mysql -> 3306, cubrid -> 30000
        'charset' => $_ENV['DB_1_CHARSET'] ?? 'utf8mb4',
        'collation' => $_ENV['DB_1_COLLATION'] ?? 'utf8mb4_unicode_ci',
        'prefix' => $_ENV['DB_1_PREFIX'] ?? '',

        'driverOptions' => [
            // Turn off persistent connections
            PDO::ATTR_PERSISTENT => false,
            // Enable exceptions
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // Emulate prepared statements
            PDO::ATTR_EMULATE_PREPARES => true,
            // Set default fetch mode to array
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Set character set
            // https://mathiasbynens.be/notes/mysql-utf8mb4
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',

            // PDO::MYSQL_ATTR_SSL_KEY => null,
            // PDO::MYSQL_ATTR_SSL_CERT => null,
            // PDO::MYSQL_ATTR_SSL_CA => null,

            // https://stackoverflow.com/a/44067335/3929620
            // PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
        ],

        'timeZone' => $_ENV['DB_1_TIMEZONE'] ?? 'UTC',
    ],
];
