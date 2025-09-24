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

use Symfony\Component\EventDispatcher\GenericEvent;

trait CatmemberEventTrait
{
    public function eventActionAddAfter(GenericEvent $event): void
    {
        parent::eventActionAddAfter($event);

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            \call_user_func_array([$this, '_'.__FUNCTION__], [$event]);
        }

        if (!empty($this->cache->taggable)) {
            $tags = [
                'global-1',
            ];

            $this->cache->invalidateTags($tags);
        } else {
            $this->cache->clear();
        }
    }

    public function eventActionEditAfter(GenericEvent $event): void
    {
        parent::eventActionEditAfter($event);

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            \call_user_func_array([$this, '_'.__FUNCTION__], [$event]);
        }

        if (!empty($this->cache->taggable)) {
            $tags = [
                'global-1',
            ];

            $this->cache->invalidateTags($tags);
        } else {
            $this->cache->clear();
        }
    }

    public function eventActionDeleteAfter(GenericEvent $event): void
    {
        parent::eventActionDeleteAfter($event);

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            \call_user_func_array([$this, '_'.__FUNCTION__], [$event]);
        }

        if (!empty($this->cache->taggable)) {
            $tags = [
                'global-1',
            ];

            $this->cache->invalidateTags($tags);
        } else {
            $this->cache->clear();
        }
    }
}
