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

// https://community.transloadit.com/t/uppy-without-dashboard-or-fileinput/14955/2

$this->scriptsFoot()->add($this->asset('asset/'.$this->env.'/js/locales/@uppy/'.$this->lang->locale.'.min.js'));

// FIXME - autoProceed not working with debug.enabled = false
$this->scriptsFoot()->beginInternal(
    $pos ?? 100
);
echo '(() => {
    const uppy'.($id ?? '').' = new Uppy({
        // If several Uppy instances are being used, for instance, on two different pages, an id should be specified.
        // This allows Uppy to store information in localStorage without colliding with other Uppy instances.
        // This ID should be persistent across page reloads and navigation—it shouldn’t be a random number
        // that is different every time Uppy is loaded.
        id: "uppy'.($id ?? null).'",
    	debug: '.((bool) !empty($this->config['debug.enabled'] ?? false) ? 'true' : 'true').',
    	'.(!empty($this->config['debug.enabled'] ?? false) ? '
            logger: debugLogger,
        ' : '
            logger: debugLogger,
        ').'
    	locale: Uppy.locales.'.$this->lang->locale.',
    	'.(!empty($autoProceed) ? '
            autoProceed: true,
        ' : '').'
    	'.(!empty($maxFileSize) || (isset($maxNumberOfFiles) && !isBlank($maxNumberOfFiles)) || !empty($allowedFileTypes) ? '
    	    restrictions: {
    	        '.(!empty($maxFileSize) ? '
    	            maxFileSize: '.(int) $maxFileSize.',
    	        ' : '').'
    	        '.(isset($maxNumberOfFiles) && !isBlank($maxNumberOfFiles) ? '
                    maxNumberOfFiles: '.(int) $maxNumberOfFiles.',
                ' : '').'
                '.(!empty($allowedFileTypes) ? '
                    // https://github.com/transloadit/uppy/issues/1828#issuecomment-531774337
                    allowedFileTypes: ["'.implode('","', (array) $allowedFileTypes).'"],
                ' : '').'
            },
    	' : '').'
    	//https://uppy.io/docs/uppy/#how-do-i-allow-duplicate-files
    	onBeforeFileAdded: () => true,
    });

    uppy'.($id ?? '').'.use(ProgressBar, {
        '.(!empty($progressBarTarget) ? '
            target: "'.$this->escape()->js($progressBarTarget).'",
        ' : '').'
        '.(!empty($progressBarFixed) ? '
            fixed: true,
        ' : '').'
    });

    uppy'.($id ?? '').'.use(XHR, {
        endpoint: "'.$this->escape()->js($this->uri($routeArgs ?? [
    'routeName' => 'api.'.$this->controller,
    'data' => [
        'action' => 'upload',
    ],
])).'",
        method: "POST",
        headers: {
        	//https://heera.it/detect-ajax-request-php-frameworks
            //https://codeigniter.com/user_guide/general/ajax.html
            "X-Requested-With": "XMLHttpRequest",
        },
        responseType: "application/json",
        '.(!empty($fieldName) ? '
            fieldName: "'.$this->escape()->js($fieldName).'",
        ' : '').'
        '.(!empty($limit) ? '
            limit: '.(int) $limit.',
        ' : '').'
        '.(!empty($timeout) ? '
            timeout: '.(int) $timeout.',
        ' : '').'
    });

    uppy'.($id ?? '').'.on("restriction-failed", (file, error) => {
        errorMessage = error.message;
        if (errorMessage.length > 100 && typeof file.name !== "undefined") {
            errorMessage = "'.$this->escape()->js(__('This file type is not allowed')).':<br><i>" + file.name + "</i>";
        }

        App.Mod.Toast.add(errorMessage, "warning").show();
    });

    const toastStalledInstances = {},
        toastStalledIds = {},
        toastStalledObjs = {},
        toastUnstalledInstances = {},
        toastUnstalledIds = {},
        toastUnstalledObjs = {};

    uppy'.($id ?? '').'.on("upload-stalled", (error, files) => {
        for (const stalledFile of files) {
            if(!toastStalledInstances.hasOwnProperty(stalledFile.id)) {
                toastStalledInstances[stalledFile.id] = App.Mod.Toast.add("'.$this->escape()->js(__('This upload seems stalled')).':<br><i>" + stalledFile.name + "</i>", "warning", {
                    stack: "upload-stalled-" + stalledFile.id,
                    autohide: false,
                });
                toastStalledIds[stalledFile.id] = toastStalledInstances[stalledFile.id].getId();
                toastStalledObjs[stalledFile.id] = toastStalledInstances[stalledFile.id].getObj();
            }

            if(!toastStalledObjs[stalledFile.id].isShown()) {
                App.Mod.Toast.hideAll("upload-progress-" + stalledFile.id);
                toastStalledInstances[stalledFile.id].show(toastStalledIds[stalledFile.id]);
            }

            window["noLongerStalledEventHandler" + stalledFile.id] = (file) => {
                if (stalledFile.id === file.id) {
                    if(!toastUnstalledInstances.hasOwnProperty(file.id)) {
                        toastUnstalledInstances[file.id] = App.Mod.Toast.add("'.$this->escape()->js(__('This upload is no longer stalled')).':<br><i>" + file.name + "</i>", "success", {
                            stack: "upload-progress-" + file.id,
                        });
                        toastUnstalledIds[file.id] = toastUnstalledInstances[file.id].getId();
                        toastUnstalledObjs[file.id] = toastUnstalledInstances[file.id].getObj();
                    }

                    if(!toastUnstalledObjs[file.id].isShown()) {
                        App.Mod.Toast.hideAll("upload-stalled-" + file.id);
                        toastUnstalledInstances[file.id].show(toastUnstalledIds[file.id]);
                    }

                    uppy'.($id ?? '').'.off("upload-progress", window["noLongerStalledEventHandler" + file.id]);
                }
            };

            uppy'.($id ?? '').'.on("upload-progress", window["noLongerStalledEventHandler" + stalledFile.id]);
        }
    });

    uppy'.($id ?? '').'.on("complete", (result) => {
        if(result.successful.length > 0) {
            let message = result.successful.length + " ";
            if(result.successful.length === 1) {
                message += "'.$this->escape()->js(sprintf(_nx('%1$s successfully uploaded.', '%1$s successfully uploaded.', 1, 'male'), $this->helper->Nette()->Strings()->lower(_n('file', 'files', 1)))).'";
            } else {
                message += "'.$this->escape()->js(sprintf(_nx('%1$s successfully uploaded.', '%1$s successfully uploaded.', 2, 'male'), $this->helper->Nette()->Strings()->lower(_n('file', 'files', 2)))).'";
            }

            App.Mod.Toast.add(message, "success").show();
        }
    });

    uppy'.($id ?? '').'.on("upload-error", (file, error, response) => {
        App.Mod.Toast.hideAll("upload-stalled-" + file.id);
        App.Mod.Toast.hideAll("upload-progress-" + file.id);
        uppy'.($id ?? '').'.removeFile(file.id);
    });

    uppy'.($id ?? '').'.on("error", (error) => {
        App.Mod.Toast.add(error.message, "danger").show();
    });
})();';
$this->scriptsFoot()->endInternal();

_n('%1$s successfully uploaded.', '%1$s successfully uploaded.', 1, 'default');
_n('%1$s successfully uploaded.', '%1$s successfully uploaded.', 1, 'male');
_n('%1$s successfully uploaded.', '%1$s successfully uploaded.', 1, 'female');
