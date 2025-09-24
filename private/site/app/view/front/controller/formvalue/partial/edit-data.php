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

// https://www.php.net/manual/en/features.file-upload.post-method.php
// https://radu.link/purpose-max-file-size-php-form-validation/
// https://discourse.slimframework.com/t/file-upload-error-handling/631/5
// https://stackoverflow.com/questions/50808715/validate-file-uploaded-by-ajax-in-laravel/50809026
// The MAX_FILE_SIZE hidden field (measured in bytes) must precede the file input field, and its value is the maximum filesize accepted by PHP.
// This form element should always be used as it saves users the trouble of waiting for a big file being transferred
// only to find that it was too large and the transfer failed.
// Keep in mind: fooling this setting on the browser side is quite easy, so never rely on files with a greater size being blocked by this feature.
// It is merely a convenience feature for users on the client side of the application.
// The PHP settings (on the server side) for maximum-size, however, cannot be fooled.
echo $this->helper->Html()->getFormField([
    'type' => 'input',
    'attr' => [
        'type' => 'hidden',
        'name' => 'MAX_FILE_SIZE',
        'value' => $this->helper->File()->getBytes($this->config['mod.'.$this->env.'.'.$this->controller.'.media.file.uploadMaxFilesize'] ?? $this->config['mod.'.$this->controller.'.media.file.uploadMaxFilesize'] ?? $this->config['media.'.$this->env.'.file.uploadMaxFilesize'] ?? $this->config['media.file.uploadMaxFilesize'] ?? \Safe\ini_get('upload_max_filesize')),
    ],
]);

$params = [
    'type' => 'input',
    'attr' => [
        'name' => $key,
        'type' => 'file',
        'id' => $this->helper->Nette()->Strings()->webalize($key),
        'class' => ['form-control'],
        'multiple' => !empty($this->Mod->formfield_option['max_files']) && $this->Mod->formfield_option['max_files'] > 1 ? true : false,
        // TODO - https://stackoverflow.com/a/60933216/3929620
        'data-max' => !empty($this->Mod->formfield_option['max_files']) && $this->Mod->formfield_option['max_files'] > 1 ? $this->Mod->formfield_option['max_files'] : false,
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file
        // The accept attribute doesn't validate the types of the selected files;
        // it provides hints for browsers to guide users towards selecting the correct file types.
        // It is still possible (in most cases) for users to toggle an option in the file chooser
        // that makes it possible to override this and select any file they wish, and then choose incorrect file types.
        // Because of this, you should make sure that the accept attribute is backed up by appropriate server-side validation.
        'accept' => implode(',', array_values($this->config['mod.'.$this->env.'.'.$this->controller.'.mime.file.allowedTypes'] ?? $this->config['mod.'.$this->controller.'.mime.file.allowedTypes'] ?? $this->config['mime.'.$this->env.'.file.allowedTypes'] ?? $this->config['mime.file.allowedTypes'])),
        'required' => !empty($this->Mod->{$key}['teachers'][$this->teacherKey]['files']) ? false : (bool) $val[$this->env]['attr']['required'],
        'data-required' => (bool) $val[$this->env]['attr']['required'],
        // https://github.com/twbs/bootstrap/issues/15218#issuecomment-586703436
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Attributes/readonly
        // The attribute is not supported or relevant to <select> or input types that are already not mutable,
        // such as checkbox and radio or cannot, by definition, start with a value, such as the file input type.
        'readonly' => !empty($this->Mod->formfield_option['max_files']) && !empty($this->Mod->{$key}['teachers'][$this->teacherKey]['files']) && (is_countable($this->Mod->{$key}['teachers'][$this->teacherKey]['files']) ? count($this->Mod->{$key}['teachers'][$this->teacherKey]['files']) : 0) >= $this->Mod->formfield_option['max_files'] ? true : false,
    ],

    'help' => [
        sprintf(
            __('Maximum number of files: %1$d.'),
            $this->Mod->formfield_option['max_files'] ?? 1,
        ),
        sprintf(
            __('Maximum weight per file: %1$s.'),
            $this->helper->File()->formatSize($this->config['mod.'.$this->env.'.'.$this->controller.'.media.file.uploadMaxFilesize'] ?? $this->config['mod.'.$this->controller.'.media.file.uploadMaxFilesize'] ?? $this->config['media.'.$this->env.'.file.uploadMaxFilesize'] ?? $this->config['media.file.uploadMaxFilesize'] ?? \Safe\ini_get('upload_max_filesize'))
        ),
        /*sprintf(
            __('Allowed extensions: %1$s.'),
            implode(', ', $this->config['mod.'.$this->env.'.'.$this->controller.'.mime.file.allowedTypes'] ?? $this->config['mod.'.$this->controller.'.mime.file.allowedTypes'] ?? $this->config['mime.'.$this->env.'.file.allowedTypes']  ?? $this->config['mime.file.allowedTypes']),
        ),*/
    ],
];

