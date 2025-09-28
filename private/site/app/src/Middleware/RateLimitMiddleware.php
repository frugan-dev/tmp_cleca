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

use App\Factory\Auth\AuthInterface;
use App\Factory\Db\DbFactory;
use App\Factory\Logger\LoggerInterface;
use App\Helper\HelperInterface;
use App\Model\Model;
use Monolog\Level;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

// https://swagger.io/docs/specification/describing-responses/
class RateLimitMiddleware extends Model implements MiddlewareInterface
{
    public static string $env = 'default';

    public array $errors = [];

    public array $notices = [];

    public $responseData = [];

    public string $mimeType = 'application/json';

    public string $logLevel = 'debug';

    public ?int $rl_day_limit = 0;

    public ?int $rl_day_used = 0;

    public ?int $rl_hour_limit = 0;

    public ?int $rl_hour_used = 0;

    public function __construct(
        protected ContainerInterface $container,
        protected DbFactory $db,
        protected AuthInterface $auth,
        protected HelperInterface $helper,
        protected LoggerInterface $logger
    ) {}

    #[\Override]
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!empty($request->getAttribute('hasIdentity')) && !empty($request->getHeaderLine($this->config['api.headers.key']))) {
            $this->checkRateLimit($request);

            if (\count($this->errors) > 0) {
                $message = current($this->errors);

                $this->logger->debug(strip_tags((string) $message), [
                    'errors' => $this->errors,
                ]);

                $this->responseData['errors'] = $this->errors;

                // The Accept request-header field can be used to specify certain media types which are acceptable for the response.
                // The Content-Type entity-header field indicates the media type of the entity-body sent to the recipient or,
                // in the case of the HEAD method, the media type that would have been sent had the request been a GET.
                if ($request->hasHeader('Accept')) {
                    if (!empty($accepts = array_intersect($request->getHeader('Accept'), [
                        'application/json',
                        'application/xml',
                    ]))) {
                        $this->mimeType = current($accepts);
                    }
                }

                $body = match ($this->mimeType) {
                    'application/xml' => $this->helper->Arrays()->toXml($this->responseData),
                    default => $this->helper->Nette()->Json()->encode($this->responseData),
                };

                $response = new Response();
                $response->getBody()->write($body);

                return $response->withHeader('Content-type', $this->mimeType)
                    ->withAddedHeader('X-RateLimit-Daily-Limit', $this->rl_day_limit)
                    ->withAddedHeader('X-RateLimit-Daily-Used', $this->rl_day_used)
                    ->withAddedHeader('X-RateLimit-Hourly-Limit', $this->rl_hour_limit)
                    ->withAddedHeader('X-RateLimit-Hourly-Used', $this->rl_hour_used)
                    ->withStatus(403)
                ;
            }

            $response = $handler->handle($request);

            if (\count($this->notices) > 0) {
                foreach ($this->notices as $notice) {
                    $this->logger->notice($notice);
                }
            }

            return $response->withHeader('X-RateLimit-Daily-Limit', $this->rl_day_limit)
                ->withAddedHeader('X-RateLimit-Daily-Used', $this->rl_day_used)
                ->withAddedHeader('X-RateLimit-Hourly-Limit', $this->rl_hour_limit)
                ->withAddedHeader('X-RateLimit-Hourly-Used', $this->rl_hour_used)
            ;
        }

        return $handler->handle($request);
    }

    protected function checkRateLimit(ServerRequestInterface $request): void
    {
        if (!empty($this->rl_day_limit = $this->auth->getIdentity()['catuser_'.static::$env.'_rl_day'] ?? 0)) {
            $this->rl_day_used = (int) $this->db->exec('SELECT id FROM '.$this->config['db.1.prefix'].'log
            WHERE environment = :environment
            AND level = :level
            AND auth_type = :auth_type
            AND auth_id = :auth_id
            AND time >= :time', [
                'environment' => static::$env,
                'level' => Level::Info->value,
                'auth_type' => $this->auth->getIdentity()['_role_type'],
                'auth_id' => $this->auth->getIdentity()['id'],
                'time' => $this->helper->Carbon()->now($this->config['db.1.timeZone'])->subDay()->timestamp,
            ]);

            if ($this->rl_day_used === ($this->rl_day_limit - 1)) {
                $this->notices[] = \sprintf(__('%1$s rate limit reached (%2$d requests per %3$s)'), __('Daily'), $this->rl_day_limit, __('day'));
            }
            if ($this->rl_day_used >= $this->rl_day_limit) {
                $this->errors[] = \sprintf(__('%1$s rate limit reached (%2$d requests per %3$s)'), __('Daily'), $this->rl_day_limit, __('day'));
            }
            if (0 === \count($this->errors)) {
                ++$this->rl_day_used;
            }
        }

        if (!empty($this->rl_hour_limit = $this->auth->getIdentity()['catuser_'.static::$env.'_rl_hour'] ?? 0)) {
            $this->rl_hour_used = (int) $this->db->exec('SELECT id FROM '.$this->config['db.1.prefix'].'log
            WHERE environment = :environment
            AND level = :level
            AND auth_type = :auth_type
            AND auth_id = :auth_id
            AND time >= :time', [
                'environment' => static::$env,
                'level' => Level::Info->value,
                'auth_type' => $this->auth->getIdentity()['_role_type'],
                'auth_id' => $this->auth->getIdentity()['id'],
                'time' => $this->helper->Carbon()->now($this->config['db.1.timeZone'])->subHour()->timestamp,
            ]);

            if ($this->rl_hour_used === ($this->rl_hour_limit - 1)) {
                $this->notices[] = \sprintf(__('%1$s rate limit reached (%2$d requests per %3$s)'), __('Hourly'), $this->rl_hour_limit, __('hour'));
            }
            if ($this->rl_hour_used >= $this->rl_hour_limit) {
                $this->errors[] = \sprintf(__('%1$s rate limit reached (%2$d requests per %3$s)'), __('Hourly'), $this->rl_hour_limit, __('hour'));
            }
            if (0 === \count($this->errors)) {
                ++$this->rl_hour_used;
            }
        }
    }
}
