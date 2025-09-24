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

echo '<div'.$this->escapeAttr(['class' => ['row', 'row-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']), 'row-'.$this->helper->Nette()->Strings()->webalize($row['type']), 'mb-3']]).'>'.PHP_EOL;
echo '<label'.$this->escapeAttr([
    'class' => $this->config['theme.'.$this->env.'.'.$this->action.'.label.class'] ?? $this->config['theme.'.$this->env.'.label.class'] ?? $this->config['theme.'.$this->action.'.label.class'] ?? $this->config['theme.label.class'] ?? false,
    'for' => $this->controller.'field-'.$row['id'],
]).'>'.PHP_EOL;

if (!empty($row['name'])) {
    echo $this->escape()->html($row['name']);
}

echo !empty($row['required']) ? ' *' : '';

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
        'value' => $this->helper->File()->getBytes($this->config['mod.'.$this->env.'.'.$this->controller.'value.media.file.uploadMaxFilesize'] ?? $this->config['mod.'.$this->controller.'value.media.file.uploadMaxFilesize'] ?? $this->config['media.'.$this->env.'.file.uploadMaxFilesize'] ?? $this->config['media.file.uploadMaxFilesize'] ?? \Safe\ini_get('upload_max_filesize')),
    ],
]);

$params = [
    'type' => 'input',
    'attr' => [
        'name' => $this->controller.'field_'.$row['id'].'[]',
        'type' => 'file',
        'id' => $this->controller.'field-'.$row['id'],
        'class' => ['form-control'],
        'multiple' => !empty($row['option']['max_files']) && $row['option']['max_files'] > 1 ? true : false,
        // TODO - https://stackoverflow.com/a/60933216/3929620
        'data-max' => !empty($row['option']['max_files']) && $row['option']['max_files'] > 1 ? $row['option']['max_files'] : false,
        // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file
        // The accept attribute doesn't validate the types of the selected files;
        // it provides hints for browsers to guide users towards selecting the correct file types.
        // It is still possible (in most cases) for users to toggle an option in the file chooser
        // that makes it possible to override this and select any file they wish, and then choose incorrect file types.
        // Because of this, you should make sure that the accept attribute is backed up by appropriate server-side validation.
        'accept' => implode(',', array_values($this->config['mod.'.$this->env.'.'.$this->controller.'value.mime.file.allowedTypes'] ?? $this->config['mod.'.$this->controller.'value.mime.file.allowedTypes'] ?? $this->config['mime.'.$this->env.'.file.allowedTypes'] ?? $this->config['mime.file.allowedTypes'])),
        'required' => !empty($row[$this->controller.'value_data']) ? false : (bool) $row['required'],
        'data-required' => (bool) $row['required'],
        'disabled' => !empty($row['option']['max_files']) && !empty($row[$this->controller.'value_data']) && (is_countable($row[$this->controller.'value_data']) ? count($row[$this->controller.'value_data']) : 0) >= $row['option']['max_files'] ? true : false,
    ],

    'help' => [
        sprintf(
            __('Maximum number of files: %1$d.'),
            $row['option']['max_files'] ?? 1,
        ),
        sprintf(
            __('Maximum weight per file: %1$s.'),
            $this->helper->File()->formatSize($this->config['mod.'.$this->env.'.'.$this->controller.'value.media.file.uploadMaxFilesize'] ?? $this->config['mod.'.$this->controller.'value.media.file.uploadMaxFilesize'] ?? $this->config['media.'.$this->env.'.file.uploadMaxFilesize'] ?? $this->config['media.file.uploadMaxFilesize'] ?? \Safe\ini_get('upload_max_filesize'))
        ),
        /*sprintf(
            __('Allowed extensions: %1$s.'),
            implode(', ', $this->config['mod.'.$this->env.'.'.$this->controller.'value.mime.file.allowedTypes'] ?? $this->config['mod.'.$this->controller.'value.mime.file.allowedTypes'] ?? $this->config['mime.'.$this->env.'.file.allowedTypes']  ?? $this->config['mime.file.allowedTypes']),
        ),*/
    ],
];

if (!empty($params['help'])) {
    $row['richtext'] = nl2br((is_array($params['help']) ? implode(PHP_EOL, $params['help']) : $params['help']).(!empty($row['richtext']) ? PHP_EOL.PHP_EOL.$row['richtext'] : ''));
}

