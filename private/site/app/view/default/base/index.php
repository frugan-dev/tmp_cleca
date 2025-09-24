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

$this->beginSection('content');

echo '<div class="table-responsive">'.PHP_EOL;

$this->addData(
    [
        'tableAttr' => ['class' => ['table', 'table-bordered', 'table-hover', 'table-sm', 'small', 'table-'.$this->controller]],
    ]
);

$this->dispatcher->dispatch(new GenericEvent(), 'event.'.$this->env.'.'.$this->controller.'.'.$this->action.'.tableAttr');

echo '<table'.$this->escapeAttr($this->tableAttr).'>'.PHP_EOL;

echo '<thead class="table-light">'.PHP_EOL;
echo '<tr>'.PHP_EOL;

foreach ($this->Mod->fieldsSortable as $key => $val) {
    if (($origKey = array_search($key, $this->Mod->replaceKeysMap, true)) === false) {
        $origKey = $key;
    }

    echo '<th scope="col" class="text-nowrap align-middle">'.PHP_EOL;

    if (!empty($this->Mod->hasFilters)) {
        // https://stackoverflow.com/a/43522746
        echo '<div class="row flex-nowrap gx-1">'.PHP_EOL;
        echo '<div class="col text-nowrap">';
    }

    echo '<a'.$this->escapeAttr([
        'href' => $this->uri([
            'routeName' => $this->env.'.'.$this->controller.'.params',
            'data' => [
                'action' => $this->action,
                'params' => implode(
                    '/',
                    array_merge(
                        [
                            $key, // orderBy
                            'desc' === $this->Mod->orderDir ? 'asc' : 'desc', // orderDir
                        ] + $this->Mod->routeParamsArrWithoutPg,
                        [$this->session->get('pg', $this->pager->pg)]
                    )
                ),
            ],
        ]),
    ]).'>'.PHP_EOL;

    echo $val[$this->env]['label']; // <-- no escape, it can contain html tags

    if ($this->Mod->orderBy === $key) {
        echo ' <i class="fas fa-sort-amount-'.array_search($this->Mod->orderDir, ['up' => 'asc', 'down' => 'desc'], true).' ms-1 text-muted"></i>';
    }

    echo '</a>'.PHP_EOL;

    if (!empty($this->Mod->hasFilters)) {
        echo '</div>'.PHP_EOL;

        echo '<div class="col-auto">';

        echo '<a data-bs-toggle="popover" data-bs-html="true" data-bs-sanitize="false" data-bs-placement="top" href="javascript:;">';
        echo '<i'.$this->escapeAttr([
            'class' => array_merge(['fas', 'fa-filter'], array_key_exists($origKey, $this->Mod->filterData) ? ['text-danger'] : []),
        ]).'></i>';
        echo '</a>'.PHP_EOL;

        // https://stackoverflow.com/a/8318442/3929620
        echo '<div class="d-none popover-content">'.PHP_EOL;

        echo '<div class="input-group input-group-sm">'.PHP_EOL;

        if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/filter/'.$this->action.'-'.$key.'.php')) {
            include _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/filter/'.$this->action.'-'.$key.'.php';
        } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/base/filter/'.$this->action.'-'.$key.'.php')) {
            include _ROOT.'/app/view/'.$this->env.'/base/filter/'.$this->action.'-'.$key.'.php';
        } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$this->controller.'/filter/'.$this->action.'-'.$key.'.php')) {
            include _ROOT.'/app/view/default/controller/'.$this->controller.'/filter/'.$this->action.'-'.$key.'.php';
        } elseif (file_exists(_ROOT.'/app/view/default/base/filter/'.$this->action.'-'.$key.'.php')) {
            include _ROOT.'/app/view/default/base/filter/'.$this->action.'-'.$key.'.php';
        } else {
            $params = [
                'type' => 'input',
                'attr' => [
                    'type' => 'text',
                    'name' => $origKey,
                    'class' => ['form-control'],
                    'form' => $this->controller.'-form-filter', // https://stackoverflow.com/a/21900324/3929620
                ],
            ];

            if (isset($this->Mod->filterData[$origKey]['value'])) {
                $params['value'] = $this->Mod->filterData[$origKey]['value'];
                $params['attr']['value'] = $this->Mod->filterData[$origKey]['value'];
            } else {
                $params['value'] = '';
                $params['attr']['value'] = '';
            }

            echo $this->helper->Html()->getFormField($params);
        }

        // https://stackoverflow.com/a/21900324/3929620
        echo '<button type="submit" class="btn btn-outline-secondary" data-loading-text="<i class=\'fas fa-spinner fa-spin fa-fw\'></i>"'.$this->escapeAttr([
            'form' => $this->controller.'-form-filter',
        ]).'>'.PHP_EOL;
        echo '<i class="fas fa-search fa-fw"></i>'.PHP_EOL;
        echo '</button>'.PHP_EOL;

        echo '</div>'.PHP_EOL;

        echo '</div>'.PHP_EOL;

        echo '</div>'.PHP_EOL;

        echo '</div>'.PHP_EOL;
    }

    echo '</th>'.PHP_EOL;
}

