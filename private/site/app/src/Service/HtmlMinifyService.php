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

namespace App\Service;

use App\Factory\Logger\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use voku\helper\HtmlMin;

/**
 * HTML Minification Service.
 *
 * Provides HTML minification functionality that can be used by middleware,
 * error handlers, controllers, or any other component that needs to minify HTML.
 *
 * This service encapsulates all minification logic including:
 * - Configuration management
 * - Handlebars template protection
 * - Error handling and logging
 * - Performance metrics
 * - Enhanced HTML content detection
 */
class HtmlMinifyService
{
    private bool $initialized = false;
    private ?HtmlMin $htmlMin = null;
    private array $handlebarsTemplates = [];

    private array $lastStats = [];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Check if HTML minification is enabled.
     */
    public function shouldEnable(): bool
    {
        return $this->getConfigWithFallback('minify.enabled', false);
    }

    /**
     * Minify HTML content.
     *
     * @param string $html        The HTML content to minify
     * @param bool   $forceMinify Force minification even if disabled (useful for testing)
     *
     * @return string The minified HTML content
     */
    public function minify(string $html, bool $forceMinify = false): string
    {
        // Return original HTML if minification is disabled and not forced
        if (!$forceMinify && !$this->shouldEnable()) {
            return $html;
        }

        // Return original HTML if empty
        if (empty($html)) {
            return $html;
        }

        // Lazy initialization
        if (!$this->initialized) {
            $this->initialize();
        }

        // Return original HTML if initialization failed
        if (null === $this->htmlMin) {
            return $html;
        }

        try {
            return $this->performMinification($html);
        } catch (\Throwable $e) {
            $this->logger->error('HTML minification failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            // Return original HTML on error
            return $html;
        }
    }

    /**
     * Check if a content type represents HTML.
     */
    public function isHtmlContentType(string $contentType): bool
    {
        return 0 === stripos($contentType, 'text/html');
    }

    /**
     * Enhanced HTML content detection that combines header and content analysis.
     *
     * This method provides comprehensive HTML detection for cases where:
     * - Content-Type header is properly set to text/html
     * - Content-Type header is missing or incorrect but content is HTML
     * - Response contains generated HTML (e.g., redirect pages, error pages)
     *
     * @param null|ResponseInterface $response Optional response object for header checking
     * @param string                 $content  Content to analyze
     *
     * @return bool True if content should be treated as HTML
     */
    public function isHtmlContent(?ResponseInterface $response, string $content): bool
    {
        // First check the Content-Type header if response is provided (most reliable method)
        if (null !== $response) {
            $contentType = $response->getHeaderLine('Content-Type');
            if ($this->isHtmlContentType($contentType)) {
                return true;
            }
        }

        // If no response provided, no Content-Type header, or it's not HTML, check the content itself
        // This handles cases like redirect responses where Content-Type might not be set
        return $this->isHtmlByContent($content);
    }

    /**
     * Check if content is HTML by analyzing the content structure.
     *
     * This method detects HTML content by looking for common HTML patterns:
     * - DOCTYPE declarations
     * - HTML opening tags
     * - Common HTML structural elements
     * - XML/XHTML declarations
     *
     * @param string $content Content to analyze
     *
     * @return bool True if content appears to be HTML
     */
    public function isHtmlByContent(string $content): bool
    {
        if (empty($content)) {
            return false;
        }

        // Trim whitespace and convert to lowercase for pattern matching
        $trimmedContent = trim($content);
        $lowerContent = strtolower($trimmedContent);

        // Check for DOCTYPE declaration (HTML5 and older versions)
        if (\Safe\preg_match('/^\s*<!doctype\s+html/i', $trimmedContent)) {
            return true;
        }

        // Check for XML/XHTML declaration followed by HTML
        if (\Safe\preg_match('/^\s*<\?xml[^>]*\?>\s*<!doctype\s+html/i', $trimmedContent)) {
            return true;
        }

        // Check for HTML opening tag (with or without attributes)
        if (\Safe\preg_match('/^\s*<html(?:\s[^>]*)?>/', $lowerContent)) {
            return true;
        }

        // Check for common HTML structural elements that strongly indicate HTML content
        $strongHtmlPatterns = [
            '/<html\b[^>]*>/i',     // HTML tag with attributes
            '/<head\b[^>]*>/i',     // HEAD tag
            '/<body\b[^>]*>/i',     // BODY tag
            '/<title\b[^>]*>/i',    // TITLE tag
        ];

        foreach ($strongHtmlPatterns as $pattern) {
            if (\Safe\preg_match($pattern, $content)) {
                return true;
            }
        }

        // Check for common HTML metadata elements
        $metaHtmlPatterns = [
            '/<meta\b[^>]*>/i',          // META tag
            '/<link\b[^>]*>/i',          // LINK tag
            '/<script\b[^>]*>/i',        // SCRIPT tag
            '/<style\b[^>]*>/i',         // STYLE tag
        ];

        $metaPatternMatches = 0;
        foreach ($metaHtmlPatterns as $pattern) {
            if (\Safe\preg_match($pattern, $content)) {
                ++$metaPatternMatches;
            }
        }

        // If we find multiple meta elements, it's likely HTML
        if ($metaPatternMatches >= 2) {
            return true;
        }

        // Check for common HTML5 semantic elements
        $html5Patterns = [
            '/<(?:header|footer|nav|main|article|section|aside)\b[^>]*>/i',
        ];

        foreach ($html5Patterns as $pattern) {
            if (\Safe\preg_match($pattern, $content)) {
                return true;
            }
        }

        // Final heuristic: check for a combination of HTML-like patterns
        // This catches cases where we have HTML-like content but not the structural elements
        $htmlLikePatterns = [
            '/<[a-z]+\b[^>]*>/i',        // Any HTML tag
            '/&[a-z]+;/i',               // HTML entities
            '/<!--.*?-->/s',             // HTML comments
        ];

        $htmlLikeMatches = 0;
        foreach ($htmlLikePatterns as $pattern) {
            if (\Safe\preg_match($pattern, $content)) {
                ++$htmlLikeMatches;
            }
        }

        // If we have multiple HTML-like patterns and the content is substantial, treat as HTML
        return $htmlLikeMatches >= 2 && \strlen($trimmedContent) > 50;
    }

    /**
     * Smart minify method that automatically detects HTML content and applies minification.
     *
     * This is a convenience method that combines HTML detection and minification.
     * Useful for cases where you want to minify content but aren't sure if it's HTML.
     *
     * @param string                 $content         Content to potentially minify
     * @param null|ResponseInterface $response        Optional response for header checking
     * @param bool                   $forceMinify     Force minification even if disabled
     * @param bool                   $strictDetection Use only header-based detection if true
     *
     * @return string Original content if not HTML, minified content if HTML
     */
    public function smartMinify(
        string $content,
        ?ResponseInterface $response = null,
        bool $forceMinify = false,
        bool $strictDetection = false
    ): string {
        if (empty($content)) {
            return $content;
        }

        // Determine if content is HTML
        $isHtml = $strictDetection
            ? ($response && $this->isHtmlContentType($response->getHeaderLine('Content-Type')))
            : $this->isHtmlContent($response, $content);

        // Only minify if content is detected as HTML
        return $isHtml ? $this->minify($content, $forceMinify) : $content;
    }

    /**
     * Get minification statistics for the last operation.
     */
    public function getLastStats(): array
    {
        return $this->lastStats ?? [];
    }

    /**
     * Get configuration value with environment fallback using existing ConfigManager.
     *
     * @param null|mixed $default
     */
    protected function getConfigWithFallback(string $suffix = '', $default = null, ?array $prefixes = null)
    {
        $env = $this->container->get('env');

        $prefixes ??= [
            "html.{$env}",
            'html',
        ];

        return $this->container->get('config')->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }

    /**
     * Perform the actual minification process.
     */
    private function performMinification(string $html): string
    {
        // https://github.com/voku/HtmlMin/issues/77#issuecomment-1160412418
        // $this->htmlMin->overwriteSpecialScriptTags(['text/x-handlebars-template']);

        // Reset handlebars templates for each operation
        $this->handlebarsTemplates = [];

        // Protect handlebars templates
        $protectedHtml = $this->protectHandlebarsTemplates($html);

        // Minify the HTML
        $minifiedHtml = $this->htmlMin->minify($protectedHtml);

        // Restore handlebars templates
        $finalHtml = $this->restoreHandlebarsTemplates($minifiedHtml);

        // Calculate and store statistics
        $this->calculateStats($html, $finalHtml);

        return $finalHtml;
    }

    /**
     * Initialize the HTML minifier with configuration.
     */
    private function initialize(): void
    {
        try {
            $this->htmlMin = new HtmlMin();
            $this->configure();
            $this->initialized = true;

            $this->logger->debugInternal('HTML minifier initialized successfully');
        } catch (\Exception $e) {
            $this->logger->error('Failed to initialize HTML minifier', [
                'error' => $e->getMessage(),
            ]);
            $this->htmlMin = null;
        }
    }

    /**
     * Configure the HTML minifier based on application settings.
     */
    private function configure(): void
    {
        if (null === $this->htmlMin) {
            return;
        }

        // Basic configuration
        $this->htmlMin->doOptimizeViaHtmlDomParser();               // optimize html via "HtmlDomParser()"
        $this->htmlMin->doRemoveComments();                         // remove default HTML comments (depends on "doOptimizeViaHtmlDomParser(true)")
        $this->htmlMin->doSumUpWhitespace();                        // sum-up extra whitespace from the Dom (depends on "doOptimizeViaHtmlDomParser(true)")
        $this->htmlMin->doRemoveWhitespaceAroundTags();             // remove whitespace around tags (depends on "doOptimizeViaHtmlDomParser(true)")
        $this->htmlMin->doOptimizeAttributes();                     // optimize html attributes (depends on "doOptimizeViaHtmlDomParser(true)")
        $this->htmlMin->doRemoveHttpPrefixFromAttributes();         // remove optional "http:"-prefix from attributes (depends on "doOptimizeAttributes(true)")
        $this->htmlMin->doKeepHttpAndHttpsPrefixOnExternalAttributes(); // keep "http:"- and "https:"-prefix for all external links
        $this->htmlMin->doRemoveDeprecatedAnchorName();             // remove deprecated anchor-jump (depends on "doOptimizeAttributes(true)")
        $this->htmlMin->doRemoveDeprecatedScriptCharsetAttribute(); // remove deprecated charset-attribute - the browser will use the charset from the HTTP-Header, anyway (depends on "doOptimizeAttributes(true)")
        $this->htmlMin->doRemoveDeprecatedTypeFromScriptTag();      // remove deprecated script-mime-types (depends on "doOptimizeAttributes(true)")
        $this->htmlMin->doRemoveDeprecatedTypeFromStylesheetLink(); // remove "type=text/css" for css links (depends on "doOptimizeAttributes(true)")
        $this->htmlMin->doRemoveDeprecatedTypeFromStyleAndLinkTag(); // remove "type=text/css" from all links and styles
        $this->htmlMin->doRemoveDefaultMediaTypeFromStyleAndLinkTag(); // remove "media="all" from all links and styles
        $this->htmlMin->doRemoveEmptyAttributes();                  // remove some empty attributes (depends on "doOptimizeAttributes(true)")
        $this->htmlMin->doRemoveValueFromEmptyInput();              // remove 'value=""' from empty <input> (depends on "doOptimizeAttributes(true)")
        $this->htmlMin->doSortCssClassNames();                      // sort css-class-names, for better gzip results (depends on "doOptimizeAttributes(true)")
        $this->htmlMin->doSortHtmlAttributes();                     // sort html-attributes, for better gzip results (depends on "doOptimizeAttributes(true)")
        $this->htmlMin->doRemoveOmittedQuotes();                    // remove quotes e.g. class="lall" => class=lall
        $this->htmlMin->doRemoveOmittedHtmlTags();                  // remove ommitted html tags e.g. <p>lall</p> => <p>lall

        // Advanced configurations
        if ($this->getConfigWithFallback('minify.aggressive', false)) {
            $this->htmlMin->doRemoveSpacesBetweenTags();            // remove more (aggressive) spaces in the dom (disabled by default)
        }

        // Experimental configurations
        if ($this->getConfigWithFallback('minify.experimental', false)) {
            $this->htmlMin->doRemoveHttpsPrefixFromAttributes();    // remove optional "https:"-prefix from attributes (depends on "doOptimizeAttributes

            $baseUrl = $this->container->get('config')->get('url.base');
            if (!empty($baseUrl)) {
                $this->htmlMin->doMakeSameDomainsLinksRelative([$baseUrl]); // make some links relative, by removing the domain from
            }

            $this->htmlMin->doRemoveDefaultAttributes();                // remove defaults (depends on "doOptimizeAttributes(true)" | disabled by default)
            $this->htmlMin->doRemoveDefaultTypeFromButton();            // remove type="submit" from button tags
        }
    }

    /**
     * Protect handlebars templates from minification.
     */
    private function protectHandlebarsTemplates(string $html): string
    {
        return \Safe\preg_replace_callback(
            '/<(script[^>]*type="text\/x-handlebars-template"[^>]*)>(.*?)(<\/script>)/is',
            function ($matches) {
                $placeholder = '___HANDLEBARS_TEMPLATE_'.\count($this->handlebarsTemplates).'___';
                $this->handlebarsTemplates[$placeholder] = '<'.$matches[1].'>'.$matches[2].$matches[3];

                return $placeholder;
            },
            $html
        );
    }

    /**
     * Restore handlebars templates after minification.
     */
    private function restoreHandlebarsTemplates(string $html): string
    {
        foreach ($this->handlebarsTemplates as $placeholder => $template) {
            $html = str_replace($placeholder, $template, $html);
        }

        return $html;
    }

    /**
     * Calculate and log minification statistics.
     */
    private function calculateStats(string $original, string $minified): void
    {
        $originalSize = \strlen($original);
        $minifiedSize = \strlen($minified);
        $savings = $originalSize - $minifiedSize;
        $savingsPercent = $originalSize > 0 ? round(($savings / $originalSize) * 100, 2) : 0;

        $this->lastStats = [
            'original_size' => $originalSize,
            'minified_size' => $minifiedSize,
            'savings_bytes' => $savings,
            'savings_percent' => $savingsPercent,
        ];

        $this->logger->debugInternal('HTML minified', $this->lastStats);
    }
}