if (!empty($row['richtext'])) {
    $params['attr']['aria-labelledby'] = 'help-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']);
}

echo $this->helper->Html()->getFormField($params);

echo '<div'.$this->escapeAttr([
    'class' => ['progress-bar-'.$row['id']],
]).'></div>'.PHP_EOL;

echo '<div class="invalid-feedback"></div>'.PHP_EOL;

echo '<div'.$this->escapeAttr([
    'class' => array_merge(['card', 'text-bg-info', 'my-2'], !empty($row[$this->controller.'value_data']) ? [] : ['d-none']),
]).'>'.PHP_EOL;
echo '<div class="card-body">'.PHP_EOL;

// no spaces or PHP_EOL, see here https://stackoverflow.com/a/54170743/3929620
echo '<ol class="mb-0">';

if (!empty($row[$this->controller.'value_id']) && !empty($row[$this->controller.'value_data'])) {
    foreach ($row[$this->controller.'value_data'] as $crc32 => $item) {
        echo '<li>';
        echo $this->escape()->html($item['name']).' <i class="small">('.$this->helper->File()->formatSize($item['size']).')</i>';
        echo '<a data-bs-toggle="modal" class="btn-danger text-danger" href="javascript:;" role="button"'.$this->escapeAttr([
            'data-id' => $row[$this->controller.'value_id'],
            'data-'.$this->controller.'field-id' => $row['id'],
            'data-file-id' => $crc32,
            'title' => __('delete'),
        ]).'><i class="fas fa-trash-alt ms-2"></i></a>'.PHP_EOL;
        echo '</li>';
    }
}

echo '</ol>'.PHP_EOL;

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;

if (!empty($row['richtext'])) {
    echo '<div'.$this->escapeAttr([
        'id' => 'help-'.$this->helper->Nette()->Strings()->webalize($this->controller.'field-'.$row['id']),
        'class' => ['form-text'],
    ]).'>'.$row['richtext'].'</div>'.PHP_EOL;
}

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;

