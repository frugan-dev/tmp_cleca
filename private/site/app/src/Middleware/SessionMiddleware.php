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

namespace App\Middleware;

use App\Factory\Session\SessionInterface;
use App\Helper\HelperInterface;
use App\Model\Model;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SessionMiddleware extends Model implements MiddlewareInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected SessionInterface $session,
        protected HelperInterface $helper,
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->session->start();

        if (!$this->session->get('referer') && !empty($_SERVER['HTTP_REFERER'] ?? null)) {
            if (!str_contains((string) $_SERVER['HTTP_REFERER'], (string) $this->helper->Url()->getBaseUrl())) {
                $this->session->set('referer', $_SERVER['HTTP_REFERER']);
            }
        }

        return $handler->handle($request);
    }
}
