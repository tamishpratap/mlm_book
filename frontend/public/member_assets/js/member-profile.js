(function () {
    'use strict';

    function initializeProfileMenu() {
        const menu = document.querySelector('[data-profile-menu]');

        if (! menu || menu.dataset.initialized === 'true') {
            return;
        }

        const trigger = menu.querySelector('[data-profile-trigger]');
        const dropdown = menu.querySelector('[data-profile-dropdown]');

        if (! trigger || ! dropdown) {
            return;
        }

        menu.dataset.initialized = 'true';
        let closeTimer;

        const menuItems = () => Array.from(dropdown.querySelectorAll('[role="menuitem"]'));

        const openMenu = function (focusFirstItem) {
            window.clearTimeout(closeTimer);
            dropdown.hidden = false;
            trigger.setAttribute('aria-expanded', 'true');

            window.requestAnimationFrame(function () {
                dropdown.classList.add('is-open');
            });

            if (focusFirstItem) {
                window.requestAnimationFrame(function () {
                    menuItems()[0]?.focus();
                });
            }
        };

        const closeMenu = function (returnFocus) {
            dropdown.classList.remove('is-open');
            trigger.setAttribute('aria-expanded', 'false');

            closeTimer = window.setTimeout(function () {
                if (trigger.getAttribute('aria-expanded') === 'false') {
                    dropdown.hidden = true;
                }
            }, 220);

            if (returnFocus) {
                trigger.focus();
            }
        };

        trigger.addEventListener('click', function () {
            if (trigger.getAttribute('aria-expanded') === 'true') {
                closeMenu(false);
            } else {
                openMenu(false);
            }
        });

        trigger.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                openMenu(true);
            }
        });

        dropdown.addEventListener('keydown', function (event) {
            const items = menuItems();
            const currentIndex = items.indexOf(document.activeElement);

            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                event.preventDefault();
                const direction = event.key === 'ArrowDown' ? 1 : -1;
                const nextIndex = (currentIndex + direction + items.length) % items.length;
                items[nextIndex]?.focus();
            }

            if (event.key === 'Home' || event.key === 'End') {
                event.preventDefault();
                items[event.key === 'Home' ? 0 : items.length - 1]?.focus();
            }
        });

        dropdown.querySelectorAll('a[role="menuitem"]').forEach(function (link) {
            link.addEventListener('click', function () {
                closeMenu(false);
            });
        });

        document.addEventListener('click', function (event) {
            if (! menu.contains(event.target) && trigger.getAttribute('aria-expanded') === 'true') {
                closeMenu(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && trigger.getAttribute('aria-expanded') === 'true') {
                closeMenu(true);
            }
        });
    }

    function initializeSidebar() {
        const menuToggle = document.querySelector('[data-menu-toggle]');
        const sidebar = document.querySelector('[data-sidebar]');
        const scrim = document.querySelector('[data-sidebar-scrim]');

        if (! menuToggle || ! sidebar || ! scrim || menuToggle.dataset.initialized === 'true') {
            return;
        }

        menuToggle.dataset.initialized = 'true';

        const closeSidebar = function () {
            sidebar.classList.remove('is-open');
            scrim.classList.remove('is-open');
            document.body.classList.remove('menu-open');
            menuToggle.setAttribute('aria-expanded', 'false');
        };

        const openSidebar = function () {
            sidebar.classList.add('is-open');
            scrim.classList.add('is-open');
            document.body.classList.add('menu-open');
            menuToggle.setAttribute('aria-expanded', 'true');
        };

        menuToggle.addEventListener('click', function () {
            if (sidebar.classList.contains('is-open')) {
                closeSidebar();
            } else {
                openSidebar();
            }
        });

        scrim.addEventListener('click', closeSidebar);

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
                closeSidebar();
            }
        });

        window.addEventListener('resize', function () {
            if (window.innerWidth >= 1024 && sidebar.classList.contains('is-open')) {
                closeSidebar();
            }
        });
    }

    function initializeImagePreviews() {
        document.querySelectorAll('[data-image-input]').forEach(function (input) {
            if (input.dataset.initialized === 'true') {
                return;
            }

            input.dataset.initialized = 'true';

            input.addEventListener('change', function () {
                const uploadType = input.dataset.uploadType;
                if (uploadType === 'cover' || uploadType === 'avatar') {
                    return;
                }

                const file = input.files?.[0];
                const preview = document.getElementById(input.dataset.previewTarget);
                const actions = input.closest('form')?.querySelector('[data-upload-actions]');

                if (! file || ! preview) {
                    return;
                }

                const previewUrl = URL.createObjectURL(file);

                if (preview.tagName === 'IMG') {
                    preview.src = previewUrl;
                } else {
                    preview.style.backgroundImage = 'url("' + previewUrl + '")';
                    preview.classList.add('has-preview');
                }

                actions?.removeAttribute('hidden');
            });
        });
    }

    function initializePasswordToggles() {
        document.querySelectorAll('[data-password-visibility]').forEach(function (button) {
            if (button.dataset.initialized === 'true') {
                return;
            }

            const input = document.getElementById(button.dataset.passwordVisibility);

            if (! input) {
                return;
            }

            button.dataset.initialized = 'true';
            button.addEventListener('click', function () {
                const showPassword = input.type === 'password';
                input.type = showPassword ? 'text' : 'password';
                button.classList.toggle('is-visible', showPassword);
                button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
                input.focus();
            });
        });
    }

    function initializeOtpInputs() {
        document.querySelectorAll('[data-otp-input]').forEach(function (input) {
            if (input.dataset.initialized === 'true') {
                return;
            }

            input.dataset.initialized = 'true';
            input.addEventListener('input', function () {
                input.value = input.value.replace(/\D/g, '').slice(0, 6);
            });
        });
    }

    function initializeOtpCooldowns() {
        document.querySelectorAll('[data-otp-send]').forEach(function (button) {
            let seconds = Number.parseInt(button.dataset.cooldown || '0', 10);
            const label = button.querySelector('[data-otp-send-label]');

            if (! label || seconds <= 0 || button.dataset.initialized === 'true') {
                return;
            }

            button.dataset.initialized = 'true';
            button.disabled = true;

            const updateLabel = function () {
                if (seconds <= 0) {
                    button.disabled = false;
                    label.textContent = 'Resend Code';
                    return false;
                }

                label.textContent = 'Resend in ' + seconds + 's';
                seconds -= 1;
                return true;
            };

            updateLabel();
            const timer = window.setInterval(function () {
                if (! updateLabel()) {
                    window.clearInterval(timer);
                }
            }, 1000);
        });
    }

    function initializeOtpSubmitButtons() {
        document.querySelectorAll('[data-otp-submit]').forEach(function (button) {
            const form = button.closest('form');

            if (! form || button.dataset.initialized === 'true') {
                return;
            }

            button.dataset.initialized = 'true';
            form.addEventListener('submit', function () {
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
            }, { once: true });
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initializeProfileMenu();
        initializeSidebar();
        initializeImagePreviews();
        initializePasswordToggles();
        initializeOtpInputs();
        initializeOtpCooldowns();
        initializeOtpSubmitButtons();

        if (window.lucide) {
            window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
        }
    });
}());
