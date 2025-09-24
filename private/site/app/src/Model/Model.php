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

namespace App\Model;

use Psr\Container\ContainerInterface;
use Slim\App;

class Model
{
    protected App $app;

    public function __construct(
        protected ContainerInterface $container
    ) {
        $this->app = $this->container->get(App::class);
    }

    public function __get($name)
    {
        return $this->container->get($name);
    }

    public function getName()
    {
        return new \ReflectionClass(static::class)->getName();
    }

    public function getNamespaceName()
    {
        return new \ReflectionClass(static::class)->getNamespaceName();
    }

    public function getShortName()
    {
        return new \ReflectionClass(static::class)->getShortName();
    }

    public function getFileName()
    {
        return new \ReflectionClass(static::class)->getFileName();
    }
}
