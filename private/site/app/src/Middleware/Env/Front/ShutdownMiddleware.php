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

namespace App\Middleware\Env\Front;

use Middlewares\ShutdownRender;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;

class ShutdownMiddleware extends \App\Middleware\ShutdownMiddleware implements MiddlewareInterface
{
    #[\Override]
    public function renderCallback(ServerRequestInterface $request): string
    {
        try {
            $customTemplate = $this->getCustomTemplate();

            if (null !== $customTemplate) {
                return $this->renderCustomTemplate($customTemplate, $request);
            }

            // Fallback to default middleware render
            return (string) (new ShutdownRender())($request);
        } catch (\Throwable $e) {
            $this->logger->error('Frontend shutdown render failed', [
                'exception' => $e,
                'error' => $e->getMessage(),
                'text' => $e->getTraceAsString(),
            ]);

            // Ultimate fallback
            $contentType = $this->detectContentType($request);

            return $this->getBasicMaintenanceMessage();
        }
    }

    /**
     * Get custom template file path if configured and exists.
     */
    protected function getCustomTemplate(): ?string
    {
        $templateFile = $this->getConfigWithFallback('page.file');

        if (empty($templateFile)) {
            return null;
        }

        $templatePath = $this->buildSecureTemplatePath($templateFile);

        if (!$templatePath || !file_exists($templatePath) || !is_readable($templatePath)) {
            $this->logger->warning('Shutdown template file not found or not readable', [
                'configured_file' => $templateFile,
                'resolved_path' => $templatePath,
            ]);

            return null;
        }

        return $templatePath;
    }

    /**
     * Build secure template file path using realpath to prevent directory traversal.
     */
    protected function buildSecureTemplatePath(string $templateFile): ?string
    {
        $baseDir = _ROOT.'/app/view/'.static::$env.'/base';

        // Ensure base directory exists
        if (!is_dir($baseDir)) {
            return null;
        }

        $baseDirReal = \Safe\realpath($baseDir);
        if (false === $baseDirReal) {
            return null;
        }

        // Build the full path
        $fullPath = $baseDirReal.\DIRECTORY_SEPARATOR.$templateFile;
        $fullPathReal = \Safe\realpath($fullPath);

        // Security check: ensure the resolved path is within the base directory
        if (false === $fullPathReal || !str_starts_with($fullPathReal, $baseDirReal.\DIRECTORY_SEPARATOR)) {
            $this->logger->warning('Attempted directory traversal in template path', [
                'template_file' => $templateFile,
                'base_dir' => $baseDirReal,
                'attempted_path' => $fullPath,
            ]);

            return null;
        }

        return $fullPathReal;
    }

    /**
     * Render custom template with variable substitution.
     */
    protected function renderCustomTemplate(string $templatePath, ServerRequestInterface $request): string
    {
        $content = \Safe\file_get_contents($templatePath);

        if (false === $content) {
            throw new \RuntimeException("Failed to read template file: {$templatePath}");
        }

        $variables = $this->buildTemplateVariables($request);

        return strtr($content, $variables);
    }

    /**
     * Build array of variables for template substitution.
     */
    protected function buildTemplateVariables(ServerRequestInterface $request): array
    {
        $variables = [];

        // Add language variable
        if (isset($this->lang) && !empty($this->lang->code)) {
            $variables['{lang}'] = $this->lang->code;
        }

        // Add company configuration variables using direct config access
        $companyConfig = $this->config->get('company', []);
        foreach ($companyConfig as $key => $value) {
            // Convert newlines to <br> for address fields
            if (\is_string($value) && str_contains((string) $key, 'address')) {
                $value = str_replace(["\r\n", "\n", "\r"], '<br>', $value);
            }

            $variables['{company.'.$key.'}'] = (string) $value;
        }

        // Add brand configuration variables
        $brandConfig = $this->config->get('brand', []);
        foreach ($brandConfig as $key => $value) {
            $variables['{brand.'.$key.'}'] = (string) $value;
        }

        // Add Google Analytics if enabled
        $variables['{ga}'] = $this->getGoogleAnalyticsCode($request);

        // Add custom page variables from config
        $customVars = $this->getConfigWithFallback('page.vars');
        if (\is_array($customVars)) {
            $variables = array_merge($variables, $customVars);
        }

        return $variables;
    }

    /**
     * Get Google Analytics code if enabled.
     */
    protected function getGoogleAnalyticsCode(ServerRequestInterface $request): string
    {
        if (!$request->getAttribute('loadGA', false)) {
            return '';
        }

        $env = $this->container->get('env');

        // Try environment-specific GA code first, then general
        $gaCode = $this->getConfigWithFallback('google.analytics.code', prefixes: [
            "service.{$env}",
            'service',
        ]);

        if (empty($gaCode)) {
            return '';
        }

        // Try to load GA partial template
        $gaPartialPath = _ROOT.'/app/view/'.static::$env.'/partial/ga.html';

        if (file_exists($gaPartialPath) && is_readable($gaPartialPath)) {
            $gaTemplate = \Safe\file_get_contents($gaPartialPath);
            if (false !== $gaTemplate) {
                return strtr($gaTemplate, [
                    '{service.google.analytics.code}' => $gaCode,
                ]);
            }
        }

        // Fallback to just the GA code
        return $gaCode;
    }
}
