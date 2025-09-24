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

se($container->get(DbInterface::class)->getDatabaseServerVersion());

se($container->get(DbInterface::class)->getPDO()->getAttribute(PDO::ATTR_SERVER_VERSION));

se($container->get(DbInterface::class)->getPDO()->getAttribute(PDO::ATTR_CLIENT_VERSION));

se(mysqli_get_client_info());

if ((is_countable($container->get('errors')) ? count($container->get('errors')) : 0) > 0) {
    se(implode(PHP_EOL, $container->get('errors')));

    return -1;
}

return 0;
