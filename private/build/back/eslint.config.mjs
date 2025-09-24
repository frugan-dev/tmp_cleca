import typescriptEslint from '@typescript-eslint/eslint-plugin';
import prettier from 'eslint-plugin-prettier';
import unicorn from 'eslint-plugin-unicorn';
import tsParser from '@typescript-eslint/parser';
import js from '@eslint/js';
import globals from 'globals';

export default [
  {
    ignores: ['dist/**', 'node_modules/**'],
  },
  js.configs.recommended,
  {
    files: ['**/*.{js,mjs,cjs,ts}'],
    plugins: {
      '@typescript-eslint': typescriptEslint,
      prettier,
      unicorn,
    },
    languageOptions: {
      parser: tsParser,
      ecmaVersion: 'latest',
      sourceType: 'module',
      globals: {
        ...globals.browser,
        ...globals.es2021,
        jsObj: 'readonly',
        App: 'writable',
        // Libraries
        Waypoint: 'readonly',
        browserUpdate: 'readonly', 
        Fancybox: 'readonly',
        LazyLoad: 'readonly',
        Popover: 'readonly',
        grecaptcha: 'readonly',
        Offcanvas: 'readonly',
        Modernizr: 'readonly',
        tinymce: 'readonly',
        Tooltip: 'readonly',
        // Global instances
        lazyLoadInstance: 'writable',
        // Custom functions
        getPreferredTheme: 'readonly',
      },
    },
    rules: {
      ...typescriptEslint.configs.recommended.rules,
      ...prettier.configs.recommended.rules,
      ...unicorn.configs.recommended.rules,
      '@typescript-eslint/ban-ts-comment': 'off',
      '@typescript-eslint/no-explicit-any': 'off',
    },
  },
];