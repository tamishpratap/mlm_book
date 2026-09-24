document.addEventListener('DOMContentLoaded', function () {
    const passwordToggles = document.querySelectorAll('[data-password-toggle]');

    passwordToggles.forEach(function (toggle) {
        const targetId = toggle.getAttribute('data-password-target');
        const passwordInput = document.getElementById(targetId);

        if (! passwordInput) {
            return;
        }

        toggle.addEventListener('click', function () {
            const isVisible = passwordInput.type === 'text';

            passwordInput.type = isVisible ? 'password' : 'text';
            toggle.classList.toggle('is-visible', ! isVisible);
            toggle.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
            passwordInput.focus();
        });
    });
});

(function ($) {
    'use strict';

    if (! $) {
        return;
    }

    $(function () {
        const $userId = $('#memberUserId');

        if (! $userId.length) {
            return;
        }

        const $feedback = $('#memberUserIdFeedback');
        const $inputWrap = $('[data-member-user-id-input-wrap]');
        const $submit = $('[data-member-register-submit]');
        const checkUrl = $userId.data('check-url');
        const helperMessage = 'Use lowercase letters, numbers, and underscores only.';
        const invalidMessage = 'Use 4–30 lowercase letters, numbers, or underscores.';
        const validPattern = /^[a-z][a-z0-9_]{2,28}[a-z0-9]$/;
        let debounceTimer;
        let activeRequest;

        function normalizeVisibleValue(value) {
            return value
                .toLowerCase()
                .replace(/\s+/g, '')
                .replace(/^@/, '');
        }

        function setState(state, message) {
            $feedback
                .removeClass('is-checking is-available is-invalid is-reserved is-network-error')
                .addClass(state ? 'is-' + state : '')
                .text(message);

            $inputWrap
                .removeClass('member-auth-input-wrap--error is-checking is-available is-invalid is-reserved')
                .addClass(state ? 'is-' + state : '');

            const isAvailable = state === 'available';
            $userId.attr('aria-invalid', isAvailable || state === '' ? 'false' : 'true');
            $submit.prop('disabled', ! isAvailable).attr('aria-disabled', isAvailable ? 'false' : 'true');
        }

        function cancelPendingCheck() {
            window.clearTimeout(debounceTimer);

            if (activeRequest) {
                activeRequest.abort();
                activeRequest = undefined;
            }
        }

        function scheduleAvailabilityCheck() {
            cancelPendingCheck();

            const normalizedValue = normalizeVisibleValue($userId.val());
            $userId.val(normalizedValue);

            if (normalizedValue === '') {
                setState('', helperMessage);

                return;
            }

            if (normalizedValue.length < 4 || ! validPattern.test(normalizedValue)) {
                setState('invalid', invalidMessage);

                return;
            }

            setState('checking', 'Checking availability…');

            debounceTimer = window.setTimeout(function () {
                const requestValue = $userId.val();
                const request = $.ajax({
                    url: checkUrl,
                    method: 'GET',
                    dataType: 'json',
                    data: {
                        user_id: requestValue,
                    },
                });

                activeRequest = request;

                request.done(function (response) {
                    if (requestValue !== $userId.val()) {
                        return;
                    }

                    if (typeof response.normalized_user_id === 'string') {
                        $userId.val(response.normalized_user_id);
                    }

                    if (response.available === true) {
                        setState('available', 'User ID is available.');
                    } else if (response.message === 'This User ID is reserved.') {
                        setState('reserved', response.message);
                    } else {
                        setState('invalid', response.message || invalidMessage);
                    }
                });

                request.fail(function (_jqXHR, textStatus) {
                    if (textStatus === 'abort') {
                        return;
                    }

                    setState('network-error', 'We could not check availability. Please try again.');
                });

                request.always(function () {
                    if (activeRequest === request) {
                        activeRequest = undefined;
                    }
                });
            }, 400);
        }

        $submit.prop('disabled', true).attr('aria-disabled', 'true');
        $userId.on('input', scheduleAvailabilityCheck);

        if ($userId.val()) {
            scheduleAvailabilityCheck();
        } else {
            setState('', helperMessage);
        }
    });
}(window.jQuery));
