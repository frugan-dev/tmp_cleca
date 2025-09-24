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

use League\Flysystem\Filesystem as Flysystem;
use League\Flysystem\ZipArchive\FilesystemZipArchiveProvider;
use League\Flysystem\ZipArchive\ZipArchiveAdapter;
use Safe\Exceptions\ExecException;
use Safe\Exceptions\FilesystemException;

class File extends Helper
{
    // FIXED - Declaration of App\Helper\File::getFileName($filename) should be compatible with App\Model\Model::getFileName()
    public function getFileBasename($filename)
    {
        $filename = basename((string) $filename);

        $info = pathinfo($filename);

        return $info['filename'];
    }

    public function getFileExt($filename)
    {
        $filename = basename((string) $filename);

        $info = pathinfo($filename);

        return $this->Nette()->Strings()->lower($info['extension']);
    }

    // https://gist.github.com/liunian/9338301
    public function formatSize($size)
    {
        $size = $this->getBytes($size);

        for ($i = 0; ($size / 1024) > 0.9; $i++, $size /= 1024);

        return round($size, [0, 0, 1, 2, 2, 3, 3, 4, 4][$i]).' '.['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'][$i];
    }

    // https://www.php.net/manual/en/function.ini-get.php
    // https://stackoverflow.com/a/46320238/3929620
    // https://stackoverflow.com/a/44767616/3929620
    public function getBytes($value)
    {
        if (!\is_string($value)) {
            return $value;
        }

        \Safe\preg_match('/^(?<value>\d+)(?<option>[K|M|G]*)$/i', $value, $matches);

        $value = (int) $matches['value'];
        $option = strtoupper((string) $matches['option']);

        if ($option) {
            if ('K' === $option) {
                $value *= 1024;
            } elseif ('M' === $option) {
                $value *= 1024 * 1024;
            } elseif ('G' === $option) {
                $value *= 1024 * 1024 * 1024;
            }
        }

        return $value;
    }

    // https://star-history.com/#alchemy-fr/Zippy&Ne-Lexa/php-zip&Date
    public function archive(array $items, string $dest, $flags = \ZipArchive::CREATE | \ZipArchive::OVERWRITE)
    {
        $return = false;

        try {
            $ext = $this->getFileExt($dest);

            if ('zip' === $ext) {
                if (class_exists(ZipArchiveAdapter::class)) {
                    // FIXME
                    // https://github.com/thephpleague/flysystem-ziparchive/issues/22
                    // https://stackoverflow.com/a/26247519/3929620
                    if ($flags & \ZipArchive::OVERWRITE && file_exists($dest)) {
                        $this->Nette()->FileSystem()->delete($dest);
                    }

                    $adapter = new ZipArchiveAdapter(new FilesystemZipArchiveProvider($dest));
                    $filesystem = new Flysystem($adapter);
                } elseif (class_exists('ZipArchive', false)) {
                    $zipArchive = new \ZipArchive();
                    if (($error = $zipArchive->open($dest, $flags)) !== true) {
                        throw new \Exception((string) $error);
                    }
                } else {
                    throw new \Exception("Unsupported archive extension: {$ext}");
                }
            } elseif (\in_array($ext, ['bz2', 'gz', 'tar', 'tgz'], true)) {
                if (class_exists('PharData', false)) {
                    if (\in_array($ext, ['gz', 'tgz'], true) && !\extension_loaded('zlib')) {
                        throw new \Exception('zlib extension is required for .gz/.tgz compression.');
                    }
                    if (\in_array($ext, ['bz2'], true) && !\extension_loaded('bzip2')) {
                        throw new \Exception('bzip2 extension is required for .bz2 compression.');
                    }
                    if ($flags & \ZipArchive::OVERWRITE && file_exists($dest)) {
                        $this->Nette()->FileSystem()->delete($dest);
                    }
                    $PharData = new \PharData($dest);
                } else {
                    throw new \Exception("Unsupported archive extension: {$ext}");
                }
            } else {
                throw new \Exception("Unsupported archive extension: {$ext}");
            }

            foreach ($items as $item) {
                $item = !\is_array($item) ? [$item => ''] : $item;

                foreach ($item as $key => $val) {
                    if (\is_string($key) && \is_string($val)) {
                        if (is_file($key)) {
                            $archivePath = trim($val, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.basename((string) $key);

                            if (!empty($filesystem)) {
                                $stream = \Safe\fopen($key, 'r+');

                                if (\is_resource($stream)) {
                                    $filesystem->writeStream($archivePath, $stream);

                                    \Safe\fclose($stream);
                                }
                            } elseif (!empty($zipArchive)) {
                                $zipArchive->addFile($key, $archivePath);
                            } elseif (!empty($PharData)) {
                                $PharData->addFile($key, $archivePath);
                            }
                        } elseif (is_dir($key)) {
                            // in() searches only the current directory, while from() searches its subdirectories too (recursively)
                            foreach ($this->Nette()->Finder()->findFiles('*')->from($key) as $fileObj) {
                                if (!$fileObj->isDir()) {
                                    $filePath = $fileObj->getRealPath();
                                    $archivePath = trim($val, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.ltrim(substr((string) $filePath, \strlen((string) $key) + 1), \DIRECTORY_SEPARATOR);

                                    if (!empty($filesystem)) {
                                        $stream = \Safe\fopen($filePath, 'r+');

                                        if (\is_resource($stream)) {
                                            $filesystem->writeStream($archivePath, $stream);

                                            \Safe\fclose($stream);
                                        }
                                    } elseif (!empty($zipArchive)) {
                                        $zipArchive->addFile($filePath, $archivePath);
                                    } elseif (!empty($PharData)) {
                                        $PharData->addFile($filePath, $archivePath);
                                    }
                                }
                            }
                        } elseif (!isBlank($key)) {
                            $archivePath = true;

                            if (!empty($filesystem)) {
                                $filesystem->write($key, $val);
                            } elseif (!empty($zipArchive)) {
                                $zipArchive->addFromString($key, $val);
                            } elseif (!empty($PharData)) {
                                $PharData->addFromString($key, $val);
                            }
                        }
                    } else {
                        throw new \Exception('invalid item');
                    }
                }
            }

            if (!empty($zipArchive)) {
                $zipArchive->close();
            }

            if (empty($archivePath)) {
                throw new \Exception('empty archive');
            }

            $return = true;
        } catch (\Exception $e) {
            $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                'error' => $e->getMessage(),
                'text' => implode(PHP_EOL, [
                    \sprintf('%1$s: %2$s', '$items', var_export($items, true)),
                    \sprintf('%1$s: %2$s', '$dest', $dest),
                ]),
            ]);
        }

        return $return;
    }

