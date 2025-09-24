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

$ModMember = $this->container->get('Mod\Member\\'.ucfirst((string) $this->env));

if (!empty($this->memberRow)) {
    echo '<hr>'.PHP_EOL;

    foreach ($this->memberRow as $key => $val) {
        echo '<dl class="row mb-0">'.PHP_EOL;
        echo '<dt'.$this->escapeAttr([
            'class' => array_merge(['dt-'.$this->helper->Nette()->Strings()->webalize($ModMember->modName.'-'.$key)], $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? []),
        ]).'>'.PHP_EOL;

        if (!empty($ModMember->fields[$key][$this->env]['label'])) {
            echo $this->escape()->html($ModMember->fields[$key][$this->env]['label']);
        }

        echo '</dt>'.PHP_EOL;

        echo '<dd'.$this->escapeAttr([
            'class' => array_merge(['dd-'.$this->helper->Nette()->Strings()->webalize($ModMember->modName.'-'.$key)], $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? []),
        ]).'>'.PHP_EOL;

        // DEPRECATED: XMP tag is deprecated, use PRE tag instead with escaped special characters
        // https://stackoverflow.com/a/4549
        echo '<pre class="position-relative overflow-hidden mb-0">';

        // https://defuse.ca/force-print-background.htm
        echo '<img class="position-absolute w-100 h-100"'.$this->escapeAttr([
            'src' => $this->asset('asset/'.$this->env.'/img/bg/e8ecef-1x1.png'),
            'alt' => '',
        ]).'>';
        echo '<div class="position-absolute top-0 start-0 p-1">';

        echo $this->escape()->html($val);

        echo '</div>'.PHP_EOL;
        echo '&nbsp;';
        echo '</pre>'.PHP_EOL;

        echo '</dd>'.PHP_EOL;
        echo '</dl>'.PHP_EOL;
    }

    echo '<hr>'.PHP_EOL;
}

echo $this->Mod->richtext;
