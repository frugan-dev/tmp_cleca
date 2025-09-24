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

<?php // https://stackoverflow.com/a/44577512?>
<nav class="navbar navbar-expand bg-light py-1 border-bottom">
    <div class="container-fluid">
        <ul class="navbar-nav ms-auto">

            <?php
            if (!empty($this->browscapInfo)) {
                if (!empty($this->browscapInfo->ismobiledevice)) {
                    ?>
                <li class="nav-item wrapper-install" style="display: none">
                    <button class="btn-install nav-link" type="button"<?php echo $this->escapeAttr([
                        'data-title' => sprintf(__('Install %1$s'), settingOrConfig(['brand.shortName', 'brand.name', 'company.shortName', 'company.name', 'app.name'])),
                        // https://uiux.blog/stop-using-the-term-click-in-a-mobile-experience-479b8ed4f567
                        'data-intro' => sprintf(__('You can install %1$s on your device by tapping on the button above!'), '<i>'.settingOrConfig(['brand.shortName', 'brand.name', 'company.shortName', 'company.name', 'app.name']).'</i>'),
                    ]); ?>>
                        <i class="fas fa-mobile-alt fa-fw me-sm-1"></i>
                        <span class="d-none d-sm-inline">
                            <?php echo $this->escape()->html(__('Install')); ?>
                        </span>
                    </button>
                </li>
            <?php
                }
            }
