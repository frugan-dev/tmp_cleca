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

echo '</label>'.PHP_EOL;
echo '<div'.$this->escapeAttr([
    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
]).'>'.PHP_EOL;

$params = [];

$params['attr']['name'] = $key;

if (!empty($this->Mod->postData)) {
    $params['value'] = $this->Mod->postData[$key] ?? 0;
}

echo '<div'.$this->escapeAttr(['class' => array_merge(['form-check'], !empty($this->config['theme.checkbox.switches']) ? ['form-switch'] : [])]).'>'.PHP_EOL;

$params = ArrayUtils::merge($val[$this->env], $params);

if (!empty($params['help'])) {
    $params['attr']['aria-labelledby'] = 'help-'.$this->helper->Nette()->Strings()->webalize($key);
}

echo $this->helper->Html()->getFormField($params);

$name = [];

$ModCatmember = $this->container->get('Mod\Catmember\\'.ucfirst((string) $this->env));

$eventName = 'event.'.$this->env.'.'.$ModCatmember->modName.'.getOne.where';
$callback = function (GenericEvent $event) use ($ModCatmember): void {
    $ModCatmember->dbData['sql'] .= ' AND a.main = :main';
    $ModCatmember->dbData['args']['main'] = 1;
};

$this->dispatcher->addListener($eventName, $callback);

$row = $ModCatmember->getOne([
    'id' => false,
]);

$this->dispatcher->removeListener($eventName, $callback);

echo '<label class="form-check-label"'.$this->escapeAttr(['for' => $key]).'>'.$this->escape()->html(sprintf(_x('Delete all main %1$s.', $this->container->get('Mod\Member\\'.ucfirst((string) $this->env))->context), $this->helper->Nette()->Strings()->lower($this->container->get('Mod\Member\\'.ucfirst((string) $this->env))->pluralName).' ('.($row['name'] ?? '').')')).'</label>'.PHP_EOL;

__('Delete all main %1$s.', 'default');
__('Delete all main %1$s.', 'male');
__('Delete all main %1$s.', 'female');

echo '</div>'.PHP_EOL;

echo '<div class="invalid-feedback"></div>'.PHP_EOL;

if (!empty($params['help'])) {
    echo '<div'.$this->escapeAttr([
        'id' => 'help-'.$this->helper->Nette()->Strings()->webalize($key),
        'class' => ['form-text'],
    ]).'>'.nl2br(is_array($params['help']) ? implode(PHP_EOL, $params['help']) : $params['help']).'</div>'.PHP_EOL;
}

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
