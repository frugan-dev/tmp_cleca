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

use Symfony\Component\Mailer\Exception\InvalidArgumentException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\Message;

use function Safe\mb_send_mail;

// https://github.com/symfony/symfony/issues/35469#issuecomment-578435495
final class MailTransport extends AbstractTransport implements \Stringable
{
    #[\Override]
    public function __toString(): string
    {
        return 'mail://';
    }

    #[\Override]
    protected function doSend(SentMessage $message): void
    {
        if (!$message->getOriginalMessage() instanceof Message) {
            throw new InvalidArgumentException(\sprintf('$message->getOriginalMessage() must be an instance of "%s" (got "%s").', Message::class, get_debug_type($message->getOriginalMessage())));
        }

        $headers = [];
        $headersToBypass = ['to', 'subject'];
        foreach ($message->getOriginalMessage()->getPreparedHeaders()->all() as $name => $header) {
            if (\in_array($name, $headersToBypass, true)) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        $headers['Content-Type'] = 'text/html; charset=UTF-8';

        // https://www.php.net/manual/en/function.mail.php#121163
        mb_send_mail(
            implode(',', $this->stringifyAddresses($message->getEnvelope()->getRecipients())),
            $message->getOriginalMessage()->getSubject(),
            // TODO - https://stackoverflow.com/a/10267876/3929620
            $message->getOriginalMessage()->getHtmlBody(), // $message->getOriginalMessage()->getTextBody()
            $headers
        );
    }
}
