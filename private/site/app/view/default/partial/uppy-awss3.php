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

$this->scriptsFoot()->add($this->asset('asset/'.$this->env.'/js/locales/@uppy/'.$this->lang->locale.'.min.js'));

$this->scriptsFoot()->beginInternal(
    $pos ?? 100
);
echo '(() => {
    const formEl = document.querySelector("form[data-sync]");
    if(!!formEl) {
        const btnEl = formEl.querySelector("button[type=\"submit\"]");
        if(!!btnEl) {
            const btnText = btnEl.innerHTML;

            const uppyReset = (() => {
                //https://stackoverflow.com/a/54533740
                btnEl.disabled = false;

                btnEl.innerHTML = btnText;
            });

    	    const uppy'.($id ?? '').' = new Uppy({
    	    	debug: '.((bool) !empty($this->config['debug.enabled'] ?? false) ? 'true' : 'true').',
    	        '.(!empty($this->config['debug.enabled'] ?? false) ? '
                    logger: debugLogger,
                ' : '
                    logger: debugLogger,
                ').'
    	    	locale: Uppy.locales.'.$this->lang->locale.',
    	    });

    	    uppy'.($id ?? '').'.use(Dashboard, {
    	    	target: ".uppy-uploader",
    	    	inline: true,
    	    	showLinkToFileUploadResult: false,
    	    	showProgressDetails: true,
    	    	hideUploadButton: true,
    	    	hideRetryButton: true,
    	    	proudlyDisplayPoweredByUppy: false,
    	    	replaceTargetContent: true,
    	    	theme: '.(!empty($this->config['theme.switcher']) ? '(typeof getPreferredTheme === "function" ? getPreferredTheme() : "auto")' : '"light"').',
    	    });

    	    uppy'.($id ?? '').'.use(AwsS3, {
    	    	getUploadParameters (file) {
    	    		return fetch("'.$this->escape()->js($this->uri([
    'routeName' => 'api.'.$this->controller,
    'data' => [
        'action' => 'upload-url',
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
            				filename: file.name,
            				contentType: file.type,

            				//https://stackoverflow.com/a/5108845/3929620
            				//https://stackoverflow.com/a/5159690/3929620
            				//https://stackoverflow.com/a/784946/3929620
            				dirname: (typeof getUppyDirname === "function" ? getUppyDirname() : ""),
            			}),
            		})
            		.then(response => response.json())
            		.then(data => {
            			return {
            				method: data.response.method,
            				url: data.response.url,
            				fields: data.response.fields,
                            headers: data.response.headers,
            			}
            		});
            	}
            });

            formEl.addEventListener("submit", (e) => {
                if (!e.defaultPrevented) {
                    e.preventDefault();

            	    if (formEl.checkValidity() === true) {
            	        if(uppy'.($id ?? '').'.getFiles().length > 0) {
                            if(typeof uppy'.($id ?? '').'.getState().error !== "undefined" && !!uppy'.($id ?? '').'.getState().error) {
            	        		uppy'.($id ?? '').'.retryAll();
            	        	} else {
            	        		uppy'.($id ?? '').'.upload();
            	        	}
            	        } else {
            	        	uppy'.($id ?? '').'.info("'.$this->escape()->js(sprintf(_x('No %1$s selected.', 'default'), $this->helper->Nette()->Strings()->firstUpper(_n('file', 'files', 1)))).'", "warning", 10000);
            	        }
            	    }
            	}
            });

            uppy'.($id ?? '').'.on("upload", (data) => {
            	//https://stackoverflow.com/a/54533740
                btnEl.disabled = true;

                const btnLoadingText = btnEl.dataset.loadingText;
                if (!!btnLoadingText) {
                    btnEl.innerHTML = btnLoadingText;
                }
            });

            uppy'.($id ?? '').'.on("file-removed", (file) => {
            	if(uppy'.($id ?? '').'.getFiles().length === 0) {
            		uppyReset();
            	}
            });

            uppy'.($id ?? '').'.on("cancel-all", (data) => {
                uppyReset();
            });

            uppy'.($id ?? '').'.on("error", (error) => {
                uppyReset();
            });

            uppy'.($id ?? '').'.on("complete", (result) => {
                if(typeof uppy'.($id ?? '').'.getState().totalProgress !== "undefined" && uppy'.($id ?? '').'.getState().totalProgress === 100) {
                    return fetch("'.$this->escape()->js($this->uri([
    'routeName' => 'api.'.$this->controller,
    'data' => [
        'action' => 'upload-after',
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
                    		result: result,
                    		dirname: (typeof getUppyDirname === "function" ? getUppyDirname() : ""),
                    	}),
                    })
                    .then(response => response.json())
                    .then(data => {
                        //console.log(data)

                        if( typeof data.response.redirect !== "undefined" ) {
                            if( typeof data.response.timeout !== "undefined" ) {
                                App.Helper.wait.start(parseInt( data.response.timeout )).then(() => {
                                    parent.location.href = data.response.redirect;
                                });
                            } else {
                                parent.location.href = data.response.redirect;
                            }
                        } else if( typeof data.response.reload !== "undefined" ) {
                            if( typeof data.response.timeout !== "undefined" ) {
                                App.Helper.wait.start(parseInt( data.response.timeout )).then(() => {
                                    //https://stackoverflow.com/a/55127750
                                    window.location.reload();
                                });
                            } else {
                                //https://stackoverflow.com/a/55127750
                                window.location.reload();
                            }
                        } else {
                            uppyReset();
                        }
                    })
                    .catch(error => {
                        //console.error(error)

                        uppy'.($id ?? '').'.info({
                            message: "'.$this->escape()->js(__('A technical problem has occurred, try again later.')).'",
                            details: error,
                        }, "error", 10000);

                        uppyReset();
                    });
                } else if(typeof uppy'.($id ?? '').'.getState().error !== "undefined" && !!uppy'.($id ?? '').'.getState().error) {
                    uppyReset();
                }
            });

            '.(!empty($this->config['theme.switcher']) ? '
            if(typeof getPreferredTheme === "function") {
                document.addEventListener("setTheme.after", (e) => {
                    uppy'.($id ?? '').'.getPlugin("Dashboard").setOptions({
                        theme: e.detail.theme,
                    });
                });
            }
            ' : '').'
        }
    }
})();';
$this->scriptsFoot()->endInternal();

__('No %1$s selected.', 'default');
__('No %1$s selected.', 'male');
__('No %1$s selected.', 'female');
