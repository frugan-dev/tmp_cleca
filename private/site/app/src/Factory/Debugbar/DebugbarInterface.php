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

namespace App\Factory\Debugbar;

use DebugBar\StandardDebugBar;

interface DebugbarInterface
{
    /**
     * Get the underlying instance.
     */
    public function getInstance(): ?StandardDebugBar;

    /**
     * Create and configure the instance.
     */
    public function create(): self;

    /**
     * Check if debugbar is enabled and available.
     */
    public function isEnabled(): bool;

    /**
     * Add a message to the messages collector.
     */
    public function addMessage(string $message, string $level = 'info'): self;

    /**
     * Get a specific collector by name.
     */
    public function getCollector(string $name): mixed;
}
