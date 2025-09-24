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

echo '<dl class="row mb-0">'.PHP_EOL;
echo '<dt'.$this->escapeAttr([
    'class' => array_merge(['dt-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']), 'dt-'.$this->helper->Nette()->Strings()->webalize($row['type'])], $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? []),
]).'>'.PHP_EOL;

if (!empty($row['name'])) {
    echo $this->escape()->html($row['name']);
}

echo '</dt>'.PHP_EOL;

echo '<dd'.$this->escapeAttr([
    'class' => array_merge(['dd-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']), 'dd-'.$this->helper->Nette()->Strings()->webalize($row['type'])], $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? []),
]).'>'.PHP_EOL;

// DEPRECATED: XMP tag is deprecated, use PRE tag instead with escaped special characters
// https://stackoverflow.com/a/4549
echo '<pre class="position-relative overflow-hidden text-wrap mb-0">';

// https://defuse.ca/force-print-background.htm
echo '<img class="position-absolute w-100 h-100 z-n1"'.$this->escapeAttr([
    'src' => $this->asset('asset/'.$this->env.'/img/bg/e8ecef-1x1.png'),
    'alt' => '',
]).'>';
echo '<div class="p-1">';

$labels = [];
$options = [
    1 => __('Yes'),
    0 => __('No'),
];

if (!empty($options)) {
    foreach ($options as $k => $v) {
        $value = $k;
        $label = $v;

        if ($value === (isset($row[$this->controller.'value_data']) ? (is_array($row[$this->controller.'value_data']) ? $row[$this->controller.'value_data'][0] : $row[$this->controller.'value_data']) : null)) {
            $labels[] = $label;

            break;
        }
    }
}

if (!empty($labels)) {
    echo '<ul class="list-unstyled mb-0">'; // <-- no PHP_EOL

    foreach ($labels as $label) {
        echo '<li>'.$this->escape()->html($label).'</li>'; // <-- no PHP_EOL
    }

    echo '</ul>'; // <-- no PHP_EOL

    if (!empty($row[$this->controller.'value_data']['teachers'] ?? null)) {
        echo '<hr>'; // <-- no PHP_EOL

        echo '<ul class="list-unstyled mb-0">'; // <-- no PHP_EOL

        foreach ($row[$this->controller.'value_data']['teachers'] as $k => $v) {
            echo '<li>#'.$v['id'].' - '.$v['firstname'].' '.$v['lastname'].' ('.$v['email'].')</li>'; // <-- no PHP_EOL
        }

        echo '</ul>'; // <-- no PHP_EOL
    }
} else {
    echo '&nbsp;';
}

echo '</div>'.PHP_EOL;
echo '</pre>'.PHP_EOL;

echo '</dd>'.PHP_EOL;
echo '</dl>'.PHP_EOL;
