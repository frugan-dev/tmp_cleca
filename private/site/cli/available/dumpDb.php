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

if (!empty($container->get(DbInterface::class)->dbKeys)) {
    $firstDbKey = current($container->get(DbInterface::class)->dbKeys);
    foreach ($container->get(DbInterface::class)->dbKeys as $dbKey) {
        if ($container->get(DbInterface::class)->hasDatabase($dbKey)) {
            switch ($container->get('config')['db.'.$dbKey.'.driver']) {
                case 'mysql':
                case 'mariadb':
                    $srcFile = \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/tmp/dump-'.$container->get(HelperInterface::class)->Carbon()->now()->format('d').'.sql');
                    $destFile = $srcFile.'.zip';

                    $container->get(DbInterface::class)->mysqldump($srcFile);

                    $container->get(HelperInterface::class)->File()->archive([$srcFile], $destFile);
                    $container->get(HelperInterface::class)->Nette()->FileSystem()->delete($srcFile);

                    break;
            }
        }
    }
}

if ((is_countable($container->get('errors')) ? count($container->get('errors')) : 0) > 0) {
    se(implode(PHP_EOL, $container->get('errors')));

    return -1;
}

return 0;
