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

$ModCatform = $this->container->get('Mod\Catform\\'.ucfirst((string) $this->env));
$ModForm = $this->container->get('Mod\Form\\'.ucfirst((string) $this->env));
$ModPage = $this->container->get('Mod\Page\\'.ucfirst((string) $this->env));
?>
<div class="list-group mb-3">
    <a<?php echo $this->escapeAttr([
        'href' => $this->uri($this->env.'.index'),
        'title' => __('Home'),
        'class' => ['list-group-item', 'list-group-item-action'],
    ]); ?>>
        <div class="d-flex">
            <div class="flex-shrink-0">
                <i class="fas fa-chevron-right fa-sm"></i>
            </div>
            <div class="flex-grow-1 ms-2">
                <?php echo $this->escape()->html(__('Home')); ?>
            </div>
        </div>
    </a>

<?php
$result = !empty($this->{$ModCatform->modName.'Row'}) ? [$this->{$ModCatform->modName.'Row'}] : ($this->{$ModCatform->modName.'Result'} ?? []);

if (!empty($result)) {
    foreach ($result as $row) {
        ?>
        <a<?php echo $this->escapeAttr([
            'href' => $this->uri([
                'routeName' => $this->env.'.'.$ModCatform->modName.'.params',
                'data' => [
                    'action' => 'view',
                    'params' => $row['id'],
                    $ModCatform->modName.'_id' => $row['id'],
                ],
            ]),
            'title' => $row['name'],
            'class' => array_merge(['list-group-item', 'list-group-item-action'], $row['id'] === $ModCatform->id ? ['active'] : []),
            'aria-current' => $row['id'] === $ModCatform->id ? 'true' : false,
        ]); ?>>
            <div class="d-flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-chevron-right fa-sm"></i>
                </div>
                <div class="flex-grow-1 ms-2">
                    <?php echo $this->escape()->html($row['label']); ?>
                    <small class="fw-light d-block">
                        <?php echo $this->escape()->html($row['subname']); ?>
                    </small>
                </div>
            </div>
        </a>
    <?php
    }

    if (!empty($this->pageCatformResult)) {
        foreach ($this->pageCatformResult as $row) {
            $menuIds = explode(',', (string) $row['menu_ids']);
            $ModPage->filterValue->sanitize($menuIds, 'intvalArray');
            if (in_array(1, $menuIds, true)) {
                ?>
                <a<?php echo $this->escapeAttr([
                    'href' => $this->uri([
                        'routeName' => $this->env.'.'.$ModPage->modName.'.params',
                        'data' => [
                            'action' => 'view',
                            'params' => $row['id'],
                        ],
                    ]),
                    'title' => $row['name'],
                    'class' => array_merge(['list-group-item', 'list-group-item-action'], $row['id'] === $ModPage->id ? ['active'] : []),
                    'aria-current' => $row['id'] === $ModPage->id ? 'true' : false,
                ]); ?>>
                    <div<?php echo $this->escapeAttr([
                        'class' => array_merge(['d-flex'], !empty($row['level']) ? ['ms-4'] : ['ms-2']),
                    ]); ?>>
                        <div class="flex-shrink-0">
                            <i<?php echo $this->escapeAttr([
                                'class' => array_merge(['fas', 'fa-chevron-right'], !empty($row['level']) ? ['fa-2xs'] : ['fa-xs']),
                            ]); ?>></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <?php echo $this->escape()->html($row['label']); ?>
                        </div>
                    </div>
                </a>
                <?php
            }
        }
    }
}

if (!empty($this->pageResult)) {
    foreach ($this->pageResult as $row) {
        $menuIds = explode(',', (string) $row['menu_ids']);
        $ModPage->filterValue->sanitize($menuIds, 'intvalArray');
        if (in_array(1, $menuIds, true)) {
            ?>
            <a<?php echo $this->escapeAttr([
                'href' => $this->uri([
                    'routeName' => $this->env.'.'.$ModPage->modName.'.params',
                    'data' => [
                        'action' => 'view',
                        'params' => $row['id'],
                    ],
                ]),
                'title' => $row['name'],
                'class' => array_merge(['list-group-item', 'list-group-item-action'], $row['id'] === $ModPage->id ? ['active'] : []),
                'aria-current' => $row['id'] === $ModPage->id ? 'true' : false,
            ]); ?>>
                <div<?php echo $this->escapeAttr([
                    'class' => array_merge(['d-flex'], !empty($row['level']) ? ['ms-2'] : []),
                ]); ?>>
                    <div class="flex-shrink-0">
                        <i<?php echo $this->escapeAttr([
                            'class' => array_merge(['fas', 'fa-chevron-right'], !empty($row['level']) ? ['fa-2xs'] : ['fa-xs']),
                        ]); ?>></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <?php echo $this->escape()->html($row['label']); ?>
                    </div>
                </div>
            </a>
            <?php
        }
    }
}
?>
</div>

