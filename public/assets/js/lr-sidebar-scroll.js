(function () {
    'use strict';

    var navigation = document.querySelector('.sidebar-nav');
    if (!navigation) return;

    var segment = window.location.pathname.split('/').filter(Boolean)[0] || 'customer';
    var key = 'lrSidebarScroll:' + (['customer', 'owner', 'driver', 'admin'].indexOf(segment) !== -1 ? segment : 'customer');
    var saved = Number(sessionStorage.getItem(key));

    if (Number.isFinite(saved) && saved > 0) navigation.scrollTop = saved;

    function savePosition() {
        sessionStorage.setItem(key, String(navigation.scrollTop));
    }

    navigation.addEventListener('scroll', savePosition, { passive: true });
    navigation.querySelectorAll('a[href]').forEach(function (link) {
        link.addEventListener('click', savePosition);
    });

    requestAnimationFrame(function () {
        var active = navigation.querySelector('.active, [aria-current="page"]');
        if (!active) return;
        var navBounds = navigation.getBoundingClientRect();
        var activeBounds = active.getBoundingClientRect();
        if (activeBounds.top < navBounds.top || activeBounds.bottom > navBounds.bottom) {
            active.scrollIntoView({ block: 'nearest' });
        }
    });
}());