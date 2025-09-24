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

namespace App\Factory\Filter\Validate;

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
            foreach ($lines as $line) {
                if (str_contains((string) $line, '|')) {
                    if (substr_count((string) $line, '|') > 1) {
                        return false;
                    }

                    [$key, $val] = explode('|', (string) $line);
                    if (isBlank($key) || isBlank($val)) {
                        return false;
                    }
                }
            }

            return true;
        }

        return false;
    }
}
