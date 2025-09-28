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

namespace App\Helper;

use App\Factory\Html\ViewHelperInterface;
use Cohensive\OEmbed\Factory as OEmbedFactory;
use Laminas\Stdlib\ArrayUtils;
use Soundasleep\Html2Text;
use Symfony\Contracts\Cache\ItemInterface;

class Html extends Helper
{
    public function getFormField($params = [])
    {
        $params = ArrayUtils::merge(
            [
                'type' => '',
                'value' => '',
                'optgroup' => [],
                'options' => [],
                'attr' => [
                    'name' => false,
                    'type' => 'hidden',
                    'value' => false,
                    'id' => false,
                ],
            ],
            $params
        );

        $viewHelper = $this->container->get(ViewHelperInterface::class);

        $buffer = '';

        switch ($params['type']) {
            case 'input':
                switch ($params['attr']['type']) {
                    /*
                     * button    Defines a clickable button (mostly used with a JavaScript to activate a script)
                     * checkbox    Defines a checkbox
                     * color    Defines a color picker
                     * date    Defines a date control (year, month and day (no time))
                     * datetime    The input type datetime has been removed from the HTML standard. Use datetime-local instead.
                     * datetime-local    Defines a date and time control (year, month, day, hour, minute, second, and fraction of a second (no time zone)
                     * email    Defines a field for an e-mail address
                     * file    Defines a file-select field and a "Browse..." button (for file uploads)
                     * hidden    Defines a hidden input field
                     * image    Defines an image as the submit button
                     * month    Defines a month and year control (no time zone)
                     * number    Defines a field for entering a number
                     * password    Defines a password field (characters are masked)
                     * radio    Defines a radio button
                     * range    Defines a control for entering a number whose exact value is not important (like a slider control)
                     * reset    Defines a reset button (resets all form values to default values)
                     * search    Defines a text field for entering a search string
                     * submit    Defines a submit button
                     * tel    Defines a field for entering a telephone number
                     * text    Default. Defines a single-line text field (default width is 20 characters)
                     * time    Defines a control for entering a time (no time zone)
                     * url    Defines a field for entering a URL
                     * week    Defines a week and year control (no time zone)
                     */

                    case 'color':
                    case 'date':
                        // DEPRECATED - https://webreflection.medium.com/using-the-input-datetime-local-9503e7efdce
                        // case 'datetime':
                        // FIXME - https://stackoverflow.com/a/69033583/3929620
                    case 'datetime-local':
                    case 'email':
                    case 'month':
                    case 'number':
                    case 'password':
                    case 'range':
                    case 'search':
                    case 'tel':
                    case 'text':
                    case 'time':
                    case 'url':
                    case 'weeek':
                        $buffer .= '<'.$params['type'].$viewHelper->escapeAttr($params['attr']).'>'.PHP_EOL;

                        break;

                    case 'checkbox':
                    case 'radio':
                        if ('' !== $params['value']) { // <--
                            if (\is_array($params['value'])) {
                                if (\in_array($params['attr']['value'], $params['value'], true)) {
                                    $params['attr']['checked'] = true;
                                }
                            } elseif ($params['value'] === $params['attr']['value']) {
                                $params['attr']['checked'] = true;
                            }
                        }

                        $buffer .= '<'.$params['type'].$viewHelper->escapeAttr($params['attr']).'>'.PHP_EOL;

                        break;

                    case 'file':
                        unset($params['attr']['value']);

                        $buffer .= '<'.$params['type'].$viewHelper->escapeAttr($params['attr']).'>'.PHP_EOL;

                        break;

                    case 'hidden':
                        $buffer .= '<'.$params['type'].$viewHelper->escapeAttr($params['attr']).'>'.PHP_EOL;

                        break;
                }

                break;

            case 'textarea':
                unset($params['attr']['type'], $params['attr']['value']);

                $buffer .= '<'.$params['type'].$viewHelper->escapeAttr($params['attr']).'>'.$params['value'].'</'.$params['type'].'>'.PHP_EOL;

                break;

            case 'select':
                unset($params['attr']['type'], $params['attr']['value']);

                $optgroup = [];

                $buffer .= '<'.$params['type'].$viewHelper->escapeAttr($params['attr']).'>'.PHP_EOL;

                foreach ($params['options'] as $key => $val) {
                    if (\is_array($val)) {
                        $attr = $val['attr'] ?? ['value' => ''];
                        $value = $val['value'] ?? $key;
                    } else {
                        $attr = (isset($key) && !isBlank($key)) ? ['value' => $key] : ['value' => ''];
                        $value = $val;
                    }

                    if (isset($attr['value'])) {
                        if (\is_array($params['value'])) {
                            if (\in_array($attr['value'], $params['value'], true)) {
                                $attr['selected'] = true;
                            }
                        } elseif ($params['value'] === $attr['value']) {
                            $attr['selected'] = true;
                        }
                    }

                    $option = '<option'.$viewHelper->escapeAttr($attr).'>'.$value.'</option>'.PHP_EOL;

                    if (isset($val['optgroup_id'])) {
                        $optgroup[$val['optgroup_id']][] = $option;
                    } else {
                        $buffer .= $option;
                    }
                }

                if ((is_countable($params['optgroup']) ? \count($params['optgroup']) : 0) > 0) {
                    foreach ($params['optgroup'] as $key => $val) {
                        if (\is_array($val)) {
                            $attr = $val['attr'] ?? ['label' => ''];
                        } else {
                            $attr = ($val) ? ['label' => $val] : ['label' => ''];
                        }

                        $value = (isset($optgroup[$key])) ? PHP_EOL.implode('', $optgroup[$key]) : '';

                        $buffer .= '<optgroup'.$viewHelper->escapeAttr($attr).'>'.$value.'</optgroup>'.PHP_EOL;
                    }
                }

                $buffer .= '</'.$params['type'].'>'.PHP_EOL;

                break;
        }

        return $buffer;
    }

