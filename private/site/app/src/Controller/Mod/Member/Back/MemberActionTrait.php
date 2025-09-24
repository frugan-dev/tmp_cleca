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

namespace App\Controller\Mod\Member\Back;

use App\Factory\Html\ViewHelperInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\Cache\ItemInterface;
use voku\helper\HtmlMin;

trait MemberActionTrait
{
    private array $archiveItems = [];

    public function actionDownload(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->setId();

        $eventName = 'event.'.static::$env.'.'.$this->modName.'.existStrict.join';
        $callback = function (GenericEvent $event): void {
            $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'cat'.$this->modName.' AS c
        ON a.cat'.$this->modName.'_id = c.id';
        };

        $eventName2 = 'event.'.static::$env.'.'.$this->modName.'.existStrict.where';
        $callback2 = function (GenericEvent $event): void {
            $this->dbData['sql'] .= ' AND c.main = :main';
            $this->dbData['args']['main'] = 0;
        };

        $this->dispatcher->addListener($eventName, $callback);
        $this->dispatcher->addListener($eventName2, $callback2);

        $row = $this->existStrict();

        $this->dispatcher->removeListener($eventName, $callback);
        $this->dispatcher->removeListener($eventName2, $callback2);

        if ($row) {
            $redirect = $this->cache->get($this->cache->getItemKey([
                $this->getShortName(),
                __FUNCTION__,
                __LINE__,
                $this->id,
            ]), function (ItemInterface $cacheItem) use ($request, $response, $args) {
                // $cacheItem->expiresAt($this->helper->Carbon()->parse('yesterday'));

                if (!empty($this->cache->taggable)) {
                    $tags = [
                        'local-'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'],
                        'local-'.$this->modName.'-'.$this->id,
                        'global-1',
                    ];

                    $cacheItem->tag($tags);
                }

                $this->serverData = (array) $request->getServerParams();

                $redirect = isset($this->serverData['HTTP_REFERER']) && !\Safe\preg_match('/'.$this->action.'/', (string) $this->serverData['HTTP_REFERER']) ? $this->serverData['HTTP_REFERER'] : $this->helper->Url()->urlFor(static::$env.'.index');

                $oldLangId = $this->lang->id;

                $fallbackId = $this->config['lang.'.static::$env.'.fallbackId'] ?? $this->config['lang.fallbackId'];
                if ($fallbackId !== $this->lang->id) {
                    if (isset($this->lang->arr[$fallbackId])) {
                        $this->translator->prepare($fallbackId);
                        $this->container->set('lang', $this->translator);
                    }
                }

                $this->_setArchiveItems($request, $response, $args);

                if ($oldLangId !== $this->lang->id) {
                    $this->translator->prepare($oldLangId);
                    $this->container->set('lang', $this->translator);
                }

                if (!empty($this->archiveItems)) {
                    $fileName = 'archive-'.$this->id.'.zip';
                    $src = \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/tmp/'.$fileName);

                    if (false !== $this->helper->File()->archive($this->archiveItems, $src)) {
                        $random = $this->helper->Nette()->Random()->generate();
                        $dest = _PUBLIC.'/symlink/'.$random.'/'.$fileName;

                        $this->helper->Nette()->FileSystem()->createDir(\dirname($dest));

                        if (false !== $this->helper->File()->symlink($src, $dest)) {
                            $this->logger->debug(\sprintf(__('Download %1$s #%2$d', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), $this->id));

                            $redirect = $this->helper->Url()->getBaseUrl().'/symlink/'.$random.'/'.$fileName;

                            $cacheItem->expiresAfter($this->helper->CarbonInterval()->createFromDateString('12 hours'));
                        } else {
                            $this->errors[] = __('A technical problem has occurred, try again later.');
                        }
                    } else {
                        $this->errors[] = __('A technical problem has occurred, try again later.');
                    }
                } else {
                    $this->errors[] = __('No results found.');
                }

                return $redirect;
            });

            if (\count($this->errors) > 0) {
                $this->session->addFlash([
                    'type' => 'toast',
                    'options' => [
                        'type' => 'warning',
                        'message' => current($this->errors),
                    ],
                ]);

                $this->logger->debug($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
                    'error' => var_export($this->errors, true),
                ]);
            }

            return $response
                ->withHeader('Location', $redirect)
                ->withStatus(302)
            ;
        }

