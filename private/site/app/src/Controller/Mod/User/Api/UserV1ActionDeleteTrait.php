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

namespace App\Controller\Mod\User\Api;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpUnauthorizedException;

trait UserV1ActionDeleteTrait
{
    protected function v1ActionDeleteDeleteCache(RequestInterface $request, ResponseInterface $response, $args): void
    {
        if ($this->rbac->isGranted($this->controller.'.'.static::$env.'.'.$this->action)) {
            if (!empty($this->cache->taggable)) {
                $tags = [
                    'local-'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'],
                    'global-1',
                ];

                if ($this->rbac->isGranted('cat'.$this->modName.'.'.static::$env.'.add')) {
                    $tags[] = 'global-0';

                    if (isDev()) {
                        $tags[] = 'permanent';
                    }
                }

                $this->cache->invalidateTags($tags);
            } else {
                $this->cache->clear();
            }

            $this->responseData['response'] = true;
        } else {
            throw new HttpUnauthorizedException($request);
        }
    }
}
