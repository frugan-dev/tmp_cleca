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

use App\Factory\Auth\AuthInterface;
use App\Factory\Mailer\MailerInterface;
use App\Factory\Session\SessionInterface;
use App\Helper\HelperInterface;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

return static function (ContainerInterface $container): void {
    $container->get(EventDispatcherInterface::class)->addListener(basename(__FILE__, '.php'), function (GenericEvent $event) use ($container): void {
        $clientIp = $container->get('request')->getAttribute('client-ip');

        if (empty($container->get('config')['debug.enabled'])
            && empty($container->get(AuthInterface::class)->getIdentity()['catuser_main'])
            && !in_array($clientIp, array_keys($container->get('config')['debug.ips']), true)) {
            $serverData = (array) $container->get('request')->getServerParams();

            $env = 'email';
            $controller = 'user';
            $action = 'notify';

            $container->get('view')->getLayoutRegistry()->setPaths([
                _ROOT.'/app/view/'.$env.'/layout',
                _ROOT.'/app/view/'.$env.'/partial',
                _ROOT.'/app/view/default/layout',
                _ROOT.'/app/view/default/partial',
            ]);

            $container->get('view')->getViewRegistry()->setPaths([
                _ROOT.'/app/view/'.$env.'/controller/'.$controller,
                _ROOT.'/app/view/'.$env.'/base',
                _ROOT.'/app/view/'.$env.'/partial',
                _ROOT.'/app/view/default/controller/'.$controller,
                _ROOT.'/app/view/default/base',
                _ROOT.'/app/view/default/partial',
            ]);

            $container->get('view')->setLayout('blank');
            $container->get('view')->setView($action);

            $subject = sprintf(__('Login to %1$s by %2$s'), $container->get(HelperInterface::class)->Url()->removeScheme($container->get(HelperInterface::class)->Url()->getBaseUrl()), $container->get(AuthInterface::class)->getIdentity()['name']);

            $container->get('view')->setData(array_merge([
                'authIdentity' => $container->get(AuthInterface::class)->getIdentity(),

                'referer' => $container->get(SessionInterface::class)->get('referer'),
                'httpUserAgent' => $serverData['HTTP_USER_AGENT'] ?? null,
                'hostByAddr' => @gethostbyaddr($clientIp),

                'env' => 'back',
                'config' => $container->get('config'),
                'auth' => $container->get(AuthInterface::class),
                'helper' => $container->get(HelperInterface::class),
                'session' => $container->get(SessionInterface::class),
                'lang' => $container->get('lang'),
            ], compact( // https://stackoverflow.com/a/30266377/3929620
                'subject',
                'container',
                'controller',
                'action',
                'clientIp'
            )));

            $html = $container->get('view')->__invoke();

            $params = [
                'to' => $container->get('config')['mail.'.$controller.'.'.$action.'.to'] ?? /* $container->get('config')['mail.to'] ?? */ $container->get('config')['debug.emailsTo'],
                'sender' => $container->get('config')['mail.'.$controller.'.'.$action.'.sender'] ?? null,
                'from' => $container->get('config')['mail.'.$controller.'.'.$action.'.from'] ?? null,
                'replyTo' => $container->get('config')['mail.'.$controller.'.'.$action.'.replyTo'] ?? null,
                'cc' => $container->get('config')['mail.'.$controller.'.'.$action.'.cc'] ?? null,
                'bcc' => $container->get('config')['mail.'.$controller.'.'.$action.'.bcc'] ?? null,
                'returnPath' => $container->get('config')['mail.'.$controller.'.'.$action.'.returnPath'] ?? null,
                'subject' => $subject,
                'html' => $html,
                'text' => $container->get(HelperInterface::class)->Html()->html2Text($html),
            ];

            $container->get(MailerInterface::class)->prepare($params);

            $container->get(MailerInterface::class)->send();
        }
    });
};
