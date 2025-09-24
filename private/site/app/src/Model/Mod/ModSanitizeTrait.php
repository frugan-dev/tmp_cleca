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

use Psr\Http\Message\RequestInterface;

trait ModSanitizeTrait
{
    public function sanitize(
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
            } else {
                $this->filterSubject->sanitize($key)->toBlankOr('trim');
            }
        }
    }

    public function sanitizeTextCaseinsensitive($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('trim');
            $this->filterSubject->sanitize($key)->toBlankOr('linearize', ' ');
            $this->filterSubject->sanitize($key)->toBlankOr('stripTags');
            $this->filterSubject->sanitize($key)->toBlankOr('stripEmoji');
        }
    }

    public function sanitizeTextTitlecase($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('trim');
            $this->filterSubject->sanitize($key)->toBlankOr('linearize', ' ');
            $this->filterSubject->sanitize($key)->toBlankOr('stripTags');
            $this->filterSubject->sanitize($key)->toBlankOr('stripEmoji');
            $this->filterSubject->sanitize($key)->toBlankOr('lowercase');
            $this->filterSubject->sanitize($key)->toBlankOr('titlecase');
        }
    }

    public function sanitizeTextLowercase($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('trim');
            $this->filterSubject->sanitize($key)->toBlankOr('lowercase');
        }
    }

    public function sanitizeTextUppercase($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('trim');
            $this->filterSubject->sanitize($key)->toBlankOr('uppercase');
        }
    }

    public function sanitizeTextUppercaseFirst($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('trim');
            $this->filterSubject->sanitize($key)->toBlankOr('linearize', ' ');
            $this->filterSubject->sanitize($key)->toBlankOr('stripTags');
            $this->filterSubject->sanitize($key)->toBlankOr('stripEmoji');
            $this->filterSubject->sanitize($key)->toBlankOr('uppercaseFirst');
        }
    }

    public function sanitizeTextarea($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('trim');
            $this->filterSubject->sanitize($key)->toBlankOr('stripTags');
        }
    }

    public function sanitizeTextareaUppercaseFirst($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('trim');
            $this->filterSubject->sanitize($key)->toBlankOr('stripTags');
            $this->filterSubject->sanitize($key)->toBlankOr('uppercaseFirst');
        }
    }

    public function sanitizeHtml($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('trim');
            $this->filterSubject->sanitize($key)->toBlankOr('purifyHtml');
        }
    }

    public function sanitizeInteger($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('int');
        }
    }

    public function sanitizeDouble($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('float');
        }
    }

    public function sanitizeBoolean($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('bool', 1, 0);
        }
    }

    public function sanitizeArray($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('array');
        }
    }

    public function sanitizeArrayInteger($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('intvalArray');
        }
    }

    public function sanitizeArrayDouble($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('floatArray');
        }
    }

    public function sanitizeArrayMixed($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('arrayMixed');
        }
    }

    public function sanitizeDateTime($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('dateTime');
        }
    }

    public function sanitizeUsername($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('trim');
            $this->filterSubject->sanitize($key)->toBlankOr('lowercase');
            $this->filterSubject->sanitize($key)->toBlankOr('alnum');
        }
    }

    public function sanitizeEmail($key, $langId = null): void
    {
        $this->sanitizeTextLowercase($key, $langId);
    }

    public function sanitizeUrl($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('trim');
            $this->filterSubject->sanitize($key)->toBlankOr('lowercase');
            $this->filterSubject->sanitize($key)->toBlankOr('addScheme');
        }
    }

    public function sanitizePhone($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $this->filterSubject->sanitize($key)->toBlankOr('trim');

            if (!empty($this->postData[$key.'_country_id'])) {
                $this->filterSubject->sanitize($key)->toBlankOr('phoneUri', ''); // poi viene formattato dal validate
            }
        }
    }

    public function sanitizeNoskip($key, $langId = null): void {}

    public function sanitizeSkip($key, $langId = null): void {}

    // TODO - Aura.Filter 4.x https://github.com/auraphp/Aura.Filter/blob/4.x/docs/sanitize.md#uploadedfileornull
    public function sanitizeUpload($key, $langId = null): void {}

    // -------------

    public function sanitizeId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeParentId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeMdate($key, $langId = null): void
    {
        $this->sanitizeDateTime($key, $langId);
    }

    public function sanitizeIdate($key, $langId = null): void
    {
        $this->sanitizeDateTime($key, $langId);
    }

    public function sanitizeSdate($key, $langId = null): void
    {
        $this->sanitizeDateTime($key, $langId);
    }

    public function sanitizeEdate($key, $langId = null): void
    {
        $this->sanitizeDateTime($key, $langId);
    }

    public function sanitizeActive($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizePreselected($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizePrintable($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizeRequired($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizeMaintenance($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizeMaintainer($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizeMain($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizeConfirmed($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizeName($key, $langId = null): void
    {
        $this->sanitizeTextUppercaseFirst($key, $langId);
    }

    public function sanitizeSubname($key, $langId = null): void
    {
        $this->sanitizeTextCaseinsensitive($key, $langId);
    }

    public function sanitizeLabel($key, $langId = null): void
    {
        $this->sanitizeTextCaseinsensitive($key, $langId);
    }

    public function sanitizeText($key, $langId = null): void
    {
        $this->sanitizeTextarea($key, $langId);
    }

    public function sanitizeRichtext($key, $langId = null): void
    {
        $this->sanitizeHtml($key, $langId);
    }

    public function sanitizeAddress($key, $langId = null): void
    {
        $this->sanitizeTextarea($key, $langId);
    }

    public function sanitizeIni($key, $langId = null): void
    {
        $this->sanitizeTextarea($key, $langId);
    }

    public function sanitizeCode($key, $langId = null): void
    {
        $this->sanitizeTextUppercase($key, $langId);
    }

    public function sanitizeFile($key, $langId = null): void
    {
        $this->sanitizeUpload($key, $langId);
    }

    public function sanitizeImg($key, $langId = null): void
    {
        $this->sanitizeUpload($key, $langId);
    }

    public function sanitizeDeleteFile($key, $langId = null): void {}

    public function sanitizeDeleteImg($key, $langId = null): void {}

    public function sanitizeUnlinkFile($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizeUnlinkImg($key, $langId = null): void
    {
        $this->sanitizeBoolean($key, $langId);
    }

    public function sanitizeHierarchy($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeLangId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeCountryId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeRegionId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeDistrictId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeCityId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeCapStart($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeCapEnd($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeCap($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeCatmemberId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeMemberId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeCatformId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeFormId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeLatitude($key, $langId = null): void
    {
        $this->sanitizeDouble($key, $langId);
    }

    public function sanitizeLongitude($key, $langId = null): void
    {
        $this->sanitizeDouble($key, $langId);
    }

    public function sanitizePhoneCountryId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeMobileCountryId($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizePerms($key, $langId = null): void
    {
        $this->sanitizeArray($key, $langId);
    }

    public function sanitizeTime($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }

    public function sanitizeBulkIds($key, $langId = null): void
    {
        $this->sanitizeArrayInteger($key, $langId);
    }

    public function sanitizeType($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            $filteredKey = $this->postData[$key];

            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
            $this->filterValue->sanitize($filteredKey, 'titlecase');
            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

            if (method_exists($this, '_'.__FUNCTION__.$filteredKey) && \is_callable([$this, '_'.__FUNCTION__.$filteredKey])) {
                \call_user_func_array([$this, '_'.__FUNCTION__.$filteredKey], [$key, $langId]);
            }
        }
    }

    public function sanitizeOption($key, $langId = null): void
    {
        $this->sanitizeArray($key, $langId);

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

    public function sanitizeOptionLang($key, $langId = null): void
    {
        $this->sanitizeArray($key, $langId);

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

    /*public function sanitizeIndexTime($key, $langId = null): void
    {
        if (isset($this->postData[$key])) {
            try {
                $formats = [
                    'Y|',
                    'Y-m|',
                    'Y-m-d|',
                    'Y-m-d H|',
                    'Y-m-d H:i|',
                    'Y-m-d H:i:s|',
                ];

                foreach ($formats as $format) {
                    if (false !== ($obj = $this->helper->Carbon()->createFromFormat($format, (string) $this->postData[$key], $this->config['db.1.timeZone']))) {
                        $this->postData[$key.'_format'] = $format;
                        break;
                    }
                }
            } catch (\Exception $e) {
            }
        }

        if (!isset($this->postData[$key.'_format'])) {
            $this->postData[$key] = null;
        }
    }*/

    public function sanitizeLogLevel($key, $langId = null): void
    {
        $this->sanitizeInteger($key, $langId);
    }
}
