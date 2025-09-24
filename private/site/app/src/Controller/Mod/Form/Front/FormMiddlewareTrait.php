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

namespace App\Controller\Mod\Form\Front;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

trait FormMiddlewareTrait
{
    public static $loaded;

    public function _processGlobal(ServerRequestInterface $request, RequestHandlerInterface $handler): ServerRequestInterface
    {
        if (!empty(static::$loaded)) {
            return $request;
        }

        static::$loaded = true;

        if (!empty($request->getAttribute('hasIdentity'))) {
            if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.fill')) {
                if (!empty($this->view->getData()->catformRow)) { // <--
                    $eventName = 'event.'.static::$env.'.'.$this->modName.'.getAll.where';
                    $callback = function (GenericEvent $event): void {
                        $this->dbData['sql'] .= ' AND a.catform_id = :catform_id';
                        $this->dbData['args']['catform_id'] = (int) $this->view->getData()->catformRow['id'];
                    };

                    $this->dispatcher->addListener($eventName, $callback);

                    ${$this->modName.'Result'} = $this->getAll(
                        [
                            'order' => 'a.hierarchy ASC',
                            'active' => true,
                        ]
                    );

                    $this->dispatcher->removeListener($eventName, $callback);

                    if (\count(${$this->modName.'Result'}) > 0) {
                        $this->view->addData(
                            [ // <--
                                ...compact( // https://stackoverflow.com/a/30266377/3929620
                                    $this->modName.'Result'
                                )]
                        );
                    }
                }
            }
        }

        return $request;
    }
}
