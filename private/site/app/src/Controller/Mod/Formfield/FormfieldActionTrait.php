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

namespace App\Controller\Mod\Formfield;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

trait FormfieldActionTrait
{
    public function _getResult(RequestInterface $request, ResponseInterface $response, $args)
    {
        if (!empty($this->container->get('Mod\Form\\'.ucfirst(static::$env))->id)) {
            $eventName = 'event.'.static::$env.'.'.$this->modName.'.getAll.where';
            $callback = function (GenericEvent $event): void {
                $this->dbData['sql'] .= ' AND a.form_id = :form_id';
                $this->dbData['args']['form_id'] = $this->container->get('Mod\Form\\'.ucfirst(static::$env))->id;
            };

            $this->dispatcher->addListener($eventName, $callback);

            ${$this->modName.'Result'} = $this->getAll(
                [
                    'order' => 'a.hierarchy ASC',
                    'active' => true,
                ]
            );

            $this->dispatcher->removeListener($eventName, $callback);

            if (!empty(${$this->modName.'Result'})) {
                array_walk(${$this->modName.'Result'}, function (&$row): void {
                    if (!empty($row['option'])) {
                        $row['option'] = $this->helper->Nette()->Json()->decode((string) $row['option'], forceArrays: true);
                    }
                    if (!empty($row['option_lang'])) {
                        $row['option_lang'] = $this->helper->Nette()->Json()->decode((string) $row['option_lang'], forceArrays: true);
                    }
                    if (isset($row['formvalue_data']) && !isBlank($row['formvalue_data'])) { // <--
                        // https://stackoverflow.com/a/4100765/3929620
                        // https://stackoverflow.com/a/35180513/3929620
                        if (\in_array($row['type'], ['checkbox', 'input_file_multiple'], true)) {
                            $row['formvalue_data'] = $this->helper->Nette()->Json()->decode((string) $row['formvalue_data'], forceArrays: true);
                            $row['formvalue_data'] = $this->helper->Arrays()->arrayMapRecursive(fn ($item) => ctype_digit((string) $item) ? (int) $item : $item, $row['formvalue_data']);
                        } elseif (\in_array($row['type'], ['recommendation'], true)) {
                            $row['formvalue_data'] = ctype_digit((string) $row['formvalue_data']) ? (int) $row['formvalue_data'] : $this->helper->Nette()->Json()->decode((string) $row['formvalue_data'], forceArrays: true);

                            $arrStatusAccepted = $arrStatusUploaded = [];
                            if (!empty($row['formvalue_data']['teachers'])) {
                                foreach ($row['formvalue_data']['teachers'] as $k => $v) {
                                    if (!empty($v['status'])) {
                                        $arrStatusAccepted[] = $v;
                                        unset($row['formvalue_data']['teachers'][$k]);
                                    } elseif (!empty($v['files'])) {
                                        $arrStatusUploaded[] = $v;
                                        unset($row['formvalue_data']['teachers'][$k]);
                                    }
                                }

                                if (!empty($arrStatusUploaded)) {
                                    $row['formvalue_data']['teachers'] = array_merge($arrStatusUploaded, $row['formvalue_data']['teachers']);
                                }

                                if (!empty($arrStatusAccepted)) {
                                    $row['formvalue_data']['teachers'] = array_merge($arrStatusAccepted, $row['formvalue_data']['teachers']);
                                }
                            }
                        } else {
                            $row['formvalue_data'] = ctype_digit((string) $row['formvalue_data']) ? (int) $row['formvalue_data'] : $row['formvalue_data'];
                        }
                    }
                });

                $this->viewData = array_merge(
                    $this->viewData,
                    compact( // https://stackoverflow.com/a/30266377/3929620
                        $this->modName.'Result'
                    )
                );
            }

            return ${$this->modName.'Result'};
        }
    }
}
