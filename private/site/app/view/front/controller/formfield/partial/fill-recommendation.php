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

$options = [
    1 => __('Yes'),
    0 => __('No'),
];
$lastKey = array_key_last($options);
foreach ($options as $k => $v) {
    echo '<div class="form-check">'.PHP_EOL;

    $value = $k;
    $label = $v;

    $params = [
        'type' => 'input',
        'attr' => [
            'name' => $this->controller.'field_'.$row['id'],
            'type' => 'radio',
            'id' => $this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id'].'-'.$value),
            'class' => ['form-check-input'],
            'required' => (bool) $row['required'],
            'value' => $value,
        ],
    ];

    if (isset($this->Mod->postData[$this->controller.'field_'.$row['id']])) {
        $params['value'] = $this->Mod->postData[$this->controller.'field_'.$row['id']];
    } else {
        $params['value'] = isset($row[$this->controller.'value_data']) ? (is_array($row[$this->controller.'value_data']) ? $row[$this->controller.'value_data'][0] : $row[$this->controller.'value_data']) : null; // <--
    }

    if (!empty($row['richtext'])) {
        $params['attr']['aria-labelledby'] = 'help-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']);
    }

    echo $this->helper->Html()->getFormField($params);

    echo '<label class="form-check-label"'.$this->escapeAttr([
        'for' => $this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id'].'-'.$value),
    ]).'>'.PHP_EOL;
    echo $this->escape()->html($label);
    echo '</label>'.PHP_EOL;

    if ($k === $lastKey) {
        echo '<div class="invalid-feedback"></div>'.PHP_EOL;
    }

    echo '</div>'.PHP_EOL;
}

if (!empty($row['richtext'])) {
    echo '<div'.$this->escapeAttr([
        'id' => 'help-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']),
        'class' => ['form-text'],
    ]).'>'.$row['richtext'].'</div>'.PHP_EOL;
}

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;

echo '<div'.$this->escapeAttr([
    'class' => array_merge(['row', 'row-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-response-'.$row['id']), 'row-response-'.$this->helper->Nette()->Strings()->webalize($row['type']), 'mb-3'], !empty($params['value']) ? [] : ['d-none']),
]).'>'.PHP_EOL;
echo '<div'.$this->escapeAttr([
    'class' => array_merge($this->config['theme.'.$this->env.'.'.$this->action.'.value.offset.class'] ?? $this->config['theme.'.$this->env.'.value.offset.class'] ?? $this->config['theme.'.$this->action.'.value.offset.class'] ?? $this->config['theme.value.offset.class'] ?? [], ['response-child']),
    // https://stackoverflow.com/a/54644168/3929620
    // 'key' => 'response-child-'.$row['id'],
]).'>'.PHP_EOL;

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;

