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
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\RawMessage;

/**
 * Transport wrapper that tracks when it's actually used for sending and captures failures.
 */
class TrackedTransport implements TransportInterface
{
    private static ?array $lastUsedTransportInfo = null;
    private static array $failedTransports = [];
    private static array $attemptedTransports = [];

    public function __construct(
        protected TransportInterface $innerTransport,
        protected readonly string $transportType,
        protected readonly ?string $provider = null,
        protected readonly ?string $providerType = null,
        protected readonly array $metadata = []
    ) {}

    public function __toString(): string
    {
        return (string) $this->innerTransport;
    }

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        $transportInfo = [
            'type' => $this->transportType,
            'provider' => $this->provider,
            'provider_type' => $this->providerType,
            'metadata' => $this->metadata,
            'timestamp' => microtime(true),
            'class' => $this->innerTransport::class,
        ];

        // Record attempt
        self::$attemptedTransports[] = $transportInfo;

        try {
            // Record that this transport is being used
            self::$lastUsedTransportInfo = $transportInfo;

            // Delegate to the actual transport
            $result = $this->innerTransport->send($message, $envelope);

            // If we get here, it succeeded
            $transportInfo['success'] = true;
            self::$lastUsedTransportInfo = $transportInfo;

            return $result;
        } catch (\Exception $e) {
            // Record the failure
            $failureInfo = array_merge($transportInfo, [
                'success' => false,
                'error' => $e->getMessage(),
                'error_class' => $e::class,
            ]);

            self::$failedTransports[] = $failureInfo;

            // Re-throw the exception so Symfony Mailer can try the next transport
            throw $e;
        }
    }

    /**
     * Get info about the last transport that was actually used successfully.
     */
    public static function getLastUsedTransportInfo(): ?array
    {
        return self::$lastUsedTransportInfo;
    }

    /**
     * Get info about all failed transport attempts.
     */
    public static function getFailedTransports(): array
    {
        return self::$failedTransports;
    }

    /**
     * Get info about all attempted transports (both successful and failed).
     */
    public static function getAttemptedTransports(): array
    {
        return self::$attemptedTransports;
    }

    /**
     * Clear all tracking info.
     */
    public static function clearAllTrackingInfo(): void
    {
        self::$lastUsedTransportInfo = null;
        self::$failedTransports = [];
        self::$attemptedTransports = [];
    }

    /**
     * Clear only the success tracking info.
     */
    public static function clearLastUsedTransportInfo(): void
    {
        self::$lastUsedTransportInfo = null;
    }

    /**
     * Get summary of transport usage.
     */
    public static function getTransportSummary(): array
    {
        $summary = [
            'total_attempts' => \count(self::$attemptedTransports),
            'total_failures' => \count(self::$failedTransports),
            'success_count' => 0,
            'failed_providers' => [],
            'successful_provider' => null,
        ];

        // Count successes
        foreach (self::$attemptedTransports as $attempt) {
            if ($attempt['success'] ?? false) {
                ++$summary['success_count'];
                $summary['successful_provider'] = $attempt['provider'];
            }
        }

        // Collect failed providers
        foreach (self::$failedTransports as $failure) {
            if ($failure['provider']) {
                $summary['failed_providers'][] = $failure['provider'];
            } else {
                $summary['failed_providers'][] = $failure['type'];
            }
        }

        $summary['failed_providers'] = array_unique($summary['failed_providers']);

        return $summary;
    }

    /**
     * Get the inner transport instance.
     */
    public function getInnerTransport(): TransportInterface
    {
        return $this->innerTransport;
    }
}
