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

$params = [
    'label' => _n('Value', 'Values', 1),
];

echo '<dt'.$this->escapeAttr([
    'class' => array_merge(['dt-multilang-'.$this->helper->Nette()->Strings()->webalize($key), 'dt-multilang-'.$langId.'-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? []),
]).'>'.PHP_EOL;

echo $params['label']; // <-- no escape, it can contain html tags

echo '</dt>'.PHP_EOL;

echo '<dd'.$this->escapeAttr([
    'class' => array_merge(['dd-multilang-'.$this->helper->Nette()->Strings()->webalize($key), 'dd-multilang-'.$langId.'-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? []),
]).'>'.PHP_EOL;

echo '' !== trim((string) $this->Mod->multilang[$langId][$key]['value']) ? $this->helper->Nette()->Strings()->truncate($this->escapeHtml(strip_tags((string) $this->Mod->multilang[$langId][$key]['value'])), 80) : '&nbsp;';

echo '</dd>'.PHP_EOL;
