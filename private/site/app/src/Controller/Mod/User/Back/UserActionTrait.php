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

namespace App\Controller\Mod\User\Back;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpUnauthorizedException;
use Symfony\Component\EventDispatcher\GenericEvent;

trait UserActionTrait
{
    public function actionView(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->existStrict()) {
            return parent::actionView($request, $response, $args);
        }

        throw new HttpNotFoundException($request);
    }

    public function actionEdit(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->existStrict()) {
            return parent::actionEdit($request, $response, $args);
        }

        throw new HttpNotFoundException($request);
    }

    public function actionDelete(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        if ($this->existStrict()) {
            return parent::actionDelete($request, $response, $args);
        }

        throw new HttpNotFoundException($request);
    }

    public function actionSwitch(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        $userIds = $this->session->get(static::$env.'.userIds', []);
        $lastUserId = !empty($userIds) ? end($userIds) : null;

        if ($this->auth->getIdentity()['id'] === (int) $this->id) {
            throw new HttpUnauthorizedException($request);
        }
        if (!empty($lastUserId)) {
            if (empty($this->auth->getIdentity()['cat'.$this->modName.'_main'] ?? null)
                && $lastUserId !== (int) $this->id) {
                throw new HttpUnauthorizedException($request);
            }
        } elseif (empty($this->auth->getIdentity()['cat'.$this->modName.'_main'] ?? null)) {
            throw new HttpUnauthorizedException($request);
        }

        if ($this->exist()) {
            $this->setFields();

            if (($lastUserId ?? null) === (int) $this->id) {
                array_pop($userIds);
                $this->session->set(static::$env.'.userIds', $userIds);

                $redirect = $this->helper->Url()->urlFor(static::$env.'.'.$this->modName);
            } else {
                $userIds[] = $this->auth->getIdentity()['id'];
                $this->session->set(static::$env.'.userIds', $userIds);

                $redirect = $this->helper->Url()->urlFor(static::$env.'.index');
            }

            $this->session->deleteFlash('alert', static::$env.'.'.$this->modName.'.checkSwitchUser');

            $logMessage = \sprintf(__('Switch %1$s from #%2$d to #%3$d', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $this->auth->getIdentity()['id'], $this->id);

            $isMain = $this->auth->getIdentity()['cat'.$this->modName.'_main'] ?? null;

            if (!empty($isMain)) {
                $this->logger->info($logMessage);
            }

            $this->auth->forceAuthenticate($this->{$this->authUsernameField});

            if (empty($isMain)) {
                $this->logger->info($logMessage);
            }

            if (!empty($langId = $this->auth->getIdentity()['lang_id'] ?? null)) {
                if ($langId !== $this->lang->id) {
                    if (isset($this->lang->arr[$langId])) {
                        $redirect = \Safe\preg_replace(
                            '~\/('.$this->lang->code.')(\/?$|\/*)~',
                            '/'.$this->lang->arr[$langId]['isoCode'].'$2',
                            (string) $redirect
                        );

                        $this->translator->prepare($langId);
                    }
                }
            }

            $this->session->addFlash([
                'type' => 'toast',
                'options' => [
                    'type' => 'success',
                    'message' => \sprintf(__('Logged in as %1$s.'), '<i>'.$this->helper->Nette()->Strings()->truncate($this->auth->getIdentity()['_name'], 30).'</i>'),
                ],
            ]);

            $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

            return $response
                ->withHeader('Location', $redirect)
                ->withStatus(302)
            ;
        }

        throw new HttpNotFoundException($request);
    }

    public function actionDeleteCache(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (!empty($this->cache->taggable)) {
            $tags = [
                'local-'.$this->modName.'-'.$this->auth->getIdentity()['id'],
                'global-1',
            ];

            if ($this->rbac->isGranted('cat'.$this->modName.'.'.static::$env.'.add')) {
                $tags[] = 'global-0';

                if (isDev()) {
                    $tags[] = 'permanent';
                }
            }

            $this->cache->invalidateTags($tags);
        } else {
            $this->cache->clear();
        }

        $this->session->addFlash([
            'type' => 'toast',
            'options' => [
                'type' => 'success',
                'message' => __('Cache cleared successfully.'),
            ],
        ]);

        $this->logger->info(__('Cache cleared', $this->context, $this->config['logger.locale']));

        $this->serverData = (array) $request->getServerParams();

        $redirect = isset($this->serverData['HTTP_REFERER']) && !\Safe\preg_match('/'.$this->action.'/', (string) $this->serverData['HTTP_REFERER']) ? $this->serverData['HTTP_REFERER'] : $this->helper->Url()->urlFor(static::$env.'.index');

        return $response
            ->withHeader('Location', $redirect)
            ->withStatus(302)
        ;
    }

    protected function _actionLogin(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->viewLayout = 'auth';
    }

    protected function _actionReset(RequestInterface $request, ResponseInterface $response, $args): void
    {
        $this->viewLayout = 'auth';
    }
}
