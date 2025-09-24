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
use WhiteHat101\Crypt\APR1_MD5;

$password = readline('Password: ');

$Mod = $container->get('Mod\User\Cli');

$fieldKey = 'password';

$algorithm = $container->get('config')['mod.'.$Mod->modName.'.'.$fieldKey.'.auth.password.hash.algorithm'] ?? $container->get('config')['mod.'.$Mod->modName.'.auth.password.hash.algorithm'] ?? $container->get('config')['auth.password.hash.algorithm'];
$options = array_key_exists('mod.'.$Mod->modName.'.'.$fieldKey.'.auth.password.hash.options', $container->get('config')) ? $container->get('config')['mod.'.$Mod->modName.'.'.$fieldKey.'.auth.password.hash.options'] : (array_key_exists('mod.'.$Mod->modName.'.auth.password.hash.options', $container->get('config')) ? $container->get('config')['mod.'.$Mod->modName.'.auth.password.hash.options'] : $container->get('config')['auth.password.hash.options']); // <--

$hash = match ($algorithm) {
    'APR1_MD5' => APR1_MD5::hash($password, $options),
    default => password_hash($password, $algorithm, $options),
};

$container->get(DbInterface::class)->exec('UPDATE '.$container->get('config')['db.1.prefix'].$Mod->modName.' SET password = :password WHERE username = :username', [
    'password' => $hash,
    'username' => $cliArgs['username'],
]);

se(sprintf('updated %1$s %2$s password', $Mod->modName, $cliArgs['username']));

if ((is_countable($container->get('errors')) ? count($container->get('errors')) : 0) > 0) {
    se(implode(PHP_EOL, $container->get('errors')));

    return -1;
}

return 0;