        throw new HttpNotFoundException($request);
    }

    protected function _actionPostDownloadBulk(RequestInterface $request, ResponseInterface $response, $args): void
    {
        // FIXME - circular dependencies
        $this->viewHelper = $this->container->get(ViewHelperInterface::class);

        $oldAction = $this->action;

        $this->action = $this->postData['action'];

        $this->check(
            $request,
            null,
            function (): void {
                $this->filterSubject->validate('bulk_ids')->isNotBlank()->setMessage(\sprintf(_x('No %1$s selected.', 'default'), $this->helper->Nette()->Strings()->lower(__('Item'))));
            }
        );

        __('No %1$s selected.', 'default');
        __('No %1$s selected.', 'male');
        __('No %1$s selected.', 'female');

        if (0 === \count($this->errors)) {
            natsort($this->postData['bulk_ids']);

            if (!empty($redirect = $this->cache->get($this->cache->getItemKey([
                $this->getShortName(),
                __FUNCTION__,
                __LINE__,
                $this->postData['bulk_ids'],
            ]), function (ItemInterface $cacheItem) use ($request, $response, $args) {
                \Safe\ini_set('max_execution_time', '60');
                \Safe\ini_set('memory_limit', '256M');

                // $cacheItem->expiresAt($this->helper->Carbon()->parse('yesterday'));

                $oldLangId = $this->lang->id;

                $fallbackId = $this->config['lang.'.static::$env.'.fallbackId'] ?? $this->config['lang.fallbackId'];
                if ($fallbackId !== $this->lang->id) {
                    if (isset($this->lang->arr[$fallbackId])) {
                        $this->translator->prepare($fallbackId);
                        $this->container->set('lang', $this->translator);
                    }
                }

                $foundIds = [];
                foreach ($this->postData['bulk_ids'] as $bulkId) {
                    $this->setId($bulkId);

                    $eventName = 'event.'.static::$env.'.'.$this->modName.'.existStrict.join';
                    $callback = function (GenericEvent $event): void {
                        $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'cat'.$this->modName.' AS c
        ON a.cat'.$this->modName.'_id = c.id';
                    };

                    $eventName2 = 'event.'.static::$env.'.'.$this->modName.'.existStrict.where';
                    $callback2 = function (GenericEvent $event): void {
                        $this->dbData['sql'] .= ' AND c.main = :main';
                        $this->dbData['args']['main'] = 0;
                    };

                    $this->dispatcher->addListener($eventName, $callback);
                    $this->dispatcher->addListener($eventName2, $callback2);

                    $row = $this->existStrict();

                    $this->dispatcher->removeListener($eventName, $callback);
                    $this->dispatcher->removeListener($eventName2, $callback2);

                    if ($row) {
                        $archiveItems = $this->archiveItems;

                        $this->_setArchiveItems($request, $response, $args);

                        if (\count($this->archiveItems) > \count($archiveItems)) {
                            $foundIds[] = $bulkId;
                        }
                    }
                }

                if (!empty($this->cache->taggable)) {
                    $tags = [
                        'local-'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'],
                        ...array_map(fn ($item) => 'local-'.$this->modName.'-'.$item, $foundIds),
                        'global-1',
                    ];

                    $cacheItem->tag($tags);
                }

                if ($oldLangId !== $this->lang->id) {
                    $this->translator->prepare($oldLangId);
                    $this->container->set('lang', $this->translator);
                }

                if (!empty($this->archiveItems)) {
                    $fileName = 'archive-'.implode('-', $foundIds).'.zip';
                    $src = \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/tmp/'.$fileName);

                    if (false !== $this->helper->File()->archive($this->archiveItems, $src)) {
                        $random = $this->helper->Nette()->Random()->generate();
                        $dest = _PUBLIC.'/symlink/'.$random.'/'.$fileName;

                        $this->helper->Nette()->FileSystem()->createDir(\dirname($dest));

                        if (false !== $this->helper->File()->symlink($src, $dest)) {
                            $this->logger->debug(\sprintf(__('Download %1$s #%2$s', $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->singularNameWithParams), implode(', #', $foundIds)));

                            $redirect = $this->helper->Url()->getBaseUrl().'/symlink/'.$random.'/'.$fileName;

                            $cacheItem->expiresAfter($this->helper->CarbonInterval()->createFromDateString('12 hours'));
                        } else {
                            $this->errors[] = __('A technical problem has occurred, try again later.');
                        }
                    } else {
                        $this->errors[] = __('A technical problem has occurred, try again later.');
                    }
                } else {
                    $this->errors[] = __('No results found.');
                }

                return $redirect ?? false;
            }))) {
                $uniqid = uniqid();

                $this->session->addFlash([
                    'type' => 'alert',
                    'options' => [
                        'type' => 'info',
                        'message' => [
                            __('Download in progress').'&hellip;',
                            \sprintf(__('%1$s if the download doesn\'t work.'), '<a class="alert-link"'.$this->viewHelper->escapeAttr([
                                'href' => $redirect,
                            ]).'>'.$this->helper->Nette()->Strings()->firstUpper(__('click here')).'</a>'),
                        ],
                        'attr' => [
                            'id' => $uniqid,
                        ],
                        // https://stackoverflow.com/a/77215205/3929620
                        // https://stackoverflow.com/a/12539054/3929620
                        // https://stackoverflow.com/a/11804706/3929620
                        'scriptsFoot' => '(() => {
    window.addEventListener("load", (event) => {
        window.location.href = "'.$this->viewHelper->escape()->js($redirect).'";

        if( typeof Alert !== \'undefined\' ) {
            const alert = Alert.getOrCreateInstance("#'.$uniqid.'");
            if (alert) {
                alert.close();
            }
        }
    });
})();',
                    ],
                ]);
            } else {
                $this->errors[] = __('No results found.');
            }
        }

        $this->action = $oldAction;
    }

    protected function _setArchiveItems(RequestInterface $request, ResponseInterface $response, $args): void
    {
        if ($this->container->has('Mod\Catform\\'.ucfirst(static::$env)) && $this->container->has('Mod\Form\\'.ucfirst(static::$env))) {
            $ModCatform = $this->container->get('Mod\Catform\\'.ucfirst(static::$env));
            $ModForm = $this->container->get('Mod\Form\\'.ucfirst(static::$env));

            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getOne.select';
            $callback = function (GenericEvent $event) use ($ModCatform, $ModForm): void {
                $this->dbData['sql'] .= ', g.'.$ModCatform->modName.'_id';
                $this->dbData['sql'] .= ', GROUP_CONCAT(DISTINCT g.'.$ModForm->modName.'_id) AS '.$ModForm->modName.'_ids';
            };

            $eventName2 = 'event.'.static::$env.'.'.$this->modName.'.getOne.join';
            $callback2 = function (GenericEvent $event) use ($ModForm): void {
                $this->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].$ModForm->modName.'value AS g
        ON a.id = g.'.$this->modName.'_id';
            };

            $this->dispatcher->addListener($eventName, $callback);
            $this->dispatcher->addListener($eventName2, $callback2);

            $this->setFields();

            $this->dispatcher->removeListener($eventName, $callback);
            $this->dispatcher->removeListener($eventName2, $callback2);

            if (!empty($this->{$ModCatform->modName.'_id'}) && !empty($this->{$ModForm->modName.'_ids'})) {
                if (!$this->container->has($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'FormResult')) {
                    $eventName = 'event.'.static::$env.'.'.$ModForm->modName.'.getAll.select';
                    $callback = function (GenericEvent $event) use ($ModForm): void {
                        $ModForm->dbData['sql'] .= ', b.subname';
                    };

                    $eventName2 = 'event.'.static::$env.'.'.$ModForm->modName.'.getAll.where';
                    $callback2 = function (GenericEvent $event) use ($ModCatform, $ModForm): void {
                        $ModForm->dbData['sql'] .= ' AND a.'.$ModCatform->modName.'_id = :'.$ModCatform->modName.'_id';
                        $ModForm->dbData['args'][$ModCatform->modName.'_id'] = $this->{$ModCatform->modName.'_id'};
                    };

                    $this->dispatcher->addListener($eventName, $callback);
                    $this->dispatcher->addListener($eventName2, $callback2);

                    $this->container->set($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'FormResult', $ModForm->getAll([
                        'order' => 'a.hierarchy DESC',
                        'active' => true,
                    ]));

                    $this->dispatcher->removeListener($eventName, $callback);
                    $this->dispatcher->removeListener($eventName2, $callback2);
                }

                if ((is_countable($this->container->get($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'FormResult')) ? \count($this->container->get($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'FormResult')) : 0) > 0) {
                    ${$ModCatform->modName.'Code'} = $this->container->get($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'FormResult')[0][$ModCatform->modName.'_code'];

                    if (!$this->container->has($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'LastFormId')) {
                        $this->container->set($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'LastFormId', $this->container->get($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'FormResult')[0]['id']);
                    }

                    if ($this->container->has('Mod\\'.ucfirst((string) $ModForm->modName).'field\\'.ucfirst(static::$env))) {
                        $ModFormfield = $this->container->get('Mod\\'.ucfirst((string) $ModForm->modName).'field\\'.ucfirst(static::$env));

                        $oldViewLayoutRegistryPaths = $this->viewLayoutRegistryPaths;
                        $oldViewRegistryPaths = $this->viewRegistryPaths;
                        $oldViewLayout = $this->viewLayout;
                        $oldController = $this->controller;
                        $oldAction = $this->action;
                        $oldViewData = $this->viewData;

                        $env = 'front';

                        $this->controller = $ModForm->modName;
                        $this->action = 'print';

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

                        $this->viewLayout = 'print';

                        $nodes = [];

                        $resultForm = $this->helper->Arrays()->usortBy($this->container->get($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'FormResult'), 'hierarchy');
                        foreach ($resultForm as $n => $rowForm) {
                            $ModForm->setId($rowForm['id']);

                            $ModFormfield->controller = $this->controller;
                            $ModFormfield->action = $this->action;

                            if (method_exists($ModFormfield, '_getResult') && \is_callable([$ModFormfield, '_getResult'])) {
                                $eventName = 'event.'.static::$env.'.'.$ModFormfield->modName.'.getAll.select';
                                $callback = function (GenericEvent $event) use ($ModForm, $ModFormfield): void {
                                    $ModFormfield->dbData['sql'] .= ', a.option, b.option_lang, b.richtext';
                                    $ModFormfield->dbData['sql'] .= ', g.id AS '.$ModForm->modName.'value_id';
                                    $ModFormfield->dbData['sql'] .= ', g.data AS '.$ModForm->modName.'value_data';
                                };

                                $eventName2 = 'event.'.static::$env.'.'.$ModFormfield->modName.'.getAll.join';
                                $callback2 = function (GenericEvent $event) use ($ModForm, $ModFormfield): void {
                                    $ModFormfield->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].$ModForm->modName.'value AS g
                                        ON a.id = g.'.$ModFormfield->modName.'_id
                                        AND g.'.$this->modName.'_id = :'.$this->modName.'_id';

                                    $ModFormfield->dbData['args'][$this->modName.'_id'] = $this->id;
                                };

                                $this->dispatcher->addListener($eventName, $callback);
                                $this->dispatcher->addListener($eventName2, $callback2);

                                ${$ModFormfield->modName.'Result'} = \call_user_func_array([$ModFormfield, '_getResult'], [$request, $response, $args]);

                                $this->dispatcher->removeListener($eventName, $callback);
                                $this->dispatcher->removeListener($eventName2, $callback2);

                                if (!empty(${$ModFormfield->modName.'Result'})) {
                                    foreach (${$ModFormfield->modName.'Result'} as $row) {
                                        if (\in_array($row['type'], ['input_file_multiple'], true)) {
                                            $dir = _ROOT.'/var/upload/'.$ModCatform->modName.'-'.$this->{$ModCatform->modName.'_id'}.'/'.$ModFormfield->modName.'-'.$row['id'].'/'.$this->modName.'-'.$this->id;

                                            if (is_dir($dir)) {
                                                $this->archiveItems[] = [
                                                    $dir => ${$ModCatform->modName.'Code'}.'/ID'.$this->id.'/F'.$row['id'],
                                                ];
                                            }
                                        } elseif (\in_array($row['type'], ['recommendation'], true)) {
                                            $data = $row[$ModForm->controller.'value_data'] ?? null;
                                            if (!empty($data['teachers'])) {
                                                foreach ($data['teachers'] as $key => $val) {
                                                    if (!empty($val['files'])) {
                                                        foreach ($val['files'] as $crc32 => $item) {
                                                            if (!empty($item['path'])) {
                                                                if (file_exists($item['path'])) {
                                                                    $this->archiveItems[] = [
                                                                        $item['path'] => ${$ModCatform->modName.'Code'}.'/ID'.$this->id.'/F'.$row['id'].'/ID'.$val['id'],
                                                                    ];
                                                                }
                                                            }
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }

                                if ($this->container->has($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'LastFormId')) {
                                    if (\in_array($this->container->get($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'LastFormId'), $this->{$ModForm->modName.'_ids'}, true) && !empty($rowForm['printable'])) {
                                        $prefix = ++$n.' - ';

                                        $this->viewData = array_merge(
                                            $oldViewData,
                                            $ModFormfield->viewData,
                                            [
                                                'env' => $env,
                                                'title' => $prefix.$rowForm['name'],
                                                'subTitle' => $rowForm['subname'],
                                                'metaTitle' => $prefix.$rowForm['name'],
                                            ]
                                        );

                                        $DOMDocument = new \DOMDocument();
                                        $DOMDocument->loadHTML($this->renderBody($request, $response, $args), LIBXML_NOERROR);

                                        // https://stackoverflow.com/a/34037291/3929620
                                        $scriptList = $DOMDocument->getElementsByTagName('script');
                                        if (!empty($scriptList->length)) {
                                            for ($i = $scriptList->length; --$i >= 0;) {
                                                $scriptEl = $scriptList->item($i);
                                                $scriptEl->parentNode->removeChild($scriptEl);
                                            }
                                        }

                                        $mainList = $DOMDocument->getElementsByTagName('main');
                                        if (!empty($mainList->length)) {
                                            $mainEl = $mainList->item(0);
                                            $nodes[] = $mainEl;
                                        }

                                        // FIXED - set new view instance
                                        $this->container->set('view', $this->container->make('view'));
                                    }
                                }
                            } else {
                                throw new HttpNotFoundException($request);
                            }
                        }

                        if (!empty($nodes)) {
                            $headerNodes = [];

                            // ---------------------- catform
                            if (!$this->container->has($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'ViewData')) {
                                $ModCatform->setId($this->{$ModCatform->modName.'_id'});

                                $ModCatform->setFields();

                                $this->container->set($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'ViewData', [
                                    'env' => $env,
                                    'title' => $ModCatform->name,
                                    'subTitle' => $ModCatform->subname,
                                    'metaTitle' => $ModCatform->name,
                                    'Mod' => $ModCatform,
                                ]);
                            }

                            $this->controller = $ModCatform->modName;

                            array_unshift(
                                $this->viewRegistryPaths,
                                _ROOT.'/app/view/'.$env.'/controller/'.$this->controller
                            );

                            $this->viewData = array_merge(
                                $oldViewData,
                                $this->container->get($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'ViewData'),
                                [
                                    'memberRow' => [
                                        'id' => $this->id,
                                        'firstname' => $this->firstname,
                                        'lastname' => $this->lastname,
                                        'email' => $this->email,
                                    ],
                                ]
                            );

                            $DOMDocument = new \DOMDocument();
                            $DOMDocument->loadHTML($this->renderBody($request, $response, $args), LIBXML_NOERROR);

                            // https://stackoverflow.com/a/34037291/3929620
                            $scriptList = $DOMDocument->getElementsByTagName('script');
                            if (!empty($scriptList->length)) {
                                for ($i = $scriptList->length; --$i >= 0;) {
                                    $scriptEl = $scriptList->item($i);
                                    $scriptEl->parentNode->removeChild($scriptEl);
                                }
                            }

                            $mainList = $DOMDocument->getElementsByTagName('main');
                            if (!empty($mainList->length)) {
                                $mainEl = $mainList->item(0);
                                $headerNodes[] = $mainEl;

                                $mainEl->parentNode->removeChild($mainEl);
                            }

                            // FIXED - set new view instance
                            $this->container->set('view', $this->container->make('view'));

                            // ---------------------- page
                            if ($this->container->has('Mod\Page\\'.ucfirst(static::$env))) {
                                $ModPage = $this->container->get('Mod\Page\\'.ucfirst(static::$env));

                                if (!$this->container->has($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'PageResult')) {
                                    $eventName = 'event.'.static::$env.'.'.$ModPage->modName.'.getAll.select';
                                    $callback = function (GenericEvent $event) use ($ModPage): void {
                                        $ModPage->dbData['sql'] .= ', b.subname';
                                    };

                                    $eventName2 = 'event.'.static::$env.'.'.$ModPage->modName.'.getAll.having';
                                    $callback2 = function (GenericEvent $event) use ($ModPage, $ModCatform): void {
                                        $ModPage->dbData['sql'] .= \Safe\preg_match('/\sHAVING\s/', (string) $ModPage->dbData['sql']) ? ' AND' : ' HAVING';

                                        // https://stackoverflow.com/a/54688059/3929620
                                        // https://stackoverflow.com/a/54690032/3929620
                                        // https://stackoverflow.com/a/37849547/3929620
                                        $ModPage->dbData['sql'] .= ' FIND_IN_SET(:catform_id, catform_ids) ';
                                        $ModPage->dbData['args']['catform_id'] = (int) $this->{$ModCatform->modName.'_id'};

                                        $ModPage->dbData['sql'] .= ' AND FIND_IN_SET(:menu_id, menu_ids) ';
                                        $ModPage->dbData['args']['menu_id'] = 3;
                                    };

                                    $this->dispatcher->addListener($eventName, $callback);
                                    $this->dispatcher->addListener($eventName2, $callback2);

                                    $this->container->set($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'PageResult', $ModPage->getAll([
                                        'order' => 'a.hierarchy ASC',
                                        'active' => true,
                                    ]));

                                    $this->dispatcher->removeListener($eventName, $callback);
                                    $this->dispatcher->removeListener($eventName2, $callback2);
                                }

                                if (!empty($this->container->get($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'PageResult'))) {
                                    $this->controller = $ModPage->modName;

                                    array_unshift(
                                        $this->viewRegistryPaths,
                                        _ROOT.'/app/view/'.$env.'/controller/'.$this->controller
                                    );

                                    foreach ($this->container->get($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'PageResult') as $rowPage) {
                                        if (!$this->container->has($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'PageId'.$rowPage['id'].'ViewData')) {
                                            $ModPage->setId($rowPage['id']);

                                            $ModPage->setFields();

                                            $this->container->set($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'PageId'.$rowPage['id'].'ViewData', [
                                                'env' => $env,
                                                'title' => $ModPage->name,
                                                'subTitle' => $ModPage->subname,
                                                'metaTitle' => $ModPage->name,
                                                'Mod' => $ModPage,
                                            ]);
                                        }

                                        $this->viewData = array_merge(
                                            $oldViewData,
                                            $this->container->get($ModCatform->modName.'Id'.$this->{$ModCatform->modName.'_id'}.'PageId'.$rowPage['id'].'ViewData')
                                        );

                                        $DOMDocument = new \DOMDocument();
                                        $DOMDocument->loadHTML($this->renderBody($request, $response, $args), LIBXML_NOERROR);

                                        // https://stackoverflow.com/a/34037291/3929620
                                        $scriptList = $DOMDocument->getElementsByTagName('script');
                                        if (!empty($scriptList->length)) {
                                            for ($i = $scriptList->length; --$i >= 0;) {
                                                $scriptEl = $scriptList->item($i);
                                                $scriptEl->parentNode->removeChild($scriptEl);
                                            }
                                        }

                                        $mainList = $DOMDocument->getElementsByTagName('main');
                                        if (!empty($mainList->length)) {
                                            $mainEl = $mainList->item(0);
                                            $headerNodes[] = $mainEl;
                                        }

                                        // FIXED - set new view instance
                                        $this->container->set('view', $this->container->make('view'));
                                    }
                                }
                            }

                            // ---------------------- form
                            $bodyList = $DOMDocument->getElementsByTagName('body');

                            if (!empty($bodyList->length)) {
                                $bodyEl = $bodyList->item(0);

                                $hrEl = $DOMDocument->createElement('hr');

                                // https://stackoverflow.com/a/4401089/3929620
                                foreach ($headerNodes as $node) {
                                    $node = $bodyEl->ownerDocument->importNode($node, true);
                                    $bodyEl->appendChild($node);

                                    // https://stackoverflow.com/a/19056035/3929620
                                    $bodyEl->appendChild(clone $hrEl);
                                }

                                // https://stackoverflow.com/a/4401089/3929620
                                foreach ($nodes as $node) {
                                    $node = $bodyEl->ownerDocument->importNode($node, true);
                                    $bodyEl->appendChild($node);

                                    // https://stackoverflow.com/a/19056035/3929620
                                    $bodyEl->appendChild(clone $hrEl);
                                }

                                $html = $DOMDocument->saveHTML();

                                if (empty($this->config['debug.enabled'])) {
                                    $html = new HtmlMin()->minify($html);
                                }

                                $this->archiveItems[] = [
                                    ${$ModCatform->modName.'Code'}.'/ID'.$this->id.'/'.$ModForm->modName.'value-'.${$ModCatform->modName.'Code'}.'-'.$this->id.'.html' => $html,
                                ];
                            }
                        }

                        $this->viewLayoutRegistryPaths = $oldViewLayoutRegistryPaths;
                        $this->viewRegistryPaths = $oldViewRegistryPaths;
                        $this->viewLayout = $oldViewLayout;
                        $this->controller = $oldController;
                        $this->action = $oldAction;
                        $this->viewData = $oldViewData;
                    }
                }
            }
        }
    }
}
