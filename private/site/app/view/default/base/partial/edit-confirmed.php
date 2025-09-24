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

use Laminas\Stdlib\ArrayUtils;

echo '<div'.$this->escapeAttr(['class' => ['row', 'row-'.$this->helper->Nette()->Strings()->webalize($key), 'mb-3']]).'>'.PHP_EOL;
echo '<label'.$this->escapeAttr([
    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
    'for' => $key,
]).'>'.PHP_EOL;

echo $val[$this->env]['label']; // <-- no escape, it can contain html tags

echo '</label>'.PHP_EOL;
echo '<div'.$this->escapeAttr([
    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
]).'>'.PHP_EOL;

if (!empty($val[$this->env]['skip']) && in_array($this->action, $val[$this->env]['skip'], true)) {
    echo '<div class="form-control-plaintext">'.PHP_EOL;

    echo ($this->Mod->{$key}) ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-times text-danger"></i>';

    echo '</div>'.PHP_EOL;
} else {
    $params = [];

    $params['attr']['name'] = $key;

    if (!empty($this->Mod->postData)) {
        $params['value'] = $this->Mod->postData[$key] ?? 0;
    } else {
        $params['value'] = $this->Mod->{$key};
    }

    echo '<div'.$this->escapeAttr(['class' => array_merge(['form-check'], !empty($this->config['theme.checkbox.switches']) ? ['form-switch'] : [])]).'>'.PHP_EOL;

    $params = ArrayUtils::merge($val[$this->env], $params);

    if (!empty($params['help'])) {
        $params['attr']['aria-labelledby'] = 'help-'.$this->helper->Nette()->Strings()->webalize($key);
    }

    echo $this->helper->Html()->getFormField($params);

    echo '<label class="form-check-label"'.$this->escapeAttr(['for' => $key]).'>&nbsp;</label>'.PHP_EOL;

    echo '</div>'.PHP_EOL;

    echo '<div class="invalid-feedback"></div>'.PHP_EOL;

    if (!empty($params['help'])) {
        echo '<div'.$this->escapeAttr([
            'id' => 'help-'.$this->helper->Nette()->Strings()->webalize($key),
            'class' => ['form-text'],
        ]).'>'.nl2br(is_array($params['help']) ? implode(PHP_EOL, $params['help']) : $params['help']).'</div>'.PHP_EOL;
    }
}

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
