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

$nameRichtext = $this->helper->Nette()->Strings()->truncate(trim(strip_tags((string) ($this->Mod->formfield_name ?? '').' '.($this->Mod->formfield_richtext ?? ''))), 50);

echo $this->Mod->formfield_id.' - '.(!empty($nameRichtext) ? $nameRichtext : '').' (';

if (method_exists($this->container->get('Mod\Formfield\\'.ucfirst((string) $this->env)), 'getFieldTypes') && is_callable([$this->container->get('Mod\Formfield\\'.ucfirst((string) $this->env)), 'getFieldTypes'])) {
    echo $this->container->get('Mod\Formfield\\'.ucfirst((string) $this->env))->getFieldTypes()[$this->Mod->formfield_type] ?? $this->Mod->formfield_type;
} else {
    echo $this->Mod->formfield_type;
}

echo ')';

echo '</dd>'.PHP_EOL;
