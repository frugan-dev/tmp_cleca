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

namespace App\Controller\Mod\Catmember;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

trait CatmemberActionTrait
{
    public function _actionGlobal(RequestInterface $request, ResponseInterface $response, $args): void
    {
        foreach ($this->config->get('mod.'.$this->modName.'.mods.arr', []) as $controller) {
            if ($this->container->has('Mod\\'.ucfirst((string) $controller).'\\'.ucfirst(static::$env))) {
                $this->mods[$controller] = [
                    'pluralName' => $this->container->get('Mod\\'.ucfirst((string) $controller).'\\'.ucfirst(static::$env))->pluralName,
                    'perms' => [],
                ];
            }
        }

        $this->mods = $this->helper->Arrays()->uasortBy($this->mods, 'pluralName');

        $this->tree->create($this->mods, ['perms']);

        $this->mods = $this->tree->get();
    }
}
