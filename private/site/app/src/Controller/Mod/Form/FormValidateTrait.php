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

namespace App\Controller\Mod\Form;

use Symfony\Component\EventDispatcher\GenericEvent;

trait FormValidateTrait
{
    public function validateHierarchy($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        parent::validateHierarchy($key, $langId, $field);

        $this->filterSubject->validate($postDataKey)->isNot('equalToValue', 0)->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));

        if (!empty($this->postData['cat'.$this->modName.'_id'])) {
            $eventName2 = 'event.'.static::$env.'.'.$this->modName.'.getOne.where';
            $callback2 = function (GenericEvent $event): void {
                $this->dbData['sql'] .= ' AND a.cat'.$this->modName.'_id = :cat'.$this->modName.'_id';
                $this->dbData['args']['cat'.$this->modName.'_id'] = $this->postData['cat'.$this->modName.'_id'];
            };

            $eventName = 'event.'.static::$env.'.'.$this->modName.'._validateFieldIfAlreadyExists.before';
            $callback = function (GenericEvent $event) use ($eventName2, $callback2): void {
                $this->dispatcher->addListener($eventName2, $callback2);
            };

            $this->dispatcher->addListener($eventName, $callback);

            $this->_validateFieldIfAlreadyExists($key, $langId, $field);

            $this->dispatcher->removeListener($eventName2, $callback2);
            $this->dispatcher->removeListener($eventName, $callback);
        }
    }

    public function validateFillInputEmail($key, $langId = null, $field = null): void
    {
        $this->validateEmail($key, $langId, $field);
    }

    public function validateFillInputFileMultiple($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $field['attr']['accept'] = implode(',', $this->config['mod.'.static::$env.'.'.$this->modName.'value.mime.file.allowedTypes'] ?? $this->config['mod.'.$this->modName.'value.mime.file.allowedTypes'] ?? $this->config['mime.'.static::$env.'.file.allowedTypes'] ?? $this->config['mime.file.allowedTypes']);
        $field['attr']['data-maxFileSize'] = $this->helper->File()->getBytes($this->config['mod.'.static::$env.'.'.$this->modName.'value.media.file.uploadMaxFilesize'] ?? $this->config['mod.'.$this->modName.'value.media.file.uploadMaxFilesize'] ?? $this->config['media.'.static::$env.'.file.uploadMaxFilesize'] ?? $this->config['media.file.uploadMaxFilesize'] ?? \Safe\ini_get('upload_max_filesize'));

        $this->validateUpload($key, $langId, $field);

        if (!empty($this->postData[$postDataKey]) && !empty($field['_row']['option']['max_files'])) {
            if (\count($this->postData[$postDataKey]) > $field['_row']['option']['max_files']) {
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            }
        }
    }

    public function validateFillInputNumberIntegerGt0($key, $langId = null, $field = null): void
    {
        $field['attr']['min'] = 1;

        $this->validateMin($key, $langId, $field);
    }

    public function validateFillInputNumberIntegerGte0($key, $langId = null, $field = null): void
    {
        $field['attr']['min'] = 0;

        $this->validateMin($key, $langId, $field);
    }

    public function validateFillInputTel($key, $langId = null, $field = null): void
    {
        // TODO
        // $this->validatePhone($key, $langId, $field);
    }

    public function validateFillInputUrl($key, $langId = null, $field = null): void
    {
        $this->validateUrl($key, $langId, $field);
    }

    public function validateFillRecommendation($key, $langId = null, $field = null): void
    {
        $postDataKey = ($langId) ? 'multilang|'.$langId.'|'.$key : $key;
        $field ??= $this->fields[$key][static::$env];

        $field['label'] = __('Teacher\'s data');

        if (!empty($this->postData[$postDataKey]) && !empty($field['_row']['option']['max_teachers'])) {
            if (empty($this->postData[$postDataKey.'_teachers']) || \count($this->postData[$postDataKey.'_teachers']) > $field['_row']['option']['max_teachers']) {
                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
            } else {
                foreach ($this->postData[$postDataKey.'_teachers'] as $k => $v) {
                    if (empty($v['firstname']) || empty($v['lastname']) || empty($v['email'])) {
                        $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                    } else {
                        $check_dns = true;

                        if (!empty($this->config['mail.noDnsCheck'])) {
                            if (\Safe\preg_match('/'.implode('|', array_map('preg_quote', $this->config['mail.noDnsCheck'], array_fill(0, \count($this->config['mail.noDnsCheck']), '/'))).'/i', (string) $v['email'])) {
                                $check_dns = false;
                            }
                        }

                        if ($check_dns) {
                            if (true !== ($response = $this->helper->Validator()->isValidEmail($v['email']))) {
                                $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                            }
                        // FIXED - avoid previous filterValue->sanitize() alterations in value
                        // https://github.com/PHP-DI/PHP-DI/issues/725#issuecomment-667505170
                        } elseif (!$this->container->make('filterValue')->validate($v['email'], 'email')) {
                            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                        }
                    }
                }

                // https://stackoverflow.com/a/49645329/3929620
                $emails = array_column($this->postData[$postDataKey.'_teachers'], 'email');
                if ($this->helper->Arrays()->hasDuplicates($emails)) {
                    $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));
                } else {
                    $ModMember = $this->container->get('Mod\Member\\'.ucfirst(static::$env));

                    foreach ($emails as $email) {
                        $ModMember->removeAllListeners();

                        $eventName = 'event.'.static::$env.'.'.$ModMember->modName.'.getOne.select';
                        $callback = function (GenericEvent $event) use ($ModMember): void {
                            $ModMember->dbData['sql'] .= ', c.main AS cat'.$ModMember->modName.'_main';
                        };

                        $eventName2 = 'event.'.static::$env.'.'.$ModMember->modName.'.getOne.join';
                        $callback2 = function (GenericEvent $event) use ($ModMember): void {
                            $ModMember->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'cat'.$ModMember->modName.' AS c
                                ON a.cat'.$ModMember->modName.'_id = c.id';
                        };

                        $eventName3 = 'event.'.static::$env.'.'.$ModMember->modName.'.getOne.where';
                        $callback3 = function (GenericEvent $event) use ($ModMember, $email): void {
                            $ModMember->dbData['sql'] .= ' AND a.email = :email';
                            $ModMember->dbData['args']['email'] = $email;
                        };

                        $this->dispatcher->addListener($eventName, $callback);
                        $this->dispatcher->addListener($eventName2, $callback2);
                        $this->dispatcher->addListener($eventName3, $callback3);

                        $row = $ModMember->getOne(
                            [
                                'id' => false,
                            ]
                        );

                        $this->dispatcher->removeListener($eventName, $callback);
                        $this->dispatcher->removeListener($eventName2, $callback2);
                        $this->dispatcher->removeListener($eventName3, $callback3);

                        $ModMember->addAllListeners();

                        if (!empty($row['id']) && empty($row['cat'.$ModMember->modName.'_main'])) {
                            $this->filterSubject->validate($postDataKey)->is('error')->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$field['label'].($langId ? ' ('.$this->lang->arr[$langId]['name'].')' : '').'</i>'));

                            break;
                        }
                    }
                }
            }
        }
    }

    public function _validateFieldIfAlreadyExists($key, $langId = null, $field = null): void
    {
        if (!\in_array($key, ['email'], true)) {
            parent::_validateFieldIfAlreadyExists($key, $langId, $field);
        }
    }
}
