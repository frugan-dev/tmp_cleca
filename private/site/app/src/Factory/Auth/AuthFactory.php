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

namespace App\Factory\Auth;

use App\Factory\Auth\Adapter\AuthAdapterInterface;
use App\Factory\Logger\LoggerInterface;
use App\Helper\HelperInterface;
use App\Model\Model;
use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Authentication\Storage\Chain;
use Psr\Container\ContainerInterface;

// https://designpatternsphp.readthedocs.io/en/latest/README.html
class AuthFactory extends Model implements AuthInterface
{
    protected ?AuthenticationServiceInterface $instance = null;

    protected AuthAdapterInterface $adapter;

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger,
        protected HelperInterface $helper,
    ) {}

    public function __call($name, $args)
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. Call create() first.', $this->getShortName()));
        }

        return \call_user_func_array([$this->instance, $name], $args);
    }

    public function getInstance(): ?AuthenticationServiceInterface
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. It should be initialized via container factory.', $this->getShortName()));
        }

        return $this->instance;
    }

    public function create(AuthAdapterInterface $adapter, ?array $storages = [], ?array $identityTypes = []): self
    {
        if (null !== $this->instance) {
            return $this; // Already initialized
        }

        $this->instance = new AuthenticationService();

        if (!empty($storages)) {
            $chain = new Chain();
            $priority = 1;

            foreach ($storages as $namespace => $args) {
                $reflection = new \ReflectionClass($namespace);

                if (empty($args)) {
                    $args = [[]];
                }

                foreach ($args as $arg) {
                    $storage = $reflection->newInstanceArgs($arg);
                    $chain->add($storage, $priority);
                    --$priority;
                }
            }

            $this->instance->setStorage($chain);
        }

        $this->adapter = $adapter;

        $this->adapter->setIdentityTypes($identityTypes);

        $this->logger->addContextProcessor($this, $this->helper);
        $this->logger->addSentryHandler($this, $this->helper);

        return $this;
    }

    public function authenticate(string $username, string $password)
    {
        $this->adapter->setCredentials($username, $password);

        return \call_user_func_array([$this->instance, __FUNCTION__], [$this->adapter]);
    }

    public function forceAuthenticate(string $username)
    {
        $this->adapter->setCredentials($username, '', true);

        return \call_user_func_array([$this->instance, 'authenticate'], [$this->adapter]);
    }
}