    // https://star-history.com/#alchemy-fr/Zippy&Ne-Lexa/php-zip&Date
    public function extract(string $src, string $dest, array|string|null $items = null): bool
    {
        $return = false;

        try {
            $ext = $this->getFileExt($src);

            if ('zip' === $ext) {
                if (class_exists(ZipArchiveAdapter::class)) {
                    $adapter = new ZipArchiveAdapter(new FilesystemZipArchiveProvider($src));
                    $filesystem = new Flysystem($adapter);

                    $contents = $filesystem->listContents('', true);
                    foreach ($contents as $file) {
                        if ('file' === $file['type']) {
                            $stream = $filesystem->readStream($file['path']);
                            $destPath = rtrim($dest, \DIRECTORY_SEPARATOR).\DIRECTORY_SEPARATOR.$file['path'];

                            if (!is_dir(\dirname($destPath))) {
                                \Safe\mkdir(\dirname($destPath), 0o777, true);
                            }

                            $destStream = \Safe\fopen($destPath, 'w');
                            if ($destStream) {
                                \Safe\stream_copy_to_stream($stream, $destStream);
                                \Safe\fclose($destStream);
                            }
                            \Safe\fclose($stream);
                        }
                    }
                } elseif (class_exists('ZipArchive', false)) {
                    $ZipArchive = new \ZipArchive();
                    if (($error = $ZipArchive->open($src)) === true) {
                        $ZipArchive->extractTo($dest, $items);
                        $ZipArchive->close();
                    } else {
                        throw new \Exception((string) $error);
                    }
                } else {
                    throw new \Exception("Unsupported archive extension: {$ext}");
                }
            } elseif (\in_array($ext, ['bz2', 'gz', 'tar', 'tgz'], true)) {
                if (class_exists('PharData', false)) {
                    if (\in_array($ext, ['gz', 'tgz'], true) && !\extension_loaded('zlib')) {
                        throw new \Exception('zlib extension is required for .gz decompression.');
                    }
                    if (\in_array($ext, ['bz2'], true) && !\extension_loaded('bzip2')) {
                        throw new \Exception('bzip2 extension is required for .bz2 decompression.');
                    }
                    $PharData = new \PharData($src);
                    $PharData->extractTo($dest, $items, true);
                } else {
                    throw new \Exception("Unsupported archive extension: {$ext}");
                }
            } else {
                throw new \Exception("Unsupported archive extension: {$ext}");
            }

            $return = true;
        } catch (\Exception $e) {
            $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                'error' => $e->getMessage(),
                'text' => implode(PHP_EOL, [
                    \sprintf('%1$s: %2$s', '$src', $src),
                    \sprintf('%1$s: %2$s', '$dest', $dest),
                ]),
            ]);
        }

        return $return;
    }

    public function symlink(string $src, string $dest)
    {
        try {
            \Safe\symlink($src, $dest);

            return true;
        } catch (FilesystemException $e) {
            $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                'error' => $e->getMessage(),
                'text' => implode(PHP_EOL, [
                    \sprintf('%1$s: %2$s', '$src', $src),
                    \sprintf('%1$s: %2$s', '$dest', $dest),
                ]),
            ]);
        }

        try {
            \Safe\exec('ln -s '.escapeshellarg($src).' '.escapeshellarg($dest), $output, $result);

            return true;
        } catch (ExecException $e) {
            $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                'error' => $e->getMessage(),
                'text' => implode(PHP_EOL, [
                    \sprintf('%1$s: %2$s', '$src', $src),
                    \sprintf('%1$s: %2$s', '$dest', $dest),
                ]),
            ]);
        }

        return false;
    }
}
