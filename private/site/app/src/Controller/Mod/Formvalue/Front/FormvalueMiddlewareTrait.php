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

namespace App\Controller\Mod\Formvalue\Front;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

trait FormvalueMiddlewareTrait
{
    public static $loaded;

    public function _processGlobal(ServerRequestInterface $request, RequestHandlerInterface $handler): ServerRequestInterface
    {
        if (!empty(static::$loaded)) {
            return $request;
        }

        static::$loaded = true;

        if (!empty($request->getAttribute('hasIdentity'))) {
            if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                if ($this->rbac->isGranted('form.'.static::$env.'.fill')) {
                    if (!empty($this->view->getData()->catformRow)) { // <--
                        $eventName = 'event.'.static::$env.'.'.$this->modName.'.getAll.where';
                        $callback = function (GenericEvent $event): void {
                            $this->dbData['sql'] .= ' AND a.member_id = :member_id';
                            $this->dbData['args']['member_id'] = $this->auth->getIdentity()['id'];
                        };

                        $eventName2 = 'event.'.static::$env.'.'.$this->modName.'.getAll.group';
                        $callback2 = function (GenericEvent $event): void {
                            $this->dbData['sql'] .= ' GROUP BY a.catform_id, a.form_id';
                        };

                        $this->dispatcher->addListener($eventName, $callback);
                        $this->dispatcher->addListener($eventName2, $callback2);

                        ${$this->modName.'Result'} = $this->getAll(
                            [
                                'order' => 'a.form_id ASC',
                                'active' => true,
                            ]
                        );

                        $this->dispatcher->removeListener($eventName, $callback);
                        $this->dispatcher->removeListener($eventName2, $callback2);

                        if (\count(${$this->modName.'Result'}) > 0) {
                            $this->view->addData(
                                [ // <--
                                    ...compact( // https://stackoverflow.com/a/30266377/3929620
                                        $this->modName.'Result'
                                    )]
                            );

                            // https://stackoverflow.com/a/49645329/3929620
                            $foundIds = array_column(${$this->modName.'Result'}, 'form_id');
                            $catIds = array_column(${$this->modName.'Result'}, 'catform_id');

                            // https://stackoverflow.com/a/39732861/3929620
                            // https://stackoverflow.com/a/52096994/3929620
                            // https://stackoverflow.com/a/67687726/3929620
                            // https://stackoverflow.com/a/63936672/3929620
                            // https://stackoverflow.com/a/14614618/3929620
                            $this->dbData['sql'] = 'SELECT a.form_id FROM '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).'formfield AS a
                            WHERE 1
                            AND a.catform_id = :catform_id
                            AND a.required = 1
                            AND a.active = 1
                            AND a.id NOT IN (
                                SELECT b.formfield_id FROM '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.' AS b
                                RIGHT JOIN '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).'formfield AS c
                                ON b.formfield_id = c.id
                                WHERE 1
                                AND b.member_id = :member_id_1
                                AND b.data IS NOT NULL
                                AND b.active = 1
                                AND c.type != :type_1
                                AND c.type != :type_2
                                AND c.required = 1
                                AND c.active = 1
                            UNION
	                            SELECT d.formfield_id FROM '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.' AS d
                                RIGHT JOIN '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).'formfield AS e
                                ON d.formfield_id = e.id
                                WHERE 1
                                AND d.member_id = :member_id_2
                                AND d.data != :data_1
                                AND d.data NOT LIKE :data_2
                                AND d.data IS NOT NULL
                                AND d.active = 1
                                AND e.type = :type_3
                                AND e.required = 1
                                AND e.active = 1
                            UNION
	                            SELECT f.formfield_id FROM '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$this->modName.' AS f
                                RIGHT JOIN '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).'formfield AS g
                                ON f.formfield_id = g.id
                                WHERE 1
                                AND f.member_id = :member_id_3
                                AND (
                                    f.data = :data_3
                                    XOR (
                                        CASE WHEN JSON_VALID(f.data)
	                                        THEN JSON_EXTRACT(f.data, "$.0") = 1
	                                        ELSE 0
	                                    END
                                        AND CASE WHEN JSON_VALID(f.data)
                                            THEN JSON_CONTAINS_PATH(f.data, "one", "$.teachers.*.status")
                                            ELSE 0
                                        END
                                        AND CASE WHEN JSON_VALID(f.data)
                                            THEN JSON_LENGTH(JSON_EXTRACT(f.data, "$.teachers.*.status")) = JSON_LENGTH(JSON_EXTRACT(f.data, "$.teachers"))
                                            ELSE 0
                                        END
                                    )
                                )
                                AND f.active = 1
                                AND g.type = :type_4
                                AND g.required = 1
                                AND g.active = 1
                            )
                            AND a.form_id IN ('.implode(',', $foundIds).')
                            GROUP BY a.form_id';

                            $this->dbData['args'] = [
                                'catform_id' => current($catIds),
                                'member_id_1' => $this->auth->getIdentity()['id'],
                                'member_id_2' => $this->auth->getIdentity()['id'],
                                'member_id_3' => $this->auth->getIdentity()['id'],
                                'type_1' => 'input_file_multiple',
                                'type_2' => 'recommendation',
                                'type_3' => 'input_file_multiple',
                                'type_4' => 'recommendation',
                                'data_1' => '[]',
                                'data_2' => '%"'.sys_get_temp_dir().'%',
                                'data_3' => '0',
                            ];

                            ${$this->modName.'PartialResult'} = $this->db->getAll($this->dbData['sql'], $this->dbData['args']);

                            if (\count(${$this->modName.'PartialResult'}) > 0) {
                                $this->view->addData(
                                    [ // <--
                                        ...compact( // https://stackoverflow.com/a/30266377/3929620
                                            $this->modName.'PartialResult'
                                        )]
                                );
                            }
                        }
                    }
                }
            }
        }

        return $request;
    }
}
