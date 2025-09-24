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

namespace App\Helper;

class Cli extends Helper
{
    // https://developer.wordpress.org/reference/functions/get_cli_args/
    public function setCliArgs($opts = [])
    {
        $args = [];

        if ($this->Env()->isCli()) {
            $shorts = '';
            $longs = [];

            foreach ($opts as $key => $val) {
                $val = array_merge(
                    [
                        'short' => '',
                        'suffix' => '',
                        'descr' => '',
                    ],
                    $val
                );

                $shorts .= $val['short'].$val['suffix'];

                $longs[] = $key.$val['suffix'];
            }

            $args = \Safe\getopt($shorts, $longs);

            foreach ($opts as $key => $val) {
                if (isset($args[$val['short']])) {
                    $args[$key] = $args[$val['short']];

                    unset($args[$val['short']]);
                } elseif (!isset($args[$key])) {
                    $args[$key] = null;
                }
            }
        }

        return $args;
    }

    public function getCliHelp($opts = [])
    {
        global $argv;

        $buffer = '';

        if ($this->Env()->isCli()) {
            $php_path = trim((string) \Safe\shell_exec('which php'));

            $buffer .= \sprintf(__('Usage: %1$s'), $php_path.' -f '.$argv[0].' -- <options>');

            $buffer .= PHP_EOL.PHP_EOL.__('Options').':';

            foreach ($opts as $key => $val) {
                $val = array_merge(
                    [
                        'short' => '',
                        'suffix' => '',
                        'descr' => '',
                    ],
                    $val
                );

                $string = PHP_EOL.'-'.$val['short'].', --'.$key;

                if (':' === $val['suffix']) {
                    $string .= '='.mb_strtoupper((string) $key, 'UTF-8');
                } elseif ('::' === $val['suffix']) {
                    $string .= '[='.mb_strtoupper((string) $key, 'UTF-8').']';
                }

                $buffer .= str_pad($string, 50);

                $buffer .= $val['descr'];
            }

            $buffer .= PHP_EOL;
        }

        return $buffer;
    }
}
