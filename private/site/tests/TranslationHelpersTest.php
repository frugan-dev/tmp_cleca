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

namespace App\Tests\Unit\Factory\Translator;

use App\Factory\Translator\TranslatorInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class TranslationHelpersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Ensure we have a clean global state
        global $container;
        $container = null;

        // Set test environment
        $_SERVER['APP_ENV'] = 'test';
    }

    /**
     * Test that translation functions return original text when no container is available.
     */
    public function testTranslationFunctionsReturnOriginalTextWithoutContainer(): void
    {
        global $container;
        $container = null;

        self::assertSame('Hello World', __('Hello World'));
        self::assertSame('Hello World', _x('Hello World', 'greeting'));
        self::assertSame('item', _n('item', 'items', 1));
        self::assertSame('items', _n('item', 'items', 2));
    }

    /**
     * Test with container that doesn't have translator service.
     */
    public function testWithContainerWithoutTranslator(): void
    {
        global $container;

        $container = new class implements ContainerInterface {
            public function get(string $id)
            {
                return null;
            }

            public function has(string $id): bool
            {
                return false;
            }
        };

        self::assertSame('Hello World', __('Hello World'));
        self::assertSame('Hello World', _x('Hello World', 'greeting'));
        self::assertSame('item', _n('item', 'items', 1));
        self::assertSame('items', _n('item', 'items', 2));
    }

    /**
     * Test with translator that returns original text (no translation found).
     */
    public function testWithTranslatorReturningOriginalText(): void
    {
        global $container;

        $translator = $this->createMockTranslator(fn ($message) => $message);
        $container = $this->createMockContainer($translator);

        self::assertSame('Hello World', __('Hello World'));
        self::assertSame('Welcome', _x('Welcome', 'greeting'));
        self::assertSame('cat', _n('cat', 'cats', 1));
        self::assertSame('cats', _n('cat', 'cats', 2));
    }

    /**
     * Test with translator returning actual translations.
     */
    public function testWithTranslatorReturningTranslations(): void
    {
        global $container;

        $translator = $this->createMockTranslator(function ($message, $context = null) {
            // Simulate some available translations
            if ('Hello World' === $message) {
                return 'Ciao Mondo';
            }
            if ('Welcome' === $message && 'greeting' === $context) {
                return 'Benvenuto';
            }

            // Fallback to original text for untranslated messages
            return $message;
        });

        $container = $this->createMockContainer($translator);

        self::assertSame('Ciao Mondo', __('Hello World'));
        self::assertSame('Benvenuto', _x('Welcome', 'greeting'));
        self::assertSame('Untranslated', __('Untranslated')); // Not translated, returns original
    }

    /**
     * Test that functions handle empty translations correctly.
     * Should return original text if translation is empty.
     */
    public function testEmptyTranslationsFallbackToOriginal(): void
    {
        global $container;

        $translator = $this->createMockTranslator(function ($message) {
            // Simulate empty translation (TranslatorFactory should handle this)
            if ('Empty Translation' === $message) {
                return ''; // Empty translation
            }

            return $message;
        });

        $container = $this->createMockContainer($translator);

        // Should return original text for empty translations
        self::assertSame('Empty Translation', __('Empty Translation'));
    }

    /**
     * Test your specific original failing cases.
     */
    public function testOriginalFailingCases(): void
    {
        global $container;
        $container = $this->createMockContainer($this->createMockTranslator());

        // These should contain the word "output" because there are no translations
        // and therefore should return the original text
        self::assertStringContainsString(
            'output',
            __('%1$s output: %2$s'),
            'Should return original text containing "output"'
        );

        self::assertStringContainsString(
            'output',
            __('%1$s output: %2$s', 'default', 'it_IT.UTF-8'),
            'Should return original text containing "output"'
        );

        self::assertStringContainsString(
            'output',
            __('%1$s output: %2$s', 'default', 'en_US.UTF-8'),
            'Should return original text containing "output"'
        );

        self::assertStringContainsString(
            'output',
            _x('%1$s output: %2$s', 'default', 'it_IT.UTF-8'),
            'Should return original text containing "output"'
        );

        self::assertStringContainsString(
            'output',
            _x('%1$s output: %2$s', 'default', 'en_US.UTF-8'),
            'Should return original text containing "output"'
        );
    }

    /**
     * Test edge cases.
     */
    public function testEdgeCases(): void
    {
        global $container;
        $container = $this->createMockContainer($this->createMockTranslator());

        // Test with empty strings
        self::assertSame('', __(''));
        self::assertSame('', _x('', 'context'));

        // Test with missing parameters (using @ to avoid warnings)
        self::assertSame('', @__());
        self::assertSame('', @_x());

        // Test _n with empty values
        self::assertSame('', _n('', '', 1));
        self::assertSame('', _n('', '', 2));

        // Test _n with only singular empty
        self::assertSame('', _n('', 'items', 1));
        self::assertSame('items', _n('', 'items', 2));
    }

    /**
     * Test that functions handle exceptions gracefully.
     */
    public function testExceptionHandling(): void
    {
        global $container;

        // Container that throws exceptions
        $container = new class implements ContainerInterface {
            public function get(string $id): void
            {
                throw new \Exception('Container error');
            }

            public function has(string $id): bool
            {
                return true; // Says it has the service but then fails
            }
        };

        // Should return original text even if there are exceptions
        self::assertSame('Hello World', __('Hello World'));
        self::assertSame('Hello World', _x('Hello World', 'greeting'));
        self::assertSame('item', _n('item', 'items', 1));
        self::assertSame('items', _n('item', 'items', 2));
    }

    /**
     * Helper to create a mock translator.
     */
    private function createMockTranslator(?\Closure $translateCallback = null): TranslatorInterface
    {
        return new readonly class($translateCallback) implements TranslatorInterface {
            public function __construct(private ?\Closure $translateCallback = null) {}

            public function translate(string $message, ?string $context = null, ?string $locale = null, ?string $domain = null): string
            {
                if ($this->translateCallback) {
                    return ($this->translateCallback)($message, $context, $locale, $domain);
                }

                return $message; // Default: return original text
            }

            public function translatePlural(string $singular, string $plural, int $number, ?string $context = null, ?string $locale = null, ?string $domain = null): string
            {
                if ($this->translateCallback) {
                    return ($this->translateCallback)($number <= 1 ? $singular : $plural, $context, $locale, $domain);
                }

                return $number <= 1 ? $singular : $plural;
            }

            // Mock methods - minimal implementation
            public function getInstance(): ?Translator
            {
                return null;
            }

            public function create(?int $langId = null): self
            {
                return $this;
            }

            public function prepare(?int $langId = null): void {}

            public function hasTranslation(string $message, ?string $context = null, ?string $locale = null, ?string $domain = null): bool
            {
                return false;
            }

            public function hasPluralTranslation(string $singular, string $plural, ?string $context = null, ?string $locale = null, ?string $domain = null): bool
            {
                return false;
            }

            public function getAvailableMessages(?string $locale = null, ?string $domain = null): array
            {
                return [];
            }

            public function getDefaultDomain(): string
            {
                return 'messages';
            }

            public function setCache(?CacheInterface $cache): void {}
        };
    }

    /**
     * Helper to create a mock container.
     */
    private function createMockContainer(TranslatorInterface $translator): ContainerInterface
    {
        return new readonly class($translator) implements ContainerInterface {
            public function __construct(private TranslatorInterface $translator) {}

            public function get(string $id)
            {
                if (TranslatorInterface::class === $id) {
                    return $this->translator;
                }

                throw new \Exception("Service {$id} not found");
            }

            public function has(string $id): bool
            {
                return TranslatorInterface::class === $id;
            }
        };
    }
}
