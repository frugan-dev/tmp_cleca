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

use App\Factory\Logger\LoggerInterface;

echo '<dt'.$this->escapeAttr([
    'class' => array_merge(['dt-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? []),
]).'>'.PHP_EOL;

echo $val[$this->env]['label']; // <-- no escape, it can contain html tags

echo '</dt>'.PHP_EOL;

echo '<dd'.$this->escapeAttr([
    'class' => array_merge(['dd-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? []),
]).'>'.PHP_EOL;

echo '<span'.$this->escapeAttr([
    'class' => array_merge(['badge', 'text-uppercase'], $this->helper->Color()->contrast($this->container->get(LoggerInterface::class)->getLevelColor($this->Mod->{$key}), $this->config['theme.color.contrast.yiq.threshold']) ? ['text-white'] : ['text-body']),
    'style' => 'background-color:'.$this->container->get(LoggerInterface::class)->getLevelColor($this->Mod->{$key}),
]).'>'.$this->escape()->html($this->container->get(LoggerInterface::class)::toMonologLevel($this->Mod->{$key})->getName()).'</span>'.PHP_EOL;

echo '</dd>'.PHP_EOL;
