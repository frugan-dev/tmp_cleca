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

use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberType;
use Monolog\Level;
use Psr\Http\Message\RequestInterface;
use Slim\Psr7\UploadedFile;
use Symfony\Component\EventDispatcher\GenericEvent;

trait ModValidateTrait
{
    public function validate(
        RequestInterface $request
    ): void {
        $action = $this->action;

        $this->filterValue->sanitize($action, 'string', '-', ' ');
        $this->filterValue->sanitize($action, 'titlecase');
        $this->filterValue->sanitize($action, 'string', ' ', '');

        foreach ($this->fieldsMonolang as $key => $val) {
            $filteredKey = $key;

            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
            $this->filterValue->sanitize($filteredKey, 'titlecase');
            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

            if (method_exists($this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)) && \is_callable([$this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)])) {
                \call_user_func_array([$this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)], [$key]);
            } elseif (method_exists($this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)) && \is_callable([$this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)])) {
                \call_user_func_array([$this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)], [$key]);
            } elseif (method_exists($this, __FUNCTION__.$action.$filteredKey) && \is_callable([$this, __FUNCTION__.$action.$filteredKey])) {
                \call_user_func_array([$this, __FUNCTION__.$action.$filteredKey], [$key]);
            } elseif (method_exists($this, __FUNCTION__.$filteredKey) && \is_callable([$this, __FUNCTION__.$filteredKey])) {
                \call_user_func_array([$this, __FUNCTION__.$filteredKey], [$key]);
            }

            if (!empty($val[static::$env]['attr']['required'])
                && (empty($val[static::$env]['skip']) || !\in_array($this->action, $val[static::$env]['skip'], true))
                // && (empty($this->postData['_skip']) || !\in_array($key, $this->postData['_skip'], true))
                && !\in_array($this->action, $this->skipRequiredValidationActions, true)
            ) {
                if (method_exists($this, '_'.__FUNCTION__.$action.$filteredKey.'Required'.ucfirst((string) static::$env)) && \is_callable([$this, '_'.__FUNCTION__.$action.$filteredKey.'Required'.ucfirst((string) static::$env)])) {
                    \call_user_func_array([$this, '_'.__FUNCTION__.$action.$filteredKey.'Required'.ucfirst((string) static::$env)], [$key]);
                } elseif (method_exists($this, '_'.__FUNCTION__.$filteredKey.'Required'.ucfirst((string) static::$env)) && \is_callable([$this, '_'.__FUNCTION__.$filteredKey.'Required'.ucfirst((string) static::$env)])) {
                    \call_user_func_array([$this, '_'.__FUNCTION__.$filteredKey.'Required'.ucfirst((string) static::$env)], [$key]);
                } elseif (method_exists($this, '_'.__FUNCTION__.$action.$filteredKey.'Required') && \is_callable([$this, '_'.__FUNCTION__.$action.$filteredKey.'Required'])) {
                    \call_user_func_array([$this, '_'.__FUNCTION__.$action.$filteredKey.'Required'], [$key]);
                } elseif (method_exists($this, '_'.__FUNCTION__.$filteredKey.'Required') && \is_callable([$this, '_'.__FUNCTION__.$filteredKey.'Required'])) {
                    \call_user_func_array([$this, '_'.__FUNCTION__.$filteredKey.'Required'], [$key]);
                } elseif (method_exists($this, '_'.__FUNCTION__.'Required') && \is_callable([$this, '_'.__FUNCTION__.'Required'])) {
                    \call_user_func_array([$this, '_'.__FUNCTION__.'Required'], [$key]);
                }
            }
        }

        if (\count($this->fieldsMultilang) > 0) {
            foreach ($this->fieldsMultilang as $key => $val) {
                $filteredKey = $key;

                $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                $this->filterValue->sanitize($filteredKey, 'titlecase');
                $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                foreach ($this->lang->arr as $langId => $langRow) {
                    if (method_exists($this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)) && \is_callable([$this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)])) {
                        \call_user_func_array([$this, __FUNCTION__.$action.$filteredKey.ucfirst((string) static::$env)], [$key, $langId]);
                    } elseif (method_exists($this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)) && \is_callable([$this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)])) {
                        \call_user_func_array([$this, __FUNCTION__.$filteredKey.ucfirst((string) static::$env)], [$key, $langId]);
                    } elseif (method_exists($this, __FUNCTION__.$action.$filteredKey) && \is_callable([$this, __FUNCTION__.$action.$filteredKey])) {
                        \call_user_func_array([$this, __FUNCTION__.$action.$filteredKey], [$key, $langId]);
                    } elseif (method_exists($this, __FUNCTION__.$filteredKey) && \is_callable([$this, __FUNCTION__.$filteredKey])) {
                        \call_user_func_array([$this, __FUNCTION__.$filteredKey], [$key, $langId]);
                    }

                    if (!empty($val[static::$env]['attr']['required'])
                        && (empty($val[static::$env]['skip']) || !\in_array($this->action, $val[static::$env]['skip'], true))
                        // && (empty($this->postData['_skip']) || !\in_array('multilang|'.$langId.'|'.$key, $this->postData['_skip'], true))
                        && !\in_array($this->action, $this->skipRequiredValidationActions, true)
                    ) {
                        if (method_exists($this, '_'.__FUNCTION__.$action.$filteredKey.'Required'.ucfirst((string) static::$env)) && \is_callable([$this, '_'.__FUNCTION__.$action.$filteredKey.'Required'.ucfirst((string) static::$env)])) {
                            \call_user_func_array([$this, '_'.__FUNCTION__.$action.$filteredKey.'Required'.ucfirst((string) static::$env)], [$key, $langId]);
                        } elseif (method_exists($this, '_'.__FUNCTION__.$filteredKey.'Required'.ucfirst((string) static::$env)) && \is_callable([$this, '_'.__FUNCTION__.$filteredKey.'Required'.ucfirst((string) static::$env)])) {
                            \call_user_func_array([$this, '_'.__FUNCTION__.$filteredKey.'Required'.ucfirst((string) static::$env)], [$key, $langId]);
                        } elseif (method_exists($this, '_'.__FUNCTION__.$action.$filteredKey.'Required') && \is_callable([$this, '_'.__FUNCTION__.$action.$filteredKey.'Required'])) {
                            \call_user_func_array([$this, '_'.__FUNCTION__.$action.$filteredKey.'Required'], [$key, $langId]);
                        } elseif (method_exists($this, '_'.__FUNCTION__.$filteredKey.'Required') && \is_callable([$this, '_'.__FUNCTION__.$filteredKey.'Required'])) {
                            \call_user_func_array([$this, '_'.__FUNCTION__.$filteredKey.'Required'], [$key, $langId]);
                        } elseif (method_exists($this, '_'.__FUNCTION__.'Required') && \is_callable([$this, '_'.__FUNCTION__.'Required'])) {
                            \call_user_func_array([$this, '_'.__FUNCTION__.'Required'], [$key, $langId]);
                        }
                    }
                }
            }
        }
    }

    public function _validateRequired($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $this->filterSubject->validate($postDataKey)->isNotBlank()->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
    }

    public function _validateEditImgRequired($key, $langId = null, $field = null): void {}

    public function _validateEditFileRequired($key, $langId = null, $field = null): void {}

    public function _validateEditPasswordRequired($key, $langId = null, $field = null): void {}

    public function validateMinlength($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            if (\array_key_exists('minlength', $field['attr'])) {
                $this->filterSubject->validate($postDataKey)->is('strlenMin', $field['attr']['minlength'])->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }

    public function validateMaxlength($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            if (\array_key_exists('maxlength', $field['attr'])) {
                $this->filterSubject->validate($postDataKey)->is('strlenMax', $field['attr']['maxlength'])->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }

    public function validateMin($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            if (\array_key_exists('min', $field['attr'])) {
                $this->filterSubject->validate($postDataKey)->is('min', $field['attr']['min'])->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }

    public function validateMax($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            if (\array_key_exists('max', $field['attr'])) {
                $this->filterSubject->validate($postDataKey)->is('max', $field['attr']['max'])->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }

    // https://www.the-art-of-web.com/php/password-strength/
    // https://www.codexworld.com/how-to/validate-password-strength-in-php/
    // https://github.com/jbafford/PasswordStrengthBundle/blob/master/Validator/Constraints/PasswordStrengthValidator.php
    // https://stackoverflow.com/a/14891168/3929620
    // https://stackoverflow.com/a/32138344/3929620
    // https://www.php.net/manual/en/function.strpbrk.php
    // use /u flag to match with full unicode
    public function validatePattern($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            if (\array_key_exists('pattern', $field['attr'])) {
                if (!\Safe\preg_match('/^'.$field['attr']['pattern'].'$/', (string) $this->postData[$postDataKey])) {
                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                }
            }
        }
    }

    // TODO - Aura.Filter 4.x https://github.com/auraphp/Aura.Filter/blob/4.x/docs/validate.md#uploadedfile
    // https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input/file
    public function validateAccept($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $files = !\is_array($this->postData[$postDataKey]) ? [$this->postData[$postDataKey]] : $this->postData[$postDataKey];
            foreach ($files as $fileObj) {
                if ($fileObj instanceof UploadedFile) {
                    if (UPLOAD_ERR_OK === $fileObj->getError()) {
                        // FIXED
                        // https://stackoverflow.com/a/12350083/3929620
                        // https://muffinman.io/blog/uploading-files-using-fetch-multipart-form-data/
                        // $fileObj->getClientMediaType() use client's mimeType
                        // \Safe\mime_content_type($fileObj->getFilePath()) needs formData.append()
                        if (!empty($mimeType = \Safe\mime_content_type($fileObj->getFilePath()))) {
                            if (\array_key_exists('accept', $field['attr'])) {
                                $accept = explode(',', (string) $field['attr']['accept']);

                                // https://stackoverflow.com/a/3432266
                                array_walk(
                                    $accept,
                                    function (&$item): void {
                                        $item = trim($item);
                                        $item = preg_quote($item, '/');
                                        $item = str_replace('\*', '.*', $item);
                                        $item = str_replace('\.', '.*\.', $item);
                                    }
                                );

                                // FIXED - avoid previous filterValue->sanitize() alterations in value
                                // https://github.com/PHP-DI/PHP-DI/issues/725#issuecomment-667505170
                                if (!$this->container->make('filterValue')->validate($mimeType, 'regex', '/(^'.implode('|', $accept).'$)/i')) {
                                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    // TODO - Aura.Filter 4.x https://github.com/auraphp/Aura.Filter/blob/4.x/docs/validate.md#uploadedfile
    public function validateMaxFileSize($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $files = !\is_array($this->postData[$postDataKey]) ? [$this->postData[$postDataKey]] : $this->postData[$postDataKey];
            foreach ($files as $fileObj) {
                if ($fileObj instanceof UploadedFile) {
                    if (!empty($fileObj->getSize())) {
                        if (\array_key_exists('data-maxFileSize', $field['attr'])) {
                            if ($fileObj->getSize() > $field['attr']['data-maxFileSize']) {
                                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                            }
                        }
                    }
                }
            }
        }
    }

    public function validateMinWidthAndHeight($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey])) {
            $files = !\is_array($this->postData[$postDataKey]) ? [$this->postData[$postDataKey]] : $this->postData[$postDataKey];
            foreach ($files as $fileObj) {
                if ($fileObj instanceof UploadedFile) {
                    if (UPLOAD_ERR_OK === $fileObj->getError()) {
                        [$width, $height, $type, $attr] = \Safe\getimagesize($fileObj->getFilePath());

                        if (\array_key_exists('data-minWidth', $field['attr'])) {
                            if ($width < $field['attr']['data-minWidth']) {
                                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The image selected for the %1$s field does not have the minimum width required.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                            }
                        }

                        if (\array_key_exists('data-minHeight', $field['attr'])) {
                            if ($height < $field['attr']['data-minHeight']) {
                                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The image selected for the %1$s field does not have the minimum height required.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                            }
                        }
                    }
                }
            }
        }
    }

    // TODO - Aura.Filter 4.x https://github.com/auraphp/Aura.Filter/blob/4.x/docs/validate.md#uploadedfile
    // https://www.slimframework.com/docs/v4/cookbook/uploading-files.html
    public function validateUpload($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $files = !\is_array($this->postData[$postDataKey]) ? [$this->postData[$postDataKey]] : $this->postData[$postDataKey];
            foreach ($files as $fileObj) {
                // FIXED - not working with Slim..
                // $this->filterSubject->validate($postDataKey)->is('upload')->setMessage(sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));

                if ($fileObj instanceof UploadedFile) {
                    if (($uploadError = $fileObj->getError()) !== UPLOAD_ERR_OK) {
                        $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                            'error' => $uploadError,
                        ]);

                        $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                    }
                }
            }
        }

        $this->validateAccept($key, $langId, $field);
        $this->validateMaxFileSize($key, $langId, $field);
    }

    public function validateText($key, $langId = null, $field = null): void
    {
        $this->validateMinlength($key, $langId, $field);
        $this->validateMaxlength($key, $langId, $field);
    }

    public function validateTextarea($key, $langId = null, $field = null): void
    {
        $this->validateMinlength($key, $langId, $field);
        $this->validateMaxlength($key, $langId, $field);
    }

    public function validateInteger($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterSubject->validate($postDataKey)->is('int')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        }

        $this->validateMin($key, $langId, $field);
        $this->validateMax($key, $langId, $field);
    }

    public function validateDouble($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterSubject->validate($postDataKey)->is('float')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        }

        $this->validateMin($key, $langId, $field);
        $this->validateMax($key, $langId, $field);
    }

    public function validateBoolean($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterSubject->validate($postDataKey)->is('bool')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        }
    }

    public function validateDateTime($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            $this->filterSubject->validate($postDataKey)->is('dateTime')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        }
    }

    public function validateEmail($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $check_dns = true;

            if (!empty($this->config['mail.noDnsCheck'])) {
                if (\Safe\preg_match('/'.implode('|', array_map('preg_quote', $this->config['mail.noDnsCheck'], array_fill(0, \count($this->config['mail.noDnsCheck']), '/'))).'/i', (string) $this->postData[$postDataKey])) {
                    $check_dns = false;
                }
            }

            if ($check_dns) {
                if (true !== ($response = $this->helper->Validator()->isValidEmail($this->postData[$postDataKey]))) {
                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                }
            } else {
                $this->filterSubject->validate($postDataKey)->is('email')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }

        $this->validateMinlength($key, $langId, $field);
        $this->validateMaxlength($key, $langId, $field);
        $this->_validateFieldIfAlreadyExists($key, $langId, $field);
    }

    public function validateUrl($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            // $this->filterSubject->validate($postDataKey)->is('url')->setMessage(sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            $this->filterSubject->validate($postDataKey)->is('urlStrict')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        }

        $this->validateMinlength($key, $langId, $field);
        $this->validateMaxlength($key, $langId, $field);
    }

    public function validateIp($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $this->filterSubject->validate($postDataKey)->is('ip', FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE)->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        }
    }

    public function validatePhone($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $value = $this->postData[$postDataKey];
            $defaultRegion = null;
            $PhoneNumberTypes = [
                // PhoneNumberType::FIXED_LINE,
                // PhoneNumberType::MOBILE,
                // PhoneNumberType::FIXED_LINE_OR_MOBILE,
                // PhoneNumberType::TOLL_FREE,
                // PhoneNumberType::PREMIUM_RATE,
                // PhoneNumberType::SHARED_COST,
                // PhoneNumberType::VOIP,
                // PhoneNumberType::PERSONAL_NUMBER,
                // PhoneNumberType::PAGER,
                // PhoneNumberType::UAN,
                // PhoneNumberType::VOICEMAIL,
            ];

            if (!empty($this->postData[$postDataKey.'_country_id'])) {
                if ($this->container->has('Mod\Country\\'.ucfirst((string) static::$env))) {
                    $row = $this->container->get('Mod\Country\\'.ucfirst((string) static::$env))->getOne(
                        [
                            'id' => (int) $this->postData[$postDataKey.'_country_id'],
                        ]
                    );

                    if (!empty($row['id'])) {
                        $value = $row['phone_code'].$value;
                        $defaultRegion = $row['iso_code'];
                    }

                    if (true !== ($response = $this->helper->Validator()->isValidPhone($value, $defaultRegion, $PhoneNumberTypes, PhoneNumberFormat::NATIONAL))) { // <--
                        if (\is_array($response)) {
                            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The value of the %1$s field must belong to the following types: %2$s'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>', implode(', ', $response)));
                        } elseif (\is_string($response)) {
                            if ($defaultRegion) {
                                $this->filterSubject->sanitize($postDataKey)->to('value', $response); // <--
                            }
                        } else {
                            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                        }
                    }
                } else {
                    $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__);

                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(__('A technical problem has occurred, try again later.'));
                }
            } else {
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }

        $this->validateMinlength($key, $langId, $field);
        $this->validateMaxlength($key, $langId, $field);
    }

    public function validateTimezone($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $this->filterSubject->validate($postDataKey)->is('inValues', \DateTimeZone::listIdentifiers(\DateTimeZone::ALL))->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        }
    }

    public function validateGRecaptchaResponse($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        // $field ??= $this->fields[$key][static::$env];

        $this->filterSubject->validate($postDataKey)->is('recaptchaV3', [
            'env' => static::$env,
            'controller' => $this->controller,
        ])->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.__('Captcha').($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
    }

    public function validateIni($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $this->filterSubject->validate($postDataKey)->is('ini')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        }
    }

    public function validateWatermarkPosition($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (\array_key_exists($postDataKey, $this->postData)) { // <--
            if (!empty($langId)) {
                if ($this->postData[$postDataKey] === $this->multilang[$langId][$key] ?? null) {
                    return;
                }
            } elseif ($this->postData[$postDataKey] === $this->{$key} ?? null) {
                return;
            }

            $refKey = str_replace('_watermark_position', '', (string) $key);
            $refPostDataKey = str_replace('_watermark_position', '', (string) $postDataKey);

            if (isset($this->postData[$refPostDataKey])) {
                if ($this->postData[$refPostDataKey] instanceof UploadedFile) {
                    if (UPLOAD_ERR_OK === $this->postData[$refPostDataKey]->getError()) {
                        return;
                    }
                }
            }

            if (\array_key_exists($refKey, $this->fields)) {
                $refField = $this->fields[$refKey][static::$env];

                if (\array_key_exists('data-type', $refField['attr'])) {
                    $src = _PUBLIC.'/media/'.$refField['attr']['data-type'].'/'.$this->modName;

                    if (!empty($langId)) {
                        $src .= '/'.$this->lang->codeArr[$langId];

                        $srcFile = $this->multilang[$langId][$refKey] ?? null;
                    } else {
                        $srcFile = $this->{$refKey} ?? null;
                    }

                    if (!empty($srcFile)) {
                        $srcDest = $src.'/src/'.$srcFile;

                        if (!file_exists($srcDest)) {
                            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('To change the %1$s field it is necessary to upload again the reference image.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                        }
                    }
                }
            }
        }
    }

    public function validateCountryCode($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            if (file_exists(_ROOT.'/vendor/umpirsky/country-list/data/'.$this->lang->locale.'/country.php')) {
                $items = require _ROOT.'/vendor/umpirsky/country-list/data/'.$this->lang->locale.'/country.php';
                $this->filterSubject->validate($postDataKey)->is('inValues', array_keys($items))->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }

    public function validateLogLevel($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $this->filterSubject->validate($postDataKey)->is('inValues', Level::VALUES)->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        }
    }

    // -------------

    public function validateDeleteId($key, $langId = null, $field = null): void
    {
        if (!empty($this->main)) {
            $this->filterSubject->validate($key)->is('error')->setMessage(\sprintf(__('You cannot delete a %1$s element.'), '<i>'.__('Main').'</i>'));
        }
    }

    public function validateMdate($key, $langId = null, $field = null): void
    {
        $this->validateDateTime($key, $langId, $field);
    }

    public function validateIdate($key, $langId = null, $field = null): void
    {
        $this->validateDateTime($key, $langId, $field);
    }

    public function validateSdate($key, $langId = null, $field = null): void
    {
        $this->validateDateTime($key, $langId, $field);
    }

    public function validateEdate($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $this->validateDateTime($key, $langId, $field);

        if (isset($this->postData[$postDataKey]) && !isBlank($this->postData[$postDataKey])) {
            if (isset($this->postData['sdate']) && !isBlank($this->postData['sdate'])) {
                $field2 = $this->fields['sdate'][static::$env];

                $edateObj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $this->postData[$postDataKey], $this->config['db.1.timeZone']);
                $sdateObj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $this->postData['sdate'], $this->config['db.1.timeZone']);

                if ($edateObj->lessThanOrEqualTo($sdateObj)) {
                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field must be after the %2$s field.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>', '<i>'.$field2['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                }
            }
        }
    }

    public function validateActive($key, $langId = null, $field = null): void
    {
        $this->validateBoolean($key, $langId, $field);
    }

    public function validateMaintenance($key, $langId = null, $field = null): void
    {
        $this->validateBoolean($key, $langId, $field);
    }

    public function validateMaintainer($key, $langId = null, $field = null): void
    {
        $this->validateBoolean($key, $langId, $field);
    }

    public function validatePreselected($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $this->validateBoolean($key, $langId, $field);

        if (empty($this->postData[$postDataKey])) {
            if ((!$langId && !empty($this->{$key})) || ($langId && !empty($this->multilang[$langId][$key]))) {
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('At least one %1$s element must always exist.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        } elseif (!empty($this->postData[$postDataKey])) {
            if (empty($this->postData['active'])) {
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('A %1$s element must always be active.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }

    public function validateMain($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $this->validateBoolean($key, $langId, $field);

        if (empty($this->postData[$postDataKey])) {
            if ((!$langId && !empty($this->{$key})) || ($langId && !empty($this->multilang[$langId][$key]))) {
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('At least one %1$s element must always exist.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        } elseif (!empty($this->postData[$postDataKey])) {
            if (empty($this->postData['active'])) {
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('A %1$s element must always be active.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }

    public function validateConfirmed($key, $langId = null, $field = null): void
    {
        $this->validateBoolean($key, $langId, $field);
    }

    public function validatePrintable($key, $langId = null, $field = null): void
    {
        $this->validateBoolean($key, $langId, $field);
    }

    public function validateRequired($key, $langId = null, $field = null): void
    {
        $this->validateBoolean($key, $langId, $field);
    }

    public function validateHierarchy($key, $langId = null, $field = null): void
    {
        $this->validateInteger($key, $langId, $field);
    }

    public function validateName($key, $langId = null, $field = null): void
    {
        $this->validateText($key, $langId, $field);
    }

    public function validateSubname($key, $langId = null, $field = null): void
    {
        $this->validateText($key, $langId, $field);
    }

    public function validateLabel($key, $langId = null, $field = null): void
    {
        $this->validateText($key, $langId, $field);
    }

    public function validateAddress($key, $langId = null, $field = null): void
    {
        $this->validateTextarea($key, $langId, $field);
    }

    public function validateMobile($key, $langId = null, $field = null): void
    {
        $this->validatePhone($key, $langId, $field);
    }

    public function validateFile($key, $langId = null, $field = null): void
    {
        $this->validateUpload($key, $langId, $field);
    }

    public function validateCode($key, $langId = null, $field = null): void
    {
        $this->validatePattern($key, $langId, $field);
        $this->_validateFieldIfAlreadyExists($key, $langId, $field);
    }

    public function validatePrice($key, $langId = null, $field = null): void
    {
        $this->validateDouble($key, $langId, $field);
    }

    public function validateImg($key, $langId = null, $field = null): void
    {
        $this->validateUpload($key, $langId, $field);
        $this->validateMinWidthAndHeight($key, $langId, $field);
    }

    public function validateImgWatermarkPosition($key, $langId = null, $field = null): void
    {
        $this->validateWatermarkPosition($key, $langId, $field);
    }

    // bypass
    public function validateDeleteFile($key, $langId = null, $field = null): void {}

    // bypass
    public function validateDeleteImg($key, $langId = null, $field = null): void {}

    public function validateLangId($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $this->filterSubject->validate($postDataKey)->is('inKeys', $this->lang->arr)->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
        }
    }

    public function validateToggleActive($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $this->validateActive($key, $langId, $field);

        if (!empty($this->postData['field'])) {
            if (!empty($this->main)) {
                $this->filterSubject->validate('id')->is('error')->setMessage(\sprintf(__('A %1$s element must always be active.'), '<i>'.__('Main').'</i>'));
            }
        }
    }

    public function validateUsername($key, $langId = null, $field = null): void
    {
        $this->validateText($key, $langId, $field);
        $this->validatePattern($key, $langId, $field);
        $this->_validateFieldIfAlreadyExists($key, $langId, $field);
    }

    public function validatePassword($key, $langId = null, $field = null): void
    {
        $this->validatePattern($key, $langId, $field);
    }

    public function validateType($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey])) {
            $filteredKey = $this->postData[$postDataKey];

            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
            $this->filterValue->sanitize($filteredKey, 'titlecase');
            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

            if (method_exists($this, '_'.__FUNCTION__.$filteredKey) && \is_callable([$this, '_'.__FUNCTION__.$filteredKey])) {
                \call_user_func_array([$this, '_'.__FUNCTION__.$filteredKey], [$key, $langId, $field]);
            }
        }
    }

    public function validateOption($key, $langId = null, $field = null): void
    {
        if (!empty($this->postData['type'])) {
            $filteredKey = $this->postData['type'];

            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
            $this->filterValue->sanitize($filteredKey, 'titlecase');
            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

            if (method_exists($this, '_'.__FUNCTION__.$filteredKey) && \is_callable([$this, '_'.__FUNCTION__.$filteredKey])) {
                \call_user_func_array([$this, '_'.__FUNCTION__.$filteredKey], [$key, $langId, $field]);
            }
        }
    }

    public function validateOptionLang($key, $langId = null, $field = null): void
    {
        if (!empty($this->postData['type'])) {
            $filteredKey = $this->postData['type'];

            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
            $this->filterValue->sanitize($filteredKey, 'titlecase');
            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

            if (method_exists($this, '_'.__FUNCTION__.$filteredKey) && \is_callable([$this, '_'.__FUNCTION__.$filteredKey])) {
                \call_user_func_array([$this, '_'.__FUNCTION__.$filteredKey], [$key, $langId, $field]);
            }
        }
    }

    protected function _validateFieldIfAlreadyExists($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        if (!empty($this->postData[$postDataKey]) && !\is_array($this->postData[$postDataKey])) {
            $this->removeAllListeners();

            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getOne.where';
            $callback = function (GenericEvent $event) use ($key, $postDataKey): void {
                $this->dbData['sql'] .= ' AND a.id != :id';
                $this->dbData['sql'] .= ' AND a.'.$key.' = :'.$key;

                $this->dbData['args']['id'] = $this->id;
                $this->dbData['args'][$key] = $this->postData[$postDataKey];
            };

            $this->dispatcher->addListener($eventName, $callback);

            $row = $this->getOne(
                [
                    'id' => false,
                ]
            );

            $this->dispatcher->removeListener($eventName, $callback);

            $this->addAllListeners();

            if (!empty($row['id'])) {
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The value of the %1$s field already exists.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }
}
