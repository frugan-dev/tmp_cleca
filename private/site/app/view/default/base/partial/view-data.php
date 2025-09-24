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

use Nette\Utils\JsonException;

echo '<dt'.$this->escapeAttr([
    'class' => array_merge(['dt-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? []),
]).'>'.PHP_EOL;

echo $val[$this->env]['label']; // <-- no escape, it can contain html tags

echo '</dt>'.PHP_EOL;

echo '<dd'.$this->escapeAttr([
    'class' => array_merge(['dd-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? []),
]).'>'.PHP_EOL;

// DEPRECATED: XMP tag is deprecated, use PRE tag instead with escaped special characters
// https://stackoverflow.com/a/4549
if ('' !== trim((string) $this->Mod->{$key})) {
    try {
        $obj = $this->helper->Nette()->Json()->decode($this->Mod->{$key});
    } catch (JsonException) {
        $obj = $this->Mod->{$key};
    }

    echo '<pre>'.$this->escape()->html(var_export($obj, true)).'</pre>';
} else {
    echo '&nbsp;';
}

echo '</dd>'.PHP_EOL;
