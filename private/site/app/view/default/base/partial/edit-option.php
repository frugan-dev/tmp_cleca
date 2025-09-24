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
    'class' => ['response-'.$this->helper->Nette()->Strings()->webalize($key)],
]); ?>></div>

<?php
$type = $this->Mod->postData['type'] ?? $this->Mod->type;

$this->scriptsFoot()->beginInternal(
    101 // default: 100
);
echo '(function(){
    const typeObj = {};
';

foreach ([
    _ROOT.'/app/view/'.$this->env.'/controller/'.$this->controller.'/partial/type',
    _ROOT.'/app/view/'.$this->env.'/base/partial/type',
    _ROOT.'/app/view/default/controller/'.$this->controller.'/partial/type',
    _ROOT.'/app/view/default/base/partial/type',
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
        if(typeof typeObj.'.basename((string) $fileObj->getPath()).' === "undefined") {
            typeObj.'.basename((string) $fileObj->getPath()).' = {};
        }

        typeObj.'.basename((string) $fileObj->getPath()).'.'.$fileBasenameCamelCase.' = "'.$this->escape()->js($buffer).'";
';
        }

        break;
    }
}

echo '
    const response'.ucfirst((string) $key).'El = document.querySelector(".response-'.$this->helper->Nette()->Strings()->webalize($key).'");
    const typeEl = document.getElementById("type");
    const requiredEl = document.getElementById("required");
    const navTabsEl = document.querySelector(".nav-tabs");
    const tabContentEl = document.querySelector(".tab-content");
    if(!!response'.ucfirst((string) $key).'El && !!typeEl) {
        '.($type ? '
            if(typeof typeObj["'.$this->escape()->js($type).'"] !== "undefined") {
                //https://flexiple.com/javascript/loop-through-object-javascript/
                Object.values(typeObj["'.$this->escape()->js($type).'"]).forEach(val => {
                    response'.ucfirst((string) $key).'El.innerHTML += val;
                });
            }

            if(!!requiredEl) {
                response'.ucfirst((string) $key).'El.querySelectorAll("[data-label-required]").forEach(el => {
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
                response'.ucfirst((string) $key).'El.querySelectorAll("[data-required]").forEach(el => {
                    if(requiredEl.checked) {
                        el.setAttribute("required", "");
                    } else {
                        el.removeAttribute("required");
                    }
                });
            }

            '.(!str_starts_with((string) $type, 'multilang_') ? '
                if(!!navTabsEl && !!tabContentEl) {
                    navTabsEl.classList.add("d-none");
                    tabContentEl.classList.add("d-none");
                }
            ' : '').'
        ' : '').'

        typeEl.addEventListener("change", function() {
            let option = typeEl.options[typeEl.selectedIndex];
            let value = typeEl.value;

            response'.ucfirst((string) $key).'El.innerHTML = "";

            if(!!navTabsEl && !!tabContentEl) {
                if(!value.startsWith("multilang_")) {
                    navTabsEl.classList.add("d-none");
                    tabContentEl.classList.add("d-none");
                } else {
                    navTabsEl.classList.remove("d-none");
                    tabContentEl.classList.remove("d-none");
                }
            }

            if(typeof typeObj[value] !== "undefined") {
                //https://flexiple.com/javascript/loop-through-object-javascript/
                Object.values(typeObj[value]).forEach(val => {
                    response'.ucfirst((string) $key).'El.innerHTML += val;
                });
            }

            if(!!requiredEl) {
                response'.ucfirst((string) $key).'El.querySelectorAll("[data-label-required]").forEach(el => {
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
                response'.ucfirst((string) $key).'El.querySelectorAll("[data-required]").forEach(el => {
                    if(requiredEl.checked) {
                        el.setAttribute("required", "");
                    } else {
                        el.removeAttribute("required");
                    }
                });
            }

            if(value.includes("_richedit_")) {
                if( typeof App !== "undefined" ) {
                    App.Sys.tinyMce();
                }
            }
        });

        if(!!requiredEl) {
            requiredEl.addEventListener("change", function() {
                response'.ucfirst((string) $key).'El.querySelectorAll("[data-label-required]").forEach(el => {
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
                response'.ucfirst((string) $key).'El.querySelectorAll("[data-required]").forEach(el => {
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
