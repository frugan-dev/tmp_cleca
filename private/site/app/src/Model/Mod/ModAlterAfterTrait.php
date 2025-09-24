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

namespace App\Model\Mod;

use Laminas\Stdlib\ArrayUtils;
use Psr\Http\Message\RequestInterface;
use Slim\Psr7\UploadedFile;
use WhiteHat101\Crypt\APR1_MD5;

trait ModAlterAfterTrait
{
    public function alterAfter(
        RequestInterface $request
    ): void {
        $action = $this->action;

        $this->filterValue->sanitize($action, 'string', '-', ' ');
        $this->filterValue->sanitize($action, 'titlecase');
        $this->filterValue->sanitize($action, 'string', ' ', '');

        foreach ($this->postData as $key => $val) {
            $langId = null;
            $filteredKey = $key;

            if ($this->helper->Nette()->Strings()->contains($key, '|')) {
                $keyArr = $this->helper->Nette()->Strings()->split($key, '~\|\s*~');

                if (\is_array($keyArr)) {
                    if (3 === \count($keyArr)) {
                        $langId = (int) $keyArr[1];
                        $filteredKey = $keyArr[2];
                    }
                }
            }

            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
            $this->filterValue->sanitize($filteredKey, 'titlecase');
            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

            if (method_exists($this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)) && \is_callable([$this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)])) {
                \call_user_func_array([$this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)], [$key, $langId]);
            } elseif (method_exists($this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)) && \is_callable([$this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)])) {
                \call_user_func_array([$this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)], [$key, $langId]);
            } elseif (method_exists($this, __FUNCTION__.$action.$filteredKey) && \is_callable([$this, __FUNCTION__.$action.$filteredKey])) {
                \call_user_func_array([$this, __FUNCTION__.$action.$filteredKey], [$key, $langId]);
            } elseif (method_exists($this, __FUNCTION__.$filteredKey) && \is_callable([$this, __FUNCTION__.$filteredKey])) {
                \call_user_func_array([$this, __FUNCTION__.$filteredKey], [$key, $langId]);
            }
        }
    }

    public function alterAfterPassword($key, $langId = null): void
    {
        if (!empty($this->postData[$key])) {
            $algorithm = $this->config['mod.'.$this->modName.'.'.$key.'.auth.password.hash.algorithm'] ?? $this->config['mod.'.$this->modName.'.auth.password.hash.algorithm'] ?? $this->config['auth.password.hash.algorithm'];
            $options = \array_key_exists('mod.'.$this->modName.'.'.$key.'.auth.password.hash.options', $this->config->toArray()) ? $this->config['mod.'.$this->modName.'.'.$key.'.auth.password.hash.options'] : (\array_key_exists('mod.'.$this->modName.'.auth.password.hash.options', $this->config->toArray()) ? $this->config['mod.'.$this->modName.'.auth.password.hash.options'] : $this->config['auth.password.hash.options']); // <--

            $this->postData[$key] = match ($algorithm) {
                'APR1_MD5' => APR1_MD5::hash($this->postData[$key], $options),
                default => password_hash((string) $this->postData[$key], $algorithm, $options),
            };
        }
    }

    public function alterAfterJson($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->postData[$key] = $this->helper->Nette()->Json()->encode($this->postData[$key]);
        }
    }

    public function alterAfterUpload($key, $langId = null, array $params = []): void
    {
        $params = ArrayUtils::merge(
            [
                'subKey' => null,
            ],
            $params
        );

        if (!empty($params['subKey'])) {
            $postValue = $this->postData[$key][$params['subKey']] ?? null;
        } else {
            $postValue = $this->postData[$key] ?? null;
        }

        if (isset($postValue)) {
            $dbValues = $this->config['mod.'.static::$env.'.'.$this->modName.'.'.$key.(isset($params['type']) ? '.'.$params['type'] : '').'.media.db.values'] ?? $this->config['mod.'.$this->modName.'.'.$key.(isset($params['type']) ? '.'.$params['type'] : '').'.media.db.values'] ?? $this->config['mod.'.static::$env.'.'.$this->modName.'.'.$key.'.media.db.values'] ?? $this->config['mod.'.$this->modName.'.'.$key.'.media.db.values'] ?? $this->config['mod.'.static::$env.'.'.$this->modName.(isset($params['type']) ? '.'.$params['type'] : '').'.media.db.values'] ?? $this->config['mod.'.$this->modName.(isset($params['type']) ? '.'.$params['type'] : '').'.media.db.values'] ?? $this->config['mod.'.static::$env.'.'.$this->modName.'.media.db.values'] ?? $this->config['mod.'.$this->modName.'.media.db.values'] ?? $this->config['media.'.static::$env.(isset($params['type']) ? '.'.$params['type'] : '').'.db.values'] ?? $this->config['media'.(isset($params['type']) ? '.'.$params['type'] : '').'.db.values'] ?? $this->config['media.'.static::$env.'.db.values'] ?? $this->config['media.db.values'] ?? null;

            $uploadRename = $this->config['mod.'.static::$env.'.'.$this->modName.'.'.$key.(isset($params['type']) ? '.'.$params['type'] : '').'.media.upload.rename'] ?? $this->config['mod.'.$this->modName.'.'.$key.(isset($params['type']) ? '.'.$params['type'] : '').'.media.upload.rename'] ?? $this->config['mod.'.static::$env.'.'.$this->modName.'.'.$key.'.media.upload.rename'] ?? $this->config['mod.'.$this->modName.'.'.$key.'.media.upload.rename'] ?? $this->config['mod.'.static::$env.'.'.$this->modName.(isset($params['type']) ? '.'.$params['type'] : '').'.media.upload.rename'] ?? $this->config['mod.'.$this->modName.(isset($params['type']) ? '.'.$params['type'] : '').'.media.upload.rename'] ?? $this->config['mod.'.static::$env.'.'.$this->modName.'.media.upload.rename'] ?? $this->config['mod.'.$this->modName.'.media.upload.rename'] ?? $this->config['media.'.static::$env.(isset($params['type']) ? '.'.$params['type'] : '').'.upload.rename'] ?? $this->config['media'.(isset($params['type']) ? '.'.$params['type'] : '').'.upload.rename'] ?? $this->config['media.'.static::$env.'.upload.rename'] ?? $this->config['media.upload.rename'] ?? null;

            $this->postData['_'.$key] = [];
            $files = !\is_array($postValue) ? [$postValue] : $postValue;
            foreach ($files as $fileObj) {
                if ($fileObj instanceof UploadedFile) {
                    if (UPLOAD_ERR_OK === $fileObj->getError()) {
                        $ext = $this->helper->File()->getFileExt($fileObj->getClientFilename());

                        if (!empty($uploadRename)) {
                            $basename = str_replace(['.', ','], '', (string) microtime(true));
                        // $basename = mt_rand(1, mt_getrandmax()) . '.' . $ext;
                        } else {
                            $basename = $this->helper->File()->getFileBasename($fileObj->getClientFilename());
                            $basename = $this->helper->Nette()->Strings()->webalize((string) $basename);
                            if (empty($basename)) {
                                // https://www.php.net/manual/en/function.uniqid.php#120123
                                $basename = bin2hex(random_bytes(8));
                            }
                        }

                        // https://www.slimframework.com/docs/v4/cookbook/uploading-files.html
                        // http://zhxnlai.github.io/printf/
                        $name = \sprintf('%s.%0.8s', $basename, $ext);

                        if (empty($uploadRename) && !empty($params['dest'])) {
                            if (str_starts_with($fileObj->getFilePath(), sys_get_temp_dir())) {
                                $n = 1;
                                while (file_exists($params['dest'].'/'.$name)) {
                                    $name = \sprintf('%s-%d.%0.8s', $basename, $n, $ext);
                                    ++$n;
                                }
                            }
                        }

                        if (!empty($dbValues)) {
                            if (!empty($params['dest'])) {
                                $crc32 = $this->helper->Strings()->crc32($params['dest'].'/'.$name);
                            } else {
                                $crc32 = $this->helper->Strings()->crc32($fileObj->getFilePath());
                            }

                            $values = [
                                'name' => $name,
                                'size' => $fileObj->getSize(),
                                // FIXED - $fileObj->getClientMediaType() use client's mimeType
                                'mimeType' => \Safe\mime_content_type($fileObj->getFilePath()),
                                'path' => $fileObj->getFilePath(),
                            ];

                            if (!empty($params['subKey'])) {
                                $this->postData['_'.$key][$params['subKey']][$crc32] = $values;
                            } else {
                                $this->postData['_'.$key][$crc32] = $values;
                            }
                        } else {
                            if (!empty($params['subKey'])) {
                                $this->postData['_'.$key][$params['subKey']] = $name;
                            } else {
                                $this->postData['_'.$key] = $name;
                            }

                            break;
                        }
                    } else {
                        // https://www.php.net/manual/en/function.unset.php#119711
                        // This is probably trivial but there is no error for unsetting a non-existing variable.
                        unset($postValue, $this->postData[$key], $this->filesData[$key]);

                        break;
                    }
                }
            }

            if (isset($postValue) && !empty($this->postData['_'.$key])) {
                if ($dbValues) {
                    $this->postData['_'.$key] = $this->helper->Arrays()->uasortBy($this->postData['_'.$key], 'name');

                    $this->postData[$key] = $this->helper->Nette()->Json()->encode($this->postData['_'.$key]);
                } else {
                    $this->postData[$key] = $this->postData['_'.$key];
                }
            }
        }
    }

    // -------------

    public function alterAfterPerms($key, $langId = null): void
    {
        $this->alterAfterJson($key, $langId);
    }

    public function alterAfterFile($key, $langId = null): void
    {
        $this->alterAfterUpload($key, $langId, [
            'type' => 'file',
        ]);
    }

    public function alterAfterImg($key, $langId = null): void
    {
        $this->alterAfterUpload($key, $langId, [
            'type' => 'img',
        ]);
    }

    public function alterAfterIndexPerms($key, $langId = null): void {}

    public function alterAfterData($key, $langId = null): void
    {
        $this->alterAfterJson($key, $langId);
    }

    public function alterAfterOption($key, $langId = null): void
    {
        if (isset($this->postData['type'])) {
            $filteredKey = $this->postData['type'];

            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
            $this->filterValue->sanitize($filteredKey, 'titlecase');
            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

            if (method_exists($this, '_'.__FUNCTION__.$filteredKey) && \is_callable([$this, '_'.__FUNCTION__.$filteredKey])) {
                \call_user_func_array([$this, '_'.__FUNCTION__.$filteredKey], [$key, $langId]);
            }
        }

        $this->alterAfterJson($key, $langId);
    }

    public function alterAfterOptionLang($key, $langId = null): void
    {
        if (isset($this->postData['type'])) {
            $filteredKey = $this->postData['type'];

            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
            $this->filterValue->sanitize($filteredKey, 'titlecase');
            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

            if (method_exists($this, '_'.__FUNCTION__.$filteredKey) && \is_callable([$this, '_'.__FUNCTION__.$filteredKey])) {
                \call_user_func_array([$this, '_'.__FUNCTION__.$filteredKey], [$key, $langId]);
            }
        }

        $this->alterAfterJson($key, $langId);
    }
}
