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

$this->beginSection('content');
?>
<form data-sync class="needs-validation" novalidate method="POST" action="" autocomplete="off" role="form">

    <dl class="row">
        <?php
        foreach ($this->Mod->fieldsMonolang as $key => $val) {
            if (isset($val[$this->env]['hidden']) && in_array($this->action, $val[$this->env]['hidden'], true)) {
                continue;
            }

            if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/partial/'.$this->action.'-'.$key.'.php')) {
                include _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/partial/'.$this->action.'-'.$key.'.php';
            } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-'.$key.'.php')) {
                include _ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-'.$key.'.php';
            } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$this->controller.'/partial/'.$this->action.'-'.$key.'.php')) {
                include _ROOT.'/app/view/default/controller/'.$this->controller.'/partial/'.$this->action.'-'.$key.'.php';
            } elseif (file_exists(_ROOT.'/app/view/default/base/partial/'.$this->action.'-'.$key.'.php')) {
                include _ROOT.'/app/view/default/base/partial/'.$this->action.'-'.$key.'.php';
            } else {
                echo '<dt'.$this->escapeAttr([
                    'class' => array_merge(['dt-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? []),
                ]).'>'.PHP_EOL;

                echo $val[$this->env]['label']; // <-- no escape, it can contain html tags

                echo '</dt>'.PHP_EOL;

                echo '<dd'.$this->escapeAttr([
                    'class' => array_merge(['dd-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? []),
                ]).'>'.PHP_EOL;

                // https://stackoverflow.com/a/4996121/3929620
                echo '' !== trim((string) $this->Mod->{$key}) ? $this->helper->Nette()->Strings()->truncate($this->escapeHtml(strip_tags((string) $this->Mod->{$key})), 80) : '&nbsp;';

                echo '</dd>'.PHP_EOL;
            }
        } ?>
    </dl>

    <?php if ((is_countable($this->Mod->fieldsMultilang) ? count($this->Mod->fieldsMultilang) : 0) > 0) { ?>

        <ul class="nav nav-tabs" role="tablist">
            <?php
            $firstKey = array_key_first($this->lang->arr);

        foreach ($this->lang->arr as $langId => $langRow) {
            $class = ['nav-link'];
            $ariaSelected = false;

            if ($langId === $firstKey) {
                $class[] = 'active';
                $ariaSelected = true;
            }

            echo '<li class="nav-item" role="presentation">'.PHP_EOL;
            echo '<button type="button" role="tab" data-bs-toggle="tab"'.$this->escapeAttr([
                'class' => $class,
                'id' => 'tab-'.$langRow['isoCode'],
                'data-bs-target' => '#tabpanel-'.$langRow['isoCode'],
                'aria-controls' => 'tabpanel-'.$langRow['isoCode'],
                'aria-selected' => $ariaSelected,
            ]).'>'.$this->escape()->html($langRow['name']).'</button>'.PHP_EOL;
            echo '</li>'.PHP_EOL;
        }
        ?>
        </ul>

        <div class="tab-content">
            <?php
        foreach ($this->lang->arr as $langId => $langRow) {
            $class = ['tab-pane', 'fade', 'mt-3'];

            if ($langId === $firstKey) {
                $class[] = 'show';
                $class[] = 'active';
            }

            echo '<div role="tabpanel"'.$this->escapeAttr([
                'class' => $class,
                'id' => 'tabpanel-'.$langRow['isoCode'],
                'aria-labelledby' => 'tab-'.$langRow['isoCode'],
                'tabindex' => 0,
            ]).'>'.PHP_EOL;

            echo '<dl class="row">'.PHP_EOL;

            foreach ($this->Mod->fieldsMultilang as $key => $val) {
                if (isset($val[$this->env]['hidden']) && in_array($this->action, $val[$this->env]['hidden'], true)) {
                    continue;
                }

                if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/partial/'.$this->action.'-multilang-'.$key.'.php')) {
                    include _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/partial/'.$this->action.'-multilang-'.$key.'.php';
                } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-multilang-'.$key.'.php')) {
                    include _ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-multilang-'.$key.'.php';
                } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$this->controller.'/partial/'.$this->action.'-multilang-'.$key.'.php')) {
                    include _ROOT.'/app/view/default/controller/'.$this->controller.'/partial/'.$this->action.'-multilang-'.$key.'.php';
                } elseif (file_exists(_ROOT.'/app/view/default/base/partial/'.$this->action.'-multilang-'.$key.'.php')) {
                    include _ROOT.'/app/view/default/base/partial/'.$this->action.'-multilang-'.$key.'.php';
                } else {
                    echo '<dt'.$this->escapeAttr([
                        'class' => array_merge(['dt-multilang-'.$this->helper->Nette()->Strings()->webalize($key), 'dt-multilang-'.$langId.'-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? []),
                    ]).'>'.PHP_EOL;

                    echo $val[$this->env]['label']; // <-- no escape, it can contain html tags

                    echo '</dt>'.PHP_EOL;

                    echo '<dd'.$this->escapeAttr([
                        'class' => array_merge(['dd-multilang-'.$this->helper->Nette()->Strings()->webalize($key), 'dd-multilang-'.$langId.'-'.$this->helper->Nette()->Strings()->webalize($key)], $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? []),
                    ]).'>'.PHP_EOL;

                    // https://stackoverflow.com/a/4996121/3929620
                    echo '' !== trim((string) $this->Mod->multilang[$langId][$key]) ? $this->helper->Nette()->Strings()->truncate($this->escapeHtml(strip_tags((string) $this->Mod->multilang[$langId][$key])), 80) : '&nbsp;';

                    echo '</dd>'.PHP_EOL;
                }
            }

            echo '</dl>'.PHP_EOL;
            echo '</div>'.PHP_EOL;
        }
        ?>
        </div>

    <?php } ?>

    <?php if (0 === (is_countable($this->Mod->errorDeps) ? count($this->Mod->errorDeps) : 0)) { ?>

        <div class="row mb-3">
            <div<?php echo $this->escapeAttr([
                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.btn.col.class'] ?? $this->config['theme.'.$this->env.'.btn.col.class'] ?? $this->config['theme.'.$this->action.'.btn.col.class'] ?? $this->config['theme.btn.col.class'] ?? false,
            ]); ?>>
                <button type="submit" data-loading-text="<span class='spinner-border spinner-border-sm align-middle me-1' role='status' aria-hidden='true'></span> <?php echo $this->escapeAttr(__('Please wait')); ?>&hellip;"<?php echo $this->escapeAttr([
                    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.btn.class'] ?? $this->config['theme.'.$this->env.'.btn.class'] ?? $this->config['theme.'.$this->action.'.btn.class'] ?? $this->config['theme.btn.class'] ?? false,
                ]); ?>>
                    <?php echo $this->escape()->html(__('Confirm')); ?>
                    <i class="fas fa-trash-alt ms-1"></i>
                </button>
            </div>
        </div>

    <?php
    } else {
        if (!$this->hasSection('deps')) {
            $this->setSection('deps', $this->render('deps'));
        }
        echo $this->getSection('deps');
    }
?>

</form>

<?php
$this->endSection();
