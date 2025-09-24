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

namespace App\Controller\Mod\Member\Front;

use Slim\Psr7\Response;
use Symfony\Component\EventDispatcher\GenericEvent;

trait MemberEventTrait
{
    public function _eventActionEditAfter(GenericEvent $event): void
    {
        if ($this->auth->getIdentity()['id'] === $this->id) {
            if ($this->email !== $this->postData['email']) {
                $this->db->exec('UPDATE '.$this->config['db.1.prefix'].$this->modName.' SET confirmed = :confirmed WHERE id = :id', [
                    'confirmed' => 0,
                    'id' => $this->id,
                ]);

                if (!empty($this->auth->getIdentity()['cat'.$this->modName.'_main'])) {
                    $ModFormvalue = $this->container->get('Mod\Formvalue\\'.ucfirst(static::$env));

                    $eventName = 'event.'.static::$env.'.'.$ModFormvalue->modName.'.getAll.select';
                    $callback = function (GenericEvent $event) use ($ModFormvalue): void {
                        $ModFormvalue->dbData['sql'] .= ', a.data';
                    };

                    // https://stackoverflow.com/a/70088964/3929620
                    $eventName2 = 'event.'.static::$env.'.'.$ModFormvalue->modName.'.getAll.where';
                    $callback2 = function (GenericEvent $event) use ($ModFormvalue): void {
                        $ModFormvalue->dbData['sql'] .= ' AND CASE WHEN JSON_VALID(a.data)
                    THEN JSON_CONTAINS(JSON_EXTRACT(a.data, "$.teachers.*.id"), :teacher_id, "$")
                    ELSE 0
                END';
                        $ModFormvalue->dbData['args']['teacher_id'] = $this->id;
                    };

                    $this->dispatcher->addListener($eventName, $callback);
                    $this->dispatcher->addListener($eventName2, $callback2);

                    $result = $ModFormvalue->getAll();

                    $this->dispatcher->removeListener($eventName, $callback);
                    $this->dispatcher->removeListener($eventName2, $callback2);

                    if (\count($result) > 0) {
                        foreach ($result as $row) {
                            if (!empty($data = $this->helper->Nette()->Json()->decode((string) $row['data'], forceArrays: true))) {
                                if (($key = $this->helper->Arrays()->recursiveArraySearch('id', $this->id, $data['teachers'], true)) !== false) {
                                    $data['teachers'][$key]['email'] = $this->postData['email'];

                                    $this->db->exec('UPDATE '.$this->config['db.1.prefix'].$ModFormvalue->modName.' SET data = :data WHERE id = :id', [
                                        'data' => $this->helper->Nette()->Json()->encode($data),
                                        'id' => $row['id'],
                                    ]);
                                }
                            }
                        }
                    }
                }

                if ($this->container->has('request')) {
                    $this->postData['action'] = 'sendkey';
                    $this->email = $this->postData['email'];
                    $this->confirmed = 0;
                    if (method_exists($this, '_actionPost'.ucfirst((string) $this->postData['action'])) && \is_callable([$this, '_actionPost'.ucfirst((string) $this->postData['action'])])) {
                        \call_user_func_array([$this, '_actionPost'.ucfirst((string) $this->postData['action'])], [$this->container->get('request'), new Response(), []]);
                    }
                    unset($this->postData['action']);
                }
            }

            $this->auth->forceAuthenticate($this->postData[$this->authUsernameField]);
        }
    }

    public function eventGetAllJoin(GenericEvent $event): void
    {
        parent::eventGetAllJoin($event);

        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'cat'.$this->modName.' AS c
        ON a.cat'.$this->modName.'_id = c.id';
    }
}
