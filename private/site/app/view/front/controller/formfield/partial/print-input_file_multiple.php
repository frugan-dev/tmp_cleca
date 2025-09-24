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

if (!empty($row[$this->controller.'value_data'])) {
    echo '<ol class="mb-0">'; // <-- no PHP_EOL

    foreach ($row[$this->controller.'value_data'] as $crc32 => $item) {
        echo '<li>'; // <-- no PHP_EOL
        echo $this->escape()->html($item['name']).' <i class="small">('.$this->helper->File()->formatSize($item['size']).')</i>'; // <-- no PHP_EOL
        echo '</li>'; // <-- no PHP_EOL
    }

    echo '</ol>'; // <-- no PHP_EOL
} else {
    echo '&nbsp;';
}

echo '</div>'.PHP_EOL;
echo '</pre>'.PHP_EOL;

echo '</dd>'.PHP_EOL;
echo '</dl>'.PHP_EOL;