if (!empty($params['help'])) {
    $params['attr']['aria-labelledby'] = 'help-'.$this->helper->Nette()->Strings()->webalize($key);
}

echo $this->helper->Html()->getFormField($params);

echo '<div'.$this->escapeAttr([
    'class' => ['progress-bar-'.$this->helper->Nette()->Strings()->webalize($key)],
]).'></div>'.PHP_EOL;

echo '<div class="invalid-feedback"></div>'.PHP_EOL;

echo '<div'.$this->escapeAttr([
    'class' => array_merge(['card', 'text-bg-info', 'my-2'], !empty($this->Mod->{$key}['teachers'][$this->teacherKey]['files']) ? [] : ['d-none']),
]).'>'.PHP_EOL;
echo '<div class="card-body">'.PHP_EOL;

// no spaces or PHP_EOL, see here https://stackoverflow.com/a/54170743/3929620
echo '<ol class="mb-0">';

if (!empty($this->Mod->{$key}['teachers'][$this->teacherKey]['files'])) {
    foreach ($this->Mod->{$key}['teachers'][$this->teacherKey]['files'] as $crc32 => $item) {
        echo '<li>';
        echo $this->escape()->html($item['name']).' <i class="small">('.$this->helper->File()->formatSize($item['size']).')</i>';
        echo '<a data-bs-toggle="modal" class="btn-danger text-danger" href="javascript:;" role="button"'.$this->escapeAttr([
            'data-id' => $this->Mod->id,
            'data-formfield-id' => $this->Mod->formfield_id,
            'data-file-id' => $crc32,
            'title' => __('delete'),
        ]).'><i class="fas fa-trash-alt ms-2"></i></a>'.PHP_EOL;
        echo '</li>';
    }
}

echo '</ol>'.PHP_EOL;

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;

if (!empty($params['help'])) {
    echo '<div'.$this->escapeAttr([
        'id' => 'help-'.$this->helper->Nette()->Strings()->webalize($key),
        'class' => ['form-text'],
    ]).'>'.nl2br(is_array($params['help']) ? implode(PHP_EOL, $params['help']) : $params['help']).'</div>'.PHP_EOL;
}

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;

