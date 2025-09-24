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

namespace App\Controller\Mod\Catform;

interface CatformInterface
{
    // https://stackoverflow.com/a/24377563/3929620
    public const UNDEFINED = 10;

    public const MAINTENANCE = 20;

    public const OPENING = 30;

    public const OPEN = 40;

    public const CLOSING = 50;

    public const CLOSED = 60;

    public const STATUSES = [
        self::UNDEFINED,
        self::MAINTENANCE,
        self::OPENING,
        self::OPEN,
        self::CLOSING,
        self::CLOSED,
    ];
}
