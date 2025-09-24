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

if (isset($content, $contentType)) {
    ?>
    <?php // https://github.com/voku/HtmlMin?>
    <nocompress>
    <?php echo $this->helper->Html()->getFormField([
        'type' => 'textarea',
        'attr' => [
            'id' => 'content',
            'name' => 'content',
            'class' => ['d-none'],
        ],
        'value' => $content,
    ]); ?>
    </nocompress>
    <?php
        // https://stackoverflow.com/a/4705882/3929620
        // contenteditable="true" cause cursor conflict
    ?>
    <div class="codemirror border bg-light position-relative" style="min-height:100px">
        <div class="spinner-wrapper position-absolute text-muted d-flex flex-column justify-content-center align-items-center w-100 h-100">
            <div class="spinner-border"></div>
            <div>
                <small><?php echo $this->escape()->html(__('Loading')); ?>&hellip;</small>
            </div>
        </div>
    </div>
    <?php
    // https://www.raresportan.com/how-to-make-a-code-editor-with-codemirror6/
    // https://discuss.codemirror.net/t/codemirror-6-proper-way-to-listen-for-changes/2395
    // https://gist.github.com/caenguidanos/58efcf54c5539101d9a47345d6cea35d
    $this->scriptsFoot()->beginInternal();
    echo '(() => {
    window.addEventListener("DOMContentLoaded", () => {
        const contentEl = document.getElementById("content");
        const codemirrorEl = document.querySelector(".codemirror");
        const spinnerWrapperEl = codemirrorEl.querySelector(".spinner-wrapper");
        if(!!contentEl && !!codemirrorEl && !!spinnerWrapperEl) {
            spinnerWrapperEl.classList.add("d-none");
            codemirrorEl.style.minHeight = "auto";

            let timer;

            const editor = new EditorView({
                state: EditorState.create({
                    doc: "'.$this->escape()->js($content).'",
                    extensions: [
                        basicSetup,
                        '.(isset($this->config['mod.storage.editor.contentTypes'][$contentType]) ? $this->config['mod.storage.editor.contentTypes'][$contentType].',' : '').'
                        EditorView.updateListener.of((v)=> {
                            if(v.docChanged) {
                                if(timer) clearTimeout(timer);
                                timer = App.Helper.wait.start(500).then(() => {
                                    contentEl.value = v.state.doc
                                });
                            }
                        }),
                    ],
                }),
                parent: codemirrorEl,
            });
        }
    });
})();';
    $this->scriptsFoot()->endInternal();
}
