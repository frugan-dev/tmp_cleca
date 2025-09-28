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

namespace App\Factory\Html;

use App\Factory\Logger\LoggerInterface;
use App\Helper\HelperInterface;
use App\Model\Model;
use Aura\Html\Escaper as AuraEscaper;
use Aura\Html\EscaperFactory;
use Aura\Html\HelperLocator;
use Aura\Html\HelperLocatorFactory;
use Laminas\Stdlib\ArrayUtils;
use Psr\Container\ContainerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Asset\PathPackage;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Asset\VersionStrategy\EmptyVersionStrategy;

class ViewHelperFactory extends Model implements ViewHelperInterface
{
    protected ?HelperLocator $instance = null;

    protected AuraEscaper $escaper;

    public function __construct(
        protected ContainerInterface $container,
        protected LoggerInterface $logger,
        protected HelperInterface $helper,
    ) {}

    public function __call($name, $args)
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. Call create() first.', $this->getShortName()));
        }

        return \call_user_func_array($this->instance->get($name), $args);
    }

    public function getInstance(): ?HelperLocator
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. It should be initialized via container factory.', $this->getShortName()));
        }

        return $this->instance;
    }

    public function create(): self
    {
        $this->instance = new HelperLocatorFactory()->newInstance();
        $this->escaper = new EscaperFactory($this->config->get('html.escaper.encoding'), $this->config->get('html.escaper.flags'))->newInstance();

        // replace escape()->attr()
        $this->instance->set('escapeAttr', fn () => new Escaper\AttrEscaper());

        // replace escape()->html()
        $this->instance->set('escapeHtml', fn () => new Escaper\HtmlEscaper());

        // replace escape()->css()
        $this->instance->set('escapeCss', fn () => new Escaper\CssEscaper());

        // replace scripts() to use type="module" instead of type="text/javascript"
        $this->instance->set('scripts', fn () => new Helper\Scripts($this->escaper));

        // replace scriptsFoot() to use type="module" instead of type="text/javascript"
        $this->instance->set('scriptsFoot', fn () => new Helper\Scripts($this->escaper));

        return $this;
    }

    public function appendData($data, $operator = false): void
    {
        // http://php.net/manual/en/function.array-filter.php#115777
        $data = array_filter(
            $data,
            function ($item) {
                if (empty($item)) {
                    return false;
                }

                return true;
            }
        );

        $newData = [];
        $oldData = $this->view->getData();

        foreach ($data as $key => $val) {
            if (isset($oldData->{$key}) && \is_array($oldData->{$key}) && \is_array($val)) {
                $newData[$key] = match ($operator) {
                    'union' => $val + $oldData->{$key},
                    'array_merge' => [...$oldData->{$key}, ...$val],
                    'array_merge_recursive' => array_merge_recursive($oldData->{$key}, $val),
                    default => ArrayUtils::merge($oldData->{$key}, $val),
                };
            } else {
                $newData[$key] = $val;
            }
        }

        $this->view->addData($newData);
    }

    // alias di escapeAttr()
    public function attr()
    {
        /*$el = call_user_func_array([$this->instance->Nette()->Html(), 'el'], array_merge(['div'], func_get_args()));

        return strtr($el->attributes(), [
            '&#64;' => '@', //https://github.com/nette/utils/blob/master/src/Utils/Html.php#L854
        ]);*/

        // return call_user_func_array([$this->escaper, __FUNCTION__], func_get_args()); // OK
        // return call_user_func_array([$this->instance, 'escapeAttr'], func_get_args()); // OK
        return \call_user_func_array([$this, 'escapeAttr'], \func_get_args()); // OK
    }

    public function uri()
    {
        return \call_user_func_array([$this->helper->Url(), 'urlFor'], \func_get_args());
    }

    // https://github.com/kriswallsmith/assetic
    public function asset($path, $rewrite = null, $fallback = null, $package = null)
    {
        $EmptyVersionStrategy = new EmptyVersionStrategy();
        $namedPackages = [
            'path' => new PathPackage('/', $EmptyVersionStrategy),
            'url' => new UrlPackage($this->helper->Url()->getBaseUrl().'/', $EmptyVersionStrategy),
        ];
        $packages = new Packages(null, $namedPackages);

        $package ??= $this->config->get('asset.'.$this->view->getLayout().'.'.$this->view->getView().'.package.type') ?? $this->config->get('asset.'.$this->view->getLayout().'.package.type') ?? $this->config->get('asset.package.type');
        if (!\array_key_exists($package, $namedPackages)) {
            $package = array_key_first($namedPackages);
        }

        $filePath = _PUBLIC.'/'.ltrim((string) $path, '/');

        $parts = explode('.', (string) $path);
        $fileExt = array_pop($parts);

        if (!is_file($filePath)) {
            if (!empty($arr = \Safe\glob(_PUBLIC.'/'.ltrim(implode('.', $parts), '/').'.*'))) {
                $filePath = current($arr);
                $tmpFileParts = explode('.', (string) $filePath);
                $fileExt = array_pop($tmpFileParts);

                $path = implode('.', $parts).'.'.$fileExt;
            }
        }

        // https://stackoverflow.com/a/792909/3929620
        if (is_file($filePath)) {
            if (!empty($rewrite)) {
                $tmpParts = explode('/', implode('.', $parts));
                $fileName = array_pop($tmpParts);

                if (is_numeric($fileName) && !\in_array($this->lang->code, $tmpParts, true)) {
                    array_push($tmpParts, $this->lang->code, $this->helper->Nette()->Strings()->webalize((string) $fileName.' '.$rewrite));
                    $parts = explode('.', implode('/', $tmpParts));
                }
            }

            if (!empty($this->config->get('asset.filenameMethod.enabled'))) {
                if (\Safe\preg_match('/'.implode('|', $this->config->get('asset.filenameMethod.arr')).'/i', $fileExt)) {
                    $parts[] = \Safe\filemtime($filePath);
                }
            }

            $parts[] = $fileExt;

            $path = implode('.', $parts);
        } else {
            $this->logger->warning('Asset file not found', [
                'args' => \func_get_args(),
                'layout' => $this->view->getLayout(),
                'view' => $this->view->getView(),
            ]);

            if (!empty($fallback)) {
                $path = $fallback;
            }
        }

        return $packages->getUrl(ltrim((string) $path, '/'), $package);
    }

    public function obfuscate($string, $type = null)
    {
        $type ??= $this->config->get('mail.obfuscate.type');

        switch ($type) {
            case 'rot13':
                $string = '<span class="obfuscated" style="display:none;">'.str_rot13((string) $string).'</span>';

                break;

            case 'hex':
                $safe = '';

                foreach (str_split((string) $string) as $letter) {
                    if (\ord($letter) > 128) {
                        return $letter;
                    }

                    // To properly obfuscate the value, we will randomly convert each letter to
                    // its entity or hexadecimal representation, keeping a bot from sniffing
                    // the randomly obfuscated letters out of the string on the responses.
                    switch (random_int(1, 3)) {
                        case 1:
                            $safe .= '&#'.\ord($letter).';';

                            break;

                        case 2:
                            $safe .= '&#x'.dechex(\ord($letter)).';';

                            break;

                        case 3:
                            $safe .= $letter;
                    }
                }

                $string = $safe;

                break;
        }

        return $string;
    }
}
