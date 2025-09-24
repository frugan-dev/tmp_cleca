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

// https://www.w3schools.com/howto/howto_js_collapse_sidebar.asp
// https://getbootstrap.com/docs/5.0/examples/sidebars/
// https://dev.to/codeply/bootstrap-5-sidebar-examples-38pb
// https://stackoverflow.com/a/37113430
?>
<div class="sidebar position-fixed top-0 start-0 vh-100 bg-gray-500 border-end">
    <a class="d-block text-center p-2"<?php echo $this->escapeAttr([
        'href' => $this->uri($this->env.'.index'),
        'title' => __('Dashboard'),
    ]); ?>>
        <img class="img-fluid d-none-collapsed d-inline-expanded" alt=""<?php echo $this->escapeAttr([
            'src' => !empty($this->config['credits.asset.url']) ? $this->config['credits.asset.url'].'/img/logo/3square/md.png' : $this->asset('asset/'.$this->env.'/img/logo/sm.png'),
        ]); ?>>
        <img class="img-fluid d-none-expanded d-inline-collapsed" alt=""<?php echo $this->escapeAttr([
            'src' => !empty($this->config['credits.asset.url']) ? $this->config['credits.asset.url'].'/img/favicon/favicon-32x32.png' : $this->asset('asset/'.$this->env.'/img/favicon/favicon-32x32.png'),
        ]); ?>>
    </a>

    <hr class="my-0">

    <nav class="navbar-primary nav nav-fill flex-column mb-0">

        <?php if (empty($this->config['mod.index.'.$this->env.'.redirect'] ?? false)) { ?>
            <a data-bs-toggle="tooltip" data-bs-placement="right"<?php echo $this->escapeAttr([
                'href' => $this->uri($this->env.'.index'),
                'title' => __('Dashboard'),
                'class' => array_merge(['nav-link'], ('index' === $this->controller) ? ['active'] : []),
            ]); ?>>
                <i class="fas fa-tachometer-alt fa-fw"></i>
                <span class="d-none-collapsed d-inline-expanded ms-1">
                    <?php echo $this->escape()->html(__('Dashboard')); ?>
                </span>
            </a>
        <?php } ?>

        <?php
        if (!$this->hasSection('main-nav-aside')) {
            $this->setSection('main-nav-aside', $this->render('main-nav-aside'));
        }
echo $this->getSection('main-nav-aside');
?>
        <hr class="my-0">

        <?php // https://stackoverflow.com/a/28316762/3929620?>
        <span data-bs-toggle="collapse" data-bs-target="body" aria-expanded="false"<?php /* aria-controls="body" */ ?>>
            <a data-bs-toggle="tooltip" data-bs-placement="right" class="nav-link" href="javascript:;"<?php echo $this->escapeAttr([
                'title' => __('Toggle sidebar'),
            ]); ?>>
                <i class="fas fa-angle-double-left fa-fw d-none-collapsed d-inline-expanded"></i>
                <i class="fas fa-angle-double-right fa-fw d-none-expanded d-inline-collapsed"></i>
            </a>
        </span>

    </nav>

    <div class="d-none-collapsed d-block-expanded">
        <hr class="my-0">

        <div class="small p-3">

            <?php
    if (!$this->hasSection('address-credits')) {
        $this->setSection('address-credits', $this->render('address-credits'));
    }
echo $this->getSection('address-credits');
?>

            <nav class="navbar-secondary nav nav-fill flex-column">

                <?php if (!empty($this->lang->arr[$this->lang->id]['termsUrl'])) { ?>
                    <a class="nav-link" target="_blank"<?php echo $this->escape()->attr([
                        'href' => $this->lang->arr[$this->lang->id]['termsUrl'],
                        'title' => __('Terms of Service'),
                    ]); ?>>
                        <?php echo $this->escape()->html(__('Terms of Service')); ?>
                    </a>
                <?php } ?>

                <?php if (!empty($this->lang->arr[$this->lang->id]['privacyUrl'])) { ?>
                    <a class="nav-link" target="_blank"<?php echo $this->escape()->attr([
                        'href' => $this->lang->arr[$this->lang->id]['privacyUrl'],
                        'title' => __('Privacy Policy'),
                    ]); ?>>
                        <?php echo $this->escape()->html(__('Privacy Policy')); ?>
                    </a>
                <?php } ?>

                <?php if (!empty($this->lang->arr[$this->lang->id]['cookieUrl'])) { ?>
                    <a class="nav-link" target="_blank"<?php echo $this->escape()->attr([
                        'href' => $this->lang->arr[$this->lang->id]['cookieUrl'],
                        'title' => __('Cookie Policy'),
                    ]); ?>>
                        <?php echo $this->escape()->html(__('Cookie Policy')); ?>
                    </a>
                <?php } ?>

            </nav>

        </div>
    </div>
</div>

<div class="sidebar-backdrop fade" data-bs-toggle="collapse" data-bs-target="body" aria-expanded="false"<?php /* aria-controls="body" */ ?>></div>

