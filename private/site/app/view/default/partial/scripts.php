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

if (!$this->config['debug.enabled']) {
    $this->scripts()->beginInternal();
    echo 'console.log = function () {};
';
    $this->scripts()->endInternal();
}

if (!empty($this->browscapInfo)) {
    // https://stackoverflow.com/a/326076
    // https://stackoverflow.com/a/14061171
    $this->scripts()->beginInternal();
    if (!empty($this->browscapInfo->ismobiledevice)) {
        echo 'try {
        if(window.self !== window.top) {
            window.top.location.href = "'.$this->escape()->js($this->uri($this->env.'.index')).'";
        }
    } catch (e) {}';
    }
    $this->scripts()->endInternal();
}

echo $this->scripts();
