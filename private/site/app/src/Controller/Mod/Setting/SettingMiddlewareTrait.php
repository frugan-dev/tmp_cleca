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

namespace App\Controller\Mod\Setting;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

trait SettingMiddlewareTrait
{
    public static $loaded;

    public function _processGlobal(ServerRequestInterface $request, RequestHandlerInterface $handler): ServerRequestInterface
    {
        if (!empty(static::$loaded)) {
            return $request;
        }

        static::$loaded = true;

        if (!$this->container->has($this->modName)) {
            $result = $this->loadAll([
                'active' => true,
            ]);
            array_walk($result, function (&$row): void {
                if (!empty($row['option'])) {
                    $row['option'] = $this->helper->Nette()->Json()->decode((string) $row['option'], forceArrays: true);
                }
                if (!empty($row['option_lang'])) {
                    $row['option_lang'] = $this->helper->Nette()->Json()->decode((string) $row['option_lang'], forceArrays: true);
                }
            });

            $this->container->set($this->modName, $result);
        }

        return $request;
    }
}