<?php
if (!isset($this->cookieData[$this->config['cookie.sidebar.name']])) {
    $this->scriptsFoot()->beginInternal(
        1 // default: 100
    );
    // https://stackoverflow.com/a/27612019/3929620
    echo '(() => {
    if( window.innerWidth >= 992 && !document.body.classList.contains("show") ) {
        document.body.classList.add("show");
    }
})();';
    $this->scriptsFoot()->endInternal();
}

$this->scriptsFoot()->beginInternal();
echo '(() => {
    const collapseEl = document.body;
    const sidebarEl = document.querySelector(".sidebar");
    const sidebarBackdropEl = document.querySelector(".sidebar-backdrop");

    if( !!collapseEl && !!sidebarEl && !!sidebarBackdropEl ) {
        const showSidebarCollapse = (() => {
            let collapse = Collapse.getOrCreateInstance(collapseEl);
            if(!!collapse) {
                collapse.show();
            }
        });

        const hideSidebarCollapse = (() => {
            let collapse = Collapse.getOrCreateInstance(collapseEl);
            if(!!collapse) {
                collapse.hide();
            }
        });

        const enableSidebarTooltip = (() => {
            const sidebarTooltipTriggerList = [].slice.call(sidebarEl.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));
            sidebarTooltipTriggerList.map(function (sidebarTooltipTriggerEl) {
                let tooltip = Tooltip.getOrCreateInstance(sidebarTooltipTriggerEl);
                if(!!tooltip) {
                    return tooltip.enable();
                }
            });
        });

        const disableSidebarTooltip = (() => {
            const sidebarTooltipTriggerList = [].slice.call(sidebarEl.querySelectorAll("[data-bs-toggle=\"tooltip\"]"));
            sidebarTooltipTriggerList.map(function (sidebarTooltipTriggerEl) {
                let tooltip = Tooltip.getInstance(sidebarTooltipTriggerEl);
                if(!!tooltip) {
                    tooltip.hide();
                    tooltip.disable();
                }
            });
        });

        '.(!isset($this->cookieData[$this->config['cookie.sidebar.name']]) ? '
        window.addEventListener("DOMContentLoaded", (event) => {
            if( window.innerWidth >= 992 ) {
                Cookies.set("'.$this->escape()->js($this->config['cookie.sidebar.name']).'", true, { path: "'.$this->uri($this->env.'.index').'", expires: parseInt( '.$this->escape()->js($this->config['cookie.sidebar.lifetime']).' ) });

                disableSidebarTooltip();
            }
        });
        ' : '
        window.addEventListener("DOMContentLoaded", (event) => {
            if( window.innerWidth < 992 ) {
                Cookies.remove("'.$this->escape()->js($this->config['cookie.sidebar.name']).'", { path: "'.$this->uri($this->env.'.index').'" });

                hideSidebarCollapse();
            }

            //https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Boolean
            //https://github.com/js-cookie/js-cookie/issues/235
            if( Cookies.get("'.$this->escape()->js($this->config['cookie.sidebar.name']).'", { path: "'.$this->uri($this->env.'.index').'" }) === "true" ) {
                disableSidebarTooltip();
            }
        });
        ').'

        window.addEventListener("resize", (event) => {
            if( collapseEl.classList.contains("show") ) {
                if( window.innerWidth >= 992 ) {
                    disableSidebarTooltip();
                    sidebarBackdropEl.classList.remove("show");
                } else {
                    sidebarBackdropEl.classList.add("show");
                }
            }
        });

        collapseEl.addEventListener("show.bs.collapse", event => {
            document.body.classList.add("collapsing-to-show");

            disableSidebarTooltip();

            if( window.innerWidth < 992 ) {
                sidebarBackdropEl.classList.add("show");
            }
        });
        collapseEl.addEventListener("shown.bs.collapse", event => {
            document.body.classList.remove("collapsing-to-show");

            if( window.innerWidth >= 992 ) {
                Cookies.set( "'.$this->escape()->js($this->config['cookie.sidebar.name']).'", true, { path: "'.$this->uri($this->env.'.index').'", expires: parseInt( '.$this->escape()->js($this->config['cookie.sidebar.lifetime']).' ) });
            }
        });
        collapseEl.addEventListener("hide.bs.collapse", event => {
            if( window.innerWidth < 992 ) {
                sidebarBackdropEl.classList.remove("show");
            }
        });
        collapseEl.addEventListener("hidden.bs.collapse", event => {
            if( window.innerWidth >= 992 ) {
                Cookies.set( "'.$this->escape()->js($this->config['cookie.sidebar.name']).'", false, { path: "'.$this->uri($this->env.'.index').'", expires: parseInt( '.$this->escape()->js($this->config['cookie.sidebar.lifetime']).' ) });
            }

            //https://stackoverflow.com/a/6976583/3929620
            if (!!document.activeElement) {
                document.activeElement.blur();
            }

            enableSidebarTooltip();
        });
    }
})();';
$this->scriptsFoot()->endInternal();
