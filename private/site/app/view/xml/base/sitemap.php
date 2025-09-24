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
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
<?php foreach ($this->lang->arr as $langId => $langRow) { ?>
    <sitemap>
        <loc><?php echo $this->uri([
            'routeName' => 'xml',
            'data' => [
                'lang' => $langRow['isoCode'],
                'slug' => 'sitemap',
            ],
            'full' => true,
        ]); ?></loc>
    </sitemap>
<?php } ?>
</sitemapindex>
