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

if (!empty($this->config['credits.asset.url'])) {
    // https://realfavicongenerator.net
    ?>
    <link rel="apple-touch-icon" sizes="180x180"<?php echo $this->escapeAttr([
        'href' => $this->config['credits.asset.url'].'/img/favicon/apple-touch-icon.png',
    ]); ?>>
    <link rel="icon" type="image/png" sizes="32x32"<?php echo $this->escapeAttr([
        'href' => $this->config['credits.asset.url'].'/img/favicon/favicon-32x32.png',
    ]); ?>>
    <link rel="icon" type="image/png" sizes="16x16"<?php echo $this->escapeAttr([
        'href' => $this->config['credits.asset.url'].'/img/favicon/favicon-16x16.png',
    ]); ?>>
    <link rel="icon" type="image/x-icon" sizes="48x48"<?php echo $this->escapeAttr([
        'href' => $this->config['credits.asset.url'].'/img/favicon/favicon.ico',
    ]); ?>>
    <link rel="manifest"<?php echo $this->escapeAttr([
        'href' => $this->asset('asset/'.$this->env.'/site.webmanifest'),
    ]); ?>>
    <link rel="mask-icon" color="#ffffff"<?php echo $this->escapeAttr([
        'href' => $this->config['credits.asset.url'].'/img/favicon/safari-pinned-tab.svg',
    ]); ?>>
    <meta name="msapplication-TileColor" content="#ffffff">
    <meta name="theme-color" content="#ffffff">
<?php
} elseif (file_exists(_ROOT.'/app/view/default/partial/'.basename(__FILE__))) {
    include _ROOT.'/app/view/default/partial/'.basename(__FILE__);
}
