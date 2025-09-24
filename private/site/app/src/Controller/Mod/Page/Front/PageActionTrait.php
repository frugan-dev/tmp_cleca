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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;

trait PageActionTrait
{
    public function actionView(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

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
                    'loadPrint' => (bool) !($this->config['debug.enabled'] ?? false),
                ]
            );
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    public function _actionView(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->title = $this->metaTitle = $this->name;
        $this->subTitle = $this->subname;

        if (!empty($this->view->getData()->catformRow)) { // <--
            $this->breadcrumb->add(
                $this->helper->Nette()->Strings()->truncate(
                    $this->view->getData()->catformRow['name'],
                    $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                ),
                $this->helper->Url()->urlFor([
                    'routeName' => static::$env.'.catform.params',
                    'data' => [
                        'action' => $this->action,
                        'params' => $args['catform_id'],
                    ],
                ])
            );
        }

        if (!empty($this->parent_id)) {
            $this->breadcrumb->add(
                $this->helper->Nette()->Strings()->truncate(
                    $this->parent_name,
                    $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                ),
                $this->helper->Url()->urlFor([
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'action' => $this->action,
                        'params' => $this->parent_id,
                    ],
                ])
            );
        }
    }
}
