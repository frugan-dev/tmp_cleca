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

echo '<div'.$this->escapeAttr(['class' => ['row', 'row-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']), 'row-'.$this->helper->Nette()->Strings()->webalize($row['type']), 'mb-3']]).'>'.PHP_EOL;
echo '<label'.$this->escapeAttr([
    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
    'for' => $this->controller.'field-'.$row['id'],
]).'>'.PHP_EOL;

if (!empty($row['name'])) {
    echo $this->escape()->html($row['name']);
}

echo !empty($row['required']) ? ' *' : '';

echo '</label>'.PHP_EOL;
echo '<div'.$this->escapeAttr([
    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
]).'>'.PHP_EOL;

$params = [
    'type' => 'input',
    'attr' => [
        'name' => $this->controller.'field_'.$row['id'],
        // is supported only in Safari 8 http://www.w3schools.com/html/tryit.asp?filename=tryhtml_input_tel
        'type' => 'tel',
        'id' => $this->controller.'field-'.$row['id'],
        'maxlength' => 128,
        'class' => ['form-control'],
        'pattern' => '[0-9\/\\\\\+\-\.\s\(\)]{1,128}',
        'required' => (bool) $row['required'],
    ],
];

if (isset($this->Mod->postData[$this->controller.'field_'.$row['id']])) {
    $params['value'] = $this->Mod->postData[$this->controller.'field_'.$row['id']];
    $params['attr']['value'] = $this->Mod->postData[$this->controller.'field_'.$row['id']];
} else {
    $params['value'] = $row[$this->controller.'value_data'] ?? null;
    $params['attr']['value'] = $row[$this->controller.'value_data'] ?? null;
}

if (!empty($row['richtext'])) {
    $params['attr']['aria-labelledby'] = 'help-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']);
}

echo $this->helper->Html()->getFormField($params);

echo '<div class="invalid-feedback"></div>'.PHP_EOL;

if (!empty($row['richtext'])) {
    echo '<div'.$this->escapeAttr([
        'id' => 'help-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']),
        'class' => ['form-text'],
    ]).'>'.$row['richtext'].'</div>'.PHP_EOL;
}

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
