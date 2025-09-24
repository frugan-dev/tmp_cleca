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
?>
<nav<?php echo $this->escapeAttr([
    'aria-label' => __('Pagination'),
]); ?>>
    <ul class="pagination justify-content-center <?php /* pagination-lg */ ?>">
            <?php
            /*
            [
            1 => 'first',
            5 => 'less',
            6 => 'previous', // This interval
            7 => 'previous', // is defined
            8 => 'previous', // by
            9 => 'previous', // $neighbours argument
            10 => 'current', // Current page
            11 => 'next',
            12 => 'next',
            13 => 'next',
            14 => 'next',
            15 => 'more',
            20 => 'last'
            ]
            */

            $firstKey = array_key_first($this->pagination);
$lastKey = array_key_last($this->pagination);

foreach ($this->pagination as $key => $val) {
    $attr = [
        'class' => ['page-item'],
    ];

    if ('current' === $val) {
        $attr['class'][] = 'active';
    } elseif ('current' === $val && in_array($key, [$firstKey, $lastKey], true)) {
        $attr['class'][] = 'disabled';
    }

    echo '<li'.$this->escapeAttr($attr).'>'.PHP_EOL;

    switch ($val) {
        case 'current':
            if ($key === $firstKey) {
                echo '<span class="page-link">'.$this->escape()->html($key).'</span>';
            } elseif ($key === $lastKey) {
                echo '<span class="page-link">'.$this->escape()->html($key).'</span>';
            } else {
                echo '<span class="page-link">'.$this->escape()->html($key).'</span>';
            }

            break;

        case 'first':
            $routeArgs = $this->routeArgs;
            if (!\Safe\preg_match('~\.params$~', (string) $routeArgs['routeName'])) {
                $routeArgs['routeName'] .= '.params';
            }
            $routeArgs['data']['params'] = !empty($this->routeParamsArrWithoutPg) ? implode('/', $this->routeParamsArrWithoutPg).'/'.$key : $key;
            echo '<a class="page-link"'.$this->escapeAttr([
                'href' => $this->uri($routeArgs),
            ]).'>&laquo;</a>';

            break;

        case 'less':
        case 'previous':
            $routeArgs = $this->routeArgs;
            if (!\Safe\preg_match('~\.params$~', (string) $routeArgs['routeName'])) {
                $routeArgs['routeName'] .= '.params';
            }
            $routeArgs['data']['params'] = !empty($this->routeParamsArrWithoutPg) ? implode('/', $this->routeParamsArrWithoutPg).'/'.$key : $key;
            echo '<a class="page-link" rel="prev"'.$this->escapeAttr([
                'href' => $this->uri($routeArgs),
            ]).'>'.$this->escape()->html($key).'</a>';

            break;

        case 'next':
        case 'more':
            $routeArgs = $this->routeArgs;
            if (!\Safe\preg_match('~\.params$~', (string) $routeArgs['routeName'])) {
                $routeArgs['routeName'] .= '.params';
            }
            $routeArgs['data']['params'] = !empty($this->routeParamsArrWithoutPg) ? implode('/', $this->routeParamsArrWithoutPg).'/'.$key : $key;
            echo '<a class="page-link" rel="next"'.$this->escapeAttr([
                'href' => $this->uri($routeArgs),
            ]).'>'.$this->escape()->html($key).'</a>';

            break;

        case 'last':
            $routeArgs = $this->routeArgs;
            if (!\Safe\preg_match('~\.params$~', (string) $routeArgs['routeName'])) {
                $routeArgs['routeName'] .= '.params';
            }
            $routeArgs['data']['params'] = !empty($this->routeParamsArrWithoutPg) ? implode('/', $this->routeParamsArrWithoutPg).'/'.$key : $key;
            echo '<a class="page-link"'.$this->escapeAttr([
                'href' => $this->uri($routeArgs),
            ]).'>&raquo;</a>';

            break;
    }

    echo '</li>'.PHP_EOL;
}
?>
    </ul>
</nav>
