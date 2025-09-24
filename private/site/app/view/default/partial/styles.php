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

$items = [
    80 => 'asset/'.$this->env.'/css/'.($this->config['debug.enabled'] ? '' : 'min/').'node/',
    90 => 'asset/'.$this->env.'/css/'.($this->config['debug.enabled'] ? '' : 'min/'),
];

foreach ($items as $weightBase => $path) {
    if (is_dir(_PUBLIC.'/'.$path)) {
        // in() searches only the current directory, while from() searches its subdirectories too (recursively)
        foreach ($this->helper->Nette()->Finder()->findFiles('*.css')->in(_PUBLIC.'/'.$path)->sortByName() as $fileObj) {
            $weight = $weightBase;
            if (!empty($this->config['asset.'.$this->env.'.css.weight'] ?? $this->config['asset.css.weight'] ?? false)) {
                $keys = array_keys($this->config['asset.'.$this->env.'.css.weight'] ?? $this->config['asset.css.weight']);

                if (\Safe\preg_match('~'.implode('|', array_map('preg_quote', $keys, array_fill(0, is_countable($keys) ? count($keys) : 0, '~'))).'~i', $fileObj->getBasename('.css'), $matches)) {
                    $weight = $this->config['asset.'.$this->env.'.css.weight'][$matches[0]] ?? $this->config['asset.css.weight'][$matches[0]];
                }
            }

            $attr = null;
            if (!empty($this->config['asset.'.$this->env.'.css.attr'] ?? $this->config['asset.css.attr'] ?? false)) {
                $keys = array_keys($this->config['asset.'.$this->env.'.css.attr'] ?? $this->config['asset.css.attr']);

                if (\Safe\preg_match('~'.implode('|', array_map('preg_quote', $keys, array_fill(0, is_countable($keys) ? count($keys) : 0, '~'))).'~i', $fileObj->getBasename('.css'), $matches)) {
                    $attr = $this->config['asset.'.$this->env.'.css.attr'][$matches[0]] ?? $this->config['asset.css.attr'][$matches[0]];
                }
            }

            $this->styles()->add(
                $this->asset($path.$fileObj->getFilename()),
                $attr,
                $weight // default: 100
            );
        }
    }
}

echo $this->styles();
