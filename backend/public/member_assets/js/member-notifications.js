(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const menu = document.querySelector('[data-notification-menu]');

        if (! menu || menu.dataset.initialized === 'true') {
            return;
        }

        menu.dataset.initialized = 'true';
        const trigger = menu.querySelector('[data-notification-trigger]');
        const dropdown = menu.querySelector('[data-notification-dropdown]');
        const content = menu.querySelector('[data-notification-content]');
        const badge = menu.querySelector('[data-notification-badge]');
        let closeTimer;

        const refreshIcons = function () {
            if (window.lucide) {
                window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
            }
        };

        const updateBadge = function (count) {
            const unreadCount = Number.parseInt(count || 0, 10);

            if (! badge) return;

            badge.textContent = unreadCount > 99 ? '99+' : String(unreadCount);
            badge.hidden = unreadCount === 0;
        };

        const loadNotifications = async function () {
            if (document.visibilityState === 'hidden') return;

            try {
                const response = await fetch(menu.dataset.dropdownUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (! response.ok) return;

                const data = await response.json();
                updateBadge(data.unread_count);

                if (data.success && content) {
                    content.classList.remove('notification-dropdown__loading');
                    content.innerHTML = data.html;
                    refreshIcons();
                }
            } catch (error) {
                // Keep the last known notification state when polling fails.
            }
        };

        const openDropdown = function () {
            window.clearTimeout(closeTimer);
            dropdown.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');
            window.requestAnimationFrame(function () {
                dropdown.classList.add('is-open');
            });
            loadNotifications();
        };

        const closeDropdown = function (returnFocus) {
            dropdown.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');
            closeTimer = window.setTimeout(function () {
                if (trigger.getAttribute('aria-expanded') === 'false') {
                    dropdown.hidden = true;
                }
            }, 180);

            if (returnFocus) trigger.focus();
        };

        trigger?.addEventListener('click', function () {
            trigger.getAttribute('aria-expanded') === 'true'
                ? closeDropdown(false)
                : openDropdown();
        });

        document.addEventListener('click', function (event) {
            if (! menu.contains(event.target) && trigger?.getAttribute('aria-expanded') === 'true') {
                closeDropdown(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && trigger?.getAttribute('aria-expanded') === 'true') {
                closeDropdown(true);
            }
        });

        document.addEventListener('submit', async function (event) {
            const readForm = event.target.closest('[data-notification-read-form]');
            const readAllForm = event.target.closest('[data-notification-read-all-form]');
            const form = readForm || readAllForm;

            if (! form || form.dataset.submitting === 'true') return;

            event.preventDefault();
            form.dataset.submitting = 'true';
            const button = form.querySelector('button[type="submit"]');
            if (button) button.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();

                if (! response.ok || ! data.success) return;

                updateBadge(data.unread_count);

                if (readForm && data.url) {
                    window.location.assign(data.url);
                    return;
                }

                document.querySelectorAll('.notification-item.is-unread').forEach(function (item) {
                    item.classList.remove('is-unread');
                    item.classList.add('is-read');
                    item.querySelector('.notification-item__unread')?.remove();
                });
                document.querySelectorAll('[data-notification-read-all-form] button').forEach(function (markAllButton) {
                    markAllButton.disabled = true;
                });
            } catch (error) {
                // The non-JavaScript form remains available if this request fails.
            } finally {
                form.dataset.submitting = 'false';
                if (button && ! readAllForm) button.disabled = false;
            }
        });

        loadNotifications();
        window.setInterval(function () {
            if (document.visibilityState === 'visible') {
                loadNotifications();
            }
        }, 30000);
        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState === 'visible') {
                loadNotifications();
            }
        });
    });
}());
