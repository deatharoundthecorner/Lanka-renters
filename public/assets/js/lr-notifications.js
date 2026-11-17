(function () {
    'use strict';

    const POLLING_INTERVAL = 5000;

    function createTextElement(tagName, className, value) {
        const element = document.createElement(tagName);
        if (className) element.className = className;
        element.textContent = value || '';
        return element;
    }

    function initializeNotificationCenter(root) {
        const endpoint = root.dataset.endpoint;
        const csrfToken = root.dataset.csrf;
        const toggle = root.querySelector('[data-lr-notification-toggle]');
        const panel = root.querySelector('[data-lr-notification-panel]');
        const dot = root.querySelector('[data-lr-notification-dot]');
        const summary = root.querySelector('[data-lr-notification-summary]');
        const list = root.querySelector('[data-lr-notification-list]');
        const markAll = root.querySelector('[data-lr-notification-mark-all]');

        if (!endpoint || !toggle || !panel || !dot || !summary || !list || !markAll) return;

        function render(data) {
            if (!data || data.success !== true || !Array.isArray(data.notifications)) return;
            const unreadCount = Number(data.unread_count) || 0;
            dot.hidden = unreadCount === 0;
            markAll.hidden = unreadCount === 0;
            summary.textContent = unreadCount > 0 ? unreadCount + ' unread' : 'You are up to date';
            list.replaceChildren();

            if (data.notifications.length === 0) {
                const empty = createTextElement('div', 'lr-notification-empty', '');
                empty.dataset.lrNotificationEmpty = '';
                empty.append(
                    createTextElement('strong', '', 'No notifications'),
                    createTextElement('span', '', 'Updates for your account will appear here.')
                );
                list.append(empty);
                return;
            }

            data.notifications.forEach(function (notification) {
                const row = createTextElement('button', 'lr-notification-row' + (notification.is_read ? '' : ' is-unread'), '');
                row.type = 'button';
                row.dataset.lrNotificationId = String(notification.id);
                const content = createTextElement('span', 'lr-notification-row__content', '');
                content.append(
                    createTextElement('strong', '', notification.title),
                    createTextElement('span', '', notification.message),
                    createTextElement('time', '', notification.display_time)
                );
                row.append(content);
                list.append(row);
            });
        }

        async function refresh() {
            try {
                const response = await fetch(endpoint, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' },
                    cache: 'no-store'
                });
                if (!response.ok) return;
                render(await response.json());
            } catch (error) {
                // Keep the last known UI state and retry on the next polling interval.
            }
        }

        async function postAction(action, notificationId) {
            const body = new URLSearchParams({ action: action, csrf_token: csrfToken });
            if (notificationId) body.set('notification_id', notificationId);
            try {
                const response = await fetch(endpoint, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: body.toString()
                });
                if (!response.ok) return;
                render(await response.json());
            } catch (error) {
                // A transient failure is intentionally non-disruptive.
            }
        }

        toggle.addEventListener('click', function () {
            const isOpen = panel.hidden;
            panel.hidden = !isOpen;
            toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            if (isOpen) refresh();
        });

        list.addEventListener('click', function (event) {
            const row = event.target.closest('[data-lr-notification-id]');
            if (!row || !list.contains(row)) return;
            postAction('mark_read', row.dataset.lrNotificationId);
        });

        markAll.addEventListener('click', function () {
            postAction('mark_all');
        });

        document.addEventListener('click', function (event) {
            if (!panel.hidden && !root.contains(event.target)) {
                panel.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !panel.hidden) {
                panel.hidden = true;
                toggle.setAttribute('aria-expanded', 'false');
                toggle.focus();
            }
        });

        window.setInterval(function () {
            if (!document.hidden) refresh();
        }, POLLING_INTERVAL);
        document.addEventListener('visibilitychange', function () {
            if (!document.hidden) refresh();
        });
        refresh();
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-lr-notifications]').forEach(initializeNotificationCenter);
    });
})();
