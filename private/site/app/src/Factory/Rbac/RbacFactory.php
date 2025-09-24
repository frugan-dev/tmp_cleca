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

namespace App\Factory\Rbac;

use App\Factory\Auth\AuthInterface;
use App\Helper\HelperInterface;
use App\Model\Model;
use Laminas\Authentication\AuthenticationServiceInterface;
use Laminas\Permissions\Rbac\Rbac;
use Laminas\Permissions\Rbac\Role;
use Psr\Container\ContainerInterface;

// https://designpatternsphp.readthedocs.io/en/latest/README.html
class RbacFactory extends Model implements RbacInterface
{
    protected ?Rbac $instance = null;

    protected Role $role;

    public function __construct(
        protected ContainerInterface $container,
        protected AuthInterface $auth,
        protected HelperInterface $helper,
    ) {}

    public function __call($name, $args)
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. Call create() first.', $this->getShortName()));
        }

        return \call_user_func_array([$this->instance, $name], $args);
    }

    public function getInstance(): ?Rbac
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. It should be initialized via container factory.', $this->getShortName()));
        }

        return $this->instance;
    }

    public function create(): self
    {
        if (null !== $this->instance) {
            return $this; // Already initialized
        }

        $this->instance = new Rbac();

        return $this;
    }

    public function isGranted(string $permission, $assertion = null): bool
    {
        if ($this->helper->Env()->isCli()) {
            return true;
        }

        if ($this->auth->getInstance() instanceof AuthenticationServiceInterface) {
            if ($this->auth->hasIdentity()) {
                if (!empty($this->auth->getIdentity()['_role_type'])) {
                    $role_type = $this->auth->getIdentity()['_role_type'];

                    if (!empty($this->auth->getIdentity()[$role_type.'_id'])
                        && !empty($this->auth->getIdentity()[$role_type.'_perms'])) {
                        $role = $role_type.'-'.$this->auth->getIdentity()[$role_type.'_id'];
                        $perms = \is_string($this->auth->getIdentity()[$role_type.'_perms']) ? $this->helper->Nette()->Json()->decode((string) $this->auth->getIdentity()[$role_type.'_perms'], forceArrays: true) : $this->auth->getIdentity()[$role_type.'_perms'];

                        if (!$this->instance->hasRole($role)) {
                            $this->role = new Role($role);

                            if ((is_countable($perms) ? \count($perms) : 0) > 0) {
                                foreach ($perms as $perm) {
                                    $this->role->addPermission($perm);
                                }
                            }

                            $this->instance->addRole($this->role);
                        }

                        if ($this->instance->hasRole($role)) {
                            if ($this->instance->isGranted($role, $permission, $assertion)) {
                                return true;
                            }
                        }
                    }
                }
            }
        }

        return false;
    }
}
