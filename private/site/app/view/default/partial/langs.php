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

if ((is_countable($this->routeArgsArr) ? count($this->routeArgsArr) : 0) > 1) {
    echo '<div class="langs">'.PHP_EOL;
    echo '<ul class="list-inline mb-0">'.PHP_EOL;

    foreach ($this->lang->arr as $langId => $langRow) {
        echo '<li class="list-inline-item">'.PHP_EOL;
        echo '<a data-bs-toggle="tooltip"'.$this->escapeAttr([
            'href' => $this->uri($this->routeArgsArr[$langId]),
            'title' => $langRow['name'],
            'class' => array_merge(['d-inline-block'], $langId === $this->lang->id ? ['active'] : []),
            'data-bs-placement' => $tooltipPlacement ?? false,
        ]).'>'.PHP_EOL;
        echo '<span'.$this->escapeAttr([
            'class' => ['border', 'border-secondary', 'fi', 'fi-'.$langRow['isoCode']],
        ]).'></span>'.PHP_EOL;
        echo '</a>'.PHP_EOL;
        echo '</li>'.PHP_EOL;
    }

    echo '</ul>'.PHP_EOL;
    echo '</div>'.PHP_EOL;
}
