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

namespace App\Factory\Pager;

use Kilte\Pagination\Pagination;

interface PagerInterface
{
    /**
     * Get the underlying instance.
     */
    public function getInstance(): ?Pagination;

    /**
     * Create and configure the instance.
     */
    public function create(?int $totRows = null, ?int $rowPerPage = null): self;

    public function prepare(?int $totRows, ?int $rowPerPage): void;
}
