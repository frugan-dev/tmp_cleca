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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;

trait CatformActionTrait
{
    public function actionView(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ((int) $args[$this->modName.'_id'] !== $this->id) {
            $this->container->set('error'.ucfirst($this->modName).'Id', true);

            throw new HttpNotFoundException($request);
        }

        if ($this->existStrict([
            'active' => true,
        ])) {
            return parent::actionView($request, $response, $args);
        }

        throw new HttpNotFoundException($request);
    }

    public function actionPrint(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->setId();

        if ($this->existStrict([
            'active' => true,
        ])) {
            $this->setFields();

            $this->title = $this->metaTitle = $this->name;
            $this->subTitle = $this->subname;

            $this->viewLayout = 'print';

            $this->viewData = array_merge(
                $this->viewData,
                [
                    'loadPrint' => (bool) !$this->config->get('debug.enabled', false),
                ]
            );

            if ($this->auth->hasIdentity()) {
                if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                    $this->viewData = array_merge(
                        $this->viewData,
                        [
                            'memberRow' => [
                                'id' => $this->auth->getIdentity()['id'],
                                'firstname' => $this->auth->getIdentity()['firstname'],
                                'lastname' => $this->auth->getIdentity()['lastname'],
                                'email' => $this->auth->getIdentity()['email'],
                            ],
                        ]
                    );
                }
            }
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    public function _actionView(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->title = $this->metaTitle = $this->name;
        $this->subTitle = $this->subname;
    }
}
