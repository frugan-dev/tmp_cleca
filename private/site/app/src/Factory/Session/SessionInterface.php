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

namespace App\Factory\Session;

use Symfony\Component\HttpFoundation\Session\Session;

interface SessionInterface
{
    /**
     * Get the underlying instance.
     */
    public function getInstance(): ?Session;

    /**
     * Create and configure the instance.
     */
    public function create(): self;

    public function hasFlash(string $type, string $uniqueKey): bool;

    public function deleteFlash(string $type, string $uniqueKey): void;

    public function addFlash(array $params = []): void;

    public function getFlash(string $type): bool|string|null;
}
