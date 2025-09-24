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
use App\Helper\HelperInterface;

$Mod = $container->get('Mod\User\Cli');

$container->get(DbInterface::class)->exec('UPDATE '.$container->get('config')['db.1.prefix'].$Mod->modName.' SET api_key = :api_key WHERE username = :username', [
    'api_key' => $container->get(HelperInterface::class)->Nette()->Random()->generate($container->get('config')['mod.'.$Mod->modName.'.api.key.length'] ?? $container->get('config')['api.key.length'], $container->get('config')['mod.'.$Mod->modName.'.api.key.charlist'] ?? $container->get('config')['api.key.charlist']),
    'username' => $cliArgs['username'],
]);

se(sprintf('updated %1$s %2$s api_key', $Mod->modName, $cliArgs['username']));

if ((is_countable($container->get('errors')) ? count($container->get('errors')) : 0) > 0) {
    se(implode(PHP_EOL, $container->get('errors')));

    return -1;
}

return 0;
