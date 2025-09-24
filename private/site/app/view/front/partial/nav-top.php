<?php declare(strict_types=1);

$value = settingOrConfig('block.header');
?>
<div class="row align-items-center gy-3">
    <div class="col-7 col-md-3">
        <?php // https://stackoverflow.com/a/66674141/3929620?>
        <h1 class="visually-hidden">
            <?php echo $this->escape()->html(settingOrConfig(['brand.name', 'company.name'])); ?>
        </h1>
        <a class="d-inline-block" rel="home"<?php echo $this->escapeAttr([
            'href' => $this->uri($this->env.'.index'),
            'title' => __('Home'),
        ]); ?>>
            <img class="img-fluid"<?php echo $this->escapeAttr([
                // TODO - use setting?
                'src' => $this->asset('asset/'.$this->env.'/img/logo/sm.png'),
                'alt' => settingOrConfig(['brand.name', 'company.name']),
            ]); ?>>
        </a>
    </div>
    <div<?php echo $this->escapeAttr([
        'class' => array_merge(['col-5', 'order-md-last'], empty($value) ? ['col-md'] : ['col-md-3']),
    ]); ?>>
        <div class="row justify-content-end">
            <div class="col-auto">
                <?php
                if (!$this->hasSection('langs')) {
                    $this->setSection('langs', $this->render('langs', [
                        'tooltipPlacement' => 'bottom',
                    ]));
                }
echo $this->getSection('langs');
?>
            </div>
        </div>

        <nav class="navbar navbar-expand">
            <div class="container-fluid px-0">
                <ul class="navbar-nav ms-auto">

                    <?php if (!$this->auth->hasIdentity()) { ?>
                        <li class="nav-item">
                            <a class="nav-link"<?php echo $this->escapeAttr([
                                'href' => $this->uri([
                                    'routeName' => $this->env.'.action',
                                    'data' => [
                                        'action' => 'login',
                                    ],
                                ]),
                                'title' => __('Login'),
                            ]); ?>>
                                <i class="fas fa-right-to-bracket fa-fw me-xl-1"></i>
                                <span class="d-none d-xl-inline">
                                    <?php echo $this->escape()->html(__('Login')); ?>
                                </span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link"<?php echo $this->escapeAttr([
                                'href' => $this->uri([
                                    'routeName' => $this->env.'.member',
                                    'data' => [
                                        'action' => 'signup',
                                    ],
                                ]),
                                'title' => __('Signup'),
                            ]); ?>>
                                <i class="fas fa-user-plus fa-fw me-xl-1"></i>
                                <span class="d-none d-xl-inline">
                                    <?php echo $this->escape()->html(__('Signup')); ?>
                                </span>
                            </a>
                        </li>
                    <?php } ?>

                    <?php if (!empty($this->config['theme.switcher'])) { ?>
                        <li class="nav-item dropdown">
                            <button class="btn-switcher nav-link dropdown-toggle" type="button" aria-expanded="false" data-bs-toggle="dropdown" data-bs-display="static"<?php echo $this->escapeAttr([
                                'aria-label' => sprintf(__('Toggle theme (%1$s)'), $this->helper->Nette()->Strings()->firstUpper(__('light'))),
                            ]); ?>>
                                <i class="fas fa-circle-half-stroke fa-fw me-xxl-1"></i>
                                <span class="btn-switcher-text d-none d-xxl-inline">
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

                    <?php if ($this->auth->hasIdentity()) { ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" type="button" aria-expanded="false" data-bs-toggle="dropdown" data-bs-display="static">
                                <i class="fas fa-user fa-fw me-xl-1"></i>
                                <span class="d-none d-xl-inline">
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

                                <?php if ('user' === $this->auth->getIdentity()['_type']) { ?>
                                    <li>
                                        <a class="dropdown-item" target="_blank"<?php echo $this->escapeAttr([
                                            'href' => $this->uri([
                                                'routeName' => 'back.index.lang',
                                                'data' => [
                                                    'lang' => $this->config['lang.back.arr'][$this->auth->getIdentity()['lang_id']]['isoCode'] ?? $this->config['lang.arr'][$this->auth->getIdentity()['lang_id']]['isoCode'] ?? null,
                                                ],
                                            ]),
                                            'title' => __('Administration'),
                                        ]); ?>>
                                            <i class="fas fa-gears fa-fw me-1"></i>
                                            <?php echo $this->escape()->html(__('Administration')); ?>
                                        </a>
                                    <li>
                                <?php } else { ?>
                                    <li>
                                        <a class="dropdown-item"<?php echo $this->escapeAttr([
                                            'href' => $this->uri([
                                                'routeName' => $this->env.'.'.$this->auth->getIdentity()['_type'].'.params',
                                                'data' => [
                                                    'action' => 'edit',
                                                    'params' => $this->auth->getIdentity()['id'],
                                                ],
                                            ]),
                                            'title' => __('Profile'),
                                        ]); ?>>
                                            <i class="fas fa-user-circle fa-fw me-1"></i>
                                            <?php echo $this->escape()->html(__('Profile')); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"<?php echo $this->escapeAttr([
                                            'href' => $this->uri([
                                                'routeName' => $this->env.'.'.$this->auth->getIdentity()['_type'].'.params',
                                                'data' => [
                                                    'action' => 'setting',
                                                    'params' => $this->auth->getIdentity()['id'],
                                                ],
                                            ]),
                                            'title' => _n('Setting', 'Settings', 2),
                                        ]); ?>>
                                            <i class="fas fa-gear fa-fw me-1"></i>
                                            <?php echo $this->escape()->html(_n('Setting', 'Settings', 2)); ?>
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item"<?php echo $this->escapeAttr([
                                            'href' => $this->uri($this->env.'.log'),
                                            'title' => $this->container->get('Mod\Log\\'.ucfirst((string) $this->env))->pluralName,
                                        ]); ?>>
                                            <i class="fas fa-book fa-fw me-1"></i>
                                            <?php echo $this->escape()->html($this->container->get('Mod\Log\\'.ucfirst((string) $this->env))->pluralName); ?>
                                        </a>
                                    </li>
                                <?php } ?>

                                <li>
                                    <a class="dropdown-item"<?php echo $this->escapeAttr([
                                        'href' => $this->uri([
                                            'routeName' => $this->env.'.'.$this->auth->getIdentity()['_type'],
                                            'data' => [
                                                'action' => 'logout',
                                            ],
                                        ]),
                                        'title' => __('Logout'),
                                    ]); ?>>
                                        <i class="fas fa-sign-out-alt fa-fw me-1"></i>
                                        <?php echo $this->escape()->html(__('Logout')); ?>
                                    </a>
                                <li>
                            </ul>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        </nav>
    </div>
    <?php if (!empty($value)) { ?>
        <div class="col text-center">
            <h1 class="fs-6 fs-lg-5 fs-xl-4 fw-bold mb-0">
                <?php echo nl2br((string) $value); ?>
            </h1>
        </div>
    <?php } ?>
