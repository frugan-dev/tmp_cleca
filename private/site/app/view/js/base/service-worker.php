<?php

declare(strict_types=1);

// https://github.com/mozilla/serviceworker-cookbook

$env = 'front';
$checkFile = _PUBLIC.'/asset/'.$env.'/site.webmanifest';

if (file_exists($checkFile)) {
    $lastMtime = \Safe\filemtime($checkFile);
} else {
    // http://stackoverflow.com/a/7448828
    $outputRoot = \Safe\shell_exec('find '.escapeshellarg(_ROOT.'/app').' -type f -printf "%T@\n"| sort -nr | head -n1');
    $outputRoot = !empty($outputRoot) ? (int) trim(substr($outputRoot, 0, strpos($outputRoot, '.'))) : time();

    $outputPublic = \Safe\shell_exec('find '.escapeshellarg((string) _PUBLIC).' -type f -printf "%T@\n"| sort -nr | head -n1');
    $outputPublic = !empty($outputPublic) ? (int) trim(substr($outputPublic, 0, strpos($outputPublic, '.'))) : time();

    $lastMtime = max($outputRoot, $outputPublic);
}
?>
const CACHE_NAME = 'cache-version-<?php echo $this->escape()->js($lastMtime); ?>';
const OFFLINE_URL = '<?php echo $this->uri([
    'routeName' => $env.'.action',
    'data' => [
        'action' => 'offline',
    ],
]); ?>';

var REQUIRED_FILES = [
    OFFLINE_URL,
    '<?php echo $this->asset('asset/'.$env.'/img/favicon/apple-touch-icon.png'); ?>',
    '<?php echo $this->asset('asset/'.$env.'/img/favicon/favicon-32x32.png'); ?>',
    '<?php echo $this->asset('asset/'.$env.'/img/favicon/favicon-16x16.png'); ?>',
    '<?php echo $this->asset('asset/'.$env.'/img/favicon/favicon.ico'); ?>',
    '<?php echo $this->asset('asset/'.$env.'/site.webmanifest'); ?>',
    'https://cdnjs.cloudflare.com/ajax/libs/bulma/0.9.4/css/bulma.min.css',
];

self.addEventListener('install', function(event) {
  // Perform install step:  loading each required file into cache
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(function(cache) {
        // Add all offline dependencies to the cache
        return cache.addAll(REQUIRED_FILES);
      })
      .then(function() {
        return self.skipWaiting();
      })
  );
});

//https://stackoverflow.com/a/51694823/3929620
self.addEventListener('fetch', function(event) {
    //console.log('[Service Worker] Fetch', event.request.url);
    if (event.request.mode === 'navigate') {
        event.respondWith((async () => {
            try {
                const preloadResponse = await event.preloadResponse;
                if (preloadResponse) {
                    return preloadResponse;
                }

                const networkResponse = await fetch(event.request);
                return networkResponse;
            } catch (error) {
                console.log('[Service Worker] Fetch failed; returning offline page instead.', error);

                const cache = await caches.open(CACHE_NAME);
                const cachedResponse = await cache.match(OFFLINE_URL);
                return cachedResponse;
            }
        })());
    }
});

self.addEventListener('activate', function(event) {
  // Calling claim() to force a "controllerchange" event on navigator.serviceWorker
  event.waitUntil(self.clients.claim());
});
