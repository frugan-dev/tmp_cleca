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

namespace App\Controller\Mod\Catform\Front;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Exception\HttpNotFoundException;

trait CatformMiddlewareTrait
{
    public static $loaded;

    public function _processGlobal(ServerRequestInterface $request, RequestHandlerInterface $handler): ServerRequestInterface
    {
        if (!empty(static::$loaded)) {
            return $request;
        }

        static::$loaded = true;

        ${$this->modName.'Id'} = $this->routeParsingService->getNumericId($request, $this->modName.'_id');

        if (null !== ${$this->modName.'Id'}) {
            $moduleName = $this->routeParsingService->getModuleName($request);
            $action = $this->routeParsingService->getAction($request);
            $params = $this->routeParsingService->getParamsString($request);

            // Validate action and params if this is the specific controller
            if ($this->modName === $moduleName) {
                if (null !== $action && \in_array($action, ['view'], true)) {
                    if (${$this->modName.'Id'} !== (int) $params) {
                        $this->container->set('error'.ucfirst((string) $this->modName).'Id', true);
                    }
                }
            }

            // Load specific catform if no error yet
            if (!$this->container->has('error'.ucfirst($this->modName).'Id')) {
                if (!empty(${$this->modName.'Row'} = $this->getOne([
                    'id' => ${$this->modName.'Id'},
                    'active' => true,
                ]))) {
                    ${$this->modName.'Row'}['status'] = $this->getStatusValue(${$this->modName.'Row'});

                    if (${$this->modName.'Id'} === ${$this->modName.'Row'}['id']) {
                        $this->view->addData([ // <--
                            // https://stackoverflow.com/a/30266377/3929620
                            ...compact($this->modName.'Row'),
                        ]);

                        return $request;
                    }
                    $this->container->set('error'.ucfirst($this->modName).'Id', true);
                } else {
                    $this->container->set('error'.ucfirst($this->modName).'Id', true);
                }
            }
        }

        ${$this->modName.'Result'} = $this->getAll([
            'order' => 'a.hierarchy ASC',
            'active' => true,
        ]);

        if (\count(${$this->modName.'Result'}) > 0) {
            array_walk(${$this->modName.'Result'}, function (&$row): void {
                $row['status'] = $this->getStatusValue($row);
            });

            $this->view->addData([ // <--
                // https://stackoverflow.com/a/30266377/3929620
                ...compact($this->modName.'Result'),
            ]);
        }

        if ($this->container->has('error'.ucfirst($this->modName).'Id')) {
            throw new HttpNotFoundException($request);
        }

        return $request;
    }
}
