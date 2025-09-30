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

namespace App\Factory\Logger\Handler;

use App\Factory\Mailer\MailerInterface;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NativeMailerHandler;
use Monolog\Handler\SymfonyMailerHandler;
use Monolog\Level;
use Monolog\LogRecord;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Failover Email Handler for Monolog.
 *
 * This handler provides robust email notification for application errors by attempting
 * to use the application's configured mail transport (SymfonyMailerHandler) first,
 * then falling back to PHP's native mail() function (NativeMailerHandler) if needed.
 *
 * INFINITE LOOP PREVENTION:
 * The handler prevents infinite recursion that can occur when:
 * 1. Application error occurs (e.g., OAuth2Transport or GraphAPITransport fails)
 * 2. Logger->error() is called to log the failure
 * 3. FailoverEmailHandler tries to send error notification email
 * 4. Uses same transport system as application (OAuth2Transport fails again)
 * 5. Logger->error() called again → infinite loop
 *
 * SOLUTION:
 * A static $isProcessing flag tracks when the handler is already active:
 * - When write() is called, set $isProcessing = true
 * - If write() called again while $isProcessing = true, skip email sending
 * - Reset $isProcessing = false in finally block
 *
 * EXECUTION FLOW EXAMPLE (OAuth2 SMTP failure):
 * 1. OAuth2Transport (smtp) fails in application
 * 2. Logger->error() called
 * 3. FailoverEmailHandler: $isProcessing=false → try primary handler (SymfonyMailerHandler)
 * 4. SymfonyMailer uses OAuth2Transport → fails again
 * 5. Logger->error() called from transport → FailoverEmailHandler: $isProcessing=true → SKIP
 * 6. Original handler continues → uses fallback (NativeMailerHandler)
 * 7. Result: Single error email sent via native mail(), no infinite loop
 *
 * EXECUTION FLOW EXAMPLE (Graph API failure - future):
 * 1. OAuth2Transport (graph) fails in application
 * 2. Logger->error() called
 * 3. FailoverEmailHandler: $isProcessing=false → try primary handler (SymfonyMailerHandler)
 * 4. SymfonyMailer tries failover transports: oauth2-graph → oauth2-smtp → smtp
 * 5. If all configured transports fail → Logger->error() called → $isProcessing=true → SKIP
 * 6. Original handler continues → uses fallback (NativeMailerHandler)
 * 7. Result: Single error email sent, no infinite loop
 *
 * This approach maintains SPF/DKIM/DMARC consistency by using the application's
 * transport configuration (oauth2-smtp, oauth2-graph, smtp, etc.) while preventing
 * system-breaking recursion when mail transports fail.
 */
class FailoverEmailHandler extends AbstractProcessingHandler
{
    private const int MAX_CONSECUTIVE_FAILURES = 3;
    private ?SymfonyMailerHandler $primaryHandler = null;
    private ?NativeMailerHandler $fallbackHandler = null;
    private bool $primaryFailed = false;
    private int $consecutiveFailures = 0;

