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

use RedBeanPHP\Facade as R;
use RedBeanPHP\QueryWriter\MySQL;

// https://github.com/zewa666/RedBean_MysqlBackup
R::ext(basename(__FILE__, '.php'), function (string $file, int $step = 100) {
    if (!R::getWriter() instanceof MySQL) {
        throw new Exception('This plugin only supports MySql.');
    }

    $buffer = '';

    foreach (R::inspect() as $table) {
        // https://stackoverflow.com/a/9565901/3929620
        $buffer .= 'DROP TABLE IF EXISTS '.$table.' CASCADE;'.PHP_EOL;
        $row = R::getRow('SHOW CREATE TABLE '.$table);
        $buffer .= $row['Create Table'].';'.PHP_EOL;

        $fields = R::inspect($table);

        $pdo = R::getPDO();
        $query = $pdo->prepare('SELECT * FROM '.$table);
        $query->execute();

        $n = 0;
        while ($row = $query->fetch()) {
            $parts = [];

            if (is_int($n / $step)) {
                if ($n > 0) {
                    $buffer .= ';'.PHP_EOL;
                }

                $buffer .= 'INSERT INTO '.$table.' VALUES';
            } else {
                $buffer .= ',';
            }

            $buffer .= ' (';

            foreach ($fields as $key => $field) {
                if (null === $row[$key]) {
                    $parts[] = 'NULL';
                } else {
                    $parts[] = $pdo->quote((string) $row[$key]);
                }
            }

            $buffer .= implode(',', $parts).')';

            ++$n;
        }

        if ($n > 0) {
            $buffer .= ';';
        }

        $buffer .= PHP_EOL;
    }

    return \Safe\file_put_contents($file, $buffer);
});
