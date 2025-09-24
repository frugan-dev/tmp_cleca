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

<div<?php echo $this->escapeAttr([
    'class' => ['response-multilang-'.$langId.'-'.$this->helper->Nette()->Strings()->webalize($key)],
]); ?>></div>

<?php
$type = $this->Mod->postData['type'] ?? $this->Mod->type;

$this->scriptsFoot()->beginInternal();
echo '(function(){
    const typeMultilang'.$langId.'Obj = {};
';

foreach ([
    _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/partial/type_multilang',
    _ROOT.'/app/view/'.$this->env.'/base/partial/type_multilang',
    _ROOT.'/app/view/default/controller/'.$this->controller.'/partial/type_multilang',
    _ROOT.'/app/view/default/base/partial/type_multilang',
] as $dir) {
    if (is_dir($dir)) {
        // in() searches only the current directory, while from() searches its subdirectories too (recursively)
        foreach ($this->helper->Nette()->Finder()->findFiles($this->action.'-*.php')->from($dir)->sortByName() as $fileObj) {
            $fileBasenamePart = ltrim((string) $fileObj->getBasename('.php'), $this->action.'-');
            $fileBasenameCamelCase = substr($fileBasenamePart, strpos((string) $fileBasenamePart, '-'));

            $this->container->get('filterValue')->sanitize($fileBasenameCamelCase, 'string', ['_', '-'], ' ');
            $this->container->get('filterValue')->sanitize($fileBasenameCamelCase, 'titlecase');
            $this->container->get('filterValue')->sanitize($fileBasenameCamelCase, 'string', ' ', '');

            \Safe\ob_start();

            include $fileObj->getPathname();
            $buffer = ob_get_contents();
            \Safe\ob_end_clean();

            echo '
        if(typeof typeMultilang'.$langId.'Obj.'.basename((string) $fileObj->getPath()).' === "undefined") {
            typeMultilang'.$langId.'Obj.'.basename((string) $fileObj->getPath()).' = {};
        }

        typeMultilang'.$langId.'Obj.'.basename((string) $fileObj->getPath()).'.'.$fileBasenameCamelCase.' = "'.$this->escape()->js($buffer).'";
';
        }

        break;
    }
}

echo '
    const responseMultilang'.$langId.ucfirst((string) $key).'El = document.querySelector(".response-multilang-'.$langId.'-'.$this->helper->Nette()->Strings()->webalize($key).'");
    const typeEl = document.getElementById("type");
    const requiredEl = document.getElementById("required");
    if(!!responseMultilang'.$langId.ucfirst((string) $key).'El && !!typeEl) {
        '.($type ? '
            if(typeof typeMultilang'.$langId.'Obj["'.$this->escape()->js($type).'"] !== "undefined") {
                //https://flexiple.com/javascript/loop-through-object-javascript/
                Object.values(typeMultilang'.$langId.'Obj["'.$this->escape()->js($type).'"]).forEach(val => {
                    responseMultilang'.$langId.ucfirst((string) $key).'El.innerHTML += val;
                });
            }

            if(!!requiredEl) {
                responseMultilang'.$langId.ucfirst((string) $key).'El.querySelectorAll("[data-label-required]").forEach(el => {
                    if(requiredEl.checked) {
                        if(!el.innerHTML.endsWith(" *")) {
                            el.innerHTML = el.innerHTML + " *";
                        }
                    } else {
                        if(el.innerHTML.endsWith(" *")) {
                            el.innerHTML = el.innerHTML.split(" ").slice(0, -1).join(" ");
                        }
                    }
                });
                responseMultilang'.$langId.ucfirst((string) $key).'El.querySelectorAll("[data-required]").forEach(el => {
                    if(requiredEl.checked) {
                        el.setAttribute("required", "");
                    } else {
                        el.removeAttribute("required");
                    }
                });
            }
        ' : '').'

        typeEl.addEventListener("change", function() {
            let option = typeEl.options[typeEl.selectedIndex];
            let value = typeEl.value;

            responseMultilang'.$langId.ucfirst((string) $key).'El.innerHTML = "";

            if(typeof typeMultilang'.$langId.'Obj[value] !== "undefined") {
                //https://flexiple.com/javascript/loop-through-object-javascript/
                Object.values(typeMultilang'.$langId.'Obj[value]).forEach(val => {
                    responseMultilang'.$langId.ucfirst((string) $key).'El.innerHTML += val;
                });
            }

            if(!!requiredEl) {
                responseMultilang'.$langId.ucfirst((string) $key).'El.querySelectorAll("[data-label-required]").forEach(el => {
                    if(requiredEl.checked) {
                        if(!el.innerHTML.endsWith(" *")) {
                            el.innerHTML = el.innerHTML + " *";
                        }
                    } else {
                        if(el.innerHTML.endsWith(" *")) {
                            el.innerHTML = el.innerHTML.split(" ").slice(0, -1).join(" ");
                        }
                    }
                });
                responseMultilang'.$langId.ucfirst((string) $key).'El.querySelectorAll("[data-required]").forEach(el => {
                    if(requiredEl.checked) {
                        el.setAttribute("required", "");
                    } else {
                        el.removeAttribute("required");
                    }
                });
            }
        });

        if(!!requiredEl) {
            requiredEl.addEventListener("change", function() {
                responseMultilang'.$langId.ucfirst((string) $key).'El.querySelectorAll("[data-label-required]").forEach(el => {
                    if(requiredEl.checked) {
                        if(!el.innerHTML.endsWith(" *")) {
                            el.innerHTML = el.innerHTML + " *";
                        }
                    } else {
                        if(el.innerHTML.endsWith(" *")) {
                            el.innerHTML = el.innerHTML.split(" ").slice(0, -1).join(" ");
                        }
                    }
                });
                responseMultilang'.$langId.ucfirst((string) $key).'El.querySelectorAll("[data-required]").forEach(el => {
                    if(requiredEl.checked) {
                        el.setAttribute("required", "");
                    } else {
                        el.removeAttribute("required");
                    }
                });
            });
        }
    }
})()';
$this->scriptsFoot()->endInternal();
