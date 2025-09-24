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
use Symfony\Component\EventDispatcher\GenericEvent;

echo '<div'.$this->escapeAttr(['class' => ['row', 'row-'.$this->helper->Nette()->Strings()->webalize($key), 'mb-3']]).'>'.PHP_EOL;
echo '<label'.$this->escapeAttr([
    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
    'for' => $key,
]).'>'.PHP_EOL;

echo $val[$this->env]['label']; // <-- no escape, it can contain html tags

echo !empty($val[$this->env]['attr']['required']) ? ' *' : '';

echo '</label>'.PHP_EOL;
echo '<div'.$this->escapeAttr([
    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
]).'>'.PHP_EOL;

echo '<div class="input-group">'.PHP_EOL;

$params = [];

$params['attr']['name'] = $key.'_country_id';

if (isset($this->Mod->postData[$key.'_country_id'])) {
    $params['value'] = $this->Mod->postData[$key.'_country_id'];
} else {
    $params['value'] = $this->config['mod.country.'.$this->controller.'.defaultId'] ?? $this->config['mod.country.defaultId'] ?? null;
}

$params['options'] = [];

$params['options'][''] = '- '.__('select').' -';

$eventName = 'event.'.$this->env.'.country.getAll.where';
$callback = function (GenericEvent $event): void {
    $this->container->get('Mod\Country\\'.ucfirst((string) $this->env))->dbData['sql'] .= ' AND a.phone_code IS NOT NULL';
};

$this->dispatcher->addListener($eventName, $callback);

$result = $this->container->get('Mod\Country\\'.ucfirst((string) $this->env))->getAll([
    'order' => (!empty($this->container->get('Mod\Country\\'.ucfirst((string) $this->env))->fields['name']['multilang']) ? 'b' : 'a').'.name ASC',
]);

$this->dispatcher->removeListener($eventName, $callback);

if ((is_countable($result) ? count($result) : 0) > 0) {
    foreach ($result as $row) {
        $params['options'][$row['id']] = '(+'.$this->escape()->html($row['phone_code']).') '.$this->escape()->html($row['name']);
    }
}

$params = ArrayUtils::merge($this->Mod->fieldsMonolang[$key.'_country_id'][$this->env], $params);

echo $this->helper->Html()->getFormField($params);

$params = [];

$params['attr']['name'] = $key;

if (isset($this->Mod->postData[$key])) {
    $params['value'] = $this->Mod->postData[$key];
    $params['attr']['value'] = $this->Mod->postData[$key];
}

$params = ArrayUtils::merge($val[$this->env], $params);

if (!empty($params['help'])) {
    $params['attr']['aria-labelledby'] = 'help-'.$this->helper->Nette()->Strings()->webalize($key);
}

echo $this->helper->Html()->getFormField($params);

echo '<div class="invalid-feedback"></div>'.PHP_EOL;

if (!empty($params['help'])) {
    echo '<div'.$this->escapeAttr([
        'id' => 'help-'.$this->helper->Nette()->Strings()->webalize($key),
        'class' => ['form-text'],
    ]).'>'.nl2br(is_array($params['help']) ? implode(PHP_EOL, $params['help']) : $params['help']).'</div>'.PHP_EOL;
}

echo '</div>'.PHP_EOL;

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
