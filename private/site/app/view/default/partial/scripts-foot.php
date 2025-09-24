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

$this->scriptsFoot()->beginInternal(
    0 // default: 100
);
if (!empty($this->jsObj)) {
    echo 'var jsObj = {';
    $arr = [];
    foreach ($this->jsObj as $key => $val) {
        if (is_bool($val)) {
            $val = $val ? 'true' : 'false';
        } elseif (str_starts_with((string) $val, '#RAW#')) {
            $val = substr((string) $val, strlen('#RAW#'));
        } elseif (!is_int($val)) {
            $val = '\''.$this->escape()->js($val).'\'';
        }
        $arr[] = $this->escape()->js($key).': '.$val;
    }
    echo implode(', ', $arr);
    echo '};';
}
$this->scriptsFoot()->endInternal();

// https://codemirror.net/6/examples/ie11/
// https://cdnjs.cloudflare.com/polyfill
// https://cert-agid.gov.it/news/scoperto-un-grave-attacco-alla-supply-chain-del-servizio-polyfill-io-piu-di-100-000-i-siti-coinvolti/
// Static Polyfills: Unlike Polyfill.io, which conditionally applies polyfills
// based on the browser's native support (using the 'gated' flag), the polyfills
// provided via cdnjs are static. This means all polyfills specified in the
// 'features' parameter will be applied unconditionally, even if the browser
// already supports them natively.
//
// Best Practice: For optimal performance and control, it is recommended to include
// only the required polyfills during the build process using a modular library
// like core-js. This ensures that only necessary polyfills are bundled and applied.
$this->scriptsFoot()->add(
    'https://cdnjs.cloudflare.com/polyfill/v3/polyfill.min.js?'.$this->helper->Url()->httpBuildQuery([
        'version' => '4.8.0',
        'features' => implode(',', [
            'default',
            'IntersectionObserver',
            'smoothscroll',
            'String.prototype.replaceAll',
        ]),
    ]),
    0 // default: 100
);

$items = [
    80 => 'asset/'.$this->env.'/js/'.($this->config['debug.enabled'] ? '' : 'min/').'node/',
    90 => 'asset/'.$this->env.'/js/'.($this->config['debug.enabled'] ? '' : 'min/'),
];

foreach ($items as $weightBase => $path) {
    if (is_dir(_PUBLIC.'/'.$path)) {
        // in() searches only the current directory, while from() searches its subdirectories too (recursively)
        foreach ($this->helper->Nette()->Finder()->findFiles('*.js')->in(_PUBLIC.'/'.$path)->sortByName() as $fileObj) {
            $weight = $weightBase;
            if (!empty($this->config['asset.'.$this->env.'.js.weight'] ?? $this->config['asset.js.weight'] ?? false)) {
                $keys = array_keys($this->config['asset.'.$this->env.'.js.weight'] ?? $this->config['asset.js.weight']);

                if (\Safe\preg_match('~'.implode('|', array_map('preg_quote', $keys, array_fill(0, is_countable($keys) ? count($keys) : 0, '~'))).'~i', $fileObj->getBasename('.js'), $matches)) {
                    $weight = $this->config['asset.'.$this->env.'.js.weight'][$matches[0]] ?? $this->config['asset.js.weight'][$matches[0]];
                }
            }

            $attr = [];
            if (!empty($this->config['asset.'.$this->env.'.js.attr'] ?? $this->config['asset.js.attr'] ?? false)) {
                $keys = array_keys($this->config['asset.'.$this->env.'.js.attr'] ?? $this->config['asset.js.attr']);

                if (\Safe\preg_match('~'.implode('|', array_map('preg_quote', $keys, array_fill(0, is_countable($keys) ? count($keys) : 0, '~'))).'~i', $fileObj->getBasename('.js'), $matches)) {
                    $attr = $this->config['asset.'.$this->env.'.js.attr'][$matches[0]] ?? $this->config['asset.js.attr'][$matches[0]];
                }
            }

            $this->scriptsFoot()->add(
                $this->asset($path.$fileObj->getFilename()),
                $weight, // default: 100
                $attr
            );
        }
    }
}

foreach (['toast', 'modal', 'fancybox'] as $item) {
    if (!$this->hasSection('flash-'.$item)) {
        $this->setSection('flash-'.$item, $this->render('flash-'.$item));
    }
    echo $this->getSection('flash-'.$item);
}

if (!$this->hasSection('sse')) {
    $this->setSection('sse', $this->render('sse'));
}
echo $this->getSection('sse');

if (!empty($this->loadCookieConsent)) {
    $this->scriptsFoot()->add(
        $this->config['cookie.consent.cdn.url']
        .'/api/cc.js/v,'.$this->escape()->js($this->config['cookie.consent.version'])
        .'/min,'.(!empty($this->config['debug.enabled']) ? 'false' : 'true')
        .'/lang,'.$this->escape()->js($this->lang->code)
        .'/google_analytics_table,true'
        .(!empty($this->loadGA) && !empty($this->config['service.'.$this->env.'.google.analytics.code'] ?? $this->config['service.google.analytics.code'] ?? false) ? '/google_analytics_code,'.($this->config['service.'.$this->env.'.google.analytics.code'] ?? $this->config['service.google.analytics.code']) : '') // <--
        .(!isDev() && !empty($this->config['service.'.$this->env.'.shinystat.user'] ?? $this->config['service.shinystat.user'] ?? false) ? 'shinystat_table,true/shinystat_code,'.$this->escape()->js($this->config['service.'.$this->env.'.shinystat.user'] ?? $this->config['service.shinystat.user']) : ''),
        100, // default: 100
        [
            // https://pagespeedchecklist.com/async-and-defer
            // Using both async and defer on the same <script> reference may also cause inconsistent or undesirable cross-browser behavior.
            // 'async' => true,
            'defer' => true,
        ]
    );
}

if (!empty($this->loadPrint)) {
    $this->scriptsFoot()->beginInternal();
    echo '(() => {
    if(typeof(window.print) !== "undefined") {
        window.print();
    }
})();';
    $this->scriptsFoot()->endInternal();
}

echo $this->scriptsFoot();

if (!$this->hasSection('scripts-foot-raw')) {
    $this->setSection('scripts-foot-raw', $this->render('scripts-foot-raw'));
}
echo $this->getSection('scripts-foot-raw');
