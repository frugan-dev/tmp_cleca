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

namespace App\Factory\Db;

class RPDO extends \RedBeanPHP\Driver\RPDO
{
    /**
     * Constructor. You may either specify dsn, user and password or
     * just give an existing PDO connection.
     *
     * Usage:
     *
     * <code>
     * $driver = new RPDO( $dsn, $user, $password );
     * </code>
     *
     * The example above illustrates how to create a driver
     * instance from a database connection string (dsn), a username
     * and a password. It's also possible to pass a PDO object.
     *
     * Usage:
     *
     * <code>
     * $driver = new RPDO( $existingConnection );
     * </code>
     *
     * The second example shows how to create an RPDO instance
     * from an existing PDO object.
     *
     * @param object|string $dsn            database connection string
     * @param string        $user           optional, usename to sign in
     * @param string        $pass           optional, password for connection login
     * @param mixed         $connectOptions
     */
    public function __construct($dsn, $user = null, $pass = null, protected $connectOptions = [])
    {
        parent::__construct($dsn, $user, $pass);
    }

    /**
     * Establishes a connection to the database using PHP\PDO
     * functionality. If a connection has already been established this
     * method will simply return directly. This method also turns on
     * UTF8 for the database and PDO-ERRMODE-EXCEPTION as well as
     * PDO-FETCH-ASSOC.
     */
    #[\Override]
    public function connect(): void
    {
        if ($this->isConnected) {
            return;
        }

        try {
            $user = $this->connectInfo['user'];
            $pass = $this->connectInfo['pass'];
            $this->pdo = new \PDO($this->dsn, $user, $pass, $this->connectOptions);
            $this->setEncoding();
            $this->pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, $this->stringifyFetches);
            // cant pass these as argument to constructor, CUBRID driver does not understand...
            $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            $this->isConnected = true;
            // run initialisation query if any
            if (null !== $this->initSQL) {
                $this->Execute($this->initSQL);
                $this->initSQL = null;
            }
        } catch (\PDOException $exception) {
            $matches = [];
            $dbname = (\Safe\preg_match('/dbname=(\w+)/', $this->dsn, $matches)) ? $matches[1] : '?';

            throw new \PDOException('Could not connect to database ('.$dbname.').', $exception->getCode());
        }
    }
}
