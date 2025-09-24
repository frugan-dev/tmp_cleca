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
?><?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
   <?php echo $this->escapeAttr([
       'xsi:schemaLocation' => array_merge([
           'http://www.sitemaps.org/schemas/sitemap/0.9',
           'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd',
       ], !empty($this->{$this->controller.'Imgs'}) ? [
           'http://www.google.com/schemas/sitemap-image/1.1',
           'http://www.google.com/schemas/sitemap-image/1.1/sitemap-image.xsd',
       ] : []),
       'xmlns:image' => !empty($this->{$this->controller.'Imgs'}) ? [
           'http://www.google.com/schemas/sitemap-image/1.1',
       ] : false,
   ]); ?>>
<?php
if (!empty($this->{$this->controller.'Result'})) {
    foreach ($this->{$this->controller.'Result'} as $key => $val) {
        ?>
<url>
<?php if (!empty($val['routeArgs'])) { ?>
    <loc><?php echo $this->uri($val['routeArgs']); ?></loc>
<?php } ?>
<?php if (!empty($val['lastmod'])) { ?>
    <lastmod><?php echo $this->escape()->html($val['lastmod']); ?></lastmod>
<?php } ?>
<?php if (!empty($val['changefreq'])) { ?>
    <changefreq><?php echo $this->escape()->html($val['changefreq']); ?></changefreq>
<?php } ?>
<?php if (!empty($val['priority'])) { ?>
    <priority><?php echo $this->escape()->html($val['priority']); ?></priority>
<?php } ?>
<?php
        if (!empty($val['imgs'])) {
            foreach ($val['imgs'] as $row) {
                ?>
    <image:image>
<?php if (!empty($row['loc'])) { ?>
        <image:loc><?php echo $this->escape()->html($row['loc']); ?></image:loc>
<?php } ?>
<?php if (!empty($row['caption'])) { ?>
        <image:caption><?php echo $this->escape()->html($row['caption']); ?></image:caption>
<?php } ?>
<?php if (!empty($row['title'])) { ?>
        <image:title><?php echo $this->escape()->html($row['title']); ?></image:title>
<?php } ?>
    </image:image>
<?php
            }
        } ?>
</url>
<?php
    }
}
?>
</urlset>
