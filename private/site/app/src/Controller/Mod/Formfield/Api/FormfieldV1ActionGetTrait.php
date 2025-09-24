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

namespace App\Controller\Mod\Formfield\Api;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

trait FormfieldV1ActionGetTrait
{
    protected function _v1ActionGetIndexFullByFieldId(RequestInterface $request, ResponseInterface $response, $args): void
    {
        if (!empty($this->isXhr)) {
            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getAll.where';
            $callback = function (GenericEvent $event): void {
                $this->dbData['sql'] .= ' AND a.type NOT LIKE :like_type';
                $this->dbData['args']['like_type'] = 'block_%';
            };

            $this->dispatcher->addListener($eventName, $callback);
        }

        parent::_v1ActionGetIndexFullByFieldId($request, $response, $args);

        if (!empty($this->isXhr)) {
            $this->dispatcher->removeListener($eventName, $callback);
        }
    }

    protected function _v1ActionGetIndexFullByFieldIdType($key, $row): void
    {
        if (!empty($this->isXhr)) {
            if (method_exists($this, 'getFieldTypes') && \is_callable([$this, 'getFieldTypes'])) {
                $type = $this->getFieldTypes()[$row[$key]] ?? $row[$key];
            } else {
                $type = $row[$key];
            }

            $this->rowData['_'.$key.'Translated'] = $type;
        }
    }

    protected function _v1ActionGetIndexFullByFieldIdMultilangName($key, $row): void
    {
        if (!empty($this->isXhr)) {
            $this->rowData['_'.$key.'RichtextTruncated'] = $this->helper->Nette()->Strings()->truncate(trim(strip_tags((string) ($row[$key] ?? '').' '.($row['richtext'] ?? ''))), 30);
        }
    }
}
