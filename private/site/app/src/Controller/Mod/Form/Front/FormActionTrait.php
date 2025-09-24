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

namespace App\Controller\Mod\Form\Front;

use App\Factory\ArraySiblings\ArraySiblingsInterface;
use App\Factory\Html\ViewHelperInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Symfony\Component\EventDispatcher\GenericEvent;

trait FormActionTrait
{
    public function actionFill(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->existStrict([
            'active' => true,
        ])) {
            $this->setFields();

            $prefix = '';
            if (!empty($this->view->getData()->{$this->modName.'Result'})) { // <--
                if (($n = $this->helper->Arrays()->recursiveArraySearch('id', $this->id, $this->view->getData()->{$this->modName.'Result'}, true)) !== false) {
                    $prefix = ++$n.' - ';
                }
            }

            $this->title = $this->metaTitle = $prefix.$this->name;
            $this->subTitle = $this->subname;

            foreach ($this->lang->codeArr as $langId => $langCode) {
                $this->routeArgsArr[$langId] = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $langCode,
                        'action' => $this->action,
                        'params' => $this->id,
                    ],
                ];
            }

            $this->routeArgs = $this->routeArgsArr[$this->lang->id];

            if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
                $this->acceptRouteArgs = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $this->lang->acceptCode,
                        'action' => $this->action,
                        'params' => $this->id,
                    ],
                    'full' => true,
                ];
            }

            if (!empty($this->view->getData()->{'cat'.$this->modName.'Row'})) { // <--
                $this->breadcrumb->add(
                    $this->helper->Nette()->Strings()->truncate(
                        $this->view->getData()->{'cat'.$this->modName.'Row'}['name'],
                        $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                    ),
                    $this->helper->Url()->urlFor([
                        'routeName' => static::$env.'.cat'.$this->modName.'.params',
                        'data' => [
                            'action' => 'view', // <--
                            'params' => $args['cat'.$this->modName.'_id'],
                        ],
                    ])
                );
            }

            $this->breadcrumb->add(
                $this->helper->Nette()->Strings()->truncate(
                    $this->title,
                    $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                ),
                $this->helper->Url()->urlFor($this->routeArgs)
            );

            if (!empty($this->view->getData()->{$this->modName.'Result'})) { // <--
                $ArraySiblings = $this->container->get(ArraySiblingsInterface::class);

                $siblings = [];

                // https://stackoverflow.com/a/49645329/3929620
                $ids = array_column($this->view->getData()->{$this->modName.'Result'}, 'id');
                $rows = array_combine($ids, $this->view->getData()->{$this->modName.'Result'});

                if (($previous = $ArraySiblings->previous($this->id, $rows, false)) !== false) {
                    $previous['_routeArgs'] = [
                        'routeName' => static::$env.'.'.$this->modName.'.params',
                        'data' => [
                            'action' => $this->action,
                            'params' => $previous['id'],
                        ],
                    ];

                    $siblings['previous'] = $previous;
                }

                if (($next = $ArraySiblings->next($this->id, $rows, false)) !== false) {
                    $next['_routeArgs'] = [
                        'routeName' => static::$env.'.'.$this->modName.'.params',
                        'data' => [
                            'action' => $this->action,
                            'params' => $next['id'],
                        ],
                    ];

                    $siblings['next'] = $next;
                }

                $this->viewData = array_merge(
                    $this->viewData,
                    [...compact( // https://stackoverflow.com/a/30266377/3929620
                        'siblings'
                    )]
                );
            }

            $foundIds = $diffIds = $partialIds = [];
            $ModMember = $this->container->get('Mod\Member\\'.ucfirst(static::$env));

            // FIXME - circular dependencies
            $this->viewHelper = $this->container->get(ViewHelperInterface::class);

            if (!empty($this->view->getData()->{$this->modName.'Result'})) { // <--
                if (!empty($this->view->getData()->{$this->modName.'valueResult'})) { // <--
                    // https://stackoverflow.com/a/49645329/3929620
                    $catIds = array_column($this->view->getData()->{$this->modName.'valueResult'}, 'cat'.$this->modName.'_id');

                    if (!\in_array($this->view->getData()->{'cat'.$this->modName.'Row'}['id'], $catIds, true)) { // <--
                        $this->errors[] = \sprintf(__('You have already filled some forms in %1$s, so you can\'t proceed here.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                            'href' => $this->helper->Url()->urlFor([
                                'routeName' => static::$env.'.cat'.$this->modName.'.params',
                                'data' => [
                                    'action' => 'view',
                                    'params' => current($catIds),
                                    'cat'.$this->modName.'_id' => current($catIds),
                                ],
                            ]),
                        ]).'>'.__('another category').'</a>');
                    }
                }
            } else {
                throw new HttpNotFoundException($request);
            }

            if (0 === \count($this->errors)) {
                // https://stackoverflow.com/a/49645329/3929620
                $ids = array_column($this->view->getData()->{$this->modName.'Result'}, 'id');
                $foundIds = !empty($this->view->getData()->{$this->modName.'valueResult'}) ? array_column($this->view->getData()->{$this->modName.'valueResult'}, $this->modName.'_id') : [];
                $partialIds = !empty($this->view->getData()->{$this->modName.'valuePartialResult'}) ? array_column($this->view->getData()->{$this->modName.'valuePartialResult'}, $this->modName.'_id') : [];
                $diffIds = array_diff($ids, $foundIds);

                if (0 === \count($diffIds) && 0 === \count($partialIds)) {
                    $this->errors[] = [
                        __('You have already completed all application forms in this category.'),
                        \sprintf(__('%1$s to print them all.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                            'href' => $this->helper->Url()->urlFor([
                                'routeName' => static::$env.'.'.$this->modName,
                                'data' => [
                                    'action' => 'index',
                                ],
                            ]),
                        ]).'>'.$this->helper->Nette()->Strings()->firstUpper(__('click here')).'</a>')];
                }
            }

            if (0 === \count($this->errors)) {
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
                if ($this->container->has('Mod\\'.ucfirst($this->modName).'field\\'.ucfirst(static::$env))) {
                    $ModFormfield = $this->container->get('Mod\\'.ucfirst($this->modName).'field\\'.ucfirst(static::$env));

                    $ModFormfield->controller = $this->controller;
                    $ModFormfield->action = $this->action;

                    if (method_exists($ModFormfield, '_'.__FUNCTION__) && \is_callable([$ModFormfield, '_'.__FUNCTION__])) {
                        $return = \call_user_func_array([$ModFormfield, '_'.__FUNCTION__], [$request, $response, $args]);

                        if (null !== $return) {
                            return $return;
                        }

                        $this->viewData = array_merge(
                            $this->viewData,
                            $ModFormfield->viewData
                        );
                    } else {
                        throw new HttpNotFoundException($request);
                    }
                } else {
                    throw new HttpNotFoundException($request);
                }
            }

            if (0 === \count($this->errors)) {
                if (empty($next) && !empty($this->view->getData()->{'cat'.$this->modName.'Row'}['cdate'])) {
                    $nowObj = $this->helper->Carbon()->now();
                    $cdateObj = $this->helper->Carbon()->createFromFormat('Y-m-d H:i:s', $this->view->getData()->{'cat'.$this->modName.'Row'}['cdate'], $this->config['db.1.timeZone'])->setTimezone(date_default_timezone_get());

                    if ($cdateObj->greaterThan($nowObj)) {
                        $this->errors[] = \sprintf(__('It is possible to complete all application forms only starting from %1$s at %2$s (%3$s).'), $cdateObj->format('j F Y'), $cdateObj->format('G:i'), $cdateObj->timezone);
                    }
                }
            }

            if (0 === \count($this->errors)) {
                if (empty($next) && (\count($diffIds) > 1 || \count($partialIds) > 0)) {
                    $this->errors[] = __('It is necessary to complete all previous forms before proceeding.');
                }
            }

            if (0 === \count($this->errors)) {
                if ('POST' === $request->getMethod() && !empty($this->viewData[$this->modName.'fieldResult'])) {
                    if (!\in_array($this->auth->getIdentity()['_type'], ['member'], true)) {
                        throw new HttpUnauthorizedException($request);
                    }

                    $this->check(
                        $request,
                        function (): void {
                            $this->sanitizeInteger('form_id');
                            $this->sanitizeBoolean('submitted');
                            $this->sanitizeInteger('MAX_FILE_SIZE');

                            foreach ($this->viewData[$this->modName.'fieldResult'] as $row) {
                                $key = $this->modName.'field_'.$row['id'];

                                if (\array_key_exists($key, $this->postData)) {
                                    $filteredKey = $row['type'];

                                    $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                                    $this->filterValue->sanitize($filteredKey, 'titlecase');
                                    $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                                    if (method_exists($this, 'sanitize'.ucfirst((string) $this->action).$filteredKey) && \is_callable([$this, 'sanitize'.ucfirst((string) $this->action).$filteredKey])) {
                                        \call_user_func_array([$this, 'sanitize'.ucfirst((string) $this->action).$filteredKey], [$key]);
                                    } else {
                                        $this->filterSubject->sanitize($key)->toBlankOr('trim');
                                    }
                                }
                            }
                        },
                        function (): void {
                            foreach ($this->viewData[$this->modName.'fieldResult'] as $row) {
                                $key = $this->modName.'field_'.$row['id'];

                                $filteredKey = $row['type'];

                                $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                                $this->filterValue->sanitize($filteredKey, 'titlecase');
                                $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                                if (\array_key_exists($key, $this->postData)) {
                                    if (method_exists($this, 'validate'.ucfirst((string) $this->action).$filteredKey) && \is_callable([$this, 'validate'.ucfirst((string) $this->action).$filteredKey])) {
                                        \call_user_func_array([$this, 'validate'.ucfirst((string) $this->action).$filteredKey], [$key, null, [
                                            'label' => $row['name'],
                                            'attr' => [],
                                            '_row' => $row,
                                        ]]);
                                    }
                                }

                                if (!empty($row['required'])) {
                                    if (method_exists($this, '_validate'.ucfirst((string) $this->action).$filteredKey.'Required') && \is_callable([$this, '_validate'.ucfirst((string) $this->action).$filteredKey.'Required'])) {
                                        \call_user_func_array([$this, '_validate'.ucfirst((string) $this->action).$filteredKey.'Required'], [$key, null, [
                                            'label' => $row['name'],
                                            'attr' => [],
                                            '_row' => $row,
                                        ]]);
                                    } elseif (method_exists($this, '_validate'.ucfirst((string) $this->action).'Required') && \is_callable([$this, '_validate'.ucfirst((string) $this->action).'Required'])) {
                                        \call_user_func_array([$this, '_validate'.ucfirst((string) $this->action).'Required'], [$key, null, [
                                            'label' => $row['name'],
                                            'attr' => [],
                                            '_row' => $row,
                                        ]]);
                                    } elseif (method_exists($this, '_validateRequired') && \is_callable([$this, 'validateRequired'])) {
                                        \call_user_func_array([$this, '_validateRequired'], [$key, null, [
                                            'label' => $row['name'],
                                            'attr' => [],
                                            '_row' => $row,
                                        ]]);
                                    }
                                }
                            }
                        },
                        function (): void {
                            foreach ($this->viewData[$this->modName.'fieldResult'] as $row) {
                                $key = $this->modName.'field_'.$row['id'];

                                // if (array_key_exists($key, $this->postData)) { // <- allow disabled file inputs with full max_files
                                $filteredKey = $row['type'];

                                $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                                $this->filterValue->sanitize($filteredKey, 'titlecase');
                                $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                                if (method_exists($this, 'alterBefore'.ucfirst((string) $this->action).$filteredKey) && \is_callable([$this, 'alterBefore'.ucfirst((string) $this->action).$filteredKey])) {
                                    \call_user_func_array([$this, 'alterBefore'.ucfirst((string) $this->action).$filteredKey], [$key]);
                                }
                                // }
                            }
                        },
                        function (): void {
                            foreach ($this->viewData[$this->modName.'fieldResult'] as $row) {
                                $key = $this->modName.'field_'.$row['id'];

                                if (\array_key_exists($key, $this->postData)) {
                                    $filteredKey = $row['type'];

                                    $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                                    $this->filterValue->sanitize($filteredKey, 'titlecase');
                                    $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                                    if (method_exists($this, 'alterAfter'.ucfirst((string) $this->action).$filteredKey) && \is_callable([$this, 'alterAfter'.ucfirst((string) $this->action).$filteredKey])) {
                                        \call_user_func_array([$this, 'alterAfter'.ucfirst((string) $this->action).$filteredKey], [$key]);
                                    }
                                }
                            }
                        }
                    );

                    if (0 === \count($this->errors)) {
                        if ($this->container->has('Mod\\'.ucfirst($this->modName).'value\\'.ucfirst(static::$env))) {
                            $ModFormvalue = $this->container->get('Mod\\'.ucfirst($this->modName).'value\\'.ucfirst(static::$env));

                            $ModFormvalue->controller = $this->controller;
                            $ModFormvalue->action = $this->action; // TODO - use `add`?
                            $ModFormvalue->filesData = $this->filesData;

                            $this->db->exec('DELETE FROM '.($this->config['mod.'.$this->modName.'.db.1.prefix'] ?? $this->config['db.1.prefix']).$ModFormvalue->modName.' WHERE '.$this->modName.'_id = :'.$this->modName.'_id AND member_id = :member_id', [$this->modName.'_id' => $this->id, 'member_id' => $this->auth->getIdentity()['id']]);

                            foreach ($this->viewData[$this->modName.'fieldResult'] as $row) {
                                $key = $this->modName.'field_'.$row['id'];

                                if (\array_key_exists($key, $this->postData)) {
                                    $filteredKey = $row['type'];

                                    $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                                    $this->filterValue->sanitize($filteredKey, 'titlecase');
                                    $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                                    $ModFormvalue->postData = [
                                        'catmember_id' => $this->auth->getIdentity()['catmember_id'],
                                        'member_id' => $this->auth->getIdentity()['id'],
                                        'catform_id' => $this->{'cat'.$this->modName.'_id'},
                                        'form_id' => $this->id,
                                        'formfield_id' => $row['id'],
                                        'data' => $this->postData[$key],
                                        'active' => 1,
                                    ];

                                    if (!empty($this->postData['_'.$key])) {
                                        $ModFormvalue->postData['_'.$key] = $this->postData['_'.$key];
                                    }

                                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$ModFormvalue->modName.'.actionAdd.before');

                                    $insertId = $ModFormvalue->dbAdd();

                                    $this->dispatcher->dispatch(new GenericEvent(arguments: [
                                        'id' => $insertId,
                                    ]), 'event.'.static::$env.'.'.$ModFormvalue->modName.'.actionAdd.after');

                                    if (method_exists($this, '_actionPost'.ucfirst((string) $this->action).$filteredKey) && \is_callable([$this, '_actionPost'.ucfirst((string) $this->action).$filteredKey])) {
                                        $return = \call_user_func_array([$this, '_actionPost'.ucfirst((string) $this->action).$filteredKey], [$request, $response, array_merge($args, [
                                            '_insertId' => $insertId,
                                            '_row' => $row,
                                        ])]);

                                        if (null !== $return) {
                                            return $return;
                                        }
                                    }
                                }
                            }
                        } else {
                            throw new HttpNotFoundException($request);
                        }
                    }

                    $oldViewLayoutRegistryPaths = $this->viewLayoutRegistryPaths;
                    $oldViewRegistryPaths = $this->viewRegistryPaths;
                    $oldViewLayout = $this->viewLayout;

                    // admin email
                    if (0 === \count($this->errors)) {
                        if (empty($next)) {
                            $env = 'email';

                            array_push(
                                $this->viewLayoutRegistryPaths,
                                _ROOT.'/app/view/'.$env.'/layout',
                                _ROOT.'/app/view/'.$env.'/partial'
                            );

                            array_push(
                                $this->viewRegistryPaths,
                                _ROOT.'/app/view/'.$env.'/controller/'.$this->controller,
                                _ROOT.'/app/view/'.$env.'/base',
                                _ROOT.'/app/view/'.$env.'/partial'
                            );

                            $subject = \sprintf(__('[%1$s #%2$d] Completed %3$s at %4$s'), $ModMember->singularName, $this->auth->getIdentity()['id'], $this->view->getData()->{'cat'.$this->modName.'Row'}['name'], $this->helper->Url()->removeScheme($this->helper->Url()->getBaseUrl()));

                            $this->viewData = array_merge(
                                $this->viewData,
                                [
                                    'Mod' => $this,
                                    'subject' => $subject,
                                ]
                            );

                            $this->viewLayout = 'blank';

                            $oldAction = $this->action;
                            $this->action .= '-admin';

                            $html = $this->renderBody($request, $response, $args);

                            $this->action = $oldAction;

                            $params = [
                                'to' => $this->config['mail.'.$this->controller.'.to'] ?? $this->config['mail.to'] ?? $this->config['debug.emailsTo'],
                                'sender' => $this->config['mail.'.$this->controller.'.sender'] ?? null,
                                'from' => $this->config['mail.'.$this->controller.'.from'] ?? null,
                                'replyTo' => $this->config['mail.'.$this->controller.'.replyTo'] ?? null,
                                'cc' => $this->config['mail.'.$this->controller.'.cc'] ?? null,
                                'bcc' => $this->config['mail.'.$this->controller.'.bcc'] ?? null,
                                'returnPath' => $this->config['mail.'.$this->controller.'.returnPath'] ?? null,
                                'subject' => $subject,
                                'html' => $html,
                                'text' => $this->helper->Html()->html2Text($html),
                            ];

                            $this->mailer->prepare($params);

                            if (!$this->mailer->send()) {
                                $this->errors[] = __('A technical problem has occurred, try again later.');
                            }
                        }
                    }

                    // member email
                    if (0 === \count($this->errors)) {
                        if (empty($next)) {
                            $subject = $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('Thank you! Your data have been received - %1$s'), $this->view->getData()->{'cat'.$this->modName.'Row'}['name']));

                            $html = $this->renderBody($request, $response, $args);

                            [, $emailDomain] = explode('@', (string) $this->auth->getIdentity()['email']);

                            $params = [
                                'to' => $this->auth->getIdentity()['email'],
                                'sender' => $this->config['mail.'.$this->controller.'.sender'] ?? null,
                                'from' => $this->config['mail.'.$this->controller.'.from'] ?? null,
                                'replyTo' => $this->config['mail.'.$this->controller.'.replyTo'] ?? null,
                                'cc' => $this->config['mail.'.$this->controller.'.cc'] ?? null,
                                'bcc' => $this->config['mail.'.$this->controller.'.'.$emailDomain.'.bcc'] ?? $this->config['mail.'.$emailDomain.'.bcc'] ?? $this->config['mail.'.$this->controller.'.bcc'] ?? null,
                                'returnPath' => $this->config['mail.'.$this->controller.'.returnPath'] ?? null,
                                'subject' => $subject,
                                'html' => $html,
                                'text' => $this->helper->Html()->html2Text($html),
                            ];

                            $this->mailer->prepare($params);

                            if (!$this->mailer->send()) {
                                $this->errors[] = __('A technical problem has occurred, try again later.');
                            }
                        }
                    }

                    $this->viewLayoutRegistryPaths = $oldViewLayoutRegistryPaths;
                    $this->viewRegistryPaths = $oldViewRegistryPaths;
                    $this->viewLayout = $oldViewLayout;

                    if (0 === \count($this->errors)) {
                        if (empty($next)) {
                            $message = [
                                \sprintf(
                                    __('Congratulations! You have successfully completed all application %1$s in this category %2$s.'),
                                    $this->helper->Nette()->Strings()->lower($this->pluralName),
                                    '<b>'.$this->view->getData()->{'cat'.$this->modName.'Row'}['name'].'</b>'
                                ),
                                __('A confirmation email has just been sent to your address.'),
                                \sprintf(
                                    __('Now you can print all application %1$s just completed.'),
                                    $this->helper->Nette()->Strings()->lower($this->pluralName)
                                ),
                            ];

                            $logMessage = \sprintf(_x('Completed %1$s #%2$d', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $this->id);

                            __('Completed %1$s #%2$d', 'default');
                            __('Completed %1$s #%2$d', 'male');
                            __('Completed %1$s #%2$d', 'female');

                            $redirect = $this->helper->Url()->urlFor([
                                'routeName' => static::$env.'.'.$this->modName,
                                'data' => [
                                    'action' => 'index',
                                ],
                            ]);
                        } elseif (\in_array($this->id, $foundIds, true) && !empty($this->postData['submitted'])) {
                            $message = \sprintf(_x('%1$s successfully modified.', $this->context), $this->singularName);

                            __('%1$s successfully modified.', 'default');
                            __('%1$s successfully modified.', 'male');
                            __('%1$s successfully modified.', 'female');

                            $logMessage = \sprintf(_x('Modified %1$s #%2$d', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $this->id);

                            __('Modified %1$s #%2$d', 'default');
                            __('Modified %1$s #%2$d', 'male');
                            __('Modified %1$s #%2$d', 'female');

                            $redirect = $this->helper->Url()->getPathUrl();
                        } else {
                            $message = \sprintf(_x('%1$s %2$s successfully filled.', $this->context), $this->singularName, '<b>'.$this->title.'</b>');

                            __('%1$s %2$s successfully filled.', 'default');
                            __('%1$s %2$s successfully filled.', 'male');
                            __('%1$s %2$s successfully filled.', 'female');

                            $logMessage = \sprintf(_x('Filled %1$s #%2$d', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $this->id);

                            __('Filled %1$s #%2$d', 'default');
                            __('Filled %1$s #%2$d', 'male');
                            __('Filled %1$s #%2$d', 'female');

                            $redirect = $this->helper->Url()->urlFor($next['_routeArgs']);
                        }

                        $this->logger->info($logMessage);

                        $this->session->addFlash([
                            'type' => 'alert',
                            'options' => [
                                'type' => 'success',
                                'message' => $message,
                            ],
                        ]);

                        return $response
                            ->withHeader('Location', $redirect)
                            // https://stackoverflow.com/a/6788439/3929620
                            // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                            ->withStatus(303)
                        ;
                    }

                    if (\count($this->errors) > 0) {
                        $this->session->addFlash([
                            'type' => 'alert',
                            'options' => [
                                'type' => 'danger',
                                'message' => current($this->errors),
                            ],
                        ]);

                        $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                            'error' => var_export($this->errors, true),
                        ]);
                    }
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

            $this->viewData = array_merge(
                $this->viewData,
                [
                    'backButton' => false,
                ]
            );
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    public function actionIndex(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->title = $this->metaTitle = \sprintf(__('Print %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName));

        foreach ($this->lang->codeArr as $langId => $langCode) {
            $this->routeArgsArr[$langId] = [
                'routeName' => static::$env.'.'.$this->modName.'.params',
                'data' => [
                    'lang' => $langCode,
                    'action' => $this->action,
                    'params' => implode('/', $this->routeParamsArr),
                ],
            ];
        }

        $this->routeArgs = $this->routeArgsArr[$this->lang->id];

        if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
            $this->acceptRouteArgs = [
                'routeName' => static::$env.'.'.$this->modName.'.params',
                'data' => [
                    'lang' => $this->lang->acceptCode,
                    'action' => $this->action,
                    'params' => implode('/', $this->routeParamsArr),
                ],
                'full' => true,
            ];
        }

        // FIXME - circular dependencies
        $this->viewHelper = $this->container->get(ViewHelperInterface::class);

        // TODO - check email confirmed, etc. ?

        if (!empty($this->view->getData()->{$this->modName.'Result'}) && !empty($this->view->getData()->{$this->modName.'valueResult'})) {
            // https://stackoverflow.com/a/49645329/3929620
            $catIds = array_column($this->view->getData()->{$this->modName.'valueResult'}, 'cat'.$this->modName.'_id');

            if (!\in_array($this->view->getData()->{'cat'.$this->modName.'Row'}['id'], $catIds, true)) { // <--
                $this->errors[] = \sprintf(__('You have already filled some forms in %1$s, so you can\'t proceed here.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                    'href' => $this->helper->Url()->urlFor([
                        'routeName' => static::$env.'.cat'.$this->modName.'.params',
                        'data' => [
                            'action' => 'view',
                            'params' => current($catIds),
                            'cat'.$this->modName.'_id' => current($catIds),
                        ],
                    ]),
                ]).'>'.__('another category').'</a>');
            }

            if (0 === \count($this->errors)) {
                // https://stackoverflow.com/a/49645329/3929620
                $ids = array_column($this->view->getData()->{$this->modName.'Result'}, 'id');
                $foundIds = array_column($this->view->getData()->{$this->modName.'valueResult'}, $this->modName.'_id');
                $partialIds = !empty($this->view->getData()->{$this->modName.'valuePartialResult'}) ? array_column($this->view->getData()->{$this->modName.'valuePartialResult'}, $this->modName.'_id') : [];
                $diffIds = array_diff($ids, $foundIds);

                if (\count($diffIds) > 0 || \count($partialIds) > 0) {
                    $emptyIds = [...$diffIds, ...$partialIds];

                    // http://stackoverflow.com/a/8321709
                    // https://www.php.net/manual/en/function.array-unique.php#122226
                    $emptyIds = array_keys(array_flip($emptyIds));

                    $this->errors[] = [
                        __('All application forms in this category must be completed, in order to be able to print them later.'),
                        \sprintf(__('%1$s to fill them all.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                            'href' => $this->helper->Url()->urlFor([
                                'routeName' => static::$env.'.'.$this->modName.'.params',
                                'data' => [
                                    'action' => 'fill',
                                    'params' => current($emptyIds),
                                ],
                            ]),
                        ]).'>'.$this->helper->Nette()->Strings()->firstUpper(__('click here')).'</a>')];
                }
            }
        }

        if (0 === \count($this->errors)) {
            $this->internalOrder = 'a.hierarchy ASC';

            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getCount.where';
            $callback = function (GenericEvent $event): void {
                $this->dbData['sql'] .= ' AND a.cat'.$this->modName.'_id = :cat'.$this->modName.'_id';
                $this->dbData['sql'] .= ' AND a.printable = :printable';
                $this->dbData['args']['cat'.$this->modName.'_id'] = $this->view->getData()->{'cat'.$this->modName.'Row'}['id'];
                $this->dbData['args']['printable'] = 1;
            };

            $eventName2 = 'event.'.static::$env.'.'.$this->modName.'.getAll.where';
            $callback2 = function (GenericEvent $event): void {
                $this->dbData['sql'] .= ' AND a.cat'.$this->modName.'_id = :cat'.$this->modName.'_id';
                $this->dbData['sql'] .= ' AND a.printable = :printable';
                $this->dbData['args']['cat'.$this->modName.'_id'] = $this->view->getData()->{'cat'.$this->modName.'Row'}['id'];
                $this->dbData['args']['printable'] = 1;
            };

            $this->dispatcher->addListener($eventName, $callback);
            $this->dispatcher->addListener($eventName2, $callback2);

            $return = parent::actionIndex($request, $response, $args);

            $this->dispatcher->removeListener($eventName, $callback);
            $this->dispatcher->removeListener($eventName2, $callback2);

            return $return;
        }

        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                $this->title,
                $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
            ),
            $this->helper->Url()->urlFor($this->routeArgs)
        );

        $this->session->addFlash([
            'type' => 'alert',
            'options' => [
                'type' => 'warning',
                'message' => current($this->errors),
                'dismissible' => false,
            ],
        ]);
    }

    public function actionPrint(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->existStrict([
            'active' => true,
        ])) {
            $this->setFields();

            $prefix = '';
            if (!empty($this->view->getData()->{$this->modName.'Result'})) { // <--
                if (($n = $this->helper->Arrays()->recursiveArraySearch('id', $this->id, $this->view->getData()->{$this->modName.'Result'}, true)) !== false) {
                    $prefix = ++$n.' - ';
                }
            }

            $this->title = $this->metaTitle = $prefix.$this->name;
            $this->subTitle = $this->subname;

            foreach ($this->lang->codeArr as $langId => $langCode) {
                $this->routeArgsArr[$langId] = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $langCode,
                        'action' => $this->action,
                        'params' => implode('/', $this->routeParamsArr),
                    ],
                ];
            }

            $this->routeArgs = $this->routeArgsArr[$this->lang->id];

            if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
                $this->acceptRouteArgs = [
                    'routeName' => static::$env.'.'.$this->modName.'.params',
                    'data' => [
                        'lang' => $this->lang->acceptCode,
                        'action' => $this->action,
                        'params' => implode('/', $this->routeParamsArr),
                    ],
                    'full' => true,
                ];
            }

            // FIXME - circular dependencies
            $this->viewHelper = $this->container->get(ViewHelperInterface::class);

            // TODO - check email confirmed, etc. ?

            if (!empty($this->view->getData()->{$this->modName.'Result'}) && !empty($this->view->getData()->{$this->modName.'valueResult'})) {
                // https://stackoverflow.com/a/49645329/3929620
                $catIds = array_column($this->view->getData()->{$this->modName.'valueResult'}, 'cat'.$this->modName.'_id');

                if (!\in_array($this->view->getData()->{'cat'.$this->modName.'Row'}['id'], $catIds, true)) { // <--
                    $this->errors[] = \sprintf(__('You have already filled some forms in %1$s, so you can\'t proceed here.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                        'href' => $this->helper->Url()->urlFor([
                            'routeName' => static::$env.'.cat'.$this->modName.'.params',
                            'data' => [
                                'action' => 'view',
                                'params' => current($catIds),
                                'cat'.$this->modName.'_id' => current($catIds),
                            ],
                        ]),
                    ]).'>'.__('another category').'</a>');
                }

                if (0 === \count($this->errors)) {
                    // https://stackoverflow.com/a/49645329/3929620
                    $ids = array_column($this->view->getData()->{$this->modName.'Result'}, 'id');
                    $foundIds = array_column($this->view->getData()->{$this->modName.'valueResult'}, $this->modName.'_id');
                    $partialIds = !empty($this->view->getData()->{$this->modName.'valuePartialResult'}) ? array_column($this->view->getData()->{$this->modName.'valuePartialResult'}, $this->modName.'_id') : [];
                    $diffIds = array_diff($ids, $foundIds);

                    if (\count($diffIds) > 0 || \count($partialIds) > 0) {
                        $emptyIds = [...$diffIds, ...$partialIds];

                        // http://stackoverflow.com/a/8321709
                        // https://www.php.net/manual/en/function.array-unique.php#122226
                        $emptyIds = array_keys(array_flip($emptyIds));

                        $this->errors[] = [
                            __('All application forms in this category must be completed, in order to be able to print them later.'),
                            \sprintf(__('%1$s to fill them all.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                                'href' => $this->helper->Url()->urlFor([
                                    'routeName' => static::$env.'.'.$this->modName.'.params',
                                    'data' => [
                                        'action' => 'fill',
                                        'params' => current($emptyIds),
                                    ],
                                ]),
                            ]).'>'.$this->helper->Nette()->Strings()->firstUpper(__('click here')).'</a>')];
                    }
                }

                if (0 === \count($this->errors)) {
                    if ($this->container->has('Mod\\'.ucfirst($this->modName).'field\\'.ucfirst(static::$env))) {
                        $ModFormfield = $this->container->get('Mod\\'.ucfirst($this->modName).'field\\'.ucfirst(static::$env));

                        $ModFormfield->controller = $this->controller;
                        $ModFormfield->action = $this->action;

                        if (method_exists($ModFormfield, '_'.__FUNCTION__) && \is_callable([$ModFormfield, '_'.__FUNCTION__])) {
                            $return = \call_user_func_array([$ModFormfield, '_'.__FUNCTION__], [$request, $response, $args]);

                            if (null !== $return) {
                                return $return;
                            }

                            $this->viewData = array_merge(
                                $this->viewData,
                                $ModFormfield->viewData
                            );
                        } else {
                            throw new HttpNotFoundException($request);
                        }
                    } else {
                        throw new HttpNotFoundException($request);
                    }
                }

                if (0 === \count($this->errors)) {
                    $this->viewLayout = 'print';

                    $this->viewData = array_merge(
                        $this->viewData,
                        [
                            'loadPrint' => (bool) !($this->config['debug.enabled'] ?? false),
                        ]
                    );
                } else {
                    $this->breadcrumb->add(
                        $this->helper->Nette()->Strings()->truncate(
                            $this->title,
                            $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                        ),
                        $this->helper->Url()->urlFor($this->routeArgs)
                    );

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
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    public function _actionIndex(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->title = $this->metaTitle = \sprintf(__('Print %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName));
    }

    protected function _actionPostFillRecommendation(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $key = $this->modName.'field_'.$args['_row']['id'];

        if (empty($this->postData[$key])) {
            return;
        }

        $ModCatmember = $this->container->get('Mod\Catmember\\'.ucfirst(static::$env));
        $ModFormvalue = $this->container->get('Mod\Formvalue\\'.ucfirst(static::$env));

        $eventName = 'event.'.static::$env.'.'.$ModCatmember->modName.'.getOne.where';
        $callback = function (GenericEvent $event) use ($ModCatmember): void {
            $ModCatmember->dbData['sql'] .= ' AND a.main = :main';
            $ModCatmember->dbData['args']['main'] = 1;
        };

        $this->dispatcher->addListener($eventName, $callback);

        $rowCatmember = $ModCatmember->getOne([
            'id' => false,
            'active' => true,
        ]);

        $this->dispatcher->removeListener($eventName, $callback);

        if (!empty($rowCatmember['id'])) {
            $ModMember = $this->container->get('Mod\Member\\'.ucfirst(static::$env));

            $oldViewLayoutRegistryPaths = $this->viewLayoutRegistryPaths;
            $oldViewRegistryPaths = $this->viewRegistryPaths;
            $oldViewLayout = $this->viewLayout;

            $env = 'email';

            array_push(
                $this->viewLayoutRegistryPaths,
                _ROOT.'/app/view/'.$env.'/layout',
                _ROOT.'/app/view/'.$env.'/partial'
            );

            array_push(
                $this->viewRegistryPaths,
                _ROOT.'/app/view/'.$env.'/controller/'.$this->controller,
                _ROOT.'/app/view/'.$env.'/base',
                _ROOT.'/app/view/'.$env.'/partial'
            );

            $this->viewLayout = 'blank';

            foreach ($this->postData[$key.'_teachers'] as $k => $v) {
                if (!empty($v['files'])) {
                    continue;
                }

                $insertId = null;

                // FIXED - reload default fields values (e.g. private_key)
                $ModMember->init();

                $ModMember->removeAllListeners();

                $eventName = 'event.'.static::$env.'.'.$ModMember->modName.'.getOne.where';
                $callback = function (GenericEvent $event) use ($ModMember, $v): void {
                    $ModMember->dbData['sql'] .= ' AND a.email = :email';
                    $ModMember->dbData['args']['email'] = $v['email'];
                };

                $this->dispatcher->addListener($eventName, $callback);

                $rowMember = $ModMember->getOne([
                    'id' => false,
                ]);

                $this->dispatcher->removeListener($eventName, $callback);

                if (empty($rowMember)) {
                    // $ModMember->controller = $this->controller;
                    $ModMember->action = $this->action;

                    $password = $this->helper->Nette()->Random()->generate(
                        $this->config['mod.'.$ModMember->modName.'.auth.password.minLength'] ?? $this->config['auth.password.minLength'],
                        $this->config['mod.'.$ModMember->modName.'.auth.password.charlist'] ?? $this->config['auth.password.charlist']
                    );

                    $ModMember->check(
                        $request,
                        function (): void {},
                        function (): void {},
                        function () use ($ModMember, $rowCatmember, $v, $password): void {
                            $ModMember->postData = [
                                'cat'.$ModMember->modName.'_id' => $rowCatmember['id'],
                                'firstname' => $v['firstname'],
                                'lastname' => $v['lastname'],
                                'email' => $v['email'],
                                'password' => $password,
                                'active' => 1,
                            ];
                        },
                    );

                    $this->errors = $ModMember->errors;

                    if (0 === \count($this->errors)) {
                        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$ModMember->modName.'.actionAdd.before');

                        $insertId = $ModMember->dbAdd();

                        $this->dispatcher->dispatch(new GenericEvent(arguments: [
                            'id' => $insertId,
                        ]), 'event.'.static::$env.'.'.$ModMember->modName.'.actionAdd.after');
                    }
                }

                $ModMember->addAllListeners();

                if (0 === \count($this->errors)) {
                    if (($teacherKey = $this->helper->Arrays()->recursiveArraySearch('email', $v['email'], $this->postData['_'.$key]['teachers'], true)) !== false) { // <--
                        $this->postData['_'.$key]['teachers'][$teacherKey]['id'] = $insertId ?? $rowMember['id'];
                    } else {
                        $this->errors[] = __('A technical problem has occurred, try again later.');
                    }
                }

                if (0 === \count($this->errors)) {
                    if (!empty($rowMember['id']) && empty($rowMember['country_id'])) {
                        $this->db->exec('UPDATE '.$this->config['db.1.prefix'].$ModMember->modName.' SET firstname = :firstname, lastname = :lastname WHERE id = :id', [
                            'firstname' => $v['firstname'],
                            'lastname' => $v['lastname'],
                            'id' => $rowMember['id'],
                        ]);
                    }
                }

                if (0 === \count($this->errors)) {
                    $ModMember->setId($insertId ?? $rowMember['id']);

                    if ($ModMember->exist()) {
                        $ModMember->setFields();

                        $this->viewData = array_merge(
                            $this->viewData,
                            [
                                'Mod' => $ModMember,
                            ]
                        );
                    } else {
                        throw new HttpNotFoundException($request);
                    }
                }

                // teacher email
                if (0 === \count($this->errors) && !empty($insertId)) {
                    $subject = $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('%1$s %2$s at %3$s for recommendation request'), __('Confirm'), $this->helper->Nette()->Strings()->lower(__('Signup')), settingOrConfig('company.supName')));

                    $this->viewData = array_merge(
                        $this->viewData,
                        [
                            'password' => $password,
                        ]
                    );

                    $oldAction = $this->action;
                    $this->action .= '-'.$args['_row']['type'].'-signup';

                    $html = $this->renderBody($request, $response, $args);

                    $this->action = $oldAction;

                    [, $emailDomain] = explode('@', (string) $ModMember->email);

                    $params = [
                        'to' => $ModMember->email,
                        'sender' => $this->config['mail.'.$ModMember->controller.'.sender'] ?? null,
                        'from' => $this->config['mail.'.$ModMember->controller.'.from'] ?? null,
                        'replyTo' => $this->config['mail.'.$ModMember->controller.'.replyTo'] ?? null,
                        'cc' => $this->config['mail.'.$ModMember->controller.'.cc'] ?? null,
                        'bcc' => $this->config['mail.'.$ModMember->controller.'.'.$emailDomain.'.bcc'] ?? $this->config['mail.'.$emailDomain.'.bcc'] ?? $this->config['mail.'.$ModMember->controller.'.bcc'] ?? null,
                        'returnPath' => $this->config['mail.'.$ModMember->controller.'.returnPath'] ?? null,
                        'subject' => $subject,
                        'html' => $html,
                        'text' => $this->helper->Html()->html2Text($html),
                    ];

                    $this->mailer->prepare($params);

                    if (!$this->mailer->send()) {
                        $this->errors[] = __('A technical problem has occurred, try again later.');
                    }
                }

                // teacher email
                if (0 === \count($this->errors)) {
                    $subject = $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('New recommendation request from %1$s - %2$s'), $this->auth->getIdentity()['firstname'].' '.$this->auth->getIdentity()['lastname'], $this->view->getData()->{'cat'.$this->modName.'Row'}['name']));

                    $this->viewData = array_merge(
                        $this->viewData,
                        [
                            'firstname' => $this->auth->getIdentity()['firstname'],
                            'lastname' => $this->auth->getIdentity()['lastname'],
                        ]
                    );

                    $oldAction = $this->action;
                    $this->action .= '-'.$args['_row']['type'];

                    $html = $this->renderBody($request, $response, $args);

                    $this->action = $oldAction;

                    [, $emailDomain] = explode('@', (string) $ModMember->email);

                    $params = [
                        'to' => $ModMember->email,
                        'sender' => $this->config['mail.'.$ModMember->controller.'.sender'] ?? null,
                        'from' => $this->config['mail.'.$ModMember->controller.'.from'] ?? null,
                        'replyTo' => $this->config['mail.'.$ModMember->controller.'.replyTo'] ?? null,
                        'cc' => $this->config['mail.'.$ModMember->controller.'.cc'] ?? null,
                        'bcc' => $this->config['mail.'.$ModMember->controller.'.'.$emailDomain.'.bcc'] ?? $this->config['mail.'.$emailDomain.'.bcc'] ?? $this->config['mail.'.$ModMember->controller.'.bcc'] ?? null,
                        'returnPath' => $this->config['mail.'.$ModMember->controller.'.returnPath'] ?? null,
                        'subject' => $subject,
                        'html' => $html,
                        'text' => $this->helper->Html()->html2Text($html),
                    ];

                    $this->mailer->prepare($params);

                    if (!$this->mailer->send()) {
                        $this->errors[] = __('A technical problem has occurred, try again later.');
                    }
                }
            }

            if (0 === \count($this->errors)) {
                $this->db->exec('UPDATE '.$this->config['db.1.prefix'].$ModFormvalue->modName.' SET data = :data WHERE id = :id', [
                    'data' => $this->helper->Nette()->Json()->encode($this->postData['_'.$key]),
                    'id' => $args['_insertId'],
                ]);
            }

            $this->viewLayoutRegistryPaths = $oldViewLayoutRegistryPaths;
            $this->viewRegistryPaths = $oldViewRegistryPaths;
            $this->viewLayout = $oldViewLayout;
        } else {
            $this->logger->error($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                'error' => 'missing catmember main',
            ]);

            $this->errors[] = __('A technical problem has occurred, try again later.');
        }
    }
}
