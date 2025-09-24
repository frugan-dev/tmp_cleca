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

namespace App\Middleware\Env\Js;

use Psr\Http\Server\MiddlewareInterface;

class ShutdownMiddleware extends \App\Middleware\ShutdownMiddleware implements MiddlewareInterface
{
    protected string $mimeType = 'application/javascript';
}
