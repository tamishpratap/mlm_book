(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const getCsrfToken = function () {
            return document.querySelector('meta[name="csrf-token"]')?.content || '';
        };

        const form = document.querySelector('[data-post-composer]');

        if (form && form.dataset.initialized !== 'true') {
            form.dataset.initialized = 'true';
        const body = form.querySelector('[data-post-body]');
        const media = form.querySelector('[data-post-media]');
        const preview = form.querySelector('[data-post-preview]');
        const previewMedia = form.querySelector('[data-post-preview-media]');
        const removeMedia = form.querySelector('[data-post-media-remove]');
        const submit = form.querySelector('[data-post-submit]');
        const submitLabel = form.querySelector('[data-post-submit-label]');
        const feedback = form.querySelector('[data-post-feedback]');
        const createBtn = form.querySelector('[data-post-create-btn]');
        let previewUrl;

        const resizeBody = function () {
            if (! body) return;
            body.style.height = 'auto';
            const minH = form.classList.contains('composer--active') || (body.value && body.value.trim().length > 0) ? 84 : 44;
            body.style.height = Math.max(minH, Math.min(body.scrollHeight, 180)) + 'px';
        };

        const activateComposer = function () {
            form.classList.add('composer--active');
            if (body) {
                body.focus();
                resizeBody();
            }
        };

        createBtn?.addEventListener('click', function (e) {
            e.preventDefault();
            activateComposer();
            body?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });

        body?.addEventListener('focus', function () {
            form.classList.add('composer--active');
            resizeBody();
        });

        const clearPreview = function (resetInput) {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = undefined;
            }

            if (media && resetInput !== false) media.value = '';
            if (previewMedia) previewMedia.replaceChildren();
            if (preview) preview.hidden = true;
        };

        const showFeedback = function (message, isError) {
            if (! feedback) return;
            feedback.textContent = message;
            feedback.classList.toggle('is-error', Boolean(isError));
            feedback.setAttribute('role', isError ? 'alert' : 'status');
        };

        body?.addEventListener('input', resizeBody);
        resizeBody();

        media?.addEventListener('change', function () {
            const file = media.files?.[0];
            clearPreview(false);

            if (! file || ! preview || ! previewMedia) return;

            previewUrl = URL.createObjectURL(file);
            let element;

            if (file.type.startsWith('image/')) {
                element = document.createElement('img');
                element.alt = 'Selected post image preview';
            } else if (file.type.startsWith('video/')) {
                element = document.createElement('video');
                element.controls = true;
                element.preload = 'metadata';
                element.setAttribute('aria-label', 'Selected post video preview');
            } else {
                clearPreview();
                showFeedback('Please choose a supported image or video file.', true);
                return;
            }

            element.src = previewUrl;
            previewMedia.appendChild(element);
            preview.hidden = false;
            showFeedback('', false);
        });

        removeMedia?.addEventListener('click', function () {
            clearPreview(true);
            media?.focus();
        });

        form.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (! submit || submit.disabled) return;

            submit.disabled = true;
            if (submitLabel) submitLabel.textContent = 'Posting…';
            showFeedback('Publishing your post…', false);

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

                if (! response.ok || ! data.success) {
                    const validationMessage = data.errors
                        ? Object.values(data.errors).flat()[0]
                        : null;
                    throw new Error(validationMessage || data.message || 'We could not publish your post. Please try again.');
                }

                const feed = document.querySelector('[data-post-feed]');
                feed?.querySelector('[data-post-empty]')?.remove();
                feed?.insertAdjacentHTML('afterbegin', data.html);
                form.reset();
                form.classList.remove('composer--active');
                clearPreview(true);
                resizeBody();
                showFeedback(data.message, false);

                if (window.lucide) {
                    window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                }
            } catch (error) {
                showFeedback(error.message || 'We could not publish your post. Please try again.', true);
                if (typeof window.showBizPostToast === 'function') {
                    window.showBizPostToast(error.message || 'We could not publish your post. Please try again.', true);
                }
            } finally {
                submit.disabled = false;
                if (submitLabel) submitLabel.textContent = 'Post';
            }
        });
        }

        // Phase 2.1 & 2.2 Post Likes & Reactions AJAX & Modal Handling
        let lastFocused = null;
        let reactionHoverTimer = null;
        let commentReactionHoverTimer = null;

        const showPostReactionPicker = function (picker) {
            if (! picker) return;
            window.clearTimeout(reactionHoverTimer);
            picker.hidden = false;
            picker.removeAttribute('hidden');
            void picker.offsetWidth;
            picker.classList.add('is-visible');
        };

        const hidePostReactionPicker = function (picker, immediate) {
            if (! picker) return;
            window.clearTimeout(reactionHoverTimer);
            picker.classList.remove('is-visible');
            if (immediate) {
                picker.hidden = true;
                picker.setAttribute('hidden', '');
            } else {
                reactionHoverTimer = window.setTimeout(function () {
                    if (! picker.classList.contains('is-visible')) {
                        picker.hidden = true;
                        picker.setAttribute('hidden', '');
                    }
                }, 180);
            }
        };

        const hideAllReactionPickers = function (immediate) {
            document.querySelectorAll('[data-post-reaction-picker]').forEach(function (picker) {
                hidePostReactionPicker(picker, immediate);
            });
        };

        const closeReactorsModal = function () {
            const modalEl = document.querySelector('[data-post-reactors-modal]') || document.querySelector('[data-post-likers-modal]');
            if (! modalEl || modalEl.dataset.closing === 'true') return;

            modalEl.dataset.closing = 'true';
            modalEl.classList.remove('is-open');
            window.setTimeout(function () {
                modalEl.remove();
                lastFocused?.focus?.();
            }, 180);
        };

        const openReactorsModal = async function (postId, triggerElement) {
            if (! postId) return;
            lastFocused = triggerElement || document.activeElement;

            try {
                const response = await fetch('/member/posts/' + postId + '/reactors', {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (! response.ok || ! data.success) return;

                closeReactorsModal();
                const tempWrap = document.createElement('div');
                tempWrap.innerHTML = data.html;
                const modalEl = tempWrap.firstElementChild;
                if (! modalEl) return;

                document.body.appendChild(modalEl);
                if (window.lucide) {
                    window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                }

                window.requestAnimationFrame(function () {
                    modalEl.classList.add('is-open');
                    modalEl.querySelector('.story-modal__close')?.focus();
                });
            } catch (err) {
                // Ignore quietly
            }
        };

        const REACTION_EMOJI_MAP = { like: '👍', love: '❤️', haha: '😂', wow: '😮', sad: '😢', angry: '😡' };
        const REACTION_LABEL_MAP = { like: 'Like', love: 'Love', haha: 'Haha', wow: 'Wow', sad: 'Sad', angry: 'Angry' };
        const REACTION_COLOR_MAP = { like: '#2563eb', love: '#ef4444', haha: '#f59e0b', wow: '#f59e0b', sad: '#f59e0b', angry: '#ea580c' };

        const sendPostReaction = async function (postId, reactionType) {
            if (! postId || ! reactionType) return;

            const pickers = document.querySelectorAll('[data-post-reaction-picker="' + postId + '"]');
            pickers.forEach(p => hidePostReactionPicker(p, true));

            const likeBtns = document.querySelectorAll('[data-post-like-btn="' + postId + '"]');
            const iconEls = document.querySelectorAll('[data-post-reaction-icon="' + postId + '"]');
            const labelEls = document.querySelectorAll('[data-post-like-label="' + postId + '"]');
            const countEls = document.querySelectorAll('[data-post-likes-count="' + postId + '"]');
            const topBadgesEls = document.querySelectorAll('[data-post-top-reactions="' + postId + '"]');

            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

            // Optimistic UI Update
            const optColor = REACTION_COLOR_MAP[reactionType] || '#2563eb';
            const optEmoji = REACTION_EMOJI_MAP[reactionType] || '👍';
            const optLabel = REACTION_LABEL_MAP[reactionType] || 'Like';

            likeBtns.forEach(likeBtn => {
                const isCurrentlyActive = likeBtn.classList.contains('is-active');
                const currentText = labelEls[0]?.textContent || '';
                // If clicking same reaction again, optimistic toggle off; else set new reaction
                if (isCurrentlyActive && currentText === optLabel) {
                    likeBtn.classList.remove('is-active');
                    likeBtn.style.color = '';
                    likeBtn.style.borderColor = '';
                    likeBtn.style.backgroundColor = '';
                } else {
                    likeBtn.classList.add('is-active');
                    likeBtn.style.color = optColor;
                    likeBtn.style.borderColor = optColor + '44';
                    likeBtn.style.backgroundColor = optColor + '12';
                }
            });

            iconEls.forEach(iconEl => {
                const isCurrentlyActive = likeBtns[0]?.classList.contains('is-active');
                if (isCurrentlyActive) {
                    iconEl.textContent = optEmoji;
                } else {
                    iconEl.innerHTML = '<i data-lucide="thumbs-up" aria-hidden="true"></i>';
                }
            });

            labelEls.forEach(labelEl => {
                const isCurrentlyActive = likeBtns[0]?.classList.contains('is-active');
                labelEl.textContent = isCurrentlyActive ? optLabel : 'Like';
            });

            // Snapshot initial state for rollback on error/unverified
            const previousState = {
                btns: Array.from(likeBtns).map(btn => ({
                    el: btn,
                    isActive: btn.classList.contains('is-active'),
                    color: btn.style.color,
                    borderColor: btn.style.borderColor,
                    backgroundColor: btn.style.backgroundColor,
                })),
                icons: Array.from(iconEls).map(el => ({ el, html: el.innerHTML })),
                labels: Array.from(labelEls).map(el => ({ el, text: el.textContent })),
                counts: Array.from(countEls).map(el => ({ el, text: el.textContent })),
                topBadges: Array.from(topBadgesEls).map(el => ({ el, html: el.innerHTML })),
            };

            const rollbackState = function () {
                previousState.btns.forEach(b => {
                    b.el.classList.toggle('is-active', b.isActive);
                    b.el.style.color = b.color;
                    b.el.style.borderColor = b.borderColor;
                    b.el.style.backgroundColor = b.backgroundColor;
                });
                previousState.icons.forEach(i => i.el.innerHTML = i.html);
                previousState.labels.forEach(l => l.el.textContent = l.text);
                previousState.counts.forEach(c => c.el.textContent = c.text);
                previousState.topBadges.forEach(tb => tb.el.innerHTML = tb.html);
                if (window.lucide) {
                    window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                }
            };

            try {
                const response = await fetch('/member/posts/' + postId + '/react', {
                    method: 'POST',
                    body: JSON.stringify({ reaction: reactionType }),
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                });
                const data = await response.json();

                if (! response.ok || ! data || ! data.success) {
                    rollbackState();
                    const errMsg = data?.message || 'Your phone number is not verified. Please complete mobile verification first to continue.';
                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast(errMsg, true);
                    }
                    return;
                }

                if (response.ok && data.success) {
                    const hasReaction = Boolean(data.user_reaction);

                    likeBtns.forEach(likeBtn => {
                        likeBtn.classList.toggle('is-active', hasReaction);
                        if (hasReaction) {
                            const rColor = data.user_reaction_color || REACTION_COLOR_MAP[data.user_reaction] || '#2563eb';
                            likeBtn.style.color = rColor;
                            likeBtn.style.borderColor = rColor + '44';
                            likeBtn.style.backgroundColor = rColor + '12';
                        } else {
                            likeBtn.style.color = '';
                            likeBtn.style.borderColor = '';
                            likeBtn.style.backgroundColor = '';
                        }
                    });

                    iconEls.forEach(iconEl => {
                        if (hasReaction) {
                            iconEl.textContent = data.user_reaction_emoji || REACTION_EMOJI_MAP[data.user_reaction] || '👍';
                        } else {
                            iconEl.innerHTML = '<i data-lucide="thumbs-up" aria-hidden="true"></i>';
                        }
                    });

                    labelEls.forEach(labelEl => {
                        labelEl.textContent = data.user_reaction_label || REACTION_LABEL_MAP[data.user_reaction] || 'Like';
                    });

                    countEls.forEach(countEl => {
                        const newCount = data.reactions_count !== undefined ? data.reactions_count : (data.likes_count !== undefined ? data.likes_count : 0);
                        countEl.textContent = newCount;
                    });

                    topBadgesEls.forEach(topBadgesEl => {
                        topBadgesEl.replaceChildren();
                        if (Array.isArray(data.top_reactions) && data.top_reactions.length > 0) {
                            data.top_reactions.forEach(function (rItem) {
                                const badge = document.createElement('span');
                                badge.className = 'post-actions__badge';
                                badge.textContent = typeof rItem === 'object' ? rItem.emoji : rItem;
                                topBadgesEl.appendChild(badge);
                            });
                        } else {
                            const badge = document.createElement('span');
                            badge.className = 'post-actions__badge';
                            badge.textContent = '👍';
                            topBadgesEl.appendChild(badge);
                        }
                    });

                    if (window.lucide) {
                        window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                    }

                }
            } catch (err) {
                rollbackState();
                console.error('Post reaction failed:', err);
                if (typeof window.showBizPostToast === 'function') {
                    window.showBizPostToast('An error occurred. Please try again.', true);
                }
            }
        };

        const closeCommentReactorsModal = function () {
            const modalEl = document.querySelector('[data-comment-reactors-modal]');
            if (! modalEl || modalEl.dataset.closing === 'true') return;

            modalEl.dataset.closing = 'true';
            modalEl.classList.remove('is-open');
            window.setTimeout(function () {
                modalEl.remove();
                lastFocused?.focus?.();
            }, 180);
        };

        const openCommentReactorsModal = async function (commentId, triggerElement) {
            if (! commentId) return;
            lastFocused = triggerElement || document.activeElement;

            try {
                const response = await fetch('/member/comments/' + commentId + '/reactors', {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (! response.ok || ! data.success) return;

                closeCommentReactorsModal();
                closeReactorsModal();
                const tempWrap = document.createElement('div');
                tempWrap.innerHTML = data.html;
                const modalEl = tempWrap.firstElementChild;
                if (! modalEl) return;

                document.body.appendChild(modalEl);
                if (window.lucide) {
                    window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                }

                window.requestAnimationFrame(function () {
                    modalEl.classList.add('is-open');
                    modalEl.querySelector('.story-modal__close')?.focus();
                });
            } catch (err) {
                // Ignore quietly
            }
        };

        const sendCommentReaction = async function (commentId, reactionType) {
            if (! commentId || ! reactionType) return;

            const pickers = document.querySelectorAll('[data-comment-reaction-picker="' + commentId + '"]');
            pickers.forEach(p => hidePostReactionPicker(p, true));

            const likeBtns = document.querySelectorAll('[data-comment-like-btn="' + commentId + '"]');
            const labelEls = document.querySelectorAll('[data-comment-reaction-label="' + commentId + '"]');
            const countEls = document.querySelectorAll('[data-comment-reactions-count="' + commentId + '"]');
            const topBadgesEls = document.querySelectorAll('[data-comment-top-reactions="' + commentId + '"]');
            const reactorsTriggers = document.querySelectorAll('[data-comment-reactors-open="' + commentId + '"]');

            const token = document.querySelector('meta[name="csrf-token"]')?.content || '';

            const optColor = REACTION_COLOR_MAP[reactionType] || '#2563eb';
            const optEmoji = REACTION_EMOJI_MAP[reactionType] || '👍';
            const optLabel = REACTION_LABEL_MAP[reactionType] || 'Like';

            likeBtns.forEach(likeBtn => {
                const isCurrentlyActive = likeBtn.classList.contains('is-active');
                const currentText = labelEls[0]?.textContent || '';
                if (isCurrentlyActive && currentText === optLabel) {
                    likeBtn.classList.remove('is-active');
                    likeBtn.style.color = '';
                    likeBtn.style.fontWeight = '';
                } else {
                    likeBtn.classList.add('is-active');
                    likeBtn.style.color = optColor;
                    likeBtn.style.fontWeight = '700';
                }
            });

            labelEls.forEach(labelEl => {
                const isCurrentlyActive = likeBtns[0]?.classList.contains('is-active');
                labelEl.textContent = isCurrentlyActive ? optLabel : 'Like';
            });

            try {
                const response = await fetch('/member/comments/' + commentId + '/react', {
                    method: 'POST',
                    body: JSON.stringify({ reaction: reactionType }),
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': token,
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    const hasReaction = Boolean(data.user_reaction);

                    likeBtns.forEach(likeBtn => {
                        likeBtn.classList.toggle('is-active', hasReaction);
                        if (hasReaction) {
                            const rColor = data.user_reaction_color || REACTION_COLOR_MAP[data.user_reaction] || '#2563eb';
                            likeBtn.style.color = rColor;
                            likeBtn.style.fontWeight = '700';
                        } else {
                            likeBtn.style.color = '';
                            likeBtn.style.fontWeight = '';
                        }
                    });

                    labelEls.forEach(labelEl => {
                        labelEl.textContent = data.user_reaction_label || REACTION_LABEL_MAP[data.user_reaction] || 'Like';
                    });

                    countEls.forEach(countEl => {
                        countEl.textContent = data.reactions_count !== undefined ? data.reactions_count : 0;
                    });

                    reactorsTriggers.forEach(trigger => {
                        if (data.reactions_count > 0) {
                            trigger.style.display = 'inline-flex';
                        } else {
                            trigger.style.display = 'none';
                        }
                    });

                    topBadgesEls.forEach(topBadgesEl => {
                        topBadgesEl.replaceChildren();
                        if (Array.isArray(data.top_reactions) && data.top_reactions.length > 0) {
                            data.top_reactions.forEach(function (rItem) {
                                const badge = document.createElement('span');
                                badge.className = 'post-comment-item__badge';
                                badge.textContent = typeof rItem === 'object' ? rItem.emoji : rItem;
                                topBadgesEl.appendChild(badge);
                            });
                        }
                    });
                }
            } catch (err) {
                console.error('Comment reaction failed:', err);
            }
        };

        const togglePostLike = function (postId) {
            // Default click on Like button sends 'like' reaction
            sendPostReaction(postId, 'like');
        };

        // Phase 2.3 Post Comments AJAX & Interaction Handling
        const submitPostComment = async function (form) {
            if (! form || form.dataset.submitting === 'true') return;

            const postId = form.dataset.postCommentForm;
            const inputEl = form.querySelector('[data-post-comment-input]');
            const submitBtn = form.querySelector('.post-comment-form__submit');
            const commentText = inputEl?.value?.trim();

            if (! postId || ! commentText) return;

            form.dataset.submitting = 'true';
            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: JSON.stringify({ comment: commentText }),
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    if (inputEl) inputEl.value = '';

                    const emptyEl = document.querySelector('[data-post-comments-empty="' + postId + '"]');
                    if (emptyEl) emptyEl.remove();

                    const listEl = document.querySelector('[data-post-comment-list="' + postId + '"]') || document.querySelector('[data-post-comments-list="' + postId + '"]');
                    if (listEl) {
                        const tempWrap = document.createElement('div');
                        tempWrap.innerHTML = data.html;
                        const newCommentEl = tempWrap.firstElementChild;
                        if (newCommentEl) {
                            listEl.appendChild(newCommentEl);
                            if (window.lucide) {
                                window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                            }
                            newCommentEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }

                    const countEls = document.querySelectorAll('[data-post-comment-count="' + postId + '"], [data-post-comments-count="' + postId + '"]');
                    countEls.forEach(function (cEl) {
                        if (data.comments_count !== undefined) cEl.textContent = data.comments_count;
                    });

                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast(data.message || 'Comment posted successfully.', false);
                    }
                } else {
                    const errMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Unable to post comment. Please try again.');
                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast(errMsg, true);
                    }
                }
            } catch (err) {
                if (typeof window.showBizPostToast === 'function') {
                    window.showBizPostToast('An error occurred while posting your comment.', true);
                }
            } finally {
                delete form.dataset.submitting;
                if (submitBtn) submitBtn.disabled = false;
            }
        };

        const loadMoreComments = async function (moreBtn) {
            if (! moreBtn || moreBtn.dataset.loading === 'true') return;

            const postId = moreBtn.dataset.postCommentsMore;
            const offset = parseInt(moreBtn.dataset.offset || '5', 10);
            if (! postId) return;

            moreBtn.dataset.loading = 'true';
            const originalText = moreBtn.textContent;
            moreBtn.textContent = 'Loading comments...';

            try {
                const response = await fetch('/member/posts/' + postId + '/comments?offset=' + offset, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    const listEl = document.querySelector('[data-post-comments-list="' + postId + '"]');
                    if (listEl && data.html) {
                        const tempWrap = document.createElement('div');
                        tempWrap.innerHTML = data.html;
                        const fragment = document.createDocumentFragment();
                        while (tempWrap.firstChild) {
                            fragment.appendChild(tempWrap.firstChild);
                        }
                        listEl.insertBefore(fragment, listEl.firstChild);
                        if (window.lucide) {
                            window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                        }
                    }

                    if (data.has_more) {
                        moreBtn.dataset.offset = String(offset + 10);
                        moreBtn.textContent = originalText;
                    } else {
                        moreBtn.remove();
                    }
                } else {
                    moreBtn.textContent = originalText;
                }
            } catch (err) {
                moreBtn.textContent = originalText;
            } finally {
                delete moreBtn.dataset.loading;
            }
        };

        // Phase 2.4 Post Comment Replies AJAX & Interaction Handling
        const submitPostReply = async function (form) {
            if (! form || form.dataset.submitting === 'true') return;

            const parentId = form.dataset.postReplyForm;
            const inputEl = form.querySelector('[data-post-reply-input]');
            const submitBtn = form.querySelector('.post-reply-form__submit');
            const commentText = inputEl?.value?.trim();

            if (! parentId || ! commentText) return;

            form.dataset.submitting = 'true';
            if (submitBtn) submitBtn.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: JSON.stringify({ comment: commentText }),
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    if (inputEl) inputEl.value = '';
                    form.hidden = true;

                    const listEl = document.querySelector('[data-post-replies-list="' + parentId + '"]');
                    if (listEl) {
                        const tempWrap = document.createElement('div');
                        tempWrap.innerHTML = data.html;
                        const newReplyEl = tempWrap.firstElementChild;
                        if (newReplyEl) {
                            listEl.appendChild(newReplyEl);
                            if (window.lucide) {
                                window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                            }
                            newReplyEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }
                    }

                    const replyCountEls = document.querySelectorAll('[data-post-replies-count="' + parentId + '"]');
                    replyCountEls.forEach(function (rEl) {
                        if (data.replies_count !== undefined) rEl.textContent = data.replies_count;
                    });
                } else {
                    const errMsg = data?.message || (data?.errors ? Object.values(data.errors).flat().join(' ') : 'Unable to post reply. Please try again.');
                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast(errMsg, true);
                    }
                }
            } catch (err) {
                if (typeof window.showBizPostToast === 'function') {
                    window.showBizPostToast('An error occurred while posting your reply.', true);
                }
            } finally {
                delete form.dataset.submitting;
                if (submitBtn) submitBtn.disabled = false;
            }
        };

        const submitEditComment = async function (form) {
            if (! form || form.dataset.submitting === 'true') return;

            const inputEl = form.querySelector('[data-post-comment-edit-input]');
            const commentText = inputEl ? inputEl.value.trim() : '';
            if (! commentText) return;

            const commentId = form.dataset.postCommentEditForm;
            const textDisplay = document.querySelector('[data-comment-text-display="' + commentId + '"]');
            const editedBadge = document.querySelector('[data-comment-edited-badge="' + commentId + '"]');
            const url = form.action;

            form.dataset.submitting = 'true';

            try {
                const response = await fetch(url, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    if (textDisplay) {
                        textDisplay.textContent = data.comment_text || commentText;
                        textDisplay.hidden = false;
                    }
                    form.hidden = true;
                    if (editedBadge) {
                        editedBadge.style.display = 'inline';
                    }
                }
            } catch (err) {
                console.error('Edit comment failed:', err);
            } finally {
                delete form.dataset.submitting;
            }
        };

        const loadMoreReplies = async function (moreBtn) {
            if (! moreBtn || moreBtn.dataset.loading === 'true') return;

            const parentId = moreBtn.dataset.postRepliesMore;
            const offset = parseInt(moreBtn.dataset.offset || '2', 10);
            if (! parentId) return;

            moreBtn.dataset.loading = 'true';
            const originalText = moreBtn.textContent;
            moreBtn.textContent = 'Loading replies...';

            try {
                const response = await fetch('/member/comments/' + parentId + '/replies?offset=' + offset, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    const listEl = document.querySelector('[data-post-replies-list="' + parentId + '"]');
                    if (listEl && data.html) {
                        const tempWrap = document.createElement('div');
                        tempWrap.innerHTML = data.html;
                        const fragment = document.createDocumentFragment();
                        while (tempWrap.firstChild) {
                            fragment.appendChild(tempWrap.firstChild);
                        }
                        listEl.appendChild(fragment);
                        if (window.lucide) {
                            window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                        }
                    }

                    if (data.has_more) {
                        moreBtn.dataset.offset = String(offset + 10);
                        moreBtn.textContent = originalText;
                    } else {
                        moreBtn.remove();
                    }
                } else {
                    moreBtn.textContent = originalText;
                }
            } catch (err) {
                moreBtn.textContent = originalText;
            } finally {
                delete moreBtn.dataset.loading;
            }
        };

        const deletePostComment = async function (deleteBtn) {
            if (! deleteBtn) return;

            const commentId = deleteBtn.dataset.postCommentDelete;
            const postId = deleteBtn.dataset.postId;
            const parentId = deleteBtn.dataset.parentId;
            if (! commentId) return;

            try {
                const response = await fetch('/member/comments/' + commentId, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    const commentEl = document.querySelector('[data-post-comment-id="' + commentId + '"]');
                    if (commentEl) {
                        commentEl.style.opacity = '0';
                        commentEl.style.transform = 'scale(0.95)';
                        window.setTimeout(function () {
                            commentEl.remove();
                            if (! data.is_reply) {
                                const listEl = document.querySelector('[data-post-comments-list="' + postId + '"]');
                                if (listEl && listEl.children.length === 0) {
                                    const emptyWrap = document.createElement('div');
                                    emptyWrap.className = 'post-comments__empty';
                                    emptyWrap.dataset.postCommentsEmpty = postId;
                                    emptyWrap.innerHTML = '<p>Be the first to comment.</p>';
                                    listEl.appendChild(emptyWrap);
                                }
                            }
                        }, 180);
                    }

                    if (data.is_reply && data.parent_id) {
                        const replyCountEls = document.querySelectorAll('[data-post-replies-count="' + data.parent_id + '"]');
                        replyCountEls.forEach(function (rEl) {
                            if (data.replies_count !== undefined) rEl.textContent = data.replies_count;
                        });
                    } else {
                        const countEls = document.querySelectorAll('[data-post-comments-count="' + postId + '"]');
                        countEls.forEach(function (cEl) {
                            if (data.comments_count !== undefined) cEl.textContent = data.comments_count;
                        });
                    }
                }
            } catch (err) {
                // Ignore quietly
            }
        };



        document.addEventListener('mouseover', function (event) {
            const postWrapper = event.target.closest('[data-post-like-wrapper]');
            if (postWrapper) {
                const postId = postWrapper.dataset.postLikeWrapper;
                const picker = postWrapper.querySelector('[data-post-reaction-picker="' + postId + '"]');
                if (picker) {
                    showPostReactionPicker(picker);
                }
            }

            const commentWrapper = event.target.closest('[data-comment-like-wrapper]');
            if (commentWrapper) {
                const commentId = commentWrapper.dataset.commentLikeWrapper;
                const picker = commentWrapper.querySelector('[data-comment-reaction-picker="' + commentId + '"]');
                if (picker) {
                    window.clearTimeout(commentReactionHoverTimer);
                    picker.hidden = false;
                }
            }
        });

        document.addEventListener('mouseout', function (event) {
            const postWrapper = event.target.closest('[data-post-like-wrapper]');
            if (postWrapper) {
                const postId = postWrapper.dataset.postLikeWrapper;
                const picker = postWrapper.querySelector('[data-post-reaction-picker="' + postId + '"]');
                if (picker && ! postWrapper.contains(event.relatedTarget)) {
                    window.clearTimeout(reactionHoverTimer);
                    reactionHoverTimer = window.setTimeout(function () {
                        hidePostReactionPicker(picker, false);
                    }, 250);
                }
            }

            const commentWrapper = event.target.closest('[data-comment-like-wrapper]');
            if (commentWrapper) {
                const commentId = commentWrapper.dataset.commentLikeWrapper;
                const picker = commentWrapper.querySelector('[data-comment-reaction-picker="' + commentId + '"]');
                if (picker && ! commentWrapper.contains(event.relatedTarget)) {
                    commentReactionHoverTimer = window.setTimeout(function () {
                        picker.hidden = true;
                    }, 280);
                }
            }
        });

        document.addEventListener('click', function (event) {
            if (! event.target.closest('[data-post-like-wrapper]')) {
                hideAllReactionPickers(false);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' || event.key === 'Esc') {
                hideAllReactionPickers(true);
            }
        });

        // Phase 2.8, 2.9, 2.10 Handling
        let activeReportPostId = null;

        const toggleSavePost = async function (postId, triggerBtn) {
            if (! postId) return;

            try {
                const response = await fetch('/member/posts/' + postId + '/save', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    const isSaved = data.is_saved;

                    // Update action buttons on post card
                    const saveActionBtns = document.querySelectorAll('[data-post-save-btn="' + postId + '"].post-action-btn--save');
                    saveActionBtns.forEach(function (btn) {
                        if (isSaved) {
                            btn.classList.add('is-active');
                        } else {
                            btn.classList.remove('is-active');
                        }
                    });

                    const saveLabelEls = document.querySelectorAll('[data-post-save-label="' + postId + '"]');
                    saveLabelEls.forEach(function (lbl) {
                        lbl.textContent = isSaved ? 'Saved' : 'Save';
                    });

                    const menuLabelEls = document.querySelectorAll('[data-post-save-menu-label="' + postId + '"]');
                    menuLabelEls.forEach(function (lbl) {
                        lbl.textContent = isSaved ? 'Unsave Post' : 'Save Post';
                    });

                    const saveCountEls = document.querySelectorAll('[data-post-saves-count="' + postId + '"]');
                    saveCountEls.forEach(function (countEl) {
                        if (data.saves_count !== undefined) countEl.textContent = data.saves_count;
                    });
                }
            } catch (err) {
                // Ignore quietly
            }
        };

        const hidePost = async function (postId) {
            if (! postId) return;

            const postCard = document.querySelector('.feed-post[data-post-id="' + postId + '"]');
            if (postCard) {
                postCard.style.transition = 'opacity 200ms ease, transform 200ms ease';
                postCard.style.opacity = '0';
                postCard.style.transform = 'scale(0.96)';
            }

            try {
                const response = await fetch('/member/posts/' + postId + '/hide', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    window.setTimeout(function () {
                        if (postCard) postCard.remove();
                    }, 200);
                } else if (postCard) {
                    postCard.style.opacity = '1';
                    postCard.style.transform = 'none';
                }
            } catch (err) {
                if (postCard) {
                    postCard.style.opacity = '1';
                    postCard.style.transform = 'none';
                }
            }
        };

        const closePostReportModal = function () {
            const modalEl = document.querySelector('[data-post-report-modal]');
            if (! modalEl || modalEl.dataset.closing === 'true') return;

            modalEl.dataset.closing = 'true';
            modalEl.classList.remove('is-open');
            activeReportPostId = null;
            window.setTimeout(function () {
                modalEl.remove();
            }, 180);
        };

        const openPostReportModal = function (postId) {
            if (! postId) return;
            activeReportPostId = postId;

            closePostReportModal();

            const modalHtml = `
                <div class="story-modal post-report-modal" role="dialog" aria-modal="true" aria-labelledby="post-report-modal-title" data-post-report-modal>
                    <button class="story-modal__backdrop" type="button" aria-label="Cancel Report" data-post-report-cancel></button>
                    <div class="story-modal__panel post-report-modal__panel">
                        <header class="story-modal__header">
                            <div>
                                <span>Report Content</span>
                                <h2 id="post-report-modal-title">Report Post</h2>
                            </div>
                            <button class="story-modal__close" type="button" aria-label="Close Modal" data-post-report-cancel>
                                <i data-lucide="x" aria-hidden="true"></i>
                            </button>
                        </header>
                        <form class="post-report-modal__form" method="POST" action="/member/posts/${postId}/report" data-post-report-form="${postId}">
                            <div class="post-report-modal__body">
                                <p class="post-report-modal__subtitle">Please select a reason why you are reporting this post:</p>
                                <div class="post-report-modal__reasons">
                                    <label class="post-report-modal__reason-label">
                                        <input type="radio" name="reason" value="Spam" checked>
                                        <span>Spam</span>
                                    </label>
                                    <label class="post-report-modal__reason-label">
                                        <input type="radio" name="reason" value="Fake News">
                                        <span>Fake News</span>
                                    </label>
                                    <label class="post-report-modal__reason-label">
                                        <input type="radio" name="reason" value="Harassment">
                                        <span>Harassment</span>
                                    </label>
                                    <label class="post-report-modal__reason-label">
                                        <input type="radio" name="reason" value="Violence">
                                        <span>Violence</span>
                                    </label>
                                    <label class="post-report-modal__reason-label">
                                        <input type="radio" name="reason" value="Adult Content">
                                        <span>Adult Content</span>
                                    </label>
                                    <label class="post-report-modal__reason-label">
                                        <input type="radio" name="reason" value="Hate Speech">
                                        <span>Hate Speech</span>
                                    </label>
                                    <label class="post-report-modal__reason-label">
                                        <input type="radio" name="reason" value="Other">
                                        <span>Other</span>
                                    </label>
                                </div>
                                <div class="post-report-modal__input-wrap">
                                    <label for="report-description" class="post-report-modal__textarea-label">Additional Details (Optional)</label>
                                    <textarea id="report-description" class="post-report-modal__textarea" name="description" placeholder="Provide more context..." aria-label="Additional details" maxlength="500" rows="3"></textarea>
                                </div>
                            </div>
                            <footer class="post-report-modal__footer">
                                <button class="post-report-modal__btn-cancel" type="button" data-post-report-cancel>Cancel</button>
                                <button class="post-report-modal__btn-submit" type="submit">Send Report</button>
                            </footer>
                        </form>
                    </div>
                </div>
            `;

            const tempWrap = document.createElement('div');
            tempWrap.innerHTML = modalHtml;
            const modalEl = tempWrap.firstElementChild;

            document.body.appendChild(modalEl);
            if (window.lucide) {
                window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
            }

            window.requestAnimationFrame(function () {
                modalEl.classList.add('is-open');
            });
        };

        const submitPostReport = async function (form) {
            if (! form || form.dataset.submitting === 'true') return;

            const postId = form.dataset.postReportForm || activeReportPostId;
            if (! postId) return;

            const reasonEl = form.querySelector('input[name="reason"]:checked');
            const reason = reasonEl ? reasonEl.value : 'Spam';
            const descEl = form.querySelector('textarea[name="description"]');
            const description = descEl ? descEl.value.trim() : '';

            form.dataset.submitting = 'true';
            const submitBtn = form.querySelector('.post-report-modal__btn-submit');
            const originalBtnText = submitBtn ? submitBtn.textContent : 'Send Report';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Sending...';
            }

            try {
                const response = await fetch('/member/posts/' + postId + '/report', {
                    method: 'POST',
                    body: JSON.stringify({ reason: reason, description: description }),
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    closePostReportModal();
                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast(data.message || 'Report submitted successfully.', false);
                    }
                } else {
                    const errMsg = data.message || 'Failed to submit report. Please try again.';
                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast(errMsg, true);
                    }
                }
            } catch (err) {
                if (typeof window.showBizPostToast === 'function') {
                    window.showBizPostToast('An error occurred while submitting your report.', true);
                }
            } finally {
                delete form.dataset.submitting;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            }
        };

        let activeSharePostId = null;

        const closePostShareModal = function (onClosed) {
            const modalEl = document.querySelector('[data-post-share-modal]');
            if (! modalEl || modalEl.dataset.closing === 'true') {
                if (typeof onClosed === 'function') onClosed();
                return;
            }

            modalEl.dataset.closing = 'true';
            modalEl.classList.remove('is-open');
            activeSharePostId = null;
            window.setTimeout(function () {
                modalEl.remove();
                lastFocused?.focus?.();
                if (typeof onClosed === 'function') onClosed();
            }, 190);
        };

        const openPostShareModal = function (postId, triggerElement) {
            if (! postId) return;

            const template = document.querySelector('[data-post-share-template="' + postId + '"]');
            if (! template) return;

            closePostShareModal();
            activeSharePostId = postId;
            lastFocused = triggerElement || document.activeElement;

            const modalEl = template.content
                ? template.content.firstElementChild.cloneNode(true)
                : null;
            if (! modalEl) return;

            const shareForm = modalEl.querySelector('[data-post-share-form]');
            if (shareForm) {
                shareForm.dataset.postShareOpener = postId;
            }

            document.body.appendChild(modalEl);
            if (window.lucide) {
                window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
            }

            window.requestAnimationFrame(function () {
                modalEl.classList.add('is-open');
                modalEl.querySelector('textarea[name="share_message"]')?.focus();
            });
        };

        const submitPostShare = async function (form) {
            if (! form || form.dataset.submitting === 'true') return;

            const targetPostId = form.dataset.postShareForm;
            const openerPostId = form.dataset.postShareOpener || activeSharePostId || targetPostId;
            const submitBtn = form.querySelector('.post-share-modal__btn-submit');
            if (! targetPostId) return;

            const originalBtnText = submitBtn ? submitBtn.textContent : '';
            form.dataset.submitting = 'true';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Sharing...';
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                });
                const data = await response.json();

                if (! response.ok || ! data.success) {
                    const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Failed to share post.');
                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast(errorMsg, true);
                    }
                    return;
                }

                const originalPostId = String(data.original_post_id || targetPostId);
                const shareCountEls = document.querySelectorAll('[data-post-share-count="' + originalPostId + '"], [data-post-shares-count="' + originalPostId + '"]');
                shareCountEls.forEach(function (countEl) {
                    if (data.shares_count !== undefined) countEl.textContent = data.shares_count;
                });

                const shareLabelEls = document.querySelectorAll('[data-post-share-label="' + openerPostId + '"]');
                shareLabelEls.forEach(function (labelEl) {
                    labelEl.textContent = 'Shared';
                });
                document.querySelectorAll('[data-post-share-open="' + openerPostId + '"]').forEach(function (btn) {
                    btn.classList.add('is-active');
                });

                const feed = document.querySelector('[data-post-feed]');
                if (feed && data.html) {
                    feed.querySelector('[data-post-empty]')?.remove();
                    feed.insertAdjacentHTML('afterbegin', data.html);
                    if (data.shared_post_id) {
                        feed.dataset.latestPostId = String(Math.max(
                            parseInt(feed.dataset.latestPostId || '0', 10),
                            parseInt(data.shared_post_id, 10)
                        ));
                    }
                }

                closePostShareModal(function () {
                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast(data.message || 'Post shared successfully.', false);
                    }
                });

                if (window.lucide) {
                    window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                }
            } catch (err) {
                if (typeof window.showBizPostToast === 'function') {
                    window.showBizPostToast('Network error while sharing post. Please try again.', true);
                }
            } finally {
                delete form.dataset.submitting;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            }
        };

        const submitSendToFriends = async function (form) {
            if (! form || form.dataset.submitting === 'true') return;

            const checkboxes = form.querySelectorAll('[data-friend-checkbox]:checked');
            if (checkboxes.length === 0) {
                if (typeof window.showBizPostToast === 'function') {
                    window.showBizPostToast('Please select at least one connection to send to.', true);
                }
                return;
            }

            const submitBtn = form.querySelector('[data-send-to-friends-submit-btn]');
            const originalBtnText = submitBtn ? submitBtn.textContent : '';
            form.dataset.submitting = 'true';
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.textContent = 'Sending...';
            }

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                });
                const data = await response.json();

                if (! response.ok || ! data.success) {
                    const errorMsg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Could not send post to connections.');
                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast(errorMsg, true);
                    }
                    return;
                }

                closePostShareModal(function () {
                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast(data.message || 'Post sent successfully.', false);
                    }
                });
            } catch (err) {
                if (typeof window.showBizPostToast === 'function') {
                    window.showBizPostToast('Network error sending post to connections.', true);
                }
            } finally {
                delete form.dataset.submitting;
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalBtnText;
                }
            }
        };

        const closePostSharersModal = function () {
            const modalEl = document.querySelector('[data-post-sharers-modal]');
            if (! modalEl || modalEl.dataset.closing === 'true') return;

            modalEl.dataset.closing = 'true';
            modalEl.classList.remove('is-open');
            window.setTimeout(function () {
                modalEl.remove();
                lastFocused?.focus?.();
            }, 180);
        };

        const openPostSharersModal = async function (postId, triggerElement) {
            if (! postId) return;
            lastFocused = triggerElement || document.activeElement;

            try {
                const response = await fetch('/member/posts/' + postId + '/sharers', {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (! response.ok || ! data.success) return;

                closePostSharersModal();
                const tempWrap = document.createElement('div');
                tempWrap.innerHTML = data.html;
                const modalEl = tempWrap.firstElementChild;
                if (! modalEl) return;

                document.body.appendChild(modalEl);
                if (window.lucide) {
                    window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                }

                window.requestAnimationFrame(function () {
                    modalEl.classList.add('is-open');
                    modalEl.querySelector('.story-modal__close')?.focus();
                });
            } catch (err) {
                // Ignore quietly
            }
        };

        // Phase 2.10 Infinite Scroll & Skeleton Loader
        let feedCurrentPage = 1;
        let feedHasMore = true;
        let feedIsLoading = false;

        const renderSkeletonLoader = function () {
            return `
                <div class="card feed-post post-skeleton" data-feed-skeleton>
                    <div class="post-skeleton__header">
                        <div class="post-skeleton__avatar"></div>
                        <div class="post-skeleton__meta">
                            <div class="post-skeleton__line post-skeleton__line--name"></div>
                            <div class="post-skeleton__line post-skeleton__line--time"></div>
                        </div>
                    </div>
                    <div class="post-skeleton__body">
                        <div class="post-skeleton__line post-skeleton__line--full"></div>
                        <div class="post-skeleton__line post-skeleton__line--three-quarters"></div>
                    </div>
                </div>
            `;
        };

        const loadNextFeedPage = async function () {
            const feedContainer = document.querySelector('.post-feed[data-post-feed]');
            if (! feedContainer || ! feedHasMore || feedIsLoading) return;

            feedIsLoading = true;

            // Append skeleton loader
            const tempWrap = document.createElement('div');
            tempWrap.innerHTML = renderSkeletonLoader();
            const skeletonEl = tempWrap.firstElementChild;
            feedContainer.appendChild(skeletonEl);

            try {
                const nextPage = feedCurrentPage + 1;
                const url = new URL(window.location.href);
                url.searchParams.set('page', nextPage);

                const response = await fetch(url.toString(), {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (skeletonEl) skeletonEl.remove();

                if (response.ok && data.success) {
                    feedCurrentPage = data.current_page;
                    feedHasMore = data.has_more;

                    if (data.html && data.html.trim() !== '') {
                        const cardsWrap = document.createElement('div');
                        cardsWrap.innerHTML = data.html;
                        while (cardsWrap.firstElementChild) {
                            const newCard = cardsWrap.firstElementChild;
                            feedContainer.appendChild(newCard);
                        }

                        if (window.lucide) {
                            window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                        }
                    }
                }
            } catch (err) {
                if (skeletonEl) skeletonEl.remove();
            } finally {
                feedIsLoading = false;
            }
        };

        const initInfiniteScroll = function () {
            const feedContainer = document.querySelector('.post-feed[data-post-feed]');
            if (! feedContainer) return;

            window.addEventListener('scroll', function () {
                if (! feedHasMore || feedIsLoading) return;

                const scrollBottom = window.innerHeight + window.scrollY;
                const pageHeight = document.documentElement.scrollHeight;

                if (pageHeight - scrollBottom < 500) {
                    loadNextFeedPage();
                }
            }, { passive: true });
        };

        const initScrollPreservation = function () {
            if ('scrollRestoration' in window.history) {
                window.history.scrollRestoration = 'auto';
            }
        };

        // Phase 3 Video Autoplay & Viewport System
        const initVideoAutoplayObserver = function () {
            if (! ('IntersectionObserver' in window)) return;

            const videoObserver = new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    const video = entry.target;
                    if (entry.isIntersecting && entry.intersectionRatio >= 0.5) {
                        video.play().catch(function () {
                            // Autoplay policy prevented playback
                        });
                    } else {
                        video.pause();
                    }
                });
            }, { threshold: 0.5 });

            const observeVideos = function () {
                const videos = document.querySelectorAll('video[data-autoplay-video]');
                videos.forEach(function (vid) {
                    if (! vid.dataset.observed) {
                        vid.dataset.observed = 'true';
                        videoObserver.observe(vid);
                    }
                });
            };

            observeVideos();

            // Observe dynamically added videos
            const observer = new MutationObserver(function () {
                observeVideos();
            });
            const feedContainer = document.querySelector('.post-feed[data-post-feed]');
            if (feedContainer) {
                observer.observe(feedContainer, { childList: true, subtree: true });
            }
        };

        // Phase 3 New Posts Polling & Manual Refresh
        const refreshFeed = async function () {
            const feedContainer = document.querySelector('.post-feed[data-post-feed]');
            if (! feedContainer) return;

            const refreshBtn = document.querySelector('[data-feed-refresh-btn]');
            const pillBtn = document.querySelector('[data-new-posts-pill]');

            if (refreshBtn) refreshBtn.classList.add('is-refreshing');

            try {
                const refreshUrl = window.location.pathname.includes('socials') ? '/member/socials' : window.location.pathname;
                const response = await fetch(refreshUrl, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success && data.html) {
                    feedContainer.innerHTML = data.html;
                    feedCurrentPage = 1;
                    feedHasMore = data.has_more;

                    if (pillBtn) {
                        pillBtn.classList.remove('is-visible');
                        setTimeout(() => { pillBtn.hidden = true; }, 300);
                    }

                    if (window.lucide) {
                        window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                    }

                    window.scrollTo({ top: feedContainer.offsetTop - 80, behavior: 'smooth' });
                }
            } catch (err) {
                // Ignore quietly
            } finally {
                if (refreshBtn) refreshBtn.classList.remove('is-refreshing');
            }
        };

        const pollNewPosts = function () {
            const feedContainer = document.querySelector('.post-feed[data-post-feed]');
            if (! feedContainer) return;

            window.setInterval(async function () {
                const latestId = feedContainer.dataset.latestPostId || '0';
                if (parseInt(latestId, 10) <= 0) return;

                try {
                    const response = await fetch('/member/feed/check-new?latest_id=' + latestId, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();

                    if (response.ok && data.success && data.has_new) {
                        const pillBtn = document.querySelector('[data-new-posts-pill]');
                        if (pillBtn) {
                            pillBtn.hidden = false;
                            requestAnimationFrame(() => {
                                pillBtn.classList.add('is-visible');
                            });
                        }
                    }
                } catch (err) {
                    // Ignore quietly
                }
            }, 25000);
        };

        initInfiniteScroll();
        initScrollPreservation();
        initVideoAutoplayObserver();
        pollNewPosts();

        // Phase 4 Complete Notification System JS
        const updateNotificationBadge = function (count) {
            const badges = document.querySelectorAll('[data-notification-badge]');
            badges.forEach(function (badge) {
                if (count > 0) {
                    badge.textContent = count > 99 ? '99+' : count;
                    badge.hidden = false;
                } else {
                    badge.hidden = true;
                }
            });
        };

        const playNotificationChime = function () {
            try {
                const AudioCtx = window.AudioContext || window.webkitAudioContext;
                if (! AudioCtx) return;
                const ctx = new AudioCtx();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(587.33, ctx.currentTime);
                osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15);
                gain.gain.setValueAtTime(0.08, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.3);
            } catch (e) {
                // Audio context blocked or unsupported
            }
        };

        const pollNotifications = function () {
            const menu = document.querySelector('[data-notification-menu]');
            if (! menu) return;

            window.setInterval(async function () {
                const pollUrl = menu.dataset.pollUrl || '/member/notifications/poll';
                const badgeEl = menu.querySelector('[data-notification-badge]');
                const currentCountText = badgeEl && ! badgeEl.hidden ? badgeEl.textContent.replace('+', '') : '0';
                const currentCount = parseInt(currentCountText, 10) || 0;

                try {
                    const response = await fetch(pollUrl + '?unread_count=' + currentCount, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    const data = await response.json();

                    if (response.ok && data.success) {
                        updateNotificationBadge(data.unread_count);

                        if (data.has_new) {
                            const bellBtn = menu.querySelector('[data-notification-trigger]');
                            if (bellBtn) {
                                bellBtn.classList.add('is-animating');
                                window.setTimeout(function () {
                                    bellBtn.classList.remove('is-animating');
                                }, 1200);
                            }
                            playNotificationChime();
                        }

                        const dropdown = menu.querySelector('[data-notification-dropdown]');
                        if (dropdown && data.html) {
                            dropdown.innerHTML = data.html;
                            if (window.lucide) {
                                window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                            }
                        }
                    }
                } catch (err) {
                    // Quiet error handling
                }
            }, 30000);
        };

        const submitNotificationDelete = async function (form) {
            const button = form.querySelector('button[type="submit"]');
            if (button) button.disabled = true;

            const item = form.closest('.notification-item');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    updateNotificationBadge(data.unread_count);
                    if (item) {
                        item.style.transition = 'opacity 200ms ease, transform 200ms ease';
                        item.style.opacity = '0';
                        item.style.transform = 'translateX(20px)';
                        window.setTimeout(function () {
                            item.remove();
                        }, 200);
                    }
                }
            } catch (err) {
                // Ignore quietly
            } finally {
                if (button) button.disabled = false;
            }
        };

        const submitNotificationClearAll = async function (form) {
            const button = form.querySelector('button[type="submit"]');
            if (button) button.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    updateNotificationBadge(0);
                    const container = document.querySelector('.notifications-page__card');
                    if (container) {
                        container.innerHTML = `
                            <div class="notification-empty">
                                <div class="notification-empty__icon">
                                    <i data-lucide="bell-off" aria-hidden="true"></i>
                                </div>
                                <h2>No Notifications</h2>
                                <p>When you get new notifications, they will show up here.</p>
                            </div>
                        `;
                        if (window.lucide) {
                            window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                        }
                    }
                }
            } catch (err) {
                // Ignore quietly
            } finally {
                if (button) button.disabled = false;
            }
        };

        const submitNotificationRead = async function (form) {
            const button = form.querySelector('button[type="submit"]');
            if (button) button.disabled = true;

            const item = form.closest('.notification-item');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    updateNotificationBadge(data.unread_count);
                    if (item) {
                        item.classList.remove('is-unread');
                        item.classList.add('is-read');
                        const unreadDot = item.querySelector('.notification-item__unread');
                        if (unreadDot) unreadDot.remove();
                    }
                    if (data.url) {
                        window.location.href = data.url;
                    }
                }
            } catch (err) {
                form.submit();
            } finally {
                if (button) button.disabled = false;
            }
        };

        // Phase 5 Profile Timeline JS Functions
        const switchProfileTab = async function (tabLink) {
            const container = document.querySelector('[data-profile-tab-content]');
            if (! container) return;

            const url = tabLink.href;
            const tabKey = tabLink.dataset.profileTab;

            document.querySelectorAll('[data-profile-tab]').forEach(function (t) {
                t.classList.remove('is-active');
            });
            tabLink.classList.add('is-active');

            container.innerHTML = `
                <div class="card post-skeleton">
                    <div class="post-skeleton__header">
                        <div class="post-skeleton__avatar"></div>
                        <div class="post-skeleton__meta">
                            <div class="post-skeleton__line post-skeleton__line--name"></div>
                            <div class="post-skeleton__line post-skeleton__line--time"></div>
                        </div>
                    </div>
                    <div class="post-skeleton__line post-skeleton__line--full" style="margin-top:14px;"></div>
                    <div class="post-skeleton__line post-skeleton__line--three-quarters"></div>
                </div>
            `;

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success && data.html) {
                    container.innerHTML = data.html;
                    window.history.pushState({ tab: tabKey }, '', url);

                    if (window.lucide) {
                        window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                    }
                }
            } catch (err) {
                // Quiet error handling
            }
        };

        const togglePinPost = async function (postId) {
            try {
                const response = await fetch('/member/posts/' + postId + '/pin', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    const labelSpan = document.querySelector('[data-post-pin-menu-label="' + postId + '"]');
                    if (labelSpan) {
                        labelSpan.textContent = data.is_pinned ? 'Unpin from profile' : 'Pin to profile top';
                    }
                    const activeTab = document.querySelector('[data-profile-tab].is-active');
                    if (activeTab) {
                        switchProfileTab(activeTab);
                    }
                }
            } catch (err) {
                // Quiet error handling
            }
        };

        const submitProfileEdit = async function (form) {
            const button = form.querySelector('button[type="submit"]');
            if (button) button.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    const modal = document.querySelector('[data-profile-edit-modal]');
                    if (modal) modal.hidden = true;

                    const nameEl = document.getElementById('profile-name');
                    if (nameEl && data.member && data.member.name) nameEl.textContent = data.member.name;

                    const activeTab = document.querySelector('[data-profile-tab].is-active');
                    if (activeTab) switchProfileTab(activeTab);
                }
            } catch (err) {
                // Quiet error handling
            } finally {
                if (button) button.disabled = false;
            }
        };

        const showUnblockAlert = function (message, isError) {
            const main = document.querySelector('main');
            if (!main || !message) return;

            const existing = main.querySelector('[data-unblock-ajax-message]');
            if (existing) existing.remove();

            const alert = document.createElement('div');
            alert.className = 'member-alert ' + (isError ? 'member-alert--error' : 'member-alert--success');
            alert.dataset.unblockAjaxMessage = 'true';
            alert.setAttribute('role', isError ? 'alert' : 'status');
            alert.setAttribute('aria-live', 'polite');
            alert.innerHTML = '<i data-lucide="' + (isError ? 'circle-alert' : 'circle-check') + '" aria-hidden="true"></i><span>' + message + '</span>';
            main.prepend(alert);

            if (window.lucide) {
                window.lucide.createIcons();
            }

            setTimeout(function () {
                alert.remove();
            }, 5000);
        };

        const submitUnblockUser = async function (form) {
            const memberId = form.dataset.unblockForm;
            const card = document.querySelector('[data-blocked-card="' + memberId + '"]');

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    if (card) {
                        card.style.opacity = '0';
                        setTimeout(function () {
                            card.remove();
                            const remainingCards = document.querySelectorAll('[data-blocked-card]');
                            if (remainingCards.length === 0) {
                                const grid = document.querySelector('.blocked-users-grid');
                                if (grid) {
                                    grid.outerHTML = '<div class="notification-empty"><div class="notification-empty__icon"><i data-lucide="shield-check" aria-hidden="true"></i></div><h2>No Blocked Users</h2><p>You haven\'t blocked any members.</p></div>';
                                    if (window.lucide) window.lucide.createIcons();
                                }
                            }
                        }, 300);
                    }
                    showUnblockAlert(data.message || 'User unblocked successfully.', false);
                } else {
                    showUnblockAlert(data.message || 'Failed to unblock user.', true);
                }
            } catch (err) {
                showUnblockAlert('An error occurred while unblocking the user.', true);
            }
        };

        // Phase 7 Marketplace JS Functions
        const toggleSaveProduct = async function (button) {
            const url = button.dataset.productSaveUrl;
            if (! url) return;

            button.disabled = true;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    button.classList.toggle('is-saved', data.is_saved);
                    const span = button.querySelector('span');
                    if (span) span.textContent = data.is_saved ? 'Saved Item' : 'Save Product';
                }
            } catch (err) {
                // Quiet error handling
            } finally {
                button.disabled = false;
            }
        };

        const filterMarketplace = async function (form) {
            const container = document.querySelector('[data-marketplace-grid-container]');
            if (! container) return;

            const url = form.action + '?' + new URLSearchParams(new FormData(form)).toString();

            try {
                const response = await fetch(url, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success && data.html) {
                    container.innerHTML = data.html;
                    window.history.pushState({}, '', url);

                    if (window.lucide) {
                        window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                    }
                }
            } catch (err) {
                // Quiet error handling
            }
        };

        const toggleJoinGroup = async function (button) {
            const url = button.dataset.groupJoinUrl;
            if (! url) return;

            button.disabled = true;
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': getCsrfToken(),
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    if (data.status === 'accepted') {
                        button.outerHTML = '<button class="member-button member-button--secondary" disabled><i data-lucide="check"></i> Joined</button>';
                    } else {
                        button.outerHTML = '<button class="member-button member-button--secondary" disabled><i data-lucide="clock"></i> Pending</button>';
                    }
                    if (window.lucide) window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                }
            } catch (err) {
                // Quiet error handling
            } finally {
                button.disabled = false;
            }
        };

        const submitEventResponse = async function (form) {
            const btn = form.querySelector('button[type="submit"]');
            if (btn) btn.disabled = true;

            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    const card = form.closest('[data-event-card]');
                    if (card) {
                        const forms = card.querySelectorAll('[data-event-response-form]');
                        forms.forEach(f => {
                            const input = f.querySelector('input[name="response"]');
                            const button = f.querySelector('button');
                            if (input && button) {
                                if (input.value === data.response) {
                                    button.className = 'member-button member-button--primary';
                                } else {
                                    button.className = 'member-button member-button--secondary';
                                }
                            }
                        });
                    }
                }
            } catch (err) {
                // Quiet error handling
            } finally {
                if (btn) btn.disabled = false;
            }
        };

        pollNotifications();

        document.addEventListener('submit', function (event) {
            const eventResponseForm = event.target.closest('[data-event-response-form]');
            if (eventResponseForm) {
                event.preventDefault();
                submitEventResponse(eventResponseForm);
                return;
            }

            const unblockForm = event.target.closest('[data-unblock-form]');
            if (unblockForm) {
                event.preventDefault();
                submitUnblockUser(unblockForm);
                return;
            }

            const profileEditForm = event.target.closest('[data-profile-edit-form]');
            if (profileEditForm) {
                event.preventDefault();
                submitProfileEdit(profileEditForm);
                return;
            }

            const notifDeleteForm = event.target.closest('[data-notification-delete-form]');
            if (notifDeleteForm) {
                event.preventDefault();
                submitNotificationDelete(notifDeleteForm);
                return;
            }

            const notifClearAllForm = event.target.closest('[data-notification-clear-all-form]');
            if (notifClearAllForm) {
                event.preventDefault();
                submitNotificationClearAll(notifClearAllForm);
                return;
            }

            const notifReadForm = event.target.closest('[data-notification-read-form]');
            if (notifReadForm) {
                event.preventDefault();
                submitNotificationRead(notifReadForm);
                return;
            }

            const reportForm = event.target.closest('[data-post-report-form]');
            if (reportForm) {
                event.preventDefault();
                submitPostReport(reportForm);
                return;
            }

            const shareForm = event.target.closest('[data-post-share-form]');
            if (shareForm) {
                event.preventDefault();
                submitPostShare(shareForm);
                return;
            }

            const sendFriendsForm = event.target.closest('[data-send-friends-form]');
            if (sendFriendsForm) {
                event.preventDefault();
                submitSendToFriends(sendFriendsForm);
                return;
            }

            const editForm = event.target.closest('[data-post-comment-edit-form]');
            if (editForm) {
                event.preventDefault();
                submitEditComment(editForm);
                return;
            }

            const commentForm = event.target.closest('[data-post-comment-form]');
            const replyForm = event.target.closest('[data-post-reply-form]');

            if (commentForm) {
                event.preventDefault();
                submitPostComment(commentForm);
                return;
            }

            if (replyForm) {
                event.preventDefault();
                submitPostReply(replyForm);
                return;
            }
        });

        document.addEventListener('input', function (event) {
            const friendSearch = event.target.closest('[data-friend-search-input]');
            if (friendSearch) {
                const query = friendSearch.value.trim().toLowerCase();
                const container = friendSearch.closest('[data-post-share-modal]');
                if (container) {
                    container.querySelectorAll('.friend-share-item').forEach(function (item) {
                        const name = item.dataset.friendName || '';
                        const username = item.dataset.friendUsername || '';
                        const matches = name.includes(query) || username.includes(query);
                        item.style.display = matches ? 'flex' : 'none';
                    });
                }
            }
        });

        document.addEventListener('change', function (event) {
            const filterSelect = event.target.closest('[data-marketplace-filter-select]');
            if (filterSelect) {
                const form = filterSelect.closest('[data-marketplace-filter-form]');
                if (form) filterMarketplace(form);
                return;
            }
        });

        document.addEventListener('click', function (event) {
            const groupJoinBtn = event.target.closest('[data-group-join-btn]');
            if (groupJoinBtn) {
                event.preventDefault();
                toggleJoinGroup(groupJoinBtn);
                return;
            }

            const productSaveBtn = event.target.closest('[data-product-save-btn]');
            if (productSaveBtn) {
                event.preventDefault();
                toggleSaveProduct(productSaveBtn);
                return;
            }

            const galleryThumbBtn = event.target.closest('.gallery-thumb-btn');
            if (galleryThumbBtn) {
                event.preventDefault();
                const viewport = document.getElementById('gallery-main-viewport');
                if (viewport) {
                    const mediaPath = galleryThumbBtn.dataset.mediaPath;
                    const mediaType = galleryThumbBtn.dataset.mediaType;
                    if (mediaType === 'video') {
                        viewport.innerHTML = '<video src="' + mediaPath + '" controls preload="metadata"></video>';
                    } else {
                        viewport.innerHTML = '<img id="gallery-main-image" src="' + mediaPath + '" alt="Product Image">';
                    }
                }
                return;
            }

            const profileTabLink = event.target.closest('[data-profile-tab]');
            if (profileTabLink) {
                event.preventDefault();
                switchProfileTab(profileTabLink);
                return;
            }

            const postPinBtn = event.target.closest('[data-post-pin-btn]');
            if (postPinBtn) {
                event.preventDefault();
                const postId = postPinBtn.dataset.postPinBtn;
                togglePinPost(postId);
                return;
            }

            const profileEditOpen = event.target.closest('[data-profile-edit-open]');
            if (profileEditOpen) {
                event.preventDefault();
                const modal = document.querySelector('[data-profile-edit-modal]');
                if (modal) modal.hidden = false;
                return;
            }

            const profileEditClose = event.target.closest('[data-profile-edit-close]');
            if (profileEditClose) {
                event.preventDefault();
                const modal = document.querySelector('[data-profile-edit-modal]');
                if (modal) modal.hidden = true;
                return;
            }

            const copyBtn = event.target.closest('[data-copy-link-btn]');
            if (copyBtn) {
                event.preventDefault();
                const copyUrl = copyBtn.dataset.copyLinkBtn || window.location.href;

                const copySuccess = function () {
                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast('Link copied successfully.', false);
                    }
                };

                const copyFailure = function () {
                    if (typeof window.showBizPostToast === 'function') {
                        window.showBizPostToast('Unable to copy link. Please try again.', true);
                    }
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(copyUrl).then(copySuccess).catch(function () {
                        try {
                            const ta = document.createElement('textarea');
                            ta.value = copyUrl;
                            ta.style.position = 'fixed';
                            ta.style.left = '-9999px';
                            document.body.appendChild(ta);
                            ta.focus();
                            ta.select();
                            const res = document.execCommand('copy');
                            document.body.removeChild(ta);
                            if (res) copySuccess(); else copyFailure();
                        } catch (e) {
                            copyFailure();
                        }
                    });
                } else {
                    try {
                        const ta = document.createElement('textarea');
                        ta.value = copyUrl;
                        ta.style.position = 'fixed';
                        ta.style.left = '-9999px';
                        document.body.appendChild(ta);
                        ta.focus();
                        ta.select();
                        const res = document.execCommand('copy');
                        document.body.removeChild(ta);
                        if (res) copySuccess(); else copyFailure();
                    } catch (e) {
                        copyFailure();
                    }
                }
                return;
            }

            const tabTrigger = event.target.closest('[data-share-tab-trigger]');
            if (tabTrigger) {
                event.preventDefault();
                const targetTab = tabTrigger.dataset.shareTabTrigger;
                const modal = tabTrigger.closest('[data-post-share-modal]');
                if (modal && targetTab) {
                    modal.querySelectorAll('[data-share-tab-trigger]').forEach(function (btn) {
                        btn.classList.toggle('is-active', btn === tabTrigger);
                    });
                    modal.querySelectorAll('[data-share-tab-panel]').forEach(function (panel) {
                        const isTarget = panel.dataset.shareTabPanel === targetTab;
                        panel.style.display = isTarget ? 'block' : 'none';
                    });
                }
                return;
            }

            const feedRefreshBtn = event.target.closest('[data-feed-refresh-btn]');
            const newPostsPill = event.target.closest('[data-new-posts-pill]');
            const postOptionsToggle = event.target.closest('[data-post-options-toggle]');

            if (feedRefreshBtn || newPostsPill) {
                event.preventDefault();
                refreshFeed();
                return;
            }
            const postSaveBtn = event.target.closest('[data-post-save-btn]');
            const postHideBtn = event.target.closest('[data-post-hide-btn]');
            const postReportOpen = event.target.closest('[data-post-report-open]');
            const postReportCancel = event.target.closest('[data-post-report-cancel]');
            const shareOpenBtn = event.target.closest('[data-post-share-open]');
            const shareCancelBtn = event.target.closest('[data-post-share-cancel]');
            const sharersOpenBtn = event.target.closest('[data-post-sharers-open]');
            const sharersCloseBtn = event.target.closest('[data-post-sharers-close]');
            const commentOptionsToggle = event.target.closest('[data-post-comment-options-toggle]');
            const commentEditToggle = event.target.closest('[data-post-comment-edit-toggle]');
            const commentEditCancel = event.target.closest('[data-post-comment-edit-cancel]');
            const deleteConfirmBtn = event.target.closest('[data-delete-comment-confirm]');
            const deleteCancelBtn = event.target.closest('[data-delete-comment-cancel]');
            const commentReactBtn = event.target.closest('[data-comment-react-btn]');
            const commentLikeBtn = event.target.closest('[data-comment-like-btn]');
            const commentReactorsOpen = event.target.closest('[data-comment-reactors-open]');
            const commentReactorsClose = event.target.closest('[data-comment-reactors-close]');
            const reactBtn = event.target.closest('[data-post-react-btn]');
            const likeBtn = event.target.closest('[data-post-like-btn]');
            const reactorsOpen = event.target.closest('[data-post-reactors-open]') || event.target.closest('[data-post-likers-open]');
            const reactorsClose = event.target.closest('[data-post-reactors-close]') || event.target.closest('[data-post-likers-close]');
            const commentsMore = event.target.closest('[data-post-comments-more]');
            const commentDelete = event.target.closest('[data-post-comment-delete]');
            const replyToggle = event.target.closest('[data-post-reply-toggle]');
            const replyCancel = event.target.closest('[data-post-reply-cancel]');
            const repliesMore = event.target.closest('[data-post-replies-more]');

            if (postOptionsToggle) {
                event.preventDefault();
                event.stopPropagation();
                const postId = postOptionsToggle.dataset.postOptionsToggle;
                const menu = document.querySelector('[data-post-options-menu="' + postId + '"]');
                if (menu) {
                    const isHidden = menu.hidden || menu.hasAttribute('hidden');
                    document.querySelectorAll('[data-post-options-menu]').forEach(m => {
                        m.hidden = true;
                        m.setAttribute('hidden', '');
                    });
                    if (isHidden) {
                        menu.hidden = false;
                        menu.removeAttribute('hidden');
                    }
                }
                return;
            }

            if (postSaveBtn) {
                event.preventDefault();
                const postId = postSaveBtn.dataset.postSaveBtn;
                const menu = postSaveBtn.closest('[data-post-options-menu]');
                if (menu) {
                    menu.hidden = true;
                    menu.setAttribute('hidden', '');
                }
                toggleSavePost(postId, postSaveBtn);
                return;
            }

            if (postHideBtn) {
                event.preventDefault();
                const postId = postHideBtn.dataset.postHideBtn;
                const menu = postHideBtn.closest('[data-post-options-menu]');
                if (menu) {
                    menu.hidden = true;
                    menu.setAttribute('hidden', '');
                }
                hidePost(postId);
                return;
            }

            if (postReportOpen) {
                event.preventDefault();
                const postId = postReportOpen.dataset.postReportOpen;
                const menu = postReportOpen.closest('[data-post-options-menu]');
                if (menu) {
                    menu.hidden = true;
                    menu.setAttribute('hidden', '');
                }
                openPostReportModal(postId);
                return;
            }

            if (postReportCancel) {
                event.preventDefault();
                closePostReportModal();
                return;
            }

            if (shareOpenBtn) {
                event.preventDefault();
                const postId = shareOpenBtn.dataset.postShareOpen;
                openPostShareModal(postId, shareOpenBtn);
                return;
            }

            if (shareCancelBtn) {
                event.preventDefault();
                closePostShareModal();
                return;
            }

            if (sharersOpenBtn) {
                event.preventDefault();
                const postId = sharersOpenBtn.dataset.postSharersOpen;
                openPostSharersModal(postId, sharersOpenBtn);
                return;
            }

            if (sharersCloseBtn) {
                event.preventDefault();
                closePostSharersModal();
                return;
            }

            if (commentOptionsToggle) {
                event.preventDefault();
                event.stopPropagation();
                const commentId = commentOptionsToggle.dataset.postCommentOptionsToggle;
                const menu = document.querySelector('[data-post-comment-options-menu="' + commentId + '"]');
                if (menu) {
                    const isHidden = menu.hidden;
                    document.querySelectorAll('[data-post-comment-options-menu]').forEach(m => m.hidden = true);
                    menu.hidden = ! isHidden;
                }
                return;
            }

            if (commentEditToggle) {
                event.preventDefault();
                const commentId = commentEditToggle.dataset.postCommentEditToggle;
                const form = document.querySelector('[data-post-comment-edit-form="' + commentId + '"]');
                const textDisplay = document.querySelector('[data-comment-text-display="' + commentId + '"]');
                const menu = document.querySelector('[data-post-comment-options-menu="' + commentId + '"]');
                if (menu) menu.hidden = true;

                if (form) {
                    form.hidden = false;
                    if (textDisplay) textDisplay.hidden = true;
                    form.querySelector('[data-post-comment-edit-input]')?.focus();
                }
                return;
            }

            if (commentEditCancel) {
                event.preventDefault();
                const commentId = commentEditCancel.dataset.postCommentEditCancel;
                const form = document.querySelector('[data-post-comment-edit-form="' + commentId + '"]');
                const textDisplay = document.querySelector('[data-comment-text-display="' + commentId + '"]');
                if (form) form.hidden = true;
                if (textDisplay) textDisplay.hidden = false;
                return;
            }

            if (deleteConfirmBtn) {
                event.preventDefault();
                if (activeDeleteBtn) {
                    deletePostComment(activeDeleteBtn);
                }
                closeDeleteCommentModal();
                return;
            }

            if (deleteCancelBtn) {
                event.preventDefault();
                closeDeleteCommentModal();
                return;
            }

            if (commentReactBtn) {
                event.preventDefault();
                event.stopPropagation();
                const commentId = commentReactBtn.dataset.commentId;
                const reactionType = commentReactBtn.dataset.commentReactBtn;
                sendCommentReaction(commentId, reactionType);
                return;
            }

            if (commentLikeBtn) {
                event.preventDefault();
                const commentId = commentLikeBtn.dataset.commentId;
                sendCommentReaction(commentId, 'like');
                return;
            }

            if (commentReactorsOpen) {
                event.preventDefault();
                const commentId = commentReactorsOpen.dataset.commentReactorsOpen;
                openCommentReactorsModal(commentId, commentReactorsOpen);
                return;
            }

            if (commentReactorsClose) {
                event.preventDefault();
                closeCommentReactorsModal();
                return;
            }

            if (reactBtn) {
                event.preventDefault();
                event.stopPropagation();
                const postId = reactBtn.dataset.postId;
                const reactionType = reactBtn.dataset.postReactBtn;
                sendPostReaction(postId, reactionType);
                return;
            }

            if (likeBtn) {
                event.preventDefault();
                const postId = likeBtn.dataset.postLikeBtn;
                const wrapper = likeBtn.closest('[data-post-like-wrapper]');
                const picker = wrapper?.querySelector('[data-post-reaction-picker="' + postId + '"]');

                if (picker) {
                    if (picker.classList.contains('is-visible')) {
                        hidePostReactionPicker(picker, false);
                    } else {
                        showPostReactionPicker(picker);
                    }
                }
                togglePostLike(postId);
                return;
            }

            if (reactorsOpen) {
                event.preventDefault();
                const postId = reactorsOpen.dataset.postReactorsOpen || reactorsOpen.dataset.postLikersOpen;
                openReactorsModal(postId, reactorsOpen);
                return;
            }

            if (reactorsClose) {
                event.preventDefault();
                closeReactorsModal();
                return;
            }

            if (replyToggle) {
                event.preventDefault();
                const commentId = replyToggle.dataset.postReplyToggle;
                const form = document.querySelector('[data-post-reply-form="' + commentId + '"]');
                if (form) {
                    form.hidden = ! form.hidden;
                    if (! form.hidden) {
                        form.querySelector('[data-post-reply-input]')?.focus();
                    }
                }
                return;
            }

            if (replyCancel) {
                event.preventDefault();
                const commentId = replyCancel.dataset.postReplyCancel;
                const form = document.querySelector('[data-post-reply-form="' + commentId + '"]');
                if (form) form.hidden = true;
                return;
            }

            if (repliesMore) {
                event.preventDefault();
                loadMoreReplies(repliesMore);
                return;
            }

            if (commentsMore) {
                event.preventDefault();
                loadMoreComments(commentsMore);
                return;
            }

            if (commentDelete) {
                event.preventDefault();
                const menu = commentDelete.closest('[data-post-comment-options-menu]');
                if (menu) menu.hidden = true;
                openDeleteCommentModal(commentDelete);
                return;
            }

            // Close options menus on outside click
            if (! event.target.closest('[data-post-options-wrapper]')) {
                document.querySelectorAll('[data-post-options-menu]').forEach(m => {
                    m.hidden = true;
                    m.setAttribute('hidden', '');
                });
            }
            if (! event.target.closest('[data-comment-options-wrapper]')) {
                document.querySelectorAll('[data-post-comment-options-menu]').forEach(m => m.hidden = true);
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                if (document.querySelector('#bizPostDeleteConfirmModal')) {
                    window.closeBizPostDeleteModal();
                } else if (document.querySelector('[data-post-report-modal]')) {
                    closePostReportModal();
                } else if (document.querySelector('[data-post-share-modal]')) {
                    closePostShareModal();
                } else if (document.querySelector('[data-post-sharers-modal]')) {
                    closePostSharersModal();
                } else if (document.querySelector('[data-delete-comment-modal]')) {
                    closeDeleteCommentModal();
                } else if (document.querySelector('[data-comment-reactors-modal]')) {
                    closeCommentReactorsModal();
                } else if (document.querySelector('[data-post-reactors-modal]') || document.querySelector('[data-post-likers-modal]')) {
                    closeReactorsModal();
                }
            }
        });

        // Business Post Options Actions: Pin, Feature, Delete
        let activeBizDeleteUrl = null;
        let activeBizDeletePostId = null;

        window.showBizPostToast = function (message, isError = false) {
            let region = document.querySelector('[data-profile-upload-toast-region]');
            if (! region) {
                region = document.createElement('div');
                region.className = 'profile-upload-toast-region';
                region.setAttribute('data-profile-upload-toast-region', '');
                region.setAttribute('aria-live', 'polite');
                region.setAttribute('aria-atomic', 'true');
                document.body.appendChild(region);
            }

            region.style.position = 'fixed';
            region.style.top = '80px';
            region.style.right = '20px';
            region.style.zIndex = '1040';
            region.style.pointerEvents = 'none';
            region.style.display = 'flex';
            region.style.flexDirection = 'column';
            region.style.gap = '8px';

            const toast = document.createElement('div');
            toast.className = 'profile-upload-toast' + (isError ? ' is-error' : '');
            toast.setAttribute('role', 'status');
            toast.style.pointerEvents = 'auto';
            toast.style.display = 'flex';
            toast.style.alignItems = 'center';
            toast.style.gap = '12px';
            toast.style.minWidth = '320px';
            toast.style.maxWidth = '380px';
            toast.style.padding = '12px 18px';
            toast.style.backgroundColor = '#ffffff';
            toast.style.color = '#0f172a';
            toast.style.border = '1px solid ' + (isError ? '#fecaca' : '#bbf7d0');
            toast.style.borderRadius = '10px';
            toast.style.boxShadow = '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)';
            toast.style.fontSize = '14px';
            toast.style.fontWeight = '500';
            toast.style.lineHeight = '1.4';
            toast.style.transition = 'all 0.2s ease-in-out';

            const iconColor = isError ? '#dc2626' : '#16a34a';
            toast.innerHTML = '<i data-lucide="' + (isError ? 'circle-alert' : 'check-circle-2') + '" style="color:' + iconColor + '; flex-shrink: 0; width: 20px; height: 20px;" aria-hidden="true"></i><span style="flex: 1; min-width: 0; word-break: break-word;"></span>';
            toast.querySelector('span').innerText = message;
            region.appendChild(toast);

            if (window.lucide) {
                window.lucide.createIcons({ attrs: { 'stroke-width': 2 } });
            }

            setTimeout(function () {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-6px)';
                setTimeout(function () {
                    toast.remove();
                    if (! region.children.length) {
                        region.remove();
                    }
                }, 200);
            }, 3500);
        };

        window.toggleBizPinPost = function (url, postId, btnElement) {
            const menu = btnElement ? btnElement.closest('[data-post-options-menu]') : null;
            if (menu) {
                menu.hidden = true;
                menu.setAttribute('hidden', '');
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const postCard = document.querySelector(`article[data-post-id="${postId}"]`);
                    const feed = document.getElementById('bizTimelineFeed') || document.querySelector('[data-post-feed]');

                    if (data.is_pinned) {
                        // Unpin previous pinned post badges & labels in DOM
                        document.querySelectorAll('[data-biz-pinned-tag="true"]').forEach(el => el.remove());
                        document.querySelectorAll('[data-biz-pin-label]').forEach(label => {
                            label.textContent = 'Pin to Top';
                        });

                        if (postCard) {
                            const metaDiv = postCard.querySelector('.post-header__meta > div');
                            if (metaDiv && !metaDiv.querySelector('[data-biz-pinned-tag]')) {
                                const pinTag = document.createElement('span');
                                pinTag.className = 'post-header__pinned-tag';
                                pinTag.setAttribute('data-biz-pinned-tag', 'true');
                                pinTag.innerHTML = '· <i data-lucide="pin" aria-hidden="true"></i> Pinned Post';
                                metaDiv.appendChild(pinTag);
                            }

                            const pinLabel = postCard.querySelector('[data-biz-pin-label]');
                            if (pinLabel) pinLabel.textContent = 'Unpin Post';

                            if (feed && postCard.parentNode === feed) {
                                feed.prepend(postCard);
                            }
                        }
                    } else {
                        if (postCard) {
                            const pinTag = postCard.querySelector('[data-biz-pinned-tag]');
                            if (pinTag) pinTag.remove();

                            const pinLabel = postCard.querySelector('[data-biz-pin-label]');
                            if (pinLabel) pinLabel.textContent = 'Pin to Top';
                        }
                    }

                    if (window.lucide) {
                        window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                    }

                    window.showBizPostToast(data.message, false);
                } else {
                    window.showBizPostToast(data.message || 'Failed to update pin status.', true);
                }
            })
            .catch(err => {
                window.showBizPostToast('An error occurred. Please try again.', true);
            });
        };

        window.toggleBizFeaturePost = function (url, postId, btnElement) {
            const menu = btnElement ? btnElement.closest('[data-post-options-menu]') : null;
            if (menu) {
                menu.hidden = true;
                menu.setAttribute('hidden', '');
            }

            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const postCard = document.querySelector(`article[data-post-id="${postId}"]`);
                    if (postCard) {
                        const metaDiv = postCard.querySelector('.post-header__meta > div');
                        const featureLabel = postCard.querySelector('[data-biz-feature-label]');

                        if (data.is_featured) {
                            if (metaDiv && !metaDiv.querySelector('[data-biz-featured-tag]')) {
                                const featTag = document.createElement('span');
                                featTag.className = 'biz-badge biz-badge--verified';
                                featTag.setAttribute('data-biz-featured-tag', 'true');
                                featTag.style.cssText = 'margin-left: 4px; font-size: 11px; padding: 2px 8px;';
                                featTag.innerHTML = '<i data-lucide="sparkles" style="width: 10px; height: 10px;"></i> Featured';
                                metaDiv.appendChild(featTag);
                            }
                            if (featureLabel) featureLabel.textContent = 'Remove Featured Tag';
                        } else {
                            const featTag = postCard.querySelector('[data-biz-featured-tag]');
                            if (featTag) featTag.remove();

                            if (featureLabel) featureLabel.textContent = 'Mark as Featured';
                        }
                    }

                    if (window.lucide) {
                        window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                    }

                    window.showBizPostToast(data.message, false);
                } else {
                    window.showBizPostToast(data.message || 'Failed to update feature status.', true);
                }
            })
            .catch(err => {
                window.showBizPostToast('An error occurred. Please try again.', true);
            });
        };

        window.closeBizPostDeleteModal = function () {
            const modal = document.getElementById('bizPostDeleteConfirmModal');
            if (modal) {
                modal.setAttribute('hidden', 'true');
                modal.style.display = 'none';
            }
            activeBizDeleteUrl = null;
            activeBizDeletePostId = null;
        };

        window.executeBizPostDelete = function () {
            if (! activeBizDeleteUrl || ! activeBizDeletePostId) return;

            const confirmBtn = document.getElementById('confirmBizDeletePostBtn');
            if (confirmBtn) confirmBtn.disabled = true;

            fetch(activeBizDeleteUrl, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': getCsrfToken(),
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(res => res.json())
            .then(data => {
                if (confirmBtn) confirmBtn.disabled = false;
                const postIdToRemove = activeBizDeletePostId;
                window.closeBizPostDeleteModal();

                if (data.success) {
                    const postCard = document.querySelector(`article[data-post-id="${postIdToRemove}"]`);
                    if (postCard) {
                        postCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        postCard.style.opacity = '0';
                        postCard.style.transform = 'translateY(-10px)';
                        setTimeout(() => postCard.remove(), 300);
                    }
                    window.showBizPostToast(data.message || 'Business post deleted successfully.', false);
                } else {
                    window.showBizPostToast(data.message || 'Failed to delete post.', true);
                }
            })
            .catch(err => {
                if (confirmBtn) confirmBtn.disabled = false;
                window.closeBizPostDeleteModal();
                window.showBizPostToast('An error occurred while deleting the post.', true);
            });
        };

        window.deleteBizPost = function (url, postId, btnElement) {
            const menu = btnElement ? btnElement.closest('[data-post-options-menu]') : null;
            if (menu) {
                menu.hidden = true;
                menu.setAttribute('hidden', '');
            }

            activeBizDeleteUrl = url;
            activeBizDeletePostId = postId;

            let modal = document.getElementById('bizPostDeleteConfirmModal');
            if (! modal) {
                modal = document.createElement('div');
                modal.id = 'bizPostDeleteConfirmModal';
                modal.className = 'photo-delete-modal';
                modal.setAttribute('role', 'dialog');
                modal.setAttribute('aria-modal', 'true');
                modal.innerHTML = `
                    <div class="photo-delete-modal__backdrop" onclick="window.closeBizPostDeleteModal()"></div>
                    <div class="photo-delete-modal__content card">
                        <header class="photo-delete-modal__header">
                            <h3>
                                <i data-lucide="trash-2" style="color: #e5484d;"></i>
                                <span>Delete Business Post?</span>
                            </h3>
                            <button type="button" class="icon-button" onclick="window.closeBizPostDeleteModal()" aria-label="Close modal">
                                <i data-lucide="x"></i>
                            </button>
                        </header>

                        <div class="photo-delete-modal__body">
                            <p>Are you sure you want to delete this business post? This action cannot be undone.</p>
                        </div>

                        <footer class="photo-delete-modal__footer">
                            <button type="button" class="member-button member-button--secondary" onclick="window.closeBizPostDeleteModal()">Cancel</button>
                            <button type="button" class="member-button member-button--danger" id="confirmBizDeletePostBtn" onclick="window.executeBizPostDelete()">
                                <i data-lucide="trash-2"></i>
                                <span>Delete</span>
                            </button>
                        </footer>
                    </div>
                `;
                document.body.appendChild(modal);

                if (window.lucide) {
                    window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
                }
            }

            modal.removeAttribute('hidden');
            modal.style.display = 'flex';
        };
    });
}());
