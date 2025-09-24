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
?>
<div class="col-sm-6 col-md-4 mb-3">
    <div class="card">

        <div class="card-header">
            <div class="row">
                <div class="col-sm">
                    <h4 class="mb-sm-0">
                        <?php echo $this->escape()->html($Mod->pluralName); ?>
                    </h4>
                </div>
                <div class="col-sm-auto text-sm-end">
                    <a class="btn btn-outline-secondary btn-sm"<?php echo $this->escapeAttr([
                        'href' => $this->uri($this->env.'.'.$controller),
                        'title' => __('view'),
                    ]); ?>><?php echo $this->escape()->html(__('view')); ?> <i class="fas fa-angle-double-right fa-sm ms-1"></i></a>
                </div>
            </div>
        </div>

        <div class="card-body">
            <?php
            $oldController = $this->controller;
$this->controller = $controller;

$totRows = 0;

if ((is_countable($rows) ? count($rows) : 0) > 0) {
    ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover table-sm small">
                        <thead class="table-light">
                            <tr>

                              <?php
                  foreach (array_slice($Mod->fieldsSortable, 0, 3) as $key => $val) {
                      echo '<th scope="col">'.PHP_EOL;

                      echo $val[$this->env]['label']; // <-- no escape, it can contain html tags

                      echo '</th>'.PHP_EOL;
                  } ?>

                            </tr>
                        </thead>
                        <tbody>

                            <?php
                            foreach ($rows as $rowKey => $row) {
                                if ('totRows' === $rowKey) {
                                    $totRows = (int) $row;
                                } else {
                                    echo '<tr scope="row">'.PHP_EOL;

                                    foreach (array_slice($Mod->fieldsSortable, 0, 3) as $key => $val) {
                                        if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$controller.'/partial/index-'.$key.'.php')) {
                                            include _ROOT.'/app/view/'.$this->env.'/controller/'.$controller.'/partial/index-'.$key.'.php';
                                        } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/base/partial/index-'.$key.'.php')) {
                                            include _ROOT.'/app/view/'.$this->env.'/base/partial/index-'.$key.'.php';
                                        } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$controller.'/partial/index-'.$key.'.php')) {
                                            include _ROOT.'/app/view/default/controller/'.$controller.'/partial/index-'.$key.'.php';
                                        } elseif (file_exists(_ROOT.'/app/view/default/base/partial/index-'.$key.'.php')) {
                                            include _ROOT.'/app/view/default/base/partial/index-'.$key.'.php';
                                        } else {
                                            echo '<td>'.$row[$key] ?? '</td>'.PHP_EOL;
                                        }
                                    }

                                    echo '</tr>'.PHP_EOL;
                                }
                            } ?>

                        </tbody>
                    </table>
                </div>
            <?php
}

$this->controller = $oldController;
?>

            <p class="card-text small text-muted text-end mb-0">
                <?php echo $this->escape()->html(sprintf(__('Total: %1$d'), $totRows)); ?>
            </p>
        </div>
    </div>
</div>
