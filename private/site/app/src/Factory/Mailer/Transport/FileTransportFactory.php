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

        $path = $dsn->getHost();
        if ($dsn->getPath()) {
            $path .= $dsn->getPath();
        }

        if (empty($path)) {
            throw new \InvalidArgumentException('File transport requires a valid path.');
        }

        $continueOnSuccess = false;
        $options = $dsn->getOptions();
        if (isset($options['continue'])) {
            $continueOnSuccess = filter_var($options['continue'], FILTER_VALIDATE_BOOLEAN);
        }

        return new FileTransport($path, $continueOnSuccess);
    }

    protected function getSupportedSchemes(): array
    {
        return ['file'];
    }
}
