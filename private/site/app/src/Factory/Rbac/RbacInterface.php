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

namespace App\Factory\Rbac;

use Laminas\Permissions\Rbac\Rbac;

interface RbacInterface
{
    /**
     * Get the underlying instance.
     */
    public function getInstance(): ?Rbac;

    /**
     * Create and configure the instance.
     */
    public function create(): self;

    public function isGranted(string $permission, $assertion = null): bool;
}
