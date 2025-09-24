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

use App\Helper\HelperInterface;
use App\Model\Model;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ReqsMiddleware extends Model implements MiddlewareInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected HelperInterface $helper,
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (is_dir(_ROOT.'/app/reqs')) {
            // in() searches only the current directory, while from() searches its subdirectories too (recursively)
            foreach ($this->helper->Nette()->Finder()->findFiles('*.php')->in(_ROOT.'/app/reqs')->sortByName() as $fileObj) {
                $return = (include_once $fileObj->getPathname())($this->container);

                if (null !== $return) {
                    return $return;
                }
            }
        }

        return $handler->handle($request);
    }
}
