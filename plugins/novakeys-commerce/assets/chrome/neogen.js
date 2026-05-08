/*
 * NovaKeys chrome behaviour.
 *   1. Tick #ng-clock once per second in Asia/Riyadh time.
 *   2. Manage the footer newsletter notice (auto-dismiss, close button).
 *   3. Disable newsletter submit button after first click to prevent
 *      double-submission.
 *
 * Each hook is a no-op if its target node is absent on the page, so
 * this loads safely on /wp-admin/, FSE editor previews, etc.
 *
 * Previously this file also nudged a fake #ng-queue indicator on a
 * 12-second interval. That has been removed — without a real queue
 * data source, displaying a randomised number labelled "orders in
 * queue" alongside a real Riyadh clock read as authoritative and was
 * consumer-deceptive under KSA Consumer Protection rules.
 */
(function () {
    'use strict';

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function tickClock() {
        var el = document.getElementById('ng-clock');
        if (!el) { return; }
        try {
            var now = new Date(new Date().toLocaleString('en-US', { timeZone: 'Asia/Riyadh' }));
            el.textContent = pad(now.getHours()) + ':' + pad(now.getMinutes()) + ':' + pad(now.getSeconds());
        } catch (e) {
            // Don't show local-machine time as if it were Riyadh time —
            // the bar is labelled "Riyadh" so a wrong number is worse
            // than no number.
            el.textContent = '--:--:--';
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function dismissNewsNotice() {
        var n = document.querySelector('[data-nk-news]');
        if (!n) { return; }

        function leave() {
            if (!n.parentNode) { return; }
            n.classList.add('is-leaving');
            setTimeout(function () {
                if (n.parentNode) { n.parentNode.removeChild(n); }
            }, 260);
        }

        var btn = n.querySelector('.ng-news-notice-close');
        if (btn) { btn.addEventListener('click', leave); }
        // Error variant carries longer Arabic copy; give screen-reader
        // users + aria-live announcers extra time before auto-dismiss.
        var hold = n.classList.contains('ng-news-notice--err') ? 12000 : 8000;
        setTimeout(leave, hold);
    }

    function disableNewsletterDoubleSubmit() {
        var f = document.querySelector('.ng-foot-newsletter-form');
        if (!f) { return; }
        f.addEventListener('submit', function () {
            var b = f.querySelector('button[type="submit"]');
            if (!b) { return; }
            b.disabled = true;
            b.classList.add('is-submitting');
        });
    }

    function init() {
        tickClock();
        setInterval(tickClock, 1000);
        dismissNewsNotice();
        disableNewsletterDoubleSubmit();
    }
}());
