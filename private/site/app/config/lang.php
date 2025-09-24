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

/*
 * TRANSLATION SYSTEM OVERVIEW: DOMAINS vs CONTEXTS
 *
 * This translation system supports both DOMAINS and CONTEXTS, which serve different purposes:
 *
 * 1. DOMAINS (Translation Domains):
 *    - Separate collections of translations (like categories)
 *    - Each domain typically corresponds to a separate .po/.mo file
 *    - Used to organize translations by functionality (e.g., 'messages', 'api', 'email')
 *    - Symfony Translation natively supports domains
 *    - Examples: 'messages' (default), 'admin', 'api', 'validation'
 *
 * 2. CONTEXTS (Translation Contexts):
 *    - Disambiguate the same string with different meanings within the same domain
 *    - Implemented using gettext's msgctxt feature (ASCII 4 separator: \004)
 *    - Allow the same English text to have different translations based on context
 *    - Symfony Translation does NOT natively support contexts (it treats them as domains)
 *    - Examples: "Post" could be translated differently in 'noun' vs 'verb' context
 *
 * IMPORTANT NOTES ABOUT FRAMEWORKS:
 *
 * - Symfony Translation: Does NOT have native context support. What they call "context"
 *   is actually what gettext calls "domain". Our system adds true context support
 *   via custom MoFileLoader and PoFileLoader that handle msgctxt properly.
 *
 * - Laminas i18n: Creates confusion by calling text domains "contexts" in their documentation
 *   (see https://docs.laminas.dev/laminas-i18n/translation/). They say "Use text domains
 *   to segregate translations by context" but they're actually referring to domains, not contexts.
 *
 * CONTEXT IMPLEMENTATION OPTIONS:
 *
 * Option A - Single File with Contexts (Recommended):
 *   - All contexts stored in the same .po/.mo file using msgctxt
 *   - More efficient file management
 *   - Standard gettext approach
 *   - Example: en_US.po contains both "Post|noun" and "Post|verb" with msgctxt
 *
 * Option B - Separate Files per Context (Legacy):
 *   - Each context gets its own domain/file
 *   - Update 'domains' to include context-specific domains
 *   - Create separate files like: en_US.male.po, en_US.female.po
 *   - Less efficient but simpler for some translation teams
 *
 * ENABLING/DISABLING CONTEXT SUPPORT:
 *
 * To ENABLE context support:
 * 1. Add contexts to the 'contexts' array below
 * 2. Use translation functions with context: __('Text', 'context') or _x('Text', 'context')
 * 3. In .po files, use msgctxt: msgctxt "context" followed by msgid "Text"
 *
 * To DISABLE context support:
 * 1. Set 'contexts' => ['default'] (single default context)
 * 2. Use standard translation functions: __('Text') without context parameter
 * 3. .po files don't need msgctxt entries
 *
 * TRANSLATION FUNCTION REFERENCE:
 * - __('text', $context, $locale, $domain) - Simple translation with optional context
 * - _x('text', $context, $locale, $domain) - Explicit contextual translation (same as __)
 * - _n('singular', 'plural', $number, $context, $locale, $domain) - Plural translation
 * - _nx('singular', 'plural', $number, $context, $locale, $domain) - Plural with context
 */

