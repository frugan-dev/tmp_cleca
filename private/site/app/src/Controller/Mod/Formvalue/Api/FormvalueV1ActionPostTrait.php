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

namespace App\Controller\Mod\Formvalue\Api;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Symfony\Component\EventDispatcher\GenericEvent;

trait FormvalueV1ActionPostTrait
{
    protected function v1ActionPostUpload(RequestInterface $request, ResponseInterface $response, $args)
    {
        if ($this->rbac->isGranted($this->controller.'.'.static::$env.'.'.$this->action)) {
            if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
                $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

                if (null !== $return) {
                    return $return;
                }
            } else {
                throw new HttpMethodNotAllowedException($request);
            }
        } else {
            throw new HttpUnauthorizedException($request);
        }
    }

    protected function _v1ActionPostUpload(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->check($request);

        if (0 === \count($this->errors)) {
            if ($this->rbac->isGranted($this->modName.'.front.edit')) { // <--
                if (!empty($this->exist([
                    'id' => $this->postData['id'],
                    'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
                ]))) {
                    $this->setId($this->postData['id']);
                    $this->setFields();

                    if (!empty($data = $this->helper->Nette()->Json()->decode((string) $this->data, forceArrays: true))) {
                        if (($teacherKey = $this->helper->Arrays()->recursiveArraySearch('id', $this->auth->getIdentity()['id'], $data['teachers'], true)) !== false) {
                            if (!empty($data['teachers'][$teacherKey]['files'])) {
                                $mergeSubArr = $this->postData['_data'] + $data['teachers'][$teacherKey]['files'];
                                $mergeSubArr = $this->helper->Arrays()->uasortBy($mergeSubArr, 'name');
                            } else {
                                $mergeSubArr = $this->postData['_data'];
                            }

                            $mergeArr = $data;
                            $mergeArr['teachers'][$teacherKey]['files'] = $mergeSubArr;
                            $mergeArr['teachers'][$teacherKey]['mdate'] = $this->helper->Carbon()->now($this->config['db.1.timeZone'])->toDateTimeString();

                            $this->postData['data'] = $this->helper->Nette()->Json()->encode($mergeArr);

                            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionEdit.before');

                            $this->dbEdit();

                            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionEdit.after');

                            if (0 === \count($this->errors)) {
                                $id = $this->id;

                                if (!empty($data['teachers'][$teacherKey]['files'])) {
                                    $diffArr = array_diff_key($this->postData['_data'], $data['teachers'][$teacherKey]['files']);
                                    $crc32 = key($diffArr);

                                    $name = $diffArr[$crc32]['name'];
                                    $size = $diffArr[$crc32]['size'];
                                } else {
                                    $crc32 = key($this->postData['_data']);
                                    $name = $this->postData['_data'][$crc32]['name'];
                                    $size = $this->postData['_data'][$crc32]['size'];
                                }
                            }
                        } else {
                            throw new HttpNotFoundException($request);
                        }
                    } else {
                        throw new HttpNotFoundException($request);
                    }
                } else {
                    throw new HttpNotFoundException($request);
                }
            } else {
                $eventName = 'event.'.static::$env.'.'.$this->modName.'.existStrict.where';
                $callback = function (GenericEvent $event): void {
                    $this->dbData['sql'] .= ' AND a.formfield_id = :formfield_id';
                    $this->dbData['sql'] .= ' AND a.member_id = :member_id';
                    $this->dbData['args']['formfield_id'] = $this->postData['formfield_id'];
                    $this->dbData['args']['member_id'] = $this->auth->getIdentity()['id'];
                };

                $this->dispatcher->addListener($eventName, $callback);

                $row = $this->existStrict([
                    'id' => false,
                    'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
                ]);

                $this->dispatcher->removeListener($eventName, $callback);

                if (!empty($row)) {
                    $this->setId($row['id']);
                    $this->setFields();

                    if (!empty($data = $this->helper->Nette()->Json()->decode((string) $this->data, forceArrays: true))) {
                        $mergeArr = $this->postData['_data'] + $data;
                        $mergeArr = $this->helper->Arrays()->uasortBy($mergeArr, 'name');

                        $this->postData['data'] = $this->helper->Nette()->Json()->encode($mergeArr);
                    }

                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionEdit.before');

                    $this->dbEdit();

                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionEdit.after');

                    if (0 === \count($this->errors)) {
                        $diffArr = array_diff_key($this->postData['_data'], $data);

                        $id = $this->id;
                        $crc32 = key($diffArr);
                        $name = $diffArr[$crc32]['name'];
                        $size = $diffArr[$crc32]['size'];
                    }
                } else {
                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionAdd.before');

                    $insertId = $this->dbAdd();

                    $this->dispatcher->dispatch(new GenericEvent(arguments: [
                        'id' => $insertId,
                    ]), 'event.'.static::$env.'.'.$this->modName.'.actionAdd.after');

                    if (0 === \count($this->errors)) {
                        $id = $insertId;
                        $crc32 = key($this->postData['_data']);
                        $name = $this->postData['_data'][$crc32]['name'];
                        $size = $this->postData['_data'][$crc32]['size'];
                    }
                }
            }

            if (0 === \count($this->errors)) {
                $this->responseData['response'] = [
                    'name' => $name,
                    'size' => $this->helper->File()->formatSize($size),
                    'id' => $id,
                    'file_id' => (int) $crc32,
                ];
            }
        }
    }
}
