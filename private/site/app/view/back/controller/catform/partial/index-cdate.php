<?php declare(strict_types=1); ?>
<td>
    <?php if ('' !== trim((string) $row[$key])) {
        $obj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $row[$key], $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

        echo $obj->toDateTimeString().' <small class="text-muted">('.$obj->timezone.')</small>';
    } ?>
</td>