?>

            <?php if (!empty($this->config['theme.switcher'])) { ?>
                <li class="nav-item dropdown">
                    <button class="btn-switcher nav-link dropdown-toggle" type="button" aria-expanded="false" data-bs-toggle="dropdown" data-bs-display="static"<?php echo $this->escapeAttr([
                        'aria-label' => sprintf(__('Toggle theme (%1$s)'), $this->helper->Nette()->Strings()->firstUpper(__('light'))),
                    ]); ?>>
                        <i class="fas fa-circle-half-stroke fa-fw me-sm-1"></i>
                        <span class="btn-switcher-text d-none d-sm-inline">
                            <?php echo $this->escape()->html(__('Theme')); ?>
                        </span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="btn-switcher-text">
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center active" data-bs-theme-value="light" aria-pressed="false">
                                <i class="fa-regular fa-sun fa-fw me-1"></i>
                                <?php echo $this->escape()->html($this->helper->Nette()->Strings()->firstUpper(__('light'))); ?>
                                <span class="btn-theme-icon ms-auto d-none">
                                    <i class="fas fa-check"></i>
                                </span>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark" aria-pressed="false">
                                <i class="fas fa-moon fa-fw me-1"></i>
                                <?php echo $this->escape()->html($this->helper->Nette()->Strings()->firstUpper(__('dark'))); ?>
                                <span class="btn-theme-icon ms-auto d-none">
                                    <i class="fas fa-check"></i>
                                </span>
                            </button>
                        </li>
                    </ul>
                </li>
            <?php } ?>

            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" type="button" aria-expanded="false" data-bs-toggle="dropdown" data-bs-display="static">
                    <i class="fas fa-user fa-fw me-sm-1"></i>
                    <span class="d-none d-sm-inline">
                        <?php echo $this->escape()->html(__('Account')); ?>
                    </span>
                </a>
                <ul class="dropdown-menu dropdown-menu-end">

                    <?php if (!empty($authName = $this->auth->getIdentity()['_name'] ?? null)) { ?>
                        <li>
                            <h6 class="dropdown-header hstack gap-1 justify-content-between">
                                <div>
                                    <?php echo $this->escape()->html($this->helper->Nette()->Strings()->truncate($authName, 30)); ?>
                                </div>
                                <?php if (!empty($authCatName = $this->auth->getIdentity()[$this->auth->getIdentity()['_role_type'].'_name'] ?? null)) { ?>
                                    <span class="badge bg-secondary">
                                        <?php echo $this->escape()->html($this->helper->Nette()->Strings()->truncate($authCatName, 30)); ?>
                                    </span>
                                <?php } ?>
                            </h6>
                        </li>
                        <li>
                            <div class="dropdown-divider"></div>
                        </li>
                    <?php } ?>

                    <li>
                        <a class="dropdown-item"<?php echo $this->escapeAttr([
                            'href' => $this->uri([
                                'routeName' => $this->env.'.user.params',
                                'data' => [
                                    'action' => 'edit',
                                    'params' => $this->auth->getIdentity()['id'],
                                ],
                            ]),
                            'title' => __('Profile'),
                        ]); ?>>
                            <i class="fas fa-user-circle fa-fw me-1"></i> <?php echo $this->escape()->html(__('Profile')); ?>
                        </a>
                    </li>

                    <?php if ($this->rbac->isGranted('catuser.'.$this->env.'.view-api')) { ?>
                        <li>
                            <a class="dropdown-item"<?php echo $this->escapeAttr([
                                'href' => $this->uri([
                                    'routeName' => $this->env.'.catuser',
                                    'data' => [
                                        'action' => 'view-api',
                                    ],
                                ]),
                                'title' => __('API Swagger'),
                            ]); ?>>
                                <i class="fas fa-code fa-fw me-1"></i> <?php echo $this->escape()->html(__('API Swagger')); ?>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if ($this->rbac->isGranted('user.'.$this->env.'.delete-cache')) { ?>
                        <li>
                            <a class="dropdown-item"<?php echo $this->escapeAttr([
                                'href' => $this->uri([
                                    'routeName' => $this->env.'.user',
                                    'data' => [
                                        'action' => 'delete-cache',
                                    ],
                                ]),
                                'title' => __('Clear cache'),
                            ]); ?>>
                                <i class="fas fa-broom fa-fw me-1"></i> <?php echo $this->escape()->html(__('Clear cache')); ?>
                            </a>
                        </li>
                    <?php } ?>

                    <li>
                        <a class="dropdown-item" target="_blank"<?php echo $this->escapeAttr([
                            'href' => $this->uri([
                                'routeName' => 'front.index.lang',
                                'data' => [
                                    'lang' => $this->config['lang.front.arr'][$this->auth->getIdentity()['lang_id']]['isoCode'] ?? $this->config['lang.front.arr'][$this->config['lang.front.fallbackId'] ?? null]['isoCode'] ?? $this->config['lang.front.arr'][$this->config['lang.fallbackId']]['isoCode'] ?? null,
                                ],
                            ]),
                            'title' => __('Public website'),
                        ]); ?>>
                            <i class="fas fa-arrow-up-right-from-square fa-fw me-1"></i> <?php echo $this->escape()->html(__('Public website')); ?>
                        </a>
                    <li>

                    <li>
                        <a class="dropdown-item"<?php echo $this->escapeAttr([
                            'href' => $this->uri([
                                'routeName' => $this->env.'.user',
                                'data' => [
                                    'action' => 'logout',
                                ],
                            ]),
                            'title' => __('Logout'),
                        ]); ?>>
                            <i class="fas fa-sign-out-alt fa-fw me-1"></i> <?php echo $this->escape()->html(__('Logout')); ?>
                        </a>
                    <li>
                </ul>
            </li>
        </ul>
    </div>
</nav>

<?php
if (!$this->hasSection('theme-switcher')) {
    $this->setSection('theme-switcher', $this->render('theme-switcher'));
}
echo $this->getSection('theme-switcher');

// https://web.dev/customize-install/
// https://www.amitmerchant.com/adding-custom-install-button-in-progressive-web-apps/
// https://www.sohamkamani.com/blog/javascript-localstorage-with-ttl-expiry/
if (!empty($this->browscapInfo)) {
    if (!empty($this->browscapInfo->ismobiledevice)) {
        $this->scriptsFoot()->beginInternal();
        echo '(() => {
    const isStandalone = window.matchMedia("(display-mode: standalone)").matches;

    if (document.referrer.startsWith("android-app://")) {
        console.log(`Display mode: "twa"`);
    } else if (navigator.standalone || isStandalone) {
        console.log(`Display mode: "standalone"`);
    } else {
        console.log(`Display mode: "browser"`);

        const iBtnEl = document.querySelector(".btn-install");
        const iWrapperEl = document.querySelector(".wrapper-install");
        if(!!iBtnEl && !!iWrapperEl) {
            let deferredPrompt;

            const now = new Date();
            const iPopoverTime = parseInt(localStorage.getItem("iPopoverTime"));

            iBtnEl.addEventListener("click", async () => {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                console.log(`User response to the install prompt: ${outcome}`);
                deferredPrompt = null;
            });

            window.addEventListener("beforeinstallprompt", (e) => {
                e.preventDefault();

                if(typeof deferredPrompt === "undefined") {
                    iWrapperEl.style.display = "";
                    '.(!empty($this->config['session.localStorage.installPopover.time']) ? '
                        if(!!!iPopoverTime || now.getTime() > (iPopoverTime + '.$this->escape()->js($this->config['session.localStorage.installPopover.time']).')) {
                            introJs().setOptions({
                                showButtons: false,
                                showBullets: false,
                            }).start();

                            localStorage.setItem("iPopoverTime", now.getTime());
                        }
                    ' : '').'
                }

                deferredPrompt = e;
                console.log(`"beforeinstallprompt" event was fired`);
            });

            window.addEventListener("appinstalled", () => {
                deferredPrompt = null;
                iWrapperEl.style.display = "none";
                App.Mod.Toast.add("'.$this->escape()->js(__('Operation performed successfully.')).'", "success").show();
                console.log("PWA was installed");
            });
        }
    }
})();';
        $this->scriptsFoot()->endInternal();
    }
}
