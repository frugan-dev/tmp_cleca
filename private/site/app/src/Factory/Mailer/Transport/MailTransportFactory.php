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

namespace App\Factory\Mailer\Transport;

use Symfony\Component\Mailer\Exception\UnsupportedSchemeException;
use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

final class MailTransportFactory extends AbstractTransportFactory
{
    #[\Override]
    public function create(Dsn $dsn): TransportInterface
    {
        if ('mail' === $dsn->getScheme()) {
            return new MailTransport($this->dispatcher, $this->logger);
        }

        if ('mail+api' === $dsn->getScheme()) {
            return new MailApiTransport($this->client, $this->dispatcher, $this->logger);
        }

        throw new UnsupportedSchemeException($dsn, 'mail', $this->getSupportedSchemes());
    }

    #[\Override]
    public function getSupportedSchemes(): array
    {
        return ['mail', 'mail+api'];
    }
}
