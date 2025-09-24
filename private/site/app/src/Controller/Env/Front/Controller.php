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

namespace App\Controller\Env\Front;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class Controller extends \App\Model\Controller
{
    use ActionTrait;

    public static string $env = 'front';

    public string $viewLayout = 'master';

    public array $multilang = [];

    #[\Override]
    public function __invoke(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                __('Home'),
                $this->config->get('breadcrumb.'.static::$env.'.textTruncate') ?? $this->config->get('breadcrumb.textTruncate')
            ),
            $this->helper->Url()->urlFor(static::$env.'.index')
        );

        return parent::__invoke($request, $response, $args);
    }

    #[\Override]
    protected function _authCheck(RequestInterface $request, ResponseInterface $response, $args): void {}
}
