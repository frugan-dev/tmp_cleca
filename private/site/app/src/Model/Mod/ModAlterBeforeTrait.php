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

trait ModAlterBeforeTrait
{
    public function alterBefore(
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

    public function alterBeforeDateTime($key, $langId = null): void
    {
        if (!empty($this->postData[$key])) {
            $this->postData[$key] = $this->helper->Carbon()->createFromFormat('Y-m-d\TH:i', $this->postData[$key], date_default_timezone_get())->setTimezone($this->config['db.1.timeZone'])->toDateTimeString();
        }
    }

    public function alterBeforePassword($key, $langId = null): void
    {
        if (empty($this->postData[$key])) { // <--
            unset($this->postData[$key]);
        }
    }

    // DEPRECATED - Aura.Filter 4.x https://github.com/auraphp/Aura.Filter/blob/4.x/docs/sanitize.md#uploadedfileornull
    public function alterBeforeUpload($key, $langId = null, array $params = []): void
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
            $files = !\is_array($postValue) ? [$postValue] : $postValue;
            foreach ($files as $fileObj) {
                if ($fileObj instanceof UploadedFile) {
                    if (UPLOAD_ERR_NO_FILE === $fileObj->getError()) {
                        // https://www.php.net/manual/en/function.unset.php#119711
                        // This is probably trivial but there is no error for unsetting a non-existing variable.
                        unset($postValue, $this->postData[$key], $this->filesData[$key]);

                        break;
                    }
                }
            }
        }
    }

    // DEPRECATED - Aura.Filter 4.x https://github.com/auraphp/Aura.Filter/blob/4.x/docs/sanitize.md#uploadedfileornull
    public function alterBeforeUnlinkUpload($key, $langId = null, array $params = []): void
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

        if (!empty($postValue)) {
            $refKey = str_replace('unlink_', '', (string) $key);

            if (!empty($params['subKey'])) {
                $postRefValue = $this->postData[$refKey][$params['subKey']] ?? null;
            } else {
                $postRefValue = $this->postData[$refKey] ?? null;
            }

            if ($postRefValue instanceof UploadedFile) {
                if (UPLOAD_ERR_NO_FILE === $postRefValue->getError()) {
                    $this->postData[$refKey] = null; // <--
                }
            } else {
                $this->postData[$refKey] = null; // <--
            }
        }
    }

    // -------------

    public function alterBeforeIdate($key, $langId = null): void
    {
        $this->alterBeforeDateTime($key, $langId);
    }

    public function alterBeforeIndexIdate($key, $langId = null): void {}

    public function alterBeforeSdate($key, $langId = null): void
    {
        $this->alterBeforeDateTime($key, $langId);
    }

    public function alterBeforeIndexSdate($key, $langId = null): void {}

    public function alterBeforeEdate($key, $langId = null): void
    {
        $this->alterBeforeDateTime($key, $langId);
    }

    public function alterBeforeIndexEdate($key, $langId = null): void {}

    public function alterBeforeFile($key, $langId = null): void
    {
        $this->alterBeforeUpload($key, $langId);
    }

    public function alterBeforeImg($key, $langId = null): void
    {
        $this->alterBeforeUpload($key, $langId);
    }

    public function alterBeforeUnlinkFile($key, $langId = null): void
    {
        $this->alterBeforeUnlinkUpload($key, $langId);
    }

    public function alterBeforeUnlinkImg($key, $langId = null): void
    {
        $this->alterBeforeUnlinkUpload($key, $langId);
    }

    public function alterBeforeOption($key, $langId = null): void
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
    }

    public function alterBeforeOptionLang($key, $langId = null): void
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
    }
}
