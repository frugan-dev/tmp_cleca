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

if (!empty($this->widgetData[$widget['controller']][$widget['action']]['result'])) {
    ?>
<div class="col-12 mb-3">
    <div class="card overflow-hidden">
        <div class="card-header">
            <div class="row">
                <div class="col-sm">
                    <h5 class="mb-sm-0">
                        <?php echo $this->escape()->html($this->widgetData[$widget['controller']][$widget['action']]['title'] ?? $widget['controller']); ?>
                    </h5>
                </div>
                <div class="col-sm-auto text-sm-end">
                    <a class="btn btn-outline-secondary btn-sm"<?php echo $this->escapeAttr([
                        'href' => $this->widgetData[$widget['controller']][$widget['action']]['href'] ?? $this->uri($this->env.'.'.$widget['controller']),
                        'title' => $this->widgetData[$widget['controller']][$widget['action']]['title'] ?? false,
                    ]); ?>>
                        <?php echo $this->escape()->html(__('View all')); ?> <i class="fas fa-long-arrow-alt-right fa-fw"></i>
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <?php
            $controller = $this->controller;
    $action = $this->action;
    $pager = $this->pager;

    $this->pager->totRows = $this->widgetData[$widget['controller']][$widget['action']]['totRows'];

    $this->addData([
        'controller' => $widget['controller'],
        'action' => $widget['action'],
        'Mod' => $this->widgetData[$widget['controller']][$widget['action']]['Mod'],
        'result' => $this->widgetData[$widget['controller']][$widget['action']]['result'],
        'pager' => $pager,
        'pagination' => [],
    ]);

    // prependPath() or appendPath() also available
    $this->container->get('view')->getViewRegistry()->setPaths([
        _ROOT.'/app/view/'.$this->env.'/controller/'.$widget['controller'],
        _ROOT.'/app/view/'.$this->env.'/base',
        _ROOT.'/app/view/default/controller/'.$widget['controller'],
        _ROOT.'/app/view/default/base',
    ]);

    $this->render($widget['action']);

    if ($this->hasSection('content')) {
        echo $this->getSection('content');
    }

    $this->addData([
        'controller' => $controller,
        'action' => $action,
        'pager' => $pager,
    ]); ?>
        </div>
    </div>
</div>
<?php
}