    // Static flag to prevent infinite recursion
    private static bool $isProcessing = false;

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly array $emailConfig,
        Level $level = Level::Error,
        bool $bubble = true
    ) {
        parent::__construct($level, $bubble);
        $this->initializeHandlers();
    }

    /**
     * Reset the failure state (can be called externally if needed).
     */
    public function resetFailureState(): void
    {
        $this->primaryFailed = false;
        $this->consecutiveFailures = 0;
    }

    /**
     * Set formatter for both handlers.
     */
    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        if ($this->primaryHandler) {
            $this->primaryHandler->setFormatter($formatter);
        }
        if ($this->fallbackHandler) {
            $this->fallbackHandler->setFormatter($formatter);
        }

        return parent::setFormatter($formatter);
    }

    /**
     * Process the log record.
     */
    protected function write(LogRecord $record): void
    {
        // Prevent infinite recursion: if we're already processing, don't try again
        if (self::$isProcessing) {
            \Safe\error_log('FailoverEmailHandler: Recursion detected, skipping email notification');

            return;
        }

        // Skip primary if too many consecutive failures
        if ($this->consecutiveFailures >= self::MAX_CONSECUTIVE_FAILURES) {
            $this->useFallbackHandler($record);

            return;
        }

        // Try primary handler with recursion protection
        if (!$this->primaryFailed && $this->primaryHandler) {
            self::$isProcessing = true;

            try {
                $this->primaryHandler->handle($record);
                $this->consecutiveFailures = 0; // Reset on success

                return;
            } catch (\Exception $e) {
                ++$this->consecutiveFailures;

                // Log the failure (to file only, avoid infinite loop)
                \Safe\error_log(\sprintf(
                    'FailoverEmailHandler: Primary handler failed (%d/%d): %s',
                    $this->consecutiveFailures,
                    self::MAX_CONSECUTIVE_FAILURES,
                    $e->getMessage()
                ));

                // Mark as failed if it's a transport error
                if ($this->isMailTransportError($e)) {
                    $this->primaryFailed = true;
                }
            } finally {
                self::$isProcessing = false;
            }
        }

        // Use fallback handler
        $this->useFallbackHandler($record);
    }

    /**
     * Initialize both handlers.
     */
    private function initializeHandlers(): void
    {
        try {
            // Primary handler: SymfonyMailerHandler (for SPF/DKIM/DMARC consistency)
            // Uses the application's configured transport system (oauth2-smtp, oauth2-graph, smtp, etc.)
            $this->mailer->prepare([
                'to' => $this->emailConfig['to'],
                'subject' => $this->emailConfig['subject'],
            ]);

            $this->primaryHandler = new SymfonyMailerHandler(
                $this->mailer->getInstance(),
                $this->mailer->getMessage(),
                Level::Error
            );
        } catch (\Exception $e) {
            // If primary handler creation fails, mark as failed
            $this->primaryFailed = true;
            \Safe\error_log('FailoverEmailHandler: Primary handler creation failed: '.$e->getMessage());
        }

        // Fallback handler: NativeMailerHandler (always available)
        try {
            $this->fallbackHandler = new NativeMailerHandler(
                $this->emailConfig['to_string'],
                $this->emailConfig['subject'],
                $this->emailConfig['from'],
                Level::Error
            );
        } catch (\Exception $e) {
            \Safe\error_log('FailoverEmailHandler: Fallback handler creation failed: '.$e->getMessage());
        }
    }

    /**
     * Use the fallback handler.
     */
    private function useFallbackHandler(LogRecord $record): void
    {
        if (!$this->fallbackHandler) {
            return; // Nothing we can do
        }

        try {
            // Add context to indicate this is from fallback
            $modifiedRecord = $record->with(
                context: array_merge($record->context, [
                    '_email_handler' => 'fallback',
                    '_primary_failed' => $this->primaryFailed,
                ])
            );

            $this->fallbackHandler->handle($modifiedRecord);
        } catch (\Exception $e) {
            // Last resort: log to error_log
            \Safe\error_log('FailoverEmailHandler: All handlers failed: '.$e->getMessage());
        }
    }

    /**
     * Check if the exception is related to mail transport issues (very conservative approach).
     */
    private function isMailTransportError(\Exception $e): bool
    {
        // Only check for specific, unambiguous mail transport exception types
        // Avoid generic exceptions that could come from other parts of the system
        $transportExceptionTypes = [
            TransportException::class,
            TransportExceptionInterface::class,
            // Future providers exceptions can be added here:
            // \Microsoft\Kiota\Abstractions\ApiException::class,
            // \Google\Service\Exception::class,
        ];

        return array_any($transportExceptionTypes, fn ($exceptionType) => $e instanceof $exceptionType);
    }
}
