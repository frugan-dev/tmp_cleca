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

namespace App\Factory\Tree;

use Shudrum\Component\ArrayFinder\ArrayFinder;

interface TreeInterface
{
    /**
     * Get the underlying instance.
     */
    public function getInstance(): ?ArrayFinder;

    /**
     * Create and configure the instance.
     */
    public function create(array $array, ?array $types = []): self;

    public function render($params = []): string;
}
