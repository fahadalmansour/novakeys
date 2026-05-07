/*
 * NovaKeys chrome behaviour. Two pieces:
 *   1. Tick #ng-clock once per second in Asia/Riyadh time.
 *   2. Nudge #ng-queue every ~12s within a small range so the order-
 *      queue indicator looks alive without making real network calls.
 *
 * Both hooks are no-ops if their target nodes are absent on the page,
 * so this loads safely on /wp-admin/, FSE editor previews, etc.
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

    function nudgeQueue() {
        var el = document.getElementById('ng-queue');
        if (!el) { return; }
        var current = parseInt(el.textContent, 10);
        if (isNaN(current)) {
            if (window.console && console.warn) {
                console.warn('[NK] #ng-queue contains non-numeric content; resetting to 14.');
            }
            current = 14;
        }
        var floor = 8, ceiling = 22;
        var delta = Math.random() < 0.5 ? -1 : 1;
        var next = current + delta;
        if (next < floor) { next = floor + 1; }
        if (next > ceiling) { next = ceiling - 1; }
        el.textContent = next;
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    function init() {
        tickClock();
        setInterval(tickClock, 1000);
        nudgeQueue();
        setInterval(nudgeQueue, 12000);
    }
}());
