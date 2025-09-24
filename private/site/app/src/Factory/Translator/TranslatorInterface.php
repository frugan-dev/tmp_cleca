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

namespace App\Factory\Translator;

use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Cache\CacheInterface as PsrCacheInterface;

interface TranslatorInterface
{
    /**
     * Get the underlying Symfony Translator instance.
     */
    public function getInstance(): ?Translator;

    /**
     * Create and configure the translator instance.
     */
    public function create(?int $langId = null): self;

    /**
     * Prepare language configuration.
     */
    public function prepare(?int $langId = null): void;

    /**
     * Set cache for translations.
     */
    public function setCache(?PsrCacheInterface $cache): void;

    /**
     * Translate a message with context support.
     * Parameter substitution (sprintf, etc.) should be handled by calling code.
     *
     * @param string      $message The message to translate
     * @param null|string $context Translation context (from msgctxt)
     * @param null|string $locale  Locale override
     * @param null|string $domain  Translation domain
     *
     * @return string Translated message
     */
    public function translate(string $message, ?string $context = null, ?string $locale = null, ?string $domain = null): string;

    /**
     * Translate a plural message with context support.
     * Parameter substitution (sprintf, etc.) should be handled by calling code.
     *
     * @param string      $singular Singular form
     * @param string      $plural   Plural form
     * @param int         $number   Number for plural rules
     * @param null|string $context  Translation context (from msgctxt)
     * @param null|string $locale   Locale override
     * @param null|string $domain   Translation domain
     *
     * @return string Translated message
     */
    public function translatePlural(string $singular, string $plural, int $number, ?string $context = null, ?string $locale = null, ?string $domain = null): string;

    /**
     * Check if a translation exists in the loaded catalogs.
     *
     * @param string      $message The message to check
     * @param null|string $context Translation context (from msgctxt)
     * @param null|string $locale  Locale override
     * @param null|string $domain  Translation domain
     *
     * @return bool True if translation exists
     */
    public function hasTranslation(string $message, ?string $context = null, ?string $locale = null, ?string $domain = null): bool;

    /**
     * Check if a plural translation exists in the loaded catalogs.
     *
     * @param string      $singular Singular form
     * @param string      $plural   Plural form
     * @param null|string $context  Translation context (from msgctxt)
     * @param null|string $locale   Locale override
     * @param null|string $domain   Translation domain
     *
     * @return bool True if plural translation exists
     */
    public function hasPluralTranslation(string $singular, string $plural, ?string $context = null, ?string $locale = null, ?string $domain = null): bool;

    /**
     * Debug method to get all available messages in a domain for debugging.
     */
    public function getAvailableMessages(?string $locale = null, ?string $domain = null): array;
}
