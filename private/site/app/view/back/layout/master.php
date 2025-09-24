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

$this->appendData([
    'htmlAttr' => [
        'lang' => $this->lang->code, // https://getbootstrap.com/docs/4.3/components/forms/#translating-or-customizing-the-strings-with-scss
        'class' => [$this->lang->code, $this->getLayout(), $this->controller, $this->action, 'no-js', 'h-100'],
        'itemscope' => true, // http://schema.org/docs/full.html
        'data-bs-theme' => 'light', // https://getbootstrap.com/docs/5.3/customize/color-modes/
    ],
    'bodyAttr' => [
        'id' => 'anchor-top',
        'class' => array_merge([$this->lang->code, $this->getLayout(), $this->controller, $this->action, 'd-flex', 'flex-column', 'h-100', 'collapse'], (isset($_COOKIE[$this->config['cookie.sidebar.name']]) && 'true' === $_COOKIE[$this->config['cookie.sidebar.name']]) ? ['show'] : []),
    ],
    'jsObj' => [
        'env' => $this->env,
        'lang' => $this->lang->code,
        'locale' => $this->lang->locale,
        'localeCharset' => $this->lang->localeCharset,
        'timeZone' => date_default_timezone_get(),
        'baseUrl' => $this->helper->Url()->getBaseUrl(),
        'apiEndpoint' => $this->uri('api'),
        'recaptchaAction' => \Safe\preg_replace('~\W+~', '', (string) $_SERVER['HTTP_HOST']),
        'recaptchaPublicKey' => $this->config['service.google.recaptcha.publicKey'] ?? null,
        'tinymceApiKey' => $this->config['service.tinymce.apiKey'] ?? null,
        'onlineMessage' => __('Connected to internet.'),
        'offlineMessage' => __('No internet connection.'),
        'waitMessage' => __('Please wait').'&hellip;',
        'loadingMessage' => __('Loading').'&hellip;',
        'tinymceClickToLoadMessage' => __('Click to load rich text editor'),
        'tinymceWillInitializeMessage' => __('TinyMCE will initialize when you click here'),
    ],
]);

// FIXED - before header styles/scripts
if (!$this->hasSection('header')) {
    $this->setSection('header', $this->render('header'));
}

?><!DOCTYPE html>
<html<?php echo $this->escapeAttr($this->htmlAttr ?? []); ?>>
<head>
    <meta charset="utf-8">
    <title><?php echo $this->escape()->html($this->metaTitle); ?></title>

    <?php
    if (!$this->hasSection('meta')) {
        $this->setSection('meta', $this->render('meta'));
    }
echo $this->getSection('meta');

if (!$this->hasSection('styles')) {
    $this->setSection('styles', $this->render('styles'));
}
echo $this->getSection('styles');

if (!$this->hasSection('scripts')) {
    $this->setSection('scripts', $this->render('scripts'));
}
echo $this->getSection('scripts');

if (!$this->hasSection('favicon')) {
    $this->setSection('favicon', $this->render('favicon'));
}
echo $this->getSection('favicon');
?>
</head>
<body<?php echo $this->escapeAttr($this->bodyAttr ?? []); ?>>
<?php
echo $this->getSection('header');

if ($this->hasSection('content')) {
    echo $this->getSection('content');
} else {
    echo $this->getContent();
}

if (!$this->hasSection('footer')) {
    $this->setSection('footer', $this->render('footer'));
}
echo $this->getSection('footer');

if (!$this->hasSection('scripts-foot')) {
    $this->setSection('scripts-foot', $this->render('scripts-foot'));
}
echo $this->getSection('scripts-foot');
?>
</body>
</html>
