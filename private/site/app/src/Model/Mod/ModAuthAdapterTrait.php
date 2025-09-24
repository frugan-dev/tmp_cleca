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

namespace App\Model\Mod;

use Laminas\Authentication\Result as AuthenticationResult;
use Symfony\Component\EventDispatcher\GenericEvent;
use WhiteHat101\Crypt\APR1_MD5;

trait ModAuthAdapterTrait
{
    public string $authUsernameField = 'username';

    public string $authCheckField = 'username';

    public string $authPasswordField = 'password';

    public array $authNameFields = ['name'];

    public function _authenticate(string $username, string $password, ?bool $force = false): AuthenticationResult
    {
        $code = AuthenticationResult::FAILURE_IDENTITY_NOT_FOUND;
        $messages = [
            __('The entered credentials do not seem correct.'),
        ];
        $identity = null;

        $eventName = 'event.'.static::$env.'.'.$this->modName.'.getOne.where';
        $callback = function (GenericEvent $event) use ($username): void {
            // https://stackoverflow.com/a/5629121/3929620
            $this->dbData['sql'] .= ' AND BINARY a.'.$this->authCheckField.' = :'.$this->authCheckField;
            $this->dbData['sql'] .= ' AND c.active = :c_active';
            $this->dbData['args'][$this->authCheckField] = $username;
            $this->dbData['args']['c_active'] = 1;
        };

        $this->dispatcher->addListener($eventName, $callback);

        $row = $this->getOne([
            'id' => null,
            'active' => true,
        ]);

        $this->dispatcher->removeListener($eventName, $callback);

        if (!empty($row[$this->authPasswordField])) {
            $algorithm = $this->config['mod.'.static::$env.'.'.$this->modName.'.'.$this->authPasswordField.'.auth.password.hash.algorithm'] ?? $this->config['mod.'.$this->modName.'.'.$this->authPasswordField.'.auth.password.hash.algorithm'] ?? $this->config['mod.'.static::$env.'.'.$this->modName.'.auth.password.hash.algorithm'] ?? $this->config['mod.'.$this->modName.'.auth.password.hash.algorithm'] ?? $this->config['auth.'.static::$env.'.password.hash.algorithm'] ?? $this->config['auth.password.hash.algorithm'];

            $resp = match ($algorithm) {
                'NONE' => true,
                'APR1_MD5' => APR1_MD5::check($password, $row[$this->authPasswordField]),
                default => password_verify($password, (string) $row[$this->authPasswordField]),
            };

            if (!empty($force) || !empty($resp)) {
                $code = AuthenticationResult::SUCCESS;
                $messages = []; // If authentication was successful, this should be an empty array.

                unset($row[$this->authPasswordField]);

                $row['_username'] = $row[$this->authUsernameField] ?? null;
                $row['_name'] = implode(' ', array_filter($row, fn ($k) => \in_array($k, $this->authNameFields, true), ARRAY_FILTER_USE_KEY));
                $row['_type'] = $this->modName;
                $row['_role_type'] = 'cat'.$this->modName;

                $row['cat'.$this->modName.'_perms'] = !empty($row['cat'.$this->modName.'_perms']) ? $this->helper->Nette()->Json()->decode((string) $row['cat'.$this->modName.'_perms'], forceArrays: true) : [];

                $identity = $row;
            } else {
                $code = AuthenticationResult::FAILURE_CREDENTIAL_INVALID;
                $messages = [
                    __('The entered credentials do not seem correct.'),
                ];
            }
        }

        return new AuthenticationResult($code, $identity, $messages);
    }
}
