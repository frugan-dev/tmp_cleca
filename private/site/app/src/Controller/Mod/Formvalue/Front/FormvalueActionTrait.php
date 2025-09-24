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

use App\Factory\Html\ViewHelperInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Symfony\Component\EventDispatcher\GenericEvent;

trait FormvalueActionTrait
{
    public function actionIndex(RequestInterface $request, ResponseInterface $response, $args)
    {
        $ModMember = $this->container->get('Mod\Member\\'.ucfirst(static::$env));

        // FIXME - circular dependencies
        $this->viewHelper = $this->container->get(ViewHelperInterface::class);

        if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
            if (empty($this->auth->getIdentity()['confirmed'])) {
                // may have been confirmed with another browser..
                $row = $ModMember->getOne([
                    'id' => $this->auth->getIdentity()['id'],
                    'active' => true,
                ]);
                if (empty($row['confirmed'])) {
                    $this->errors[] = __('We sent you a confirmation email, didn\'t you get it?')
                        .'<br>'.\sprintf(__('%1$s to send it again.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                            'href' => $this->helper->Url()->urlFor([
                                'routeName' => static::$env.'.member.params',
                                'data' => [
                                    'action' => 'setting',
                                    'params' => $this->auth->getIdentity()['id'],
                                ],
                            ]),
                        ]).'>'.$this->helper->Nette()->Strings()->firstUpper(__('click here')).'</a>');
                } else {
                    $this->auth->forceAuthenticate($row[$ModMember->authUsernameField]);
                }
            }
        }

        if (0 === \count($this->errors)) {
            if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                if (empty($this->auth->getIdentity()['country_id'])) {
                    $this->errors[] = __('Some personal data are missing.')
                        .'<br>'.\sprintf(__('%1$s to complete them.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                            'href' => $this->helper->Url()->urlFor([
                                'routeName' => static::$env.'.member.params',
                                'data' => [
                                    'action' => 'edit',
                                    'params' => $this->auth->getIdentity()['id'],
                                ],
                            ]),
                        ]).'>'.$this->helper->Nette()->Strings()->firstUpper(__('click here')).'</a>');
                }
            }
        }

        if (0 === \count($this->errors)) {
            // https://stackoverflow.com/a/59460379/3929620
            // https://stackoverflow.com/a/70088964/3929620
            // https://stackoverflow.com/a/62797108/3929620
            // https://stackoverflow.com/a/65172358/3929620
            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getCount.where';
            $callback = function (GenericEvent $event): void {
                if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                    $this->dbData['sql'] .= ' AND CASE WHEN JSON_VALID(a.data)
            THEN JSON_CONTAINS(JSON_EXTRACT(a.data, "$.teachers.*.id"), :teacher_id, "$")
            ELSE 0
        END';
                    $this->dbData['args']['teacher_id'] = $this->auth->getIdentity()['id'];
                } else {
                    $this->dbData['sql'] .= ' AND CASE WHEN JSON_VALID(a.data)
            THEN JSON_CONTAINS_PATH(a.data, "one", "$.teachers.*.id")
            ELSE 0
        END';
                }
            };

            $eventName2 = 'event.'.static::$env.'.'.$this->modName.'.getAll.select';
            $callback2 = function (GenericEvent $event): void {
                $this->dbData['sql'] .= ', CONCAT(e.lastname, " ", e.firstname) AS member_lastname_firstname';
                $this->dbData['sql'] .= ', g.code AS catform_code';
                $this->dbData['sql'] .= ', g.sdate'; // <--
                $this->dbData['sql'] .= ', g.edate'; // <--
                $this->dbData['sql'] .= ', g.maintenance'; // <--

                if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                    // https://stackoverflow.com/a/52175323/3929620
                    $this->dbData['sql'] .= ', CASE WHEN JSON_VALID(a.data)
            THEN JSON_CONTAINS_PATH(a.data, "one", CONCAT(SUBSTRING_INDEX(JSON_UNQUOTE(JSON_SEARCH(a.data, "one", :email)), ".", 3), ".status"))
            ELSE 0
        END AS active';
                    $this->dbData['args']['email'] = $this->auth->getIdentity()['email'];
                } else {
                    $this->dbData['sql'] .= ', CASE WHEN JSON_VALID(a.data)
            THEN JSON_LENGTH(JSON_EXTRACT(a.data, "$.teachers.*.status")) = JSON_LENGTH(JSON_EXTRACT(a.data, "$.teachers"))
            ELSE 0
        END AS active';
                }
            };

            $eventName3 = 'event.'.static::$env.'.'.$this->modName.'.getAll.join';
            $callback3 = function (GenericEvent $event): void {
                $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'member AS e
                ON a.member_id = e.id';
                $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'catform AS g
        ON a.catform_id = g.id';
            };

            $eventName4 = 'event.'.static::$env.'.'.$this->modName.'.getAll.where';
            $callback4 = function (GenericEvent $event): void {
                if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                    $this->dbData['sql'] .= ' AND CASE WHEN JSON_VALID(a.data)
            THEN JSON_CONTAINS(JSON_EXTRACT(a.data, "$.teachers.*.id"), :teacher_id, "$")
            ELSE 0
        END';
                    $this->dbData['args']['teacher_id'] = $this->auth->getIdentity()['id'];
                } else {
                    $this->dbData['sql'] .= ' AND CASE WHEN JSON_VALID(a.data)
            THEN JSON_CONTAINS_PATH(a.data, "one", "$.teachers.*.id")
            ELSE 0
        END';
                }
            };

            $this->dispatcher->addListener($eventName, $callback);
            $this->dispatcher->addListener($eventName2, $callback2);
            $this->dispatcher->addListener($eventName3, $callback3);
            $this->dispatcher->addListener($eventName4, $callback4);

            $return = parent::actionIndex($request, $response, $args);

            $this->dispatcher->removeListener($eventName, $callback);
            $this->dispatcher->removeListener($eventName2, $callback2);
            $this->dispatcher->removeListener($eventName3, $callback3);
            $this->dispatcher->removeListener($eventName4, $callback4);

            if (null !== $return) {
                return $return;
            }

            if (!empty($this->viewData['result'])) {
                $ModCatform = $this->container->get('Mod\Catform\\'.ucfirst(static::$env));

                array_walk($this->viewData['result'], function (&$row) use ($ModCatform): void {
                    $row['catform_status'] = $ModCatform->getStatusValue($row);
                });
            }
        } else {
            $this->session->addFlash([
                'type' => 'alert',
                'options' => [
                    'type' => 'warning',
                    'message' => current($this->errors),
                    'dismissible' => false,
                ],
            ]);
        }
    }

    public function actionView(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        // https://stackoverflow.com/a/70088964/3929620
        $eventName = 'event.'.static::$env.'.'.$this->modName.'.existStrict.where';
        $callback = function (GenericEvent $event): void {
            if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                $this->dbData['sql'] .= ' AND CASE WHEN JSON_VALID(a.data)
        THEN JSON_CONTAINS(JSON_EXTRACT(a.data, "$.teachers.*.id"), :teacher_id, "$")
        ELSE 0
    END';
                $this->dbData['args']['teacher_id'] = $this->auth->getIdentity()['id'];
            } else {
                $this->dbData['sql'] .= ' AND CASE WHEN JSON_VALID(a.data)
        THEN JSON_CONTAINS_PATH(a.data, "one", "$.teachers.*.id")
        ELSE 0
    END';
            }
        };

        $this->dispatcher->addListener($eventName, $callback);

        $row = $this->existStrict([
            'active' => true,
        ]);

        $this->dispatcher->removeListener($eventName, $callback);

        if ($row) {
            if (null !== ($return = parent::actionView($request, $response, $args))) {
                return $return;
            }

            $this->data = $this->helper->Nette()->Json()->decode((string) $this->data, forceArrays: true);

            $teacherKey = null;
            if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                if (($teacherKey = $this->helper->Arrays()->recursiveArraySearch('id', $this->auth->getIdentity()['id'], $this->data, true)) === false) {
                    throw new HttpNotFoundException($request);
                }
            }

            $this->viewData = array_merge(
                $this->viewData,
                [...compact( // https://stackoverflow.com/a/30266377/3929620
                    'teacherKey'
                )]
            );
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    public function actionEdit(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        // https://stackoverflow.com/a/70088964/3929620
        $eventName = 'event.'.static::$env.'.'.$this->modName.'.existStrict.where';
        $callback = function (GenericEvent $event): void {
            if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                $this->dbData['sql'] .= ' AND CASE WHEN JSON_VALID(a.data)
        THEN JSON_CONTAINS(JSON_EXTRACT(a.data, "$.teachers.*.id"), :teacher_id, "$")
        ELSE 0
    END';
                $this->dbData['args']['teacher_id'] = $this->auth->getIdentity()['id'];
            } else {
                $this->dbData['sql'] .= ' AND CASE WHEN JSON_VALID(a.data)
        THEN JSON_CONTAINS_PATH(a.data, "one", "$.teachers.*.id")
        ELSE 0
    END';
            }
        };

        $this->dispatcher->addListener($eventName, $callback);

        $row = $this->existStrict([
            'active' => true,
        ]);

        $this->dispatcher->removeListener($eventName, $callback);

        if ($row) {
            $ModMember = $this->container->get('Mod\Member\\'.ucfirst(static::$env));

            // FIXME - circular dependencies
            $this->viewHelper = $this->container->get(ViewHelperInterface::class);

            if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                if (empty($this->auth->getIdentity()['confirmed'])) {
                    // may have been confirmed with another browser..
                    $row = $ModMember->getOne([
                        'id' => $this->auth->getIdentity()['id'],
                        'active' => true,
                    ]);
                    if (empty($row['confirmed'])) {
                        $this->errors[] = __('We sent you a confirmation email, didn\'t you get it?')
                            .'<br>'.\sprintf(__('%1$s to send it again.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                                'href' => $this->helper->Url()->urlFor([
                                    'routeName' => static::$env.'.member.params',
                                    'data' => [
                                        'action' => 'setting',
                                        'params' => $this->auth->getIdentity()['id'],
                                    ],
                                ]),
                            ]).'>'.$this->helper->Nette()->Strings()->firstUpper(__('click here')).'</a>');
                    } else {
                        $this->auth->forceAuthenticate($row[$ModMember->authUsernameField]);
                    }
                }
            }

            if (0 === \count($this->errors)) {
                if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                    if (empty($this->auth->getIdentity()['country_id'])) {
                        $this->errors[] = __('Some personal data are missing.')
                            .'<br>'.\sprintf(__('%1$s to complete them.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                                'href' => $this->helper->Url()->urlFor([
                                    'routeName' => static::$env.'.member.params',
                                    'data' => [
                                        'action' => 'edit',
                                        'params' => $this->auth->getIdentity()['id'],
                                    ],
                                ]),
                            ]).'>'.$this->helper->Nette()->Strings()->firstUpper(__('click here')).'</a>');
                    }
                }
            }

            if ('POST' === $request->getMethod()) {
                if (\count($this->errors) > 0 || !\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                    throw new HttpUnauthorizedException($request);
                }
            }

            if (null !== ($return = parent::actionEdit($request, $response, $args))) {
                return $return;
            }

            $this->data = $this->helper->Nette()->Json()->decode((string) $this->data, forceArrays: true);

            $teacherKey = null;
            if (\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                if (($teacherKey = $this->helper->Arrays()->recursiveArraySearch('id', $this->auth->getIdentity()['id'], $this->data, true)) === false) {
                    throw new HttpNotFoundException($request);
                }
            }

            $this->viewData = array_merge(
                $this->viewData,
                [...compact( // https://stackoverflow.com/a/30266377/3929620
                    'teacherKey'
                )]
            );

            if ('POST' !== $request->getMethod() && \count($this->errors) > 0) {
                $this->session->addFlash([
                    'type' => 'alert',
                    'options' => [
                        'type' => 'warning',
                        'message' => current($this->errors),
                        'dismissible' => false,
                    ],
                ]);
            }
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    public function _actionIndex(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->title = $this->metaTitle = __('Recommendation letters');
    }

    public function _actionView(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $breadcrumbs = $this->breadcrumb->getBreadcrumbs();

        // FIXED - Breadcrumbs::setBreadcrumbs() only accepts correctly formatted arrays
        if (empty($breadcrumbs[0]['href'])) {
            $breadcrumbs[0]['href'] = '/';
        }

        $breadcrumbs[1]['name'] = __('Recommendation letters');

        $this->breadcrumb->setBreadcrumbs($breadcrumbs);

        $this->title = $this->metaTitle = \sprintf(__('Detail %1$s'), $this->helper->Nette()->Strings()->lower(__('Recommendation letters')));
    }

    public function _actionEdit(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $ModCatform = $this->container->get('Mod\Catform\\'.ucfirst(static::$env));
        $status = $ModCatform->getStatusValue([
            'sdate' => $this->catform_sdate,
            'edate' => $this->catform_edate,
            'maintenance' => $this->catform_maintenance,
        ]);

        if (!empty($this->active) || !\in_array($status, [$ModCatform::MAINTENANCE, $ModCatform::OPEN, $ModCatform::CLOSING], true)) {
            throw new HttpNotFoundException($request);
        }

        $breadcrumbs = $this->breadcrumb->getBreadcrumbs();

        // FIXED - Breadcrumbs::setBreadcrumbs() only accepts correctly formatted arrays
        if (empty($breadcrumbs[0]['href'])) {
            $breadcrumbs[0]['href'] = '/';
        }

        $breadcrumbs[1]['name'] = __('Recommendation letters');

        $this->breadcrumb->setBreadcrumbs($breadcrumbs);

        $this->title = $this->metaTitle = \sprintf(__('Edit %1$s'), $this->helper->Nette()->Strings()->lower(__('Recommendation letters')));
    }
}
