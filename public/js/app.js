/**
 * ISU OSAS Hub — minimal progressive enhancements.
 * No frameworks. Authorization, validation and workflow stay on the server.
 */
(function () {
    'use strict';

    /* ── Mobile navigation drawer ─────────────────────────────── */
    var toggle = document.getElementById('nav-toggle');
    var sidebar = document.getElementById('app-sidebar');
    var overlay = document.getElementById('sidebar-overlay');

    function setNavOpen(open) {
        if (!sidebar || !toggle) return;
        sidebar.classList.toggle('is-open', open);
        if (overlay) {
            overlay.classList.toggle('is-visible', open);
            overlay.hidden = !open;
        }
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        toggle.setAttribute('aria-label', open ? 'Close navigation menu' : 'Open navigation menu');
        document.body.style.overflow = open && window.innerWidth <= 1024 ? 'hidden' : '';
    }

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            setNavOpen(!sidebar.classList.contains('is-open'));
        });

        if (overlay) {
            overlay.addEventListener('click', function () { setNavOpen(false); });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && sidebar.classList.contains('is-open')) {
                setNavOpen(false);
                toggle.focus();
            }
        });

        sidebar.addEventListener('click', function (e) {
            var link = e.target.closest('a');
            if (link && window.innerWidth <= 1024) setNavOpen(false);
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth > 1024) setNavOpen(false);
        });
    }

    /* ── Filter / form submission loading states ──────────────── */
    document.querySelectorAll('form.filter-bar, form.filter-form').forEach(function (form) {
        form.addEventListener('submit', function () {
            var button = form.querySelector('button[type="submit"][data-loading-label]');
            if (button) {
                button.dataset.originalLabel = button.textContent;
                button.textContent = button.dataset.loadingLabel;
                button.disabled = true;
                button.setAttribute('aria-busy', 'true');
            }
            form.classList.add('is-loading');
        });
    });

    /* ── Password show/hide toggles ───────────────────────────── */
    document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
        button.addEventListener('click', function () {
            var target = document.querySelector(button.getAttribute('data-toggle-password'));
            if (!target) return;

            var hidden = target.type !== 'text';
            target.type = hidden ? 'text' : 'password';
            button.textContent = hidden ? 'Hide' : 'Show';
            button.setAttribute('aria-pressed', hidden ? 'true' : 'false');
            button.setAttribute('aria-label', hidden ? 'Hide password' : 'Show password');
        });
    });

    /* ── OTP resend cooldown countdown ────────────────────────── */
    var cooldownEl = document.getElementById('resend-cooldown');
    var resendBtn = document.getElementById('resend-button');

    if (cooldownEl && resendBtn) {
        var remaining = parseInt(cooldownEl.getAttribute('data-seconds'), 10) || 0;

        var tick = function () {
            if (remaining <= 0) {
                cooldownEl.textContent = '';
                resendBtn.disabled = false;
                return;
            }
            cooldownEl.textContent = 'You can resend in ' + remaining + 's';
            resendBtn.disabled = true;
            remaining -= 1;
            setTimeout(tick, 1000);
        };

        if (remaining > 0) {
            resendBtn.disabled = true;
            tick();
        } else {
            resendBtn.disabled = false;
        }
    }
})();
