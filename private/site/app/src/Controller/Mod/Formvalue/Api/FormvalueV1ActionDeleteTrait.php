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

trait FormvalueV1ActionDeleteTrait
{
    protected function v1ActionDeleteDeleteFile(RequestInterface $request, ResponseInterface $response, $args)
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

    protected function _v1ActionDeleteDeleteFile(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->setRouteParams();

        if (($paramId = array_search('id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $id = (int) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (($paramId = array_search('file_id', $this->routeParamsArr, true)) !== false) {
            if (isset($this->routeParamsArr[$paramId + 1])) {
                $fileId = (int) $this->routeParamsArr[$paramId + 1];
            }
        }

        if (!empty($id) && !empty($fileId)) {
            if (!empty($this->exist([
                'id' => $id,
                'active' => ($this->rbac->isGranted($this->controller.'.'.static::$env.'.add') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.edit') || $this->rbac->isGranted($this->controller.'.'.static::$env.'.delete')) ? (\array_key_exists('active', $this->getData) ? (bool) $this->getData['active'] : null) : true,
            ]))) {
                $this->setId($id);
                $this->setFields();

                if (!empty($data = $this->helper->Nette()->Json()->decode((string) $this->data, forceArrays: true))) {
                    if ($this->rbac->isGranted($this->modName.'.front.edit')) { // <--
                        if (($teacherKey = $this->helper->Arrays()->recursiveArraySearch('id', $this->auth->getIdentity()['id'], $data['teachers'], true)) !== false) {
                            if (\array_key_exists($fileId, $data['teachers'][$teacherKey]['files'])) {
                                unset($data['teachers'][$teacherKey]['files'][$fileId]);
                            }/* else {
                                throw new HttpNotFoundException($request);
                            }*/
                        } else {
                            throw new HttpNotFoundException($request);
                        }
                    } elseif (\array_key_exists($fileId, $data)) {
                        unset($data[$fileId]);
                    }/* else {
                        throw new HttpNotFoundException($request);
                    }*/

                    $this->postData = [
                        'data' => $this->helper->Nette()->Json()->encode($data),
                    ];

                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionEdit.before');

                    $this->dbEdit();

                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionEdit.after');

                    $dest = _ROOT.'/var/upload/catform-'.$this->catform_id.'/formfield-'.$this->formfield_id.'/'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'];

                    if (is_dir($dest)) {
                        // in() searches only the current directory, while from() searches its subdirectories too (recursively)
                        foreach ($this->helper->Nette()->Finder()->findFiles('*')->in($dest) as $fileObj) {
                            $crc32 = $this->helper->Strings()->crc32($fileObj->getRealPath());
                            if ($crc32 === $fileId) {
                                $this->helper->Nette()->FileSystem()->delete($fileObj->getRealPath());

                                break;
                            }
                        }
                    }
                } else {
                    $this->errors[] = __('A technical problem has occurred, try again later.');
                }
            } else {
                throw new HttpNotFoundException($request);
            }

            if (0 === \count($this->errors)) {
                $this->responseData['response'] = true;
            }
        } else {
            throw new HttpNotFoundException($request);
        }
    }
}