if ((is_countable($this->Mod->actions) ? count($this->Mod->actions) : 0) > 0) {
    echo '<th class="text-nowrap">'.PHP_EOL;

    if (!empty($this->bulkActions)) {
        // https://stackoverflow.com/a/21900324/3929620
        // no action="", so redirect from dashboard to module
        echo '<form data-sync novalidate method="POST" autocomplete="off" role="form"'.$this->escapeAttr([
            'id' => $this->controller.'-form-bulk',
            'action' => $this->uri($this->env.'.'.$this->controller),
        ]).'>'.PHP_EOL;

        echo '<div class="input-group input-group-sm flex-nowrap">'.PHP_EOL;

        $params = [
            'type' => 'select',
            'attr' => [
                'name' => 'action',
                'class' => ['form-select'],
            ],
        ];

        $params['options'] = [];

        $params['options'][''] = '- '.__('select').' -';

        foreach ($this->bulkActions as $item) {
            $params['options'][$item] = __($item);
        }

        if (isset($this->Mod->postData['action'])) {
            $params['value'] = $this->Mod->postData['action'];
        }

        echo $this->helper->Html()->getFormField($params);

        echo '<div class="input-group-text">'.PHP_EOL;

        echo $this->helper->Html()->getFormField([
            'type' => 'input',
            'attr' => [
                'type' => 'checkbox',
                'id' => 'bulk-trigger',
                'class' => ['form-check-input'],
            ],
        ]);

        echo '</div>'.PHP_EOL;

        if (empty($this->config['mod.'.$this->env.'.'.$this->controller.'.'.$this->action.'.maxBulk'] ?? $this->config['mod.'.$this->env.'.'.$this->controller.'.maxBulk'] ?? $this->config['mod.'.$this->env.'.maxBulk'] ?? $this->config['mod.maxBulk'] ?? $this->config['pagination.'.$this->env.'.rowPerPage'] ?? $this->config['pagination.rowPerPage'])) {
            echo '<a data-bs-toggle="modal" href="javascript:;" role="button" class="btn btn-outline-secondary" data-loading-text="<i class=\'fas fa-spinner fa-spin fa-fw\'></i>">'.PHP_EOL;
            echo '<i class="fas fa-chevron-right fa-fw"></i>'.PHP_EOL;
            echo '</a>'.PHP_EOL;

            $this->scriptsFoot()->beginInternal();
            echo '(() => {
    window.addEventListener("DOMContentLoaded", () => {
        const formBulkElement = document.getElementById("'.$this->escape()->js($this->controller).'-form-bulk");
        if (formBulkElement) {
            const btnModalElement = formBulkElement.querySelector("[data-bs-toggle=\"modal\"]");
            if (btnModalElement) {
                const modalBulkInstance = App.Mod.Modal.add(
                    "'.$this->escape()->js(__('Do you confirm the operation?')).'",
                    "'.$this->escape()->js(__('Warning')).'",
                    "<button type=\"submit\" form=\"'.$this->escape()->js($this->controller).'-form-bulk\" data-loading-text=\"<span class=\'spinner-border spinner-border-sm align-middle me-1\' role=\'status\' aria-hidden=\'true\'></span> '.$this->escapeAttr(__('Please wait')).'&hellip;\"'.addcslashes((string) $this->escapeAttr([
                'class' => array_merge($this->config['theme.'.$this->env.'.'.$this->action.'.btn.class'] ?? $this->config['theme.'.$this->env.'.btn.class'] ?? $this->config['theme.'.$this->action.'.btn.class'] ?? $this->config['theme.btn.class'] ?? [], ['btn-danger']),
            ]), '"').'>'.$this->escape()->js(__('Confirm')).' <i class=\"fas fa-triangle-exclamation ms-1\"></i></button>",
                    "sm",
                    "danger"
                );
                btnModalElement.setAttribute("data-bs-target", "#modal-" + modalBulkInstance.getId());

                btnModalElement.addEventListener("click", () => {
                    //FIXME - double backdrop
                    const modalBackdropList = Array.prototype.slice.call(
                        document.querySelectorAll(".modal-backdrop")
                    );
                    if (modalBackdropList.length > 1) {
                        for (const modalBackdropElement of modalBackdropList) {
                            modalBackdropElement.parentNode.removeChild(modalBackdropElement);
                            break;
                        }
                    }
                });
            }
        }
    });
})();';
            $this->scriptsFoot()->endInternal();
        } else {
            echo '<button type="submit" class="btn btn-outline-secondary" data-loading-text="<i class=\'fas fa-spinner fa-spin fa-fw\'></i>">'.PHP_EOL;
            echo '<i class="fas fa-chevron-right fa-fw"></i>'.PHP_EOL;
            echo '</button>'.PHP_EOL;
        }

        echo '</div>'.PHP_EOL;

        echo '</div>'.PHP_EOL;

        echo '</form>'.PHP_EOL;

        $this->scriptsFoot()->beginInternal();
        echo '(() => {
    window.addEventListener("DOMContentLoaded", () => {
        const bulkTriggerEl = document.getElementById("bulk-trigger");
        if(!!bulkTriggerEl) {
            bulkTriggerEl.addEventListener("click", () => {
                const bulkInputList = [].slice.call(document.querySelectorAll(\'input[name="bulk_ids[]"]\'));
                bulkInputList.map(function (bulkInputEl) {
                    bulkInputEl.checked = bulkTriggerEl.checked;
                });
            });
        }
    });
})();';
        $this->scriptsFoot()->endInternal();
    }

    echo '</th>'.PHP_EOL;
}

