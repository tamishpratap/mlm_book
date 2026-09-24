document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const wrap = button.closest('.member-auth-input-wrap');
            const input = wrap ? wrap.querySelector('input') : null;
            if (!input) return;

            const isVisible = input.type === 'text';
            input.type = isVisible ? 'password' : 'text';
            button.classList.toggle('is-visible', !isVisible);
            button.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            input.focus();
        });
    });
});
