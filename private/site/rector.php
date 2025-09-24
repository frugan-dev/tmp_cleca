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

use Rector\CodeQuality\Rector\Class_\InlineConstructorDefaultToPropertyRector;
use Rector\Config\RectorConfig;
use Rector\Renaming\Rector\FuncCall\RenameFunctionRector;
use Rector\Set\ValueObject\LevelSetList;

// https://getrector.com/blog/5-common-mistakes-in-rector-config-and-how-to-avoid-them
return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__,
        dirname(__DIR__, 2).'/public',
    ]);

    // https://getrector.com/documentation/ignoring-rules-or-paths
    $rectorConfig->skip([
        __DIR__.'/cli/enabled',
        __DIR__.'/patch',
        __DIR__.'/var',
        __DIR__.'/vendor',

        RenameFunctionRector::class => [
            __DIR__.'/cli/*.php',
            __DIR__.'/.php-cs-fixer.dist.php',
            __DIR__.'/bootstrap.php',
            dirname(__DIR__, 2).'/public/index.php',
        ],
    ]);

    // https://github.com/rectorphp/rector/blob/main/docs/rector_rules_overview.md
    // https://github.com/rectorphp/rector/tree/main/packages/Set/ValueObject
    // register a single rule
    // $rectorConfig->rule(InlineConstructorDefaultToPropertyRector::class);

    // define sets of rules
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
    ]);

    if (file_exists(__DIR__.'/vendor/thecodingmachine/safe/rector-migrate.php')) {
        (require __DIR__.'/vendor/thecodingmachine/safe/rector-migrate.php')($rectorConfig);
    }
};
