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

if (!empty($this->config['api.'.$this->env.'.sse.enabled'] ?? $this->config['api.sse.enabled'] ?? false)) {
    $this->scriptsFoot()->beginInternal();
    echo '(() => {
    window.addEventListener("DOMContentLoaded", () => {
        window.sse = new EventSource("'.$this->escape()->js($this->uri([
        'routeName' => 'api',
        'data' => [
            'action' => 'sse',
        ],
        'queryParams' => [
            'url' => $this->helper->Url()->getPathUrl(),
        ],
    ])).'");

        window.toastReloadInstance = App.Mod.Toast.add("'.$this->escape()->js(sprintf(__('Some page elements have changed, please %1$s.'), '<a class="alert-link" onclick="window.location.reload()" href="javascript:;">'.__('reload the page').'</a>')).'", "info", {
            autohide: false,
        });
        window.toastReloadId = toastReloadInstance.getId();
        window.toastReloadObj = toastReloadInstance.getObj();

        window.toastErrorInstance = App.Mod.Toast.add("'.$this->escape()->js(sprintf(__('Data retrieval error, please %1$s.'), '<a class="alert-link" onclick="window.location.reload()" href="javascript:;">'.__('reload the page').'</a>')).'", "warning", {
            autohide: false,
        });
        window.toastErrorId = toastErrorInstance.getId();
        window.toastErrorObj = toastErrorInstance.getObj();
        window.toastErrorTimeoutId = App.Helper.wait.getTimeoutId();

        window.hasMessage = false;

        sse.onmessage = (e) => {
            //console.log(e);
            hasMessage = true;

            App.Helper.wait.clear(toastErrorTimeoutId);
            if(toastErrorObj.isShown()) {
                toastErrorInstance.hide(toastErrorId);
            }
        };

        sse.onerror = (err) => {
            //console.log(err);
            if(hasMessage && !toastErrorObj.isShown()) {
                App.Helper.wait.start(10000, toastErrorTimeoutId).then(() => {
                    toastErrorInstance.show(toastErrorId);
                });
            }
        };

        sse.onclose = (e) => {
            //console.log(e);
            App.Helper.wait.clear(toastErrorTimeoutId);
        };

        sse.addEventListener("reload", (e) => {
            if(!toastReloadObj.isShown()) {
                toastReloadInstance.show(toastReloadId);
            }
        });

        sse.addEventListener("forceReload", (e) => {
            window.location.reload();
        });

        sse.addEventListener("forceRedirect", (e) => {
            window.location = e.data ?? null;
        });

        //https://github.com/whatwg/html/issues/3380
        //https://bugzilla.mozilla.org/show_bug.cgi?id=833462#c11
        window.addEventListener("beforeunload", (e) => {
            sse.close();
        });
    });
})();';
    $this->scriptsFoot()->endInternal();

    foreach ($this->container->get('mods') as $controller) {
        if (file_exists(_ROOT.'/app/view/'.$this->env.'/controller/'.$controller.'/partial/sse.php')) {
            include _ROOT.'/app/view/'.$this->env.'/controller/'.$controller.'/partial/sse.php';
        }
    }
    ?>
    <script class="template-date" type="text/x-handlebars-template">
        {{content}} <small class="text-muted">({{timezone}})</small>
    </script>
<?php
}
