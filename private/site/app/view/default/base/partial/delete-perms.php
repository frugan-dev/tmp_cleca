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

echo '<div class="table-responsive">'.PHP_EOL;
echo '<table class="table table-bordered table-striped table-sm small">'.PHP_EOL;

echo '<thead>'.PHP_EOL;
echo '<tr>'.PHP_EOL;

echo '<th scope="col" class="text-nowrap">'.$this->escape()->html(__('Module')).'</th>'.PHP_EOL;

foreach ($this->config['mod.perms.action.arr'] as $action) {
    echo '<th scope="col" class="text-nowrap">'.$this->escape()->html(__($action)).'</th>'.PHP_EOL;
}

echo '</tr>'.PHP_EOL;
echo '</thead>'.PHP_EOL;

echo '<tbody>'.PHP_EOL;

foreach ($this->Mod->mods as $controller => $row) {
    echo '<tr>'.PHP_EOL;

    echo '<td class="text-nowrap text-end fw-bold">'.PHP_EOL;
    echo $this->escape()->html($row['pluralName']);
    echo '</td>'.PHP_EOL;

    foreach ($this->config['mod.perms.action.arr'] as $action) {
        echo '<td class="text-nowrap">'.PHP_EOL;

        if (array_key_exists($action, $row['perms'])) {
            foreach ($row['perms'][$action] as $env => $perms) {
                foreach ($perms as $perm) {
                    echo '<ul class="list-unstyled mb-0">'.PHP_EOL;

                    if (in_array($controller.'.'.$env.'.'.$perm, (array) $this->Mod->{$key}, true)) {
                        echo '<i class="fas fa-check fa-fw text-success me-1"></i>';
                    } else {
                        echo '<i class="fas fa-times fa-fw text-danger me-1"></i>';
                    }

                    echo ' '.$this->escape()->html(__($env).': '.$this->container->get('Mod\\'.ucfirst((string) $controller.'\\'.ucfirst((string) $env)))->getPermLabel($perm));
                    echo '</ul>'.PHP_EOL;
                }
            }
        }

        echo '</td>'.PHP_EOL;
    }

    echo '</tr>'.PHP_EOL;
}

echo '</tbody>'.PHP_EOL;

echo '</table>'.PHP_EOL;
echo '</div>'.PHP_EOL;

echo '</dd>'.PHP_EOL;
