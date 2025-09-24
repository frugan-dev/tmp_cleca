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

namespace App\Middleware;

use App\Factory\Db\DbFactory;
use App\Model\Model;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class DbMiddleware extends Model implements MiddlewareInterface
{
    public function __construct(
        protected ContainerInterface $container,
        protected DbFactory $db
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            $response = $handler->handle($request);

            // Only add debug logs for successful responses (not errors)
            if (!empty($this->config['db.debug.enabled']) && !empty($this->config['db.debug.appendLogs'])) {
                // https://html.spec.whatwg.org/multipage/syntax.html#syntax-tag-omission
                $bodyContent = \Safe\preg_replace('~<\/body>~', @s($this->db->getLogs()).'</body>', $response->getBody()->__toString());

                $response->getBody()->write($bodyContent);
            }

            return $response;
        } catch (\Throwable $exception) {
            // This catch block will execute for any error (404, 500, etc.)
            // Even though the error will be re-thrown, we can do cleanup here

            // Always close DB connections, even for errors
            $this->closeDbConnection();

            // Re-throw the exception so ErrorMiddleware can handle it
            throw $exception;
        } finally {
            // This will ALWAYS execute, regardless of success or error
            $this->closeDbConnection();
        }
    }

    /**
     * Close database connection if it's open.
     */
    private function closeDbConnection(): void
    {
        /*
         * Closes the database connection.
         * While database connections are closed automatically at the end of the PHP script,
         * closing database connections is generally recommended to improve performance.
         * Closing a database connection will immediately return the resources to PHP.
         */
        if ($this->db->testConnection()) {
            $this->db->close();
        }
    }
}
