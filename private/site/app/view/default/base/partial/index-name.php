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
<td><?php
if ('' !== trim((string) $row[$key])) {
    echo '<a'.$this->escapeAttr([
        'href' => $this->uri([
            'routeName' => $this->env.'.'.$this->controller.'.params',
            'data' => [
                'action' => 'edit',
                'params' => $row['id'],
            ],
        ]),
        'title' => __('edit'),
    ]).'>'.$this->escape()->html($this->helper->Nette()->Strings()->truncate($row[$key], 80)).'</a>';
}
?></td>
