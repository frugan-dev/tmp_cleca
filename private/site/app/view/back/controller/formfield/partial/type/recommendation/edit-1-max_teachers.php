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
    'label' => __('Max. teachers'),
    'type' => 'input',
    'attr' => [
        'type' => 'number',
        'id' => $key.'-max-teachers',
        'name' => $key.'[max_teachers]',
        'class' => ['form-control'],
        'min' => 1,
        'step' => 1,
        'value' => 1,
        'required' => true,
    ],
];

echo '<div'.$this->escapeAttr(['class' => ['row', 'row-'.$this->helper->Nette()->Strings()->webalize($params['attr']['id']), 'mb-3']]).'>'.PHP_EOL;
echo '<label'.$this->escapeAttr([
    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
    'for' => $params['attr']['id'],
]).'>'.PHP_EOL;

echo $params['label']; // <-- no escape, it can contain html tags

echo !empty($params['attr']['required']) ? ' *' : '';

echo '</label>'.PHP_EOL;
echo '<div'.$this->escapeAttr([
    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
]).'>'.PHP_EOL;

if (isset($this->Mod->postData[$key]['max_teachers'])) {
    $params['value'] = $this->Mod->postData[$key]['max_teachers'];
    $params['attr']['value'] = $this->Mod->postData[$key]['max_teachers'];
} else {
    $params['value'] = $this->Mod->{$key}['max_teachers'] ?? '';
    $params['attr']['value'] = $this->Mod->{$key}['max_teachers'] ?? '';
}

if (!empty($params['help'])) {
    $params['attr']['aria-labelledby'] = 'help-'.$this->helper->Nette()->Strings()->webalize($params['attr']['id']);
}

echo $this->helper->Html()->getFormField($params);

echo '<div class="invalid-feedback"></div>'.PHP_EOL;

if (!empty($params['help'])) {
    echo '<div'.$this->escapeAttr([
        'id' => 'help-'.$this->helper->Nette()->Strings()->webalize($params['attr']['id']),
        'class' => ['form-text'],
    ]).'>'.nl2br(is_array($params['help']) ? implode(PHP_EOL, $params['help']) : $params['help']).'</div>'.PHP_EOL;
}

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
