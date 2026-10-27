/* NovelVerse — interaksi antarmuka (tanpa dependensi, CSP-friendly). */
(function () {
    'use strict';

    var root = document.documentElement;
    var THEME_KEY = 'novelverse_theme';
    var READER_KEY = 'nv:reader-settings';
    var PROGRESS_KEY = 'nv:progress';
    var READ_PREFIX = 'nv:read:';

    function qs(selector, scope) {
        return (scope || document).querySelector(selector);
    }

    function qsa(selector, scope) {
        return Array.prototype.slice.call((scope || document).querySelectorAll(selector));
    }

    function storageGet(key, fallback) {
        try {
            var raw = window.localStorage.getItem(key);
            return raw === null ? fallback : JSON.parse(raw);
        } catch (error) {
            return fallback;
        }
    }

    function storageSet(key, value) {
        try {
            window.localStorage.setItem(key, JSON.stringify(value));
        } catch (error) {
            /* Penyimpanan dapat diblokir oleh mode privat; abaikan dengan aman. */
        }
    }

    function clamp(value, min, max) {
        return Math.min(max, Math.max(min, value));
    }

    function formatNumber(value) {
        try {
            return Number(value).toLocaleString('id-ID');
        } catch (error) {
            return String(value);
        }
    }

    /* ------------------------------------------------------------------
       Tema situs (gelap/terang) — cookie agar server merender tanpa kilat.
       ------------------------------------------------------------------ */
    function applyTheme(theme, persist) {
        var next = theme === 'light' ? 'light' : 'dark';
        root.setAttribute('data-theme', next);

        var meta = qs('meta[name="theme-color"]');
        if (meta) {
            meta.setAttribute('content', next === 'light' ? '#f7f4ee' : '#0a0d1a');
        }

        qsa('[data-theme-toggle]').forEach(function (button) {
            button.setAttribute('aria-pressed', next === 'light' ? 'true' : 'false');
            button.setAttribute('aria-label', next === 'light' ? 'Aktifkan mode gelap' : 'Aktifkan mode terang');
            button.setAttribute('title', next === 'light' ? 'Aktifkan mode gelap' : 'Aktifkan mode terang');
        });

        if (persist) {
            try {
                document.cookie = THEME_KEY + '=' + next + '; path=/; max-age=31536000; samesite=lax';
            } catch (error) {
                /* Cookie dapat ditolak; tema tetap berlaku untuk halaman ini. */
            }
        }
    }

    function setupTheme() {
        applyTheme(root.getAttribute('data-theme') === 'light' ? 'light' : 'dark', false);
        qsa('[data-theme-toggle]').forEach(function (button) {
            button.addEventListener('click', function () {
                var next = root.getAttribute('data-theme') === 'light' ? 'dark' : 'light';
                applyTheme(next, true);
            });
        });
    }

    /* ------------------------------------------------------------------
       Menu seluler & dropdown <details>
       ------------------------------------------------------------------ */
    function setupNavigation() {
        var toggle = qs('[data-nav-toggle]');
        var menu = qs('#mobile-menu');

        function setMenu(open) {
            if (!toggle || !menu) {
                return;
            }
            menu.hidden = !open;
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            toggle.setAttribute('aria-label', open ? 'Tutup menu navigasi' : 'Buka menu navigasi');
            if (open) {
                var search = qs('input[type="search"]', menu);
                if (search) {
                    window.setTimeout(function () {
                        search.focus();
                    }, 30);
                }
            }
        }

        if (toggle && menu) {
            toggle.addEventListener('click', function () {
                setMenu(menu.hidden);
            });
            qsa('a', menu).forEach(function (link) {
                link.addEventListener('click', function () {
                    setMenu(false);
                });
            });
            document.addEventListener('click', function (event) {
                if (menu.hidden) {
                    return;
                }
                if (menu.contains(event.target) || toggle.contains(event.target)) {
                    return;
                }
                setMenu(false);
            });
            window.addEventListener('resize', function () {
                if (window.innerWidth > 1060 && !menu.hidden) {
                    setMenu(false);
                }
            });
        }

        var menus = qsa('details.menu, details.reader-settings, details.report-toggle');

        menus.forEach(function (details) {
            details.addEventListener('toggle', function () {
                if (!details.open) {
                    return;
                }
                menus.forEach(function (other) {
                    if (other !== details) {
                        other.removeAttribute('open');
                    }
                });
            });
        });

        document.addEventListener('click', function (event) {
            menus.forEach(function (details) {
                if (details.open && !details.contains(event.target)) {
                    details.removeAttribute('open');
                }
            });
        });

        document.addEventListener('keydown', function (event) {
            if (event.key !== 'Escape') {
                return;
            }
            menus.forEach(function (details) {
                details.removeAttribute('open');
            });
            if (menu && !menu.hidden) {
                setMenu(false);
                if (toggle) {
                    toggle.focus();
                }
            }
        });
    }

    /* ------------------------------------------------------------------
       Notifikasi & konfirmasi
       ------------------------------------------------------------------ */
    function dismissAlert(alert) {
        if (!alert || alert.dataset.dismissing === '1') {
            return;
        }
        alert.dataset.dismissing = '1';
        alert.style.transition = 'opacity .2s ease, transform .2s ease';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-6px)';
        window.setTimeout(function () {
            alert.remove();
        }, 220);
    }

    function setupAlerts() {
        qsa('[data-alert]').forEach(function (alert) {
            var close = qs('[data-dismiss]', alert);
            if (close) {
                close.addEventListener('click', function () {
                    dismissAlert(alert);
                });
            }
            if (alert.classList.contains('alert--success') || alert.classList.contains('alert--info')) {
                window.setTimeout(function () {
                    dismissAlert(alert);
                }, 9000);
            }
        });
    }

    function setupConfirmations() {
        document.addEventListener('submit', function (event) {
            var form = event.target;
            if (!form || form.tagName !== 'FORM') {
                return;
            }
            var message = form.getAttribute('data-confirm');
            if (!message) {
                return;
            }
            if (!window.confirm(message)) {
                event.preventDefault();
            }
        }, true);
    }

    /* ------------------------------------------------------------------
       Kata sandi & pratinjau gambar
       ------------------------------------------------------------------ */
    function setupPasswordToggles() {
        qsa('[data-password-toggle]').forEach(function (button) {
            var input = document.getElementById(button.getAttribute('aria-controls') || '');
            if (!input) {
                return;
            }
            var original = button.getAttribute('aria-label') || 'Tampilkan kata sandi';
            button.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                button.setAttribute('aria-label', show ? 'Sembunyikan kata sandi' : original);
                button.classList.toggle('is-active', show);
            });
        });
    }

    function setupImagePreviews() {
        qsa('[data-image-input]').forEach(function (input) {
            var selector = input.getAttribute('data-preview-target');
            var preview = selector ? qs(selector) : null;
            if (!preview) {
                return;
            }
            var previousUrl = null;
            input.addEventListener('change', function () {
                var file = input.files && input.files[0];
                if (!file || !/^image\//.test(file.type)) {
                    return;
                }
                if (previousUrl) {
                    URL.revokeObjectURL(previousUrl);
                }
                previousUrl = URL.createObjectURL(file);
                preview.src = previousUrl;
                preview.hidden = false;
            });
        });
    }

    /* ------------------------------------------------------------------
       Filter daftar bab (halaman novel & dashboard penulis)
       ------------------------------------------------------------------ */
    function setupChapterFilters() {
        qsa('[data-chapter-filter]').forEach(function (input) {
            var scope = input.closest('.panel, section, .dashboard-main') || document;
            var list = qs('[data-chapter-list]', scope) || scope;
            var items = qsa('[data-chapter-item]', list);
            var empty = qs('[data-chapter-empty]', scope);

            function update() {
                var query = input.value.trim().toLowerCase();
                var shown = 0;
                items.forEach(function (item) {
                    var haystack = (item.getAttribute('data-search') || item.textContent || '').toLowerCase();
                    var match = query === '' || haystack.indexOf(query) !== -1;
                    item.hidden = !match;
                    if (match) {
                        shown += 1;
                    }
                });
                if (empty) {
                    empty.hidden = query === '' || shown > 0;
                }
            }

            input.addEventListener('input', update);
            input.addEventListener('search', update);
            update();
        });
    }

    /* ------------------------------------------------------------------
       Penghitung kata pada editor bab
       ------------------------------------------------------------------ */
    function setupWordCount() {
        var textarea = qs('[data-chapter-content]');
        if (!textarea) {
            return;
        }
        var wordOutput = qs('[data-word-count]');
        var charOutput = qs('[data-char-count]');

        function update() {
            var value = textarea.value || '';
            var trimmed = value.trim();
            var words = trimmed === '' ? 0 : trimmed.split(/\s+/u).length;
            if (wordOutput) {
                wordOutput.textContent = formatNumber(words) + ' kata';
            }
            if (charOutput) {
                charOutput.textContent = formatNumber(value.length) + ' karakter';
            }
        }

        textarea.addEventListener('input', update);
        update();
    }

    /* ------------------------------------------------------------------
       Progres tamu (localStorage)
       ------------------------------------------------------------------ */
    function readGuestMap() {
        var map = storageGet(PROGRESS_KEY, {});
        return map && typeof map === 'object' ? map : {};
    }

    function guestProgress(slug) {
        var entry = readGuestMap()[slug];
        if (!entry || typeof entry !== 'object') {
            return null;
        }
        var chapter = parseInt(entry.chapter, 10);
        if (!isFinite(chapter) || chapter < 1) {
            return null;
        }
        return {
            chapter: chapter,
            percent: isFinite(parseFloat(entry.percent)) ? clamp(parseFloat(entry.percent), 0, 100) : 0,
            updated: entry.updated || null
        };
    }

    function writeGuestProgress(slug, chapter, percent) {
        var map = readGuestMap();
        map[slug] = {
            chapter: chapter,
            percent: clamp(percent, 0, 100),
            updated: new Date().toISOString()
        };
        storageSet(PROGRESS_KEY, map);
    }

    function guestReadList(slug) {
        var list = storageGet(READ_PREFIX + slug, []);
        return Array.isArray(list) ? list.map(function (value) {
            return parseInt(value, 10);
        }).filter(function (value) {
            return isFinite(value);
        }) : [];
    }

    function addGuestRead(slug, chapter) {
        var list = guestReadList(slug);
        if (list.indexOf(chapter) === -1) {
            list.push(chapter);
            storageSet(READ_PREFIX + slug, list.slice(-300));
        }
        return list;
    }

    function slugFromBase(base) {
        var match = /\/novel\/([^/]+)\/baca\/?$/.exec(base || '');
        return match ? decodeURIComponent(match[1]) : null;
    }

    function setLinkLabel(element, label) {
        var nodes = Array.prototype.slice.call(element.childNodes);
        for (var i = nodes.length - 1; i >= 0; i -= 1) {
            if (nodes[i].nodeType === 3 && nodes[i].nodeValue.trim() !== '') {
                nodes[i].nodeValue = ' ' + label;
                return;
            }
        }
        element.appendChild(document.createTextNode(' ' + label));
    }

    function setupGuestContinue() {
        qsa('[data-guest-continue]').forEach(function (link) {
            var base = link.getAttribute('data-chapter-url-base') || '';
            var slug = slugFromBase(base);
            if (!slug) {
                return;
            }
            var entry = guestProgress(slug);
            if (!entry) {
                return;
            }
            link.href = base + entry.chapter;
            setLinkLabel(link, link.getAttribute('data-guest-label') || 'Lanjutkan membaca');
            link.setAttribute('title', 'Lanjutkan bab ' + entry.chapter + (entry.percent > 0 ? ' (' + Math.round(entry.percent) + '%)' : ''));
        });

        qsa('[data-chapter-item]').forEach(function (item) {
            var link = item.querySelector('a[href*="/baca/"]');
            if (!link) {
                return;
            }
            var match = /\/novel\/([^/]+)\/baca\/(\d+)/.exec(link.getAttribute('href') || '');
            if (!match) {
                return;
            }
            var slug = decodeURIComponent(match[1]);
            var chapter = parseInt(match[2], 10);
            if (guestReadList(slug).indexOf(chapter) !== -1) {
                item.classList.add('is-read');
            }
        });
    }

    /* ------------------------------------------------------------------
       Pembaca: pengaturan, TOC, progres, navigasi papan ketik
       ------------------------------------------------------------------ */
    function setupReader() {
        var reader = qs('#reader');
        if (!reader) {
            return;
        }

        var content = qs('[data-reader-content]', reader);
        var progressBar = qs('[data-reader-progress-bar]', reader);
        var saveStatus = qs('[data-reader-save-status]', reader);
        var toc = qs('#reader-toc');
        var tocToggle = qs('[data-reader-toc]');
        var tocClose = qs('[data-reader-toc-close]');
        var tocFilter = qs('[data-toc-filter]');
        var slug = reader.getAttribute('data-novel-slug') || '';
        var chapterNumber = parseInt(reader.getAttribute('data-chapter-number') || '0', 10);
        var authenticated = reader.getAttribute('data-authenticated') === '1';
        var progressUrl = reader.getAttribute('data-progress-url') || '';
        var csrfMeta = qs('meta[name="csrf-token"]');
        var csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : '';
        var prevUrl = reader.getAttribute('data-prev-url') || '';
        var nextUrl = reader.getAttribute('data-next-url') || '';
        var saved = -1;
        var inFlight = false;
        var timer = null;
        var statusTimer = null;

        /* Tema & tipografi baca. */
        var defaults = { theme: 'auto', font: 19, width: 720, spacing: 1.9 };
        var stored = storageGet(READER_KEY, null);
        var settings = {
            theme: stored && ['auto', 'light', 'dark'].indexOf(stored.theme) !== -1 ? stored.theme : defaults.theme,
            font: stored && isFinite(parseFloat(stored.font)) ? clamp(parseFloat(stored.font), 16, 28) : defaults.font,
            width: stored && isFinite(parseFloat(stored.width)) ? clamp(parseFloat(stored.width), 560, 960) : defaults.width,
            spacing: stored && isFinite(parseFloat(stored.spacing)) ? clamp(parseFloat(stored.spacing), 1.5, 2.4) : defaults.spacing
        };

        var fontInput = qs('[data-reader-font]', reader);
        var widthInput = qs('[data-reader-width]', reader);
        var spacingInput = qs('[data-reader-spacing]', reader);
        var fontOutput = qs('[data-reader-font-value]', reader);
        var widthOutput = qs('[data-reader-width-value]', reader);
        var spacingOutput = qs('[data-reader-spacing-value]', reader);
        var themeButtons = qsa('[data-reader-theme]', reader);

        function renderSettings() {
            if (settings.theme === 'auto') {
                reader.removeAttribute('data-reader-theme');
            } else {
                reader.setAttribute('data-reader-theme', settings.theme);
            }
            reader.style.setProperty('--reader-font-size', settings.font + 'px');
            reader.style.setProperty('--reader-width', settings.width + 'px');
            reader.style.setProperty('--reader-line-height', String(settings.spacing));

            if (fontInput) {
                fontInput.value = String(settings.font);
            }
            if (widthInput) {
                widthInput.value = String(settings.width);
            }
            if (spacingInput) {
                spacingInput.value = String(settings.spacing);
            }
            if (fontOutput) {
                fontOutput.textContent = Math.round(settings.font) + ' px';
            }
            if (widthOutput) {
                widthOutput.textContent = Math.round(settings.width) + ' px';
            }
            if (spacingOutput) {
                spacingOutput.textContent = String(settings.spacing).replace('.', ',');
            }
            themeButtons.forEach(function (button) {
                var active = button.getAttribute('data-reader-theme') === settings.theme;
                button.classList.toggle('is-active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });
        }

        function persistSettings() {
            storageSet(READER_KEY, settings);
        }

        themeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                settings.theme = button.getAttribute('data-reader-theme') || 'auto';
                renderSettings();
                persistSettings();
            });
        });

        if (fontInput) {
            fontInput.addEventListener('input', function () {
                settings.font = clamp(parseFloat(fontInput.value) || defaults.font, 16, 28);
                renderSettings();
                persistSettings();
            });
        }
        if (widthInput) {
            widthInput.addEventListener('input', function () {
                settings.width = clamp(parseFloat(widthInput.value) || defaults.width, 560, 960);
                renderSettings();
                persistSettings();
            });
        }
        if (spacingInput) {
            spacingInput.addEventListener('input', function () {
                settings.spacing = clamp(parseFloat(spacingInput.value) || defaults.spacing, 1.5, 2.4);
                renderSettings();
                persistSettings();
            });
        }

        var resetButton = qs('[data-reader-reset]', reader);
        if (resetButton) {
            resetButton.addEventListener('click', function () {
                settings = {
                    theme: defaults.theme,
                    font: defaults.font,
                    width: defaults.width,
                    spacing: defaults.spacing
                };
                renderSettings();
                persistSettings();
            });
        }

        renderSettings();

        /* Tandai bab tamu sebagai sudah dibaca. */
        if (!authenticated && slug && chapterNumber > 0) {
            addGuestRead(slug, chapterNumber);
            var existing = guestProgress(slug);
            var percentage = existing && existing.chapter === chapterNumber ? existing.percent : 0;
            writeGuestProgress(slug, chapterNumber, percentage);
            qsa('[data-toc-chapter]', reader).forEach(function (link) {
                var number = parseInt(link.getAttribute('data-toc-chapter'), 10);
                if (guestReadList(slug).indexOf(number) !== -1) {
                    link.classList.add('is-read');
                }
            });
        }

        /* Daftar bab (TOC). */
        function setToc(open) {
            if (!toc) {
                return;
            }
            toc.hidden = !open;
            if (tocToggle) {
                tocToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            }
            if (open) {
                var current = qs('.reader-toc__link.is-current', toc);
                if (current) {
                    current.scrollIntoView({ block: 'center' });
                }
            }
        }

        if (tocToggle) {
            tocToggle.addEventListener('click', function () {
                setToc(Boolean(toc && toc.hidden));
            });
        }
        if (tocClose) {
            tocClose.addEventListener('click', function () {
                setToc(false);
                if (tocToggle) {
                    tocToggle.focus();
                }
            });
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && toc && !toc.hidden) {
                setToc(false);
                if (tocToggle) {
                    tocToggle.focus();
                }
            }
        });
        document.addEventListener('click', function (event) {
            if (!toc || toc.hidden || !tocToggle) {
                return;
            }
            if (toc.contains(event.target) || tocToggle.contains(event.target)) {
                return;
            }
            setToc(false);
        });

        if (tocFilter) {
            tocFilter.addEventListener('input', function () {
                var query = tocFilter.value.trim().toLowerCase();
                qsa('[data-toc-item]', reader).forEach(function (item) {
                    item.hidden = query !== '' && item.textContent.toLowerCase().indexOf(query) === -1;
                });
            });
        }

        /* Indikator progres gulir. */
        function scrollPercent() {
            var doc = document.documentElement;
            var max = doc.scrollHeight - window.innerHeight;
            if (max <= 0) {
                return 100;
            }
            return clamp((window.scrollY || doc.scrollTop || 0) / max * 100, 0, 100);
        }

        function paintProgress() {
            var percent = scrollPercent();
            if (progressBar) {
                progressBar.style.width = percent.toFixed(2) + '%';
            }
            return percent;
        }

        var frame = null;
        function onScroll() {
            if (frame !== null) {
                return;
            }
            frame = window.requestAnimationFrame(function () {
                frame = null;
                var percent = paintProgress();
                if (Math.abs(percent - saved) >= 2) {
                    scheduleSave();
                }
            });
        }
        window.addEventListener('scroll', onScroll, { passive: true });

        function setStatus(message, tone) {
            if (!saveStatus) {
                return;
            }
            saveStatus.textContent = message;
            saveStatus.dataset.tone = tone || '';
            if (statusTimer) {
                window.clearTimeout(statusTimer);
            }
            if (tone === 'success') {
                statusTimer = window.setTimeout(function () {
                    saveStatus.textContent = '';
                }, 5000);
            }
        }

        function scheduleSave() {
            if (timer) {
                window.clearTimeout(timer);
            }
            timer = window.setTimeout(function () {
                timer = null;
                saveProgress(false);
            }, 1600);
        }

        function saveProgress(keepalive) {
            var percent = Math.round(scrollPercent() * 100) / 100;
            if (percent === saved && !keepalive) {
                return;
            }

            if (!authenticated) {
                writeGuestProgress(slug, chapterNumber, percent);
                saved = percent;
                addGuestRead(slug, chapterNumber);
                setStatus('Progres tersimpan di perangkat ini · ' + Math.round(percent) + '%', 'success');
                return;
            }

            if (!progressUrl || !csrfToken) {
                return;
            }
            if (inFlight) {
                return;
            }

            saved = percent;
            inFlight = true;
            setStatus('Menyimpan progres…', 'pending');

            var body = new URLSearchParams();
            body.set('percent', percent.toFixed(2));
            body.set('_token', csrfToken);

            window.fetch(progressUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
                    'X-CSRF-Token': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                },
                body: body.toString(),
                credentials: 'same-origin',
                keepalive: Boolean(keepalive)
            }).then(function (response) {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                return response.json();
            }).then(function () {
                setStatus('Progres tersimpan · ' + Math.round(percent) + '%', 'success');
            }).catch(function () {
                saved = -1;
                setStatus('Progres belum tersimpan. Periksa koneksi lalu gulir kembali.', 'error');
            }).then(function () {
                inFlight = false;
            });
        }

        window.addEventListener('pagehide', function () {
            if (timer) {
                window.clearTimeout(timer);
                timer = null;
            }
            saveProgress(true);
        });
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'hidden') {
                saveProgress(true);
            }
        });

        paintProgress();

        /* Navigasi papan ketik; tidak aktif saat mengetik. */
        document.addEventListener('keydown', function (event) {
            if (event.defaultPrevented || event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
                return;
            }
            var target = event.target;
            if (target && (target.isContentEditable || /^(INPUT|TEXTAREA|SELECT|OPTION)$/.test(target.tagName))) {
                return;
            }
            if (event.key === 'ArrowLeft' && prevUrl) {
                window.location.assign(prevUrl);
            } else if (event.key === 'ArrowRight' && nextUrl) {
                window.location.assign(nextUrl);
            }
        });
    }

    function init() {
        setupTheme();
        setupNavigation();
        setupAlerts();
        setupConfirmations();
        setupPasswordToggles();
        setupImagePreviews();
        setupChapterFilters();
        setupWordCount();
        setupGuestContinue();
        setupReader();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
