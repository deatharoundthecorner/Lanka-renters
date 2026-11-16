(function () {
    'use strict';

    document.documentElement.classList.add('js');

    var body = document.body;
    var sidebar = document.querySelector('[data-sidebar]');
    var openButton = document.querySelector('[data-sidebar-open]');
    var closeButton = document.querySelector('[data-sidebar-close]');
    var overlay = document.querySelector('[data-sidebar-overlay]');
    var profileMenu = document.querySelector('[data-profile-menu]');
    var profileTrigger = document.querySelector('[data-profile-trigger]');
    var profilePanel = document.querySelector('[data-profile-panel]');

    function setSidebar(open) {
        if (!sidebar || !openButton) {
            return;
        }

        body.classList.toggle('sidebar-is-open', open);
        openButton.setAttribute('aria-expanded', open ? 'true' : 'false');

        if (open && closeButton) {
            closeButton.focus();
        } else if (!open && document.activeElement === closeButton) {
            openButton.focus();
        }
    }

    function setProfileMenu(open) {
        if (!profileTrigger || !profilePanel) {
            return;
        }

        profileTrigger.setAttribute('aria-expanded', open ? 'true' : 'false');
        profilePanel.hidden = !open;
    }

    if (openButton) {
        openButton.addEventListener('click', function () {
            setSidebar(openButton.getAttribute('aria-expanded') !== 'true');
        });
    }

    if (closeButton) {
        closeButton.addEventListener('click', function () {
            setSidebar(false);
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            setSidebar(false);
        });
    }

    if (profileTrigger) {
        profileTrigger.addEventListener('click', function () {
            setProfileMenu(profileTrigger.getAttribute('aria-expanded') !== 'true');
        });
    }

    document.addEventListener('click', function (event) {
        if (profileMenu && !profileMenu.contains(event.target)) {
            setProfileMenu(false);
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') {
            return;
        }

        if (body.classList.contains('sidebar-is-open')) {
            setSidebar(false);
        }

        if (profileTrigger && profileTrigger.getAttribute('aria-expanded') === 'true') {
            setProfileMenu(false);
            profileTrigger.focus();
        }
    });

    window.addEventListener('resize', function () {
        if (window.innerWidth > 960 && body.classList.contains('sidebar-is-open')) {
            setSidebar(false);
        }
    });
}());
