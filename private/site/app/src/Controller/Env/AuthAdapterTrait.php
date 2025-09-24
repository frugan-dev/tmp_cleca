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

namespace App\Controller\Env;

use App\Helper\HelperInterface;
use Laminas\Authentication\Result as AuthenticationResult;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

trait AuthAdapterTrait
{
    private array $identityTypes = [];

    private $username;

    private $password;

    private $force = false;

    public function __construct(
        protected ContainerInterface $container,
        protected EventDispatcherInterface $dispatcher,
        protected HelperInterface $helper,
    ) {}

    public function setIdentityTypes(?array $identityTypes): void
    {
        $this->identityTypes = $identityTypes;
    }

    public function setCredentials(string $username, string $password, ?bool $force = false): void
    {
        $this->username = $username;
        $this->password = $password;
        $this->force = $force;
    }

    public function authenticate(): AuthenticationResult
    {
        $code = AuthenticationResult::FAILURE_UNCATEGORIZED;
        $messages = [
            __('A technical problem has occurred, try again later.'),
        ];
        $identity = null;

        if (!empty($this->identityTypes)) {
            foreach ($this->identityTypes as $identityType) {
                $namespace = 'Mod\\'.ucfirst((string) $identityType).'\\'.ucfirst((string) static::$env);

                if ($this->container->has($namespace)) {
                    $Mod = $this->container->get($namespace);

                    if (method_exists($Mod, '_'.__FUNCTION__) && \is_callable([$Mod, '_'.__FUNCTION__])) {
                        $return = \call_user_func_array([$Mod, '_'.__FUNCTION__], [$this->username, $this->password, $this->force]);

                        if (!$return instanceof AuthenticationResult) {
                            throw new \Exception("'{$return}' must be an instance of AuthenticationResult");
                        }

                        if ($return->isValid()) {
                            break;
                        }
                    }
                }
            }
        }

        $this->force = false;

        return $return ?? new AuthenticationResult($code, $identity, $messages);
    }
}
