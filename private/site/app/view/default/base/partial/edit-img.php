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

use Laminas\Stdlib\ArrayUtils;

$this->appendData([
    'jsObj' => [
        'uploadMaxFilesize' => $this->helper->File()->getBytes($this->config['mod.'.$this->controller.'.img.uploadMaxFilesize'] ?? $this->config['media.img.uploadMaxFilesize'] ?? \Safe\ini_get('upload_max_filesize')),
        'textErrorFilesize' => __('The selected file exceeds the allowed size.'),
        'textErrorFilesizes' => __('The sum of the selected files exceeds the allowable size.'),
        'textErrorMimetype' => __('The selected file type is not allowed.'),
    ],
]);

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

if ('' !== trim((string) $this->Mod->{$key})) {
    echo '<div class="d-flex mb-3">'.PHP_EOL;

    echo '<a data-fancybox title=""'.$this->escapeAttr([
        'href' => $this->asset('media/img/'.$this->controller.'/lg/'.$this->Mod->{$key}),
    ]).'>'.PHP_EOL;
    echo '<img class="img-thumbnail" alt=""'.$this->escapeAttr([
        'src' => $this->asset('media/img/'.$this->controller.'/xs/'.$this->Mod->{$key}),
    ]).'>'.PHP_EOL;
    echo '</a>'.PHP_EOL;

    if (empty($val[$this->env]['attr']['required'])) {
        echo '<div class="flex-grow-1 ms-3">'.PHP_EOL;
        echo '<div'.$this->escapeAttr(['class' => array_merge(['form-check'], !empty($this->config['theme.checkbox.switches']) ? ['form-switch'] : [])]).'>'.PHP_EOL;

        echo $this->helper->Html()->getFormField([
            'type' => 'input',
            'attr' => [
                'type' => 'checkbox',
                'id' => 'unlink-'.$key,
                'name' => 'unlink_'.$key,
                'value' => 1,
                'class' => ['form-check-input'],
            ],
        ]);

        echo '<label class="form-check-label"'.$this->escapeAttr(['for' => $this->helper->Nette()->Strings()->webalize('unlink-'.$key)]).'>'.$this->escape()->html(__('delete')).'</label>'.PHP_EOL;

        echo '</div>'.PHP_EOL;
        echo '</div>'.PHP_EOL;
    }

    echo '</div>'.PHP_EOL;
}

if (!empty($val[$this->env]['attr']['data-maxFileSize'])) {
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
            'value' => $val[$this->env]['attr']['data-maxFileSize'],
        ],
    ]);
}

$params = [];

$params['attr']['name'] = $key;

if (!empty($val[$this->env]['attr']['required']) && !empty($this->Mod->{$key})) {
    $params['attr']['required'] = false;
}

$params = ArrayUtils::merge($val[$this->env], $params);

if (!empty($params['help'])) {
    $params['attr']['aria-labelledby'] = 'help-'.$this->helper->Nette()->Strings()->webalize($key);
}

echo $this->helper->Html()->getFormField($params);

echo '<div class="invalid-feedback"></div>'.PHP_EOL;

if (!empty($params['help'])) {
    echo '<div'.$this->escapeAttr([
        'id' => 'help-'.$this->helper->Nette()->Strings()->webalize($key),
        'class' => ['form-text'],
    ]).'>'.nl2br(is_array($params['help']) ? implode(PHP_EOL, $params['help']) : $params['help']).'</div>'.PHP_EOL;
}

echo '</div>'.PHP_EOL;
echo '</div>'.PHP_EOL;
