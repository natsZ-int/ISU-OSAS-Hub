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

    /* ── Row action submission feedback ───────────────────────── */
    document.querySelectorAll('form[data-action-form]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            // A cancelled confirm() keeps the form on the page; leave the button usable.
            if (event.defaultPrevented) return;

            var button = form.querySelector('button[type="submit"][data-loading-label]');
            if (!button) return;

            var label = button.querySelector('span');
            if (label) {
                label.textContent = button.dataset.loadingLabel;
            } else {
                button.textContent = button.dataset.loadingLabel;
            }

            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
            button.classList.add('is-processing');
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

/* ── Google-style live search ─────────────────────────────────────────────
   Progressive enhancement for every search input in the system. Inputs can
   either be bare fields annotated with data-live-search attributes or use the
   shared <x-live-search> Blade component. All suggestions are produced by
   role-scoped Laravel endpoints, so the browser never receives records the
   signed-in user is not allowed to see. */
(function () {
    'use strict';

    var instanceCounter = 0;

    function debounce(fn, wait) {
        var timer = null;

        function debounced() {
            var args = arguments;
            var context = this;

            if (timer) window.clearTimeout(timer);
            timer = window.setTimeout(function () {
                fn.apply(context, args);
            }, wait);
        }

        debounced.cancel = function () {
            if (timer) window.clearTimeout(timer);
            timer = null;
        };

        return debounced;
    }

    function escapeRegExp(value) {
        return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    function highlight(target, text, term) {
        target.textContent = '';
        text = text == null ? '' : String(text);

        var tokens = String(term || '').split(/\s+/).filter(Boolean);
        if (!tokens.length) {
            target.textContent = text;
            return;
        }

        tokens.sort(function (a, b) { return b.length - a.length; });

        var pattern;
        try {
            pattern = new RegExp('(' + tokens.map(escapeRegExp).join('|') + ')', 'gi');
        } catch (e) {
            target.textContent = text;
            return;
        }

        var lowerTokens = tokens.map(function (token) { return token.toLowerCase(); });

        text.split(pattern).forEach(function (part) {
            if (part === '') return;

            if (lowerTokens.indexOf(part.toLowerCase()) !== -1) {
                var mark = document.createElement('mark');
                mark.textContent = part;
                target.appendChild(mark);
            } else {
                target.appendChild(document.createTextNode(part));
            }
        });
    }

    function createShell(input) {
        var shell = document.createElement('div');
        shell.className = 'live-search';

        var control = document.createElement('div');
        control.className = 'live-search-control';

        input.parentNode.insertBefore(shell, input);
        shell.appendChild(control);
        control.appendChild(input);

        return shell;
    }

    function ensureMarkup(shell, input) {
        var control = shell.querySelector('.live-search-control');
        if (!control) {
            control = document.createElement('div');
            control.className = 'live-search-control';
            shell.insertBefore(control, input);
            control.appendChild(input);
        }

        var spinner = shell.querySelector('.live-search-spinner');
        if (!spinner) {
            spinner = document.createElement('span');
            spinner.className = 'live-search-spinner';
            spinner.setAttribute('aria-hidden', 'true');
            control.appendChild(spinner);
        }

        var results = shell.querySelector('.live-search-results');
        if (!results) {
            results = document.createElement('div');
            results.className = 'live-search-results';
            results.setAttribute('role', 'listbox');
            results.hidden = true;
            shell.appendChild(results);
        }

        var sr = shell.querySelector('.live-search-sr');
        if (!sr) {
            sr = document.createElement('div');
            sr.className = 'live-search-sr';
            sr.setAttribute('aria-live', 'polite');
            shell.appendChild(sr);
        }

        return { control: control, spinner: spinner, results: results, sr: sr };
    }

    function initLiveSearch(node) {
        if (node.dataset.liveSearchReady === '1') return;

        var input;
        var shell;

        if (node.tagName === 'INPUT' || node.tagName === 'TEXTAREA') {
            input = node;
            shell = node.closest('.live-search') || createShell(input);
        } else {
            shell = node;
            input = shell.querySelector('input, textarea');
        }

        if (!input) return;

        var endpoint = node.getAttribute('data-live-search-endpoint')
            || shell.getAttribute('data-live-search-endpoint');

        if (!endpoint) return;

        node.dataset.liveSearchReady = '1';
        shell.dataset.liveSearchReady = '1';

        var parts = ensureMarkup(shell, input);
        var results = parts.results;
        var sr = parts.sr;

        var minChars = parseInt(node.getAttribute('data-live-search-min'), 10) || 1;
        var wait = parseInt(node.getAttribute('data-live-search-debounce'), 10) || 250;
        var instanceId = 'live-search-' + (++instanceCounter);

        var activeIndex = -1;
        var controller = null;
        var lastTerm = '';
        var requestId = 0;

        input.setAttribute('autocomplete', 'off');
        input.setAttribute('role', 'combobox');
        input.setAttribute('aria-autocomplete', 'list');
        input.setAttribute('aria-expanded', 'false');
        input.setAttribute('aria-controls', instanceId + '-results');
        results.id = instanceId + '-results';

        function setLoading(loading) {
            shell.classList.toggle('is-loading', loading);
            input.setAttribute('aria-busy', loading ? 'true' : 'false');
        }

        function openResults() {
            results.hidden = false;
            input.setAttribute('aria-expanded', 'true');
        }

        function closeResults() {
            results.hidden = true;
            input.setAttribute('aria-expanded', 'false');
            input.removeAttribute('aria-activedescendant');
            activeIndex = -1;
        }

        function clearResults() {
            results.textContent = '';
            activeIndex = -1;
        }

        function setActive(index) {
            var options = results.querySelectorAll('.live-search-option');

            activeIndex = index;

            options.forEach(function (option, optionIndex) {
                var active = optionIndex === index;
                option.classList.toggle('is-active', active);
                option.setAttribute('aria-selected', active ? 'true' : 'false');
            });

            if (options[index]) {
                input.setAttribute('aria-activedescendant', options[index].id);
            } else {
                input.removeAttribute('aria-activedescendant');
            }
        }

        function appendStatus(message) {
            var status = document.createElement('div');
            status.className = 'live-search-status';
            status.textContent = message;
            results.appendChild(status);
        }

        function render(data, term) {
            clearResults();
            results.textContent = '';

            var items = Array.isArray(data.results) ? data.results : [];

            if (!items.length) {
                appendStatus('No results found for "' + term + '".');
                sr.textContent = 'No results found.';
                openResults();
                return;
            }

            items.forEach(function (item, index) {
                var option = document.createElement('a');
                option.className = 'live-search-option';
                option.href = item.url || '#';
                option.id = instanceId + '-option-' + index;
                option.setAttribute('role', 'option');
                option.setAttribute('aria-selected', 'false');

                var main = document.createElement('span');
                main.className = 'live-search-option-main';

                var title = document.createElement('span');
                title.className = 'live-search-option-title';
                highlight(title, item.title, term);
                main.appendChild(title);

                if (item.subtitle) {
                    var subtitle = document.createElement('span');
                    subtitle.className = 'live-search-option-subtitle';
                    highlight(subtitle, item.subtitle, term);
                    main.appendChild(subtitle);
                }

                if (item.meta) {
                    var meta = document.createElement('span');
                    meta.className = 'live-search-option-meta';
                    meta.textContent = item.meta;
                    main.appendChild(meta);
                }

                option.appendChild(main);

                if (item.badge) {
                    var badge = document.createElement('span');
                    badge.className = 'live-search-option-badge is-' + (item.badge_tone || 'neutral');
                    badge.textContent = item.badge;
                    option.appendChild(badge);
                }

                option.addEventListener('mouseenter', function () { setActive(index); });

                results.appendChild(option);
            });

            var hint = document.createElement('div');
            hint.className = 'live-search-hint';
            hint.textContent = '↑ ↓ to navigate · Enter to open · Esc to close';
            results.appendChild(hint);

            sr.textContent = items.length + (items.length === 1 ? ' result available.' : ' results available.');
            openResults();
        }

        function renderError(term) {
            results.textContent = '';
            appendStatus('Search is temporarily unavailable. Press Enter to search "' + term + '".');
            sr.textContent = 'Search unavailable.';
            openResults();
        }

        function runSearch(term) {
            if (controller) controller.abort();
            controller = new AbortController();
            lastTerm = term;
            var currentRequest = ++requestId;

            setLoading(true);

            var separator = endpoint.indexOf('?') === -1 ? '?' : '&';
            var url = endpoint + separator + 'q=' + encodeURIComponent(term) + '&limit=8';

            window.fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('Search request failed');
                }

                return response.json();
            }).then(function (data) {
                if (currentRequest !== requestId) return;
                if (input.value.trim() !== term) return;
                render(data, term);
            }).catch(function (error) {
                if (error && error.name === 'AbortError') return;
                if (currentRequest !== requestId) return;
                if (input.value.trim() !== term) return;
                renderError(term);
            }).then(function () {
                if (currentRequest !== requestId) return;
                setLoading(false);
            });
        }

        var scheduleSearch = debounce(function (term) {
            runSearch(term);
        }, wait);

        input.addEventListener('input', function () {
            var term = input.value.trim();

            if (term.length < minChars) {
                scheduleSearch.cancel();
                if (controller) controller.abort();
                lastTerm = '';
                clearResults();
                closeResults();
                sr.textContent = '';
                setLoading(false);
                return;
            }

            scheduleSearch(term);
        });

        input.addEventListener('focus', function () {
            var term = input.value.trim();

            if (term.length >= minChars && term !== lastTerm && results.hidden) {
                scheduleSearch(term);
            }
        });

        input.addEventListener('keydown', function (event) {
            var options = results.querySelectorAll('.live-search-option');

            if (event.key === 'ArrowDown') {
                if (results.hidden || options.length === 0) return;
                event.preventDefault();
                setActive(activeIndex < options.length - 1 ? activeIndex + 1 : 0);
                return;
            }

            if (event.key === 'ArrowUp') {
                if (results.hidden || options.length === 0) return;
                event.preventDefault();
                setActive(activeIndex > 0 ? activeIndex - 1 : options.length - 1);
                return;
            }

            if (event.key === 'Enter') {
                if (!results.hidden && activeIndex >= 0 && options[activeIndex]) {
                    event.preventDefault();
                    window.location.href = options[activeIndex].href;
                }
                return;
            }

            if (event.key === 'Escape') {
                if (!results.hidden) {
                    event.preventDefault();
                    closeResults();
                    input.focus();
                }
            }
        });

        document.addEventListener('pointerdown', function (event) {
            if (!shell.contains(event.target)) {
                closeResults();
            }
        });
    }

    document.querySelectorAll('[data-live-search]').forEach(initLiveSearch);
})();