    public function html2Text($html)
    {
        return Html2Text::convert((string) $html);
    }

    public function purifyHtml($html, $params = [])
    {
        $params = ArrayUtils::merge(
            [
                'HTML.Allowed' => 'a[href|target|title], abbr, acronym, b, blockquote, br, caption, cite, code, dd, del, dfn, dl, dt, em, h3, h4, h5, h6, hr, i, img[src|alt], ins, kbd, li, ol, p[align], pre, s, span[style], strike, strong, sub, sup, table[width|cellpadding], tbody, td, tfoot, th, thead, tr, tt, u, ul, var',
                // 'AutoFormat.Linkify' => true,
                'AutoFormat.RemoveEmpty.RemoveNbsp' => true,
                'AutoFormat.RemoveEmpty' => true,

                // FIXED
                // http://htmlpurifier.org/docs#toclink7
                // The target attribute has been deprecated for a long time, so I highly recommend you look at other ways of,
                // say, opening new windows when you click a link (my favorites are “Don't do it!” or, if you must, JavaScript)
                // But if you must, the %Attr.AllowedFrameTargets directive is what you are looking for.
                //
                // https://www.w3.org/TR/2011/WD-html-markup-20110525/a.html
                // The target attribute on the a element was deprecated in a previous version of HTML,
                // but is no longer deprecated, as it useful in Web applications, particularly in combination with the iframe element.
                //
                // https://laracasts.com/discuss/channels/general-discussion/htmlpurifier-allow-target-blank?reply=626294
                'Attr.AllowedFrameTargets' => ['_blank'],
            ],
            $params
        );

        $config = \HTMLPurifier_Config::createDefault();

        foreach ($params as $key => $val) {
            $config->set($key, $val);
        }

        // https://gist.github.com/marcus-at-localhost/a0baec722aefe68cf019bc31f120c3ac
        $def = $config->getHTMLDefinition(true);
        $def->info_tag_transform['div'] = new \HTMLPurifier_TagTransform_Simple('p');

        return new \HTMLPurifier($config)->purify((string) $html);
    }