</div>

<?php
// https://getbootstrap.com/docs/5.3/customize/color-modes/
// https://stackoverflow.com/a/57795495/3929620
// https://www.designcise.com/web/tutorial/how-to-fix-the-javascript-typeerror-matchmedia-addeventlistener-is-not-a-function
$this->scripts()->beginInternal();
echo '(() => {
    '.(!empty($this->config['theme.switcher']) ? '
    const storedTheme = localStorage.getItem("theme");

    window.getPreferredTheme = () => {
        if (storedTheme) {
            return storedTheme;
        }

        return window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
    }

    const setTheme = function (theme) {
        document.documentElement.setAttribute("data-bs-theme", theme);

        const event = new CustomEvent("setTheme.after", {
          detail: {
            theme: theme,
          },
        });
        document.dispatchEvent(event);
    }

    setTheme(getPreferredTheme());

    const showActiveTheme = (theme, focus = false) => {
        const btnSwitcherEl = document.querySelector(".btn-switcher");
        const btnSwitcherTextEl = document.querySelector(".btn-switcher-text");
        const btnThemeActiveEl = document.querySelector(`[data-bs-theme-value="${theme}"]`);

        if (!btnSwitcherEl || !btnSwitcherTextEl || !btnThemeActiveEl) {
            return;
        }

        const btnThemeActiveIconEl = btnThemeActiveEl.querySelector(".btn-theme-icon");

        if (!btnThemeActiveIconEl) {
            return;
        }

        document.querySelectorAll("[data-bs-theme-value]").forEach(el => {
            el.classList.remove("active");
            el.setAttribute("aria-pressed", "false");
        })
        document.querySelectorAll("[data-bs-theme-value] .btn-theme-icon").forEach(el => {
            el.classList.add("d-none");
        })

        btnThemeActiveEl.classList.add("active");
        btnThemeActiveEl.setAttribute("aria-pressed", "true");
        btnThemeActiveIconEl.classList.remove("d-none");

        const btnSwitcherLabel = `${btnSwitcherTextEl.textContent} (${btnThemeActiveEl.dataset.bsThemeValue})`;
        btnSwitcherEl.setAttribute("aria-label", btnSwitcherLabel);

        if (focus) {
            btnSwitcherEl.focus();
        }
    }

    const mediaQuery = window.matchMedia("(prefers-color-scheme: dark)");
    if (mediaQuery.addEventListener) {
        mediaQuery.addEventListener("change", () => {
            if (storedTheme !== "light" && storedTheme !== "dark") {
                setTheme(getPreferredTheme());
            }
        });
    } else {
        mediaQuery.addListener(() => {
            if (storedTheme !== "light" && storedTheme !== "dark") {
                setTheme(getPreferredTheme());
            }
        });
    }

    window.addEventListener("DOMContentLoaded", () => {
        showActiveTheme(getPreferredTheme());

        document.querySelectorAll("[data-bs-theme-value]")
            .forEach(toggle => {
                toggle.addEventListener("click", () => {
                    const theme = toggle.getAttribute("data-bs-theme-value");
                    localStorage.setItem("theme", theme);
                    setTheme(theme);
                    showActiveTheme(theme, true);
                })
            })
    });
    ' : '
    localStorage.removeItem("theme");
    ').'
})();';
$this->scripts()->endInternal();
