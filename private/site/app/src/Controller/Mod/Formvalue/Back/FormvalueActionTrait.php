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

namespace App\Controller\Mod\Formvalue\Back;

use App\Factory\Html\ViewHelperInterface;
use PhpOffice\PhpSpreadsheet\Helper\Html;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RedBeanPHP\Facade as R;
use Slim\Exception\HttpNotFoundException;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\Cache\ItemInterface;

trait FormvalueActionTrait
{
    public function actionReset(RequestInterface $request, ResponseInterface $response, $args)
    {
        $this->title = $this->metaTitle = \sprintf(__('Reset %1$s', $this->context), $this->helper->Nette()->Strings()->lower($this->pluralName));

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

        if ($this->rbac->isGranted($this->modName.'.'.static::$env.'.index')) {
            $this->breadcrumb->add(
                $this->helper->Nette()->Strings()->truncate(
                    \sprintf(__('List %1$s'), $this->helper->Nette()->Strings()->lower($this->pluralName)),
                    $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
                ),
                $this->helper->Url()->urlFor([
                    'routeName' => static::$env.'.'.$this->controller.'.params',
                    'data' => [
                        'action' => 'index',
                        'params' => implode(
                            '/',
                            array_merge(
                                $this->session->get('routeParamsArrWithoutPg', []),
                                [$this->session->get('pg', $this->pager->pg)]
                            )
                        ),
                    ],
                ])
            );
        }

        $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.breadcrumb');

        if (method_exists($this, '_'.__FUNCTION__) && \is_callable([$this, '_'.__FUNCTION__])) {
            $return = \call_user_func_array([$this, '_'.__FUNCTION__], [$request, $response, $args]);

            if (null !== $return) {
                return $return;
            }
        }

        $this->breadcrumb->add(
            $this->helper->Nette()->Strings()->truncate(
                $this->title,
                $this->config['breadcrumb.'.static::$env.'.textTruncate'] ?? $this->config['breadcrumb.textTruncate']
            ),
            $this->helper->Url()->urlFor($this->routeArgs)
        );

        if ('POST' === $request->getMethod()) {
            $this->check($request);

            if (0 === \count($this->errors)) {
                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.before');

                if ($this->container->has('Mod\Member\\'.ucfirst(static::$env)) && $this->container->has('Mod\Catform\\'.ucfirst(static::$env))) {
                    $ModMember = $this->container->get('Mod\Member\\'.ucfirst(static::$env));
                    $ModCatform = $this->container->get('Mod\Catform\\'.ucfirst(static::$env));

                    $this->helper->Nette()->FileSystem()->delete(_ROOT.'/var/upload/'.$ModCatform->modName.'-'.$this->postData[$ModCatform->modName.'_id']);

                    if (0 === \count($this->errors) && !empty($this->postData[$ModMember->modName.'_not_main_in'])) {
                        $eventName = 'event.'.static::$env.'.'.$ModMember->modName.'.getAll.where';
                        $callback = function (GenericEvent $event) use ($ModMember, $ModCatform): void {
                            $ModMember->dbData['sql'] .= ' AND c.main = :main';
                            $ModMember->dbData['sql'] .= ' AND g.'.$ModCatform->modName.'_id = :'.$ModCatform->modName.'_id';
                            $ModMember->dbData['args']['main'] = 0;
                            $ModMember->dbData['args'][$ModCatform->modName.'_id'] = $this->postData[$ModCatform->modName.'_id'];
                        };

                        $this->dispatcher->addListener($eventName, $callback);

                        ${$ModMember->modName.'Result'} = $ModMember->getAll([
                            'order' => 'a.id ASC',
                        ]);

                        $this->dispatcher->removeListener($eventName, $callback);

                        if ((is_countable(${$ModMember->modName.'Result'}) ? \count(${$ModMember->modName.'Result'}) : 0) > 0) {
                            // https://stackoverflow.com/a/49645329/3929620
                            $ids = array_column(${$ModMember->modName.'Result'}, 'id');

                            // Hunting Beans (5.1+) to find and delete beans in one go
                            // https://stackoverflow.com/a/3108650
                            // R::exec( 'DELETE FROM '.$this->config['db.1.prefix'].'log WHERE auth_type = ? AND auth_id IN ('.R::genSlots( $ids ).')', array_merge(['cat'.$ModMember->modName], $ids ));
                            R::hunt($this->config['db.1.prefix'].'log', ' auth_type = ? AND auth_id IN ('.R::genSlots($ids).') ', ['cat'.$ModMember->modName, ...$ids]);

                            R::hunt($this->config['db.1.prefix'].$ModMember->modName, ' id IN ('.R::genSlots($ids).') ', $ids);
                        }
                    }

                    if (0 === \count($this->errors)) {
                        R::exec(
                            'DELETE FROM '.$this->config['db.1.prefix'].$this->modName.' WHERE '.$ModCatform->modName.'_id = :'.$ModCatform->modName.'_id',
                            [
                                $ModCatform->modName.'_id' => $this->postData[$ModCatform->modName.'_id'],
                            ]
                        );
                    }

                    if (0 === \count($this->errors) && !empty($this->postData[$ModMember->modName.'_not_main_out'])) {
                        $eventName = 'event.'.static::$env.'.'.$ModMember->modName.'.getAll.where';
                        $callback = function (GenericEvent $event) use ($ModMember, $ModCatform): void {
                            $ModMember->dbData['sql'] .= ' AND c.main = :main';
                            $ModMember->dbData['sql'] .= ' AND g.'.$ModCatform->modName.'_id IS NULL';
                            $ModMember->dbData['args']['main'] = 0;
                        };

                        $this->dispatcher->addListener($eventName, $callback);

                        ${$ModMember->modName.'Result'} = $ModMember->getAll([
                            'order' => 'a.id ASC',
                        ]);

                        $this->dispatcher->removeListener($eventName, $callback);

                        if ((is_countable(${$ModMember->modName.'Result'}) ? \count(${$ModMember->modName.'Result'}) : 0) > 0) {
                            // https://stackoverflow.com/a/49645329/3929620
                            $ids = array_column(${$ModMember->modName.'Result'}, 'id');

                            // Hunting Beans (5.1+) to find and delete beans in one go
                            // https://stackoverflow.com/a/3108650
                            // R::exec( 'DELETE FROM '.$this->config['db.1.prefix'].'log WHERE auth_type = ? AND auth_id IN ('.R::genSlots( $ids ).')', array_merge(['cat'.$ModMember->modName], $ids ));
                            R::hunt($this->config['db.1.prefix'].'log', ' auth_type = ? AND auth_id IN ('.R::genSlots($ids).') ', ['cat'.$ModMember->modName, ...$ids]);

                            R::hunt($this->config['db.1.prefix'].$ModMember->modName, ' id IN ('.R::genSlots($ids).') ', $ids);
                        }
                    }

                    if (0 === \count($this->errors) && !empty($this->postData[$ModMember->modName.'_main'])) {
                        $eventName = 'event.'.static::$env.'.'.$ModMember->modName.'.getAll.where';
                        $callback = function (GenericEvent $event) use ($ModMember): void {
                            $ModMember->dbData['sql'] .= ' AND c.main = :main';
                            $ModMember->dbData['args']['main'] = 1;
                        };

                        $this->dispatcher->addListener($eventName, $callback);

                        ${$ModMember->modName.'Result'} = $ModMember->getAll([
                            'order' => 'a.id ASC',
                        ]);

                        $this->dispatcher->removeListener($eventName, $callback);

                        if ((is_countable(${$ModMember->modName.'Result'}) ? \count(${$ModMember->modName.'Result'}) : 0) > 0) {
                            // https://stackoverflow.com/a/49645329/3929620
                            $ids = array_column(${$ModMember->modName.'Result'}, 'id');

                            // Hunting Beans (5.1+) to find and delete beans in one go
                            // https://stackoverflow.com/a/3108650
                            // R::exec( 'DELETE FROM '.$this->config['db.1.prefix'].'log WHERE auth_type = ? AND auth_id IN ('.R::genSlots( $ids ).')', array_merge(['cat'.$ModMember->modName], $ids ));
                            R::hunt($this->config['db.1.prefix'].'log', ' auth_type = ? AND auth_id IN ('.R::genSlots($ids).') ', ['cat'.$ModMember->modName, ...$ids]);

                            R::hunt($this->config['db.1.prefix'].$ModMember->modName, ' id IN ('.R::genSlots($ids).') ', $ids);
                        }
                    }

                    if (0 === \count($this->errors)) {
                        if (empty($ModMember->getCount())) {
                            // https://stackoverflow.com/a/10727590
                            R::wipe($this->config['db.1.prefix'].$ModMember->modName);
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

                $this->dispatcher->dispatch(new GenericEvent(), 'event.'.static::$env.'.'.$this->modName.'.'.__FUNCTION__.'.after');

                if (0 === \count($this->errors)) {
                    $this->logger->notice(\sprintf(_nx('Reset %1$s of %2$s #%3$d', 'Reset %1$s of %2$s #%3$d', 2, $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->pluralNameWithParams), $this->helper->Nette()->Strings()->lower($ModCatform->singularNameWithParams), $this->postData[$ModCatform->modName.'_id']));

                    _n('Reset %1$s of %2$s #%3$d', 'Reset %1$s of %2$s #%3$d', 1, 'default');
                    _n('Reset %1$s of %2$s #%3$d', 'Reset %1$s of %2$s #%3$d', 1, 'male');
                    _n('Reset %1$s of %2$s #%3$d', 'Reset %1$s of %2$s #%3$d', 1, 'female');

                    $this->session->addFlash([
                        'type' => 'toast',
                        'options' => [
                            'type' => 'success',
                            'message' => \sprintf(_nx('%1$s successfully reset.', '%1$s successfully reset.', 2, $this->context), $this->pluralName),
                        ],
                    ]);

                    _n('%1$s successfully reset.', '%1$s successfully reset.', 1, 'default');
                    _n('%1$s successfully reset.', '%1$s successfully reset.', 1, 'male');
                    _n('%1$s successfully reset.', '%1$s successfully reset.', 1, 'female');

                    return $response
                        ->withHeader('Location', $this->helper->Url()->urlFor([
                            'routeName' => static::$env.'.'.$this->controller.'.params',
                            'data' => [
                                'action' => 'index',
                                'params' => implode(
                                    '/',
                                    array_merge(
                                        $this->session->get('routeParamsArrWithoutPg', []),
                                        [$this->session->get('pg', $this->pager->pg)]
                                    )
                                ),
                            ],
                        ]))
                        // https://stackoverflow.com/a/6788439/3929620
                        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Location
                        ->withStatus(303)
                    ;
                }
            }

            if (\count($this->errors) > 0) {
                $this->session->addFlash([
                    'type' => 'toast',
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

        $this->session->addFlash([
            'type' => 'alert',
            'options' => [
                'env' => static::$env, // <-
                'type' => 'warning',
                'message' => \sprintf(_x('Do you confirm the reset of all %1$s?', $this->context), $this->helper->Nette()->Strings()->lower($this->pluralName)),
                'dismissible' => false,
            ],
        ]);

        __('Do you confirm the reset of all %1$s?', 'default');
        __('Do you confirm the reset of all %1$s?', 'male');
        __('Do you confirm the reset of all %1$s?', 'female');
    }

    public function _actionPostExportCheckbox($row)
    {
        $labels = [];
        $options = explode(PHP_EOL, (string) $row['option_lang']['values']);

        if (!empty($options)) {
            // https://stackoverflow.com/a/3432266
            $options = array_map('trim', $options);
            foreach ($options as $k => $v) {
                if (str_contains($v, '|')) {
                    [$value, $label] = explode('|', $v);
                    if (ctype_digit((string) $value)) {
                        $value = (int) $value;
                    }
                } else {
                    $value = $k;
                    $label = $v;
                }

                if (\in_array($value, $row[$this->modName.'_data'] ?? [], true)) {
                    $labels[] = '- '.$label;
                }
            }
        }

        if (!empty($labels)) {
            // FIXED - PHP_EOL no working
            return implode("\n", $labels);
        }
    }

    public function _actionPostExportCountry($row)
    {
        if (!empty($rowCountry = $this->container->get('Mod\Country\\'.ucfirst((string) static::$env))->getOne([
            'id' => $row[$this->modName.'_data'],
            'active' => true,
        ]))) {
            return $rowCountry['name'];
        }
    }

    public function _actionPostExportInputFileMultiple($row)
    {
        $values = [];
        foreach ($row[$this->modName.'_data'] as $crc32 => $item) {
            $values[] = '- '.$item['name'].' ('.$this->helper->File()->formatSize($item['size']).')';
        }

        // FIXED - PHP_EOL no working
        return implode("\n", $values);
    }

    public function _actionPostExportRadio($row)
    {
        $options = explode(PHP_EOL, (string) $row['option_lang']['values']);

        if (!empty($options)) {
            // https://stackoverflow.com/a/3432266
            $options = array_map('trim', $options);
            foreach ($options as $k => $v) {
                if (str_contains($v, '|')) {
                    [$value, $label] = explode('|', $v);
                    if (ctype_digit((string) $value)) {
                        $value = (int) $value;
                    }
                } else {
                    $value = $k;
                    $label = $v;
                }

                if (\in_array($value, [$row[$this->modName.'_data'] ?? null], true)) {
                    return $label;
                }
            }
        }
    }

    public function _actionPostExportRecommendation($row)
    {
        $labels = [];
        $options = [
            1 => __('Yes'),
            0 => __('No'),
        ];

        if (!empty($options)) {
            foreach ($options as $k => $v) {
                $value = $k;
                $label = $v;

                if ($value === (isset($row[$this->modName.'_data']) ? (\is_array($row[$this->modName.'_data']) ? $row[$this->modName.'_data'][0] : $row[$this->modName.'_data']) : null)) {
                    $labels[] = $label;

                    break;
                }
            }
        }

        $data = $row[$this->modName.'_data'] ?? null;
        if (!empty($data['teachers'])) {
            foreach ($data['teachers'] as $key => $val) {
                $labels[] = '';
                $labels[] = '#'.$val['id'].' - '.$val['firstname'].' '.$val['lastname'].' ('.$val['email'].'):';

                if (!empty($val['files'])) {
                    foreach ($val['files'] as $crc32 => $item) {
                        $labels[] = '- '.$item['name'].' ('.$this->helper->File()->formatSize($item['size']).')';
                    }
                }
            }
        }

        if (!empty($labels)) {
            // FIXED - PHP_EOL no working
            return implode("\n", $labels);
        }
    }

    public function _actionPostExportSelect($row)
    {
        $options = explode(PHP_EOL, (string) $row['option_lang']['values']);

        if (!empty($options)) {
            // https://stackoverflow.com/a/3432266
            $options = array_map('trim', $options);
            foreach ($options as $k => $v) {
                if (str_contains($v, '|')) {
                    [$value, $label] = explode('|', $v);
                    if (ctype_digit((string) $value)) {
                        $value = (int) $value;
                    }
                } else {
                    $value = $k;
                    $label = $v;
                }

                if (\in_array($value, [$row[$this->modName.'_data'] ?? null], true)) {
                    return $label;
                }
            }
        }
    }

    protected function _actionPostExport(RequestInterface $request, ResponseInterface $response, $args): void
    {
        // FIXME - circular dependencies
        $this->viewHelper = $this->container->get(ViewHelperInterface::class);

        $options = [
            '__FUNCTION__' => __FUNCTION__,
        ];

        if (!empty($redirect = $this->cache->get($this->cache->getItemKey([
            $this->getShortName(),
            $options,
            __LINE__,
            $this->postData,
        ]), function (ItemInterface $cacheItem) use ($request, $response, $args, $options) {
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

            if ($this->container->has('Mod\Member\\'.ucfirst(static::$env)) && $this->container->has('Mod\Form\\'.ucfirst(static::$env))) {
                $ModMember = $this->container->get('Mod\Member\\'.ucfirst(static::$env));
                $ModForm = $this->container->get('Mod\Form\\'.ucfirst(static::$env));

                if (!empty($this->cache->taggable)) {
                    $tags = [
                        'local-'.$this->auth->getIdentity()['_type'].'-'.$this->auth->getIdentity()['id'],
                        'global-1',
                    ];

                    $cacheItem->tag($tags);
                }

                if ($this->container->has('Mod\\'.ucfirst((string) $ModForm->modName).'field\\'.ucfirst(static::$env))) {
                    $ModFormfield = $this->container->get('Mod\\'.ucfirst((string) $ModForm->modName).'field\\'.ucfirst(static::$env));

                    $eventName = 'event.'.static::$env.'.'.$ModMember->modName.'.getAll.where';
                    $callback = function (GenericEvent $event) use ($ModMember, $ModForm): void {
                        $ModMember->dbData['sql'] .= ' AND c.main = :main';
                        $ModMember->dbData['sql'] .= ' AND g.cat'.$ModForm->modName.'_id = :cat'.$ModForm->modName.'_id';
                        $ModMember->dbData['args']['main'] = 0;
                        $ModMember->dbData['args']['cat'.$ModForm->modName.'_id'] = $this->postData['cat'.$ModForm->modName.'_id'];
                    };

                    $this->dispatcher->addListener($eventName, $callback);

                    ${$ModMember->modName.'Result'} = $ModMember->getAll([
                        'order' => 'a.id ASC',
                        'active' => true,
                    ]);

                    $this->dispatcher->removeListener($eventName, $callback);

                    if ((is_countable(${$ModMember->modName.'Result'}) ? \count(${$ModMember->modName.'Result'}) : 0) > 0) {
                        array_walk(${$ModMember->modName.'Result'}, function (&$row) use ($ModForm): void {
                            $row[$ModForm->modName.'_ids'] = !empty($row[$ModForm->modName.'_ids']) ? explode(',', (string) $row[$ModForm->modName.'_ids']) : [];

                            $this->filterValue->sanitize($row[$ModForm->modName.'_ids'], 'intvalArray');
                        });

                        $eventName = 'event.'.$ModForm::$env.'.'.$ModForm->modName.'.getAll.select';
                        $callback = function (GenericEvent $event) use ($ModForm): void {
                            $ModForm->dbData['sql'] .= ', d.name AS cat'.$ModForm->modName.'_name';
                        };

                        $eventName2 = 'event.'.$ModForm::$env.'.'.$ModForm->modName.'.getAll.join';
                        $callback2 = function (GenericEvent $event) use ($ModForm): void {
                            $ModForm->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].'cat'.$ModForm->modName.'_lang AS d
                            ON a.cat'.$ModForm->modName.'_id = d.item_id';

                            // https://stackoverflow.com/a/20123337/3929620
                            // https://stackoverflow.com/a/22499259/3929620
                            // https://stackoverflow.com/a/40682033/3929620
                            foreach (['d'] as $letter) {
                                if (empty($this->db->getPDO()->getAttribute(\PDO::ATTR_EMULATE_PREPARES))) {
                                    $ModForm->dbData['sql'] .= ' AND '.$letter.'.lang_id = :'.$letter.'_lang_id';
                                    $ModForm->dbData['args'][$letter.'_lang_id'] = $this->lang->id;
                                } else {
                                    $ModForm->dbData['sql'] .= ' AND '.$letter.'.lang_id = :lang_id';
                                }
                            }

                            if (!empty($this->db->getPDO()->getAttribute(\PDO::ATTR_EMULATE_PREPARES))) {
                                $ModForm->dbData['args']['lang_id'] = $this->lang->id;
                            }
                        };

                        $eventName3 = 'event.'.$ModForm::$env.'.'.$ModForm->modName.'.getAll.where';
                        $callback3 = function (GenericEvent $event) use ($ModForm): void {
                            $ModForm->dbData['sql'] .= ' AND a.cat'.$ModForm->modName.'_id = :cat'.$ModForm->modName.'_id';
                            $ModForm->dbData['args']['cat'.$ModForm->modName.'_id'] = $this->postData['cat'.$ModForm->modName.'_id'];
                        };

                        $this->dispatcher->addListener($eventName, $callback);
                        $this->dispatcher->addListener($eventName2, $callback2);
                        $this->dispatcher->addListener($eventName3, $callback3);

                        ${$ModForm->modName.'Result'} = $ModForm->getAll([
                            'order' => 'a.hierarchy DESC',
                            'active' => true,
                        ]);

                        $this->dispatcher->removeListener($eventName, $callback);
                        $this->dispatcher->removeListener($eventName2, $callback2);
                        $this->dispatcher->removeListener($eventName3, $callback3);

                        if ((is_countable(${$ModForm->modName.'Result'}) ? \count(${$ModForm->modName.'Result'}) : 0) > 0) {
                            $Spreadsheet = new Spreadsheet();

                            // Maximum 31 characters allowed in sheet title
                            // https://stackoverflow.com/a/26187405/3929620
                            $title = $this->helper->Nette()->Strings()->truncate(${$ModForm->modName.'Result'}[0]['cat'.$ModForm->modName.'_code'].' - '.${$ModForm->modName.'Result'}[0]['cat'.$ModForm->modName.'_name'], 31, '');

                            $Spreadsheet->getProperties()->setCreator($this->config['credits.name'])
                                ->setLastModifiedBy($this->config['credits.name'])
                                ->setTitle($title)
                                ->setSubject($title)
                                ->setDescription($title)
                            ;

                            $maxWidth = 100;

                            $n = 1;
                            $resultForm = $this->helper->Arrays()->usortBy(${$ModForm->modName.'Result'}, 'hierarchy');
                            foreach ($resultForm as $rowForm) {
                                if (!empty($rowForm['printable'])) {
                                    $ModForm->setId($rowForm['id']);

                                    $ModFormfield->controller = $this->controller;
                                    $ModFormfield->action = $this->action;

                                    $rowCount = 1;
                                    $column = 'A';

                                    if ($n > 1) {
                                        $Spreadsheet->createSheet();
                                    }

                                    $sheetTitle = $this->helper->Nette()->Strings()->truncate(${$ModForm->modName.'Result'}[0]['cat'.$ModForm->modName.'_code'].' - '.$n.' - '.$rowForm['name'], 31, '');

                                    $Spreadsheet->setActiveSheetIndex($n - 1)
                                        ->setTitle($sheetTitle)
                                    ;

                                    $fields = [
                                        'id' => $ModMember->fields['id'][static::$env]['label'],
                                        'firstname' => $ModMember->fields['firstname'][static::$env]['label'],
                                        'lastname' => $ModMember->fields['lastname'][static::$env]['label'],
                                        'email' => $ModMember->fields['email'][static::$env]['label'],
                                    ];

                                    $eventName = 'event.'.$ModFormfield::$env.'.'.$ModFormfield->modName.'.getAll.where';
                                    $callback = function (GenericEvent $event) use ($ModFormfield, $ModForm): void {
                                        $ModFormfield->dbData['sql'] .= ' AND a.'.$ModForm->modName.'_id = :'.$ModForm->modName.'_id';
                                        $ModFormfield->dbData['args'][$ModForm->modName.'_id'] = $ModForm->id;
                                    };

                                    $this->dispatcher->addListener($eventName, $callback);

                                    ${$ModFormfield->modName.'Result'} = $ModFormfield->getAll([
                                        'order' => 'a.hierarchy ASC',
                                        'active' => true,
                                    ]);

                                    $this->dispatcher->removeListener($eventName, $callback);

                                    if ((is_countable(${$ModFormfield->modName.'Result'}) ? \count(${$ModFormfield->modName.'Result'}) : 0) > 0) {
                                        foreach (${$ModFormfield->modName.'Result'} as $rowFormfield) {
                                            if (!\array_key_exists($rowFormfield['type'], $ModFormfield->getFieldTypes('block'))) {
                                                $fields[$rowFormfield['id']] = trim($rowFormfield['name'].' ('.($ModFormfield->getFieldTypes()[$rowFormfield['type']] ?? $rowFormfield['type']).')');
                                            }
                                        }
                                    }

                                    $Spreadsheet->getActiveSheet()->fromArray(array_values($fields), null, $column.$rowCount);
                                    $Spreadsheet->getActiveSheet()->getStyle('1:1')->getFont()->setBold(true);

                                    ++$rowCount;

                                    foreach (${$ModMember->modName.'Result'} as $rowMember) {
                                        $column = 'A';
                                        $columnCount = 1;

                                        foreach (\array_slice($fields, 0, 4) as $fieldKey => $fieldVal) {
                                            $Spreadsheet->getActiveSheet()->setCellValue([$columnCount, $rowCount], $rowMember[$fieldKey]);
                                            $Spreadsheet->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);

                                            ++$column;
                                            ++$columnCount;
                                        }

                                        if (method_exists($ModFormfield, '_getResult') && \is_callable([$ModFormfield, '_getResult'])) {
                                            $eventName = 'event.'.static::$env.'.'.$ModFormfield->modName.'.getAll.select';
                                            $callback = function (GenericEvent $event) use ($ModFormfield): void {
                                                $ModFormfield->dbData['sql'] .= ', a.option, b.option_lang';
                                                $ModFormfield->dbData['sql'] .= ', g.data AS '.$this->modName.'_data';
                                            };

                                            $eventName2 = 'event.'.static::$env.'.'.$ModFormfield->modName.'.getAll.join';
                                            $callback2 = function (GenericEvent $event) use ($ModFormfield, $ModMember, $rowMember): void {
                                                $ModFormfield->dbData['sql'] .= ' LEFT JOIN '.$this->config['db.1.prefix'].$this->modName.' AS g
                                                    ON a.id = g.'.$ModFormfield->modName.'_id
                                                    AND g.'.$ModMember->modName.'_id = :'.$ModMember->modName.'_id';

                                                $ModFormfield->dbData['args'][$ModMember->modName.'_id'] = $rowMember['id'];
                                            };

                                            $this->dispatcher->addListener($eventName, $callback);
                                            $this->dispatcher->addListener($eventName2, $callback2);

                                            ${$ModFormfield->modName.'Result'} = \call_user_func_array([$ModFormfield, '_getResult'], [$request, $response, $args]);

                                            $this->dispatcher->removeListener($eventName, $callback);
                                            $this->dispatcher->removeListener($eventName2, $callback2);

                                            if (!empty(${$ModFormfield->modName.'Result'})) {
                                                foreach (${$ModFormfield->modName.'Result'} as $rowFormfield) {
                                                    if (!\array_key_exists($rowFormfield['type'], $ModFormfield->getFieldTypes('block'))) {
                                                        $value = '';

                                                        if (isset($rowFormfield[$this->modName.'_data']) && !isBlank($rowFormfield[$this->modName.'_data'])) {
                                                            $filteredKey = $rowFormfield['type'];

                                                            $this->filterValue->sanitize($filteredKey, 'string', ['_', '-'], ' ');
                                                            $this->filterValue->sanitize($filteredKey, 'titlecase');
                                                            $this->filterValue->sanitize($filteredKey, 'string', ' ', '');

                                                            if (method_exists($this, $options['__FUNCTION__'].$filteredKey) && \is_callable([$this, $options['__FUNCTION__'].$filteredKey])) {
                                                                $value = \call_user_func_array([$this, $options['__FUNCTION__'].$filteredKey], [$rowFormfield]);
                                                            } elseif (!\is_array($rowFormfield[$this->modName.'_data'])) {
                                                                $value = $rowFormfield[$this->modName.'_data'];
                                                            }
                                                        }

                                                        $Spreadsheet->getActiveSheet()->setCellValue([$columnCount, $rowCount], $value);
                                                        $Spreadsheet->getActiveSheet()->getColumnDimension($column)->setAutoSize(true);

                                                        ++$column;
                                                        ++$columnCount;
                                                    }
                                                }
                                            }
                                        } else {
                                            throw new HttpNotFoundException($request);
                                        }

                                        $color1Obj = $this->helper->Color()->color('white');
                                        $color2Obj = \in_array(${$ModForm->modName.'Result'}[0]['id'], $rowMember[$ModForm->modName.'_ids'], true) ? $this->helper->Color()->color('#'.$this->config['theme.color.success']) : $this->helper->Color()->color('#'.$this->config['theme.color.warning']);
                                        $colorObj = $this->helper->Color()->mix($color1Obj, $color2Obj, 80);

                                        $Spreadsheet->getActiveSheet()
                                            ->getStyle('A'.$rowCount.':'.$this->helper->Strings()->decrementString($column).$rowCount)
                                            ->applyFromArray([
                                                'fill' => [
                                                    'fillType' => Fill::FILL_SOLID,
                                                    'color' => [
                                                        'rgb' => ltrim($colorObj->toHexString(), '#'),
                                                    ],
                                                ],
                                                'borders' => [
                                                    'allBorders' => [
                                                        'borderStyle' => Border::BORDER_THIN,
                                                    ],
                                                ],
                                                'alignment' => [
                                                    'wrapText' => true,
                                                    'vertical' => Alignment::VERTICAL_TOP,
                                                ],
                                            ])
                                        ;

                                        $Spreadsheet->getActiveSheet()->getRowDimension($rowCount)->setRowHeight(50);

                                        ++$rowCount;
                                    }

                                    ++$n;
                                }
                            }

                            // https://github.com/PHPOffice/PhpSpreadsheet/issues/275
                            foreach ($Spreadsheet->getAllSheets() as $sheet) {
                                $sheet->calculateColumnWidths();
                                foreach ($sheet->getColumnDimensions() as $colDim) {
                                    if (!$colDim->getAutoSize()) {
                                        continue;
                                    }
                                    $colWidth = $colDim->getWidth();
                                    if ($colWidth > $maxWidth) {
                                        $colDim->setAutoSize(false);
                                        $colDim->setWidth($maxWidth);
                                    }
                                }
                            }

                            $Spreadsheet->setActiveSheetIndex(0);

                            $fileName = $this->helper->Nette()->Strings()->webalize($title).'-'.$this->helper->Carbon()->now(date_default_timezone_get())->toDateString().'.xlsx';
                            $src = \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/tmp/'.$fileName);

                            $writer = IOFactory::createWriter($Spreadsheet, 'Xlsx');
                            $writer->save($src);

                            $random = $this->helper->Nette()->Random()->generate();
                            $dest = _PUBLIC.'/symlink/'.$random.'/'.$fileName;

                            $this->helper->Nette()->FileSystem()->createDir(\dirname($dest));

                            if (false !== $this->helper->File()->symlink($src, $dest)) {
                                $this->logger->info(\sprintf(_nx('Exported %1$s', 'Exported %1$s', 2, $this->context, $this->config['logger.locale']), $this->helper->Nette()->Strings()->lower($this->pluralNameWithParams)));

                                _n('Exported %1$s', 'Exported %1$s', 1, 'default');
                                _n('Exported %1$s', 'Exported %1$s', 1, 'male');
                                _n('Exported %1$s', 'Exported %1$s', 1, 'female');

                                $redirect = $this->helper->Url()->getBaseUrl().'/symlink/'.$random.'/'.$fileName;

                                $cacheItem->expiresAfter($this->helper->CarbonInterval()->createFromDateString('12 hours'));
                            } else {
                                if ($oldLangId !== $this->lang->id) {
                                    $this->translator->prepare($oldLangId);
                                    $this->container->set('lang', $this->translator);
                                }

                                $this->errors[] = __('A technical problem has occurred, try again later.');
                            }
                        } else {
                            if ($oldLangId !== $this->lang->id) {
                                $this->translator->prepare($oldLangId);
                                $this->container->set('lang', $this->translator);
                            }

                            $this->errors[] = __('No results found.');
                        }
                    } else {
                        if ($oldLangId !== $this->lang->id) {
                            $this->translator->prepare($oldLangId);
                            $this->container->set('lang', $this->translator);
                        }

                        $this->errors[] = __('No results found.');
                    }
                }
            }

            if ($oldLangId !== $this->lang->id) {
                $this->translator->prepare($oldLangId);
                $this->container->set('lang', $this->translator);
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

    /*public function _actionPostExportTextarea($row)
    {
        return (new Html())->toRichTextObject($row[$this->modName.'_data']);
    }*/
}
