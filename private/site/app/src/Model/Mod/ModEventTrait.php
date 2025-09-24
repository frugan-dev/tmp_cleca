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

namespace App\Model\Mod;

use Laminas\Stdlib\ArrayUtils;
use Nette\Utils\Image;
use Slim\Psr7\UploadedFile;
use Symfony\Component\EventDispatcher\GenericEvent;

// https://symfony.com/doc/current/components/event_dispatcher/generic_event.html
// https://code.tutsplus.com/handling-events-in-your-php-applications-using-the-symfony-eventdispatcher-component--cms-31328t
// https://doeken.org/blog/event-dispatching-exploration
// https://tomasvotruba.com/blog/2020/05/25/the-bulletproof-event-naming-for-symfony-event-dispatcher
trait ModEventTrait
{
    public $watermarkPath;

    public static array $addAllListeners = [];

    public function addAllListeners(): void
    {
        /*$namespaceBase = substr($this->getNamespaceName(), 0, strpos($this->getNamespaceName(), '\\'));
        if (_NAMESPACE_BASE !== $namespaceBase) {
            return;
        }*/

        $this->addListeners('exist');
        $this->addListeners('existStrict');
        $this->addListeners('getCount');
        $this->addListeners('getOne');
        $this->addListeners('getAll');

        if (!empty(static::$addAllListeners[$this->modName])) {
            return;
        }

        static::$addAllListeners[$this->modName] = true;

        $this->addListeners('actionAdd');
        $this->addListeners('actionEdit');
        $this->addListeners('actionDelete');
        $this->addListeners('actionCopy');
        $this->addListeners('actionDeleteBulk');

        $this->addListeners('v1ActionPostAdd');
        $this->addListeners('v1ActionPutEdit');
        $this->addListeners('v1ActionDeleteDelete');
        $this->addListeners('v1ActionPostCopy');
        $this->addListeners('v1ActionDeleteDeleteBulk');

        $this->addListeners('treeFactoryCreate');
        $this->addListeners('treeFactoryCreatePerms');
        $this->addListeners('treeFactoryCreateApis');
    }

    public function addListeners($method): void
    {
        if (method_exists($this, __FUNCTION__.ucfirst((string) $method)) && \is_callable([$this, __FUNCTION__.ucfirst((string) $method)])) {
            \call_user_func([$this, __FUNCTION__.ucfirst((string) $method)]);
        }
    }

    public function removeAllListeners(): void
    {
        /*$namespaceBase = substr($this->getNamespaceName(), 0, strpos($this->getNamespaceName(), '\\'));
        if (_NAMESPACE_BASE !== $namespaceBase) {
            return;
        }*/

        $this->removeListeners('exist');
        $this->removeListeners('existStrict');
        $this->removeListeners('getCount');
        $this->removeListeners('getOne');
        $this->removeListeners('getAll');

        // $this->removeListeners('actionAdd');
        // $this->removeListeners('actionEdit');
        // $this->removeListeners('actionDelete');
        // $this->removeListeners('actionCopy');
        // $this->removeListeners('actionDeleteBulk');

        // $this->removeListeners('v1ActionPostAdd');
        // $this->removeListeners('v1ActionPutEdit');
        // $this->removeListeners('v1ActionDeleteDelete');
        // $this->removeListeners('v1ActionPostCopy');
        // $this->removeListeners('v1ActionDeleteDeleteBulk');

        // $this->removeListeners('treeFactoryCreate');
        // $this->removeListeners('treeFactoryCreatePerms');
        // $this->removeListeners('treeFactoryCreateApis');
    }

    public function removeListeners($method): void
    {
        if (method_exists($this, __FUNCTION__.ucfirst((string) $method)) && \is_callable([$this, __FUNCTION__.ucfirst((string) $method)])) {
            \call_user_func([$this, __FUNCTION__.ucfirst((string) $method)]);
        }
    }

    public function addListenersExist(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.exist.where', [$this, 'eventExistWhere']);
    }

