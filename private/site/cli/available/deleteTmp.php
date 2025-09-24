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
foreach ($container->get(HelperInterface::class)->Nette()->Finder()->find('*')->in(_ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/tmp')->filter(function ($fileObj) use ($container, $nowObj) {
    // https://stackoverflow.com/a/34512584
    $mtimeObj = $container->get(HelperInterface::class)->Carbon()->createFromTimestamp($fileObj->getMTime());

    return $mtimeObj->lessThanOrEqualTo($nowObj);
}) as $fileObj) {
    se(sprintf('deleted tmp %1$s', $fileObj->getRealPath()));
    $container->get(HelperInterface::class)->Nette()->FileSystem()->delete($fileObj->getRealPath());
}

if ((is_countable($container->get('errors')) ? count($container->get('errors')) : 0) > 0) {
    se(implode(PHP_EOL, $container->get('errors')));

    return -1;
}

return 0;
