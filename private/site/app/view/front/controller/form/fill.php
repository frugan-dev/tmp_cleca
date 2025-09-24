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

if (!empty($this->formfieldResult)) {
    $foundIds = !empty($this->formvalueResult) ? array_column($this->formvalueResult, 'form_id') : [];
    $partialIds = !empty($this->formvaluePartialResult) ? array_column($this->formvaluePartialResult, 'form_id') : [];
    ?>
    <form data-sync class="needs-validation" novalidate method="POST" action="" autocomplete="off" enctype="multipart/form-data" role="form"<?php echo $this->escapeAttr([
        'id' => 'form-'.$this->action,
    ]); ?>>
        <input type="hidden"<?php echo $this->escapeAttr([
            'name' => $this->controller.'_id',
            'value' => $this->Mod->id,
        ]); ?>>
        <input type="hidden" name="submitted"<?php echo $this->escapeAttr([
            'value' => in_array($this->Mod->id, $foundIds, true) && !in_array($this->Mod->id, $partialIds, true) ? 1 : 0,
        ]); ?>>

        <?php
        foreach ($this->formfieldResult as $row) {
            if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'field/partial/'.$this->action.'-'.$row['type'].'.php')) {
                include _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'field/partial/'.$this->action.'-'.$row['type'].'.php';
            } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-'.$row['type'].'.php')) {
                include _ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-'.$row['type'].'.php';
            } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$this->controller.'field/partial/'.$this->action.'-'.$row['type'].'.php')) {
                include _ROOT.'/app/view/default/controller/'.$this->controller.'field/partial/'.$this->action.'-'.$row['type'].'.php';
            } elseif (file_exists(_ROOT.'/app/view/default/base/partial/'.$this->action.'-'.$row['type'].'.php')) {
                include _ROOT.'/app/view/default/base/partial/'.$this->action.'-'.$row['type'].'.php';
            } else {
                echo '<div'.$this->escapeAttr(['class' => ['row', 'row-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']), 'row-'.$this->helper->Nette()->Strings()->webalize($row['type']), 'mb-3']]).'>'.PHP_EOL;
                echo '<label'.$this->escapeAttr([
                    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
                    'for' => $this->controller.'field-'.$row['id'],
                ]).'>'.PHP_EOL;

                if (!empty($row['name'])) {
                    echo $this->escape()->html($row['name']);
                }

                echo !empty($row['required']) ? ' *' : '';

                echo '</label>'.PHP_EOL;
                echo '<div'.$this->escapeAttr([
                    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.class'] ?? $this->config['theme.'.$this->env.'.value.class'] ?? $this->config['theme.'.$this->action.'.value.class'] ?? $this->config['theme.value.class'] ?? false,
                ]).'>'.PHP_EOL;

                $params = [
                    'type' => 'input',
                    'attr' => [
                        'name' => $this->controller.'field_'.$row['id'],
                        'type' => 'text',
                        'id' => $this->controller.'field-'.$row['id'],
                        'maxlength' => 128,
                        'class' => ['form-control'],
                        'required' => (bool) $row['required'],
                    ],
                ];

                if (isset($this->Mod->postData[$this->controller.'field_'.$row['id']])) {
                    $params['value'] = $this->Mod->postData[$this->controller.'field_'.$row['id']];
                    $params['attr']['value'] = $this->Mod->postData[$this->controller.'field_'.$row['id']];
                } else {
                    $params['value'] = $row[$this->controller.'value_data'] ?? null;
                    $params['attr']['value'] = $row[$this->controller.'value_data'] ?? null;
                }

                if (!empty($row['richtext'])) {
                    $params['attr']['aria-labelledby'] = 'help-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']);
                }

                echo $this->helper->Html()->getFormField($params);

                echo '<div class="invalid-feedback"></div>'.PHP_EOL;

                if (!empty($row['richtext'])) {
                    echo '<div'.$this->escapeAttr([
                        'id' => 'help-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']),
                        'class' => ['form-text'],
                    ]).'>'.$row['richtext'].'</div>'.PHP_EOL;
                }

                echo '</div>'.PHP_EOL;
                echo '</div>'.PHP_EOL;
            }
        }
    ?>

        <div class="row mb-3">
            <div<?php echo $this->escapeAttr([
                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.value.offset.class'] ?? $this->config['theme.'.$this->env.'.value.offset.class'] ?? $this->config['theme.'.$this->action.'.value.offset.class'] ?? $this->config['theme.value.offset.class'] ?? false,
            ]); ?>>
                <ul class="list-unstyled text-muted fs-xs">
                    <li>* <?php echo $this->escape()->html(__('Required fields')); ?></li>
                </ul>
            </div>
        </div>

        <div class="row mb-3">
            <div<?php echo $this->escapeAttr([
                'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.btn.col.class'] ?? $this->config['theme.'.$this->env.'.btn.col.class'] ?? $this->config['theme.'.$this->action.'.btn.col.class'] ?? $this->config['theme.btn.col.class'] ?? false,
            ]); ?>>
                <?php // TODO - modal confirm at last form??>
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
    if ($this->rbac->isGranted($this->controller.'value.api.upload')) {
        echo $this->render('form-delete');
    }
    ?>

    <?php // no spaces or PHP_EOL, see here https://stackoverflow.com/a/54170743/3929620?>
    <script class="template-recommendation-wrapper" type="text/x-handlebars-template"><?php
    ?><div class="recommendation-wrapper">
            <fieldset class="border border-secondary p-3">
                <legend class="border border-secondary fs-6 px-2 mb-0">
                    {{title}}
                </legend>
                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label text-lg-end"<?php echo $this->escapeAttr([
                        'for' => $this->controller.'field-{{id}}-teachers-{{counter}}-firstname',
                    ]); ?>>
                        <?php echo $this->escape()->html(__('Firstname')); ?> *
                    </label>
                    <div class="col-lg">
                        <?php
                    $params = [
                        'type' => 'input',
                        'attr' => [
                            'name' => $this->controller.'field_{{id}}_teachers[{{counter}}][firstname]',
                            'type' => 'text',
                            'id' => $this->controller.'field-{{id}}-teachers-{{counter}}-firstname',
                            'maxlength' => 128,
                            'class' => ['form-control'],
                            // Use data-required instead of required to handle dynamic visibility:
                            // - data-required="true" marks fields that should be required when section is visible
                            // - The actual 'required' attribute is managed by JavaScript based on radio button state
                            // - This prevents validation errors on hidden fields when radio button = "No"
                            'data-required' => true,
                            'value' => '{{valueFirstname}}',
                        ],
                    ];

    if (!empty($params['help'])) {
        $params['attr']['aria-labelledby'] = 'help-'.$this->controller.'field-{{id}}-teachers-{{counter}}-firstname';
    }

    echo $this->helper->Html()->getFormField($params);

    echo '<div class="invalid-feedback"></div>'.PHP_EOL;

    if (!empty($params['help'])) {
        echo '<div'.$this->escapeAttr([
            'id' => 'help-'.$this->controller.'field-{{id}}-teachers-{{counter}}-firstname',
            'class' => ['form-text'],
        ]).'>'.nl2br(is_array($params['help']) ? implode(PHP_EOL, $params['help']) : $params['help']).'</div>'.PHP_EOL;
    }
    ?>
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="col-lg-4 col-form-label text-lg-end"<?php echo $this->escapeAttr([
                        'for' => $this->controller.'field-{{id}}-teachers-{{counter}}-lastname',
                    ]); ?>>
                        <?php echo $this->escape()->html(__('Lastname')); ?> *
                    </label>
                    <div class="col-lg">
                        <?php
    $params = [
        'type' => 'input',
        'attr' => [
            'name' => $this->controller.'field_{{id}}_teachers[{{counter}}][lastname]',
            'type' => 'text',
            'id' => $this->controller.'field-{{id}}-teachers-{{counter}}-lastname',
            'maxlength' => 128,
            'class' => ['form-control'],
            // Use data-required instead of required to handle dynamic visibility:
            // - data-required="true" marks fields that should be required when section is visible
            // - The actual 'required' attribute is managed by JavaScript based on radio button state
            // - This prevents validation errors on hidden fields when radio button = "No"
            'data-required' => true,
            'value' => '{{valueLastname}}',
        ],
    ];

    if (!empty($params['help'])) {
        $params['attr']['aria-labelledby'] = 'help-'.$this->controller.'field-{{id}}-teachers-{{counter}}-lastname';
    }

    echo $this->helper->Html()->getFormField($params);

    echo '<div class="invalid-feedback"></div>'.PHP_EOL;

    if (!empty($params['help'])) {
        echo '<div'.$this->escapeAttr([
            'id' => 'help-'.$this->controller.'field-{{id}}-teachers-{{counter}}-lastname',
            'class' => ['form-text'],
        ]).'>'.nl2br(is_array($params['help']) ? implode(PHP_EOL, $params['help']) : $params['help']).'</div>'.PHP_EOL;
    }
    ?>
                    </div>
                </div>
                <div class="row">
                    <label class="col-lg-4 col-form-label text-lg-end"<?php echo $this->escapeAttr([
                        'for' => $this->controller.'field-{{id}}-teachers-{{counter}}-email',
                    ]); ?>>
                        <?php echo $this->escape()->html(__('Email')); ?> *
                    </label>
                    <div class="col-lg">
                        <?php
    $params = [
        'type' => 'input',
        'attr' => [
            'name' => $this->controller.'field_{{id}}_teachers[{{counter}}][email]',
            'type' => 'email',
            'id' => $this->controller.'field-{{id}}-teachers-{{counter}}-email',
            'maxlength' => 128,
            'class' => ['form-control'],
            // Use data-required instead of required to handle dynamic visibility:
            // - data-required="true" marks fields that should be required when section is visible
            // - The actual 'required' attribute is managed by JavaScript based on radio button state
            // - This prevents validation errors on hidden fields when radio button = "No"
            'data-required' => true,
            'value' => '{{valueEmail}}',
        ],
    ];

    if (!empty($params['help'])) {
        $params['attr']['aria-labelledby'] = 'help-'.$this->controller.'field-{{id}}-teachers-{{counter}}-email';
    }

    echo $this->helper->Html()->getFormField($params);

    echo '<div class="invalid-feedback"></div>'.PHP_EOL;

    if (!empty($params['help'])) {
        echo '<div'.$this->escapeAttr([
            'id' => 'help-'.$this->controller.'field-{{id}}-teachers-{{counter}}-email',
            'class' => ['form-text'],
        ]).'>'.nl2br(is_array($params['help']) ? implode(PHP_EOL, $params['help']) : $params['help']).'</div>'.PHP_EOL;
    }
    ?>
                    </div>
                </div>
                {{{status}}}
            </fieldset>
            <div class="d-flex justify-content-end">
                {{{button}}}
            </div>
        </div><?php
    ?></script>

    <script class="template-recommendation-status" type="text/x-handlebars-template">
        <div class="row mt-3">
            <label class="col-lg-4 col-form-label text-lg-end">
                <?php echo $this->escape()->html(__('Status')); ?>
            </label>
            <div class="col-lg">
                <div role="alert"{{{attr}}}>
                    <ul class="mb-0">
                        {{{content}}}
                    </ul>
                </div>
            </div>
        </div>
    </script>

    <script class="template-recommendation-button-add" type="text/x-handlebars-template">
        <button class="btn-add btn btn-secondary btn-sm" data-bs-toggle="tooltip" type="button"<?php echo $this->escapeAttr([
            'data-bs-title' => __('add'),
        ]); ?>>
            <i class="fas fa-plus fa-fw"></i>
        </button>
    </script>

    <script class="template-recommendation-button-delete" type="text/x-handlebars-template">
        <button class="btn-delete btn btn-secondary btn-sm" data-bs-toggle="tooltip" type="button"<?php echo $this->escapeAttr([
            'data-bs-title' => __('delete'),
        ]); ?>>
            <i class="fas fa-trash-alt fa-fw"></i>
        </button>
    </script>

    <?php // no spaces or PHP_EOL, see here https://stackoverflow.com/a/54170743/3929620?>
    <script class="template-li" type="text/x-handlebars-template">
        <li>
            {{{content}}}
        </li>
    </script>

    <?php
    // TODO - https://dev.to/chromiumdev/sure-you-want-to-leavebrowser-beforeunload-event-4eg5
    /*$this->scriptsFoot()->beginInternal();
    echo '(() => {
    const form'.ucfirst($this->action).'Element = document.getElementById("form-'.$this->action.'");
    if (form'.ucfirst($this->action).'Element) {
        let formChanged = false;
        form'.ucfirst($this->action).'Element.addEventListener("change", () => formChanged = true);
        window.addEventListener("beforeunload", (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = "'.$this->escape()->js(__('You have unsaved changes!')).'";
            }
        });
    }
})();';
    $this->scriptsFoot()->endInternal();*/
} else {
    echo '<p class="text-danger card-text">'.$this->escape()->html(__('No results found.')).'</p>'.PHP_EOL;
}
