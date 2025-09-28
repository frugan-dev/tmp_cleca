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

use App\Factory\Html\ViewHelperInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Symfony\Component\EventDispatcher\GenericEvent;

trait MemberActionTrait
{
    public function actionView(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->existStrict([
            'active' => true,
        ])) {
            return parent::actionView($request, $response, $args);
        }

        throw new HttpNotFoundException($request);
    }

    public function actionEdit(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->existStrict([
            'active' => true,
        ])) {
            return parent::actionEdit($request, $response, $args);
        }

        throw new HttpNotFoundException($request);
    }

    public function actionDelete(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->existStrict([
            'active' => true,
        ])) {
            return parent::actionDelete($request, $response, $args);
        }

        throw new HttpNotFoundException($request);
    }

    public function actionSignup(RequestInterface $request, ResponseInterface $response, $args)
    {
        // FIXME - circular dependencies
        $this->viewHelper = $this->container->get(ViewHelperInterface::class);

        if (!empty($request->getAttribute('hasIdentity'))) {
            $this->session->addFlash([
                'type' => 'toast',
                'options' => [
                    'type' => 'warning',
                    'message' => \sprintf(__('To view this content, you must first %1$s.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                        'href' => $this->helper->Url()->urlFor([
                            'routeName' => static::$env.'.'.$this->modName,
                            'data' => [
                                'action' => 'logout',
                            ],
                        ]),
                    ]).'>'.$this->helper->Nette()->Strings()->lower(__('Logout')).'</a>'),
                    'autohide' => false,
                ],
            ]);

            return $response
                ->withHeader('Location', $this->helper->Url()->urlFor(static::$env.'.index'))
                ->withStatus(302)
            ;
        }

        $this->title = $this->metaTitle = __('Signup');

        foreach ($this->lang->codeArr as $langId => $langCode) {
            $this->routeArgsArr[$langId] = [
                'routeName' => static::$env.'.'.$this->modName,
                'data' => [
                    'lang' => $langCode,
                    'action' => $this->action,
                ],
            ];
        }

        $this->routeArgs = $this->routeArgsArr[$this->lang->id];

        if (!empty($this->lang->acceptCode) && $this->lang->acceptCode !== $this->lang->code) {
            $this->acceptRouteArgs = [
                'routeName' => static::$env.'.'.$this->modName,
                'data' => [
                    'lang' => $this->lang->acceptCode,
                    'action' => $this->action,
                ],
                'full' => true,
            ];
        }

        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                $this->title,
                $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
            ),
            $this->helper->Url()->urlFor($this->routeArgs)
        );

        if ('POST' === $request->getMethod()) {
            $this->serverData = (array) $request->getServerParams();

            $this->check(
                $request,
                function (): void {
                    $this->sanitizeFirstname('firstname');

                    $this->sanitizeFirstname('lastname');

                    $this->sanitizeEmail('email');

                    $this->filterSubject->sanitize('privacy')->toBlankOr('bool', 1, 0);

                    $this->filterSubject->sanitize('password')->toBlankOr('trim');

                    $this->filterSubject->sanitize('more')->toBlankOr('trim');
                },
                function (): void {
                    $this->validateFirstname('firstname');
                    $this->filterSubject->validate('firstname')->isNotBlank()->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$this->fields['firstname'][static::$env]['label'].'</i>'));

                    $this->validateFirstname('lastname');
                    $this->filterSubject->validate('lastname')->isNotBlank()->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$this->fields['lastname'][static::$env]['label'].'</i>'));

                    $this->validateEmail('email');
                    $this->filterSubject->validate('email')->isNotBlank()->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$this->fields['email'][static::$env]['label'].'</i>'));

                    $this->filterSubject->validate('privacy')->is('equalToValue', 1)->setMessage(\sprintf(_nx('You must agree to the %1$s', 'You must agree to the %1$s', 1, 'female'), '<i>'.__('Privacy Policy').'</i>'));

                    _n('You must agree to the %1$s', 'You must agree to the %1$s', 1, 'default');
                    _n('You must agree to the %1$s', 'You must agree to the %1$s', 1, 'male');
                    _n('You must agree to the %1$s', 'You must agree to the %1$s', 1, 'female');

                    $this->validatePassword('password');
                    $this->filterSubject->validate('password')->isNotBlank()->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.$this->fields['password'][static::$env]['label'].'</i>'));

                    $this->validateGRecaptchaResponse('g-recaptcha-response');

                    if (!$this->session->get('timestamp_'.$this->controller.'_time')) {
                        $this->session->set('timestamp_'.$this->controller.'_time', $this->helper->Carbon()->now()->subSeconds(5)->getTimestamp());

                        if ((int) ($this->postData['timestamp'] ?? $this->helper->Carbon()->now()->getTimestamp()) > $this->session->get('timestamp_'.$this->controller.'_time')) {
                            $this->session->set('timestamp_'.$this->controller.'_error', true);
                        }
                    }

                    if ($this->session->get('timestamp_'.$this->controller.'_error')) {
                        $this->filterSubject->validate('timestamp')->is('error')->setMessage(__('SPAM attempt detected.').($this->config['debug.enabled'] ? ' (timestamp)' : ''));
                    }

                    $this->filterSubject->validate('more')->isBlank()->setMessage(__('SPAM attempt detected.').($this->config['debug.enabled'] ? ' (more)' : ''));
                }
            );

            if (0 === \count($this->errors)) {
                $this->session->remove('timestamp_'.$this->controller.'_time');
                $this->session->remove('timestamp_'.$this->controller.'_error');

                $Mod = $this->container->get('Mod\Cat'.$this->modName.'\\'.ucfirst(static::$env));

                $eventName = 'event.'.static::$env.'.'.$Mod->modName.'.getOne.where';
                $callback = function (GenericEvent $event) use ($Mod): void {
                    $Mod->dbData['sql'] .= ' AND a.preselected = :preselected';
                    $Mod->dbData['args']['preselected'] = 1;
                };

                $this->dispatcher->addListener($eventName, $callback);

                $row = $Mod->getOne([
                    'id' => false,
                    'active' => true,
                ]);

                $this->dispatcher->removeListener($eventName, $callback);

                if (!empty($row['id'])) {
                    $this->postData['cat'.$this->modName.'_id'] = $row['id'];
                    $this->postData['active'] = 1;

                    $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionAdd.before');

                    $insertId = $this->dbAdd();

                    $this->dispatcher->dispatch(new GenericEvent(arguments: [
                        'id' => $insertId,
                    ]), 'event.'.static::$env.'.'.$this->modName.'.actionAdd.after');

                    $oldViewLayoutRegistryPaths = $this->viewLayoutRegistryPaths;
                    $oldViewRegistryPaths = $this->viewRegistryPaths;
                    $oldViewLayout = $this->viewLayout;

                    // admin email
                    if (0 === \count($this->errors)) {
                        $this->setId($insertId);

                        if ($this->exist()) {
                            $this->setFields();

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

                            $subject = $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('[%1$s #%2$d] %3$s at %4$s'), $this->singularName, $this->id, $this->title, $this->helper->Url()->removeScheme($this->helper->Url()->getBaseUrl())));

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
                        } else {
                            throw new HttpNotFoundException($request);
                        }
                    }

                    // member email
                    if (0 === \count($this->errors)) {
                        $subject = $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('%1$s %2$s at %3$s'), __('Confirm'), $this->helper->Nette()->Strings()->lower($this->title), settingOrConfig('company.supName')));

                        $html = $this->renderBody($request, $response, $args);

                        [, $emailDomain] = explode('@', $this->email);

                        $params = [
                            'to' => $this->email,
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

                    $this->viewLayoutRegistryPaths = $oldViewLayoutRegistryPaths;
                    $this->viewRegistryPaths = $oldViewRegistryPaths;
                    $this->viewLayout = $oldViewLayout;

                    if (0 === \count($this->errors)) {
                        $result = $this->auth->forceAuthenticate($this->postData[$this->authUsernameField]);

                        if ($result->isValid()) {
                            // TODO - delay toast?

                            $this->session->addFlash([
                                'type' => 'alert',
                                'options' => [
                                    'type' => 'success',
                                    'message' => __('Your registration has been successful, check your email box to confirm your account.')
                                        .'<br>'.__('We also invite you to complete your personal data below.'),
                                ],
                            ]);

                            $this->logger->info(\sprintf(__('%1$s %2$s #%3$d', $this->context, $this->config['logger.locale']), ucfirst($this->action), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $result->getIdentity()['id']));

                            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                            return $response
                                ->withHeader('Location', $this->helper->Url()->urlFor([
                                    'routeName' => static::$env.'.'.$this->modName.'.params',
                                    'data' => [
                                        'action' => 'edit',
                                        'params' => $this->id,
                                    ],
                                ]))
                                // https://stackoverflow.com/a/6788439/3929620
                                // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                                ->withStatus(303)
                            ;
                        }
                        $this->errors = $result->getMessages();
                    }
                } else {
                    $this->logger->error('Missing cat'.$this->modName.' preselected');

                    $this->errors[] = __('A technical problem has occurred, try again later.');
                }
            }

            if (\count($this->errors) > 0) {
                $message = current($this->errors);

                $this->session->addFlash([
                    'type' => 'alert',
                    'options' => [
                        'env' => static::$env, // <-
                        'type' => 'danger',
                        'message' => $message,
                    ],
                ]);

                $this->logger->warning(strip_tags((string) $message), [
                    'error' => strip_tags((string) $message),
                    'text' => \Safe\json_encode($this->errors),
                ]);
            }
        }
    }

    public function actionSetting(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->existStrict([
            'active' => true,
        ])) {
            $this->setFields();

            $this->title = $this->metaTitle = _n('Setting', 'Settings', 2);

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

            $this->breadcrumb->add(
                $this->helper->Nette()->Strings()->truncate(
                    $this->title,
                    $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                ),
                $this->helper->Url()->urlFor($this->routeArgs)
            );

            if ('POST' === $request->getMethod()) {
                $this->check(
                    $request,
                    function (): void {
                        $this->filterSubject->sanitize('action')->toBlankOr('trim');
                    },
                    function (): void {
                        $this->filterSubject->validate('action')->isNotBlank()->setMessage(\sprintf(__('The %1$s field does not seem correct.'), '<i>'.__('Action').'</i>'));
                    }
                );

                if (0 === \count($this->errors)) {
                    $actionCamelCase = $this->postData['action'];

                    $this->filterValue->sanitize($actionCamelCase, 'string', ['_', '-'], ' ');
                    $this->filterValue->sanitize($actionCamelCase, 'titlecase');
                    $this->filterValue->sanitize($actionCamelCase, 'string', ' ', '');

                    if (method_exists($this, '_actionPost'.ucfirst((string) $actionCamelCase)) && \is_callable([$this, '_actionPost'.ucfirst((string) $actionCamelCase)])) {
                        $return = \call_user_func_array([$this, '_actionPost'.ucfirst((string) $actionCamelCase)], [$request, $response, $args]);

                        if (null !== $return) {
                            return $return;
                        }
                    } else {
                        $this->errors[] = __('A technical problem has occurred, try again later.');
                    }
                }

                if (0 === \count($this->errors)) {
                    return $response
                        ->withHeader('Location', $this->helper->Url()->urlFor($this->routeArgs))
                        // https://stackoverflow.com/a/6788439/3929620
                        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                        ->withStatus(303)
                    ;
                }

                if (\count($this->errors) > 0) {
                    $message = current($this->errors);

                    $this->session->addFlash([
                        'type' => 'toast',
                        'options' => [
                            'type' => 'danger',
                            'message' => $message,
                        ],
                    ]);

                    $this->logger->warning(strip_tags((string) $message), [
                        'error' => strip_tags((string) $message),
                        'text' => \Safe\json_encode($this->errors),
                    ]);
                }
            }
        } else {
            throw new HttpNotFoundException($request);
        }
    }

    protected function _actionReset(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->loginRouteArgs = [
            'routeName' => static::$env.'.action',
            'data' => [
                'action' => 'login',
            ],
        ];
    }

    protected function _actionConfirm(RequestInterface $request, ResponseInterface $response, $args): void
    {
        if (!empty($request->getAttribute('hasIdentity'))) {
            $this->profileRouteArgs = [
                'routeName' => static::$env.'.'.$this->modName.'.params',
                'data' => [
                    'action' => 'edit',
                    'params' => $this->auth->getIdentity()['id'],
                ],
            ];
        } else {
            $this->loginRouteArgs = [
                'routeName' => static::$env.'.action',
                'data' => [
                    'action' => 'login',
                ],
            ];
        }
    }

    protected function _actionPostSendkey(RequestInterface $request, ResponseInterface $response, $args): void
    {
        if (empty($this->confirmed)) {
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

            $subject = $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('%1$s %2$s at %3$s'), __('Confirm'), $this->helper->Nette()->Strings()->lower(__('Email')), settingOrConfig('company.supName')));

            $this->viewData = array_merge(
                $this->viewData,
                [
                    'Mod' => $this,
                    'subject' => $subject,
                ]
            );

            $this->viewLayout = 'blank';

            $oldAction = $this->action;
            $this->action = $this->postData['action'];

            $html = $this->renderBody($request, $response, $args);

            $this->action = $oldAction;

            [, $emailDomain] = explode('@', $this->email);

            $params = [
                'to' => $this->email,
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

            if ($this->mailer->send()) {
                $this->logger->info(\sprintf(__('%1$s %2$s request for %3$s', $this->context, $this->config['logger.locale']), __('Confirm'), $this->helper->Nette()->Strings()->lower(__('Email')), $this->email));

                $this->session->addFlash([
                    'type' => 'toast',
                    'options' => [
                        'type' => 'success',
                        'message' => __('Confirmation email sent successfully.'),
                    ],
                ]);
            } else {
                $this->errors[] = __('A technical problem has occurred, try again later.');
            }

            $this->viewLayoutRegistryPaths = $oldViewLayoutRegistryPaths;
            $this->viewRegistryPaths = $oldViewRegistryPaths;
            $this->viewLayout = $oldViewLayout;

            $this->session->remove('timestamp.'.static::$env.'.'.$this->modName.'.checkConfirmed');
        } else {
            $this->errors[] = \sprintf(_x('%1$s already confirmed.', 'female'), __('Email'));

            __('%1$s already confirmed.', 'default');
            __('%1$s already confirmed.', 'male');
            __('%1$s already confirmed.', 'female');
        }
    }

    protected function _actionPostDelete(RequestInterface $request, ResponseInterface $response, $args)
    {
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

        $subject = $this->helper->Nette()->Strings()->firstUpper(\sprintf(__('[%1$s #%2$d] %3$s at %4$s'), $this->singularName, $this->id, $this->helper->Nette()->Strings()->firstUpper($this->postData['action']), $this->helper->Url()->removeScheme($this->helper->Url()->getBaseUrl())));

        $this->viewData = array_merge(
            $this->viewData,
            [
                'Mod' => $this,
                'subject' => $subject,
            ]
        );

        $this->viewLayout = 'blank';

        $oldAction = $this->action;
        $this->action = $this->postData['action'].'-admin';

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

        if ($this->mailer->send()) {
            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionDelete.before');

            $return = $this->dbDelete();

            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.actionDelete.after');

            if (0 === \count($this->errors)) {
                $redirect = $this->session->get(static::$env.'.redirectAfterLogout', $this->helper->Url()->urlFor(static::$env.'.index'));

                $this->session->remove(static::$env.'.redirectAfterLogout');

                $this->session->addFlash([
                    'type' => 'modal',
                    'options' => [
                        'type' => 'success',
                        'title' => __('Success'),
                        'body' => __('Operation performed successfully.')
                            .'<br>'.__('We\'re sorry to see you leave').'&hellip; <i class="far fa-face-frown ms-1"></i>',
                        'size' => 'sm',
                    ],
                ]);

                $this->auth->clearIdentity();

                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                $this->logger->notice(\sprintf(_x('Deleted %1$s %2$s', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $this->email));

                __('Deleted %1$s #%2$d', 'default');
                __('Deleted %1$s #%2$d', 'male');
                __('Deleted %1$s #%2$d', 'female');

                return $response
                    ->withHeader('Location', $redirect)
                    // https://stackoverflow.com/a/6788439/3929620
                    // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                    ->withStatus(303)
                ;
            }
        } else {
            $this->errors[] = __('A technical problem has occurred, try again later.');
        }

        $this->viewLayoutRegistryPaths = $oldViewLayoutRegistryPaths;
        $this->viewRegistryPaths = $oldViewRegistryPaths;
        $this->viewLayout = $oldViewLayout;
    }
}
