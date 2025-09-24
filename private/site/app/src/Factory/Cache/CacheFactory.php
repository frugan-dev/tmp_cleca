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

namespace App\Factory\Cache;

use App\Factory\Db\DbInterface;
use App\Factory\Logger\LoggerInterface;
use App\Factory\Translator\TranslatorInterface;
use App\Helper\HelperInterface;
use App\Model\Model;
use Psr\Container\ContainerInterface;
use Psr\SimpleCache\CacheInterface as PsrCacheInterface;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;
use Symfony\Component\Cache\Adapter\NullAdapter;
use Symfony\Component\Cache\Adapter\PdoAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;

// https://designpatternsphp.readthedocs.io/en/latest/README.html
class CacheFactory extends Model implements CacheInterface
{
    public bool $taggable = false;

    protected ?AdapterInterface $instance = null;

    protected ?string $adapter = null;

    protected int $expireSeconds = 0;

    public function __construct(
        protected ContainerInterface $container,
        protected DbInterface $db,
        protected HelperInterface $helper,
        protected LoggerInterface $logger,
    ) {}

    public function __call($name, $args)
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. Call create() first.', $this->getShortName()));
        }

        return \call_user_func_array([$this->instance, $name], $args);
    }

    public function getInstance(): ?AdapterInterface
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. It should be initialized via container factory.', $this->getShortName()));
        }

        return $this->instance;
    }

    public function create(?string $adapter = null): self
    {
        if (null !== $this->instance) {
            return $this; // Already initialized
        }

        $this->adapter = $adapter ?? $this->getConfigWithFallback('adapter');

        if (!empty($expire = $this->getConfigWithFallback('expire'))) {
            // the most impactful change is in diffIn* methods. They were returning positive integer by default, they will now return float
            // In Carbon 3, using (int) $after->diffInSeconds($before, true) or (int) abs($after->diffInSeconds($before)) allows to get explicitly an absolute and truncated value, so the same result as in v2.
            $this->expireSeconds = \is_int($expire) ? $expire : (int) $this->helper->Carbon()->now()->diffInSeconds($expire);
        }

        if (method_exists($this, $this->adapter.'Adapter') && \is_callable([$this, $this->adapter.'Adapter'])) {
            $this->instance = \call_user_func([$this, $this->adapter.'Adapter']);
        } else {
            $this->instance = \call_user_func($this->nullAdapter(...));
        }

        // Set cache on translator if available (avoid circular dependency)
        $this->setupTranslatorCache();

        return $this;
    }

    // PSR-3 methods
    public function get(string $key, callable $callback, ?float $beta = null, ?array &$metadata = null): mixed
    {
        return $this->instance->get($key, $callback, $beta, $metadata);
    }

    public function delete(string $key): bool
    {
        return $this->instance->delete($key);
    }

    // https://symfony.com/doc/current/components/cache/psr6_psr16_adapters.html
    public function psr16Cache(): PsrCacheInterface
    {
        return new Psr16Cache(
            $this->instance
        );
    }

    /**
     * Get configuration value with environment fallback using existing ConfigManager.
     *
     * @param null|mixed $default
     */
    public function getConfigWithFallback(string $suffix = '', $default = null, ?array $prefixes = null)
    {
        $env = $this->container->get('env');

        $basePrefixes = (null !== $this->adapter) ? [
            "cache.{$env}.storage.{$this->adapter}",
            "cache.storage.{$this->adapter}",
        ] : [];

        $basePrefixes = array_merge([
            "cache.{$env}.storage",
            'cache.storage',
        ], $basePrefixes);

        $prefixes ??= $basePrefixes;

        return $this->config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }

    public function nullAdapter(): AdapterInterface
    {
        return new NullAdapter();
    }

    public function saveItem($cacheItem, $value, $expire = null)
    {
        $cacheItem->set($value);

        if (empty($expire)) {
            $expire = $this->getConfigWithFallback('expire');
        }

        // By default, cache items are stored permanently
        if (!empty($expire)) {
            $cacheItem->expiresAt($this->helper->Carbon()->parse($expire));
        }

        return $this->save($cacheItem);
    }

    // TODO
    public function getItemKey($params = []): string
    {
        $buffer = '';

        // https://killtheradio.net/tricks-hacks/phps-preg-functions-dont-release-memory/
        // https://github.com/troydavisson/PHRETS/issues/37#issuecomment-45427855
        // \Safe\ini_set('memory_limit', '-1');

        foreach ($params as $param) {
            if (isset($param) && !isBlank($param)) {
                if (\is_array($param)) {
                    $param = array_map(function ($item) {
                        // https://stackoverflow.com/a/13983926/3929620
                        // https://www.php.net/manual/en/function.spl-object-hash.php
                        // https://docs.opis.io/closure/3.x/features.html
                        if ($item instanceof \Closure) {
                            if (($serializedItem = $this->helper->Strings()->serialize($item)) !== false) {
                                $item = $serializedItem;

                                // FIXED - removed Opis closure spl_object_hash($this->closure)
                                // https://killtheradio.net/tricks-hacks/phps-preg-functions-dont-release-memory/
                                $item = \Safe\preg_replace('/\"self\";s:32:\"([^\"]{32})\";/', '"self";s:32:"";', $item);

                                // FIXED - remove resolve closure's scope and $this object
                                $pos = stripos($item, ';s:5:"scope";');

                                if (\is_int($pos)) {
                                    $item = substr($item, 0, $pos);
                                }

                                // FIXED
                                $item = \Safe\preg_replace('/C:32:\"Opis\\\Closure\\\SerializableClosure\":(\d+):/', '', $item);
                            } else {
                                $item = var_export($item, true);
                            }
                        }

                        return $item;
                    }, $param);

                    $param = $this->helper->Nette()->Json()->encode($param);
                } else {
                    $param = (string) $param;
                }

                // https://stackoverflow.com/a/5820612/3929620
                if (\strlen((string) $param) > 100 || false !== strpbrk((string) $param, '{}()/\@:')) {
                    $param = $this->helper->Strings()->crc32($param);
                }

                $buffer .= '.'.$param;
            }
        }

        // ini_restore('memory_limit');

        return ltrim($buffer, '.');
    }

    /**
     * Setup translator cache to avoid circular dependency.
     * Called after cache is initialized but before translator cache is needed.
     */
    protected function setupTranslatorCache(): void
    {
        // Only setup if translator is already available and cache is enabled for translations
        if ($this->container->has(TranslatorInterface::class)
            && $this->getConfigWithFallback('translation.enabled', false)) {
            try {
                $translator = $this->container->get(TranslatorInterface::class);
                $translator->getInstance()?->setCache($this->psr16Cache());
            } catch (\Throwable $e) {
                // Ignore errors during cache setup to avoid breaking the application
                // This can happen if translator is not yet fully initialized
                $this->logger->warningInternal('Failed to setup translator cache', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    private function filesystemAdapter(): AdapterInterface
    {
        $env = $this->container->get('env');

        return new FilesystemAdapter(
            // the subdirectory of the main cache directory where cache items are stored
            '', // default: ''
            // in seconds; applied to cache items that don't define their own lifetime
            // 0 means to store the cache items indefinitely (i.e. until the files are deleted)
            $this->expireSeconds, // default: 0
            // the main cache directory (the application needs read-write permissions on it)
            // if none is specified, a directory is created inside the system temporary directory
            $this->getConfigWithFallback('adapter.filesystem.path') // default: null
        );
    }

    // https://stackoverflow.com/a/40811498
    // https://stackoverflow.com/a/57517412/3929620
    // https://github.com/symfony/symfony/issues/33201
    private function filesystemTagAwareAdapter(): AdapterInterface
    {
        $env = $this->container->get('env');

        $this->taggable = true;

        return new FilesystemTagAwareAdapter(
            // the subdirectory of the main cache directory where cache items are stored
            '', // default: ''
            // in seconds; applied to cache items that don't define their own lifetime
            // 0 means to store the cache items indefinitely (i.e. until the files are deleted)
            $this->expireSeconds, // default: 0
            // the main cache directory (the application needs read-write permissions on it)
            // if none is specified, a directory is created inside the system temporary directory
            $this->getConfigWithFallback('adapter.filesystemTagAware.path') // default: null
        );
    }

    private function phpFilesAdapter(): AdapterInterface
    {
        $env = $this->container->get('env');

        return new PhpFilesAdapter(
            // the subdirectory of the main cache directory where cache items are stored
            '', // default: ''
            // in seconds; applied to cache items that don't define their own lifetime
            // 0 means to store the cache items indefinitely (i.e. until the files are deleted)
            $this->expireSeconds, // default: 0
            // the main cache directory (the application needs read-write permissions on it)
            // if none is specified, a directory is created inside the system temporary directory
            $this->getConfigWithFallback('adapter.phpFiles.path') // default: null
        );
    }

    private function pdoAdapter(): AdapterInterface
    {
        return new PdoAdapter(
            // A \PDO or Connection instance or DSN string or null
            // You can either pass an existing database connection as PDO instance or
            // a Doctrine DBAL Connection or a DSN string that will be used to
            // lazy-connect to the database when the cache is actually used.
            $this->db->getPDO(),
            // the subdirectory of the main cache directory where cache items are stored
            '', // default: ''
            // in seconds; applied to cache items that don't define their own lifetime
            // 0 means to store the cache items indefinitely (i.e. until the files are deleted)
            $this->expireSeconds, // default: 0
            // An associative array of options
            // db_table: The name of the table [default: cache_items]
            // db_id_col: The column where to store the cache id [default: item_id]
            // db_data_col: The column where to store the cache data [default: item_data]
            // db_lifetime_col: The column where to store the lifetime [default: item_lifetime]
            // db_time_col: The column where to store the timestamp [default: item_time]
            // db_username: The username when lazy-connect [default: '']
            // db_password: The password when lazy-connect [default: '']
            // db_connection_options: An array of driver-specific connection options [default: []]
            [ // default: []
                'db_table' => $this->config->get('db.prefix').'cache',
            ]
        );
    }

    // TODO
    private function memcachedAdapter(): AdapterInterface
    {
        return new MemcachedAdapter(
            // Memcached $client
            $this->memcached,
            // the subdirectory of the main cache directory where cache items are stored
            '', // default: ''
            // in seconds; applied to cache items that don't define their own lifetime
            // 0 means to store the cache items indefinitely (i.e. until the files are deleted)
            $this->expireSeconds // default: 0
        );
    }
}
