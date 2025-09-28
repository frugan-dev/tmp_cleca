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

use App\Factory\Cache\CacheInterface;
use Symfony\Component\Process\Process;

class Env extends Helper
{
    public function isDev()
    {
        return (bool) (!empty($_SERVER['APP_ENV']) && str_contains((string) $_SERVER['APP_ENV'], 'develop'));
    }

    public function isDocker()
    {
        return (bool) isset($_SERVER['APP_ENV']);
    }

    // https://www.php.net/manual/en/function.php-sapi-name.php
    public function isCli()
    {
        // https://discourse.slimframework.com/t/using-slim-4-app-in-cli-mode/3669/3
        // https://stackoverflow.com/a/8690491/3929620
        return (bool) \in_array(\PHP_SAPI, ['cli', 'cli-server'], true);
    }

    // https://www.php.net/manual/en/function.php-sapi-name.php
    public function isCgi()
    {
        return (bool) \in_array(\PHP_SAPI, ['cgi', 'cgi-fcgi'], true);
    }

    // https://www.php.net/manual/en/function.php-sapi-name.php
    public function isFpm()
    {
        return (bool) \in_array(\PHP_SAPI, ['fpm-fcgi'], true);
    }

    public function settingOrConfig(array|string $keys)
    {
        if ($this->container->has('setting')) {
            if (\is_array($keys)) {
                foreach ($keys as $key) {
                    if (isset($this->container->get('setting')[$key]['option_lang']['value'])) {
                        $return = $this->container->get('setting')[$key]['option_lang']['value'];
                    } elseif (isset($this->container->get('setting')[$key]['option']['value'])) {
                        $return = $this->container->get('setting')[$key]['option']['value'];
                    }

                    if (isset($return)) {
                        break;
                    }
                }
            } elseif (isset($this->container->get('setting')[$keys]['option_lang']['value'])) {
                $return = $this->container->get('setting')[$keys]['option_lang']['value'];
            } elseif (isset($this->container->get('setting')[$keys]['option']['value'])) {
                $return = $this->container->get('setting')[$keys]['option']['value'];
            }
        }

        if (!isset($return)) {
            if (\is_array($keys)) {
                foreach ($keys as $key) {
                    if ($this->config->has($key)) {
                        $return = $this->config->get($key);

                        break;
                    }
                }
            } elseif ($this->config->has($keys)) {
                $return = $this->config->get($keys);
            }
        }

        return $return ?? null;
    }

    // http://semver.org
    // https://stackoverflow.com/a/33986403/3929620
    // https://stackoverflow.com/a/12142066/3929620
    // https://liquidsoftware.com/blog/the-7-deadly-sins-of-versioning-part-2/
    public function version()
    {
        if (\defined('_APP_VERSION')) {
            $version = _APP_VERSION;
        } elseif (!empty($this->config->get('app.version'))) {
            $version = $this->config->get('app.version');
        } elseif (!empty($this->config->get('git.local.path')) && is_dir($this->config->get('git.local.path').'/.git')) {
            $cache = $this->container->get(CacheInterface::class);

            if (!empty($cache)) {
                $cacheKey = __FUNCTION__.'.'.\Safe\filemtime($this->config->get('git.local.path').'/.git');

                $cacheItem = $cache->getItem($cacheKey);

                if ($cacheItem->isHit()) {
                    $version = $cacheItem->get();
                }
            }

            if (empty($version)) {
                try {
                    // FIXED - FastCGI fatal: detected dubious ownership in repository at '<path to the repository>'
                    // https://confluence.atlassian.com/bbkb/git-command-returns-fatal-error-about-the-repository-being-owned-by-someone-else-1167744132.html
                    // https://archive.virtualmin.com/node/51885
                    // The mustRun() method is identical to run(), except that it will throw a Symfony\Component\Process\Exception\ProcessFailedException if the process couldn’t be executed successfully (i.e. the process exited with a non-zero code)
                    $Process = new Process(['git', '-C', $this->config->get('git.local.path'), 'describe', '--tags', '--abbrev=0']);

                    // https://stackoverflow.com/a/61016204/3929620
                    // $Process->setTimeout(null);
                    // $Process->setIdleTimeout(null);

                    $Process->run();

                    if ($Process->isSuccessful()) {
                        $tag = trim($Process->getOutput());

                        $Process = new Process(['git', '-C', $this->config->get('git.local.path'), 'log', '--pretty="%h"', '-n1', 'HEAD']);

                        // https://stackoverflow.com/a/61016204/3929620
                        // $Process->setTimeout(null);
                        // $Process->setIdleTimeout(null);

                        $Process->run();

                        if ($Process->isSuccessful()) {
                            $hash = trim($Process->getOutput());

                            // FIXME - https://stackoverflow.com/a/7128879/3929620
                            $hash = html_entity_decode($hash);
                            $hash = \Safe\preg_replace('/[^a-z0-9]/', '', $hash);

                            /*$Process = new Process(['git', '-C', $this->config->get('git.local.path'), 'log', '-n1', '--pretty=%ci', 'HEAD']);

                            //https://stackoverflow.com/a/61016204/3929620
                            //$Process->setTimeout(null);
                            //$Process->setIdleTimeout(null);

                            $Process->run();

                            if ($Process->isSuccessful()) {*/
                            // $datetime = substr(trim( $Process->getOutput() ), 0, 19);

                            $Process = new Process(['git', '-C', $this->config->get('git.local.path'), 'rev-parse', '--abbrev-ref', 'HEAD']);

                            // https://stackoverflow.com/a/61016204/3929620
                            // $Process->setTimeout(null);
                            // $Process->setIdleTimeout(null);

                            $Process->run();

                            if ($Process->isSuccessful()) {
                                $branch = trim($Process->getOutput());

                                // $version = sprintf('%1$s+sha.%2$s (%3$s %4$s)', $tag, $hash, $this->Carbon()->createFromFormat('Y-m-d H:i:s', $datetime, $this->config->get('db.1.timeZone')));
                                $version = \sprintf('%1$s+sha.%2$s', $tag, $hash);

                                if ('master' !== $branch) {
                                    $version .= ' '.\sprintf('[%1$s]', $branch);
                                }
                            }
                            // }
                        }
                    }
                } catch (\Exception $e) {
                    $this->logger->debug($e->getMessage(), [
                        'exception' => $e,
                    ]);
                }

                if (!empty($cache) && !empty($version)) {
                    $cache->saveItem($cacheItem, $version);
                }
            }
        }

        if (!empty($version)) {
            return $version;
        }

        return '== no value ==';
    }

    // https://www.php.net/manual/en/errorfunc.constants.php#123958
    public function getErrorTypeByValue(int $type)
    {
        $constants = get_defined_constants(true);

        foreach ($constants['Core'] as $key => $value) {
            if (\Safe\preg_match('/^E_/', (string) $key)) {
                if ($type === $value) {
                    return "{$key}={$value}";
                }
            }
        }

        return $type;
    }
}
