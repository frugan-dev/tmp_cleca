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

use App\Factory\Mailer\MailerInterface;
use App\Helper\HelperInterface;

$mailer = $container->get(MailerInterface::class);

$params = [
    'date' => $container->get(HelperInterface::class)->Carbon()->now(),
    'to' => $container->get('config')['debug.emailsTo'],
    'subject' => sprintf('test %1$d', time()),
    'html' => sprintf('test %1$d', time()),
    'text' => sprintf('test %1$d', time()),
];

$mailer->prepare($params);

if ($mailer->send()) {
    se(sprintf('sent mail to %1$s', implode(', ', array_keys($container->get('config')['debug.emailsTo']))));
} else {
    $container->errors[] = sprintf('%1$s: %2$s', '__LINE__', __LINE__);
}

if ((is_countable($container->get('errors')) ? count($container->get('errors')) : 0) > 0) {
    se(implode(PHP_EOL, $container->get('errors')));

    return -1;
}

return 0;
