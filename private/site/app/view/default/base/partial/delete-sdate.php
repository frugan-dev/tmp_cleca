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

echo '<dt'.$this->escapeAttr([
    'class' => array_merge(['dt-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? []),
]).'>'.PHP_EOL;

echo $val[$this->env]['label']; // <-- no escape, it can contain html tags

echo '</dt>'.PHP_EOL;

echo '<dd'.$this->escapeAttr([
    'class' => array_merge(['dd-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? []),
]).'>'.PHP_EOL;

// https://stackoverflow.com/a/21608708/3929620
if ('' !== trim((string) $this->Mod->{$key})) {
    $obj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $this->Mod->{$key}, $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

    echo $obj->toDateTimeString().' <small class="text-muted">('.$obj->timezone.')</small>';
} else {
    echo '&nbsp;';
}

echo '</dd>'.PHP_EOL;
