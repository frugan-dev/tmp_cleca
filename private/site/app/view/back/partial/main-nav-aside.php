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

if ($this->container->has('modsSortedByWeight')) {
    $groupId = -1;
    foreach ($this->container->get('modsSortedByWeight') as $item) {
        if ($this->rbac->isGranted($item['controller'].'.'.$this->env.'.index')) {
            if ($this->container->get('Mod\\'.ucfirst((string) $item['controller']).'\\'.ucfirst((string) $this->env))->groupId !== $groupId) {
                $groupId = $this->container->get('Mod\\'.ucfirst((string) $item['controller']).'\\'.ucfirst((string) $this->env))->groupId;
                ?>
                <hr class="my-0">
                <?php
            }

            if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$item['controller'].'/partial/item-nav-aside.php')) {
                include _ROOT.'/app/view/'.$this->env.'/controller/'.$item['controller'].'/partial/item-nav-aside.php';
            } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$item['controller'].'/partial/item-nav-aside.php')) {
                include _ROOT.'/app/view/default/controller/'.$item['controller'].'/partial/item-nav-aside.php';
            } else {
                ?>
    <a data-bs-toggle="tooltip" data-bs-placement="right"<?php echo $this->escapeAttr([
        'href' => $this->uri($this->env.'.'.$item['controller']),
        'title' => $this->container->get('Mod\\'.ucfirst((string) $item['controller']).'\\'.ucfirst((string) $this->env))->pluralName,
        'class' => array_merge(['nav-link'], ($item['controller'] === $this->controller) ? ['active'] : []),
    ]); ?>>
        <i<?php echo $this->escapeAttr([
            'class' => ['fas', 'fa-fw', $this->container->get('Mod\\'.ucfirst((string) $item['controller']).'\\'.ucfirst((string) $this->env))->faClass],
        ]); ?>></i>
        <span class="d-none-collapsed d-inline-expanded ms-1">
            <?php echo $this->escape()->html($this->container->get('Mod\\'.ucfirst((string) $item['controller']).'\\'.ucfirst((string) $this->env))->pluralName); ?>
        </span>
    </a>
 <?php
            }
        }
    }
}