    public function addListenersExistStrict(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.existStrict.preSelect', [$this, 'eventExistStrictPreSelect']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.existStrict.select', [$this, 'eventExistStrictSelect']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.existStrict.join', [$this, 'eventExistStrictJoin']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.existStrict.where', [$this, 'eventExistStrictWhere']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.existStrict.group', [$this, 'eventExistStrictGroup']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.existStrict.having', [$this, 'eventExistStrictHaving']);
    }

    public function addListenersGetCount(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getCount.preSelect', [$this, 'eventGetCountPreSelect']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getCount.select', [$this, 'eventGetCountSelect']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getCount.join', [$this, 'eventGetCountJoin']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getCount.where', [$this, 'eventGetCountWhere']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getCount.group', [$this, 'eventGetCountGroup']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getCount.having', [$this, 'eventGetCountHaving']);
    }

    public function addListenersGetOne(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getOne.preSelect', [$this, 'eventGetOnePreSelect']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getOne.select', [$this, 'eventGetOneSelect']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getOne.join', [$this, 'eventGetOneJoin']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getOne.where', [$this, 'eventGetOneWhere']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getOne.having', [$this, 'eventGetOneHaving']);
    }

    public function addListenersGetAll(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getAll.preSelect', [$this, 'eventGetAllPreSelect']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getAll.select', [$this, 'eventGetAllSelect']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getAll.join', [$this, 'eventGetAllJoin']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getAll.where', [$this, 'eventGetAllWhere']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getAll.group', [$this, 'eventGetAllGroup']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getAll.having', [$this, 'eventGetAllHaving']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.getAll.after', [$this, 'eventGetAllAfter']);
    }

    public function addListenersActionAdd(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.actionAdd.before', [$this, 'eventActionAddBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.actionAdd.after', [$this, 'eventActionAddAfter']);
    }

    public function addListenersActionEdit(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.actionEdit.before', [$this, 'eventActionEditBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.actionEdit.after', [$this, 'eventActionEditAfter']);
    }

    public function addListenersActionDelete(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.actionDelete.before', [$this, 'eventActionDeleteBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.actionDelete.after', [$this, 'eventActionDeleteAfter']);
    }

    public function addListenersActionCopy(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.actionCopy.before', [$this, 'eventActionCopyBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.actionCopy.after', [$this, 'eventActionCopyAfter']);
    }

    public function addListenersActionDeleteBulk(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.actionDeleteBulk.before', [$this, 'eventActionDeleteBulkBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.actionDeleteBulk.after', [$this, 'eventActionDeleteBulkAfter']);
    }

    public function addListenersV1ActionPostAdd(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPostAdd.before', [$this, 'eventV1ActionPostAddBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPostAdd.after', [$this, 'eventV1ActionPostAddAfter']);
    }

    public function addListenersV1ActionPutEdit(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPutEdit.before', [$this, 'eventV1ActionPutEditBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPutEdit.after', [$this, 'eventV1ActionPutEditAfter']);
    }

    public function addListenersV1ActionDeleteDelete(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'._v1ActionDeleteDelete.before', [$this, 'eventV1ActionDeleteDeleteBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'._v1ActionDeleteDelete.after', [$this, 'eventV1ActionDeleteDeleteAfter']);
    }

    public function addListenersV1ActionPostCopy(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPostCopy.before', [$this, 'eventV1ActionPostCopyBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPostCopy.after', [$this, 'eventV1ActionPostCopyAfter']);
    }

    public function addListenersV1ActionDeleteDeleteBulk(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'._v1ActionDeleteDeleteBulk.before', [$this, 'eventV1ActionDeleteDeleteBulkBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'._v1ActionDeleteDeleteBulk.after', [$this, 'eventV1ActionDeleteDeleteBulkAfter']);
    }

    public function addListenersTreeFactoryCreate(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.before', [$this, 'eventTreeFactoryCreateBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.after', [$this, 'eventTreeFactoryCreateAfter']);
    }

    public function addListenersTreeFactoryCreatePerms(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.perms.before', [$this, 'eventTreeFactoryCreatePermsBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.perms.after', [$this, 'eventTreeFactoryCreatePermsAfter']);
    }

    public function addListenersTreeFactoryCreateApis(): void
    {
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.apis.before', [$this, 'eventTreeFactoryCreateApisBefore']);
        $this->dispatcher->addListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.apis.after', [$this, 'eventTreeFactoryCreateApisAfter']);
    }

    public function removeListenersExist(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.exist.where', [$this, 'eventExistWhere']);
    }

    public function removeListenersExistStrict(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.existStrict.preSelect', [$this, 'eventExistStrictPreSelect']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.existStrict.select', [$this, 'eventExistStrictSelect']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.existStrict.join', [$this, 'eventExistStrictJoin']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.existStrict.where', [$this, 'eventExistStrictWhere']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.existStrict.group', [$this, 'eventExistStrictGroup']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.existStrict.having', [$this, 'eventExistStrictHaving']);
    }

    public function removeListenersGetCount(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getCount.preSelect', [$this, 'eventGetCountPreSelect']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getCount.select', [$this, 'eventGetCountSelect']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getCount.join', [$this, 'eventGetCountJoin']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getCount.where', [$this, 'eventGetCountWhere']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getCount.group', [$this, 'eventGetCountGroup']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getCount.having', [$this, 'eventGetCountHaving']);
    }

    public function removeListenersGetOne(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getOne.preSelect', [$this, 'eventGetOnePreSelect']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getOne.select', [$this, 'eventGetOneSelect']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getOne.join', [$this, 'eventGetOneJoin']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getOne.where', [$this, 'eventGetOneWhere']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getOne.having', [$this, 'eventGetOneHaving']);
    }

    public function removeListenersGetAll(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getAll.preSelect', [$this, 'eventGetAllPreSelect']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getAll.select', [$this, 'eventGetAllSelect']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getAll.join', [$this, 'eventGetAllJoin']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getAll.where', [$this, 'eventGetAllWhere']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getAll.group', [$this, 'eventGetAllGroup']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getAll.having', [$this, 'eventGetAllHaving']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.getAll.after', [$this, 'eventGetAllAfter']);
    }

    public function removeListenersActionAdd(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPostAdd.before', [$this, 'eventV1ActionPostAddBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPostAdd.after', [$this, 'eventV1ActionPostAddAfter']);
    }

    public function removeListenersActionEdit(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.actionEdit.before', [$this, 'eventActionEditBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.actionEdit.after', [$this, 'eventActionEditAfter']);
    }

    public function removeListenersActionDelete(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.actionDelete.before', [$this, 'eventActionDeleteBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.actionDelete.after', [$this, 'eventActionDeleteAfter']);
    }

    public function removeListenersActionCopy(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.actionCopy.before', [$this, 'eventActionCopyBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.actionCopy.after', [$this, 'eventActionCopyAfter']);
    }

    public function removeListenersActionDeleteBulk(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.actionDeleteBulk.before', [$this, 'eventActionDeleteBulkBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.actionDeleteBulk.after', [$this, 'eventActionDeleteBulkAfter']);
    }

    public function removeListenersV1ActionPostAdd(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPostAdd.before', [$this, 'eventV1ActionPostAddBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPostAdd.after', [$this, 'eventV1ActionPostAddAfter']);
    }

    public function removeListenersV1ActionPutEdit(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPutEdit.before', [$this, 'eventV1ActionPutEditBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPutEdit.after', [$this, 'eventV1ActionPutEditAfter']);
    }

    public function removeListenersV1ActionDeleteDelete(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionDeleteDelete.before', [$this, 'eventV1ActionDeleteDeleteBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionDeleteDelete.after', [$this, 'eventV1ActionDeleteDeleteAfter']);
    }

    public function removeListenersV1ActionPostCopy(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPostCopy.before', [$this, 'eventV1ActionPostCopyBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionPostCopy.after', [$this, 'eventV1ActionPostCopyAfter']);
    }

    public function removeListenersV1ActionDeleteDeleteBulk(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionDeleteDeleteBulk.before', [$this, 'eventV1ActionDeleteDeleteBulkBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'._v1ActionDeleteDeleteBulk.after', [$this, 'eventV1ActionDeleteDeleteBulkAfter']);
    }

    public function removeListenersTreeFactoryCreate(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.before', [$this, 'eventTreeFactoryCreateBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.after', [$this, 'eventTreeFactoryCreateAfter']);
    }

    public function removeListenersTreeFactoryCreatePerms(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.perms.before', [$this, 'eventTreeFactoryCreatePermsBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.perms.after', [$this, 'eventTreeFactoryCreatePermsAfter']);
    }

    public function removeListenersTreeFactoryCreateApis(): void
    {
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.apis.before', [$this, 'eventTreeFactoryCreateApisBefore']);
        $this->dispatcher->removeListener('event.'.static::$env.'.'.$this->modName.'.TreeFactory.create.apis.after', [$this, 'eventTreeFactoryCreateApisAfter']);
    }

    public function eventExistWhere(GenericEvent $event): void {}

    public function eventExistStrictPreSelect(GenericEvent $event): void {}

    public function eventExistStrictSelect(GenericEvent $event): void {}

    public function eventExistStrictJoin(GenericEvent $event): void {}

    public function eventExistStrictWhere(GenericEvent $event): void {}

    public function eventExistStrictGroup(GenericEvent $event): void {}

    public function eventExistStrictHaving(GenericEvent $event): void {}

    public function eventGetCountPreSelect(GenericEvent $event): void {}

    public function eventGetCountSelect(GenericEvent $event): void {}

    public function eventGetCountJoin(GenericEvent $event): void {}

    public function eventGetCountWhere(GenericEvent $event): void
    {
        $this->_eventFilter($event);
        $this->_eventSearch($event);
    }

    public function eventGetCountGroup(GenericEvent $event): void {}

    public function eventGetCountHaving(GenericEvent $event): void {}

    public function eventGetOnePreSelect(GenericEvent $event): void {}

    public function eventGetOneSelect(GenericEvent $event): void {}

    public function eventGetOneJoin(GenericEvent $event): void {}

    public function eventGetOneWhere(GenericEvent $event): void {}

    public function eventGetOneHaving(GenericEvent $event): void {}

    public function eventGetAllPreSelect(GenericEvent $event): void {}

    public function eventGetAllSelect(GenericEvent $event): void {}

    public function eventGetAllJoin(GenericEvent $event): void {}

    public function eventGetAllWhere(GenericEvent $event): void
    {
        $this->_eventFilter($event);
        $this->_eventSearch($event);
    }

    public function eventGetAllGroup(GenericEvent $event): void {}

    public function eventGetAllHaving(GenericEvent $event): void {}

    public function eventGetAllAfter(GenericEvent $event): void {}

    public function eventActionAddBefore(GenericEvent $event): void
    {
        $this->_handleUpload($event);
    }

    public function eventActionAddAfter(GenericEvent $event): void
    {
        $this->_disableOthers($event, [
            'id' => $event['id'] ?? null,
            'keys' => ['main', 'preselected'],
        ]);
    }

    public function eventV1ActionPostAddBefore(GenericEvent $event): void
    {
        $this->eventActionAddBefore($event);
    }

    public function eventV1ActionPostAddAfter(GenericEvent $event): void
    {
        $this->eventActionAddAfter($event);
    }

    public function eventActionEditBefore(GenericEvent $event): void
    {
        $this->_handleUpload($event);
        $this->_handleWatermark($event);
    }

    public function eventActionEditAfter(GenericEvent $event): void
    {
        $this->_disableOthers($event, [
            'id' => $this->id,
            'keys' => ['main', 'preselected'],
        ]);
        $this->_unlinkUpload($event);
    }

    public function eventV1ActionPutEditBefore(GenericEvent $event): void
    {
        $this->eventActionEditBefore($event);
    }

    public function eventV1ActionPutEditAfter(GenericEvent $event): void
    {
        $this->eventActionEditAfter($event);
    }

    public function eventActionDeleteBefore(GenericEvent $event): void {}

    public function eventActionDeleteAfter(GenericEvent $event): void
    {
        $this->_deleteUpload($event);
    }

    public function eventV1ActionDeleteDeleteBefore(GenericEvent $event): void
    {
        $this->eventActionDeleteBefore($event);
    }

    public function eventV1ActionDeleteDeleteAfter(GenericEvent $event): void
    {
        $this->eventActionDeleteAfter($event);
    }

    public function eventActionCopyBefore(GenericEvent $event): void {}

    public function eventActionCopyAfter(GenericEvent $event): void {}

    public function eventV1ActionPostCopyBefore(GenericEvent $event): void
    {
        $this->eventActionCopyBefore($event);
    }

    public function eventV1ActionPostCopyAfter(GenericEvent $event): void
    {
        $this->eventActionCopyAfter($event);
    }

    public function eventActionDeleteBulkBefore(GenericEvent $event): void {}

    public function eventActionDeleteBulkAfter(GenericEvent $event): void {}

    public function eventV1ActionDeleteDeleteBulkBefore(GenericEvent $event): void
    {
        $this->eventActionDeleteBulkBefore($event);
    }

    public function eventV1ActionDeleteDeleteBulkAfter(GenericEvent $event): void
    {
        $this->eventActionDeleteBulkAfter($event);
    }

    public function eventTreeFactoryCreateBefore(GenericEvent $event): void {}

    public function eventTreeFactoryCreateAfter(GenericEvent $event): void {}

    public function eventTreeFactoryCreatePermsBefore(GenericEvent $event): void {}

    public function eventTreeFactoryCreatePermsAfter(GenericEvent $event): void
    {
        $this->_handlePerms($event);
    }

    public function eventTreeFactoryCreateApisBefore(GenericEvent $event): void {}

    public function eventTreeFactoryCreateApisAfter(GenericEvent $event): void
    {
        $this->_handleApis($event);
    }

    public function _eventGetAllSelect(GenericEvent $event): void
    {
        $fieldsKeys = array_intersect(array_keys($this->fieldsMonolang), array_keys($this->fieldsSortable));

        // https://stackoverflow.com/a/3432266
        array_walk(
            $fieldsKeys,
            function (&$key): void {
                $key = 'a.'.$key;
            }
        );

        if (!empty($fieldsKeys)) {
            $this->dbData['sql'] = str_replace('a.*', implode(', ', $fieldsKeys), (string) $this->dbData['sql']);
        }

        $fieldsKeys = array_diff(array_keys($this->fieldsMultilang), array_keys($this->fieldsSortable));

        // https://stackoverflow.com/a/3432266
        array_walk(
            $fieldsKeys,
            function (&$key): void {
                $key = ', b.'.$key.' AS '.$key;
            }
        );

        if (!empty($fieldsKeys)) {
            $this->dbData['sql'] = str_replace($fieldsKeys, '', (string) $this->dbData['sql']);
        }
    }

    protected function _handlePerms(GenericEvent $event): void
    {
        if (!empty($this->allowedPerms)) {
            foreach ($this->config['mod.perms.action.arr'] as $action) {
                foreach ($this->container->get('envs') as $env) {
                    if (\Safe\preg_match('~'.implode('|', array_map('preg_quote', $this->allowedPerms, array_fill(0, \count($this->allowedPerms), '~'))).'~', $action.'.'.$env.'.'.$action, $matches)) {
                        $this->tree->getInstance()->set($event['traversePath'].'.perms.'.$action.'.'.$env, [$action]);
                    }
                }
            }
        }

        if (!empty($this->additionalPerms)) {
            foreach ($this->additionalPerms as $actionEnv => $perms) {
                if (\is_array($perms)) {
                    if (($oldPerms = $this->tree->getInstance()->get($event['traversePath'].'.perms.'.$actionEnv)) === null) {
                        $oldPerms = [];
                    }
                    $this->tree->getInstance()->set($event['traversePath'].'.perms.'.$actionEnv, array_merge($oldPerms, $perms));
                }
            }
        }
    }

    protected function _handleApis(GenericEvent $event): void
    {
        if (!empty($this->allowedApis)) {
            foreach ($this->config['mod.apis.arr'] as $method => $specs) {
                foreach ($specs as $endpoint => $spec) {
                    if (\Safe\preg_match('~'.implode('|', array_map('preg_quote', $this->allowedApis, array_fill(0, \count($this->allowedApis), '~'))).'~', (string) $endpoint, $matches)) {
                        $this->tree->getInstance()->set($event['traversePath'].'.apis.'.$method.'.'.$endpoint, $spec);
                    }
                }
            }
        }

        if (!empty($this->additionalApis)) {
            foreach ($this->additionalApis as $method => $specs) {
                if (\is_array($specs)) {
                    if (($oldSpec = $this->tree->getInstance()->get($event['traversePath'].'.apis.'.$method)) === null) {
                        $oldSpec = [];
                    }
                    // http://codelegance.com/array-merging-in-php/
                    $this->tree->getInstance()->set($event['traversePath'].'.apis.'.$method, ArrayUtils::merge($oldSpec, $specs/* , true */)); // <-- with preserveNumericKeys
                    // $this->tree->getInstance()->set($event['traversePath'] . '.apis.' . $method, array_merge_recursive($oldSpec, $specs));
                }
            }
        }
    }

    protected function _handleUpload(GenericEvent $event, array $params = []): void
    {
        $params = ArrayUtils::merge(
            [
                'type' => null,
                'subKey' => null,
            ],
            $params
        );

        if (\count($this->filesData) > 0) {
            foreach ($this->filesData as $key => $value) {
                $files = !\is_array($value) ? [$value] : $value;
                foreach ($files as $fileObj) {
                    if ($fileObj instanceof UploadedFile) {
                        if (UPLOAD_ERR_OK === $fileObj->getError()) {
                            $langId = null;
                            $filteredKey = $key;

                            if ($this->helper->Nette()->Strings()->contains($key, '|')) {
                                $keyArr = $this->helper->Nette()->Strings()->split($key, '~\|\s*~');

                                if (\is_array($keyArr)) {
                                    if (3 === \count($keyArr)) {
                                        $langId = (int) $keyArr[1];
                                        $filteredKey = $keyArr[2];
                                    }
                                }
                            }

                            if (\array_key_exists($filteredKey, $this->fields)) {
                                $field = $this->fields[$filteredKey][static::$env];

                                $type = $params['type'] ?? null;

                                if (empty($type)) {
                                    if (isset($field['attr']['type']) && 'file' === $field['attr']['type']) {
                                        if (\array_key_exists('data-type', $field['attr'])) {
                                            $type = $field['attr']['data-type'];
                                        }
                                    }
                                }

                                if (!empty($type)) {
                                    if (!empty($params['subKey'])) {
                                        $postValue = $this->postData['_'.$key][$params['subKey']] ?? $this->postData[$key][$params['subKey']] ?? null;
                                    } else {
                                        $postValue = $this->postData['_'.$key] ?? $this->postData[$key] ?? null;
                                    }

                                    if (!empty($postValue)) {
                                        // TODO - support array $postValue

                                        $sizeArr = $this->config['mod.'.$this->modName.'.'.$type.'.'.$filteredKey.'.sizeArr'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.sizeArr'] ?? $this->config['media.'.$type.'.sizeArr'] ?? null;

                                        $dest = $params['dest'] ?? _PUBLIC.'/media/'.$type.'/'.$this->modName;

                                        if (!empty($langId)) {
                                            $dest .= '/'.$this->lang->codeArr[$langId];

                                            if (!empty($params['subKey'])) {
                                                $oldValue = $this->multilang[$langId][$filteredKey][$params['subKey']] ?? null;
                                            } else {
                                                $oldValue = $this->multilang[$langId][$filteredKey] ?? null;
                                            }
                                        } else {
                                            if (!empty($params['subKey'])) {
                                                $oldValue = $this->{$filteredKey}[$params['subKey']] ?? null;
                                            } else {
                                                $oldValue = $this->{$filteredKey} ?? null;
                                            }
                                        }

                                        // TODO - support array from json $oldValue

                                        if (!empty($sizeArr)) {
                                            if (!empty($params['subKey'])) {
                                                $postExifOrientationValue = $this->postData[$filteredKey.'_exif_orientation'][$params['subKey']] ?? null;
                                                $postWatermarkPathValue = $this->postData[$filteredKey.'_watermark_path'][$params['subKey']] ?? null;
                                                $postWatermarkPositionValue = $this->postData[$filteredKey.'_watermark_position'][$params['subKey']] ?? null;
                                                $postWatermarkOpacityValue = $this->postData[$filteredKey.'_watermark_opacity'][$params['subKey']] ?? null;
                                            } else {
                                                $postExifOrientationValue = $this->postData[$filteredKey.'_exif_orientation'] ?? null;
                                                $postWatermarkPathValue = $this->postData[$filteredKey.'_watermark_path'] ?? null;
                                                $postWatermarkPositionValue = $this->postData[$filteredKey.'_watermark_position'] ?? null;
                                                $postWatermarkOpacityValue = $this->postData[$filteredKey.'_watermark_opacity'] ?? null;
                                            }

                                            $srcDest = $dest.'/src/'.$postValue;

                                            $this->helper->Nette()->FileSystem()->createDir(\dirname($srcDest));

                                            // https://stackoverflow.com/a/61182259/3929620
                                            $fileObj->moveTo($srcDest);

                                            $srcType = $this->helper->Nette()->Image()->detectTypeFromFile($srcDest);

                                            if (\in_array($srcType, [Image::PNG, Image::WEBP], true)) {
                                                $hasAlphaChannel = $this->helper->Image()->hasAlphaChannel($srcDest);
                                            }

                                            if (!empty($oldValue) && $oldValue !== $postValue) {
                                                $oldFileDest = $dest.'/src/'.$oldValue;
                                                $oldFileType = $this->helper->Nette()->Image()->detectTypeFromFile($oldFileDest);

                                                $this->helper->Nette()->FileSystem()->delete($oldFileDest);
                                            }

                                            foreach ($sizeArr as $sizeKey => $sizeVal) {
                                                $destTypeArr = !empty($sizeVal['type']) ? (array) $sizeVal['type'] : [null];

                                                foreach ($destTypeArr as $destType) {
                                                    $image = $this->helper->Nette()->Image()->fromFile($srcDest);

                                                    // https://forum.nette.org/cs/34396-utils-image-auto-orientace-obrazku
                                                    // https://github.com/recurser/exif-orientation-examples
                                                    // https://www.daveperrett.com/articles/2012/07/28/exif-orientation-handling-is-a-ghetto/
                                                    // https://www.impulseadventure.com/photo/exif-orientation.html
                                                    // https://stackoverflow.com/a/33031994
                                                    // https://stackoverflow.com/a/16761966
                                                    // http://www.php.net/manual/en/function.exif-read-data.php#76964
                                                    // https://stackoverflow.com/a/3615106
                                                    if (\in_array($srcType, [Image::JPEG], true)
                                                        && \function_exists('exif_read_data')
                                                        && !empty($postExifOrientationValue ?? $sizeVal['exifOrientation'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$filteredKey.'.exif.orientation'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.exif.orientation'] ?? $this->config['media.'.$type.'.exif.orientation'] ?? null)) {
                                                        // https://stackoverflow.com/a/8864064
                                                        $exif = @exif_read_data($srcDest);

                                                        if (!empty($exif['Orientation'])) {
                                                            switch ((int) $exif['Orientation']) {
                                                                case 1: // nothing
                                                                    break;

                                                                case 2: // flip horizontal
                                                                    \call_user_func_array([$image, 'flip'], [IMG_FLIP_HORIZONTAL]);

                                                                    break;

                                                                case 3: // rotate 180 degrees
                                                                    \call_user_func_array([$image, 'rotate'], [180, 0]);

                                                                    break;

                                                                case 4: // flip vertical
                                                                    \call_user_func_array([$image, 'flip'], [IMG_FLIP_VERTICAL]);

                                                                    break;

                                                                case 5: // flip vertical + rotate 90 degrees counter-clockwise
                                                                    \call_user_func_array([$image, 'flip'], [IMG_FLIP_VERTICAL]);
                                                                    \call_user_func_array([$image, 'rotate'], [270, 0]);

                                                                    break;

                                                                case 6: // rotate 90 degrees counter-clockwise
                                                                    \call_user_func_array([$image, 'rotate'], [270, 0]);

                                                                    break;

                                                                case 7: // flip horizontal + rotate 90 degrees counter-clockwise
                                                                    \call_user_func_array([$image, 'flip'], [IMG_FLIP_HORIZONTAL]);
                                                                    \call_user_func_array([$image, 'rotate'], [270, 0]);

                                                                    break;

                                                                case 8: // rotate 90 degrees clockwise
                                                                    \call_user_func_array([$image, 'rotate'], [90, 0]);

                                                                    break;
                                                            }
                                                        }
                                                    }

                                                    $args = [];

                                                    $args[] = $sizeVal['width'] ?? null;
                                                    $args[] = $sizeVal['height'] ?? null;

                                                    if (!empty($sizeVal['flag'])) {
                                                        $args[] = $sizeVal['flag'];
                                                    }

                                                    \call_user_func_array([$image, 'resize'], array_map(fn ($item) => empty($item) ? null : $item, $args));

                                                    $this->watermarkPath = $postWatermarkPathValue ?? $sizeVal['watermarkPath'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$filteredKey.'.watermark.path'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.watermark.path'] ?? $this->config['media.'.$type.'.watermark.path'] ?? null;

                                                    $this->dispatcher->dispatch(new GenericEvent(arguments: [
                                                        'key' => $filteredKey,
                                                        'sizeKey' => $sizeKey,
                                                        'sizeVal' => $sizeVal,
                                                        'field' => $field,
                                                    ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.watermarkPath');

                                                    $watermarkPosition = $postWatermarkPositionValue ?? $sizeVal['watermarkPosition'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$filteredKey.'.watermark.position'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.watermark.position'] ?? $this->config['media.'.$type.'.watermark.position'] ?? null;

                                                    if (!empty($this->watermarkPath) && !empty($watermarkPosition)) {
                                                        if (file_exists($this->watermarkPath)) {
                                                            $watermark = $this->helper->Nette()->Image()->fromFile($this->watermarkPath);

                                                            $watermarkOpacity = $postWatermarkOpacityValue ?? $sizeVal['watermarkOpacity'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$filteredKey.'.watermark.opacity'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.watermark.opacity'] ?? $this->config['media.'.$type.'.watermark.opacity'] ?? null;

                                                            switch ($watermarkPosition) {
                                                                case 'TL':
                                                                    $left = '1%';
                                                                    $top = '1%';

                                                                    break;

                                                                case 'TC':
                                                                    $left = '50%';
                                                                    $top = '1%';

                                                                    break;

                                                                case 'TR':
                                                                    $left = '99%';
                                                                    $top = '1%';

                                                                    break;

                                                                case 'CL':
                                                                    $left = '1%';
                                                                    $top = '50%';

                                                                    break;

                                                                case 'CC':
                                                                    $left = '50%';
                                                                    $top = '50%';

                                                                    break;

                                                                case 'CR':
                                                                    $left = '99%';
                                                                    $top = '50%';

                                                                    break;

                                                                case 'BL':
                                                                    $left = '1%';
                                                                    $top = '99%';

                                                                    break;

                                                                case 'BC':
                                                                    $left = '50%';
                                                                    $top = '99%';

                                                                    break;

                                                                default:
                                                                    $left = '99%';
                                                                    $top = '99%';
                                                            }

                                                            $args = [];

                                                            $args[] = $watermark;
                                                            $args[] = $left;
                                                            $args[] = $top;

                                                            if (!empty($watermarkOpacity)) {
                                                                $args[] = $watermarkOpacity;
                                                            }

                                                            \call_user_func_array([$image, 'place'], $args);
                                                        }
                                                    }

                                                    $fileDest = $dest.'/'.$sizeKey.'/'.$postValue;

                                                    if (!empty($destType)) {
                                                        if ($srcType !== $destType) {
                                                            $parts = explode('.', (string) $postValue);
                                                            array_pop($parts);

                                                            $fileDest = $dest.'/'.$sizeKey.'/'.implode('.', $parts).'.'.$this->helper->Nette()->Image()->typeToExtension($destType);

                                                            // https://github.com/php-imagine/Imagine/issues/283
                                                            // https://stackoverflow.com/a/2570015/3929620
                                                            // https://www.quora.com/What-happens-to-an-alpha-channel-if-you-save-or-convert-a-PNG-image-to-a-JPG-file
                                                            if (!empty($hasAlphaChannel) && !\in_array($destType, [Image::PNG, Image::WEBP], true)) {
                                                                $blank = $this->helper->Nette()->Image()->fromBlank($image->getWidth(), $image->getHeight(), $this->helper->Nette()->Image()->rgb(255, 255, 255));
                                                                $image = $blank->place($image);
                                                            }
                                                        }
                                                    }

                                                    $this->helper->Nette()->FileSystem()->createDir(\dirname($fileDest));

                                                    $args = [];

                                                    $args[] = $fileDest;
                                                    $args[] = $sizeVal['quality'] ?? null;
                                                    $args[] = $destType;

                                                    \call_user_func_array([$image, 'save'], $args);

                                                    if (!empty($oldValue) && $oldValue !== $postValue) {
                                                        $oldFileDest = $dest.'/'.$sizeKey.'/'.$oldValue;

                                                        if (!empty($destType)) {
                                                            if ($oldFileType !== $destType) {
                                                                $parts = explode('.', $oldValue);
                                                                array_pop($parts);

                                                                $oldFileDest = $dest.'/'.$sizeKey.'/'.implode('.', $parts).'.'.$this->helper->Nette()->Image()->typeToExtension($destType);
                                                            }
                                                        }

                                                        $this->helper->Nette()->FileSystem()->delete($oldFileDest);
                                                    }
                                                }
                                            }
                                        } else {
                                            $fileDest = $dest.'/'.$postValue;

                                            $this->helper->Nette()->FileSystem()->createDir(\dirname($fileDest));

                                            // https://stackoverflow.com/a/61182259/3929620
                                            $fileObj->moveTo($fileDest);

                                            if (!empty($oldValue) && $oldValue !== $postValue) {
                                                $oldFileDest = $dest.'/'.$oldValue;

                                                $this->helper->Nette()->FileSystem()->delete($oldFileDest);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function _handleWatermark(GenericEvent $event, array $params = []): void
    {
        $params = ArrayUtils::merge(
            [
                'type' => null,
                'subKey' => null,
            ],
            $params
        );

        foreach ($this->fields as $key => $val) {
            $field = $val[static::$env];
            $type = $params['type'] ?? null;

            if (empty($type)) {
                if (isset($field['attr']['type']) && 'file' === $field['attr']['type']) {
                    if (\array_key_exists('data-type', $field['attr'])) {
                        $type = $field['attr']['data-type'];
                    }
                }
            }

            if (!empty($type)) {
                $sizeArr = $this->config['mod.'.$this->modName.'.'.$type.'.'.$key.'.sizeArr'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.sizeArr'] ?? $this->config['media.'.$type.'.sizeArr'] ?? null;

                if (!empty($sizeArr)) {
                    $dest = $params['dest'] ?? _PUBLIC.'/media/'.$type.'/'.$this->modName;

                    if (!empty($val['multilang'])) {
                        foreach ($this->lang->arr as $langId => $langRow) {
                            if (!empty($params['subKey'])) {
                                $postValue = $this->postData['_multilang|'.$langId.'|'.$key][$params['subKey']] ?? $this->postData['multilang|'.$langId.'|'.$key][$params['subKey']] ?? null;
                            } else {
                                $postValue = $this->postData['_multilang|'.$langId.'|'.$key] ?? $this->postData['multilang|'.$langId.'|'.$key] ?? null;
                            }

                            if (!empty($postValue)) {
                                // TODO - support array $postValue
                                continue;
                            }

                            if (!empty($params['subKey'])) {
                                $postExifOrientationValue = $this->postData['multilang|'.$langId.'|'.$key.'_exif_orientation'][$params['subKey']] ?? $this->postData[$key.'_exif_orientation'][$params['subKey']] ?? null;
                                $postWatermarkPathValue = $this->postData['multilang|'.$langId.'|'.$key.'_watermark_path'][$params['subKey']] ?? $this->postData[$key.'_watermark_path'][$params['subKey']] ?? null;
                                $postWatermarkPositionValue = $this->postData['multilang|'.$langId.'|'.$key.'_watermark_position'][$params['subKey']] ?? $this->postData[$key.'_watermark_position'][$params['subKey']] ?? null;
                                $postWatermarkOpacityValue = $this->postData['multilang|'.$langId.'|'.$key.'_watermark_opacity'][$params['subKey']] ?? $this->postData[$key.'_watermark_opacity'][$params['subKey']] ?? null;
                            } else {
                                $postExifOrientationValue = $this->postData['multilang|'.$langId.'|'.$key.'_exif_orientation'] ?? $this->postData[$key.'_exif_orientation'] ?? null;
                                $postWatermarkPathValue = $this->postData['multilang|'.$langId.'|'.$key.'_watermark_path'] ?? $this->postData[$key.'_watermark_path'] ?? null;
                                $postWatermarkPositionValue = $this->postData['multilang|'.$langId.'|'.$key.'_watermark_position'] ?? $this->postData[$key.'_watermark_position'] ?? null;
                                $postWatermarkOpacityValue = $this->postData['multilang|'.$langId.'|'.$key.'_watermark_opacity'] ?? $this->postData[$key.'_watermark_opacity'] ?? null;
                            }

                            if (!empty($postWatermarkPositionValue)) {
                                if (!empty($params['subKey'])) {
                                    $watermarkPositionValue = $this->multilang[$langId][$key.'_watermark_position'][$params['subKey']] ?? null;
                                } else {
                                    $watermarkPositionValue = $this->multilang[$langId][$key.'_watermark_position'] ?? null;
                                }

                                if ($postWatermarkPositionValue !== $watermarkPositionValue) {
                                    if (!empty($params['subKey'])) {
                                        $value = $this->multilang[$langId][$key][$params['subKey']] ?? null;
                                    } else {
                                        $value = $this->multilang[$langId][$key] ?? null;
                                    }

                                    if (!empty($value)) {
                                        // TODO - support array from json $value

                                        $srcDest = $dest.'/'.$langRow['isoCode'].'/src/'.$value;
                                        $srcType = $this->helper->Nette()->Image()->detectTypeFromFile($srcDest);

                                        if (\in_array($srcType, [Image::PNG, Image::WEBP], true)) {
                                            $hasAlphaChannel = $this->helper->Image()->hasAlphaChannel($srcDest);
                                        }

                                        foreach ($sizeArr as $sizeKey => $sizeVal) {
                                            $destTypeArr = !empty($sizeVal['type']) ? (array) $sizeVal['type'] : [null];

                                            foreach ($destTypeArr as $destType) {
                                                $image = $this->helper->Nette()->Image()->fromFile($srcDest);

                                                // https://forum.nette.org/cs/34396-utils-image-auto-orientace-obrazku
                                                // https://github.com/recurser/exif-orientation-examples
                                                // https://www.daveperrett.com/articles/2012/07/28/exif-orientation-handling-is-a-ghetto/
                                                // https://www.impulseadventure.com/photo/exif-orientation.html
                                                // https://stackoverflow.com/a/33031994
                                                // https://stackoverflow.com/a/16761966
                                                // http://www.php.net/manual/en/function.exif-read-data.php#76964
                                                // https://stackoverflow.com/a/3615106
                                                if (\in_array($srcType, [Image::JPEG], true)
                                                    && \function_exists('exif_read_data')
                                                    && !empty($postExifOrientationValue ?? $sizeVal['exifOrientation'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$key.'.exif.orientation'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.exif.orientation'] ?? $this->config['media.'.$type.'.exif.orientation'] ?? null)) {
                                                    // https://stackoverflow.com/a/8864064
                                                    $exif = @exif_read_data($srcDest);

                                                    if (!empty($exif['Orientation'])) {
                                                        switch ((int) $exif['Orientation']) {
                                                            case 1: // nothing
                                                                break;

                                                            case 2: // flip horizontal
                                                                \call_user_func_array([$image, 'flip'], [IMG_FLIP_HORIZONTAL]);

                                                                break;

                                                            case 3: // rotate 180 degrees
                                                                \call_user_func_array([$image, 'rotate'], [180, 0]);

                                                                break;

                                                            case 4: // flip vertical
                                                                \call_user_func_array([$image, 'flip'], [IMG_FLIP_VERTICAL]);

                                                                break;

                                                            case 5: // flip vertical + rotate 90 degrees counter-clockwise
                                                                \call_user_func_array([$image, 'flip'], [IMG_FLIP_VERTICAL]);
                                                                \call_user_func_array([$image, 'rotate'], [270, 0]);

                                                                break;

                                                            case 6: // rotate 90 degrees counter-clockwise
                                                                \call_user_func_array([$image, 'rotate'], [270, 0]);

                                                                break;

                                                            case 7: // flip horizontal + rotate 90 degrees counter-clockwise
                                                                \call_user_func_array([$image, 'flip'], [IMG_FLIP_HORIZONTAL]);
                                                                \call_user_func_array([$image, 'rotate'], [270, 0]);

                                                                break;

                                                            case 8: // rotate 90 degrees clockwise
                                                                \call_user_func_array([$image, 'rotate'], [90, 0]);

                                                                break;
                                                        }
                                                    }
                                                }

                                                $args = [];

                                                $args[] = $sizeVal['width'] ?? null;
                                                $args[] = $sizeVal['height'] ?? null;

                                                if (!empty($sizeVal['flag'])) {
                                                    $args[] = $sizeVal['flag'];
                                                }

                                                \call_user_func_array([$image, 'resize'], array_map(fn ($item) => empty($item) ? null : $item, $args));

                                                $this->watermarkPath = $postWatermarkPathValue ?? $sizeVal['watermarkPath'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$key.'.watermark.path'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.watermark.path'] ?? $this->config['media.'.$type.'.watermark.path'] ?? null;

                                                $this->dispatcher->dispatch(new GenericEvent(arguments: [
                                                    'key' => $key,
                                                    'sizeKey' => $sizeKey,
                                                    'sizeVal' => $sizeVal,
                                                    'field' => $field,
                                                ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.watermarkPath');

                                                $watermarkPosition = $postWatermarkPositionValue ?? $sizeVal['watermarkPosition'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$key.'.watermark.position'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.watermark.position'] ?? $this->config['media.'.$type.'.watermark.position'] ?? null;

                                                if (!empty($this->watermarkPath) && !empty($watermarkPosition)) {
                                                    if (file_exists($this->watermarkPath)) {
                                                        $watermark = $this->helper->Nette()->Image()->fromFile($this->watermarkPath);

                                                        $watermarkOpacity = $postWatermarkOpacityValue ?? $sizeVal['watermarkOpacity'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$key.'.watermark.opacity'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.watermark.opacity'] ?? $this->config['media.'.$type.'.watermark.opacity'] ?? null;

                                                        switch ($postWatermarkPositionValue) {
                                                            case 'TL':
                                                                $left = '1%';
                                                                $top = '1%';

                                                                break;

                                                            case 'TC':
                                                                $left = '50%';
                                                                $top = '1%';

                                                                break;

                                                            case 'TR':
                                                                $left = '99%';
                                                                $top = '1%';

                                                                break;

                                                            case 'CL':
                                                                $left = '1%';
                                                                $top = '50%';

                                                                break;

                                                            case 'CC':
                                                                $left = '50%';
                                                                $top = '50%';

                                                                break;

                                                            case 'CR':
                                                                $left = '99%';
                                                                $top = '50%';

                                                                break;

                                                            case 'BL':
                                                                $left = '1%';
                                                                $top = '99%';

                                                                break;

                                                            case 'BC':
                                                                $left = '50%';
                                                                $top = '99%';

                                                                break;

                                                            default:
                                                                $left = '99%';
                                                                $top = '99%';
                                                        }

                                                        $args = [];

                                                        $args[] = $watermark;
                                                        $args[] = $left;
                                                        $args[] = $top;

                                                        if (!empty($watermarkOpacity)) {
                                                            $args[] = $watermarkOpacity;
                                                        }

                                                        \call_user_func_array([$image, 'place'], $args);
                                                    }
                                                }

                                                $fileDest = $dest.'/'.$langRow['isoCode'].'/'.$sizeKey.'/'.$value;

                                                if (!empty($destType)) {
                                                    if ($srcType !== $destType) {
                                                        $parts = explode('.', (string) $value);
                                                        array_pop($parts);

                                                        $fileDest = $dest.'/'.$langRow['isoCode'].'/'.$sizeKey.'/'.implode('.', $parts).'.'.$this->helper->Nette()->Image()->typeToExtension($destType);

                                                        // https://github.com/php-imagine/Imagine/issues/283
                                                        // https://stackoverflow.com/a/2570015/3929620
                                                        // https://www.quora.com/What-happens-to-an-alpha-channel-if-you-save-or-convert-a-PNG-image-to-a-JPG-file
                                                        if (!empty($hasAlphaChannel) && !\in_array($destType, [Image::PNG, Image::WEBP], true)) {
                                                            $blank = $this->helper->Nette()->Image()->fromBlank($image->getWidth(), $image->getHeight(), $this->helper->Nette()->Image()->rgb(255, 255, 255));
                                                            $image = $blank->place($image);
                                                        }
                                                    }
                                                }

                                                $this->helper->Nette()->FileSystem()->createDir(\dirname($fileDest));

                                                $args = [];

                                                $args[] = $fileDest;
                                                $args[] = $sizeVal['quality'] ?? null;
                                                $args[] = $destType;

                                                \call_user_func_array([$image, 'save'], $args);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        if (!empty($params['subKey'])) {
                            $postValue = $this->postData['_'.$key][$params['subKey']] ?? $this->postData[$key][$params['subKey']] ?? null;
                        } else {
                            $postValue = $this->postData['_'.$key] ?? $this->postData[$key] ?? null;
                        }

                        if (!empty($postValue)) {
                            // TODO - support array $postValue

                            continue;
                        }

                        if (!empty($params['subKey'])) {
                            $postExifOrientationValue = $this->postData[$key.'_exif_orientation'][$params['subKey']] ?? null;
                            $postWatermarkPathValue = $this->postData[$key.'_watermark_path'][$params['subKey']] ?? null;
                            $postWatermarkPositionValue = $this->postData[$key.'_watermark_position'][$params['subKey']] ?? null;
                            $postWatermarkOpacityValue = $this->postData[$key.'_watermark_opacity'][$params['subKey']] ?? null;
                        } else {
                            $postExifOrientationValue = $this->postData[$key.'_exif_orientation'] ?? null;
                            $postWatermarkPathValue = $this->postData[$key.'_watermark_path'] ?? null;
                            $postWatermarkPositionValue = $this->postData[$key.'_watermark_position'] ?? null;
                            $postWatermarkOpacityValue = $this->postData[$key.'_watermark_opacity'] ?? null;
                        }

                        if (!empty($postWatermarkPositionValue)) {
                            if (!empty($params['subKey'])) {
                                $watermarkPositionValue = $this->{$key.'_watermark_position'}[$params['subKey']] ?? null;
                            } else {
                                $watermarkPositionValue = $this->{$key.'_watermark_position'} ?? null;
                            }

                            if ($postWatermarkPositionValue !== $watermarkPositionValue) {
                                if (!empty($params['subKey'])) {
                                    $value = $this->{$key}[$params['subKey']] ?? null;
                                } else {
                                    $value = $this->{$key} ?? null;
                                }

                                if (!empty($value)) {
                                    // TODO - support array from json $value

                                    $srcDest = $dest.'/src/'.$value;
                                    $srcType = $this->helper->Nette()->Image()->detectTypeFromFile($srcDest);

                                    if (\in_array($srcType, [Image::PNG, Image::WEBP], true)) {
                                        $hasAlphaChannel = $this->helper->Image()->hasAlphaChannel($srcDest);
                                    }

                                    foreach ($sizeArr as $sizeKey => $sizeVal) {
                                        $destTypeArr = !empty($sizeVal['type']) ? (array) $sizeVal['type'] : [null];

                                        foreach ($destTypeArr as $destType) {
                                            $image = $this->helper->Nette()->Image()->fromFile($srcDest);

                                            // https://forum.nette.org/cs/34396-utils-image-auto-orientace-obrazku
                                            // https://github.com/recurser/exif-orientation-examples
                                            // https://www.daveperrett.com/articles/2012/07/28/exif-orientation-handling-is-a-ghetto/
                                            // https://www.impulseadventure.com/photo/exif-orientation.html
                                            // https://stackoverflow.com/a/33031994
                                            // https://stackoverflow.com/a/16761966
                                            // http://www.php.net/manual/en/function.exif-read-data.php#76964
                                            // https://stackoverflow.com/a/3615106
                                            if (\in_array($srcType, [Image::JPEG], true)
                                                && \function_exists('exif_read_data')
                                                && !empty($postExifOrientationValue ?? $sizeVal['exifOrientation'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$key.'.exif.orientation'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.exif.orientation'] ?? $this->config['media.'.$type.'.exif.orientation'] ?? null)) {
                                                // https://stackoverflow.com/a/8864064
                                                $exif = @exif_read_data($srcDest);

                                                if (!empty($exif['Orientation'])) {
                                                    switch ((int) $exif['Orientation']) {
                                                        case 1: // nothing
                                                            break;

                                                        case 2: // flip horizontal
                                                            \call_user_func_array([$image, 'flip'], [IMG_FLIP_HORIZONTAL]);

                                                            break;

                                                        case 3: // rotate 180 degrees
                                                            \call_user_func_array([$image, 'rotate'], [180, 0]);

                                                            break;

                                                        case 4: // flip vertical
                                                            \call_user_func_array([$image, 'flip'], [IMG_FLIP_VERTICAL]);

                                                            break;

                                                        case 5: // flip vertical + rotate 90 degrees counter-clockwise
                                                            \call_user_func_array([$image, 'flip'], [IMG_FLIP_VERTICAL]);
                                                            \call_user_func_array([$image, 'rotate'], [270, 0]);

                                                            break;

                                                        case 6: // rotate 90 degrees counter-clockwise
                                                            \call_user_func_array([$image, 'rotate'], [270, 0]);

                                                            break;

                                                        case 7: // flip horizontal + rotate 90 degrees counter-clockwise
                                                            \call_user_func_array([$image, 'flip'], [IMG_FLIP_HORIZONTAL]);
                                                            \call_user_func_array([$image, 'rotate'], [270, 0]);

                                                            break;

                                                        case 8: // rotate 90 degrees clockwise
                                                            \call_user_func_array([$image, 'rotate'], [90, 0]);

                                                            break;
                                                    }
                                                }
                                            }

                                            $args = [];

                                            $args[] = $sizeVal['width'] ?? null;
                                            $args[] = $sizeVal['height'] ?? null;

                                            if (!empty($sizeVal['flag'])) {
                                                $args[] = $sizeVal['flag'];
                                            }

                                            \call_user_func_array([$image, 'resize'], array_map(fn ($item) => empty($item) ? null : $item, $args));

                                            $this->watermarkPath = $postWatermarkPathValue ?? $sizeVal['watermarkPath'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$key.'.watermark.path'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.watermark.path'] ?? $this->config['media.'.$type.'.watermark.path'] ?? null;

                                            $this->dispatcher->dispatch(new GenericEvent(arguments: [
                                                'key' => $key,
                                                'sizeKey' => $sizeKey,
                                                'sizeVal' => $sizeVal,
                                                'field' => $field,
                                            ]), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.watermarkPath');

                                            $watermarkPosition = $postWatermarkPositionValue ?? $sizeVal['watermarkPosition'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$key.'.watermark.position'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.watermark.position'] ?? $this->config['media.'.$type.'.watermark.position'] ?? null;

                                            if (!empty($this->watermarkPath) && !empty($watermarkPosition)) {
                                                if (file_exists($this->watermarkPath)) {
                                                    $watermark = $this->helper->Nette()->Image()->fromFile($this->watermarkPath);

                                                    $watermarkOpacity = $postWatermarkOpacityValue ?? $sizeVal['watermarkOpacity'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.'.$key.'.watermark.opacity'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.watermark.opacity'] ?? $this->config['media.'.$type.'.watermark.opacity'] ?? null;

                                                    switch ($postWatermarkPositionValue) {
                                                        case 'TL':
                                                            $left = '1%';
                                                            $top = '1%';

                                                            break;

                                                        case 'TC':
                                                            $left = '50%';
                                                            $top = '1%';

                                                            break;

                                                        case 'TR':
                                                            $left = '99%';
                                                            $top = '1%';

                                                            break;

                                                        case 'CL':
                                                            $left = '1%';
                                                            $top = '50%';

                                                            break;

                                                        case 'CC':
                                                            $left = '50%';
                                                            $top = '50%';

                                                            break;

                                                        case 'CR':
                                                            $left = '99%';
                                                            $top = '50%';

                                                            break;

                                                        case 'BL':
                                                            $left = '1%';
                                                            $top = '99%';

                                                            break;

                                                        case 'BC':
                                                            $left = '50%';
                                                            $top = '99%';

                                                            break;

                                                        default:
                                                            $left = '99%';
                                                            $top = '99%';
                                                    }

                                                    $args = [];

                                                    $args[] = $watermark;
                                                    $args[] = $left;
                                                    $args[] = $top;

                                                    if (!empty($watermarkOpacity)) {
                                                        $args[] = $watermarkOpacity;
                                                    }

                                                    \call_user_func_array([$image, 'place'], $args);
                                                }
                                            }

                                            $fileDest = $dest.'/'.$sizeKey.'/'.$value;

                                            if (!empty($destType)) {
                                                if ($srcType !== $destType) {
                                                    $parts = explode('.', $value);
                                                    array_pop($parts);

                                                    $fileDest = $dest.'/'.$sizeKey.'/'.implode('.', $parts).'.'.$this->helper->Nette()->Image()->typeToExtension($destType);

                                                    // https://github.com/php-imagine/Imagine/issues/283
                                                    // https://stackoverflow.com/a/2570015/3929620
                                                    // https://www.quora.com/What-happens-to-an-alpha-channel-if-you-save-or-convert-a-PNG-image-to-a-JPG-file
                                                    if (!empty($hasAlphaChannel) && !\in_array($destType, [Image::PNG, Image::WEBP], true)) {
                                                        $blank = $this->helper->Nette()->Image()->fromBlank($image->getWidth(), $image->getHeight(), $this->helper->Nette()->Image()->rgb(255, 255, 255));
                                                        $image = $blank->place($image);
                                                    }
                                                }
                                            }

                                            $this->helper->Nette()->FileSystem()->createDir(\dirname($fileDest));

                                            $args = [];

                                            $args[] = $fileDest;
                                            $args[] = $sizeVal['quality'] ?? null;
                                            $args[] = $destType;

                                            \call_user_func_array([$image, 'save'], $args);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    protected function _unlinkUpload(GenericEvent $event, array $params = []): void
    {
        $params = ArrayUtils::merge(
            [
                'type' => null,
                'subKey' => null,
            ],
            $params
        );

        foreach ($this->fields as $key => $val) {
            $field = $val[static::$env];
            $type = $params['type'] ?? null;

            if (empty($type)) {
                if (isset($field['attr']['type']) && 'file' === $field['attr']['type']) {
                    if (\array_key_exists('data-type', $field['attr'])) {
                        $type = $field['attr']['data-type'];
                    }
                }
            }

            if (!empty($type)) {
                $sizeArr = $this->config['mod.'.$this->modName.'.'.$type.'.'.$key.'.sizeArr'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.sizeArr'] ?? $this->config['media.'.$type.'.sizeArr'] ?? null;

                $dest = $params['dest'] ?? _PUBLIC.'/media/'.$type.'/'.$this->modName;

                if (!empty($val['multilang'])) {
                    foreach ($this->lang->arr as $langId => $langRow) {
                        if (!empty($params['subKey'])) {
                            $unlinkValue = $this->postData['multilang|'.$langId.'|unlink_'.$key][$params['subKey']] ?? null;
                        } else {
                            $unlinkValue = $this->postData['multilang|'.$langId.'|unlink_'.$key] ?? null;
                        }

                        if (!empty($unlinkValue)) {
                            if (!empty($params['subKey'])) {
                                $value = $this->multilang[$langId][$key][$params['subKey']] ?? null;
                            } else {
                                $value = $this->multilang[$langId][$key] ?? null;
                            }

                            if (!empty($value)) {
                                // TODO - support array from json $value

                                if (!empty($params['subKey'])) {
                                    $postValue = $this->postData['_multilang|'.$langId.'|'.$key][$params['subKey']] ?? $this->postData['multilang|'.$langId.'|'.$key][$params['subKey']] ?? null;
                                } else {
                                    $postValue = $this->postData['_multilang|'.$langId.'|'.$key] ?? $this->postData['multilang|'.$langId.'|'.$key] ?? null;
                                }

                                if (!empty($postValue)) {
                                    // TODO - support array $postValue

                                    if ($value === $postValue) {
                                        continue;
                                    }
                                }

                                if (!empty($sizeArr)) {
                                    $srcDest = $dest.'/'.$langRow['isoCode'].'/src/'.$value;
                                    $srcType = $this->helper->Nette()->Image()->detectTypeFromFile($srcDest);

                                    $this->helper->Nette()->FileSystem()->delete($srcDest);

                                    foreach ($sizeArr as $sizeKey => $sizeVal) {
                                        $destTypeArr = !empty($sizeVal['type']) ? (array) $sizeVal['type'] : [null];

                                        foreach ($destTypeArr as $destType) {
                                            $fileDest = $dest.'/'.$langRow['isoCode'].'/'.$sizeKey.'/'.$value;

                                            if (!empty($destType)) {
                                                if ($srcType !== $destType) {
                                                    $parts = explode('.', (string) $value);
                                                    array_pop($parts);

                                                    $fileDest = $dest.'/'.$langRow['isoCode'].'/'.$sizeKey.'/'.implode('.', $parts).'.'.$this->helper->Nette()->Image()->typeToExtension($destType);
                                                }
                                            }

                                            $this->helper->Nette()->FileSystem()->delete($fileDest);
                                        }
                                    }
                                } else {
                                    $fileDest = $dest.'/'.$langRow['isoCode'].'/'.$value;

                                    $this->helper->Nette()->FileSystem()->delete($fileDest);
                                }
                            }
                        }
                    }
                } else {
                    if (!empty($params['subKey'])) {
                        $unlinkValue = $this->postData['unlink_'.$key][$params['subKey']] ?? null;
                    } else {
                        $unlinkValue = $this->postData['unlink_'.$key] ?? null;
                    }

                    if (!empty($unlinkValue)) {
                        if (!empty($params['subKey'])) {
                            $value = $this->{$key}[$params['subKey']] ?? null;
                        } else {
                            $value = $this->{$key} ?? null;
                        }

                        if (!empty($value)) {
                            // TODO - support array from json $value

                            if (!empty($params['subKey'])) {
                                $postValue = $this->postData['_'.$key][$params['subKey']] ?? $this->postData[$key][$params['subKey']] ?? null;
                            } else {
                                $postValue = $this->postData['_'.$key] ?? $this->postData[$key] ?? null;
                            }

                            if (!empty($postValue)) {
                                // TODO - support array $postValue

                                if ($value === $postValue) {
                                    continue;
                                }
                            }

                            if (!empty($sizeArr)) {
                                $srcDest = $dest.'/src/'.$value;
                                $srcType = $this->helper->Nette()->Image()->detectTypeFromFile($srcDest);

                                $this->helper->Nette()->FileSystem()->delete($srcDest);

                                foreach ($sizeArr as $sizeKey => $sizeVal) {
                                    $destTypeArr = !empty($sizeVal['type']) ? (array) $sizeVal['type'] : [null];

                                    foreach ($destTypeArr as $destType) {
                                        $fileDest = $dest.'/'.$sizeKey.'/'.$value;

                                        if (!empty($destType)) {
                                            if ($srcType !== $destType) {
                                                $parts = explode('.', $value);
                                                array_pop($parts);

                                                $fileDest = $dest.'/'.$sizeKey.'/'.implode('.', $parts).'.'.$this->helper->Nette()->Image()->typeToExtension($destType);
                                            }
                                        }

                                        $this->helper->Nette()->FileSystem()->delete($fileDest);
                                    }
                                }
                            } else {
                                $fileDest = $dest.'/'.$value;

                                $this->helper->Nette()->FileSystem()->delete($fileDest);
                            }
                        }
                    }
                }
            }
        }
    }

    protected function _deleteUpload(GenericEvent $event, array $params = []): void
    {
        $params = ArrayUtils::merge(
            [
                'type' => null,
                'subKey' => null,
            ],
            $params
        );

        foreach ($this->fields as $key => $val) {
            $field = $val[static::$env];

            $type = $params['type'] ?? null;

            if (empty($type)) {
                if (isset($field['attr']['type']) && 'file' === $field['attr']['type']) {
                    if (\array_key_exists('data-type', $field['attr'])) {
                        $type = $field['attr']['data-type'];
                    }
                }
            }

            if (!empty($type)) {
                $sizeArr = $this->config['mod.'.$this->modName.'.'.$type.'.'.$key.'.sizeArr'] ?? $this->config['mod.'.$this->modName.'.'.$type.'.sizeArr'] ?? $this->config['media.'.$type.'.sizeArr'] ?? null;

                $dest = $params['dest'] ?? _PUBLIC.'/media/'.$type.'/'.$this->modName;

                if (!empty($val['multilang'])) {
                    foreach ($this->lang->arr as $langId => $langRow) {
                        if (!empty($params['subKey'])) {
                            $value = $this->multilang[$langId][$key][$params['subKey']] ?? null;
                        } else {
                            $value = $this->multilang[$langId][$key] ?? null;
                        }

                        if (!empty($value)) {
                            // TODO - support array from json $value

                            if (!empty($sizeArr)) {
                                $srcDest = $dest.'/'.$langRow['isoCode'].'/src/'.$value;
                                $srcType = $this->helper->Nette()->Image()->detectTypeFromFile($srcDest);

                                $this->helper->Nette()->FileSystem()->delete($srcDest);

                                foreach ($sizeArr as $sizeKey => $sizeVal) {
                                    $destTypeArr = !empty($sizeVal['type']) ? (array) $sizeVal['type'] : [null];

                                    foreach ($destTypeArr as $destType) {
                                        $fileDest = $dest.'/'.$langRow['isoCode'].'/'.$sizeKey.'/'.$value;

                                        if (!empty($destType)) {
                                            if ($srcType !== $destType) {
                                                $parts = explode('.', (string) $value);
                                                array_pop($parts);

                                                $fileDest = $dest.'/'.$langRow['isoCode'].'/'.$sizeKey.'/'.implode('.', $parts).'.'.$this->helper->Nette()->Image()->typeToExtension($destType);
                                            }
                                        }

                                        $this->helper->Nette()->FileSystem()->delete($fileDest);
                                    }
                                }
                            } else {
                                $fileDest = $dest.'/'.$langRow['isoCode'].'/'.$value;

                                $this->helper->Nette()->FileSystem()->delete($fileDest);
                            }
                        }
                    }
                } else {
                    if (!empty($params['subKey'])) {
                        $value = $this->{$key}[$params['subKey']] ?? null;
                    } else {
                        $value = $this->{$key} ?? null;
                    }

                    if (!empty($value)) {
                        // TODO - support array from json $value

                        if (!empty($sizeArr)) {
                            $srcDest = $dest.'/src/'.$value;
                            $srcType = $this->helper->Nette()->Image()->detectTypeFromFile($srcDest);

                            $this->helper->Nette()->FileSystem()->delete($srcDest);

                            foreach ($sizeArr as $sizeKey => $sizeVal) {
                                $destTypeArr = !empty($sizeVal['type']) ? (array) $sizeVal['type'] : [null];

                                foreach ($destTypeArr as $destType) {
                                    $fileDest = $dest.'/'.$sizeKey.'/'.$value;

                                    if (!empty($destType)) {
                                        if ($srcType !== $destType) {
                                            $parts = explode('.', $value);
                                            array_pop($parts);

                                            $fileDest = $dest.'/'.$sizeKey.'/'.implode('.', $parts).'.'.$this->helper->Nette()->Image()->typeToExtension($destType);
                                        }
                                    }

                                    $this->helper->Nette()->FileSystem()->delete($fileDest);
                                }
                            }
                        } else {
                            $fileDest = $dest.'/'.$value;

                            $this->helper->Nette()->FileSystem()->delete($fileDest);
                        }
                    }
                }
            }
        }
    }

    protected function _disableOthers(GenericEvent $event, array $params = []): void
    {
        $params = ArrayUtils::merge(
            [
                'id' => null,
                'keys' => [],
            ],
            $params
        );

        if (!empty($params['keys'])) {
            foreach ($params['keys'] as $key) {
                if (empty($this->{$key})) {
                    if (!empty($this->postData[$key])
                        // && (empty($this->postData['_skip']) || !\in_array($key, $this->postData['_skip'], true))
                    ) {
                        $this->db->exec(
                            'UPDATE '.$this->config['db.1.prefix'].$this->modName.' SET '.$key.' = :'.$key.' WHERE id != :id',
                            [
                                $key => 0,
                                'id' => $params['id'],
                            ]
                        );
                    }
                }
            }
        }
    }

    protected function _eventFilter(GenericEvent $event): void
    {
        if ($this->auth->hasIdentity()) {
            if (($filterData = $this->session->get(static::$env.'.'.$this->auth->getIdentity()['id'].'.filterData')) !== null) {
                if (!empty($filterData[$this->controller][$this->action])) {
                    foreach ($filterData[$this->controller][$this->action] as $key => $val) {
                        $val = ArrayUtils::merge(
                            [
                                'key' => $key,
                                'value' => null,
                                'mode' => null,
                                'alias' => (\array_key_exists($key, $this->fieldsMultilang) ? 'b' : 'a').'.',
                            ],
                            $val
                        );

                        if (!isBlank($val['value'])) {
                            switch (true) {
                                case 'NULL' === $val['value']:
                                    $this->dbData['sql'] .= ' AND '.$val['alias'].$val['key'].' IS NULL';

                                    break;

                                case 'EMPTY' === $val['value']:
                                    // FIXED - MySql double field with value === 0
                                    // https://stackoverflow.com/a/12939764/3929620
                                    // $this->dbData['sql'] .= ' AND '.$val['alias'] . $val['key'] . ' = ""';
                                    $this->dbData['sql'] .= ' AND COALESCE('.$val['alias'].$val['key'].', "") = ""';

                                    break;

                                default:
                                    switch (true) {
                                        case 'STRICT' === $val['mode']:
                                            $this->dbData['sql'] .= ' AND '.$val['alias'].$val['key'].' = :filter_'.$val['key'];

                                            $this->dbData['args']['filter_'.$val['key']] = $val['value'];

                                            break;

                                        case 'TIME' === $val['mode']:
                                            // https://stackoverflow.com/a/18276813
                                            $this->dbData['sql'] .= ' AND CONVERT_TZ(FROM_UNIXTIME('.$val['alias'].$val['key'].'), :filter_'.$val['key'].'_from_timezone, :filter_'.$val['key'].'_to_timezone) LIKE :filter_'.$val['key'];

                                            $this->dbData['args']['filter_'.$val['key'].'_from_timezone'] = $this->config['db.1.timeZone'];
                                            $this->dbData['args']['filter_'.$val['key'].'_to_timezone'] = date_default_timezone_get();
                                            $this->dbData['args']['filter_'.$val['key']] = '%'.$val['value'].'%';

                                            break;

                                        case 'DATE' === $val['mode']:
                                            // https://stackoverflow.com/a/18276813
                                            $this->dbData['sql'] .= ' AND CONVERT_TZ('.$val['alias'].$val['key'].', :filter_'.$val['key'].'_from_timezone, :filter_'.$val['key'].'_to_timezone) LIKE :filter_'.$val['key'];

                                            $this->dbData['args']['filter_'.$val['key'].'_from_timezone'] = $this->config['db.1.timeZone'];
                                            $this->dbData['args']['filter_'.$val['key'].'_to_timezone'] = date_default_timezone_get();
                                            $this->dbData['args']['filter_'.$val['key']] = '%'.$val['value'].'%';

                                            break;

                                        case 'LEFT_LIKE' === $val['mode']:
                                            $this->dbData['sql'] .= ' AND '.$val['alias'].$val['key'].' LIKE :filter_'.$val['key'];

                                            $this->dbData['args']['filter_'.$val['key']] = '%'.$val['value'];

                                            break;

                                        case 'RIGHT_LIKE' === $val['mode']:
                                            $this->dbData['sql'] .= ' AND '.$val['alias'].$val['key'].' LIKE :filter_'.$val['key'];

                                            $this->dbData['args']['filter_'.$val['key']] = $val['value'].'%';

                                            break;

                                        default:
                                            $this->dbData['sql'] .= ' AND '.$val['alias'].$val['key'].' LIKE :filter_'.$val['key'];

                                            $this->dbData['args']['filter_'.$val['key']] = '%'.$val['value'].'%';

                                            break;
                                    }

                                    break;
                            }
                        }
                    }
                }
            }
        }
    }

    protected function _eventSearch(GenericEvent $event): void
    {
        if ($this->auth->hasIdentity()) {
            if (($searchData = $this->session->get(static::$env.'.'.$this->auth->getIdentity()['id'].'.searchData')) !== null) {
                if (!empty($searchData[$this->controller][$this->action])) {
                    $this->dbData['sql'] .= ' AND (a.id IS NULL';

                    foreach ($searchData[$this->controller][$this->action] as $key => $val) {
                        $val = ArrayUtils::merge(
                            [
                                'key' => $key,
                                'value' => null,
                                'mode' => null,
                                'alias' => (\array_key_exists($key, $this->fieldsMultilang) ? 'b' : 'a').'.',
                            ],
                            $val
                        );

                        if (!empty($val['value'])) {
                            switch (true) {
                                case 'STRICT' === $val['mode']:
                                    $this->dbData['sql'] .= ' OR '.$val['alias'].$val['key'].' = :search_'.$val['key'];

                                    $this->dbData['args']['search_'.$val['key']] = $val['value'];

                                    break;

                                case 'TIME' === $val['mode']:
                                    // https://stackoverflow.com/a/18276813
                                    $this->dbData['sql'] .= ' OR CONVERT_TZ(FROM_UNIXTIME('.$val['alias'].$val['key'].'), :search_'.$val['key'].'_from_timezone, :search_'.$val['key'].'_to_timezone) LIKE :search_'.$val['key'];

                                    $this->dbData['args']['search_'.$val['key'].'_from_timezone'] = $this->config['db.1.timeZone'];
                                    $this->dbData['args']['search_'.$val['key'].'_to_timezone'] = date_default_timezone_get();
                                    $this->dbData['args']['search_'.$val['key']] = '%'.$val['value'].'%';

                                    break;

                                case 'DATE' === $val['mode']:
                                    // https://stackoverflow.com/a/18276813
                                    $this->dbData['sql'] .= ' OR CONVERT_TZ('.$val['alias'].$val['key'].', :search_'.$val['key'].'_from_timezone,   :search_'.$val['key'].'_to_timezone) LIKE :search_'.$val['key'];

                                    $this->dbData['args']['search_'.$val['key'].'_from_timezone'] = $this->config['db.1.timeZone'];
                                    $this->dbData['args']['search_'.$val['key'].'_to_timezone'] = date_default_timezone_get();
                                    $this->dbData['args']['search_'.$val['key']] = '%'.$val['value'].'%';

                                    break;

                                case 'LEFT_LIKE' === $val['mode']:
                                    $this->dbData['sql'] .= ' OR '.$val['alias'].$val['key'].' LIKE :search_'.$val['key'];

                                    $this->dbData['args']['search_'.$val['key']] = '%'.$val['value'];

                                    break;

                                case 'RIGHT_LIKE' === $val['mode']:
                                    $this->dbData['sql'] .= ' OR '.$val['alias'].$val['key'].' LIKE :search_'.$val['key'];

                                    $this->dbData['args']['search_'.$val['key']] = $val['value'].'%';

                                    break;

                                default:
                                    $this->dbData['sql'] .= ' OR '.$val['alias'].$val['key'].' LIKE :search_'.$val['key'];

                                    $this->dbData['args']['search_'.$val['key']] = '%'.$val['value'].'%';

                                    break;
                            }
                        }
                    }

                    $this->dbData['sql'] .= ')';
                }
            }
        }
    }
}
