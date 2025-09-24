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

namespace App\Factory\Breadcrumb;

use App\Model\Model;
use Creitive\Breadcrumbs\Breadcrumbs;

// https://designpatternsphp.readthedocs.io/en/latest/README.html
class BreadcrumbFactory extends Model implements BreadcrumbInterface
{
    protected ?Breadcrumbs $instance = null;

    public function __call($name, $args)
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. Call create() first.', $this->getShortName()));
        }

        return \call_user_func_array([$this->instance, $name], $args);
    }

    public function getInstance(): ?Breadcrumbs
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. It should be initialized via container factory.', $this->getShortName()));
        }

        return $this->instance;
    }

    // https://stackoverflow.com/a/57102716/3929620
    public function create(): self
    {
        if (null !== $this->instance) {
            return $this; // Already initialized
        }

        $this->instance = new Breadcrumbs();

        if (($cssClasses = $this->getConfigWithFallback('cssClasses')) !== false) {
            $this->setCssClasses($cssClasses);
        }

        if (($listItemCssClass = $this->getConfigWithFallback('listItemCssClass', false)) !== false) {
            $this->setListItemCssClass($listItemCssClass);
        }

        if (!\is_bool($divider = $this->getConfigWithFallback('divider'))) {
            $this->setDivider($divider);
        }

        return $this;
    }

    public function setBreadcrumbs($breadcrumbs)
    {
        $this->removeAll();

        return \call_user_func_array([$this->instance, __FUNCTION__], [$breadcrumbs]);
    }

    /**
     * Get configuration value with environment fallback using existing ConfigManager.
     *
     * @param null|mixed $default
     */
    public function getConfigWithFallback(string $suffix = '', $default = null, ?array $prefixes = null)
    {
        $env = $this->container->get('env');

        $prefixes ??= [
            "breadcrumb.{$env}",
            'breadcrumb',
        ];

        return $this->config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }
}