$this->scriptsFoot()->beginInternal();
echo '(() => {
    const response'.$row['id'].'Element = document.querySelector(".row-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-response-'.$row['id']).'");
    const templateWrapperEl = document.querySelector(".template-'.$this->helper->Nette()->Strings()->webalize($row['type']).'-wrapper");
    const templateStatusEl = document.querySelector(".template-'.$this->helper->Nette()->Strings()->webalize($row['type']).'-status");
    const templateButtonAddEl = document.querySelector(".template-'.$this->helper->Nette()->Strings()->webalize($row['type']).'-button-add");
    const templateButtonDeleteEl = document.querySelector(".template-'.$this->helper->Nette()->Strings()->webalize($row['type']).'-button-delete");
    const templateLiEl = document.querySelector(".template-li");
    if(response'.$row['id'].'Element && templateWrapperEl && templateStatusEl && templateButtonAddEl && templateButtonDeleteEl && templateLiEl) {
        let counter = 0, totFieldset = 0;
        let html = "";

        const templateButtonAdd = Handlebars.compile(templateButtonAddEl.innerHTML);
        const buttonAdd = templateButtonAdd();

        const templateButtonDelete = Handlebars.compile(templateButtonDeleteEl.innerHTML);
        const buttonDelete = templateButtonDelete();

        const templateWrapper = Handlebars.compile(templateWrapperEl.innerHTML);

        const response'.$row['id'].'ChildElement = response'.$row['id'].'Element.querySelector(".response-child");

        const btnDeleteEventListener = (e) => {
            const wrapperElement = e.currentTarget.closest(".'.$this->helper->Nette()->Strings()->webalize($row['type']).'-wrapper");
            if(wrapperElement) {
                const tooltip = Tooltip.getInstance(e.currentTarget);
                if(tooltip) {
                    tooltip.hide();
                }

                //https://bobbyhadz.com/blog/javascript-failed-to-execute-remove-child-on-node
                //response'.$row['id'].'ChildElement.removeChild(wrapperElement);
                //wrapperElement.parentNode.removeChild(wrapperElement);
                wrapperElement.remove();

                --totFieldset;
            }
        }

        const btnDeleteListFunction = () => {
            const btnDeleteList = Array.prototype.slice.call(
                response'.$row['id'].'ChildElement.querySelectorAll(".btn-delete")
            );
            if (btnDeleteList) {
                btnDeleteList.map(function (btnDeleteElement) {
                    btnDeleteElement.removeEventListener("click", btnDeleteEventListener);
                    btnDeleteElement.addEventListener("click", btnDeleteEventListener);
                });
            }
        }
        ';

if (!empty($this->Mod->postData[$this->controller.'field_'.$row['id'].'_teachers'] ?? $row[$this->controller.'value_data']['teachers'] ?? null)) {
    echo '
    let templateStatusPending, templateStatusDeclined, templateStatusAccepted;

    const templateLi = Handlebars.compile(templateLiEl.innerHTML);
    const templateStatus = Handlebars.compile(templateStatusEl.innerHTML);
    ';

    foreach ($this->Mod->postData[$this->controller.'field_'.$row['id'].'_teachers'] ?? $row[$this->controller.'value_data']['teachers'] as $k => $v) {
        $mdateObj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $v['mdate'] ?? $this->helper->Carbon()->now($this->config['db.1.timeZone'])->toDateTimeString(), $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());
        $ldateObj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $v['ldate'] ?? $this->helper->Carbon()->now($this->config['db.1.timeZone'])->toDateTimeString(), $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

        echo '
        templateStatusPending = templateStatus({
            content: templateLi({
                content: "'.$this->escape()->js(sprintf(__('Last email sent to teacher at %1$s'), '<b>'.$ldateObj->toDateTimeString().'</b> <small class=\"text-muted\">('.$ldateObj->timezone.')</small>')).'",
            }) + templateLi({
                content: "'.$this->escape()->js(__('Waiting teacher\'s feedback')).'",
            }),
            attr: App.Helper.escapeHTMLAttribute({
                class: ["alert", "alert-warning", "fade", "show", "small"],
            }),
        });
        templateStatusDeclined = templateStatus({
            content: templateLi({
                content: "'.$this->escape()->js(sprintf(__('The teacher declined the request at %1$s'), '<b>'.$mdateObj->toDateTimeString().'</b> <small class=\"text-muted\">('.$mdateObj->timezone.')</small>')).'",
            }) + templateLi({
                content: "'.$this->escape()->js(__('Now you can complete all forms')).'",
            }),
            attr: App.Helper.escapeHTMLAttribute({
                class: ["alert", "alert-danger", "fade", "show", "small"],
            }),
        });
        templateStatusUploaded = templateStatus({
            content: templateLi({
                content: "'.$this->escape()->js(sprintf(__('The teacher has uploaded the required documents at %1$s'), '<b>'.$mdateObj->toDateTimeString().'</b> <small class=\"text-muted\">('.$mdateObj->timezone.')</small>')).'",
            }) + templateLi({
                content: "'.$this->escape()->js(__('The teacher has not yet confirmed the operation')).'",
            }) + templateLi({
                content: "'.$this->escape()->js(sprintf(__('Last email sent to teacher at %1$s'), '<b>'.$ldateObj->toDateTimeString().'</b> <small class=\"text-muted\">('.$ldateObj->timezone.')</small>')).'",
            }),
            attr: App.Helper.escapeHTMLAttribute({
                class: ["alert", "alert-info", "fade", "show", "small"],
            }),
        });
        templateStatusAccepted = templateStatus({
            content: templateLi({
                content: "'.$this->escape()->js(sprintf(__('The teacher has uploaded the required documents at %1$s'), '<b>'.$mdateObj->toDateTimeString().'</b> <small class=\"text-muted\">('.$mdateObj->timezone.')</small>')).'",
            }),
            attr: App.Helper.escapeHTMLAttribute({
                class: ["alert", "alert-success", "fade", "show", "small"],
            }),
        });

        html = templateWrapper({
            id: '.$row['id'].',
            counter: ++counter,
            title: "'.$this->escape()->js(__('Teacher\'s data')).'",
            valueFirstname: "'.$this->escape()->js($v['firstname']).'",
            valueLastname: "'.$this->escape()->js($v['lastname']).'",
            valueEmail: "'.$this->escape()->js($v['email']).'",
            status: '.(empty($this->Mod->postData) ? (!empty($v['status']) && !empty($v['files']) ? 'templateStatusAccepted' : (empty($v['status']) && !empty($v['files']) ? 'templateStatusUploaded' : 'templateStatusPending')) : '""').',
            button: '.(($row['option']['max_teachers'] ?? 1) > 1 ? 'counter === 1 ? buttonAdd : buttonDelete' : '""').',
        });

        response'.$row['id'].'ChildElement.insertAdjacentHTML("beforeend", html);

        ++totFieldset;
        ';
    }

    if (count($this->Mod->postData[$this->controller.'field_'.$row['id'].'_teachers'] ?? $row[$this->controller.'value_data']['teachers']) > 1) {
        echo '
        btnDeleteListFunction();
    ';
    }
} else {
    echo '
        html = templateWrapper({
            id: '.$row['id'].',
            counter: ++counter,
            title: "'.$this->escape()->js(__('Teacher\'s data')).'",
            valueFirstname: "",
            valueLastname: "",
            valueEmail: "",
            status: "",
            button: '.(($row['option']['max_teachers'] ?? 1) > 1 ? 'buttonAdd' : '""').',
        });

        response'.$row['id'].'ChildElement.insertAdjacentHTML("beforeend", html);

        ++totFieldset;
        ';
}
echo '

        const btnAddElement = response'.$row['id'].'ChildElement.querySelector(".btn-add");
        if (btnAddElement) {
            btnAddElement.addEventListener("click", () => {
                if(totFieldset < '.($row['option']['max_teachers'] ?? 1).') {
                    html = templateWrapper({
                        id: '.$row['id'].',
                        counter: ++counter,
                        title: "'.$this->escape()->js(__('Teacher\'s data')).'",
                        valueFirstname: "",
                        valueLastname: "",
                        valueEmail: "",
                        status: "",
                        button: buttonDelete,
                    });

                    response'.$row['id'].'ChildElement.insertAdjacentHTML("beforeend", html);

                    // Get only the new fieldset elements to avoid duplicate listeners
                    const newFieldset = response'.$row['id'].'ChildElement.lastElementChild;
                    for (const element of newFieldset.querySelectorAll("[data-required]")) {
                        element.setAttribute("required", "");
                    }

                    ++totFieldset;

                    //https://stackoverflow.com/a/6976583/3929620
                    window.focus();
                    if (document.activeElement) {
                        document.activeElement.blur();
                    }

                    btnDeleteListFunction();
                    App.Sys.formValidation();
                    App.Sys.tooltip();
                }
            });
        }

        for (const inputRadio'.$row['id'].'Element of document.querySelectorAll(
          "input[name=\"'.$this->controller.'field_'.$row['id'].'\"]"
        )) {
            inputRadio'.$row['id'].'Element.addEventListener("change", () => {
                if(Number.parseInt(inputRadio'.$row['id'].'Element.value) === 1) {
                    for (const element of response'.$row['id'].'ChildElement.querySelectorAll("[data-required]")) {
                        element.setAttribute("required", "");
                    }

                    response'.$row['id'].'Element.classList.remove("d-none");
                } else {
                    for (const element of response'.$row['id'].'ChildElement.querySelectorAll("[data-required]")) {
                        element.removeAttribute("required");
                    }

                    response'.$row['id'].'Element.classList.add("d-none");
                }
            });
        }

        // Initialize required attributes on page load for existing fields
        const checkedRadio'.$row['id'].' = document.querySelector(
            "input[name=\"'.$this->controller.'field_'.$row['id'].'\"]:checked"
        );
        if (checkedRadio'.$row['id'].' && Number.parseInt(checkedRadio'.$row['id'].'.value) === 1) {
            for (const element of response'.$row['id'].'ChildElement.querySelectorAll("[data-required]")) {
                element.setAttribute("required", "");
            }
        }
    }
})();';
$this->scriptsFoot()->endInternal();
