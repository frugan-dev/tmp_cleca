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
?>
<div class="d-none">

    <?php if (!empty($this->config['service.sentry.js.dsn'] ?? $this->config['service.sentry.dsn'] ?? false)) { ?>
        <script>
            if( typeof Sentry !== 'undefined' ) {
                Sentry.init({
                    dsn: '<?php echo $this->escape()->js($this->config['service.sentry.js.dsn'] ?? $this->config['service.sentry.dsn']); ?>',
                    debug: <?php echo (bool) !empty($this->config['debug.enabled'] ?? false) ? 'true' : 'false'; ?>,
                    <?php if (!empty($this->config['service.sentry.js.release'] ?? $this->config['service.sentry.release'] ?? $this->helper->Nette()->Strings()->webalize($this->config['app.name']).'@'.version())) { ?>
                    release: '<?php echo $this->escape()->js($this->config['service.sentry.js.release'] ?? $this->config['service.sentry.release'] ?? $this->helper->Nette()->Strings()->webalize($this->config['app.name']).'@'.version()); ?>',
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.environment'] ?? $this->config['service.sentry.environment'] ?? false)) { ?>
                    environment: '<?php echo $this->escape()->js($this->config['service.sentry.js.environment'] ?? $this->config['service.sentry.environment']); ?>',
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.tunnel'] ?? $this->config['service.sentry.tunnel'] ?? false)) { ?>
                    tunnel: '<?php echo $this->escape()->js($this->config['service.sentry.js.tunnel'] ?? $this->config['service.sentry.tunnel']); ?>',
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.sampleRate'] ?? $this->config['service.sentry.sampleRate'] ?? false)) { ?>
                    sampleRate: <?php echo $this->escape()->js($this->config['service.sentry.js.sampleRate'] ?? $this->config['service.sentry.sampleRate']); ?>,
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.maxBreadcrumbs'] ?? $this->config['service.sentry.maxBreadcrumbs'] ?? false)) { ?>
                    maxBreadcrumbs: <?php echo $this->escape()->js($this->config['service.sentry.js.maxBreadcrumbs'] ?? $this->config['service.sentry.maxBreadcrumbs']); ?>,
                    <?php } ?>
                    <?php if (isset($this->config['service.sentry.js.attachStacktrace']) || isset($this->config['service.sentry.attachStacktrace'])) { ?>
                    attachStacktrace: <?php echo (bool) ($this->config['service.sentry.js.attachStacktrace'] ?? $this->config['service.sentry.attachStacktrace']) ? 'true' : 'false'; ?>,
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.denyUrls'] ?? $this->config['service.sentry.denyUrls'] ?? false)) { ?>
                    denyUrls: '<?php echo $this->escape()->js($this->config['service.sentry.js.denyUrls'] ?? $this->config['service.sentry.denyUrls']); ?>',
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.allowUrls'] ?? $this->config['service.sentry.allowUrls'] ?? false)) { ?>
                    allowUrls: '<?php echo $this->escape()->js($this->config['service.sentry.js.allowUrls'] ?? $this->config['service.sentry.allowUrls']); ?>',
                    <?php } ?>
                    <?php if (isset($this->config['service.sentry.js.autoSessionTracking']) || isset($this->config['service.sentry.autoSessionTracking'])) { ?>
                    autoSessionTracking: <?php echo (bool) ($this->config['service.sentry.js.autoSessionTracking'] ?? $this->config['service.sentry.autoSessionTracking']) ? 'true' : 'false'; ?>,
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.initialScope'] ?? $this->config['service.sentry.initialScope'] ?? false)) { ?>
                    initialScope: <?php echo $this->config['service.sentry.js.initialScope'] ?? $this->config['service.sentry.initialScope']; ?>,
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.maxValueLength'] ?? $this->config['service.sentry.maxValueLength'] ?? false)) { ?>
                    maxValueLength: <?php echo $this->escape()->js($this->config['service.sentry.js.maxValueLength'] ?? $this->config['service.sentry.maxValueLength']); ?>,
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.normalizeDepth'] ?? $this->config['service.sentry.normalizeDepth'] ?? false)) { ?>
                    normalizeDepth: <?php echo $this->config['service.sentry.js.normalizeDepth'] ?? $this->config['service.sentry.normalizeDepth']; ?>,
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.integrations'] ?? $this->config['service.sentry.integrations'] ?? false)) { ?>
                    integrations: '<?php echo $this->escape()->js($this->config['service.sentry.js.integrations'] ?? $this->config['service.sentry.integrations']); ?>',
                    <?php } ?>
                    <?php if (isset($this->config['service.sentry.js.defaultIntegrations']) || isset($this->config['service.sentry.defaultIntegrations'])) { ?>
                    defaultIntegrations: <?php echo (bool) ($this->config['service.sentry.js.defaultIntegrations'] ?? $this->config['service.sentry.defaultIntegrations']) ? 'true' : 'false'; ?>,
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.beforeSend'] ?? $this->config['service.sentry.beforeSend'] ?? false)) { ?>
                    beforeSend: <?php echo $this->escape()->js($this->config['service.sentry.js.beforeSend'] ?? $this->config['service.sentry.beforeSend']); ?>,
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.jsbeforeBreadcrumb'] ?? $this->config['service.sentry.beforeBreadcrumb'] ?? false)) { ?>
                    beforeBreadcrumb: <?php echo $this->escape()->js($this->config['service.sentry.jsbeforeBreadcrumb'] ?? $this->config['service.sentry.beforeBreadcrumb']); ?>,
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.transport'] ?? $this->config['service.sentry.transport'] ?? false)) { ?>
                    transport: '<?php echo $this->escape()->js($this->config['service.sentry.js.transport'] ?? $this->config['service.sentry.transport']); ?>',
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.tracesSampleRate'] ?? $this->config['service.sentry.tracesSampleRate'] ?? false)) { ?>
                    tracesSampleRate: <?php echo $this->escape()->js($this->config['service.sentry.js.tracesSampleRate'] ?? $this->config['service.sentry.tracesSampleRate']); ?>,
                    <?php } ?>
                    <?php if (!empty($this->config['service.sentry.js.tracesSampler'] ?? $this->config['service.sentry.tracesSampler'] ?? false)) { ?>
                    tracesSampler: <?php echo $this->escape()->js($this->config['service.sentry.js.tracesSampler'] ?? $this->config['service.sentry.tracesSampler']); ?>,
                    <?php } ?>
                });
                <?php if (!empty($this->clientIp)) { ?>
                Sentry.setUser({
                    'ip_address': '<?php echo $this->escape()->js($this->clientIp); ?>',
                });
                <?php } ?>
                Sentry.setTag('locale', jsObj.locale );
            }
        </script>
    <?php } ?>

</div>
