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

use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractApiTransport;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\ResponseInterface;

use function Safe\mb_send_mail;

// https://github.com/symfony/symfony/issues/35469#issuecomment-578435495
final class MailApiTransport extends AbstractApiTransport implements \Stringable
{
    #[\Override]
    public function __toString(): string
    {
        return 'mail+api://';
    }

    #[\Override]
    protected function doSendApi(SentMessage $sentMessage, Email $email, Envelope $envelope): ResponseInterface
    {
        $headers = [];
        $headersToBypass = ['to', 'subject'];
        foreach ($email->getHeaders()->all() as $name => $header) {
            if (\in_array($name, $headersToBypass, true)) {
                continue;
            }

            $headers[$header->getName()] = $header->getBodyAsString();
        }

        $headers['Content-Type'] = 'text/html; charset=UTF-8';

        // https://www.php.net/manual/en/function.mail.php#121163
        mb_send_mail(
            implode(',', $this->stringifyAddresses($this->getRecipients($email, $envelope))),
            $email->getSubject(),
            // TODO - https://stackoverflow.com/a/10267876/3929620
            $email->getHtmlBody(), // $email->getTextBody()
            $headers
        );

        return $this->client->request('HEAD', 'https://google.com');
    }
}
