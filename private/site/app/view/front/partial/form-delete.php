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
<form id="form-delete" method="POST" role="form" action="">
    <input type="hidden" name="id" value="">
    <input type="hidden" name="formfield_id" value="">
    <input type="hidden" name="file_id" value="">
</form>

<?php // no spaces or PHP_EOL, see here https://stackoverflow.com/a/54170743/3929620?>
<script class="template-input-file-multiple-li" type="text/x-handlebars-template"><?php
?><li>
    {{name}} <i class="small">({{size}})</i>
    <a data-bs-toggle="modal" class="btn-danger text-danger" href="javascript:;" role="button"{{{attr}}}>
        <i class="fas fa-trash-alt ms-2"></i>
    </a>
</li><?php
?></script>

<?php
$this->scriptsFoot()->beginInternal();
echo '(() => {
    const formDeleteElement = document.getElementById("form-delete");
    if (formDeleteElement) {
        const inputIdElement = formDeleteElement.querySelector("input[name=\"id\"]");
        const inputFormfieldIdElement = formDeleteElement.querySelector("input[name=\"formfield_id\"]");
        const inputFileIdElement = formDeleteElement.querySelector("input[name=\"file_id\"]");

        const modalDeleteInstance = App.Mod.Modal.add(
            "'.$this->escape()->js(__('Do you confirm the operation?')).'",
            "'.$this->escape()->js(__('Warning')).'",
            "<button type=\"submit\" form=\"form-delete\" data-loading-text=\"<span class=\'spinner-border spinner-border-sm align-middle me-1\' role=\'status\' aria-hidden=\'true\'></span> '.$this->escapeAttr(__('Please wait')).'&hellip;\"'.addcslashes((string) $this->escapeAttr([
    'class' => array_merge($this->config['theme.'.$this->env.'.'.$this->action.'.btn.class'] ?? $this->config['theme.'.$this->env.'.btn.class'] ?? $this->config['theme.'.$this->action.'.btn.class'] ?? $this->config['theme.btn.class'] ?? [], ['btn-danger']),
]), '"').'>'.$this->escape()->js(__('Confirm')).' <i class=\"fas fa-triangle-exclamation ms-1\"></i></button>",
            "sm",
            "danger"
        );
        window.modalDeleteId = modalDeleteInstance.getId(); // <--
        const modalDeleteArr = modalDeleteInstance.getArr();

        //FIXME - double backdrop
        modalDeleteArr[window.modalDeleteId].modalEl.addEventListener("hidden.bs.modal", (event) => {
            document.body.removeAttribute("style");
        });

        const btnModalEventListener = (e) => {
            inputIdElement.value = e.currentTarget.getAttribute("data-id");
            inputFormfieldIdElement.value = e.currentTarget.getAttribute("data-formfield-id");
            inputFileIdElement.value = e.currentTarget.getAttribute("data-file-id");

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
        }

        window.btnModalListFunction = () => { // <--
            const btnModalList = Array.prototype.slice.call(
                document.querySelectorAll("[data-bs-toggle=\"modal\"]")
            );
            if (btnModalList) {
                btnModalList.map(function (btnModalElement) {
                    btnModalElement.setAttribute("data-bs-target", "#modal-" + window.modalDeleteId);

                    btnModalElement.removeEventListener("click", btnModalEventListener);
                    btnModalElement.addEventListener("click", btnModalEventListener);
                });
            }
        }
        window.btnModalListFunction();

        formDeleteElement.addEventListener("submit", (event) => {
            event.preventDefault();

            const formId = formDeleteElement.getAttribute("id");
            const buttonSubmitElement = document.querySelector("button[type=\"submit\"][form=\"" + formId + "\"]");
            if(buttonSubmitElement) {
                //https://stackoverflow.com/a/54533740
                buttonSubmitElement.disabled = true;

                var buttonSubmitInnerHTML = buttonSubmitElement.innerHTML;
                const buttonSubmitLoadingText = buttonSubmitElement.dataset.loadingText;
                if (buttonSubmitLoadingText) {
                    buttonSubmitElement.innerHTML = buttonSubmitLoadingText;
                    buttonSubmitElement.classList.add("btn-loading");
                }
            }

            const response = fetch("'.$this->uri([
    'routeName' => 'api.formvalue',
    'data' => [
        'action' => 'delete-file',
        'params' => implode('/', [
            'id',
            '" + inputIdElement.value + "',
            'file_id',
            '" + inputFileIdElement.value',
        ]),
    ],
]).', {
                // HTTP DELETE requests, like GET and HEAD requests, should not contain a body,
                // as this may cause some servers to work incorrectly.
                // But you can still send data to the server with an HTTP DELETE request using URL parameters.
                method: "DELETE",
                headers: {
                  "Content-Type": "application/json",
                  "Accept": "application/json",
                  //https://heera.it/detect-ajax-request-php-frameworks
                  //https://codeigniter.com/user_guide/general/ajax.html
                  "X-Requested-With": "XMLHttpRequest",
                },
            })
            .then(response => response.json())
            .then(data => {
                //console.log(data)

                if(!!data.response) {
                    if(buttonSubmitElement) {
                        //https://stackoverflow.com/a/54533740
                        buttonSubmitElement.disabled = false;

                        if (buttonSubmitInnerHTML) {
                            buttonSubmitElement.innerHTML = buttonSubmitInnerHTML;
                            buttonSubmitElement.classList.remove("btn-loading");
                        }
                    }

                    //https://stackoverflow.com/a/66545752/3929620
                    modalDeleteInstance.hide();

                    const inputFileElement = document.getElementById("'.(isset($inputFileId) ? $this->helper->Nette()->Strings()->webalize($inputFileId).'"' : 'formfield-" + inputFormfieldIdElement.value').');
                    const buttonTriggerElement = document.querySelector("[data-id=\"" + inputIdElement.value + "\"][data-file-id=\"" + inputFileIdElement.value + "\"]");
                    if(inputFileElement && buttonTriggerElement) {
                        const liElement = buttonTriggerElement.parentNode;
                        const olElement = liElement.parentNode;
                        const cardElement = olElement.closest(".card");

                        olElement.removeChild(liElement);

                        //https://stackoverflow.com/a/54170743/3929620
                        if(!olElement.hasChildNodes()) {
                            cardElement.classList.add("d-none");

                            if(inputFileElement.hasAttribute("data-required")) {
                                inputFileElement.setAttribute("required", "");
                            }
                        }
                    }

                    App.Mod.Toast.add(
                        "'.$this->escape()->js(sprintf(_n('%1$s successfully deleted.', '%1$s successfully deleted.', 1, 'male'), $this->helper->Nette()->Strings()->firstUpper(_n('file', 'files', 1)))).'",
                        "success"
                    ).show();

                    const event_ = new CustomEvent("form-delete-success", {
                        detail: {
                            response: data.response,
                            id: inputIdElement.value,
                            formfield_id: inputFormfieldIdElement.value,
                            file_id: inputFileIdElement.value,
                        },
                    });
                    document.dispatchEvent(event_);

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

                if(buttonSubmitElement) {
                    //https://stackoverflow.com/a/54533740
                    buttonSubmitElement.disabled = false;

                    if (buttonSubmitInnerHTML) {
                        buttonSubmitElement.innerHTML = buttonSubmitInnerHTML;
                        buttonSubmitElement.classList.remove("btn-loading");
                    }
                }

                //https://stackoverflow.com/a/66545752/3929620
                modalDeleteInstance.hide();

                App.Mod.Toast.add(error, "danger").show();

                const event_ = new CustomEvent("form-delete-error", {
                    detail: {
                        error: error,
                        id: inputIdElement.value,
                        formfield_id: inputFormfieldIdElement.value,
                        file_id: inputFileIdElement.value,
                    },
                });
                document.dispatchEvent(event_);
            });
        });
    }
})();';
$this->scriptsFoot()->endInternal();
