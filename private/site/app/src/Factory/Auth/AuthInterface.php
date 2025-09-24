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

namespace App\Factory\Auth;

use App\Factory\Auth\Adapter\AuthAdapterInterface;
use Laminas\Authentication\AuthenticationServiceInterface;

interface AuthInterface
{
    /**
     * Get the underlying instance.
     */
    public function getInstance(): ?AuthenticationServiceInterface;

    /**
     * Create and configure the instance.
     */
    public function create(AuthAdapterInterface $adapter, ?array $storages = [], ?array $identityTypes = []): self;
}
