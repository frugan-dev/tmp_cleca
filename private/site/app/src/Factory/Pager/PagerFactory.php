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

namespace App\Factory\Pager;

use App\Model\Model;
use App\Service\Route\RouteParsingService;
use Kilte\Pagination\Pagination;
use Psr\Container\ContainerInterface;

// https://designpatternsphp.readthedocs.io/en/latest/README.html
class PagerFactory extends Model implements PagerInterface
{
    public int $totPages = 1;

    public int $totRows;

    public int $pg = 1;

    public int $rowPerPage;

    public int $neighbours = 2;

    protected ?Pagination $instance = null;

    public function __construct(
        protected ContainerInterface $container,
        protected RouteParsingService $routeParsingService,
    ) {}

    public function __call($name, $args)
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. Call create() first.', $this->getShortName()));
        }

        return \call_user_func_array([$this->instance, $name], $args);
    }

    public function getInstance(): ?Pagination
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. It should be initialized via container factory.', $this->getShortName()));
        }

        return $this->instance;
    }

    // https://stackoverflow.com/a/57102716/3929620
    public function create(?int $totRows = null, ?int $rowPerPage = null): self
    {
        $this->prepare($totRows, $rowPerPage);

        $this->instance = new Pagination(
            $this->totRows,
            $this->pg,
            $this->rowPerPage,
            $this->neighbours
        );

        return $this;
    }

    public function prepare(?int $totRows, ?int $rowPerPage): void
    {
        $this->totRows = $totRows ?? 1;
        $this->rowPerPage = $rowPerPage ?? $this->getConfigWithFallback('rowPerPage', 1);

        $this->totPages = (int) ceil($this->totRows / $this->rowPerPage);

        if (($params = $this->routeParsingService->getParamsString()) !== null) {
            $params = explode('/', $params);

            // https://www.php.net/manual/en/language.operators.arithmetic.php#120654
            // https://stackoverflow.com/a/9153969/3929620
            // $number % 2 === 0    -----> true -> even (pari), false -> odd (dispari)
            // $number & 1          -----> true -> odd (dispari), false -> even (pari)
            if (\count($params) & 1) {
                $lastParam = (int) end($params);

                if (\in_array($lastParam, array_map('intval', range(1, $this->totPages)), true)) {
                    $this->pg = $lastParam;
                }
            }
        }
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
            "pagination.{$env}",
            'pagination',
        ];

        return $this->config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }
}
