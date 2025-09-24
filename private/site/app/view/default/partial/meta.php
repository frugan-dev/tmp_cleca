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
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="mobile-web-app-capable" content="yes">
<?php // https://blog.mzikmund.com/2015/08/removing-touch-highlights-on-smartphones/?>
<meta name="msapplication-tap-highlight" content="no">
<?php if (!empty($this->metaDescription)) { ?>
    <meta name="description"<?php echo $this->escapeAttr([
        'content' => $this->metaDescription,
    ]); ?>>
<?php } ?>
<?php if (!empty($this->metaKeywords)) { ?>
    <meta name="keywords"<?php echo $this->escapeAttr([
        'content' => $this->metaKeywords,
    ]); ?>>
<?php } ?>
<meta property="og:title"<?php echo $this->escapeAttr([
    'content' => $this->metaTitle,
]); ?>>
<?php if (!empty($this->metaDescription)) { ?>
    <meta property="og:description"<?php echo $this->escapeAttr([
        'content' => $this->metaDescription,
    ]); ?>>
<?php } ?>
<?php if (!empty($this->metaImage)) { ?>
    <meta property="og:image"<?php echo $this->escapeAttr([
        'content' => $this->metaImage,
    ]); ?>>
<?php } ?>
<?php if (!empty($this->fullUrl)) { ?>
    <meta property="og:url"<?php echo $this->escapeAttr([
        'content' => $this->fullUrl,
    ]); ?>>
    <link rel="canonical"<?php echo $this->escapeAttr([
        'href' => $this->fullUrl,
    ]); ?>>
<?php } ?>
<?php
// https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Language
if ((is_countable($this->routeArgsArr) ? count($this->routeArgsArr) : 0) > 1) {
    $langArr = $this->lang->arr;

    if ($this->lang->id !== array_key_first($langArr)) {
        uksort($langArr, function ($a, $b) {
            if ($a === $this->lang->id) {
                return -1;
            }
            if ($b === $this->lang->id) {
                return 1;
            }

            return 0;
        });
    }

    foreach ($langArr as $langId => $langRow) {
        if ($langId === $this->lang->id) {
            ?>
    <link rel="alternate" hreflang="x-default"<?php echo $this->escapeAttr([
        'href' => $this->uri(array_merge(
            $this->routeArgsArr[$langId],
            [
                'full' => true,
            ]
        )),
    ]); ?>>
<?php } ?>
    <link rel="alternate"<?php echo $this->escapeAttr([
        'hreflang' => $langRow['isoCode'],
        'href' => $this->uri(array_merge(
            $this->routeArgsArr[$langId],
            [
                'full' => true,
            ]
        )),
    ]); ?>>
<?php
    }
}
