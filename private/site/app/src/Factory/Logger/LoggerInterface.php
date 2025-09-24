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

namespace App\Factory\Logger;

use Psr\Log\LoggerInterface as PsrLoggerInterface;

/**
 * Factory interface for creating and managing multiple logger channels.
 */
interface LoggerInterface extends PsrLoggerInterface
{
    /**
     * Create and configure all logger instances from config.
     */
    public function create(): self;

    /**
     * Get a logger instance by channel name.
     * If no channel specified, returns the default (first configured) channel.
     *
     * @throws \RuntimeException if channel doesn't exist or isn't configured
     */
    public function channel(?string $channelName = null): PsrLoggerInterface;

    /**
     * Get all available channel names.
     */
    public function getChannels(): array;

    /**
     * Check if a channel exists and is configured.
     */
    public function hasChannel(string $channelName): bool;

    /**
     * Log to a specific channel with dynamic level.
     *
     * @param mixed $level
     */
    public function logToChannel(string $channelName, $level, string|\Stringable $message, array $context = []): void;

    // PSR-3 methods delegate to main channel
    public function log($level, string|\Stringable $message, array $context = []): void;
}