    // https://oembed.com
    public function oembed($html, $params = [])
    {
        $params = ArrayUtils::merge(
            [
                'env' => 'front',
                'accessToken' => null,
            ],
            $params
        );

        // https://developer.wordpress.org/reference/classes/wp_embed/
        // https://stackoverflow.com/a/23367301/3929620
        if (\Safe\preg_match('#(^|\s|>)https?://#i', (string) $html)) {
            // Find URLs on their own line.
            $html = \Safe\preg_replace_callback('~^(\s*)(https?://[^\s<>"]+)(\s*)$~im', fn ($match) => \call_user_func_array($this->_oembedCallback(...), [$match, $params]), (string) $html);

            // Find URLs in their own paragraph.
            $html = \Safe\preg_replace_callback('~((?: [^>]*)?>\s*)(https?://[^\s<>"]+)(\s*<\/p>)~i', fn ($match) => \call_user_func_array($this->_oembedCallback(...), [$match, $params]), (string) $html);
        }

        return $html;
    }

    private function _oembedCallback($match, $params = [])
    {
        $params = ArrayUtils::merge(
            [
                'env' => 'front',
                'accessToken' => null,
            ],
            $params
        );

        $cache = $this->container->get(CacheInterface::class);

        return $cache->get($cache->getItemKey([
            $this->getShortName(),
            __FUNCTION__,
            __LINE__,
            $match,
            $params,
        ]), function (ItemInterface $cacheItem) use ($match, $params) {
            $html = $match[2];

            try {
                $oembedFactory = $this->container->get(OEmbedFactory::class);

                $options = [];

                // https://github.com/KaneCohen/oembed/issues/3
                // https://github.com/ricardofiorani/oembed#method-2
                // To use 'Meta oEmbed Read', your use of this endpoint must be reviewed and approved by Facebook.
                // To submit this 'Meta oEmbed Read' feature for review please read our documentation on reviewable features:
                // https://developers.facebook.com/docs/apps/review
                if (!empty($params['accessToken']) && (str_contains((string) $html, 'facebook.com') || str_contains((string) $html, 'instagram.com'))) {
                    $options['access_token'] = $params['accessToken'];
                }

                $oembed = $oembedFactory->get($html, $options);

                if (!$oembed) {
                    throw new \RuntimeException('Unable to fetch embed data from: '.$html);
                }

                $this->logger->debug('oEmbed data retrieved successfully', [
                    'url' => $html,
                    'data' => $oembed->data(),
                    'with_access_token' => !empty($options['access_token']),
                ]);

                $html = $oembed->html();

                if ('video' === $oembed->data()['type'] && \Safe\preg_match('~allowfullscreen~i', $html) && \Safe\preg_match('~<iframe~i', $html)) {
                    switch ($this->config->get('theme.'.$params['env'].'.type') ?? $this->config->get('theme.type', true)) {
                        case 'twbs3':
                        case 'twbs4':
                            $html = \Safe\preg_replace('/<iframe/', '<iframe class="embed-responsive-item"', $html);
                            $html = '<div><div class="embed-responsive embed-responsive-16by9">'.PHP_EOL.$html.'</div></div>'.PHP_EOL;

                            break;

                        default:
                            $html = '<div><div class="ratio ratio-16x9">'.PHP_EOL.$html.'</div></div>'.PHP_EOL;
                    }
                }
            } catch (\Exception $e) {
                $this->logger->warning($e->getMessage(), [
                    'exception' => $e,
                    'error' => $e->getMessage(),
                    'text' => $e->getTraceAsString(),
                ]);
            }

            return $match[1].$html.$match[3];
        });
    }
}