echo '</tr>'.PHP_EOL;
echo '</thead>'.PHP_EOL;

if (isset($this->result)) {
    echo '<tbody>'.PHP_EOL;

    foreach ($this->result as $row) {
        // Note that breaking words isn’t possible in Arabic, which is the most used RTL language.
        // Therefore .text-break is removed from our RTL compiled CSS.
        $trAttr = ['class' => ['text-break'], 'scope' => 'row'];

        if (!empty($row['id'])) {
            $trAttr['class'][] = 'tr-'.$this->helper->Nette()->Strings()->webalize($this->controller.'-'.$this->action.'-'.$row['id']);
        }

        $this->addData(
            [
                'trAttr' => $trAttr,
            ]
        );

        $this->dispatcher->dispatch(new GenericEvent(arguments: [
            'row' => $row,
        ]), 'event.'.$this->env.'.'.$this->controller.'.'.$this->action.'.row');

        echo '<tr'.$this->escapeAttr($this->trAttr).'>'.PHP_EOL;

        foreach ($this->Mod->fieldsSortable as $key => $val) {
            if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/partial/'.$this->action.'-'.$key.'.php')) {
                include _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/partial/'.$this->action.'-'.$key.'.php';
            } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-'.$key.'.php')) {
                include _ROOT.'/app/view/'.$this->env.'/base/partial/'.$this->action.'-'.$key.'.php';
            } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$this->controller.'/partial/'.$this->action.'-'.$key.'.php')) {
                include _ROOT.'/app/view/default/controller/'.$this->controller.'/partial/'.$this->action.'-'.$key.'.php';
            } elseif (file_exists(_ROOT.'/app/view/default/base/partial/'.$this->action.'-'.$key.'.php')) {
                include _ROOT.'/app/view/default/base/partial/'.$this->action.'-'.$key.'.php';
            } else {
                echo '<td>'.($row[$key] ?? '').'</td>'.PHP_EOL;
            }
        }

        if ((is_countable($this->Mod->actions) ? count($this->Mod->actions) : 0) > 0) {
            echo '<td>'.PHP_EOL;

            $this->beginSection('actions');

            foreach ($this->Mod->actions as $key => $val) {
                if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/action/'.$this->action.'-'.$val.'.php')) {
                    include _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/action/'.$this->action.'-'.$val.'.php';
                } elseif (file_exists(_ROOT.'/app/view/'.$this->env.'/base/action/'.$this->action.'-'.$val.'.php')) {
                    include _ROOT.'/app/view/'.$this->env.'/base/action/'.$this->action.'-'.$val.'.php';
                } elseif (file_exists(_ROOT.'/app/view/default/controller/'.$this->controller.'/action/'.$this->action.'-'.$val.'.php')) {
                    include _ROOT.'/app/view/default/controller/'.$this->controller.'/action/'.$this->action.'-'.$val.'.php';
                } elseif (file_exists(_ROOT.'/app/view/default/base/action/'.$this->action.'-'.$val.'.php')) {
                    include _ROOT.'/app/view/default/base/action/'.$this->action.'-'.$val.'.php';
                }
            }

            $this->endSection('actions');

            $actions = $this->getSection('actions');

            if (!empty($actions)) {
                echo '<div class="d-grid d-sm-flex align-items-sm-start justify-content-sm-end gap-2 position-relative">'.$actions.'</div>'.PHP_EOL;
            }

            echo '</td>'.PHP_EOL;
        }

        echo '</tr>'.PHP_EOL;
    }

    echo '</tbody>'.PHP_EOL;
}

echo '</table>'.PHP_EOL;
echo '</div>'.PHP_EOL;

