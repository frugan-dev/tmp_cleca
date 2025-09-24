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

if (!empty($this->formfieldResult)) {
    foreach ($this->formfieldResult as $row) {
        if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'field/partial/'.$this->action.'-'.$row['type'].'.php')) {
            include _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'field/partial/'.$this->action.'-'.$row['type'].'.php';
        } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-'.$row['type'].'.php')) {
            include _ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-'.$row['type'].'.php';
        } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$this->controller.'field/partial/'.$this->action.'-'.$row['type'].'.php')) {
            include _ROOT.'/app/view/default/controller/'.$this->controller.'field/partial/'.$this->action.'-'.$row['type'].'.php';
        } elseif (file_exists(_ROOT.'/app/view/default/base/partial/'.$this->action.'-'.$row['type'].'.php')) {
            include _ROOT.'/app/view/default/base/partial/'.$this->action.'-'.$row['type'].'.php';
        } else {
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

            if (isset($row[$this->controller.'value_data']) && !isBlank($row[$this->controller.'value_data']) && !is_array($row[$this->controller.'value_data'])) {
                echo $this->escape()->html($row[$this->controller.'value_data']);
            } else {
                echo '&nbsp;';
            }

            echo '</div>'.PHP_EOL;
            echo '</pre>'.PHP_EOL;

            echo '</dd>'.PHP_EOL;
            echo '</dl>'.PHP_EOL;
        }
    }
} else {
    echo '<p class="text-danger card-text">'.$this->escape()->html(__('No results found.')).'</p>'.PHP_EOL;
}
