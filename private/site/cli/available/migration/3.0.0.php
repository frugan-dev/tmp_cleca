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

use App\Factory\Db\DbInterface;
use Monolog\Level;

return;
$container->get(DbInterface::class)->exec('ALTER TABLE '.$container->get('config')['db.1.prefix'].'catuser ADD `api_log_level` INT(11) UNSIGNED NULL AFTER `api_rl_day`');
$container->get(DbInterface::class)->exec(
    'UPDATE '.$container->get('config')['db.1.prefix'].'catuser SET api_log_level = :api_log_level',
    [
        'api_log_level' => Level::Info->value,
    ]
);
