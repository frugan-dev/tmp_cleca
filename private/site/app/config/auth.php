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

return [
    'path' => $_ENV['AUTH_PATH'],

    'username.minLength' => 3,

    'password.minLength' => 20,

    // https://github.com/nette/utils/blob/master/src/Utils/Random.php#L25
    'password.charlist' => '0-9a-zA-Z_|!$%&/()=?^[]{}*@#.:,;<>+-',

    /*
     * https://www.php.net/manual/en/password.constants.php
     * https://doc.nette.org/en/3.1/passwords
     *
     * The default is PASSWORD_DEFAULT, so the algorithm choice is left to PHP.
     * Algorithm may change in newer PHP releases when newer, stronger hashing algorithms are supported.
     * Therefore you should be aware that the length of the resulting hash can change.
     * Therefore you should store the resulting hash in a way that can store enough characters, 255 is the recommended width.
     */
    'password.hash.algorithm' => PASSWORD_DEFAULT,
    'api.password.hash.algorithm' => 'NONE',

    /*
     * This is how you'd use the bcrypt algorithm and change the hashing speed using the cost parameter from the default 10.
     * In year 2020, with cost 10, the hashing of one password takes roughly 80ms, cost 11 takes 160ms, cost 12 then 320ms, the scale is logarithmic.
     * The slower the better, cost 10–12 is considered slow enough for most use cases.
     */
    'password.hash.options' => [
        'cost' => 10,
    ],

    'privateKey.minLength' => 50,
];
