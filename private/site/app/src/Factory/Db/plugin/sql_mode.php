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

use RedBeanPHP\Facade as R;

// https://dba.stackexchange.com/a/322675/274546
// https://dba.stackexchange.com/a/322675
// https://stackoverflow.com/a/45113578/3929620
// https://stackoverflow.com/a/23211224/3929620
// https://stackoverflow.com/a/42950082/3929620
// https://dev.mysql.com/doc/refman/8.0/en/sql-mode.html#sqlmode_no_auto_value_on_zero
// https://www.linode.com/community/questions/17070/how-can-i-disable-mysql-strict-mode
// R::exec('SET sql_mode=(SELECT REPLACE(@@sql_mode, "STRICT_TRANS_TABLES,", ""));');
// R::exec('SET sql_mode=(SELECT REPLACE(@@sql_mode, "STRICT_TRANS_TABLES", "ALLOW_INVALID_DATES"));');
