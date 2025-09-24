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

$this->beginSection('content');
?>
<form data-sync class="needs-validation" novalidate method="POST" action="" autocomplete="off" enctype="multipart/form-data" role="form">

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
        }
    }
?>

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

            foreach ($this->Mod->fieldsMultilang as $key => $val) {
                if (isset($val[$this->env]['hidden']) && in_array($this->action, $val[$this->env]['hidden'], true)) {
                    continue;
                }

                if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/partial/'.$this->action.'-multilang-'.$key.'.php')) {
                    include _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/partial/'.$this->action.'-multilang-'.$key.'.php';
                } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-multilang-'.$key.'.php')) {
                    include _ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-multilang-'.$key.'.php';
                } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/default/controller/'.$this->controller.'/partial/'.$this->action.'-multilang-'.$key.'.php')) {
                    include _ROOT.'/app/view/'.$this->env.'/default/controller/'.$this->controller.'/partial/'.$this->action.'-multilang-'.$key.'.php';
                } elseif (file_exists(_ROOT.'/app/view/default/base/partial/'.$this->action.'-multilang-'.$key.'.php')) {
                    include _ROOT.'/app/view/default/base/partial/'.$this->action.'-multilang-'.$key.'.php';
                } else {
                    echo '<div'.$this->escapeAttr(['class' => ['row', 'row-multilang-'.$this->helper->Nette()->Strings()->webalize($key), 'row-multilang-'.$langId.'-'.$this->helper->Nette()->Strings()->webalize($key), 'mb-3']]).'>'.PHP_EOL;
                    echo '<label'.$this->escapeAttr([
                        'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
                        'for' => 'multilang|'.$langId.'|'.$this->helper->Nette()->Strings()->webalize($key),
                    ]).'>'.PHP_EOL;

                    echo $val[$this->env]['label']; // <-- no escape, it can contain html tags

                    echo !empty($val[$this->env]['attr']['required']) ? ' *' : '';

                    echo '</label>'.PHP_EOL;
                    echo '<div'.$this->escapeAttr([
                        'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
                    ]).'>'.PHP_EOL;

                    $params = [];

                    $params['attr']['name'] = 'multilang|'.$langId.'|'.$key;
                    $params['attr']['id'] = 'multilang-'.$langId.'-'.$key;

                    if (isset($this->Mod->postData['multilang|'.$langId.'|'.$key])) {
                        $params['value'] = $this->Mod->postData['multilang|'.$langId.'|'.$key];
                        $params['attr']['value'] = $this->Mod->postData['multilang|'.$langId.'|'.$key];
                    }

                    $params = ArrayUtils::merge($val[$this->env], $params);

                    if (!empty($params['help'])) {
                        $params['attr']['aria-labelledby'] = 'help-'.$langId.'-'.$this->helper->Nette()->Strings()->webalize($key);
                    }

                    echo $this->helper->Html()->getFormField($params);

                    echo '<div class="invalid-feedback"></div>'.PHP_EOL;

                    if (!empty($params['help'])) {
                        echo '<div'.$this->escapeAttr([
                            'id' => 'help-'.$langId.'-'.$this->helper->Nette()->Strings()->webalize($key),
                            'class' => ['form-text'],
                        ]).'>'.nl2br(is_array($params['help']) ? implode(PHP_EOL, $params['help']) : $params['help']).'</div>'.PHP_EOL;
                    }

                    echo '</div>'.PHP_EOL;
                    echo '</div>'.PHP_EOL;
                }
            }

            echo '</div>'.PHP_EOL;
        }
        ?>
        </div>

    <?php } ?>

    <div class="row mb-3">
        <div<?php echo $this->escapeAttr([
            'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.btn.col.class'] ?? $this->config['theme.'.$this->env.'.btn.col.class'] ?? $this->config['theme.'.$this->action.'.btn.col.class'] ?? $this->config['theme.btn.col.class'] ?? false,
        ]); ?>>
            <button type="submit" data-loading-text="<span class='spinner-border spinner-border-sm align-middle me-1' role='status' aria-hidden='true'></span> <?php echo $this->escapeAttr(__('Please wait')); ?>&hellip;"<?php echo $this->escapeAttr([
                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.btn.class'] ?? $this->config['theme.'.$this->env.'.btn.class'] ?? $this->config['theme.'.$this->action.'.btn.class'] ?? $this->config['theme.btn.class'] ?? false,
            ]); ?>>
                <?php echo $this->escape()->html(__('Save')); ?>
                <i class="fas fa-floppy-disk ms-1"></i>
            </button>
        </div>
    </div>

</form>

<?php
$this->endSection();