if ($this->rbac->isGranted($this->controller.'value.api.upload')) {
    // https://community.transloadit.com/t/uppy-without-dashboard-or-fileinput/14955
    // https://developer.mozilla.org/en-US/docs/Web/API/FileList
    // https://developer.mozilla.org/en-US/docs/Web/API/File_API/Using_files_from_web_applications
    // https://stackoverflow.com/a/33855825/3929620
    // https://github.com/transloadit/uppy/issues/1332#issuecomment-470281886
    if (!$this->hasSection('uppy-'.$row['id'])) {
        $this->setSection('uppy-'.$row['id'], $this->render('uppy-standalone', [
            'id' => $row['id'],
            'routeArgs' => [
                'routeName' => 'api.'.$this->controller.'value',
                'data' => [
                    'action' => 'upload',
                ],
            ],
            'autoProceed' => true,
            'fieldName' => 'data',
            // 'limit' => 1,
            'maxFileSize' => $this->config['mod.'.$this->env.'.'.$this->controller.'value.media.file.uploadMaxFilesize'] ?? $this->config['mod.'.$this->controller.'value.media.file.uploadMaxFilesize'] ?? $this->config['media.'.$this->env.'.file.uploadMaxFilesize'] ?? $this->config['media.file.uploadMaxFilesize'] ?? \Safe\ini_get('upload_max_filesize'),
            // 'maxNumberOfFiles' => !empty($row['option']['max_files']) ? (!empty($row[$this->controller.'value_data']) ? $row['option']['max_files'] - count($row[$this->controller.'value_data']) : $row['option']['max_files']) : 0,
            // 'maxNumberOfFiles' => !empty($row['option']['max_files']) ? $row['option']['max_files'] : 1,
            'allowedFileTypes' => [...array_values($this->config['mod.'.$this->env.'.'.$this->controller.'value.mime.file.allowedTypes'] ?? $this->config['mod.'.$this->controller.'value.mime.file.allowedTypes'] ?? $this->config['mime.'.$this->env.'.file.allowedTypes'] ?? $this->config['mime.file.allowedTypes']), ...array_map(fn ($item) => '.'.$item, $this->helper->Arrays()->getExtFromConfig($this->config['mod.'.$this->env.'.'.$this->controller.'value.mime.file.allowedTypes'] ?? $this->config['mod.'.$this->controller.'value.mime.file.allowedTypes'] ?? $this->config['mime.'.$this->env.'.file.allowedTypes'] ?? $this->config['mime.file.allowedTypes']))],
            'progressBarTarget' => '.progress-bar-'.$row['id'],
            // enable to test stalled uploads
            // 'timeout' => 1 * 1000,
        ]));
    }
    echo $this->getSection('uppy-'.$row['id']);

    $this->scriptsFoot()->beginInternal(
        101 // default: 100
    );
    echo '(() => {
    if(typeof uppy'.$row['id'].' !== "undefined") {
        const inputFile'.$row['id'].'Element = document.getElementById("'.$this->controller.'field-'.$row['id'].'");
        if(inputFile'.$row['id'].'Element) {
            const maxNumberOfFiles'.$row['id'].' = inputFile'.$row['id'].'Element.hasAttribute("data-max") ? Number.parseInt(inputFile'.$row['id'].'Element.getAttribute("data-max")) : 1;
            let oddmentFiles'.$row['id'].' = '.(!empty($row['option']['max_files']) ? (!empty($row[$this->controller.'value_data']) ? $row['option']['max_files'] - (is_countable($row[$this->controller.'value_data']) ? count($row[$this->controller.'value_data']) : 0) : $row['option']['max_files']) : 0).';

            inputFile'.$row['id'].'Element.addEventListener("change", function(e) {
                if(inputFile'.$row['id'].'Element.files.length <= oddmentFiles'.$row['id'].') {
                    uppy'.$row['id'].'.setMeta({
                        catform_id: '.$this->{'cat'.$this->controller.'Row'}['id'].',
                        form_id: '.$this->Mod->id.',
                        formfield_id: '.$row['id'].',
                    });

                    //https://stackoverflow.com/a/41221424/3929620
                    //https://stackoverflow.com/a/36995700/3929620
                    for (const file of inputFile'.$row['id'].'Element.files) {
                        try {
                            uppy'.$row['id'].'.addFile({
                                data: file,
                                name: file.name,
                            });
                        } catch (error) {}
                    }
                } else {
                    App.Mod.Toast.add("'.$this->escape()->js(sprintf(__('Maximum number of files: %1$d.'), $row['option']['max_files'] ?? 1)).'", "warning").show();
                }

                //https://stackoverflow.com/a/3162319/3929620
                inputFile'.$row['id'].'Element.value = "";
            });

            uppy'.$row['id'].'.on("upload-success", (file, response) => {
                App.Mod.Toast.hideAll("upload-stalled-" + file.id);
                App.Mod.Toast.hideAll("upload-progress-" + file.id);

                --oddmentFiles'.$row['id'].';
                if(oddmentFiles'.$row['id'].' <= 0) {
                    inputFile'.$row['id'].'Element.disabled = true;
                }

                const templateLiEl = document.querySelector(".template-'.$this->helper->Nette()->Strings()->webalize($row['type']).'-li");
                const cardElement = App.Helper.getNextSibling(inputFile'.$row['id'].'Element, ".card");
                if(templateLiEl && cardElement) {
                    const olElement = cardElement.querySelector("ol");
                    if(olElement) {
                        const templateLi = Handlebars.compile(templateLiEl.innerHTML);
                        const html = templateLi({
                            name: response.body.response.name,
                            size: response.body.response.size,
                            attr: App.Helper.escapeHTMLAttribute({
                                "data-id": response.body.response.id,
                                "data-'.$this->controller.'field-id": '.$row['id'].',
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

            uppy'.$row['id'].'.on("complete", (result) => {
                inputFile'.$row['id'].'Element.removeAttribute("required");
            });

            document.addEventListener("form-delete-success", (e) => {
                if(Number.parseInt(e.detail.'.$this->controller.'field_id) === '.$row['id'].') {
                    ++oddmentFiles'.$row['id'].';
                    inputFile'.$row['id'].'Element.disabled = false;
                }
            });
        }
    }
})();';
    $this->scriptsFoot()->endInternal();
}
