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

namespace App\Controller\Mod\Page\Front;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

trait PageMiddlewareTrait
{
    public static $loaded;

    public function _processGlobal(ServerRequestInterface $request, RequestHandlerInterface $handler): ServerRequestInterface
    {
        if (!empty(static::$loaded)) {
            return $request;
        }

        static::$loaded = true;

        $eventName = 'event.'.static::$env.'.'.$this->modName.'.getAll.having';
        $callback = function (GenericEvent $event): void {
            $this->dbData['sql'] .= \Safe\preg_match('/\sHAVING\s/', (string) $this->dbData['sql']) ? ' AND' : ' HAVING';

            $this->dbData['sql'] .= ' catform_ids IS NULL';
        };

        $this->dispatcher->addListener($eventName, $callback);

        ${$this->modName.'Result'} = $this->getTree(
            [
                'order' => 'a.hierarchy ASC',
                'active' => true,
                'field' => 'name',
                'removeAllListeners' => false,
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

        if (!empty($this->view->getData()->catformRow)) { // <--
            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getAll.having';
            $callback = function (GenericEvent $event): void {
                $this->dbData['sql'] .= \Safe\preg_match('/\sHAVING\s/', (string) $this->dbData['sql']) ? ' AND' : ' HAVING';

                // https://stackoverflow.com/a/54688059/3929620
                // https://stackoverflow.com/a/54690032/3929620
                // https://stackoverflow.com/a/37849547/3929620
                $this->dbData['sql'] .= ' FIND_IN_SET(:catform_id, catform_ids) ';
                $this->dbData['args']['catform_id'] = (int) $this->view->getData()->catformRow['id'];
            };

            $this->dispatcher->addListener($eventName, $callback);

            ${$this->modName.'CatformResult'} = $this->getTree(
                [
                    'order' => 'a.hierarchy ASC',
                    'active' => true,
                    'field' => 'name',
                    'removeAllListeners' => false,
                ]
            );

            $this->dispatcher->removeListener($eventName, $callback);

            if (\count(${$this->modName.'CatformResult'}) > 0) {
                $this->view->addData(
                    [ // <--
                        ...compact( // https://stackoverflow.com/a/30266377/3929620
                            $this->modName.'CatformResult'
                        )]
                );
            }
        }

        return $request;
    }
}