if ($this->rbac->isGranted($this->controller.'.api.upload')) {
    // https://community.transloadit.com/t/uppy-without-dashboard-or-fileinput/14955
    // https://developer.mozilla.org/en-US/docs/Web/API/FileList
    // https://developer.mozilla.org/en-US/docs/Web/API/File_API/Using_files_from_web_applications
    // https://stackoverflow.com/a/33855825/3929620
    // https://github.com/transloadit/uppy/issues/1332#issuecomment-470281886
    if (!$this->hasSection('uppy-'.$this->helper->Nette()->Strings()->webalize($key))) {
        $this->setSection('uppy-'.$this->helper->Nette()->Strings()->webalize($key), $this->render('uppy-standalone', [
            'id' => $this->Mod->formfield_id,
            'routeArgs' => [
                'routeName' => 'api.'.$this->controller,
                'data' => [
                    'action' => 'upload',
                ],
            ],
            'autoProceed' => true,
            'fieldName' => $key,
            // 'limit' => 1,
            'maxFileSize' => $this->config['mod.'.$this->env.'.'.$this->controller.'.media.file.uploadMaxFilesize'] ?? $this->config['mod.'.$this->controller.'.media.file.uploadMaxFilesize'] ?? $this->config['media.'.$this->env.'.file.uploadMaxFilesize'] ?? $this->config['media.file.uploadMaxFilesize'] ?? \Safe\ini_get('upload_max_filesize'),
            // 'maxNumberOfFiles' => !empty($this->Mod->formfield_option['max_files']) ? (!empty($this->Mod->{$key}['teachers'][$this->teacherKey]['files']) ? $this->Mod->formfield_option['max_files'] - count($this->Mod->{$key}['teachers'][$this->teacherKey]['files']) : $this->Mod->formfield_option['max_files']) : 0,
            // 'maxNumberOfFiles' => !empty($this->Mod->formfield_option['max_files']) ? $this->Mod->formfield_option['max_files'] : 1,
            'allowedFileTypes' => [...array_values($this->config['mod.'.$this->env.'.'.$this->controller.'.mime.file.allowedTypes'] ?? $this->config['mod.'.$this->controller.'.mime.file.allowedTypes'] ?? $this->config['mime.'.$this->env.'.file.allowedTypes'] ?? $this->config['mime.file.allowedTypes']), ...array_map(fn ($item) => '.'.$item, $this->helper->Arrays()->getExtFromConfig($this->config['mod.'.$this->env.'.'.$this->controller.'.mime.file.allowedTypes'] ?? $this->config['mod.'.$this->controller.'.mime.file.allowedTypes'] ?? $this->config['mime.'.$this->env.'.file.allowedTypes'] ?? $this->config['mime.file.allowedTypes']))],
            'progressBarTarget' => '.progress-bar-'.$this->helper->Nette()->Strings()->webalize($key),
            // enable to test stalled uploads
            // 'timeout' => 1 * 1000,
        ]));
    }
    echo $this->getSection('uppy-'.$this->helper->Nette()->Strings()->webalize($key));

    $this->scriptsFoot()->beginInternal(
        101 // default: 100
    );
    echo '(() => {
    if(typeof uppy'.$this->Mod->formfield_id.' !== "undefined") {
        const inputFile'.$this->Mod->formfield_id.'Element = document.getElementById("'.$this->helper->Nette()->Strings()->webalize($key).'");
        if(inputFile'.$this->Mod->formfield_id.'Element) {
            const maxNumberOfFiles'.$this->Mod->formfield_id.' = inputFile'.$this->Mod->formfield_id.'Element.hasAttribute("data-max") ? Number.parseInt(inputFile'.$this->Mod->formfield_id.'Element.getAttribute("data-max")) : 1;
            let oddmentFiles'.$this->Mod->formfield_id.' = '.(!empty($this->Mod->formfield_option['max_files']) ? (!empty($this->Mod->{$key}['teachers'][$this->teacherKey]['files']) ? $this->Mod->formfield_option['max_files'] - (is_countable($this->Mod->{$key}['teachers'][$this->teacherKey]['files']) ? count($this->Mod->{$key}['teachers'][$this->teacherKey]['files']) : 0) : $this->Mod->formfield_option['max_files']) : 0).';

            inputFile'.$this->Mod->formfield_id.'Element.addEventListener("change", function(e) {
                if(inputFile'.$this->Mod->formfield_id.'Element.files.length <= oddmentFiles'.$this->Mod->formfield_id.') {
                    uppy'.$this->Mod->formfield_id.'.setMeta({
                        id: '.$this->Mod->id.',
                        catform_id: '.$this->Mod->catform_id.',
                        form_id: '.$this->Mod->form_id.',
                        formfield_id: '.$this->Mod->formfield_id.',
                    });

                    //https://stackoverflow.com/a/41221424/3929620
                    //https://stackoverflow.com/a/36995700/3929620
                    for (const file of inputFile'.$this->Mod->formfield_id.'Element.files) {
                        try {
                            uppy'.$this->Mod->formfield_id.'.addFile({
                                data: file,
                                name: file.name,
                            });
                        } catch (error) {}
                    }
                } else {
                    App.Mod.Toast.add("'.$this->escape()->js(sprintf(__('Maximum number of files: %1$d.'), $this->Mod->formfield_option['max_files'] ?? 1)).'", "warning").show();
                }

                //https://stackoverflow.com/a/3162319/3929620
                inputFile'.$this->Mod->formfield_id.'Element.value = "";
            });

            uppy'.$this->Mod->formfield_id.'.on("upload-success", (file, response) => {
                App.Mod.Toast.hideAll("upload-stalled-" + file.id);
                App.Mod.Toast.hideAll("upload-progress-" + file.id);

                --oddmentFiles'.$this->Mod->formfield_id.';
                if(oddmentFiles'.$this->Mod->formfield_id.' <= 0) {
                    //inputFile'.$this->Mod->formfield_id.'Element.disabled = true;
                    inputFile'.$this->Mod->formfield_id.'Element.setAttribute("readonly", "");
                }

                const templateLiEl = document.querySelector(".template-input-file-multiple-li");
                const cardElement = App.Helper.getNextSibling(inputFile'.$this->Mod->formfield_id.'Element, ".card");
                if(templateLiEl && cardElement) {
                    const olElement = cardElement.querySelector("ol");
                    if(olElement) {
                        const templateLi = Handlebars.compile(templateLiEl.innerHTML);
                        const html = templateLi({
                            name: response.body.response.name,
                            size: response.body.response.size,
                            attr: App.Helper.escapeHTMLAttribute({
                                "data-id": '.$this->Mod->id.',
                                "data-formfield-id": '.$this->Mod->formfield_id.',
                                "data-file-id": response.body.response.file_id,
                                "data-bs-target": "#modal-" + window.modalDeleteId,
                                title: "'.$this->escape()->js(__('delete')).'",
                            }),
                        });

                        olElement.insertAdjacentHTML("beforeend", html);

                        if(cardElement.classList.contains("d-none")) {
                            cardElement.classList.remove("d-none");
                        }

                        window.btnModalListFunction();
                    }
                }
            });

            uppy'.$this->Mod->formfield_id.'.on("complete", (result) => {
                inputFile'.$this->Mod->formfield_id.'Element.removeAttribute("required");
            });

            document.addEventListener("form-delete-success", (e) => {
                if(Number.parseInt(e.detail.formfield_id) === '.$this->Mod->formfield_id.') {
                    ++oddmentFiles'.$this->Mod->formfield_id.';
                    //inputFile'.$this->Mod->formfield_id.'Element.disabled = false;
                    inputFile'.$this->Mod->formfield_id.'Element.removeAttribute("readonly");
                }
            });
        }
    }
})();';
    $this->scriptsFoot()->endInternal();
}
