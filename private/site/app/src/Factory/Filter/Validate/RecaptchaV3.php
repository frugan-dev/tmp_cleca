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

namespace App\Factory\Filter\Validate;

use App\Factory\Logger\LoggerInterface;
use App\Model\Model;
use Laminas\Stdlib\ArrayUtils;
use Psr\Container\ContainerInterface;
use ReCaptcha\ReCaptcha;
use ReCaptcha\RequestMethod\CurlPost;

class RecaptchaV3 extends Model
{
    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger
    ) {}

    public function __invoke($subject, $field, $params = [])
    {
        $params = ArrayUtils::merge(
            [
                'env' => 'front',
                'controllerCamelCase' => null,
            ],
            $params
        );

        $value = $subject->{$field};

        // http://stackoverflow.com/a/30848193
        if (\Safe\ini_get('allow_url_fopen')) {
            $recaptcha = new ReCaptcha($this->config->get('service.'.$params['env'].'.'.$params['controllerCamelCase'].'.google.recaptcha.privateKey') ?? $this->config->get('service.'.$params['env'].'.google.recaptcha.privateKey') ?? $this->config->get('service.google.recaptcha.privateKey'));
        } else {
            $recaptcha = new ReCaptcha($this->config->get('service.'.$params['env'].'.'.$params['controllerCamelCase'].'.google.recaptcha.privateKey') ?? $this->config->get('service.'.$params['env'].'.google.recaptcha.privateKey') ?? $this->config->get('service.google.recaptcha.privateKey'), new CurlPost());
            // $recaptcha = new \ReCaptcha\ReCaptcha($this->config->get('service.'.$params['env'].'.'.$params['controllerCamelCase'].'.google.recaptcha.privateKey') ?? $this->config->get('service.'.$params['env'].'.google.recaptcha.privateKey') ?? $this->config->get('service.google.recaptcha.privateKey'), new \ReCaptcha\RequestMethod\SocketPost());
        }

        $resp = $recaptcha->setExpectedHostname($_SERVER['HTTP_HOST'])
            // Note: actions may only contain alphanumeric characters and slashes, and must not be user-specific.
            ->setExpectedAction(\Safe\preg_replace('~\W+~', '', (string) $_SERVER['HTTP_HOST']))
            ->setScoreThreshold($this->config->get('service.'.$params['env'].'.'.$params['controllerCamelCase'].'.google.recaptcha.scoreThreshold') ?? $this->config->get('service.'.$params['env'].'.google.recaptcha.scoreThreshold') ?? $this->config->get('service.google.recaptcha.scoreThreshold'))
            ->verify($value, $this->container->get('request')->getAttribute('client-ip'))
        ;

        if ($resp->isSuccess()) {
            return true;
        }

        $this->logger->warning($this->getShortName().' -> '.__FUNCTION__.' -> '.__LINE__, [
            'error' => var_export($resp->getErrorCodes(), true),
        ]);

        return false;
    }
}
