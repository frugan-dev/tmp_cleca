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

use App\Factory\Db\DbInterface;
use App\Factory\Translator\TranslatorInterface;
use App\Helper\HelperInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Slim\Factory\ServerRequestCreatorFactory;
use Symfony\Component\EventDispatcher\GenericEvent;

$dbFrom = 2;
$dbTo = 1;

$Mod = $container->get('Mod\Formfield\Cli');

$container->get(DbInterface::class)->selectDatabase($dbFrom);

$dbData = [];
$dbData['args'] = [];

$dbData['sql'] = 'SELECT a.*,
       b.name AS name_en, b.help_text AS help_text_en, b.option_lang AS option_lang_en,
       c.name AS name_it, c.help_text AS help_text_it, c.option_lang AS option_lang_it
FROM '.$container->get('config')['db.'.$dbFrom.'.prefix'].'formfield AS a
JOIN '.$container->get('config')['db.'.$dbFrom.'.prefix'].'formfield_lang AS b
ON a.id = b.item_id
JOIN '.$container->get('config')['db.'.$dbFrom.'.prefix'].'formfield_lang AS c
ON a.id = c.item_id
WHERE 1
AND b.lang_id = 1
AND c.lang_id = 2
ORDER BY a.id ASC';

$result = $container->get(DbInterface::class)->getAll($dbData['sql'], $dbData['args']);

$container->get(DbInterface::class)->selectDatabase($dbTo);

if (!empty($result)) {
    foreach ($result as $row) {
        // https://discourse.slimframework.com/t/using-slim-4-app-in-cli-mode/3669
        // https://github.com/slimphp/Slim/issues/2710#issuecomment-499267451
        $container->set(
            'request',
            ServerRequestCreatorFactory::create()
                ->createServerRequestFromGlobals()
                ->withMethod('POST')
                ->withHeader('CONTENT_TYPE', 'application/x-www-form-urlencoded')
        );

        $Mod->action = 'add';
        $Mod->errors = [];
        $Mod->postData = [];
        $filesData = [];

        // https://stackoverflow.com/a/5101554/3929620
        // $Mod->postData['id'] = (int) $row['id'];

        $Mod->postData['mdate'] = $row['mdate'];

        $type = $row['type'];
        $type = strtr($type, [
            'multilang.' => '',
            '.' => '_',
        ]);

        if ('recaptcha' === $type) {
            continue;
        }

        if (in_array($type, [
            'block_text_title',
            'block_textarea',
            'block_textarea_richedit_simple',
        ], true)) {
            $type = 'block_text';
        }

        $Mod->postData['type'] = $type;

        $Mod->postData['catform_id'] = (int) $row['catform_id'];
        $Mod->postData['form_id'] = (int) $row['form_id'];

        $Mod->postData['option'] = !empty($row['option']) ? $container->get(HelperInterface::class)->Nette()->Json()->decode((string) $row['option'], forceArrays: true) : null;

        $Mod->postData['hierarchy'] = ((int) $row['hierarchy'] * 10);
        $Mod->postData['required'] = (int) $row['required'];

        $Mod->postData['active'] = (int) $row['active'];

        foreach ($container->get(TranslatorInterface::class)->arr as $langId => $langRow) {
            $Mod->postData['multilang|'.$langId.'|name'] = !empty($row['name_'.$langRow['isoCode']]) ? $row['name_'.$langRow['isoCode']] : null;

            if (in_array($type, ['block_text'], true)) {
                $Mod->postData['multilang|'.$langId.'|richtext'] = !empty($row['option_lang_'.$langRow['isoCode']]) ? $row['option_lang_'.$langRow['isoCode']] : null;

                $Mod->postData['multilang|'.$langId.'|option_lang'] = null;
            } else {
                $Mod->postData['multilang|'.$langId.'|richtext'] = !empty($row['help_text_'.$langRow['isoCode']]) ? $row['help_text_'.$langRow['isoCode']] : null;

                if (!empty($row['option_lang_'.$langRow['isoCode']])) {
                    $Mod->postData['multilang|'.$langId.'|option_lang']['values'] = $row['option_lang_'.$langRow['isoCode']];
                } else {
                    $Mod->postData['multilang|'.$langId.'|option_lang'] = null;
                }
            }
        }

        if (!empty($Mod->postData['multilang|1|name']) && empty($Mod->postData['multilang|2|name'])) {
            $Mod->postData['multilang|2|name'] = $Mod->postData['multilang|1|name'];
        }

        if (!empty($Mod->postData['multilang|1|richtext']) && empty($Mod->postData['multilang|2|richtext'])) {
            $Mod->postData['multilang|2|richtext'] = $Mod->postData['multilang|1|richtext'];
        }

        $requestByRef = $container->get('request')
            ->withParsedBody($Mod->postData)
        ;

        $Mod->check($requestByRef);

        if (0 === count($Mod->errors)) {
            $container->get(EventDispatcherInterface::class)->dispatch(new GenericEvent(), 'event.'.$Mod::$env.'.'.$Mod->modName.'.actionAdd.before');

            $insertId = $Mod->dbAdd();

            $container->get(EventDispatcherInterface::class)->dispatch(new GenericEvent(arguments: [
                'id' => $insertId,
            ]), 'event.'.$Mod::$env.'.'.$Mod->modName.'.actionAdd.after');

            $container->get(DbInterface::class)->exec('UPDATE '.$container->get('config')['db.'.$dbTo.'.prefix'].$Mod->modName.' SET id = :legacy_id WHERE id = :id', ['legacy_id' => (int) $row['id'], 'id' => $insertId]);

            $container->get(DbInterface::class)->exec('UPDATE '.$container->get('config')['db.'.$dbTo.'.prefix'].$Mod->modName.'_lang SET item_id = :legacy_id WHERE item_id = :item_id', ['legacy_id' => (int) $row['id'], 'item_id' => $insertId]);

            if (0 === count($Mod->errors)) {
                se(sprintf('added %1$s legacy_id #%2$d', $Mod->modName, (int) $row['id']));
            } else {
                $container->errors[] = sprintf('%1$s: %2$s', 'legacy_id', (int) $row['id']);
                $container->errors[] = sprintf('%1$s: %2$s', '__LINE__', __LINE__);
            }
        } else {
            se($row);
            se($Mod->errors);
        }
    }
}

if ((is_countable($container->get('errors')) ? count($container->get('errors')) : 0) > 0) {
    se(implode(PHP_EOL, $container->get('errors')));

    return -1;
}

return 0;
