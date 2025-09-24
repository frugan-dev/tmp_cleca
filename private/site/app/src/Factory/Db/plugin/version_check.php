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

if (!empty($this->config->get('app.mysql.server.minVersion'))) {
    if (version_compare($this->getDatabaseServerVersion(), (string) $this->config->get('app.mysql.server.minVersion'), '<')) {
        exit(sprintf(
            __('MySQL server %.1f+ required.'),
            $this->config->get('app.mysql.server.minVersion')
        ));
    }
} elseif (!empty($this->config->get('app.mariadb.server.minVersion'))) {
    if (version_compare($this->getDatabaseServerVersion(), (string) $this->config->get('app.mariadb.server.minVersion'), '<')) {
        exit(sprintf(
            __('MariaDB server %.1f+ required.'),
            $this->config->get('app.mariadb.server.minVersion')
        ));
    }
}
