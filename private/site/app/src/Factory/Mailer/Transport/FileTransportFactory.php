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

use Symfony\Component\Mailer\Transport\AbstractTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;
use Symfony\Component\Mailer\Transport\TransportInterface;

class FileTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        if (!\in_array($dsn->getScheme(), $this->getSupportedSchemes(), true)) {
            throw new \InvalidArgumentException(\sprintf(
                'The "%s" scheme is not supported; supported schemes for mailer "%s" are: "%s".',
                $dsn->getScheme(),
                static::class,
                implode('", "', $this->getSupportedSchemes())
            ));
        }

        // Extract path from DSN host
        $path = $dsn->getHost();

        // Handle default host
        if (empty($path) || 'default' === $path) {
            throw new \InvalidArgumentException('File transport requires a valid path in the DSN host component. Use file:///path/to/emails');
        }

        // Decode the path (in case it was URL encoded)
        $path = urldecode($path);

        // Validate path
        if (empty($path)) {
            throw new \InvalidArgumentException('File transport requires a valid path.');
        }

        // Check for continue parameter in query string
        $continueOnSuccess = filter_var(
            $dsn->getOption('continue', false),
            FILTER_VALIDATE_BOOLEAN
        );

        return new FileTransport($path, $continueOnSuccess);
    }

    protected function getSupportedSchemes(): array
    {
        return ['file'];
    }
}
