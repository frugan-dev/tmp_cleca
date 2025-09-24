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
use Symfony\Component\EventDispatcher\GenericEvent;

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

$Mod = $this->container->get('Mod\Formfield\\'.ucfirst((string) $this->env));

if (isset($this->Mod->postData[$key])) {
    $params['value'] = $this->Mod->postData[$key];
} else {
    $params['value'] = $this->Mod->{$key};
}

if (isset($this->Mod->postData['form_id'])) {
    $formId = $this->Mod->postData['form_id'];
} else {
    $formId = $this->Mod->form_id;
}

if (!empty($formId)) {
    $params['options'] = [];

    $params['options'][''] = '- '.__('select').' -';

    $eventName = 'event.'.$this->env.'.'.$Mod->modName.'.getAll.where';
    $callback = function (GenericEvent $event) use ($Mod, $formId): void {
        $Mod->dbData['sql'] .= ' AND a.form_id = :form_id';
        $Mod->dbData['args']['form_id'] = $formId;
    };

    $this->dispatcher->addListener($eventName, $callback);

    $result = $Mod->getAll([
        'order' => (!empty($Mod->fields['hierarchy']) ? 'a.hierarchy ASC, ' : '').(!empty($Mod->fields['name']['multilang']) ? 'b' : 'a').'.name ASC',
    ]);

    $this->dispatcher->removeListener($eventName, $callback);

    if ((is_countable($result) ? count($result) : 0) > 0) {
        foreach ($result as $row) {
            if (method_exists($Mod, 'getFieldTypes') && is_callable([$Mod, 'getFieldTypes'])) {
                $type = $Mod->getFieldTypes()[$row['type']] ?? $row['type'];
            } else {
                $type = $row['type'];
            }

            $nameRichtext = $this->helper->Nette()->Strings()->truncate(trim(strip_tags((string) ($row['name'] ?? '').' '.($row['richtext'] ?? ''))), 30);

            $params['options'][$row['id']] = $this->escape()->html($this->helper->Nette()->Strings()->truncate($row['id'].' - '.(!empty($nameRichtext) ? $nameRichtext : '').' ('.$type.')', 50));
        }
    }
} else {
    $params['attr']['disabled'] = true;
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

echo '<div class="col-lg-auto ps-0 align-self-center d-none d-lg-block">'.PHP_EOL;
echo '<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>'.PHP_EOL;
echo '</div>'.PHP_EOL;

echo '</div>'.PHP_EOL;

$this->scriptsFoot()->beginInternal();
echo '(function() {
    const catformIdEl = document.getElementById("catform_id");
    const formIdEl = document.getElementById("form_id");
    const '.$Mod->modName.'IdEl = document.getElementById("'.$Mod->modName.'_id");

    if(!!catformIdEl && !!formIdEl && !!'.$Mod->modName.'IdEl) {
        const '.$Mod->modName.'SpinnerEl = App.Helper.getNextSibling('.$Mod->modName.'IdEl.parentNode, "div").querySelector(".spinner-border");

        catformIdEl.addEventListener("change", function(e) {
            '.$Mod->modName.'IdEl.innerHTML = "";
            '.$Mod->modName.'IdEl.classList.remove("is-invalid", "is-valid", "disabled");
            '.$Mod->modName.'IdEl.disabled = true;
        });

        formIdEl.addEventListener("change", function(e) {
            formId = this.value;

            '.$Mod->modName.'IdEl.innerHTML = "";
            '.$Mod->modName.'IdEl.classList.remove("is-invalid", "is-valid", "disabled");
            '.$Mod->modName.'IdEl.disabled = true;

            if(!!formId) {
                '.$Mod->modName.'IdEl.innerHTML = "<option value=\"\">- '.$this->escape()->js(__('select')).' -</option>";

                if(!!'.$Mod->modName.'SpinnerEl) {
                    '.$Mod->modName.'SpinnerEl.classList.remove("d-none");
                }

                const response = fetch("'.$this->uri([
    'routeName' => 'api.'.$Mod->modName,
    'data' => [
        'action' => 'index-full-by-field-id',
        'params' => implode('/', [
            'hierarchy',
            'asc',
            'field_name',
            'form_id',
            'field_id',
            '" + formId',
        ]),
    ],
]).', {
                    method: "GET",
                    headers: {
                      "Content-Type": "application/json",
                      //https://heera.it/detect-ajax-request-php-frameworks
                      //https://codeigniter.com/user_guide/general/ajax.html
                      "X-Requested-With": "XMLHttpRequest"
                    }
                })
                .then(response => response.json())
                .then(data => {
                    //console.log(data);

                    for (var i = 0; i < data.response.length; i++) {
                        //https://stackoverflow.com/a/22260849/3929620
                        '.$Mod->modName.'IdEl.insertAdjacentHTML("beforeend", "<option value=\"" + data.response[i].id + "\">" + data.response[i].id + " - " + (!!data.response[i]._nameRichtextTruncated ? data.response[i]._nameRichtextTruncated : "") + " (" + data.response[i]._typeTranslated + ")</option>");
                    }

                    '.$Mod->modName.'IdEl.removeAttribute("disabled");

                    if(!!'.$Mod->modName.'SpinnerEl) {
                        '.$Mod->modName.'SpinnerEl.classList.add("d-none");
                    }
                })
                .catch((error) => console.error(error));
            }
        });
    }
})()';
$this->scriptsFoot()->endInternal();
