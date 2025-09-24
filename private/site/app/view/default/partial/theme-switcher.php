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

// https://getbootstrap.com/docs/5.3/customize/color-modes/
// https://stackoverflow.com/a/57795495/3929620
// https://www.designcise.com/web/tutorial/how-to-fix-the-javascript-typeerror-matchmedia-addeventlistener-is-not-a-function
if (!empty($this->config['theme.switcher'])) {
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
        document.documentElement.setAttribute("data-theme", theme); // bulma

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
}
