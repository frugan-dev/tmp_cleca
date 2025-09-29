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

use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;

class FileTransport extends AbstractTransport
{
    private readonly string $filePath;

    public function __construct(
        string $filePath,
        private readonly bool $continueOnSuccess = false
    ) {
        $this->filePath = rtrim($filePath, '/');

        if (!is_dir($this->filePath)) {
            if (!\Safe\mkdir($this->filePath, 0o755, true) && !is_dir($this->filePath)) {
                throw new \RuntimeException("Cannot create directory: {$this->filePath}");
            }
        }

        if (!is_writable($this->filePath)) {
            throw new \RuntimeException("Directory is not writable: {$this->filePath}");
        }

        parent::__construct();
    }

    public function __toString(): string
    {
        $suffix = $this->continueOnSuccess ? '?continue=1' : '';

        return \sprintf('file://%s%s', $this->filePath, $suffix);
    }

    protected function doSend(SentMessage $message): void
    {
        $envelope = $message->getEnvelope();
        $rawMessage = $message->getOriginalMessage();

        $filename = \sprintf(
            '%s_%s_%s.eml',
            date('Y-m-d_H-i-s'),
            uniqid(),
            substr(md5($envelope->getSender()->getAddress()), 0, 8)
        );

        $filepath = $this->filePath.'/'.$filename;
        $content = $rawMessage->toString();

        if (false === \Safe\file_put_contents($filepath, $content)) {
            throw new \RuntimeException("Failed to write email to file: {$filepath}");
        }

        // Trigger exception to continue to next transport if configured
        if ($this->continueOnSuccess) {
            throw new TransportException('File transport: email saved, continuing to next transport');
        }
    }
}
