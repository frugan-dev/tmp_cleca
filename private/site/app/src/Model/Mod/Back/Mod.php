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

namespace App\Model\Mod\Back;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Mod extends \App\Model\Mod\Mod
{
    use ModEventTrait;

    public static string $env = 'back';

    public string $viewLayout = 'master';

    #[\Override]
    public function __invoke(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                __('Dashboard'),
                $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
            ),
            $this->helper->Url()->urlFor(static::$env.'.index')
        );

        $this->viewData = [...$this->viewData, 'Mod' => $this];

        return parent::__invoke($request, $response, $args);
    }
}
