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

use App\Factory\Logger\LoggerInterface;
use App\Model\Model;
use Psr\Container\ContainerInterface;
use RedBeanPHP\Adapter\DBAdapter;
use RedBeanPHP\Facade as R;
use RedBeanPHP\OODB;
use RedBeanPHP\QueryWriter;
use RedBeanPHP\RedException;
use RedBeanPHP\ToolBox;

// https://designpatternsphp.readthedocs.io/en/latest/README.html
class DbFactory extends Model implements DbInterface
{
    public array $dbKeys = [];
    public array $dbConfigs = []; // Cache for database configurations

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger
    ) {}

    // https://github.com/gabordemooij/redbean/issues/439#issuecomment-97981695
    public function __call($name, $args)
    {
        // https://stackoverflow.com/a/2344044/3929620
        // forward_static_call_array forwards the static context to the method that's called,
        // while call_user_func_array doesn't
        return forward_static_call_array([R::class, $name], $args);
    }

    public function create(): self
    {
        $this->setDbKeys();

        if (!empty($this->dbKeys)) {
            $primaryDbKey = $this->getPrimaryDbKey();

            foreach ($this->dbKeys as $dbKey) {
                if (!R::hasDatabase($dbKey)) {
                    $this->setupDatabase($dbKey);
                }
            }

            // Return to the primary database if we switched
            if ($primaryDbKey !== $dbKey) {
                R::selectDatabase($primaryDbKey);
            }

            // Load plugins if available
            $this->loadDatabasePlugins();
        }

        if (!R::testConnection()) {
            throw new RedException('Unable to connect to database.');
        }

        $this->logger->addDatabaseHandler($this);

        return $this;
    }

    /**
     * Get the primary (first) database key.
     */
    public function getPrimaryDbKey(): int
    {
        if (empty($this->dbKeys)) {
            throw new RedException('No database configurations available');
        }

        return $this->dbKeys[0]; // First key (already sorted in setDbKeys)
    }

    /**
     * Get database configuration for a specific database key.
     */
    public function getDbConfig(int $dbKey): ?array
    {
        if (isset($this->dbConfigs[$dbKey])) {
            return $this->dbConfigs[$dbKey];
        }

        $config = $this->config->get("db.{$dbKey}");

        if (\is_array($config)) {
            $this->dbConfigs[$dbKey] = $config;

            return $config;
        }

        return null;
    }

    /**
     * Get all available database keys.
     */
    public function getDbKeys(): array
    {
        return $this->dbKeys;
    }

    /**
     * Check if a database configuration exists and is valid.
     */
    public function isDbConfigValid(int $dbKey): bool
    {
        $config = $this->getDbConfig($dbKey);

        if (!$config) {
            return false;
        }

        // Check required fields
        $required = ['driver', 'host', 'dbname', 'user', 'password'];

        foreach ($required as $field) {
            if (!isset($config[$field]) || empty($config[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get the prefix for a specific database and module.
     * If no dbKey is specified, uses the primary database key.
     */
    public function getPrefix(?string $modName = null, ?int $dbKey = null): string
    {
        $dbKey ??= $this->getPrimaryDbKey();

        // Try module-specific prefix first
        if ($modName) {
            $modPrefix = $this->config->get("mod.{$modName}.db.{$dbKey}.prefix");
            if (null !== $modPrefix) {
                return $modPrefix;
            }
        }

        // Fallback to database default prefix
        return $this->config->get("db.{$dbKey}.prefix", '');
    }

    public function getLogs($separator = PHP_EOL): bool|string|null
    {
        if (null !== ($logger = $this->getLogger())) {
            $logs = $logger->getLogs();

            if (\is_array($logs)) {
                $out = implode($separator, $logs);

                return \Safe\preg_replace('/resultset\: (\d+) rows/', 'resultset: $1 rows'.$separator, $out);
            }
        }

        return false;
    }

    /**
     * Set up database keys by scanning the configuration for numeric database identifiers.
     */
    private function setDbKeys(): void
    {
        $dbConfig = $this->config->get('db');

        if (!\is_array($dbConfig)) {
            $this->logger->warning('Database configuration is not available or not an array');

            return;
        }

        $this->dbKeys = [];

        foreach ($dbConfig as $key => $value) {
            // Check if the key is numeric (represents a database ID)
            if (is_numeric($key) && \is_array($value)) {
                $dbKey = (int) $key;

                // Validate that this is a proper database configuration
                if ($this->isValidDbConfig($value)) {
                    if (!\in_array($dbKey, $this->dbKeys, true)) {
                        $this->dbKeys[] = $dbKey;
                    }
                } else {
                    $this->logger->warning("Invalid database configuration for key: {$dbKey}");
                }
            }
        }

        // Sort database keys to ensure consistent order
        sort($this->dbKeys);

        if (empty($this->dbKeys)) {
            $this->logger->warning('No valid database configurations found');
        } else {
            $this->logger->debugInternal('Found database configurations', ['keys' => $this->dbKeys]);
        }
    }

    /**
     * Validate if a configuration array contains valid database settings.
     */
    private function isValidDbConfig(array $config): bool
    {
        $required = ['driver', 'host', 'dbname', 'user'];

        foreach ($required as $field) {
            if (!isset($config[$field])) {
                return false;
            }
        }

        // Driver should be one of the supported types
        $supportedDrivers = ['mysql', 'mariadb', 'postgresql', 'sqlite', 'cubrid'];
        if (!\in_array($config['driver'], $supportedDrivers, true)) {
            return false;
        }

        return true;
    }

    /**
     * Set up a specific database connection.
     */
    private function setupDatabase(int $dbKey): void
    {
        $config = $this->getDbConfig($dbKey);

        if (!$config) {
            throw new RedException("Database configuration for key '{$dbKey}' not found");
        }

        if (!$this->isDbConfigValid($dbKey)) {
            throw new RedException("Invalid database configuration for key '{$dbKey}'");
        }

        match ($config['driver']) {
            'mysql', 'mariadb' => $this->setupMysqlDatabase($dbKey, $config),
            'postgresql' => $this->setupPostgresqlDatabase($dbKey, $config),
            'sqlite' => $this->setupSqliteDatabase($dbKey, $config),
            default => throw new RedException("Unsupported database driver: {$config['driver']}"),
        };
    }

    /**
     * Set up MySQL/MariaDB database connection.
     */
    private function setupMysqlDatabase(int $dbKey, array $config): void
    {
        $dsn = $config['driver'];
        $dsn .= ':host='.$config['host'];
        $dsn .= ';dbname='.$config['dbname'];
        $dsn .= ';port='.($config['port'] ?? 3306);
        $dsn .= ';charset='.($config['charset'] ?? 'utf8mb4');

        $driverOptions = $config['driverOptions'] ?? [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => true,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
        ];

        // R::setup( $dsn, $config['user'], $config['password'] );

        // Note that createToolbox() will not immediately establish a connection to the database.
        // Instead, it will prepare the connection and connect 'lazily', i.e. the moment
        // a connection is really required, for instance when attempting to load a bean.
        // R::createToolbox( $dsn, $config['user'], $config['password'], $this->config->get('db.frozen.enabled') );
        // R::useMysqlSSL( $config['ssl.client.key'], $config['ssl.client.cert'], $config['ssl.ca.cert']);

        $pdo = new RPDO($dsn, $config['user'], $config['password'], $driverOptions);
        $adapter = new DBAdapter($pdo);
        $writer = new QueryWriter\MySQL($adapter);
        $oodb = new OODB($writer, $this->config->get('db.frozen.enabled', false));

        R::$toolboxes[$dbKey] = new ToolBox($oodb, $adapter, $writer);
        R::configureFacadeWithToolbox(R::$toolboxes[$dbKey]);

        R::selectDatabase($dbKey);

        // Configure PDO attributes
        // https://stackoverflow.com/a/20123337/3929620
        // https://stackoverflow.com/a/22499259/3929620
        // https://stackoverflow.com/a/40682033/3929620
        R::getPDO()->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        R::getPDO()->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);

        // Set timezone if specified
        if (!empty($config['timeZone'])) {
            R::exec('SET time_zone = ?', [$config['timeZone']]);
        }

        $this->configureDatabaseOptions($dbKey);
    }

    /**
     * Set up PostgreSQL database connection.
     */
    private function setupPostgresqlDatabase(int $dbKey, array $config): void
    {
        $dsn = 'pgsql:';
        $dsn .= 'host='.$config['host'];
        $dsn .= ';dbname='.$config['dbname'];
        $dsn .= ';port='.($config['port'] ?? 5432);

        $driverOptions = $config['driverOptions'] ?? [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $pdo = new RPDO($dsn, $config['user'], $config['password'], $driverOptions);
        $adapter = new DBAdapter($pdo);
        $writer = new QueryWriter\PostgreSQL($adapter);
        $oodb = new OODB($writer, $this->config->get('db.frozen.enabled', false));

        R::$toolboxes[$dbKey] = new ToolBox($oodb, $adapter, $writer);
        R::configureFacadeWithToolbox(R::$toolboxes[$dbKey]);

        R::selectDatabase($dbKey);

        $this->configureDatabaseOptions($dbKey);
    }

    /**
     * Set up SQLite database connection.
     */
    private function setupSqliteDatabase(int $dbKey, array $config): void
    {
        $dsn = 'sqlite:'.$config['dbname']; // For SQLite, dbname is the file path

        $driverOptions = $config['driverOptions'] ?? [
            PDO::ATTR_PERSISTENT => false,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        $pdo = new RPDO($dsn, null, null, $driverOptions);
        $adapter = new DBAdapter($pdo);
        $writer = new QueryWriter\SQLiteT($adapter);
        $oodb = new OODB($writer, $this->config->get('db.frozen.enabled', false));

        R::$toolboxes[$dbKey] = new ToolBox($oodb, $adapter, $writer);
        R::configureFacadeWithToolbox(R::$toolboxes[$dbKey]);

        R::selectDatabase($dbKey);

        $this->configureDatabaseOptions($dbKey);
    }

    /**
     * Configure database-specific options like debug mode and frozen state.
     */
    private function configureDatabaseOptions(int $dbKey): void
    {
        // Set debug mode
        R::debug(
            $this->config->get('db.debug.enabled', false),
            $this->config->get('db.debug.mode', 0)
        );

        // Set frozen mode
        $frozenEnabled = $this->config->get('db.frozen.enabled', false);
        $frozenTypes = $this->config->get('db.frozen.types', []);

        // https://stackoverflow.com/a/28844889/3929620
        R::freeze(!empty($frozenEnabled) && !empty($frozenTypes) ? $frozenTypes : $frozenEnabled);
    }

    /**
     * Load database plugins if available.
     */
    private function loadDatabasePlugins(): void
    {
        $pluginDir = __DIR__.'/plugin';

        if (is_dir($pluginDir)) {
            foreach (\Safe\glob($pluginDir.'/*.php') as $file) {
                include_once $file;
            }
        }
    }
}