<?php
if ($this->auth->hasIdentity()) {
    if (!empty($this->formResult)) {
        // https://stackoverflow.com/a/49645329/3929620
        $ids = array_column($this->formResult, 'id');
        $foundIds = !empty($this->formvalueResult) ? array_column($this->formvalueResult, 'form_id') : [];
        $partialIds = !empty($this->formvaluePartialResult) ? array_column($this->formvaluePartialResult, 'form_id') : [];
        $diffIds = array_diff($ids, $foundIds);

        if (0 === count($diffIds) && 0 === count($partialIds)) {
            ?>
            <div class="list-group mb-3">
                <a<?php echo $this->escapeAttr([
                    'href' => $this->uri([
                        'routeName' => $this->env.'.'.$ModForm->modName,
                        'data' => [
                            'action' => 'index',
                        ],
                    ]),
                    'title' => sprintf(__('Print %1$s'), $this->helper->Nette()->Strings()->lower($ModForm->pluralName)),
                    'class' => array_merge(['list-group-item', 'list-group-item-action', 'list-group-item-primary'], $this->controller === $ModForm->modName && 'index' === $this->action ? ['active'] : []),
                    'aria-current' => $this->controller === $ModForm->modName && 'index' === $this->action ? 'true' : false,
                ]); ?>>
                    <div class="d-flex ms-2">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chevron-right fa-xs"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <?php echo $this->escape()->html(sprintf(__('Print %1$s'), $this->helper->Nette()->Strings()->lower($ModForm->pluralName))); ?>
                        </div>
                    </div>
                </a>
            </div>
            <?php
        } elseif (!empty($this->{$ModCatform->modName.'Row'}) && in_array($this->{$ModCatform->modName.'Row'}['status'], [$ModCatform::MAINTENANCE, $ModCatform::OPEN, $ModCatform::CLOSING], true)) {
            ?>
        <div class="list-group mb-3">
            <?php foreach ($this->formResult as $n => $row) { ?>
                <a<?php echo $this->escapeAttr([
                    'href' => $this->uri([
                        'routeName' => $this->env.'.'.$ModForm->modName.'.params',
                        'data' => [
                            'action' => 'fill',
                            'params' => $row['id'],
                        ],
                    ]),
                    'title' => $row['name'],
                    'class' => array_merge(['list-group-item', 'list-group-item-action', 'list-group-item-primary'], $row['id'] === $ModForm->id ? ['active'] : []),
                    'aria-current' => $row['id'] === $ModForm->id ? 'true' : false,
                ]); ?>>
                    <div class="d-flex ms-2">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chevron-right fa-xs"></i>
                        </div>
                        <div class="flex-grow-1 ms-2">
                            <div class="d-flex">
                                <div class="flex-grow-1">
                                    <?php echo $this->escape()->html(sprintf(__('Form %1$d'), ++$n)); ?>
                                </div>
                                <?php if (in_array($row['id'], $partialIds, true)) { ?>
                                    <div class="flex-shrink-0 ms-2">
                                        <i class="fas fa-check fa-lg text-warning"></i>
                                    </div>
                                <?php } elseif (in_array($row['id'], $foundIds, true)) { ?>
                                    <div class="flex-shrink-0 ms-2">
                                        <i class="fas fa-check fa-lg text-success"></i>
                                    </div>
                                <?php } ?>
                            </div>
                            <small class="fw-light d-block">
                                <?php echo $this->escape()->html($row['label']); ?>
                            </small>
                        </div>
                    </div>
                </a>
            <?php } ?>
        </div>
<?php
        }
    }

    if ($this->rbac->isGranted('formvalue.'.$this->env.'.index')) {
        ?>
        <div class="list-group mb-3">
            <a<?php echo $this->escapeAttr([
                'href' => $this->uri([
                    'routeName' => $this->env.'.formvalue',
                    'data' => [
                        'action' => 'index',
                        $ModCatform->modName.'_id' => 0,
                    ],
                ]),
                'title' => __('Recommendation letters'),
                'class' => array_merge(['list-group-item', 'list-group-item-action', 'list-group-item-info'], 'formvalue' === $this->controller ? ['active'] : []),
                'aria-current' => 'formvalue' === $this->controller && 'index' === $this->action ? 'true' : false,
            ]); ?>>
                <div class="d-flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-chevron-right fa-sm"></i>
                    </div>
                    <div class="flex-grow-1 ms-2">
                        <?php echo $this->escape()->html(__('Recommendation letters')); ?>
                    </div>
                </div>
            </a>
        </div>
        <?php
    }
}
?>

