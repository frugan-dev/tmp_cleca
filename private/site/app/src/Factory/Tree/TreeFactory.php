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

namespace App\Factory\Tree;

use App\Factory\ArraySiblings\ArraySiblingsInterface;
use App\Factory\Html\ViewHelperInterface;
use App\Helper\HelperInterface;
use App\Model\Model;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Shudrum\Component\ArrayFinder\ArrayFinder;
use Symfony\Component\EventDispatcher\GenericEvent;

// https://designpatternsphp.readthedocs.io/en/latest/README.html
class TreeFactory extends Model implements TreeInterface
{
    protected ?ArrayFinder $instance = null;

    public function __construct(
        protected ContainerInterface $container,
        protected EventDispatcherInterface $dispatcher,
        protected ViewHelperInterface $viewHelper,
        protected HelperInterface $helper,
        protected ArraySiblingsInterface $arraySiblings
    ) {}

    public function __call($name, $args)
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. Call create() first.', $this->getShortName()));
        }

        return \call_user_func_array([$this->instance, $name], $args);
    }

    public function getInstance(): ?ArrayFinder
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. It should be initialized via container factory.', $this->getShortName()));
        }

        return $this->instance;
    }

    // https://stackoverflow.com/a/57102716/3929620
    public function create(array $array, ?array $types = []): self
    {
        $this->instance = new ArrayFinder();

        $env = $this->container->get('env');

        foreach ($array as $key => $val) {
            $traversePath = self::getTraversePath($key, $array);

            if (!empty($types)) {
                foreach ($types as $type) {
                    $this->dispatcher->dispatch(new GenericEvent(arguments: [
                        'traversePath' => $traversePath,
                        'key' => $key,
                        'val' => $val,
                    ]), 'event.'.$env.'.'.$key.'.'.$this->getShortName().'.'.__FUNCTION__.'.'.$type.'.before');
                }
            } else {
                $this->dispatcher->dispatch(new GenericEvent(arguments: [
                    'traversePath' => $traversePath,
                    'key' => $key,
                    'val' => $val,
                ]), 'event.'.$env.'.'.$key.'.'.$this->getShortName().'.'.__FUNCTION__.'.before');
            }

            $this->instance->set($traversePath, $val);

            if (!empty($types)) {
                foreach ($types as $type) {
                    $this->dispatcher->dispatch(new GenericEvent(arguments: [
                        'traversePath' => $traversePath,
                        'key' => $key,
                        'val' => $val,
                    ]), 'event.'.$env.'.'.$key.'.'.$this->getShortName().'.'.__FUNCTION__.'.'.$type.'.after');
                }
            } else {
                $this->dispatcher->dispatch(new GenericEvent(arguments: [
                    'traversePath' => $traversePath,
                    'key' => $key,
                    'val' => $val,
                ]), 'event.'.$env.'.'.$key.'.'.$this->getShortName().'.'.__FUNCTION__.'.after');
            }
        }

        return $this;
    }

    public static function getReverseTraversePathArr($key, array $array, array $pathArr = []): array
    {
        $pathArr[] = $key;

        if (!empty($array[$key]['parent_id'])) {
            $pathArr[] = 'items';

            return self::getReverseTraversePathArr($array[$key]['parent_id'], $array, $pathArr);
        }

        return $pathArr;
    }

    public static function getTraversePath($key, array $array): string
    {
        return implode('.', array_reverse(self::getReverseTraversePathArr($key, $array)));
    }

    // TODO
    public function getSiblings($key, array $array): void {}

    public function render($params = []): string
    {
        // http://codelegance.com/array-merging-in-php/
        // $params = ArrayUtils::merge(
        $params = array_merge(
            [
                'env' => $this->container->get('env'),
                'items' => $this->instance->get(), // <-- ArrayUtils merge arrays
                'level' => 0,
                'parentTag' => 'ul',
                'parentTagAttr' => [],
                'childTag' => 'li',
                'childTagAttr' => [],
                'linkTag' => 'a',
                'linkTagAttr' => [],
                'labelTag' => null,
                'labelTagAttr' => [],
            ],
            $params
        );

        $buffer = '';

        if ((is_countable($params['items']) ? \count($params['items']) : 0) > 0) {
            $buffer .= str_repeat("\t", $params['level']).'<'.($params[$params['level'].'-parentTag'] ?? $params['parentTag']).$this->viewHelper->escapeAttr($params[$params['level'].'-parentTagAttr'] ?? $params['parentTagAttr']).'>'.PHP_EOL;

            foreach ($params['items'] as $key => $val) {
                $subBuffer = '';

                if (!empty($val['items'])) {
                    $subBuffer = $this->render(array_merge(
                        $params,
                        [
                            'items' => $val['items'],
                            'level' => ($params['level'] + 1),
                        ]
                    ));
                }

                $buffer .= str_repeat("\t", $params['level'] + 1).'<'.($params[$params['level'].'-childTag'] ?? $params['childTag']).$this->viewHelper->escapeAttr($params[$params['level'].'-childTagAttr'] ?? $params['childTagAttr']).'>'.PHP_EOL;

                $linkTagAttr = $params[$params['level'].'-linkTagAttr'] ?? $params['linkTagAttr'];

                $routeArgs = false;

                if (!empty($val['routeArgs'])) {
                    $routeArgs = $val['routeArgs'];
                } elseif ($params['env'].'.index' === $key) {
                    $routeArgs = $key;
                } elseif (!empty($val['slug']) || !empty($val['metaTitle'])) {
                    $routeArgs = [
                        'routeName' => $params['env'].'.page',
                        'data' => [
                            'slug' => $this->helper->Nette()->Strings()->webalize(
                                $val['slug'] ?? $val['metaTitle'],
                                $this->config->get('url.'.$params['env'].'.nette.webalize.charlist') ?? $this->config->get('url.nette.webalize.charlist')
                            ),
                        ],
                    ];
                }

                $linkTagAttr['href'] = !empty($routeArgs) ? $this->helper->Url()->urlFor($routeArgs) : false;

                $linkTagAttr['title'] = $val['title'] ?? false;

                $buffer .= str_repeat("\t", $params['level'] + 2).'<'.($params[$params['level'].'-linkTag'] ?? $params['linkTag']).$this->viewHelper->escapeAttr($linkTagAttr).'>'.PHP_EOL;

                $buffer .= str_repeat("\t", $params['level'] + 3);

                if (!empty($params[$params['level'].'-labelTag'] ?? $params['labelTag'])) {
                    $buffer .= '<'.($params[$params['level'].'-labelTag'] ?? $params['labelTag']).$this->viewHelper->escapeAttr($params[$params['level'].'-labelTagAttr'] ?? $params['labelTagAttr']).'>';
                }

                $buffer .= $val['label'] ?? $val['title'] ?? '== no value ==';

                if (!empty($params[$params['level'].'-labelTag'] ?? $params['labelTag'])) {
                    $buffer .= '</'.($params[$params['level'].'-labelTag'] ?? $params['labelTag']).'>';
                }

                $buffer .= PHP_EOL;

                $buffer .= str_repeat("\t", $params['level'] + 2).'</'.($params[$params['level'].'-linkTag'] ?? $params['linkTag']).'>'.PHP_EOL;

                $buffer .= $subBuffer;

                $buffer .= str_repeat("\t", $params['level'] + 1).'</'.($params[$params['level'].'-childTag'] ?? $params['childTag']).'>'.PHP_EOL;
            }

            $buffer .= str_repeat("\t", $params['level']).'</'.($params[$params['level'].'-parentTag'] ?? $params['parentTag']).'>'.PHP_EOL;
        }

        return $buffer;
    }
}
