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

if (is_dir(_ROOT.'/cli/available/migration') && is_dir(_ROOT.'/cli/enabled/migration')) {
    \Safe\preg_match('/^v?([\d\.]+)/', (string) version(), $matches);
    $version = $matches[1] ?? '0.0.0';

    // in() searches only the current directory, while from() searches its subdirectories too (recursively)
    foreach ($container->get(HelperInterface::class)->Nette()->Finder()->findFiles('*.php')->in(_ROOT.'/cli/available/migration')->sortByName() as $fileObj) {
        if (is_link(_ROOT.'/cli/enabled/migration/'.$fileObj->getBasename())) {
            continue;
        }

        if (true === version_compare($fileObj->getBasename('.php'), $version, '>')) {
            continue;
        }

        se(sprintf(__('Processing %1$s'), 'migration/'.$fileObj->getBasename()).'...');

        include_once $fileObj->getPathname();

        $container->get(HelperInterface::class)->File()->symlink($fileObj->getPathname(), _ROOT.'/cli/enabled/migration/'.$fileObj->getBasename());
    }
}

if ((is_countable($container->get('errors')) ? count($container->get('errors')) : 0) > 0) {
    se(implode(PHP_EOL, $container->get('errors')));

    return -1;
}

return 0;
