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

namespace App\Controller\Mod\Catform\Back;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait CatformActionTrait
{
    public function actionIndex(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (null !== ($return = parent::actionIndex($request, $response, $args))) {
            return $return;
        }

        if (!empty($this->viewData['result'])) {
            array_walk($this->viewData['result'], function (&$row): void {
                $row['status'] = $this->getStatusValue($row);
            });
        }
    }

    public function _actionWidgetIndex(RequestInterface $request, ResponseInterface $response, $args): void
    {
        parent::_actionWidgetIndex($request, $response, $args);

        if (!empty($this->widgetData[$this->controller][$this->action])) {
            array_walk($this->widgetData[$this->controller][$this->action]['result'], function (&$row): void {
                $row['status'] = $this->getStatusValue($row);
            });
        }
    }
}
