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

use Symfony\Component\EventDispatcher\GenericEvent;

if (!empty($result ?? $this->catformResult ?? [])) {
    $ModCatform = $this->container->get('Mod\Catform\\'.ucfirst((string) $this->env));
    $ModForm = $this->container->get('Mod\Form\\'.ucfirst((string) $this->env));
    ?>
    <div class="row gy-3">
    <?php foreach ($result ?? $this->catformResult as $row) { ?>
        <div class="col-lg-6">
            <div class="card overflow-hidden border-primary h-100">
                <div class="card-header text-bg-primary text-center">
                    <h5 class="fw-bold mb-0">
                        <?php echo $this->escape()->html($row['name']); ?>
                        <small class="h6 fw-light d-inline-block">
                            <?php echo $this->escape()->html($row['subname']); ?>
                        </small>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($row['text'])) { ?>
                        <?php echo $this->helper->Nette()->Strings()->truncate(nl2br((string) $this->escapeHtml($row['text'])), 1000); ?>
                        <hr>
                    <?php } ?>

                    <dl class="row">
                        <dt<?php echo $this->escapeAttr([
                            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
                        ]); ?>>
                            <?php echo $this->escape()->html(__('State')); ?>
                        </dt>
                        <dd<?php echo $this->escapeAttr([
                            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
                        ]); ?>>
                            <span<?php echo $this->escapeAttr([
                                'class' => array_merge(['badge', 'text-uppercase'], $this->helper->Color()->contrast($ModCatform->getStatusColor($row['status']), $this->config['theme.color.contrast.yiq.threshold']) ? ['text-white'] : ['text-body']),
                                'style' => 'background-color:'.$ModCatform->getStatusColor($row['status']),
                            ]); ?>>
                                <?php echo $this->escape()->html(_x('status-'.$row['status'])); ?>
                            </span>
                        </dd>

                        <dt<?php echo $this->escapeAttr([
                            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
                        ]); ?>>
                            <?php echo $this->escape()->html($ModCatform->fields['sdate'][$this->env]['label']); ?>
                        </dt>
                        <dd<?php echo $this->escapeAttr([
                            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
                        ]); ?>>
                            <?php
                                $obj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $row['sdate'], $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

        echo $this->escape()->html(sprintf(__('%1$s at %2$s'), $obj->format('j F Y'), $obj->format('G:i')));
        ?>
                            <small class="text-muted ms-1">
                                (<?php echo $this->escape()->html($obj->timezone); ?>)
                            </small>
                        </dd>

                        <?php if (!empty($row['cdate'])) { ?>
                            <dt<?php echo $this->escapeAttr([
                                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
                            ]); ?>>
                                <?php echo $this->escape()->html($ModCatform->fields['cdate'][$this->env]['label']); ?>
                            </dt>
                            <dd<?php echo $this->escapeAttr([
                                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
                            ]); ?>>
                                <?php
                                    $obj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $row['cdate'], $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

                            echo $this->escape()->html(sprintf(__('%1$s at %2$s'), $obj->format('j F Y'), $obj->format('G:i')));
                            ?>
                                <small class="text-muted ms-1">
                                    (<?php echo $this->escape()->html($obj->timezone); ?>)
                                </small>
                            </dd>
                        <?php } ?>

                        <dt<?php echo $this->escapeAttr([
                            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
                        ]); ?>>
                            <?php echo $this->escape()->html($ModCatform->fields['edate'][$this->env]['label']); ?>
                        </dt>
                        <dd<?php echo $this->escapeAttr([
                            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
                        ]); ?>>
                            <?php
        $obj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $row['edate'], $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

        echo $this->escape()->html(sprintf(__('%1$s at %2$s'), $obj->format('j F Y'), $obj->format('G:i')));
        ?>
                            <small class="text-muted ms-1">
                                (<?php echo $this->escape()->html($obj->timezone); ?>)
                            </small>
                        </dd>

                        <dt<?php echo $this->escapeAttr([
                            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
                        ]); ?>>
                            <?php echo $this->escape()->html($this->helper->Nette()->Strings()->firstUpper(sprintf($this->helper->Nette()->Strings()->lower(_n('Total %1$s', 'Total %1$s', 2)), $this->helper->Nette()->Strings()->lower(_n('Form', 'Forms', 2))))); ?>
                        </dt>
                        <dd<?php echo $this->escapeAttr([
                            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
                        ]); ?>>
                            <?php
                            $eventName = 'event.'.$this->env.'.'.$ModForm->modName.'.getCount.where';
        $callback = function (GenericEvent $event) use ($ModForm, $row): void {
            $ModForm->dbData['sql'] .= ' AND a.cat'.$ModForm->modName.'_id = :cat'.$ModForm->modName.'_id';
            $ModForm->dbData['args']['cat'.$ModForm->modName.'_id'] = (int) $row['id'];
        };

        $this->dispatcher->addListener($eventName, $callback);

        echo $ModForm->getCount([
            'active' => true,
        ]);

        $this->dispatcher->removeListener($eventName, $callback);
        ?>
                        </dd>
                    </dl>

                    <div class="d-grid">
                        <a class="btn btn-primary btn-lg mx-sm-auto"<?php echo $this->escapeAttr([
                            'href' => $this->uri([
                                'routeName' => $this->env.'.'.$ModCatform->modName.'.params',
                                'data' => [
                                    'action' => 'view',
                                    'params' => $row['id'],
                                    $ModCatform->modName.'_id' => $row['id'],
                                ],
                            ]),
                            'title' => $row['name'],
                        ]); ?>>
                            <?php echo $this->escape()->html(__('Enter')); ?>
                            <i class="fas fa-long-arrow-alt-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php } ?>
    </div>
<?php
}