if (!empty($this->Mod->hasFilters)) {
    // https://stackoverflow.com/a/21900324/3929620
    // <-- no action="", so redirect from dashboard to module
    echo '<form data-sync novalidate method="POST" autocomplete="off" role="form"'.$this->escapeAttr([
        'id' => $this->controller.'-form-filter',
        'action' => $this->uri([
            'routeName' => $this->env.'.'.$this->controller.'.params',
            'data' => [
                'action' => $this->action,
                'params' => implode(
                    '/',
                    array_merge(
                        [
                            $this->Mod->orderBy,
                            $this->Mod->orderDir,
                        ] + $this->Mod->routeParamsArrWithoutPg,
                        [$this->session->get('pg', $this->pager->pg)]
                    )
                ),
            ],
        ]),
    ]).'>'.PHP_EOL;
    echo '<input type="hidden" name="action" value="_filter">'.PHP_EOL;
    echo '</form>'.PHP_EOL;
}

if (isset($this->result)) {
    echo '<p class="card-text small text-muted text-end">'.$this->escape()->html(sprintf(__('Total: %1$d'), $this->pager->totRows)).'</p>'.PHP_EOL;

    if ((is_countable($this->pagination) ? count($this->pagination) : 0) > 0) {
        echo $this->render('pagination');
    }

    if ($this->rbac->isGranted($this->controller.'.api.edit')) { // <--
        // https://developer.mozilla.org/en-US/docs/Web/API/EventTarget/addEventListener
        // Please note that while anonymous and arrow functions are similar, they have different `this` bindings.
        // While anonymous (and all traditional JavaScript functions) create their own `this` bindings,
        // arrow functions inherit the `this` binding of the containing function.
        // That means that the variables and constants available to the containing function
        // are also available to the event handler when using an arrow function.
        $this->scriptsFoot()->beginInternal();
        echo '(() => {
    const editToggleTriggerList = [].slice.call(document.querySelectorAll(".table-'.$this->controller.' .edit-toggle"));
    editToggleTriggerList.map(function (editToggleTriggerEl) {
        editToggleTriggerEl.addEventListener("click", (event) => {
            //https://stackoverflow.com/a/70619441/3929620
            //https://stackoverflow.com/a/37949156/3929620
            //https://dev.to/sprite421/css-class-manipulation-with-vanilla-javascript-4nii
            //https://bobbyhadz.com/blog/javascript-remove-all-classes-from-element
            const iconSuccess = editToggleTriggerEl.querySelector(".text-success");
            const iconDanger = editToggleTriggerEl.querySelector(".text-danger");
            const iconSpinner = editToggleTriggerEl.querySelector(".text-muted");
            if(!!iconSuccess && !!iconDanger && !!iconSpinner) {
                const iconVisible = window.getComputedStyle(iconSuccess).display === "inline-block" ? iconSuccess : iconDanger;

                iconSuccess.style.display = "none";
                iconDanger.style.display = "none";
                iconSpinner.style.display = "inline-block";

                const response = fetch("'.$this->escape()->js($this->uri([
            'routeName' => 'api.'.$this->controller,
            'data' => [
                'action' => 'toggle',
            ],
        ])).'", {
                    method: "POST",
                    headers: {
                      "Content-Type": "application/json",
                      "Accept": "application/json",
                      //https://heera.it/detect-ajax-request-php-frameworks
                      //https://codeigniter.com/user_guide/general/ajax.html
                      "X-Requested-With": "XMLHttpRequest",
                    },
                    body: JSON.stringify({
                        field: editToggleTriggerEl.getAttribute("data-field"),
                        id: editToggleTriggerEl.getAttribute("data-id"),
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    //console.log(data)

                    if(!!data.response) {
                        if(!!data.checked) {
                            iconSuccess.style.display = "inline-block";
                            iconDanger.style.display = "none";
                            iconSpinner.style.display = "none";
                        } else {
                            iconSuccess.style.display = "none";
                            iconDanger.style.display = "inline-block";
                            iconSpinner.style.display = "none";
                        }

                        return;
                    }

                    let error = "'.$this->escape()->js(__('A technical problem has occurred, try again later.')).'";
                    if(data.errors[0] !== "undefined") {
                        error = data.errors[0];
                    }

                    throw new Error(error);
                })
                .catch(error => {
                    //console.error(error)

                    if(iconVisible === iconSuccess) {
                        iconDanger.style.display = "none";
                    } else {
                        iconSuccess.style.display = "none";
                    }

                    iconSpinner.style.display = "none";
                    iconVisible.style.display = "inline-block";

                    App.Mod.Toast.add(error, "danger").show();
                });
            }
        });
    });
})();';
        $this->scriptsFoot()->endInternal();
    }
} else {
    echo '<p class="text-danger card-text">'.$this->escape()->html(__('No results found.')).'</p>'.PHP_EOL;
}

$this->endSection();

$this->beginSection('back-button');
$this->endSection();
