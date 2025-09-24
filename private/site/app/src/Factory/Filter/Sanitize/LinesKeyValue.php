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

namespace App\Factory\Filter\Sanitize;

use App\Helper\HelperInterface;
use Psr\Container\ContainerInterface;

class LinesKeyValue
{
    public function __construct(
        protected ContainerInterface $container,
        protected HelperInterface $helper,
    ) {}

    public function __invoke($subject, $field)
    {
        $value = $subject->{$field};

        $lines = $this->helper->Arrays()->splitStringToArray($value);

        if (\is_array($lines)) {
            $newLines = [];
            foreach ($lines as $line) {
                if (1 === substr_count((string) $line, '|')) {
                    [$key, $val] = array_map('trim', explode('|', (string) $line));
                    if (!empty($key) && !is_numeric($key)) { // <-- not isBlank() here
                        $key = $this->helper->Nette()->Strings()->webalize((string) $key);
                    }
                    $newLines[] = $key.'|'.$val;
                } else {
                    $newLines[] = trim((string) $line);
                }
            }

            $subject->{$field} = implode(PHP_EOL, $newLines);

            return true;
        }

        return false;
    }
}
