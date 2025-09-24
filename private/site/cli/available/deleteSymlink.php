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

use App\Helper\HelperInterface;

$nowObj = $container->get(HelperInterface::class)->Carbon()->now();
$nowObj->subDay();

// in() searches only the current directory, while from() searches its subdirectories too (recursively)
foreach ($container->get(HelperInterface::class)->Nette()->Finder()->findDirectories('*')->in(_PUBLIC.'/symlink')->filter(function ($dirObj) use ($container, $nowObj) {
    // https://stackoverflow.com/a/34512584
    $mtimeObj = $container->get(HelperInterface::class)->Carbon()->createFromTimestamp($dirObj->getMTime());

    return $mtimeObj->lessThanOrEqualTo($nowObj);
}) as $dirObj) {
    se(sprintf('deleted symlink %1$s', $dirObj->getRealPath()));
    $container->get(HelperInterface::class)->Nette()->FileSystem()->delete($dirObj->getRealPath());
}

if ((is_countable($container->get('errors')) ? count($container->get('errors')) : 0) > 0) {
    se(implode(PHP_EOL, $container->get('errors')));

    return -1;
}

return 0;
