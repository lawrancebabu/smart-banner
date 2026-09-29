(function () {
    'use strict';

    var data = window.SmartBanner || {};
    var markup = data.markup || {};
    var COOKIE = 'spb_dismissed';

    function getDismissed() {
        var match = document.cookie.split('; ').filter(function (c) {
            return c.indexOf(COOKIE + '=') === 0;
        })[0];

        if (!match) {
            return [];
        }

        try {
            var ids = JSON.parse(decodeURIComponent(match.split('=')[1]));
            return Array.isArray(ids) ? ids.map(Number) : [];
        } catch (e) {
            return [];
        }
    }

    function saveDismissed(ids) {
        document.cookie = COOKIE + '=' + encodeURIComponent(JSON.stringify(ids)) + ';path=/;max-age=86400;SameSite=Lax';
    }

    function wrap(pos, html) {
        var el = document.createElement('div');
        el.className = 'spb-pos-wrap';
        el.id = 'spb-pos-' + pos;
        el.setAttribute('data-pos', pos);
        el.innerHTML = html;

        // Remove banners dismissed after this page was cached.
        getDismissed().forEach(function (id) {
            var b = el.querySelector('#spb-banner-' + id);
            if (b) {
                b.parentNode.removeChild(b);
            }
        });

        return el.querySelector('.spb-banner') ? el : null;
    }

    function pad() {
        var top = document.getElementById('spb-pos-top');
        document.body.style.paddingTop = (top && top.classList.contains('spb-sticky')) ? top.offsetHeight + 'px' : '';
    }

    function insertTop() {
        var el = markup.top && wrap('top', markup.top);
        if (!el) {
            return;
        }
        if (data.sticky) {
            el.classList.add('spb-sticky');
        }
        document.body.insertBefore(el, document.body.firstChild);
    }

    function insertBelowHeader() {
        var el = markup['below-header'] && wrap('below-header', markup['below-header']);
        if (!el) {
            return;
        }
        var header = document.querySelector('header.site-header, header#masthead, header[role="banner"], header.wp-block-template-part, header.header, #header, header');
        if (header && header.parentNode) {
            header.parentNode.insertBefore(el, header.nextSibling);
        } else {
            document.body.insertBefore(el, document.body.children[1] || null);
        }
    }

    function insertFooter() {
        var el = markup.footer && wrap('footer', markup.footer);
        if (!el) {
            return;
        }
        var footer = document.querySelector('footer.site-footer, footer#colophon, footer[role="contentinfo"], footer.wp-block-template-part, footer.footer, #footer, footer');
        if (footer && footer.parentNode) {
            footer.parentNode.insertBefore(el, footer);
        } else {
            document.body.appendChild(el);
        }
    }

    function dismiss(id) {
        var el = document.getElementById('spb-banner-' + id);
        if (!el) {
            return;
        }

        var wrapEl = el.closest('.spb-pos-wrap');
        el.style.overflow = 'hidden';
        el.style.maxHeight = el.offsetHeight + 'px';
        el.style.transition = 'max-height .25s ease, opacity .22s ease';

        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                el.style.maxHeight = '0';
                el.style.opacity = '0';
            });
        });

        setTimeout(function () {
            var ids = getDismissed();
            el.parentNode.removeChild(el);
            if (ids.indexOf(id) === -1) {
                ids.push(id);
            }
            saveDismissed(ids);
            if (wrapEl && !wrapEl.querySelector('.spb-banner')) {
                wrapEl.parentNode.removeChild(wrapEl);
            }
            pad();
        }, 270);
    }

    function startCountdowns() {
        var els = document.querySelectorAll('.spb-countdown[data-target]');

        Array.prototype.forEach.call(els, function (el) {
            var target = parseInt(el.getAttribute('data-target'), 10);
            var units = {};

            if (isNaN(target)) {
                return;
            }

            ['d', 'h', 'm', 's'].forEach(function (u) {
                units[u] = el.querySelector('[data-unit="' + u + '"]');
            });

            function two(n) {
                return String(Math.max(0, n)).padStart(2, '0');
            }

            function tick() {
                var diff = Math.max(0, target - Date.now());
                if (units.d) { units.d.textContent = two(Math.floor(diff / 86400000)); }
                if (units.h) { units.h.textContent = two(Math.floor((diff % 86400000) / 3600000)); }
                if (units.m) { units.m.textContent = two(Math.floor((diff % 3600000) / 60000)); }
                if (units.s) { units.s.textContent = two(Math.floor((diff % 60000) / 1000)); }
                return diff;
            }

            var timer = setInterval(function () {
                if (tick() === 0) {
                    clearInterval(timer);
                }
            }, 1000);
            tick();
        });
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('[data-spb-dismiss]') : null;
        if (btn) {
            e.preventDefault();
            dismiss(parseInt(btn.getAttribute('data-spb-dismiss'), 10));
        }
    });

    insertTop();
    insertBelowHeader();
    insertFooter();
    pad();
    startCountdowns();
    window.addEventListener('resize', pad);
})();
