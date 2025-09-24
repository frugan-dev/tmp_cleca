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
    'name' => 'CLE App',

    // http://semver.org
    'version' => 'v3.0.0',

    // https://www.php.net/supported-versions.php
    'php.minVersion' => 8.4,

    // https://dba.stackexchange.com/a/289989/274546
    // https://dba.stackexchange.com/a/165802/274546
    // https://mariadb.com/kb/en/json_table/
    'mariadb.server.minVersion' => 11.0, // 11.2.3-MariaDB

    'mysql.server.minVersion' => null,
    'mysql.client.minVersion' => 'mysqlnd 8.2', // mysqlnd 8.2.12

    'timeZone' => 'UTC',
];
