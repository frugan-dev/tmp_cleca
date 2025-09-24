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

if (!empty($items = $this->Mod->{$key}['teachers'])) {
    if (!empty($this->teacherKey)) {
        $items = [$this->Mod->{$key}['teachers'][$this->teacherKey]];
    }

    echo '<div class="table-responsive">'.PHP_EOL;
    echo '<table class="table table-bordered table-hover table-sm small caption-top">'.PHP_EOL;

    echo '<caption class="fw-bold">'.PHP_EOL;
    echo $val[$this->env]['label']; // <-- no escape, it can contain html tags
    echo '</caption>'.PHP_EOL;

    echo '<thead class="table-light">'.PHP_EOL;
    echo '<tr>'.PHP_EOL;

    echo '<th scope="col" class="text-nowrap">'.$this->escape()->html(__('ID')).'</th>'.PHP_EOL;
    echo '<th scope="col" class="text-nowrap">'.$this->escape()->html(__('Lastname')).'</th>'.PHP_EOL;
    echo '<th scope="col" class="text-nowrap">'.$this->escape()->html(__('Firstname')).'</th>'.PHP_EOL;
    echo '<th scope="col" class="text-nowrap">'.$this->escape()->html(__('Email')).'</th>'.PHP_EOL;
    echo '<th scope="col" class="text-nowrap">'.$this->escape()->html(__('Notification date')).'</th>'.PHP_EOL;
    echo '<th scope="col" class="text-nowrap">'.$this->escape()->html(__('Modification date')).'</th>'.PHP_EOL;
    echo '<th scope="col" class="text-nowrap">'.$this->escape()->html(_n('File', 'Files', 2)).'</th>'.PHP_EOL;
    echo '<th scope="col" class="text-nowrap">'.$this->escape()->html(__('State')).'</th>'.PHP_EOL;

    echo '</tr>'.PHP_EOL;
    echo '</thead>'.PHP_EOL;

    echo '<tbody>'.PHP_EOL;

    foreach ($items as $item) {
        echo '<tr>'.PHP_EOL;

        echo '<td class="text-nowrap">'.PHP_EOL;
        echo $this->escape()->html($item['id']);
        echo '</td>'.PHP_EOL;

        echo '<td class="text-nowrap">'.PHP_EOL;
        echo $this->escape()->html($item['lastname']);
        echo '</td>'.PHP_EOL;

        echo '<td class="text-nowrap">'.PHP_EOL;
        echo $this->escape()->html($item['firstname']);
        echo '</td>'.PHP_EOL;

        echo '<td class="text-nowrap">'.PHP_EOL;
        echo '<a title=""'.$this->escapeAttr([
            'href' => 'mailto:'.$item['email'],
        ]).'>'.$this->escape()->html($item['email']).'</a>';
        echo '</td>'.PHP_EOL;

        echo '<td class="text-nowrap">'.PHP_EOL;
        if (!empty($item['ldate'])) {
            $obj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $item['ldate'], $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

            echo $obj->toDateTimeString().'<br><small class="text-muted">('.$obj->timezone.')</small>';
        }
        echo '</td>'.PHP_EOL;

        echo '<td class="text-nowrap">'.PHP_EOL;
        if (!empty($item['mdate'])) {
            $obj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $item['mdate'], $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

            echo $obj->toDateTimeString().'<br><small class="text-muted">('.$obj->timezone.')</small>';
        }
        echo '</td>'.PHP_EOL;

        echo '<td class="text-nowrap">'.PHP_EOL;
        if (!empty($item['files'])) {
            echo '<ol class="mb-0">';
            foreach ($item['files'] as $crc32 => $file) {
                echo '<li>';
                echo $this->escape()->html($file['name']).' <i class="small">('.$this->helper->File()->formatSize($file['size']).')</i>';
                echo '</li>';
            }
            echo '</ol>'.PHP_EOL;
        }
        echo '</td>'.PHP_EOL;

        echo '<td class="text-nowrap">'.PHP_EOL;
        if (!empty($item['status'])) {
            echo '<i class="fas fa-check fa-lg fa-fw text-success"></i>'.PHP_EOL;
        } else {
            echo '<i class="fas fa-times fa-lg fa-fw text-danger"></i>'.PHP_EOL;
        }
        echo '</td>'.PHP_EOL;

        echo '</tr>'.PHP_EOL;
    }

    echo '</tbody>'.PHP_EOL;

    echo '</table>'.PHP_EOL;
    echo '</div>'.PHP_EOL;
}
