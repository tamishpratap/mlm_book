(function () {
    'use strict';

    var csrfToken = document.querySelector('meta[name="csrf-token"]');

    function refreshIcons() {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function showMessage(message, success) {
        var main = document.querySelector('main');

        if (!main || !message) {
            return;
        }

        var existing = main.querySelector('[data-friend-ajax-message]');

        if (existing) {
            existing.remove();
        }

        var alert = document.createElement('div');
        alert.className = 'member-alert ' + (success ? 'member-alert--success' : 'member-alert--error');
        alert.dataset.friendAjaxMessage = 'true';
        alert.setAttribute('role', success ? 'status' : 'alert');
        alert.setAttribute('aria-live', 'polite');
        alert.textContent = message;
        main.prepend(alert);

        window.setTimeout(function () {
            alert.remove();
        }, 5000);
    }

    function replaceMemberActions(memberId, html, form) {
        var safeMemberId = String(memberId).replace(/[^0-9]/g, '');

        if (!safeMemberId || !html) {
            return;
        }

        var actionAreas = Array.from(document.querySelectorAll(
            '[data-friend-actions][data-friend-member-id="' + safeMemberId + '"]'
        ));

        if (actionAreas.length > 0) {
            actionAreas.forEach(function (area) {
                area.outerHTML = html;
            });
        } else if (form) {
            var targetContainer = form.closest('.friend-actions') || form.closest('.friend-card__actions') || form;
            targetContainer.outerHTML = html;
        }

        refreshIcons();
    }

    function removeResolvedRequest(form) {
        if (form.dataset.friendResolution !== 'true') {
            return;
        }

        var incomingCard = form.closest('[data-incoming-request-card]');
        if (incomingCard) {
            var incomingList = incomingCard.closest('[data-incoming-request-list]');
            incomingCard.remove();

            if (incomingList) {
                var remainingIncoming = incomingList.querySelectorAll('[data-incoming-request-card]').length;
                var incomingCountEl = document.querySelector('[data-incoming-count]');
                if (incomingCountEl) {
                    incomingCountEl.textContent = remainingIncoming;
                }

                if (remainingIncoming === 0 && !incomingList.querySelector('[data-incoming-empty]')) {
                    var emptyIn = document.createElement('div');
                    emptyIn.className = 'friend-empty friend-empty--compact';
                    emptyIn.dataset.incomingEmpty = 'true';
                    emptyIn.style.gridColumn = '1 / -1';
                    emptyIn.innerHTML = '<p>No new connection requests</p>';
                    incomingList.appendChild(emptyIn);
                }
            }
            return;
        }

        var outgoingCard = form.closest('[data-outgoing-request-card]');
        if (outgoingCard) {
            var outgoingList = outgoingCard.closest('[data-outgoing-request-list]');
            outgoingCard.remove();

            if (outgoingList) {
                var remainingOutgoing = outgoingList.querySelectorAll('[data-outgoing-request-card]').length;
                var outgoingCountEl = document.querySelector('[data-outgoing-count]');
                if (outgoingCountEl) {
                    outgoingCountEl.textContent = remainingOutgoing;
                }

                if (remainingOutgoing === 0 && !outgoingList.querySelector('[data-outgoing-empty]')) {
                    var emptyOut = document.createElement('div');
                    emptyOut.className = 'friend-empty friend-empty--compact';
                    emptyOut.dataset.outgoingEmpty = 'true';
                    emptyOut.style.gridColumn = '1 / -1';
                    emptyOut.innerHTML = '<p>No pending sent connection requests</p>';
                    outgoingList.appendChild(emptyOut);
                }
            }
        }
    }

    document.addEventListener('submit', async function (event) {
        var form = event.target.closest('.friendship-action-form');

        if (!form) {
            return;
        }

        event.preventDefault();

        if (form.dataset.submitting === 'true') {
            return;
        }

        form.dataset.submitting = 'true';

        var buttons = Array.from(form.querySelectorAll('button'));
        var buttonLabels = buttons.map(function (button) {
            return button.innerHTML;
        });

        buttons.forEach(function (button) {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        });

        var submitButton = form.querySelector('button[type="submit"]');

        if (submitButton) {
            submitButton.textContent = form.dataset.loadingText || 'Working…';
        }

        try {
            var response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': csrfToken ? csrfToken.content : ''
                }
            });
            var payload = await response.json();

            if (!response.ok || !payload.success) {
                throw new Error(payload.message || 'The connection action could not be completed.');
            }

            replaceMemberActions(payload.member_id, payload.html, form);
            removeResolvedRequest(form);
            showMessage(payload.message, true);
        } catch (error) {
            buttons.forEach(function (button, index) {
                button.disabled = false;
                button.removeAttribute('aria-busy');
                button.innerHTML = buttonLabels[index];
            });
            form.dataset.submitting = 'false';
            refreshIcons();
            showMessage(error.message || 'The connection action could not be completed. Please try again.', false);
            if (typeof window.showBizPostToast === 'function') {
                window.showBizPostToast(error.message || 'The connection action could not be completed. Please try again.', true);
            }
        }
    });
})();
