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

$Mod = $this->container->get('Mod\Member\\'.ucfirst((string) $this->env));

if (isset($this->Mod->postData[$key])) {
    $params['value'] = $this->Mod->postData[$key];
} else {
    $params['value'] = $this->Mod->{$key};
}

if (isset($this->Mod->postData['cat'.$Mod->modName.'_id'])) {
    ${'cat'.$Mod->modName.'Id'} = $this->Mod->postData['cat'.$Mod->modName.'_id'];
} else {
    ${'cat'.$Mod->modName.'Id'} = $this->Mod->{'cat'.$Mod->modName.'_id'};
}

if (!empty(${'cat'.$Mod->modName.'Id'})) {
    $params['options'] = [];

    $params['options'][''] = '- '.__('select').' -';

    $catId = ${'cat'.$Mod->modName.'Id'};
    $eventName = 'event.'.$this->env.'.'.$Mod->modName.'.getAll.where';
    $callback = function (GenericEvent $event) use ($Mod, $catId): void {
        $Mod->dbData['sql'] .= ' AND a.cat'.$Mod->modName.'_id = :cat'.$Mod->modName.'_id';
        $Mod->dbData['args']['cat'.$Mod->modName.'_id'] = $catId;
    };

    $this->dispatcher->addListener($eventName, $callback);

    $result = $Mod->getAll([
        'order' => 'a.lastname ASC, a.firstname ASC',
    ]);

    $this->dispatcher->removeListener($eventName, $callback);

    if ((is_countable($result) ? count($result) : 0) > 0) {
        foreach ($result as $row) {
            $params['options'][$row['id']] = $this->escape()->html($row['id'].' - '.$row['lastname'].' '.$row['firstname'].' ('.$row['email'].')');
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
    const cat'.$Mod->modName.'IdEl = document.getElementById("cat'.$Mod->modName.'_id");
    const '.$Mod->modName.'IdEl = document.getElementById("'.$Mod->modName.'_id");

    if(!!cat'.$Mod->modName.'IdEl && !!'.$Mod->modName.'IdEl) {
        const '.$Mod->modName.'SpinnerEl = App.Helper.getNextSibling('.$Mod->modName.'IdEl.parentNode, "div").querySelector(".spinner-border");

        cat'.$Mod->modName.'IdEl.addEventListener("change", function(e) {
            cat'.$Mod->modName.'Id = this.value;

            '.$Mod->modName.'IdEl.innerHTML = "";
            '.$Mod->modName.'IdEl.classList.remove("is-invalid", "is-valid", "disabled");
            '.$Mod->modName.'IdEl.disabled = true;

            if(!!cat'.$Mod->modName.'Id) {
                '.$Mod->modName.'IdEl.innerHTML = "<option value=\"\">- '.$this->escape()->js(__('select')).' -</option>";

                if(!!'.$Mod->modName.'SpinnerEl) {
                    '.$Mod->modName.'SpinnerEl.classList.remove("d-none");
                }

                const response = fetch("'.$this->uri([
    'routeName' => 'api.'.$Mod->modName,
    'data' => [
        'action' => 'index-full-by-field-id',
        'params' => implode('/', [
            'lastname',
            'asc',
            'field_name',
            'cat'.$Mod->modName.'_id',
            'field_id',
            '" + cat'.$Mod->modName.'Id',
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
                        '.$Mod->modName.'IdEl.insertAdjacentHTML("beforeend", "<option value=\"" + data.response[i].id + "\">" + data.response[i].id + " - " + data.response[i].lastname + " " + data.response[i].firstname + " (" + data.response[i].email + ")</option>");
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
