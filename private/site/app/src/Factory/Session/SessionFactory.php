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

namespace App\Factory\Session;

use App\Factory\Html\ViewHelperInterface;
use App\Helper\HelperInterface;
use App\Model\Model;
use Laminas\Stdlib\ArrayUtils;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

// https://designpatternsphp.readthedocs.io/en/latest/README.html
class SessionFactory extends Model implements SessionInterface
{
    protected ?Session $instance = null;

    public function __construct(
        protected ContainerInterface $container,
        protected ViewHelperInterface $viewHelper,
        protected HelperInterface $helper,
    ) {}

    public function __call($name, $args)
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. Call create() first.', $this->getShortName()));
        }

        return \call_user_func_array([$this->instance, $name], $args);
    }

    public function getInstance(): ?Session
    {
        if (null === $this->instance) {
            throw new \RuntimeException(\sprintf('"%s" not initialized. It should be initialized via container factory.', $this->getShortName()));
        }

        return $this->instance;
    }

    public function create(): self
    {
        if (null !== $this->instance) {
            return $this; // Already initialized
        }

        if ($this->helper->Env()->isCli()) {
            $this->instance = new Session(new MockArraySessionStorage());
        } else {
            $sessionOptions = $this->buildSessionOptions();
            $this->instance = new Session(new NativeSessionStorage($sessionOptions));
        }

        return $this;
    }

    /**
     * Get configuration value with environment fallback using existing ConfigManager.
     *
     * @param null|mixed $default
     */
    public function getConfigWithFallback(string $suffix = '', $default = null, ?array $prefixes = null)
    {
        $env = $this->container->get('env');

        $prefixes ??= [
            "session.{$env}",
            'session',
        ];

        return $this->config->getRepository()->getWithFallback($prefixes, $suffix, $default);
    }

    public function hasFlash(string $type, string $uniqueKey): bool
    {
        foreach ($this->instance->getFlashBag()->peek($type) as $options) {
            $optionsObj = $this->helper->Nette()->Json()->decode((string) $options);

            if ($optionsObj->uniqueKey === $uniqueKey) {
                return true;
            }
        }

        return false;
    }

    public function deleteFlash(string $type, string $uniqueKey): void
    {
        $flashes = [];
        foreach ($this->instance->getFlashBag()->get($type) as $options) {
            $optionsObj = $this->helper->Nette()->Json()->decode((string) $options);

            if ($optionsObj->uniqueKey !== $uniqueKey) {
                $flashes[] = $options;
            }
        }

        if (!empty($flashes)) {
            $this->instance->getFlashBag()->set($type, $flashes);
        }
    }

    public function addFlash(array $params = []): void
    {
        $params = ArrayUtils::merge(
            [
                'type' => null,
                'options' => [
                    // https://blog.codinghorror.com/speed-hashing/
                    // https://stackoverflow.com/a/7723730
                    // https://stackoverflow.com/a/6839990
                    // https://stackoverflow.com/a/3665331
                    'uniqueKey' => $this->helper->Strings()->crc32($this->helper->Nette()->Json()->encode($params)),
                ],
            ],
            $params
        );

        if (!empty($params['type']) && !empty($params['options'])) {
            $this->instance->getFlashBag()->add($params['type'], $this->helper->Nette()->Json()->encode($params['options']));
        }
    }

    public function getFlash(string $type): bool|string|null
    {
        if (method_exists($this, __FUNCTION__.ucfirst((string) $type)) && \is_callable([$this, __FUNCTION__.ucfirst((string) $type)])) {
            return \call_user_func_array([$this, __FUNCTION__.ucfirst((string) $type)], [$type]);
        }

        return false;
    }

    public function getFlashAlert(string $type)
    {
        $buffer = false;

        foreach ($this->instance->getFlashBag()->get($type) as $options) {
            $optionsObj = (object) ArrayUtils::merge(
                [
                    'env' => 'front',
                    'dismissible' => true,
                    'autoDismissible' => $this->getConfigWithFallback($type.'.delay', false),
                    'attr' => [
                        'class' => [
                            $type,
                            'fade',
                            'show',
                            '__animate__animated',
                            '__animate__fadeIn',
                        ],
                        'role' => 'alert',
                    ],
                ],
                $this->helper->Nette()->Json()->decode((string) $options, forceArrays: true)
            );

            if (!empty($optionsObj->message)) {
                if (!empty($optionsObj->type)) {
                    $optionsObj->attr['class'][] = $type.'-'.$optionsObj->type;
                }
                if (!empty($optionsObj->dismissible)) {
                    $optionsObj->attr['class'][] = $type.'-dismissible';
                }
                if (!empty($optionsObj->autoDismissible)) {
                    $optionsObj->attr['class'][] = $type.'-auto-dismissible';
                }
                if (!empty($optionsObj->postDismissible)) {
                    $optionsObj->attr['class'][] = $type.'-post-dismissible';
                }
                if (!empty($optionsObj->postXhrDismissible)) {
                    $optionsObj->attr['class'][] = $type.'-post-xhr-dismissible';
                }
                if (!empty($optionsObj->fixedTop)) {
                    $optionsObj->attr['class'][] = 'position-fixed'; // see also .fixed-top
                    $optionsObj->attr['class'][] = 'z-fixed';
                    $optionsObj->attr['class'][] = 'top-0';
                    $optionsObj->attr['class'][] = 'start-50';
                    $optionsObj->attr['class'][] = 'translate-middle-x';
                    $optionsObj->attr['class'][] = 'rounded-0';
                    $optionsObj->attr['class'][] = 'rounded-bottom';
                }
                if (!empty($optionsObj->fixedBottom)) {
                    $optionsObj->attr['class'][] = 'position-fixed'; // see also .fixed-bottom
                    $optionsObj->attr['class'][] = 'z-fixed';
                    $optionsObj->attr['class'][] = 'bottom-0';
                    $optionsObj->attr['class'][] = 'start-50';
                    $optionsObj->attr['class'][] = 'translate-middle-x';
                    $optionsObj->attr['class'][] = 'mb-0';
                    $optionsObj->attr['class'][] = 'rounded-0';
                    $optionsObj->attr['class'][] = 'rounded-top';
                }

                $buffer .= '<div'.$this->viewHelper->escapeAttr($optionsObj->attr).'>'.PHP_EOL;

                if (!empty($optionsObj->postDismissible) || !empty($optionsObj->postXhrDismissible)) {
                    $buffer .= '<form novalidate method="POST" action="" autocomplete="off" role="form"'.$this->viewHelper->escapeAttr([
                        'data-'.(!empty($optionsObj->postXhrDismissible) ? 'a' : '').'sync' => true,
                    ]).'>'.PHP_EOL;
                    if (\is_array($optionsObj->postXhrDismissible)) {
                        foreach ($optionsObj->postXhrDismissible as $params) {
                            $buffer .= $this->helper->Html()->getFormField($params);
                        }
                    }
                    $buffer .= '<button type="submit" data-loading-text="<span class=\'spinner-border spinner-border-sm align-baseline\' role=\'status\' aria-hidden=\'true\'></span>"'.$this->viewHelper->escapeAttr([
                        'class' => array_merge(['outline-none'], \in_array($this->getConfigWithFallback('theme.'.$optionsObj->env.'.type') ?? $this->getConfigWithFallback('theme.type', true), ['twbs3', 'twbs4'], true) ? ['close'] : ['btn-close']),
                        'aria-label' => __('Close'),
                    ]).'>'.PHP_EOL;
                    $buffer .= \in_array($this->getConfigWithFallback('theme.'.$optionsObj->env.'.type') ?? $this->getConfigWithFallback('theme.type', true), ['twbs3', 'twbs4'], true) ? '<span aria-hidden="true">&times;</span>'.PHP_EOL : '';
                    $buffer .= '</button>'.PHP_EOL;
                    $buffer .= '</form>'.PHP_EOL;
                } elseif (!empty($optionsObj->dismissible) || !empty($optionsObj->autoDismissible)) {
                    $buffer .= '<button type="button"'.$this->viewHelper->escapeAttr([
                        'class' => array_merge(['outline-none'], \in_array($this->getConfigWithFallback('theme.'.$optionsObj->env.'.type') ?? $this->getConfigWithFallback('theme.type', true), ['twbs3', 'twbs4'], true) ? ['close'] : ['btn-close']),
                        'data-dismiss' => \in_array($this->getConfigWithFallback('theme.'.$optionsObj->env.'.type') ?? $this->getConfigWithFallback('theme.type', true), ['twbs3', 'twbs4'], true) ? $type : false,
                        'data-bs-dismiss' => !\in_array($this->getConfigWithFallback('theme.'.$optionsObj->env.'.type') ?? $this->getConfigWithFallback('theme.type', true), ['twbs3', 'twbs4'], true) ? $type : false,
                        'aria-label' => __('Close'),
                    ]).'>'.PHP_EOL;
                    $buffer .= \in_array($this->getConfigWithFallback('theme.'.$optionsObj->env.'.type') ?? $this->getConfigWithFallback('theme.type', true), ['twbs3', 'twbs4'], true) ? '<span aria-hidden="true">&times;</span>'.PHP_EOL : '';
                    $buffer .= '</button>'.PHP_EOL;
                }

                $buffer .= \is_array($optionsObj->message) ? implode('<br>'.PHP_EOL, $optionsObj->message) : $optionsObj->message;

                $buffer .= '</div>'.PHP_EOL;

                if (!empty($optionsObj->autoDismissible)) {
                    $this->viewHelper->scriptsFoot()->beginInternal();
                    echo '(() => {
    if( typeof App !== \'undefined\' ) {
        const '.$type.'AutoDismissibleList = Array.prototype.slice.call(document.querySelectorAll(".'.$type.'-auto-dismissible"));
        if (!!'.$type.'AutoDismissibleList) {
            '.$type.'AutoDismissibleList.map(function ('.$type.'AutoDismissibleElement) {
                App.Helper.wait.start('.(int) $optionsObj->autoDismissible.').then(() => {
                    const alert = Alert.getOrCreateInstance('.$type.'AutoDismissibleElement);
                    if (alert) {
                        alert.close();
                    }
                });
            });
        }
    }
})();';
                    $this->viewHelper->scriptsFoot()->endInternal();
                }

                if (!empty($optionsObj->scriptsFoot)) {
                    $this->viewHelper->scriptsFoot()->beginInternal();
                    echo $optionsObj->scriptsFoot;
                    $this->viewHelper->scriptsFoot()->endInternal();
                }
            }
        }

        return $buffer;
    }

    public function getFlashModal(string $type)
    {
        $buffer = false;

        foreach ($this->instance->getFlashBag()->get($type) as $options) {
            $optionsObj = (object) ArrayUtils::merge(
                [
                    'env' => 'front',
                ],
                $this->helper->Nette()->Json()->decode((string) $options, forceArrays: true)
            );

            if (!empty($optionsObj->body)) {
                $optionsObj->body = \is_array($optionsObj->body) ? implode('<br>', $optionsObj->body) : $optionsObj->body;
                $optionsObj->body = $this->helper->Strings()->linearize(trim((string) $optionsObj->body), ' ');

                if (!empty($optionsObj->footer)) {
                    $optionsObj->footer = \is_array($optionsObj->footer) ? implode('<br>', $optionsObj->footer) : $optionsObj->footer;
                    $optionsObj->footer = $this->helper->Strings()->linearize(trim((string) $optionsObj->footer), ' ');
                }

                $this->viewHelper->scriptsFoot()->beginInternal();
                echo '(() => {
    if( typeof App !== \'undefined\' ) {
        App.Mod.Modal.add(
            "'.$this->viewHelper->escape()->js($optionsObj->body).'",
            '.(isset($optionsObj->title) ? '"'.$this->viewHelper->escape()->js($optionsObj->title).'"' : 'null').',
            '.(isset($optionsObj->footer) ? '"'.$this->viewHelper->escape()->js($optionsObj->footer).'"' : 'null').',
            '.(isset($optionsObj->size) ? '"'.$this->viewHelper->escape()->js($optionsObj->size).'"' : 'null').',
            '.(isset($optionsObj->type) ? '"'.$this->viewHelper->escape()->js($optionsObj->type).'"' : 'null').',
            {
                '.(isset($optionsObj->animation) ? 'animation: '.(!empty($optionsObj->animation) ? 'true' : 'false').',' : '').'
                '.(isset($optionsObj->staticBackdrop) ? 'staticBackdrop: '.(!empty($optionsObj->staticBackdrop) ? 'true' : 'false').',' : '').'
                '.(isset($optionsObj->scrollable) ? 'scrollable: '.(!empty($optionsObj->scrollable) ? 'true' : 'false').',' : '').'
                '.(isset($optionsObj->centered) ? 'centered: '.(!empty($optionsObj->centered) ? 'true' : 'false').',' : '').'
                '.(isset($optionsObj->headerAttr) ? 'headerAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->headerAttr)).'",' : '').'
                '.(isset($optionsObj->bodyAttr) ? 'bodyAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->bodyAttr)).'",' : '').'
                '.(isset($optionsObj->footerAttr) ? 'footerAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->footerAttr)).'",' : '').'
                '.(isset($optionsObj->titleAttr) ? 'titleAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->titleAttr)).'",' : '').'
                '.(isset($optionsObj->headerBtnCloseAttr) ? 'headerBtnCloseAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->headerBtnCloseAttr)).'",' : '').'
                '.(isset($optionsObj->footerBtnCloseAttr) ? 'footerBtnCloseAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->footerBtnCloseAttr)).'",' : '').'
                '.(isset($optionsObj->attr) ? 'attr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->attr)).'",' : '').'
                '.(isset($optionsObj->dialogAttr) ? 'dialogAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->dialogAttr)).'",' : '').'
                '.(isset($optionsObj->contentAttr) ? 'contentAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->contentAttr)).'",' : '').'
            }
        ).show();
    }
})();';

                if (!empty($optionsObj->scriptsFoot)) {
                    echo $optionsObj->scriptsFoot;
                }
                $this->viewHelper->scriptsFoot()->endInternal();

                $buffer = null;
            }
        }

        return $buffer;
    }

    public function getFlashToast(string $type)
    {
        $buffer = false;

        foreach ($this->instance->getFlashBag()->get($type) as $options) {
            $optionsObj = (object) ArrayUtils::merge(
                [
                    'env' => 'front',
                    'delay' => $this->getConfigWithFallback($type.'.delay', false),
                ],
                $this->helper->Nette()->Json()->decode((string) $options, forceArrays: true)
            );

            if (!empty($optionsObj->message)) {
                $optionsObj->message = \is_array($optionsObj->message) ? implode('<br>', $optionsObj->message) : $optionsObj->message;
                $optionsObj->message = $this->helper->Strings()->linearize(trim((string) $optionsObj->message), ' ');

                $this->viewHelper->scriptsFoot()->beginInternal();
                echo '(() => {
    if( typeof App !== \'undefined\' ) {
        App.Mod.Toast.add(
            "'.$this->viewHelper->escape()->js($optionsObj->message).'",
            '.(isset($optionsObj->type) ? '"'.$this->viewHelper->escape()->js($optionsObj->type).'"' : 'null').',
            {
                '.(isset($optionsObj->title) ? 'title: "'.$this->viewHelper->escape()->js($optionsObj->title).'",' : '').'
                '.(isset($optionsObj->subTitle) ? 'subTitle: "'.$this->viewHelper->escape()->js($optionsObj->subTitle).'",' : '').'
                '.(isset($optionsObj->autohide) ? 'autohide: '.(!empty($optionsObj->autohide) ? 'true' : 'false').',' : '').'
                '.(!empty($optionsObj->delay ?? false) ? 'delay: '.((int) $optionsObj->delay).',' : '').'
                '.(isset($optionsObj->animation) ? 'animation: '.(!empty($optionsObj->animation) ? 'true' : 'false').',' : '').'
                '.(isset($optionsObj->nativeAnimation) ? 'nativeAnimation: '.(!empty($optionsObj->nativeAnimation) ? 'true' : 'false').',' : '').'
                '.(isset($optionsObj->headerAttr) ? 'headerAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->headerAttr)).'",' : '').'
                '.(isset($optionsObj->bodyAttr) ? 'bodyAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->bodyAttr)).'",' : '').'
                '.(isset($optionsObj->btnCloseAttr) ? 'btnCloseAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->btnCloseAttr)).'",' : '').'
                '.(isset($optionsObj->attr) ? 'attr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->attr)).'",' : '').'
                '.(isset($optionsObj->childAttr) ? 'childAttr: "'.$this->viewHelper->escape()->js($this->viewHelper->escapeAttr($optionsObj->childAttr)).'",' : '').'
            }
        ).show();
    }
})();';

                if (!empty($optionsObj->scriptsFoot)) {
                    echo $optionsObj->scriptsFoot;
                }
                $this->viewHelper->scriptsFoot()->endInternal();

                $buffer = null;
            }
        }

        return $buffer;
    }

    public function getFlashFancybox(string $type)
    {
        $buffer = false;

        foreach ($this->instance->getFlashBag()->get($type) as $options) {
            $optionsObj = (object) ArrayUtils::merge(
                [
                    'env' => 'front',
                    'attr' => [
                        'id' => 'dialog-content',
                        'class' => [],
                        'style' => [
                            'display:none;',
                        ],
                    ],
                    'childAttr' => [
                        'class' => [
                            'alert',
                            'mb-0',
                        ],
                    ],
                ],
                $this->helper->Nette()->Json()->decode((string) $options, forceArrays: true)
            );

            if (!empty($optionsObj->message)) {
                $optionsObj->message = \is_array($optionsObj->message) ? implode('<br>'.PHP_EOL, $optionsObj->message) : $optionsObj->message;

                if (!empty($optionsObj->type)) {
                    $optionsObj->childAttr['class'][] = 'alert-'.$optionsObj->type;

                    $optionsObj->message = '<div'.$this->viewHelper->escapeAttr($optionsObj->childAttr).'>'.$optionsObj->message.'</div>'.PHP_EOL;
                }

                if (!empty($optionsObj->size)) {
                    $optionsObj->attr['class'][] = $type.'-'.$optionsObj->size;
                }

                $buffer .= '<main'.$this->viewHelper->escapeAttr($optionsObj->attr).'>'.$optionsObj->message.'</main>'.PHP_EOL;

                $this->viewHelper->scriptsFoot()->beginInternal();
                echo '(() => {
    if( typeof Fancybox !== \'undefined\' ) {
        new Fancybox([
            {
                src: "#'.$optionsObj->attr['id'].'",
                type: "inline",
            },
        ]'.(!empty($optionsObj->callback) ? ', {
            '.$optionsObj->callback.'
        }' : '').');
    }
})();';
                $this->viewHelper->scriptsFoot()->endInternal();
            }
        }

        return $buffer;
    }

    /**
     * Build session options array from configuration with environment fallbacks.
     * These are the options supported by Symfony NativeSessionStorage.
     */
    protected function buildSessionOptions(): array
    {
        $options = [];

        // Session options supported by Symfony NativeSessionStorage
        // These correspond to PHP session configuration directives
        $configKeys = [
            // Basic session configuration
            'name',                    // session.name
            'save_path',              // session.save_path
            'save_handler',           // session.save_handler

            // Cache configuration
            'cache_limiter',          // session.cache_limiter
            'cache_expire',           // session.cache_expire

            // Cookie configuration
            'cookie_lifetime',        // session.cookie_lifetime
            'cookie_path',           // session.cookie_path
            'cookie_domain',         // session.cookie_domain
            'cookie_secure',         // session.cookie_secure
            'cookie_httponly',       // session.cookie_httponly
            'cookie_samesite',       // session.cookie_samesite (PHP 7.3+)

            // Security and behavior
            'use_cookies',           // session.use_cookies
            'use_only_cookies',      // session.use_only_cookies
            'use_strict_mode',       // session.use_strict_mode (PHP 5.5.2+)
            'use_trans_sid',         // session.use_trans_sid

            // Garbage collection
            'gc_probability',        // session.gc_probability
            'gc_divisor',           // session.gc_divisor
            'gc_maxlifetime',       // session.gc_maxlifetime

            // Session ID configuration
            'sid_length',           // session.sid_length (PHP 7.1+)
            'sid_bits_per_character', // session.sid_bits_per_character (PHP 7.1+)

            // Serialization
            'serialize_handler',     // session.serialize_handler

            // Other options
            'lazy_write',           // session.lazy_write (PHP 7.0+)
            'referer_check',        // session.referer_check
        ];

        foreach ($configKeys as $key) {
            $value = $this->getConfigWithFallback($key);
            if (null !== $value) {
                $options[$key] = $value;
            }
        }

        return $options;
    }
}
