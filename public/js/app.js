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

    /* ── Live filtering (refresh results without a page reload) ─ */
    document.querySelectorAll('form[data-live-filter]').forEach(function (form) {
        var selector = form.getAttribute('data-live-filter-target');
        var target = selector ? document.querySelector(selector) : null;

        if (!target) return;
        if (typeof window.fetch !== 'function' || typeof window.AbortController !== 'function') return;

        var status = document.querySelector('[data-live-filter-status]');
        var countEl = document.querySelector('[data-live-filter-count]');
        var timer = null;
        var controller = null;
        var requestId = 0;

        function buildRequest() {
            var params = new URLSearchParams();

            new FormData(form).forEach(function (value, key) {
                if (String(value).trim() !== '') params.append(key, value);
            });

            var query = params.toString();

            return {
                query: query,
                url: form.action + (query !== '' ? '?' + query : '')
            };
        }

        function refresh() {
            if (typeof window.fetch !== 'function') return;

            if (controller) controller.abort();
            controller = new AbortController();

            var current = ++requestId;
            var request = buildRequest();

            target.classList.add('is-loading');
            target.setAttribute('aria-busy', 'true');

            window.fetch(request.url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                signal: controller.signal
            }).then(function (response) {
                if (!response.ok) throw new Error('Filter request failed');
                return response.json();
            }).then(function (data) {
                if (current !== requestId) return;

                target.innerHTML = data.html;

                if (countEl) {
                    countEl.textContent = data.total + (data.total === 1 ? ' record' : ' records');
                }

                if (status) {
                    status.textContent = data.total === 0
                        ? 'No records match the current filters.'
                        : 'Showing ' + data.first + ' to ' + data.last + ' of ' + data.total + ' records.';
                }

                if (window.history && window.history.replaceState) {
                    window.history.replaceState({}, '', request.url);
                }
            }).catch(function (error) {
                if (error.name === 'AbortError') return;
                form.classList.remove('is-loading');
            }).finally(function () {
                if (current !== requestId) return;

                form.classList.remove('is-loading');
                target.classList.remove('is-loading');
                target.removeAttribute('aria-busy');
            });
        }

        function debouncedRefresh() {
            window.clearTimeout(timer);
            timer = window.setTimeout(refresh, 300);
        }

        form.querySelectorAll('input, select').forEach(function (field) {
            if (field.type === 'hidden') return;
            field.addEventListener('input', debouncedRefresh);
            field.addEventListener('change', debouncedRefresh);
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            window.clearTimeout(timer);
            refresh();
        });
    });

    /* ── Live search (Google-style typeahead suggestions) ─────── */
    document.querySelectorAll('[data-smart-search]').forEach(function (root) {
        var form = root.closest('form');
        var input = root.querySelector('[data-smart-search-input]');
        var panel = root.querySelector('[data-smart-search-panel]');
        var status = root.querySelector('[data-smart-search-status]');
        var endpoint = root.getAttribute('data-smart-search-endpoint');
        var label = root.getAttribute('data-smart-search-label') || 'results';

        if (!input || !panel || !endpoint) return;
        if (typeof window.fetch !== 'function' || typeof window.AbortController !== 'function') return;

        var controller = null;
        var timer = null;
        var options = [];
        var activeIndex = -1;
        var lastQuery = '';
        var uid = 'smart-search-' + Math.random().toString(36).slice(2, 9);

        function setExpanded(open) {
            input.setAttribute('aria-expanded', open ? 'true' : 'false');
            panel.hidden = !open;
        }

        function announce(message) {
            if (status) status.textContent = message;
        }

        function close() {
            setExpanded(false);
            clearActive();
        }

        function clearActive() {
            options.forEach(function (option) {
                option.classList.remove('is-active');
                option.setAttribute('aria-selected', 'false');
            });
            options = [];
            activeIndex = -1;
            input.removeAttribute('aria-activedescendant');
        }

        function setActive(index) {
            if (!options.length) return;
            if (index < 0) index = options.length - 1;
            if (index >= options.length) index = 0;

            options.forEach(function (option, position) {
                var isActive = position === index;
                option.classList.toggle('is-active', isActive);
                option.setAttribute('aria-selected', isActive ? 'true' : 'false');
            });

            activeIndex = index;
            input.setAttribute('aria-activedescendant', options[index].id);
            options[index].scrollIntoView({ block: 'nearest' });
        }

        function initials(name) {
            return String(name || '').trim().split(/\s+/).slice(0, 2).map(function (part) {
                return part.charAt(0);
            }).join('').toUpperCase();
        }

        function escapeRegex(value) {
            return value.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        }

        /**
         * Highlight every occurrence of each search token, not just the first.
         */
        function highlight(text, term) {
            var fragment = document.createDocumentFragment();
            var safeText = text == null ? '' : String(text);
            var tokens = String(term || '').trim().split(/\s+/).filter(Boolean);

            if (!tokens.length) {
                fragment.appendChild(document.createTextNode(safeText));
                return fragment;
            }

            tokens.sort(function (a, b) { return b.length - a.length; });

            var regex = new RegExp('(' + tokens.map(escapeRegex).join('|') + ')', 'gi');
            var lastIndex = 0;
            var match;

            while ((match = regex.exec(safeText)) !== null) {
                if (match.index > lastIndex) {
                    fragment.appendChild(document.createTextNode(safeText.slice(lastIndex, match.index)));
                }

                var mark = document.createElement('mark');
                mark.textContent = match[0];
                fragment.appendChild(mark);
                lastIndex = regex.lastIndex;

                if (match[0].length === 0) regex.lastIndex += 1;
            }

            if (lastIndex < safeText.length) {
                fragment.appendChild(document.createTextNode(safeText.slice(lastIndex)));
            }

            return fragment;
        }

        function appendBadge(container, labelText, tone) {
            if (!labelText) return;

            var badge = document.createElement('span');
            var safeTone = String(tone || 'info').toLowerCase().replace(/[^a-z0-9_-]/g, '');
            badge.className = 'badge badge-' + safeTone;
            badge.textContent = labelText;
            container.appendChild(badge);
        }

        function createOption(result, term, index) {
            var option = document.createElement('button');
            var title = result.title || result.name || '';
            var subtitle = result.subtitle || result.email || '';
            var metaParts = [];

            if (result.meta) metaParts.push(result.meta);
            if (result.student_number && subtitle.indexOf(result.student_number) === -1) {
                metaParts.push(result.student_number);
            }
            if (result.type && (!result.meta || result.meta.indexOf(result.type) === -1)) {
                metaParts.push(result.type);
            }

            option.type = 'button';
            option.className = 'smart-search-option';
            option.id = uid + '-' + index;
            option.setAttribute('role', 'option');
            option.setAttribute('aria-selected', 'false');
            option.setAttribute('tabindex', '-1');
            option.dataset.search = result.search || title;
            option.dataset.url = result.url || '';
            option.title = title + (subtitle ? ' — ' + subtitle : '');

            var avatar = document.createElement('span');
            avatar.className = 'smart-search-avatar';
            avatar.setAttribute('aria-hidden', 'true');
            avatar.textContent = result.avatar || initials(title) || '?';

            var body = document.createElement('span');
            body.className = 'smart-search-body';

            var name = document.createElement('span');
            name.className = 'smart-search-name';
            name.appendChild(highlight(title, term));
            body.appendChild(name);

            var meta = document.createElement('span');
            meta.className = 'smart-search-meta';
            meta.appendChild(highlight(subtitle, term));

            if (metaParts.length) {
                meta.appendChild(document.createTextNode((subtitle ? ' · ' : '') + metaParts.join(' · ')));
            }
            body.appendChild(meta);

            var badges = document.createElement('span');
            badges.className = 'smart-search-badges';

            if (Array.isArray(result.badges) && result.badges.length) {
                result.badges.forEach(function (item) {
                    if (item && typeof item === 'object') {
                        appendBadge(badges, item.label, item.tone);
                    } else {
                        appendBadge(badges, item, 'info');
                    }
                });
            } else {
                appendBadge(badges, result.badge, result.badge_tone);
                if (!result.badge && result.role) {
                    appendBadge(badges, result.role, result.role);
                }
            }

            if (result.active === false) {
                appendBadge(badges, 'Inactive', 'inactive');
            }

            if (result.match_score !== null && result.match_score !== undefined) {
                appendBadge(badges, 'Match ' + Math.round(result.match_score) + '%', 'admin');
            }

            option.appendChild(avatar);
            option.appendChild(body);
            option.appendChild(badges);

            option.addEventListener('mousedown', function (event) {
                event.preventDefault();
            });

            option.addEventListener('click', function () {
                select(option);
            });

            option.addEventListener('mouseenter', function () {
                setActive(options.indexOf(option));
            });

            return option;
        }

        function select(option) {
            if (!option) return;

            var url = option.dataset.url;

            if (url) {
                close();
                window.location.assign(url);
                return;
            }

            if (!form) return;

            input.value = option.dataset.search || '';
            close();
            announce('Filtering ' + label + ' by ' + input.value);

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }

        function renderResults(results, term) {
            clearActive();
            panel.innerHTML = '';

            if (!results.length) {
                var empty = document.createElement('div');
                empty.className = 'smart-search-empty';
                empty.textContent = 'No ' + label + ' match “' + term + '”.';
                panel.appendChild(empty);
                setExpanded(true);
                announce('No ' + label + ' match ' + term);
                return;
            }

            var fragment = document.createDocumentFragment();

            results.forEach(function (result, index) {
                fragment.appendChild(createOption(result, term, index));
            });

            panel.appendChild(fragment);

            options = Array.prototype.slice.call(
                panel.querySelectorAll('.smart-search-option')
            );

            var footnote = document.createElement('div');
            footnote.className = 'smart-search-footnote';
            footnote.innerHTML = '<span>↑ ↓ to navigate</span><span>Enter to open · Esc to close</span>';
            panel.appendChild(footnote);

            setExpanded(true);
            announce(results.length + (results.length === 1 ? ' suggestion available' : ' suggestions available'));
        }

        function renderError() {
            clearActive();
            panel.innerHTML = '';

            var error = document.createElement('div');
            error.className = 'smart-search-empty smart-search-error';
            error.textContent = 'Search is unavailable right now. Please try again.';
            panel.appendChild(error);
            setExpanded(true);
            announce('Search is unavailable right now.');
        }

        function fetchSuggestions(term) {
            if (typeof window.fetch !== 'function') return;

            if (controller) controller.abort();
            controller = new AbortController();
            lastQuery = term;
            root.classList.add('is-loading');

            window.fetch(endpoint + '?q=' + encodeURIComponent(term), {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                signal: controller.signal
            }).then(function (response) {
                if (!response.ok) throw new Error('Search request failed');
                return response.json();
            }).then(function (data) {
                if (lastQuery !== term) return;
                if (document.activeElement !== input) return;
                renderResults(data.results || [], data.query || term);
            }).catch(function (error) {
                if (error.name === 'AbortError') return;
                if (lastQuery === term && document.activeElement === input) renderError();
            }).finally(function () {
                if (lastQuery === term) root.classList.remove('is-loading');
            });
        }

        input.addEventListener('input', function () {
            var term = input.value.trim();

            window.clearTimeout(timer);

            if (controller) {
                controller.abort();
                controller = null;
            }
            lastQuery = term;
            root.classList.remove('is-loading');

            if (term === '') {
                close();
                panel.innerHTML = '';
                announce('');
                return;
            }

            // One character is enough; suggestions update on every keystroke.
            timer = window.setTimeout(function () {
                fetchSuggestions(term);
            }, 180);
        });

        input.addEventListener('focus', function () {
            var term = input.value.trim();
            if (term !== '' && panel.hidden) fetchSuggestions(term);
        });

        input.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                if (panel.hidden) {
                    var term = input.value.trim();
                    if (term !== '') fetchSuggestions(term);
                    return;
                }
                setActive(activeIndex + 1);
            } else if (event.key === 'ArrowUp') {
                if (panel.hidden) return;
                event.preventDefault();
                setActive(activeIndex - 1);
            } else if (event.key === 'Enter') {
                if (!panel.hidden && activeIndex >= 0) {
                    event.preventDefault();
                    select(options[activeIndex]);
                }
            } else if (event.key === 'Escape') {
                if (!panel.hidden) {
                    event.preventDefault();
                    close();
                }
            } else if (event.key === 'Tab') {
                close();
            }
        });

        input.addEventListener('blur', function () {
            window.setTimeout(function () {
                if (!root.contains(document.activeElement)) close();
            }, 120);
        });

        // Close when clicking anywhere outside this search area.
        document.addEventListener('click', function (event) {
            if (!root.contains(event.target)) close();
        });

        if (form) {
            form.addEventListener('submit', function () {
                close();
            });
        }
    });
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
