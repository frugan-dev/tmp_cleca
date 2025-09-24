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

namespace App\Factory\Mailer\OAuth2\Authenticator;

use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;

interface AuthenticatorInterface extends \Symfony\Component\Mailer\Transport\Smtp\Auth\AuthenticatorInterface
{
    public function authenticate(EsmtpTransport $client): void;
}
