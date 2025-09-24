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

class System extends Helper
{
    // https://www.php.net/manual/en/function.memory-get-usage.php#120665
    // https://stackoverflow.com/a/39599873/3929620
    // Returns used memory either in percent (without percent sign) or free and overall in bytes
    public function getMemoryUsage($percentage = true)
    {
        $memoryTotal = null;
        $memoryFree = null;

        if (stristr(PHP_OS, 'win')) {
            // Get total physical memory (this is in bytes)
            $cmd = 'wmic ComputerSystem get TotalPhysicalMemory';
            @\Safe\exec($cmd, $outputTotalPhysicalMemory);

            // Get free physical memory (this is in kibibytes!)
            $cmd = 'wmic OS get FreePhysicalMemory';
            @\Safe\exec($cmd, $outputFreePhysicalMemory);

            if ($outputTotalPhysicalMemory && $outputFreePhysicalMemory) {
                // Find total value
                foreach ($outputTotalPhysicalMemory as $line) {
                    if ($line && \Safe\preg_match('/^[0-9]+$/', $line)) {
                        $memoryTotal = $line;

                        break;
                    }
                }

                // Find free value
                foreach ($outputFreePhysicalMemory as $line) {
                    if ($line && \Safe\preg_match('/^[0-9]+$/', $line)) {
                        $memoryFree = $line;
                        $memoryFree *= 1024;  // convert from kibibytes to bytes

                        break;
                    }
                }
            }
        } else {
            if (is_readable('/proc/meminfo')) {
                $stats = @\Safe\file_get_contents('/proc/meminfo');

                if (false !== $stats) {
                    // Separate lines
                    $stats = str_replace(["\r\n", "\n\r", "\r"], "\n", $stats);
                    $stats = explode("\n", $stats);

                    // Separate values and find correct lines for total and free mem
                    foreach ($stats as $statLine) {
                        $statLineData = explode(':', trim($statLine));

                        //
                        // Extract size (TODO: It seems that (at least) the two values for total and free memory have the unit "kB" always. Is this correct?
                        //

                        // Total memory
                        if (2 === \count($statLineData) && 'MemTotal' === trim($statLineData[0])) {
                            $memoryTotal = trim($statLineData[1]);
                            $memoryTotal = explode(' ', $memoryTotal);
                            $memoryTotal = $memoryTotal[0];
                            $memoryTotal *= 1024;  // convert from kibibytes to bytes
                        }

                        // Free memory
                        if (2 === \count($statLineData) && 'MemFree' === trim($statLineData[0])) {
                            $memoryFree = trim($statLineData[1]);
                            $memoryFree = explode(' ', $memoryFree);
                            $memoryFree = $memoryFree[0];
                            $memoryFree *= 1024;  // convert from kibibytes to bytes
                        }
                    }
                }
            }
        }

        if (null === $memoryTotal || null === $memoryFree) {
            return null;
        } else {
            if ($percentage) {
                return 100 - ($memoryFree * 100 / $memoryTotal);
            } else {
                return [
                    'total' => $memoryTotal,
                    'free' => $memoryFree,
                ];
            }
        }
    }

    // https://www.php.net/manual/en/function.sys-getloadavg.php#118673
    // Returns current CPU load as percentage (just number, without percent sign) value under Windows and Linux.
    // It will return a decimal value as percentage of current CPU load or NULL if something went wrong (e. g. insufficient access rights).
    public function getCpuUsage()
    {
        $load = null;

        if (stristr(PHP_OS, 'win')) {
            $cmd = 'wmic cpu get loadpercentage /all';
            @\Safe\exec($cmd, $output);

            if ($output) {
                foreach ($output as $line) {
                    if ($line && \Safe\preg_match('/^[0-9]+$/', $line)) {
                        $load = $line;

                        break;
                    }
                }
            }
        } else {
            if (is_readable('/proc/stat')) {
                // Collect 2 samples - each with 1 second period
                // See: https://de.wikipedia.org/wiki/Load#Der_Load_Average_auf_Unix-Systemen
                $statData1 = $this->_getServerLoadLinuxData();
                sleep(1);
                $statData2 = $this->_getServerLoadLinuxData();

                if (
                    (null !== $statData1)
                    && (null !== $statData2)
                ) {
                    // Get difference
                    $statData2[0] -= $statData1[0];
                    $statData2[1] -= $statData1[1];
                    $statData2[2] -= $statData1[2];
                    $statData2[3] -= $statData1[3];

                    // Sum up the 4 values for User, Nice, System and Idle and calculate
                    // the percentage of idle time (which is part of the 4 values!)
                    $cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];

                    // Invert percentage to get CPU time, not idle time
                    $load = 100 - ($statData2[3] * 100 / $cpuTime);
                }
            }
        }

        return $load;
    }

    private function _getServerLoadLinuxData()
    {
        if (is_readable('/proc/stat')) {
            $stats = @\Safe\file_get_contents('/proc/stat');

            if (false !== $stats) {
                // Remove double spaces to make it easier to extract values with explode()
                $stats = \Safe\preg_replace('/[[:blank:]]+/', ' ', $stats);

                // Separate lines
                $stats = str_replace(["\r\n", "\n\r", "\r"], "\n", $stats);
                $stats = explode("\n", $stats);

                // Separate values and find line for main CPU load
                foreach ($stats as $statLine) {
                    $statLineData = explode(' ', trim($statLine));

                    // Found!
                    if (
                        (\count($statLineData) >= 5)
                        && ('cpu' === $statLineData[0])
                    ) {
                        return [
                            $statLineData[1],
                            $statLineData[2],
                            $statLineData[3],
                            $statLineData[4],
                        ];
                    }
                }
            }
        }

        return null;
    }
}
