(function () {
    'use strict';

    function getCsrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function refreshLucideIcons() {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    }

    function showToast(message, isSuccess) {
        if (!message) return;

        var existing = document.querySelector('[data-community-ajax-message]');
        if (existing) existing.remove();

        var alert = document.createElement('div');
        alert.className = 'member-alert ' + (isSuccess ? 'member-alert--success' : 'member-alert--error');
        alert.dataset.communityAjaxMessage = 'true';
        alert.setAttribute('role', isSuccess ? 'status' : 'alert');
        alert.setAttribute('aria-live', 'polite');
        alert.style.cssText = 'position: fixed; top: 24px; right: 24px; z-index: 100000; padding: 12px 20px; border-radius: 12px; font-size: 13.5px; font-weight: 600; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2); transition: all 0.3s ease;' +
            (isSuccess ? 'background: #10b981; color: #ffffff;' : 'background: #ef4444; color: #ffffff;');
        alert.textContent = message;
        document.body.appendChild(alert);

        setTimeout(function () {
            if (alert.parentNode) alert.remove();
        }, 5000);
    }

    function showModalAlert(containerEl, message, isSuccess) {
        if (!containerEl || !message) return;
        var existing = containerEl.querySelector('[data-community-modal-alert]');
        if (existing) existing.remove();

        var alert = document.createElement('div');
        alert.className = 'community-modal-alert';
        alert.dataset.communityModalAlert = 'true';
        alert.style.cssText = 'padding: 10px 14px; margin-bottom: 14px; border-radius: 10px; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 8px; box-sizing: border-box; transition: all 0.3s ease;' +
            (isSuccess
                ? 'background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;'
                : 'background: #fee2e2; color: #991b1b; border: 1px solid #fecaca;');

        var iconName = isSuccess ? 'check-circle' : 'alert-circle';
        alert.innerHTML = '<i data-lucide="' + iconName + '" style="width: 16px; height: 16px; flex-shrink: 0;"></i> <span>' + escapeHtml(message) + '</span>';

        var sectionHeader = containerEl.querySelector('[data-community-friends-count-label]');
        if (sectionHeader && sectionHeader.parentElement && sectionHeader.parentElement.parentElement) {
            var targetParent = sectionHeader.parentElement.parentElement;
            targetParent.insertBefore(alert, targetParent.firstChild);
        } else {
            containerEl.prepend(alert);
        }
        refreshLucideIcons();

        setTimeout(function () {
            if (alert.parentNode) alert.remove();
        }, 6000);
    }

    function updateMemberCounters(count) {
        if (typeof count === 'undefined') return;
        var counters = document.querySelectorAll('[data-community-member-counter], [data-community-member-tab-count]');
        counters.forEach(function (counter) {
            counter.textContent = count;
        });
    }

    function updatePendingCounters(count) {
        if (typeof count === 'undefined') return;
        var counters = document.querySelectorAll('[data-community-pending-counter]');
        counters.forEach(function (counter) {
            counter.textContent = count;
        });
    }

    // Handle Join Click
    document.addEventListener('click', async function (event) {
        var joinBtn = event.target.closest('[data-community-join-btn]');
        if (!joinBtn) return;

        event.preventDefault();
        var url = joinBtn.dataset.communityJoinBtn;
        if (!url || joinBtn.disabled) return;

        joinBtn.disabled = true;
        joinBtn.style.opacity = '0.6';

        try {
            var response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                var actionArea = joinBtn.closest('[data-community-action-area]');
                if (actionArea && data.html) {
                    actionArea.outerHTML = data.html;
                    refreshLucideIcons();
                }
                updateMemberCounters(data.member_count);
            } else {
                showToast(data.message || 'Unable to join community.', false);
                joinBtn.disabled = false;
                joinBtn.style.opacity = '1';
            }
        } catch (err) {
            showToast('Network error while attempting to join.', false);
            joinBtn.disabled = false;
            joinBtn.style.opacity = '1';
        }
    });

    // Handle Leave Click
    document.addEventListener('click', async function (event) {
        var leaveBtn = event.target.closest('[data-community-leave-btn]');
        if (!leaveBtn) return;

        event.preventDefault();
        var url = leaveBtn.dataset.communityLeaveBtn;
        if (!url || leaveBtn.disabled) return;

        if (!confirm('Are you sure you want to leave this community?')) {
            return;
        }

        leaveBtn.disabled = true;

        try {
            var response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                var actionArea = leaveBtn.closest('[data-community-action-area]');
                if (actionArea && data.html) {
                    actionArea.outerHTML = data.html;
                    refreshLucideIcons();
                }
                updateMemberCounters(data.member_count);
            } else {
                showToast(data.message || 'Unable to leave community.', false);
                leaveBtn.disabled = false;
            }
        } catch (err) {
            showToast('Network error while attempting to leave.', false);
            leaveBtn.disabled = false;
        }
    });

    // Handle Join Request Accept / Reject
    document.addEventListener('click', async function (event) {
        var handleBtn = event.target.closest('[data-community-request-handle]');
        if (!handleBtn) return;

        event.preventDefault();
        var url = handleBtn.dataset.communityRequestHandle;
        var action = handleBtn.dataset.action;
        if (!url || !action || handleBtn.disabled) return;

        var card = handleBtn.closest('[data-community-request-card]');
        handleBtn.disabled = true;

        try {
            var response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: action })
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                if (card) {
                    card.remove();
                }
                updateMemberCounters(data.member_count);
                updatePendingCounters(data.pending_count);

                var grid = document.querySelector('[data-community-requests-grid]');
                if (grid && !grid.querySelector('[data-community-request-card]')) {
                    grid.innerHTML = '<div class="fb-empty-state" style="grid-column: 1 / -1; width: 100%;"><div class="fb-empty-state__icon"><i data-lucide="user-check"></i></div><h3>No Pending Requests</h3><p>All join requests have been processed.</p></div>';
                    refreshLucideIcons();
                }
            } else {
                showToast(data.message || 'Failed to process request.', false);
                handleBtn.disabled = false;
            }
        } catch (err) {
            showToast('Network error while processing request.', false);
            handleBtn.disabled = false;
        }
    });

    // Handle Role Change (Admin Promotion / Demotion)
    document.addEventListener('click', async function (event) {
        var roleBtn = event.target.closest('[data-community-role-btn]');
        if (!roleBtn) return;

        event.preventDefault();
        var url = roleBtn.dataset.communityRoleBtn;
        var targetRole = roleBtn.dataset.role;
        if (!url || !targetRole || roleBtn.disabled) return;

        roleBtn.disabled = true;

        try {
            var response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ role: targetRole })
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                window.location.reload();
            } else {
                showToast(data.message || 'Failed to update member role.', false);
                roleBtn.disabled = false;
            }
        } catch (err) {
            showToast('Network error while updating role.', false);
            roleBtn.disabled = false;
        }
    });

    // Handle Member Removal
    document.addEventListener('click', async function (event) {
        var removeBtn = event.target.closest('[data-community-remove-btn]');
        if (!removeBtn) return;

        event.preventDefault();
        var url = removeBtn.dataset.communityRemoveBtn;
        if (!url || removeBtn.disabled) return;

        if (!confirm('Are you sure you want to remove this member from the community?')) {
            return;
        }

        var card = removeBtn.closest('[data-community-member-card]');
        removeBtn.disabled = true;

        try {
            var response = await fetch(url, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                if (card) {
                    card.remove();
                }
                updateMemberCounters(data.member_count);
            } else {
                showToast(data.message || 'Failed to remove member.', false);
                removeBtn.disabled = false;
            }
        } catch (err) {
            showToast('Network error while removing member.', false);
            removeBtn.disabled = false;
        }
    });

    // Handle Community Post Composer Submission via AJAX
    document.addEventListener('submit', async function (event) {
        var composer = event.target.closest('[data-community-post-composer]');
        if (!composer) return;

        event.preventDefault();
        var submitBtn = composer.querySelector('button[type="submit"]');
        if (submitBtn && submitBtn.disabled) return;

        if (submitBtn) submitBtn.disabled = true;

        var formData = new FormData(composer);

        try {
            var response = await fetch(composer.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: formData
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                composer.reset();

                var feedContainer = document.querySelector('[data-community-feed]');
                if (feedContainer && data.html) {
                    var emptyState = feedContainer.querySelector('.post-empty-state');
                    if (emptyState) emptyState.remove();

                    var tempDiv = document.createElement('div');
                    tempDiv.innerHTML = data.html;
                    var newCard = tempDiv.firstElementChild;
                    if (newCard) {
                        feedContainer.prepend(newCard);
                        refreshLucideIcons();
                    }
                }
            } else {
                showToast(data.message || 'Failed to publish post.', false);
            }
        } catch (err) {
            showToast('Network error while publishing post.', false);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });

    function openCommunityPopupModal(modal) {
        if (!modal) return;
        modal.removeAttribute('hidden');
        modal.style.removeProperty('display');
        modal.style.setProperty('display', 'flex', 'important');
        modal.classList.add('show');
        modal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('modal-open');
        if (window.bootstrap && window.bootstrap.Modal) {
            try {
                var bsModal = window.bootstrap.Modal.getOrCreateInstance(modal);
                bsModal.show();
            } catch (e) {}
        }
        refreshLucideIcons();

        if (modal.id === 'inviteShareModal' || modal.classList.contains('community-share-modal')) {
            var communityId = modal.dataset.communityShareModalId;
            if (communityId) {
                loadFriendsForCommunityModal(communityId);
            }
        }
    }

    function closeCommunityPopupModal(modal) {
        if (!modal) return;
        modal.classList.remove('show');
        modal.style.setProperty('display', 'none', 'important');
        modal.setAttribute('hidden', '');
        modal.setAttribute('aria-hidden', 'true');
        if (window.bootstrap && window.bootstrap.Modal) {
            try {
                var bsModal = window.bootstrap.Modal.getInstance(modal);
                if (bsModal) {
                    bsModal.hide();
                }
            } catch (e) {}
        }
        var activeModals = document.querySelectorAll('.modal.show, .community-share-modal.show');
        if (activeModals.length === 0) {
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            var backdrop = document.querySelector('.modal-backdrop');
            if (backdrop) backdrop.remove();
        }
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' || e.keyCode === 27) {
            var activeModals = document.querySelectorAll('.modal.show');
            activeModals.forEach(function (m) {
                closeCommunityPopupModal(m);
            });
        }
    });

    // Handle Bootstrap modal show events
    document.addEventListener('show.bs.modal', function (event) {
        var modal = event.target;
        if (modal && (modal.id === 'inviteShareModal' || modal.classList.contains('community-share-modal'))) {
            modal.removeAttribute('hidden');
            modal.style.setProperty('display', 'block', 'important');
            var communityId = modal.dataset.communityShareModalId;
            if (communityId) {
                loadFriendsForCommunityModal(communityId);
            }
        }
    });

    // Auto-trigger load on DOMContentLoaded if share modal is present
    document.addEventListener('DOMContentLoaded', function () {
        refreshLucideIcons();
        var shareModal = document.getElementById('inviteShareModal');
        if (shareModal) {
            var communityId = shareModal.dataset.communityShareModalId;
            if (communityId) {
                loadFriendsForCommunityModal(communityId);
            }
        }
    });

    // Phase 4: Share Modal Open / Close & Friends Loading Handlers
    document.addEventListener('click', function (event) {
        var openBtn = event.target.closest('[data-share-modal-open], [data-bs-target="#inviteShareModal"]');
        if (openBtn) {
            event.preventDefault();
            var modal = document.getElementById('inviteShareModal');
            if (modal) {
                openCommunityPopupModal(modal);
                var communityId = openBtn.dataset.shareModalOpen || modal.dataset.communityShareModalId;
                if (communityId) {
                    loadFriendsForCommunityModal(communityId);
                }
            }
            return;
        }

        var closeBtn = event.target.closest('[data-share-modal-close], [data-bs-dismiss="modal"]');
        if (closeBtn) {
            var modal = closeBtn.closest('.community-share-modal') || document.getElementById('inviteShareModal');
            if (modal) {
                closeCommunityPopupModal(modal);
            }
        }
    });

    async function loadFriendsForCommunityModal(communityId) {
        var container = document.getElementById('communityFriendsListContainer-' + communityId);
        if (!container) return;

        var url = container.dataset.getFriendsUrl;
        if (!url) return;

        var label = document.querySelector('[data-community-friends-count-label="' + communityId + '"]');
        var selectAllCb = document.querySelector('[data-community-select-all-friends="' + communityId + '"]');
        var sendBtn = document.getElementById('sendCommunityInvitesBtn-' + communityId);

        container.innerHTML = '<div style="padding: 24px; text-align: center; color: #64748b; font-size: 13px;"><i data-lucide="loader-2" class="spin" style="width: 18px; height: 18px; vertical-align: middle; margin-right: 6px;"></i> Loading your connections list...</div>';
        refreshLucideIcons();

        try {
            var response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                }
            });
            var data = await response.json();

            if (data.success && data.friends) {
                var friends = data.friends;
                if (label) {
                    label.textContent = friends.length + ' ' + (friends.length === 1 ? 'connection' : 'connections') + ' available';
                }

                if (friends.length === 0) {
                    container.innerHTML = '<div style="padding: 20px; text-align: center; color: #64748b; font-size: 13px;">No connections found to invite. Add connections to grow your network!</div>';
                    if (selectAllCb) selectAllCb.disabled = true;
                    if (sendBtn) sendBtn.disabled = true;
                    return;
                }

                var html = '';
                var hasAvailable = false;

                friends.forEach(function (f) {
                    var statusBadge = '';
                    var inputHtml = '';

                    if (f.is_member) {
                        statusBadge = '<span class="community-badge" style="background:#e2e8f0; color:#64748b; font-size:11px; padding: 4px 8px; border-radius: 6px; font-weight: 600;">Already Joined</span>';
                    } else if (f.is_invited) {
                        statusBadge = '<span class="community-badge" style="background:#fef3c7; color:#d97706; font-size:11px; padding: 4px 8px; border-radius: 6px; font-weight: 600;">Invitation Sent</span>';
                    } else {
                        hasAvailable = true;
                        inputHtml = '<input type="checkbox" name="invitee_ids[]" value="' + f.id + '" class="community-friend-checkbox-' + communityId + '" style="width: 16px; height: 16px; cursor: pointer; accent-color: #4f7df3;">';
                    }

                    html += '<div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 12px; background: #f8fafc; border-radius: 12px; border: 1px solid #f1f5f9; box-sizing: border-box;">' +
                                '<div style="display: flex; align-items: center; gap: 10px;">' +
                                    '<img src="' + f.avatar_url + '" style="width: 36px; height: 36px; border-radius: 50%; object-fit: cover;">' +
                                    '<div>' +
                                        '<strong style="font-size: 13px; color: #0f172a; display: block; line-height: 1.2;">' + escapeHtml(f.name) + '</strong>' +
                                        '<span style="font-size: 11px; color: #64748b;">' + escapeHtml(f.username) + '</span>' +
                                    '</div>' +
                                '</div>' +
                                '<div>' + (statusBadge || inputHtml) + '</div>' +
                            '</div>';
                });

                container.innerHTML = html;
                if (selectAllCb) {
                    selectAllCb.disabled = !hasAvailable;
                    selectAllCb.checked = false;
                }
                if (sendBtn) sendBtn.disabled = true;

                var friendCbs = container.querySelectorAll('.community-friend-checkbox-' + communityId);
                friendCbs.forEach(function (cb) {
                    cb.addEventListener('change', function () {
                        updateSendButtonState(communityId);
                    });
                });

            } else {
                container.innerHTML = '<div style="padding: 20px; text-align: center; color: #ef4444; font-size: 13px;">Unable to load connections.</div>';
            }
        } catch (err) {
            container.innerHTML = '<div style="padding: 20px; text-align: center; color: #ef4444; font-size: 13px;">Network error while loading connections.</div>';
        }
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function updateSendButtonState(communityId) {
        var container = document.getElementById('communityFriendsListContainer-' + communityId);
        var sendBtn = document.getElementById('sendCommunityInvitesBtn-' + communityId);
        if (!container || !sendBtn) return;

        var checkedCbs = container.querySelectorAll('.community-friend-checkbox-' + communityId + ':checked');
        sendBtn.disabled = checkedCbs.length === 0;
    }

    // Select All Friends Checkbox Handler
    document.addEventListener('change', function (event) {
        var selectAll = event.target.closest('[data-community-select-all-friends]');
        if (!selectAll) return;

        var communityId = selectAll.dataset.communitySelectAllFriends;
        var container = document.getElementById('communityFriendsListContainer-' + communityId);
        if (!container) return;

        var cbs = container.querySelectorAll('.community-friend-checkbox-' + communityId);
        cbs.forEach(function (cb) {
            cb.checked = selectAll.checked;
        });
        updateSendButtonState(communityId);
    });

    // Send Selected Invitations Handler
    document.addEventListener('click', async function (event) {
        var sendBtn = event.target.closest('[data-community-send-invites]');
        if (!sendBtn) return;

        event.preventDefault();
        var communityId = sendBtn.dataset.communityId;
        var url = sendBtn.dataset.communitySendInvites;
        if (!url || !communityId || sendBtn.disabled) return;

        var container = document.getElementById('communityFriendsListContainer-' + communityId);
        if (!container) return;

        var checkedCbs = container.querySelectorAll('.community-friend-checkbox-' + communityId + ':checked');
        var inviteeIds = Array.from(checkedCbs).map(function (cb) { return parseInt(cb.value); });

        if (inviteeIds.length === 0) return;

        sendBtn.disabled = true;
        sendBtn.style.opacity = '0.6';
        var originalBtnHtml = sendBtn.innerHTML;
        sendBtn.innerHTML = '<i data-lucide="loader-2" class="spin" style="width: 15px; height: 15px; vertical-align: middle; margin-right: 6px;"></i> Sending...';
        refreshLucideIcons();

        var modalCard = sendBtn.closest('.community-share-modal__card') || sendBtn.closest('.modal-content') || container.parentElement;

        try {
            var response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ invitee_ids: inviteeIds })
            });

            var data = await response.json();

            if (data.success) {
                var successMsg = data.message || 'Community invitation sent successfully!';
                showModalAlert(modalCard, successMsg, true);
                showToast(successMsg, true);

                // Reload friends list so badges update immediately to "Invitation Sent"
                await loadFriendsForCommunityModal(communityId);
            } else {
                var errorMsg = data.message || 'Failed to send invitations.';
                showModalAlert(modalCard, errorMsg, false);
                showToast(errorMsg, false);
                sendBtn.disabled = false;
                sendBtn.style.opacity = '1';
                sendBtn.innerHTML = originalBtnHtml;
                refreshLucideIcons();
            }
        } catch (err) {
            showModalAlert(modalCard, 'Network error while sending invitations.', false);
            showToast('Network error while sending invitations.', false);
            sendBtn.disabled = false;
            sendBtn.style.opacity = '1';
            sendBtn.innerHTML = originalBtnHtml;
            refreshLucideIcons();
        }
    });

    // Phase 4: Copy Invite Link Handler
    document.addEventListener('click', function (event) {
        var copyBtn = event.target.closest('[data-community-copy-btn]');
        if (!copyBtn) return;

        event.preventDefault();
        var communityId = copyBtn.dataset.communityCopyBtn;
        var input = document.querySelector('[data-community-share-url="' + communityId + '"]');
        if (!input) return;

        var textToCopy = input.value;
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(textToCopy).then(function () {
                showToast('Community link copied.', true);
            }).catch(function () {
                fallbackCopy(input);
            });
        } else {
            fallbackCopy(input);
        }
    });

    function fallbackCopy(input) {
        input.select();
        document.execCommand('copy');
        showToast('Community link copied.', true);
    }

    // Phase 4: Native Share API Handler
    document.addEventListener('click', function (event) {
        var shareBtn = event.target.closest('[data-community-native-share]');
        if (!shareBtn) return;

        event.preventDefault();
        var title = shareBtn.dataset.title || 'Community';
        var url = shareBtn.dataset.url;

        if (navigator.share) {
            navigator.share({
                title: 'Join ' + title + ' on MLM Book',
                text: 'You are invited to join ' + title + ' community on MLM Book!',
                url: url
            }).catch(function (err) {
                // User cancelled or share failed
            });
        } else {
            showToast('Native share not supported. Use the copy button above.', false);
        }
    });

    // Phase 4: Admin Regenerate Invite Link Handler
    document.addEventListener('click', async function (event) {
        var genBtn = event.target.closest('[data-community-invite-generate]');
        if (!genBtn) return;

        event.preventDefault();
        var url = genBtn.dataset.communityInviteGenerate;
        if (!url || genBtn.disabled) return;

        genBtn.disabled = true;

        try {
            var response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                var modal = genBtn.closest('.community-share-modal');
                if (modal) {
                    var input = modal.querySelector('input[data-community-share-url]');
                    if (input && data.invite_url) {
                        input.value = data.invite_url;
                    }
                }
            } else {
                showToast(data.message || 'Failed to generate new link.', false);
            }
        } catch (err) {
            showToast('Network error while generating link.', false);
        } finally {
            genBtn.disabled = false;
        }
    });

    // Phase 4: Admin Revoke Invite Link Handler
    document.addEventListener('click', async function (event) {
        var revokeBtn = event.target.closest('[data-community-invite-revoke]');
        if (!revokeBtn) return;

        event.preventDefault();
        var url = revokeBtn.dataset.communityInviteRevoke;
        if (!url || revokeBtn.disabled) return;

        if (!confirm('Are you sure you want to revoke the current invite link? Old invite links will no longer work.')) {
            return;
        }

        revokeBtn.disabled = true;

        try {
            var response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                var modal = revokeBtn.closest('.community-share-modal');
                if (modal) {
                    var input = modal.querySelector('input[data-community-share-url]');
                    if (input) {
                        input.value = 'Revoked (Generate new link)';
                    }
                }
            } else {
                showToast(data.message || 'Failed to revoke link.', false);
            }
        } catch (err) {
            showToast('Network error while revoking link.', false);
        } finally {
            revokeBtn.disabled = false;
        }
    });

    // Phase 5: Handle Report Resolution Action
    document.addEventListener('click', async function (event) {
        var reportBtn = event.target.closest('[data-community-report-handle]');
        if (!reportBtn) return;

        event.preventDefault();
        var url = reportBtn.dataset.communityReportHandle;
        var status = reportBtn.dataset.status;
        var deleteContent = reportBtn.dataset.deleteContent || 0;

        if (!url || !status || reportBtn.disabled) return;
        reportBtn.disabled = true;

        try {
            var response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    status: status,
                    delete_content: deleteContent
                })
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                var row = reportBtn.closest('tr');
                if (row) row.remove();
            } else {
                showToast(data.message || 'Failed to update report status.', false);
                reportBtn.disabled = false;
            }
        } catch (err) {
            showToast('Network error while processing report.', false);
            reportBtn.disabled = false;
        }
    });

    // Phase 5: Unban Member Handler
    document.addEventListener('click', async function (event) {
        var unbanBtn = event.target.closest('[data-community-unban-btn]');
        if (!unbanBtn) return;

        event.preventDefault();
        var url = unbanBtn.dataset.communityUnbanBtn;
        var memberId = unbanBtn.dataset.memberId;
        if (!url || !memberId || unbanBtn.disabled) return;

        unbanBtn.disabled = true;

        try {
            var response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ member_id: memberId })
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                var row = unbanBtn.closest('tr');
                if (row) row.remove();
            } else {
                showToast(data.message || 'Failed to unban member.', false);
                unbanBtn.disabled = false;
            }
        } catch (err) {
            showToast('Network error while unbanning member.', false);
            unbanBtn.disabled = false;
        }
    });

    // Phase 5: Unmute Member Handler
    document.addEventListener('click', async function (event) {
        var unmuteBtn = event.target.closest('[data-community-unmute-btn]');
        if (!unmuteBtn) return;

        event.preventDefault();
        var url = unmuteBtn.dataset.communityUnmuteBtn;
        var memberId = unmuteBtn.dataset.memberId;
        if (!url || !memberId || unmuteBtn.disabled) return;

        unmuteBtn.disabled = true;

        try {
            var response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ member_id: memberId })
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                var row = unmuteBtn.closest('tr');
                if (row) row.remove();
            } else {
                showToast(data.message || 'Failed to unmute member.', false);
                unmuteBtn.disabled = false;
            }
        } catch (err) {
            showToast('Network error while unmuting member.', false);
            unmuteBtn.disabled = false;
        }
    });

    // Phase 5: Governance Settings Form Submission
    document.addEventListener('submit', async function (event) {
        var form = event.target.closest('[data-community-settings-form]');
        if (!form) return;

        event.preventDefault();
        var submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn && submitBtn.disabled) return;

        if (submitBtn) submitBtn.disabled = true;
        var formData = new FormData(form);

        try {
            var response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: formData
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
            } else {
                showToast(data.message || 'Failed to update settings.', false);
            }
        } catch (err) {
            showToast('Network error while saving settings.', false);
        } finally {
            if (submitBtn) submitBtn.disabled = false;
        }
    });

    // Phase 6: Lightbox Open / Close Handler
    document.addEventListener('click', function (event) {
        var item = event.target.closest('[data-lightbox-media]');
        if (item) {
            event.preventDefault();
            var mediaUrl = item.dataset.lightboxMedia;
            var mediaType = item.dataset.lightboxType || 'image';
            var author = item.dataset.lightboxAuthor || 'Member';
            var date = item.dataset.lightboxDate || '';
            var body = item.dataset.lightboxBody || '';

            var modal = document.getElementById('communityLightboxModal');
            var container = document.getElementById('lightboxMediaContainer');
            var authorEl = document.getElementById('lightboxAuthor');
            var dateEl = document.getElementById('lightboxDate');
            var bodyEl = document.getElementById('lightboxBody');
            var downloadEl = document.getElementById('lightboxDownload');

            if (!modal || !container) return;

            if (mediaType === 'video') {
                container.innerHTML = '<video src="' + mediaUrl + '" controls autoplay style="max-width: 100%; max-height: 65vh;"></video>';
            } else {
                container.innerHTML = '<img src="' + mediaUrl + '" alt="Photo preview" style="max-width: 100%; max-height: 65vh; object-fit: contain;">';
            }

            if (authorEl) authorEl.textContent = author;
            if (dateEl) dateEl.textContent = date;
            if (bodyEl) bodyEl.textContent = body;
            if (downloadEl) downloadEl.href = mediaUrl;

            modal.removeAttribute('hidden');
            modal.setAttribute('aria-hidden', 'false');
            refreshLucideIcons();
            return;
        }

        var closeBtn = event.target.closest('[data-lightbox-close]');
        if (closeBtn) {
            event.preventDefault();
            var modal = closeBtn.closest('#communityLightboxModal');
            if (modal) {
                modal.setAttribute('hidden', '');
                modal.setAttribute('aria-hidden', 'true');
                var container = document.getElementById('lightboxMediaContainer');
                if (container) container.innerHTML = '';
            }
        }

        // Phase 7: Preferences Modal Open / Close
        var prefOpenBtn = event.target.closest('[data-preferences-modal-open], [data-bs-target="#notificationPreferencesModal"]');
        if (prefOpenBtn) {
            event.preventDefault();
            var prefModal = document.getElementById('notificationPreferencesModal');
            if (prefModal) {
                if (window.bootstrap && window.bootstrap.Modal) {
                    bootstrap.Modal.getOrCreateInstance(prefModal).show();
                } else {
                    openCommunityPopupModal(prefModal);
                }
            }
            return;
        }

        var prefCloseBtn = event.target.closest('[data-preferences-modal-close], [data-bs-dismiss="modal"]');
        if (prefCloseBtn) {
            var prefModal = prefCloseBtn.closest('.community-share-modal');
            if (prefModal) {
                closeCommunityPopupModal(prefModal);
            }
        }

        // Phase 7: Mark Single Notification as Read
        var markReadBtn = event.target.closest('[data-community-notification-read]');
        if (markReadBtn) {
            event.preventDefault();
            var url = markReadBtn.dataset.communityNotificationRead;
            var item = markReadBtn.closest('[data-community-notification-item]');

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success && item) {
                    item.style.background = 'transparent';
                    item.style.borderLeft = 'none';
                    markReadBtn.remove();
                    showToast('Notification marked as read.', true);
                }
            });
            return;
        }

        // Phase 7: Mark All Notifications as Read
        var markAllBtn = event.target.closest('[data-community-mark-all-read]');
        if (markAllBtn) {
            event.preventDefault();
            var url = markAllBtn.dataset.communityMarkAllRead;

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            }).then(function(res) { return res.json(); })
            .then(function(data) {
                if (data.success) {
                    showToast('All notifications marked as read.', true);
                    setTimeout(function() { window.location.reload(); }, 600);
                }
            });
            return;
        }
    });

    // Phase 7: Preferences Form AJAX
    document.addEventListener('submit', async function (event) {
        var form = event.target.closest('[data-community-preferences-form]');
        if (!form) return;

        event.preventDefault();
        var formData = new FormData(form);

        try {
            var response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                },
                body: formData
            });

            var data = await response.json();
            if (data.success) {
                showToast(data.message, true);
                var modal = form.closest('.community-share-modal');
                if (modal) {
                    closeCommunityPopupModal(modal);
                }
            } else {
                showToast(data.message || 'Failed to update preferences.', false);
            }
        } catch (err) {
            showToast('Network error while saving preferences.', false);
        }
    });

    // Phase 8: Live Instant Search Handler
    var searchTimer = null;
    document.addEventListener('input', function (event) {
        var input = event.target.closest('[data-community-live-search]');
        if (!input) return;

        var url = input.dataset.communityLiveSearch;
        var container = document.getElementById('communityLiveSearchResults');
        if (!container) return;

        clearTimeout(searchTimer);
        var query = input.value.trim();

        if (query.length < 2) {
            container.style.display = 'none';
            container.innerHTML = '';
            return;
        }

        searchTimer = setTimeout(function () {
            fetch(url + '?q=' + encodeURIComponent(query), {
                headers: { 'Accept': 'application/json' }
            })
            .then(function (res) { return res.json(); })
            .then(function (data) {
                if (!data.results || data.results.length === 0) {
                    container.innerHTML = '<div style="padding: 12px; font-size: 13px; color: var(--color-text-secondary); text-align: center;">No matching communities found</div>';
                    container.style.display = 'block';
                    return;
                }

                var html = '';
                data.results.forEach(function (item) {
                    var logoHtml = item.logo_url
                        ? '<img src="' + item.logo_url + '" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover;">'
                        : '<div style="width: 28px; height: 28px; border-radius: 50%; background: var(--color-primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">' + item.name.charAt(0) + '</div>';

                    html += '<a href="' + item.url + '" style="display: flex; align-items: center; gap: 10px; padding: 8px 10px; border-radius: var(--radius-sm); text-decoration: none; color: inherit; transition: background 0.2s;" onmouseover="this.style.background=\'var(--color-surface-alt)\'" onmouseout="this.style.background=\'transparent\'">';
                    html += logoHtml;
                    html += '<div style="flex: 1;"><strong style="font-size: 13px; color: var(--color-text-main); display: block;">' + item.name + '</strong><span style="font-size: 11px; color: var(--color-text-secondary);">' + item.category + ' · ' + item.member_count + ' members</span></div>';
                    html += '</a>';
                });

                container.innerHTML = html;
                container.style.display = 'block';
            });
        }, 250);
    });

    document.addEventListener('click', function (event) {
        var container = document.getElementById('communityLiveSearchResults');
        if (container && !event.target.closest('[data-community-live-search]') && !event.target.closest('#communityLiveSearchResults')) {
            container.style.display = 'none';
        }
    });

    // ==========================================================================
    // COMMUNITY PHOTO INTERACTION SYSTEM (REUSING PROFILE ARCHITECTURE)
    // ==========================================================================
    var activeTargetType = null;
    var pendingUploadFile = null;
    var currentZoom = 1;
    var currentFullscreenZoom = 1;

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-action-trigger]');
        if (trigger) {
            event.stopPropagation();
            var targetType = trigger.dataset.actionTrigger;
            var targetMenu = document.querySelector('[data-photo-menu="' + targetType + '"]');
            if (targetMenu) {
                var isHidden = targetMenu.hidden;
                document.querySelectorAll('[data-photo-menu]').forEach(function (m) { m.hidden = true; });
                targetMenu.hidden = !isHidden;
            }
            return;
        }

        if (!event.target.closest('[data-photo-menu]')) {
            document.querySelectorAll('[data-photo-menu]').forEach(function (m) { m.hidden = true; });
        }
    });

    document.addEventListener('click', function (event) {
        var actionBtn = event.target.closest('[data-photo-action]');
        if (!actionBtn) return;

        event.stopPropagation();
        document.querySelectorAll('[data-photo-menu]').forEach(function (m) { m.hidden = true; });

        var action = actionBtn.dataset.photoAction;
        var photoType = actionBtn.dataset.photoType;
        activeTargetType = photoType;

        if (action === 'view') {
            openFullscreenPhotoViewer(photoType);
        } else if (action === 'edit') {
            triggerPhotoFileInput(photoType);
        } else if (action === 'delete') {
            openPhotoDeleteModal(photoType);
        }
    });

    document.addEventListener('change', function (event) {
        var input = event.target.closest('input[data-image-input]');
        if (!input) return;

        var file = input.files && input.files[0];
        if (!file) return;

        var uploadType = input.dataset.uploadType;
        activeTargetType = uploadType;
        pendingUploadFile = file;

        window.openPhotoPreviewModal(file, uploadType);
    });

    window.closePhotoPreviewModal = function () {
        var modal = document.getElementById('profilePhotoModal');
        if (modal) {
            modal.hidden = true;
            modal.style.display = 'none';
        }
        pendingUploadFile = null;
        var coverInput = document.getElementById('cover_photo') || document.getElementById('community_cover_photo');
        if (coverInput) coverInput.value = '';
        var avatarInput = document.getElementById('profile_photo') || document.getElementById('community_logo');
        if (avatarInput) avatarInput.value = '';
        var modalImg = document.getElementById('modalPhotoPreviewImg');
        if (modalImg) modalImg.src = '';
        var errorBox = document.getElementById('photoModalError');
        if (errorBox) {
            errorBox.hidden = true;
            errorBox.style.display = 'none';
        }
        window.resetPhotoZoom();
    };

    window.openPhotoPreviewModal = function (file, photoType) {
        var modal = document.getElementById('profilePhotoModal');
        var previewImg = document.getElementById('modalPhotoPreviewImg');
        var titleText = document.getElementById('photoModalTitleText');
        var errorBox = document.getElementById('photoModalError');

        if (!modal || !previewImg) return;

        if (errorBox) {
            errorBox.hidden = true;
            errorBox.style.display = 'none';
        }

        if (titleText) {
            if (photoType === 'cover') {
                titleText.textContent = 'Preview Cover Photo';
            } else if (photoType === 'avatar') {
                titleText.textContent = 'Preview Profile Photo';
            } else {
                titleText.textContent = 'Preview Community Logo';
            }
        }

        var reader = new FileReader();
        reader.onload = function (e) {
            previewImg.src = e.target.result;
            window.resetPhotoZoom();
            modal.hidden = false;
            modal.style.display = 'flex';
            refreshLucideIcons();
        };
        reader.readAsDataURL(file);
    };

    window.triggerReplacePhotoInput = function () {
        var input = null;
        if (activeTargetType === 'cover') {
            input = document.getElementById('cover_photo') || document.getElementById('community_cover_photo');
        } else if (activeTargetType === 'avatar') {
            input = document.getElementById('profile_photo') || document.getElementById('community_logo');
        } else if (activeTargetType === 'logo') {
            input = document.getElementById('community_logo');
        }
        if (input) input.click();
    };

    window.setPhotoZoom = function (val) {
        currentZoom = parseFloat(val) || 1;
        var stageImg = document.getElementById('modalPhotoPreviewImg');
        if (stageImg) {
            stageImg.style.transform = 'scale(' + currentZoom + ')';
        }
    };

    window.adjustPhotoZoom = function (delta) {
        var slider = document.getElementById('photoZoomSlider');
        var newZoom = Math.min(Math.max(currentZoom + delta, 1), 2.5);
        if (slider) slider.value = newZoom;
        window.setPhotoZoom(newZoom);
    };

    window.resetPhotoZoom = function () {
        var slider = document.getElementById('photoZoomSlider');
        if (slider) slider.value = 1;
        window.setPhotoZoom(1);
    };

    window.submitPhotoUpload = async function () {
        if (!activeTargetType || !pendingUploadFile) {
            window.closePhotoPreviewModal();
            return;
        }

        var formSelector = null;
        if (activeTargetType === 'cover') {
            formSelector = '[data-profile-cover-form], [data-community-cover-form]';
        } else if (activeTargetType === 'avatar') {
            formSelector = '[data-profile-photo-form], [data-community-logo-form]';
        } else {
            formSelector = '[data-community-logo-form]';
        }

        var form = document.querySelector(formSelector);
        if (!form) return;

        var saveBtn = document.getElementById('savePhotoUploadBtn');
        var saveBtnText = document.getElementById('savePhotoUploadBtnText');
        if (saveBtn) saveBtn.disabled = true;
        if (saveBtnText) saveBtnText.textContent = 'Saving...';

        var formData = new FormData();
        formData.append('_token', getCsrfToken());
        if (activeTargetType === 'cover') {
            formData.append('cover_photo', pendingUploadFile);
        } else if (activeTargetType === 'avatar') {
            if (form.hasAttribute('data-profile-photo-form')) {
                formData.append('profile_photo', pendingUploadFile);
            } else {
                formData.append('logo', pendingUploadFile);
            }
        } else {
            formData.append('logo', pendingUploadFile);
        }

        try {
            var response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken()
                },
                body: formData
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                var updatedUrl = data.photo_url || data.cover_url || data.logo_url;

                if (activeTargetType === 'cover' && updatedUrl) {
                    var cacheBustedUrl = updatedUrl + (updatedUrl.includes('?') ? '&' : '?') + 't=' + Date.now();
                    var coverImg = document.getElementById('cover-photo-preview') || document.getElementById('community-cover-preview');
                    if (coverImg) {
                        if (coverImg.tagName === 'IMG') {
                            coverImg.src = cacheBustedUrl;
                        } else {
                            var newImg = document.createElement('img');
                            newImg.className = 'profile-cover__image';
                            newImg.id = coverImg.id;
                            newImg.src = cacheBustedUrl;
                            newImg.alt = 'Cover Photo';
                            newImg.setAttribute('data-action-trigger', 'cover');
                            newImg.style.cursor = 'pointer';
                            coverImg.replaceWith(newImg);
                        }
                    }
                    var coverMenuItem = document.getElementById('coverDeleteMenuItem') || document.getElementById('communityCoverDeleteMenuItem');
                    if (coverMenuItem) coverMenuItem.style.display = 'flex';
                } else if ((activeTargetType === 'avatar' || activeTargetType === 'logo') && updatedUrl) {
                    var avatarImg = document.getElementById('profile-photo-preview') || document.getElementById('community-logo-preview');
                    if (avatarImg) {
                        if (avatarImg.tagName === 'IMG') {
                            avatarImg.src = updatedUrl;
                        } else {
                            var newAvatarImg = document.createElement('img');
                            newAvatarImg.className = 'profile-avatar';
                            newAvatarImg.id = avatarImg.id;
                            newAvatarImg.src = updatedUrl;
                            newAvatarImg.alt = 'Profile Photo';
                            avatarImg.replaceWith(newAvatarImg);
                        }
                    }
                    var avatarMenuItem = document.getElementById('avatarDeleteMenuItem') || document.getElementById('communityLogoDeleteMenuItem');
                    if (avatarMenuItem) avatarMenuItem.style.display = 'flex';
                }
                window.closePhotoPreviewModal();
            } else {
                var errorBox = document.getElementById('photoModalError');
                var errorText = document.getElementById('photoModalErrorText');
                if (errorBox && errorText) {
                    errorText.textContent = data.message || 'Failed to save image.';
                    errorBox.hidden = false;
                    errorBox.style.display = 'flex';
                }
            }
        } catch (err) {
            showToast('Network error while saving photo.', false);
        } finally {
            if (saveBtn) saveBtn.disabled = false;
            if (saveBtnText) saveBtnText.textContent = 'Save Changes';
        }
    };

    function openFullscreenPhotoViewer(photoType) {
        var viewer = document.getElementById('photoFullscreenViewer');
        var img = document.getElementById('fullscreenViewerImg');
        var title = document.getElementById('fullscreenViewerTitle');
        if (!viewer || !img) return;

        var src = null;
        if (photoType === 'cover') {
            var coverImg = document.getElementById('cover-photo-preview') || document.getElementById('community-cover-preview');
            src = coverImg && coverImg.tagName === 'IMG' ? coverImg.src : (coverImg ? coverImg.dataset.defaultCover : null);
        } else {
            var avatarEl = document.getElementById('profile-photo-preview') || document.getElementById('community-logo-preview');
            if (avatarEl) {
                if (avatarEl.tagName === 'IMG' && avatarEl.src) {
                    src = avatarEl.src;
                } else if (avatarEl.dataset.defaultAvatar) {
                    src = avatarEl.dataset.defaultAvatar;
                }
            }
        }

        if (!src) {
            src = window.defaultAvatarUrl || '/member_assets/images/dashboard/image/profile.png';
        }

        img.src = src;
        if (title) {
            if (photoType === 'cover') {
                title.textContent = 'Cover Photo';
            } else if (photoType === 'avatar') {
                title.textContent = 'Profile Photo';
            } else {
                title.textContent = 'Community Logo';
            }
        }
        window.resetFullscreenZoom();
        viewer.hidden = false;
        viewer.style.display = 'flex';
        refreshLucideIcons();
    }

    window.closeFullscreenViewer = function () {
        var viewer = document.getElementById('photoFullscreenViewer');
        if (viewer) {
            viewer.hidden = true;
            viewer.style.display = 'none';
        }
    };

    window.adjustFullscreenZoom = function (delta) {
        currentFullscreenZoom = Math.min(Math.max(currentFullscreenZoom + delta, 0.5), 3);
        var img = document.getElementById('fullscreenViewerImg');
        var zoomLabel = document.getElementById('fullscreenZoomLevel');
        if (img) img.style.transform = 'scale(' + currentFullscreenZoom + ')';
        if (zoomLabel) zoomLabel.textContent = Math.round(currentFullscreenZoom * 100) + '%';
    };

    window.resetFullscreenZoom = function () {
        currentFullscreenZoom = 1;
        var img = document.getElementById('fullscreenViewerImg');
        var zoomLabel = document.getElementById('fullscreenZoomLevel');
        if (img) img.style.transform = 'scale(1)';
        if (zoomLabel) zoomLabel.textContent = '100%';
    };

    window.handleFullscreenStageClick = function (event) {
        if (event.target.id === 'fullscreenViewerStage') {
            window.closeFullscreenViewer();
        }
    };

    function triggerPhotoFileInput(photoType) {
        var input = null;
        if (photoType === 'cover') {
            input = document.getElementById('cover_photo') || document.getElementById('community_cover_photo');
        } else if (photoType === 'avatar') {
            input = document.getElementById('profile_photo') || document.getElementById('community_logo');
        } else if (photoType === 'logo') {
            input = document.getElementById('community_logo');
        }
        if (input) input.click();
    }

    window.confirmRemovePhoto = function () {
        var targetType = activeTargetType || 'cover';
        window.closePhotoPreviewModal();

        var coverMenuItem = document.getElementById('coverDeleteMenuItem') || document.getElementById('communityCoverDeleteMenuItem');
        var avatarMenuItem = document.getElementById('avatarDeleteMenuItem') || document.getElementById('communityLogoDeleteMenuItem');

        var hasSavedPhoto = false;
        if (targetType === 'cover') {
            var currentCover = document.getElementById('cover-photo-preview') || document.getElementById('community-cover-preview');
            hasSavedPhoto = (currentCover && currentCover.tagName === 'IMG') || (coverMenuItem && coverMenuItem.style.display !== 'none');
        } else {
            var currentAvatar = document.getElementById('profile-photo-preview') || document.getElementById('community-logo-preview');
            hasSavedPhoto = (currentAvatar && currentAvatar.tagName === 'IMG') || (avatarMenuItem && avatarMenuItem.style.display !== 'none');
        }

        if (hasSavedPhoto) {
            openPhotoDeleteModal(targetType);
        }
    };

    function openPhotoDeleteModal(photoType) {
        var modal = document.getElementById('photoDeleteConfirmModal');
        var titleText = document.getElementById('deleteModalTitleText');
        var confirmText = document.getElementById('deleteModalConfirmText');
        if (!modal) return;

        activeTargetType = photoType;
        if (titleText) {
            titleText.textContent = photoType === 'cover' ? 'Delete Cover Photo' : (photoType === 'avatar' ? 'Delete Profile Photo' : 'Delete Community Logo');
        }
        if (confirmText) {
            confirmText.textContent = photoType === 'cover'
                ? 'Are you sure you want to delete this cover photo?'
                : (photoType === 'avatar' ? 'Are you sure you want to delete your profile photo?' : 'Are you sure you want to delete this community logo?');
        }

        modal.hidden = false;
        modal.style.display = 'flex';
        refreshLucideIcons();
    }

    window.closeDeleteConfirmModal = function () {
        var modal = document.getElementById('photoDeleteConfirmModal');
        if (modal) {
            modal.hidden = true;
            modal.style.display = 'none';
        }
    };

    window.executePhotoDelete = async function () {
        if (!activeTargetType) return;

        var formSelector = null;
        if (activeTargetType === 'cover') {
            formSelector = '[data-profile-cover-form], [data-community-cover-form]';
        } else if (activeTargetType === 'avatar') {
            formSelector = '[data-profile-photo-form], [data-community-logo-form]';
        } else {
            formSelector = '[data-community-logo-form]';
        }

        var form = document.querySelector(formSelector);
        if (!form) return;

        var deleteUrl = form.dataset.deleteUrl;
        if (!deleteUrl) return;

        var confirmBtn = document.getElementById('confirmDeletePhotoBtn');
        var confirmBtnText = document.getElementById('confirmDeleteBtnText');
        if (confirmBtn) confirmBtn.disabled = true;
        if (confirmBtnText) confirmBtnText.textContent = 'Deleting...';

        try {
            var response = await fetch(deleteUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json'
                }
            });

            var data = await response.json();

            if (data.success) {
                showToast(data.message, true);
                if (activeTargetType === 'cover') {
                    var coverImg = document.getElementById('cover-photo-preview') || document.getElementById('community-cover-preview');
                    if (coverImg) {
                        var previewDiv = document.createElement('div');
                        previewDiv.className = 'profile-cover__preview';
                        previewDiv.id = coverImg.id;
                        previewDiv.setAttribute('data-action-trigger', 'cover');
                        previewDiv.style.cursor = 'pointer';
                        coverImg.replaceWith(previewDiv);
                    }
                    var coverMenuItem = document.getElementById('coverDeleteMenuItem') || document.getElementById('communityCoverDeleteMenuItem');
                    if (coverMenuItem) coverMenuItem.style.display = 'none';
                    var coverInput = document.getElementById('cover_photo') || document.getElementById('community_cover_photo');
                    if (coverInput) coverInput.value = '';
                } else if (activeTargetType === 'avatar' || activeTargetType === 'logo') {
                    var avatarImg = document.getElementById('profile-photo-preview') || document.getElementById('community-logo-preview');
                    if (avatarImg) {
                        var initialsDiv = document.createElement('div');
                        initialsDiv.className = 'profile-avatar profile-avatar--initials';
                        initialsDiv.id = avatarImg.id;
                        initialsDiv.setAttribute('role', 'img');
                        var defaultAvatar = avatarImg.dataset.defaultAvatar || (window.defaultAvatarUrl || '/member_assets/images/dashboard/image/profile.png');
                        initialsDiv.setAttribute('data-default-avatar', defaultAvatar);
                        initialsDiv.innerHTML = '<span>M</span>';
                        avatarImg.replaceWith(initialsDiv);
                    }
                    var avatarMenuItem = document.getElementById('avatarDeleteMenuItem') || document.getElementById('communityLogoDeleteMenuItem');
                    if (avatarMenuItem) avatarMenuItem.style.display = 'none';
                    var avatarInput = document.getElementById('profile_photo') || document.getElementById('community_logo');
                    if (avatarInput) avatarInput.value = '';
                }
                window.closeDeleteConfirmModal();
            } else {
                showToast(data.message || 'Failed to delete photo.', false);
            }
        } catch (err) {
            showToast('Network error while deleting photo.', false);
        } finally {
            if (confirmBtn) confirmBtn.disabled = false;
            if (confirmBtnText) confirmBtnText.textContent = 'Delete';
        }
    };

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            window.closePhotoPreviewModal();
            window.closeFullscreenViewer();
            window.closeDeleteConfirmModal();
        }
    });
})();
