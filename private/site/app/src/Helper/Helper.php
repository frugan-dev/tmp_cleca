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

namespace App\Helper;

use App\Factory\Logger\LoggerInterface;
use App\Model\Model;
use Psr\Container\ContainerInterface;

class Helper extends Model implements HelperInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger
    ) {
        // required to initialize $this->app
        parent::__construct($container);
    }

    public function __call($name, $args)
    {
        $namespace = ucwords($this->getNamespaceName().'\\'.$name, '\\');

        if (class_exists($namespace)) {
            if (empty($args)) {
                $args = [$this->container];
            }

            if (!$this->container->has($namespace)) {
                // https://stackoverflow.com/a/8735314/3929620
                $reflection = new \ReflectionClass($namespace);
                $this->container->set($namespace, $reflection->newInstanceArgs($args));
            }

            return $this->container->get($namespace);
        }

        return false;
    }
}