return [
    'contentNegotiation' => [
        // Enable browser language detection
        'enabled' => true,

        'redirect' => false,
    ],
    'cli.contentNegotiation.enabled' => false,
    'cli.contentNegotiation.redirect' => false,

    'arr' => [
        1 => [
            'isoCode' => 'en',
            'locale' => 'en_US',
            'localeCharset' => 'en_US.UTF-8',
            'name' => 'English',

            'privacyUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
            'cookieUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
        ],

        2 => [
            'isoCode' => 'it',
            'locale' => 'it_IT',
            'localeCharset' => 'it_IT.UTF-8',
            'name' => 'Italiano',

            'privacyUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
            'cookieUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
        ],

        /*3 => [
            'isoCode' => 'fr',
            'locale' => 'fr_FR',
            'localeCharset' => 'fr_FR.UTF-8',
            'name' => 'Français',

            'privacyUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
            'cookieUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
        ],*/
    ],

    'fallbackId' => 1,

    /*
     * TRANSLATION DOMAINS
     *
     * Domains are separate collections of translations, each typically stored in separate files.
     * The first domain in this array becomes the default domain for all translations.
     *
     * Examples:
     * - 'messages': Main application translations (default)
     * - 'api': API-specific messages
     * - 'email': Email template translations
     * - 'admin': Administrative interface translations
     *
     * Each domain can have its own filename pattern (see filename_patterns below).
     */
    'domains' => [
        'messages',   // Default domain - will be used when no domain is specified
        // 'api',     // API domain: app/locale/api/en_US.mo (if using subdirectories)
        // 'email',   // Email domain: app/locale/email/en_US.mo
        // 'admin',   // Admin domain: app/locale/admin/en_US.mo
    ],

    /*
     * TRANSLATION CONTEXTS
     *
     * Contexts allow the same text to have different translations based on usage.
     * This uses gettext's msgctxt feature to disambiguate identical strings.
     *
     * Examples:
     * - 'default': No specific context (can be omitted in function calls)
     * - 'male': Male-specific translations (e.g., "Dear Mr.")
     * - 'female': Female-specific translations (e.g., "Dear Ms.")
     * - 'formal': Formal tone translations
     * - 'informal': Informal tone translations
     * - 'noun': When a word is used as a noun
     * - 'verb': When the same word is used as a verb
     *
     * Usage in code:
     * - __('Post', 'noun') vs __('Post', 'verb')
     * - _x('Dear', 'male') vs _x('Dear', 'female')
     *
     * Usage in .po files:
     * msgctxt "noun"
     * msgid "Post"
     * msgstr "Articolo"
     *
     * msgctxt "verb"
     * msgid "Post"
     * msgstr "Pubblicare"
     *
     * To disable context support, set this to ['default'] only.
     */
    'contexts' => [
        'default',
        'male',
        'female',
        // 'formal',
        // 'informal',
        // 'noun',
        // 'verb',
        // Add more contexts as needed
    ],

    // Translation file loaders configuration
    'loaders' => [
        // Which loaders to enable (in order of preference)
        'enabled' => [
            'mo',      // Compiled gettext files (fastest)
            // 'po',   // Source gettext files (for development)
            // 'php',  // PHP array files
            // 'yaml', // YAML files
            // 'json', // JSON files
        ],

        // Priority order for file formats (first found wins if use_priority is true)
        'priority' => [
            'mo',      // Fastest, use in production
            // 'po',      // Good for development/debugging
            // 'php',     // Simple PHP arrays
            // 'yaml',    // Human-readable
            // 'json',    // Web-friendly
            // 'xliff', // XML-based
            // 'csv',   // Spreadsheet-friendly
            // 'ini',   // Simple key=value
        ],

        // Whether to use priority (first format found wins) or load all formats
        'use_priority' => true,
    ],

    // Locale directory
    'locale_dir' => _ROOT.'/app/locale',

    // Default filename pattern
    // Available placeholders: {locale}, {domain}, {extension}
    'filename_pattern' => '{locale}.{extension}',

    /*
     * DOMAIN-SPECIFIC FILENAME PATTERNS
     *
     * Customize filename patterns for specific domains.
     * This allows organizing translation files in subdirectories or with custom naming.
     *
     * Examples:
     * - Single file with contexts: '{locale}.{extension}' (uses msgctxt within file)
     * - Separate files per context: '{locale}.{context}.{extension}' (separate files)
     * - Subdirectories: '{domain}/{locale}.{extension}' (organized by domain)
     *
     * Pattern placeholders:
     * - {locale}: Language locale (e.g., en_US, it_IT)
     * - {domain}: Translation domain (e.g., messages, api, email)
     * - {extension}: File extension (e.g., mo, po, php)
     */
    'filename_patterns' => [
        'messages' => '{locale}.{extension}',                    // Main: app/locale/en_US.mo
        // 'api' => 'api/{locale}.{extension}',                 // API: app/locale/api/en_US.mo
        // 'email' => 'email/{locale}.{extension}',             // Email: app/locale/email/en_US.mo
        // 'admin' => 'admin/{locale}.{extension}',             // Admin: app/locale/admin/en_US.mo

        /*
         * FOR SEPARATE CONTEXT FILES (Alternative approach):
         * Uncomment and modify these if you want separate files per context
         * instead of using msgctxt within the same file:
         *
         * 'messages.male' => '{locale}.male.{extension}',     // app/locale/en_US.male.mo
         * 'messages.female' => '{locale}.female.{extension}', // app/locale/en_US.female.mo
         */
    ],

    'listeners' => [
        'dynamic_translation' => [
            // Track translations with dynamic content (variables, function calls, etc.)
            'enabled' => false,

            // File for dynamic translations (variables passed to translation functions)
            'file' => \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/cache/dynamic_translation.php'),
        ],
        'missing_translation' => [
            // Track missing translations for easier identification
            'enabled' => false,

            // Missing translations file
            'file' => \Safe\preg_replace('~/+~', '/', _ROOT.'/var/'.($_SERVER['APP_ENV'] ?? null).'/cache/missing_translation.php'),

            // Source languages - languages in which the original code is written.
            // Missing translations will not be reported for these languages.
            // If empty, fallback language from 'fallbackId' will be used.
            'source_languages' => [
                'en_US',
                'en_US.UTF-8',
                'en_GB',
                'en_GB.UTF-8',
                'en',
            ],

            // Additional settings to reduce false positives
            'verify_translation_exists' => true, // Double-check if translation exists before marking as missing
            'throttle_duplicates' => true,       // Prevent duplicate entries per request
        ],
    ],

    // Environment-specific overrides
    'front' => [
        'arr' => [
            1 => [
                'isoCode' => 'en',
                'locale' => 'en_US',
                'localeCharset' => 'en_US.UTF-8',
                'name' => 'English',

                'privacyUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
                'cookieUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
            ],

            /*2 => [
                'isoCode' => 'it',
                'locale' => 'it_IT',
                'localeCharset' => 'it_IT.UTF-8',
                'name' => 'Italiano',

                'privacyUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
                'cookieUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
            ],*/

            /*3 => [
                'isoCode' => 'fr',
                'locale' => 'fr_FR',
                'localeCharset' => 'fr_FR.UTF-8',
                'name' => 'Français',

                'privacyUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
                'cookieUrl' => 'https://www.unibo.it/en/university/privacy-policy-and-legal-notes/privacy-policy/personal-data-processing',
            ],*/
        ],

        // Frontend: Only MO for performance
        'loaders' => [
            'enabled' => ['mo'],
            'priority' => ['mo'],
            'use_priority' => true,
        ],
    ],

    'cli' => [
        // CLI: Both formats for translation management tools
        'loaders' => [
            'enabled' => ['po', 'mo'], // CLI: PO first for debugging
            'priority' => ['po', 'mo'], // PO preferred for CLI tools
            'use_priority' => false,    // Load both for comparison/validation
        ],
    ],

    'api' => [
        // API: Only MO for performance
        'loaders' => [
            'enabled' => ['mo'],
            'priority' => ['mo'],
            'use_priority' => true,
        ],
    ],
];
