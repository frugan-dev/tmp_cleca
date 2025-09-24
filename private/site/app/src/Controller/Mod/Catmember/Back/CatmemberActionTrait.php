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

namespace App\Controller\Mod\Catmember\Back;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait CatmemberActionTrait
{
    public function actionView(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->_actionGlobal($request, $response, $args);

        return parent::actionView($request, $response, $args);
    }

    public function actionAdd(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->_actionGlobal($request, $response, $args);

        return parent::actionAdd($request, $response, $args);
    }

    public function actionEdit(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->_actionGlobal($request, $response, $args);

        return parent::actionEdit($request, $response, $args);
    }

    public function actionDelete(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->_actionGlobal($request, $response, $args);

        return parent::actionDelete($request, $response, $args);
    }
}