<?php if (!empty($this->{$ModCatform->modName.'Row'})) { ?>
    <div class="card text-bg-light mb-3">
        <div class="card-header">
            <?php echo $this->escape()->html(__('Application form details')); ?>
        </div>
        <div class="card-body">
            <dl>
                <dt>
                    <?php echo $this->escape()->html(__('State')); ?>
                </dt>
                <dd>
                    <span<?php echo $this->escapeAttr([
                        'class' => array_merge(['badge', 'text-uppercase'], $this->helper->Color()->contrast($ModCatform->getStatusColor($this->{$ModCatform->modName.'Row'}['status']), $this->config['theme.color.contrast.yiq.threshold']) ? ['text-white'] : ['text-body']),
                        'style' => 'background-color:'.$ModCatform->getStatusColor($this->{$ModCatform->modName.'Row'}['status']),
                    ]); ?>>
                        <?php echo $this->escape()->html(_x('status-'.$this->{$ModCatform->modName.'Row'}['status'])); ?>
                    </span>
                </dd>

                <dt>
                    <?php echo $this->escape()->html($ModCatform->fields['sdate'][$this->env]['label']); ?>
                </dt>
                <dd>
                    <?php
                $obj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $this->{$ModCatform->modName.'Row'}['sdate'], $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

    echo $this->escape()->html(sprintf(__('%1$s at %2$s'), $obj->format('j F Y'), $obj->format('G:i')));
    ?>
                    <small class="text-muted ms-1">
                        (<?php echo $this->escape()->html($obj->timezone); ?>)
                    </small>
                </dd>

                <?php if (!empty($this->{$ModCatform->modName.'Row'}['cdate'])) { ?>
                    <dt>
                        <?php echo $this->escape()->html($ModCatform->fields['cdate'][$this->env]['label']); ?>
                    </dt>
                    <dd>
                        <?php
                    $obj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $this->{$ModCatform->modName.'Row'}['cdate'], $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

                    echo $this->escape()->html(sprintf(__('%1$s at %2$s'), $obj->format('j F Y'), $obj->format('G:i')));
                    ?>
                        <small class="text-muted ms-1">
                            (<?php echo $this->escape()->html($obj->timezone); ?>)
                        </small>
                    </dd>
                <?php } ?>

                <dt>
                    <?php echo $this->escape()->html($ModCatform->fields['edate'][$this->env]['label']); ?>
                </dt>
                <dd>
                    <?php
    $obj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $this->{$ModCatform->modName.'Row'}['edate'], $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

    echo $this->escape()->html(sprintf(__('%1$s at %2$s'), $obj->format('j F Y'), $obj->format('G:i')));
    ?>
                    <small class="text-muted ms-1">
                        (<?php echo $this->escape()->html($obj->timezone); ?>)
                    </small>
                </dd>

                <dt>
                    <?php echo $this->escape()->html($this->helper->Nette()->Strings()->firstUpper(sprintf($this->helper->Nette()->Strings()->lower(_n('Total %1$s', 'Total %1$s', 2)), $this->helper->Nette()->Strings()->lower(_n('Form', 'Forms', 2))))); ?>
                </dt>
                <dd>
                    <?php if (!empty($this->formResult)) {
                        echo is_countable($this->formResult) ? count($this->formResult) : 0;
                    } else {
                        $eventName = 'event.'.$this->env.'.'.$ModForm->modName.'.getCount.where';
                        $callback = function (GenericEvent $event) use ($ModCatform, $ModForm): void {
                            $ModForm->dbData['sql'] .= ' AND a.cat'.$ModForm->modName.'_id = :cat'.$ModForm->modName.'_id';
                            $ModForm->dbData['args']['cat'.$ModForm->modName.'_id'] = (int) $this->{$ModCatform->modName.'Row'}['id'];
                        };

                        $this->dispatcher->addListener($eventName, $callback);

                        echo $ModForm->getCount([
                            'active' => true,
                        ]);

                        $this->dispatcher->removeListener($eventName, $callback);
                    } ?>
                </dd>
            </dl>
        </div>
    </div>
<?php
}
