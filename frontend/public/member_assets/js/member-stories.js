(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        if (document.body.dataset.storiesInitialized === 'true') return;
        document.body.dataset.storiesInitialized = 'true';

        const modal = document.querySelector('[data-story-create-modal]');
        const form = modal?.querySelector('[data-story-form]');
        const media = form?.querySelector('[data-story-media]');
        const preview = form?.querySelector('[data-story-preview]');
        const previewMedia = form?.querySelector('[data-story-preview-media]');
        const submit = form?.querySelector('[data-story-submit]');
        const submitLabel = form?.querySelector('[data-story-submit-label]');
        const feedback = form?.querySelector('[data-story-feedback]');
        const viewerMount = document.querySelector('[data-story-viewer-mount]');
        let previewUrl;
        let lastFocused;
        let isSubmitting = false;
        let activeViewerState;

        const refreshIcons = function () {
            if (window.lucide) {
                window.lucide.createIcons({ attrs: { 'stroke-width': 1.9 } });
            }
        };

        const showFeedback = function (message, isError) {
            if (! feedback) return;
            feedback.textContent = message;
            feedback.classList.toggle('is-error', Boolean(isError));
            feedback.setAttribute('role', isError ? 'alert' : 'status');
        };

        const showPageMessage = function (message, isError) {
            const main = document.querySelector('main');
            if (! main) return;

            main.querySelector('[data-story-ajax-message]')?.remove();
            const alert = document.createElement('div');
            alert.className = 'member-alert ' + (isError ? 'member-alert--error' : 'member-alert--success');
            alert.dataset.storyAjaxMessage = 'true';
            alert.setAttribute('role', isError ? 'alert' : 'status');
            alert.setAttribute('aria-live', 'polite');
            alert.textContent = message;
            main.prepend(alert);
        };

        const updateSubmit = function () {
            if (submit) submit.disabled = isSubmitting || ! media?.files?.length;
        };

        const clearPreview = function (resetInput) {
            if (previewUrl) {
                URL.revokeObjectURL(previewUrl);
                previewUrl = undefined;
            }

            if (media && resetInput !== false) media.value = '';
            previewMedia?.replaceChildren();
            if (preview) preview.hidden = true;
            const uploadLabel = form?.querySelector('.story-upload');
            if (uploadLabel) uploadLabel.hidden = false;

            const charCounter = form?.querySelector('[data-story-char-counter]');
            if (charCounter && resetInput) charCounter.textContent = '0 / 500';

            updateSubmit();
        };

        const openCreateModal = function () {
            if (! modal) return;
            lastFocused = document.activeElement;
            modal.hidden = false;
            document.body.classList.add('story-overlay-open');
            window.requestAnimationFrame(function () {
                modal.classList.add('is-open');
                modal.querySelector('[data-story-create-close]')?.focus();
            });
        };

        const closeCreateModal = function (reset) {
            if (! modal) return;
            modal.classList.remove('is-open');
            window.setTimeout(function () {
                modal.hidden = true;
                if (! document.querySelector('[data-story-viewer]')) {
                    document.body.classList.remove('story-overlay-open');
                }
            }, 180);

            if (reset) {
                form?.reset();
                clearPreview(true);
                showFeedback('', false);
            }

            lastFocused?.focus?.();
        };

        let globalStoryMuted = true;
        let preloadedMedia = new Map();
        let pendingFetches = new Set();

        const preloadStoryMedia = async function (storyId) {
            if (! storyId) return;
            const key = String(storyId);
            if (preloadedMedia.has(key) || pendingFetches.has(key)) return;

            pendingFetches.add(key);
            try {
                const response = await fetch('/member/stories/' + key, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();
                if (data.success && data.html) {
                    preloadedMedia.set(key, data);
                    const temp = document.createElement('div');
                    temp.innerHTML = data.html;
                    const media = temp.querySelector('[data-story-media]');
                    if (media && media.src) {
                        if (media.tagName.toLowerCase() === 'img') {
                            const img = new Image();
                            img.src = media.src;
                        } else if (media.tagName.toLowerCase() === 'video') {
                            const video = document.createElement('video');
                            video.src = media.src;
                            video.preload = 'auto';
                        }
                    }
                }
            } catch (err) {
                // Ignore preloading errors quietly
            } finally {
                pendingFetches.delete(key);
            }
        };

        const navigateToStory = async function (storyId) {
            if (! storyId) {
                closeViewer();
                return;
            }
            if (! viewerMount) return;

            const key = String(storyId);

            try {
                let data = preloadedMedia.get(key);
                if (! data) {
                    const response = await fetch('/member/stories/' + key, {
                        headers: {
                            Accept: 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                    });
                    data = await response.json();
                }

                if (! data || ! data.success) {
                    closeViewer();
                    return;
                }

                if (data.story_id && data.views_count !== undefined) {
                    updateStoryViewCount(data.story_id, data.views_count);
                }

                cleanupViewer(true);
                viewerMount.innerHTML = data.html;
                refreshIcons();
                const newViewer = viewerMount.querySelector('[data-story-viewer]');
                initializeViewer(newViewer);
                window.requestAnimationFrame(function () {
                    newViewer?.classList.add('is-open');
                });
            } catch (err) {
                closeViewer();
            }
        };

        const setViewerProgress = function (state, percentage) {
            if (! state?.progress) return;
            const safePercentage = Math.min(100, Math.max(0, Number.isFinite(percentage) ? percentage : 0));
            state.progress.style.width = safePercentage + '%';
        };

        const setViewerButtonIcon = function (button, icon) {
            if (! button) return;
            button.replaceChildren();
            const iconElement = document.createElement('i');
            iconElement.dataset.lucide = icon;
            iconElement.setAttribute('aria-hidden', 'true');
            button.appendChild(iconElement);
            refreshIcons();
        };

        const pauseImageProgress = function (state) {
            if (! state || state.mediaType !== 'image' || state.imagePaused || ! state.imageStartedAt) return;
            state.imageElapsed += performance.now() - state.imageStartedAt;
            state.imageStartedAt = 0;
            state.imagePaused = true;
            window.cancelAnimationFrame(state.imageProgressFrame);
        };

        const runImageProgress = function (state) {
            if (! state || state !== activeViewerState || state.mediaType !== 'image') return;

            state.imagePaused = false;
            state.imageStartedAt = performance.now();
            const tick = function (timestamp) {
                if (state !== activeViewerState || state.imagePaused) return;

                const elapsed = state.imageElapsed + (timestamp - state.imageStartedAt);
                setViewerProgress(state, (elapsed / 5000) * 100);
                if (elapsed < 5000) {
                    state.imageProgressFrame = window.requestAnimationFrame(tick);
                } else {
                    setViewerProgress(state, 100);
                    const nextId = state.viewer?.dataset.storyNextId;
                    navigateToStory(nextId);
                }
            };
            state.imageProgressFrame = window.requestAnimationFrame(tick);
        };

        const resumeImageProgress = function (state) {
            if (! state || state.mediaType !== 'image' || ! state.imagePaused || state.imageElapsed >= 5000) return;
            runImageProgress(state);
        };

        const updateVideoControls = function (state) {
            const video = state?.media;
            if (! video || state.mediaType !== 'video') return;

            const paused = video.paused || video.ended;
            state.playButton?.classList.toggle('is-paused', paused);
            state.playButton?.setAttribute('aria-label', paused ? 'Play Story' : 'Pause Story');
            setViewerButtonIcon(state.playButton, paused ? 'play' : 'pause');

            if (state.muteButton) {
                state.muteButton.setAttribute('aria-label', video.muted ? 'Unmute Story' : 'Mute Story');
                setViewerButtonIcon(state.muteButton, video.muted ? 'volume-x' : 'volume-2');
            }
        };

        const showViewerReady = function (state) {
            if (! state || state !== activeViewerState || state.ready) return;
            state.ready = true;
            if (state.loading) state.loading.hidden = true;

            if (state.mediaType === 'image') {
                runImageProgress(state);
            }
        };

        const showViewerError = function (state) {
            if (! state || state !== activeViewerState) return;
            state.hasError = true;
            if (state.loading) state.loading.hidden = true;
            if (state.error) state.error.hidden = false;
            if (state.media) state.media.hidden = true;
            if (state.playButton) state.playButton.hidden = true;
            if (state.muteButton) state.muteButton.hidden = true;
            pauseImageProgress(state);
            state.media?.pause?.();

            const nextId = state.viewer?.dataset.storyNextId;
            window.setTimeout(function () {
                if (activeViewerState === state) {
                    if (nextId) {
                        navigateToStory(nextId);
                    } else {
                        closeViewer();
                    }
                }
            }, 3000);
        };

        const cleanupViewer = function (resetVideo) {
            const state = activeViewerState;
            if (! state) return;

            state.controller.abort();
            window.cancelAnimationFrame(state.imageProgressFrame);
            if (state.videoSafetyTimeout) {
                window.clearTimeout(state.videoSafetyTimeout);
            }
            if (state.mediaType === 'video' && state.media) {
                state.media.pause();
                if (resetVideo) {
                    try {
                        state.media.currentTime = 0;
                    } catch (error) {
                        // Some browsers do not allow seeking before metadata is ready.
                    }
                }
            }
            activeViewerState = undefined;
        };

        const initializeViewer = function (viewer) {
            if (! viewer) return;
            cleanupViewer(true);
            document.body.classList.add('story-overlay-open');
            document.body.classList.add('story-viewer-active');

            const state = {
                viewer,
                controller: new AbortController(),
                mediaType: viewer.dataset.storyMediaType,
                media: viewer.querySelector('[data-story-media]'),
                progress: viewer.querySelector('[data-story-progress-fill]'),
                loading: viewer.querySelector('[data-story-loading]'),
                error: viewer.querySelector('[data-story-error]'),
                playButton: viewer.querySelector('[data-story-play]'),
                muteButton: viewer.querySelector('[data-story-mute]'),
                imageElapsed: 0,
                imageStartedAt: 0,
                imagePaused: false,
                imageProgressFrame: 0,
                videoSafetyTimeout: null,
                ready: false,
                hasError: false,
            };
            activeViewerState = state;
            setViewerProgress(state, 0);

            // Preload adjacent stories
            const nextId = viewer.dataset.storyNextId;
            const prevId = viewer.dataset.storyPrevId;
            if (nextId) preloadStoryMedia(nextId);
            if (prevId) preloadStoryMedia(prevId);

            if (! state.media) {
                // Unavailable media fallback: display error message and auto-advance after 3s
                if (state.loading) state.loading.hidden = true;
                if (state.error) state.error.hidden = false;
                window.setTimeout(function () {
                    if (activeViewerState === state) {
                        if (nextId) {
                            navigateToStory(nextId);
                        } else {
                            closeViewer();
                        }
                    }
                }, 3000);
                return;
            }

            const listenerOptions = { signal: state.controller.signal };
            state.media.addEventListener('error', function () {
                showViewerError(state);
            }, listenerOptions);

            if (state.mediaType === 'image') {
                state.media.addEventListener('load', function () {
                    showViewerReady(state);
                }, listenerOptions);
                document.addEventListener('visibilitychange', function () {
                    if (document.hidden) {
                        pauseImageProgress(state);
                    } else {
                        resumeImageProgress(state);
                    }
                }, listenerOptions);

                if (state.media.complete) {
                    if (state.media.naturalWidth > 0) {
                        showViewerReady(state);
                    } else {
                        showViewerError(state);
                    }
                }

                return;
            }

            // Video Story Logic
            state.media.muted = globalStoryMuted;

            const updateProgress = function () {
                if (! Number.isFinite(state.media.duration) || state.media.duration <= 0) return;
                setViewerProgress(state, (state.media.currentTime / state.media.duration) * 100);
            };
            const markVideoReady = function () {
                showViewerReady(state);
                updateVideoControls(state);
            };

            state.media.addEventListener('loadedmetadata', function () {
                markVideoReady();
                updateProgress();
            }, listenerOptions);
            state.media.addEventListener('canplay', markVideoReady, listenerOptions);
            state.media.addEventListener('timeupdate', updateProgress, listenerOptions);
            state.media.addEventListener('play', function () {
                updateVideoControls(state);
            }, listenerOptions);
            state.media.addEventListener('pause', function () {
                updateVideoControls(state);
            }, listenerOptions);
            state.media.addEventListener('ended', function () {
                setViewerProgress(state, 100);
                updateVideoControls(state);
                if (state.videoSafetyTimeout) window.clearTimeout(state.videoSafetyTimeout);
                const nextId = state.viewer?.dataset.storyNextId;
                navigateToStory(nextId);
            }, listenerOptions);

            state.muteButton?.addEventListener('click', function (e) {
                e.stopPropagation();
                globalStoryMuted = ! state.media.muted;
                state.media.muted = globalStoryMuted;
                updateVideoControls(state);
            }, listenerOptions);

            updateVideoControls(state);
            if (state.media.readyState >= 1) {
                markVideoReady();
                updateProgress();
            }

            state.media.play().catch(function () {
                updateVideoControls(state);
            });

            // Video safety fallback timer (max 15s) in case video stalls or fails to trigger ended
            state.videoSafetyTimeout = window.setTimeout(function () {
                if (activeViewerState === state) {
                    const nextId = state.viewer?.dataset.storyNextId;
                    navigateToStory(nextId);
                }
            }, 15000);
        };

        const closeViewer = function () {
            const viewer = document.querySelector('[data-story-viewer]');
            if (! viewer || viewer.dataset.storyClosing === 'true') return;

            viewer.dataset.storyClosing = 'true';
            cleanupViewer(true);
            globalStoryMuted = true;

            if (viewer.dataset.storyStandalone === 'true') {
                document.body.classList.remove('story-overlay-open');
                document.body.classList.remove('story-viewer-active');
                window.location.assign(viewer.dataset.storyCloseUrl);
                return;
            }

            viewer.classList.remove('is-open');
            window.setTimeout(function () {
                viewer.remove();
                if (! modal || modal.hidden) {
                    document.body.classList.remove('story-overlay-open');
                    document.body.classList.remove('story-viewer-active');
                }
                lastFocused?.focus?.();
            }, 210);
        };

        const closeViewersModal = function () {
            const modalEl = document.querySelector('[data-story-viewers-modal]');
            if (! modalEl || modalEl.dataset.storyClosing === 'true') return;

            modalEl.dataset.storyClosing = 'true';
            modalEl.classList.remove('is-open');
            window.setTimeout(function () {
                modalEl.remove();
                if (! modal || modal.hidden) {
                    if (! document.querySelector('[data-story-viewer]')) {
                        document.body.classList.remove('story-overlay-open');
                    }
                }
                lastFocused?.focus?.();
            }, 180);
        };

        const openViewersModal = async function (storyId, triggerElement) {
            const mount = document.querySelector('[data-story-viewer-mount]') || document.querySelector('[data-story-viewer]') || document.body;
            if (! storyId || ! mount) return;
            lastFocused = triggerElement || document.activeElement;

            try {
                const response = await fetch('/member/stories/' + storyId + '/viewers', {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (! response.ok || ! data.success) {
                    if (response.status === 403 && typeof showStoryToast === 'function') {
                        showStoryToast(storyId, data.message || 'Only the story owner can view story viewers.', true);
                    }
                    return;
                }

                closeViewersModal();
                const tempWrap = document.createElement('div');
                tempWrap.innerHTML = data.html;
                const modalEl = tempWrap.firstElementChild;
                if (! modalEl) return;

                mount.appendChild(modalEl);
                document.body.classList.add('story-overlay-open');
                refreshIcons();

                window.requestAnimationFrame(function () {
                    modalEl.classList.add('is-open');
                    modalEl.querySelector('.story-modal__close')?.focus();
                });
            } catch (error) {
                // Ignore error quietly or show toast
            }
        };

        const updateStoryViewCount = function (storyId, newCount) {
            if (storyId === undefined || newCount === undefined) return;
            const elements = document.querySelectorAll('[data-story-views-count="' + storyId + '"]');
            elements.forEach(function (el) {
                if (el.closest('.story__views-badge--owner') || el.closest('.story-viewer__seen-by')) {
                    el.textContent = 'Seen by ' + newCount;
                } else {
                    el.textContent = newCount;
                }
            });
        };

        const closeReactorsModal = function () {
            const modalEl = document.querySelector('[data-story-reactors-modal]');
            if (! modalEl || modalEl.dataset.storyClosing === 'true') return;

            modalEl.dataset.storyClosing = 'true';
            modalEl.classList.remove('is-open');
            window.setTimeout(function () {
                modalEl.remove();
                if (! modal || modal.hidden) {
                    if (! document.querySelector('[data-story-viewer]')) {
                        document.body.classList.remove('story-overlay-open');
                    }
                }
                lastFocused?.focus?.();
            }, 180);
        };

        const openReactorsModal = async function (storyId, triggerElement) {
            const mount = document.querySelector('[data-story-viewer-mount]') || document.querySelector('[data-story-viewer]') || document.body;
            if (! storyId || ! mount) return;
            lastFocused = triggerElement || document.activeElement;

            try {
                const response = await fetch('/member/stories/' + storyId + '/reactors', {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (! response.ok || ! data.success) {
                    throw new Error('Reactions unavailable.');
                }

                closeReactorsModal();
                const tempWrap = document.createElement('div');
                tempWrap.innerHTML = data.html;
                const modalEl = tempWrap.firstElementChild;
                if (! modalEl) return;

                mount.appendChild(modalEl);
                document.body.classList.add('story-overlay-open');
                refreshIcons();

                window.requestAnimationFrame(function () {
                    modalEl.classList.add('is-open');
                    modalEl.querySelector('.story-modal__close')?.focus();
                });
            } catch (error) {
                // Ignore quietly
            }
        };

        const updateStoryReactionUI = function (data) {
            if (! data || ! data.story_id) return;

            const storyId = data.story_id;
            const likeBtn = document.querySelector('[data-story-like-btn="' + storyId + '"]');
            const likeIcon = document.querySelector('[data-story-like-icon="' + storyId + '"]');
            const likeLabel = document.querySelector('[data-story-like-label="' + storyId + '"]');
            const reactionsCountEl = document.querySelector('[data-story-reactions-count="' + storyId + '"]');
            const topReactionsWrap = document.querySelector('[data-story-top-reactions="' + storyId + '"]');
            const picker = document.querySelector('[data-story-reaction-picker="' + storyId + '"]');

            if (likeBtn) {
                likeBtn.classList.toggle('is-active', Boolean(data.has_liked || data.user_reaction));
            }

            if (likeIcon) {
                likeIcon.replaceChildren();
                if (data.user_reaction_emoji) {
                    likeIcon.textContent = data.user_reaction_emoji;
                } else {
                    const iconElement = document.createElement('i');
                    iconElement.dataset.lucide = 'thumbs-up';
                    iconElement.setAttribute('aria-hidden', 'true');
                    likeIcon.appendChild(iconElement);
                    refreshIcons();
                }
            }

            if (likeLabel) {
                likeLabel.textContent = data.user_reaction_label || (data.has_liked ? 'Liked' : 'Like');
            }

            if (reactionsCountEl && data.reactions_count !== undefined) {
                reactionsCountEl.textContent = data.reactions_count;
            }

            if (topReactionsWrap && Array.isArray(data.top_reactions)) {
                topReactionsWrap.replaceChildren();
                data.top_reactions.forEach(function (emoji) {
                    const i = document.createElement('i');
                    i.textContent = emoji;
                    topReactionsWrap.appendChild(i);
                });
            }

            if (picker) {
                const pickerBtns = picker.querySelectorAll('[data-story-react-btn]');
                pickerBtns.forEach(function (btn) {
                    btn.classList.toggle('is-active', btn.dataset.storyReactBtn === data.user_reaction);
                });
                picker.hidden = true;
            }
        };

        const toggleLikeStory = async function (storyId) {
            if (! storyId) return;

            try {
                const response = await fetch('/member/stories/' + storyId + '/like', {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    updateStoryReactionUI(data);
                }
            } catch (err) {
                // Ignore
            }
        };

        const sendStoryReaction = async function (storyId, reactionType) {
            if (! storyId || ! reactionType) return;

            try {
                const formData = new FormData();
                formData.append('reaction', reactionType);

                const response = await fetch('/member/stories/' + storyId + '/react', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();
                if (response.ok && data.success) {
                    updateStoryReactionUI(data);
                }
            } catch (err) {
                // Ignore
            }
        };

        const closeRepliesModal = function () {
            const modalEl = document.querySelector('[data-story-replies-modal]');
            if (! modalEl || modalEl.dataset.storyClosing === 'true') return;

            modalEl.dataset.storyClosing = 'true';
            modalEl.classList.remove('is-open');
            window.setTimeout(function () {
                modalEl.remove();
                if (! modal || modal.hidden) {
                    if (! document.querySelector('[data-story-viewer]')) {
                        document.body.classList.remove('story-overlay-open');
                    }
                }
                lastFocused?.focus?.();
            }, 180);
        };

        const openRepliesModal = async function (storyId, triggerElement) {
            const mount = document.querySelector('[data-story-viewer-mount]') || document.querySelector('[data-story-viewer]') || document.body;
            if (! storyId || ! mount) return;
            lastFocused = triggerElement || document.activeElement;

            try {
                const response = await fetch('/member/stories/' + storyId + '/replies', {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (! response.ok || ! data.success) {
                    throw new Error('Replies unavailable.');
                }

                closeRepliesModal();
                const tempWrap = document.createElement('div');
                tempWrap.innerHTML = data.html;
                const modalEl = tempWrap.firstElementChild;
                if (! modalEl) return;

                mount.appendChild(modalEl);
                document.body.classList.add('story-overlay-open');
                refreshIcons();

                document.querySelector('[data-story-unread-badge="' + storyId + '"]')?.remove();

                window.requestAnimationFrame(function () {
                    modalEl.classList.add('is-open');
                    modalEl.querySelector('.story-modal__close')?.focus();
                });
            } catch (error) {
                // Ignore quietly
            }
        };

        const showStoryToast = function (storyId, message, isError) {
            const toast = document.querySelector('[data-story-toast="' + storyId + '"]');
            if (! toast) return;

            toast.textContent = message;
            toast.classList.toggle('is-error', Boolean(isError));
            toast.hidden = false;

            window.requestAnimationFrame(function () {
                toast.classList.add('is-visible');
            });

            window.setTimeout(function () {
                toast.classList.remove('is-visible');
                window.setTimeout(function () {
                    toast.hidden = true;
                }, 200);
            }, 2500);
        };

        const sendStoryReply = async function (form) {
            if (! form) return;

            const storyId = form.dataset.storyReplyForm;
            const input = form.querySelector('[data-story-reply-input="' + storyId + '"]');
            const submitBtn = form.querySelector('button[type="submit"]');

            if (! input || ! input.value.trim()) return;

            if (submitBtn) submitBtn.disabled = true;

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

                if (response.ok && data.success) {
                    input.value = '';
                    showStoryToast(storyId, data.message || 'Your reply has been sent.', false);
                } else {
                    const message = data.errors?.message?.[0] || data.message || 'We could not send your reply.';
                    showStoryToast(storyId, message, true);
                }
            } catch (err) {
                showStoryToast(storyId, 'We could not send your reply. Try again.', true);
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        };

        const deleteStoryReply = async function (replyId, deleteBtn) {
            if (! replyId) return;

            try {
                const response = await fetch('/member/story-replies/' + replyId, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    const item = document.querySelector('[data-story-reply-id="' + replyId + '"]');
                    if (item) {
                        item.style.opacity = '0';
                        item.style.transform = 'translateY(-8px)';
                        window.setTimeout(function () {
                            item.remove();
                            if (data.story_id && data.replies_count !== undefined) {
                                const countEl = document.querySelector('[data-story-replies-count="' + data.story_id + '"]');
                                if (countEl) countEl.textContent = data.replies_count;
                            }
                        }, 180);
                    }
                }
            } catch (err) {
                // Ignore
            }
        };

        document.addEventListener('submit', function (event) {
            const replyForm = event.target.closest('[data-story-reply-form]');
            if (replyForm) {
                event.preventDefault();
                sendStoryReply(replyForm);
            }
        });

        let holdTimer = null;
        let isHolding = false;
        let touchStartX = 0;
        let touchStartY = 0;

        const startHold = function (e) {
            const viewer = e.target.closest('[data-story-viewer]');
            if (! viewer || e.target.closest('button, form, a, input')) return;
            holdTimer = window.setTimeout(function () {
                isHolding = true;
                viewer.classList.add('is-holding');
                if (activeViewerState) {
                    if (activeViewerState.mediaType === 'image') {
                        pauseImageProgress(activeViewerState);
                    } else if (activeViewerState.mediaType === 'video' && activeViewerState.media) {
                        activeViewerState.media.pause();
                    }
                }
            }, 150);
        };

        const endHold = function () {
            window.clearTimeout(holdTimer);
            if (isHolding) {
                isHolding = false;
                const viewer = document.querySelector('[data-story-viewer]');
                if (viewer) viewer.classList.remove('is-holding');
                if (activeViewerState) {
                    if (activeViewerState.mediaType === 'image') {
                        resumeImageProgress(activeViewerState);
                    } else if (activeViewerState.mediaType === 'video' && activeViewerState.media && activeViewerState.ready) {
                        activeViewerState.media.play().catch(function () {});
                    }
                }
            }
        };

        document.addEventListener('mousedown', startHold);
        document.addEventListener('mouseup', endHold);
        document.addEventListener('touchstart', function (e) {
            const viewer = e.target.closest('[data-story-viewer]');
            if (viewer) {
                touchStartX = e.touches[0].clientX;
                touchStartY = e.touches[0].clientY;
                startHold(e);
            }
        }, { passive: true });

        document.addEventListener('touchend', function (e) {
            endHold();
            const viewer = e.target.closest('[data-story-viewer]');
            if (! viewer || ! activeViewerState) return;

            const touchEndX = e.changedTouches[0].clientX;
            const touchEndY = e.changedTouches[0].clientY;
            const dx = touchEndX - touchStartX;
            const dy = touchEndY - touchStartY;

            if (Math.abs(dx) > 50 && Math.abs(dy) < 60) {
                if (dx < 0) {
                    const nextId = viewer.dataset.storyNextId;
                    navigateToStory(nextId);
                } else {
                    const prevId = viewer.dataset.storyPrevId;
                    navigateToStory(prevId);
                }
            }
        });

        const deleteStory = async function (storyId, deleteUrl) {
            if (! storyId || ! deleteUrl) return;

            try {
                const response = await fetch(deleteUrl, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    },
                });
                const data = await response.json();

                if (response.ok && data.success) {
                    closeViewer();

                    const storyCards = document.querySelectorAll('[data-story-id="' + storyId + '"]');
                    storyCards.forEach(function (card) {
                        card.remove();
                    });

                    showPageMessage(data.message || 'Your story has been deleted.', false);
                } else {
                    showPageMessage(data.message || 'Failed to delete story.', true);
                }
            } catch (err) {
                console.error('Delete story failed:', err);
                showPageMessage('An error occurred while deleting story.', true);
            }
        };

        document.addEventListener('click', async function (event) {
            const openButton = event.target.closest('[data-story-create-open]');
            const createClose = event.target.closest('[data-story-create-close]');
            const viewerClose = event.target.closest('[data-story-viewer-close]');
            const viewersOpen = event.target.closest('[data-story-viewers-open]');
            const viewersClose = event.target.closest('[data-story-viewers-close]');
            const reactorsOpen = event.target.closest('[data-story-reactors-open]');
            const reactorsClose = event.target.closest('[data-story-reactors-close]');
            const repliesOpen = event.target.closest('[data-story-replies-open]');
            const repliesClose = event.target.closest('[data-story-replies-close]');
            const replyDelete = event.target.closest('[data-story-reply-delete]');
            const navPrev = event.target.closest('[data-story-nav-prev]');
            const navNext = event.target.closest('[data-story-nav-next]');
            const likeBtn = event.target.closest('[data-story-like-btn]');
            const reactBtn = event.target.closest('[data-story-react-btn]');
            const storyLink = event.target.closest('[data-story-link]');
            const optionsToggle = event.target.closest('[data-story-options-toggle]');
            const optionsClose = event.target.closest('[data-story-options-close]');
            const deleteTrigger = event.target.closest('[data-story-delete-trigger]');
            const deleteCancel = event.target.closest('[data-story-delete-cancel]');
            const deleteConfirm = event.target.closest('[data-story-delete-confirm]');
            const copyLink = event.target.closest('[data-story-copy-link]');

            if (optionsToggle) {
                event.preventDefault();
                event.stopPropagation();
                const storyId = optionsToggle.dataset.storyOptionsToggle;
                const menu = document.querySelector('[data-story-options-menu="' + storyId + '"]');
                if (menu) {
                    menu.hidden = ! menu.hidden;
                    if (! menu.hidden && activeViewerState) {
                        pauseImageProgress(activeViewerState);
                    }
                }
                return;
            }

            if (optionsClose) {
                event.preventDefault();
                const storyId = optionsClose.dataset.storyOptionsClose;
                const menu = document.querySelector('[data-story-options-menu="' + storyId + '"]');
                if (menu) menu.hidden = true;
                if (activeViewerState) runImageProgress(activeViewerState);
                return;
            }

            if (deleteTrigger) {
                event.preventDefault();
                event.stopPropagation();
                const storyId = deleteTrigger.dataset.storyDeleteTrigger;
                const menu = document.querySelector('[data-story-options-menu="' + storyId + '"]');
                if (menu) menu.hidden = true;
                const modal = document.querySelector('[data-story-delete-modal="' + storyId + '"]');
                if (modal) {
                    modal.hidden = false;
                    window.requestAnimationFrame(function () {
                        modal.classList.add('is-open');
                    });
                }
                if (activeViewerState) pauseImageProgress(activeViewerState);
                return;
            }

            if (deleteCancel) {
                event.preventDefault();
                const storyId = deleteCancel.dataset.storyDeleteCancel;
                const modal = document.querySelector('[data-story-delete-modal="' + storyId + '"]');
                if (modal) {
                    modal.classList.remove('is-open');
                    window.setTimeout(function () {
                        modal.hidden = true;
                    }, 180);
                }
                if (activeViewerState) runImageProgress(activeViewerState);
                return;
            }

            if (deleteConfirm) {
                event.preventDefault();
                event.stopPropagation();
                const storyId = deleteConfirm.dataset.storyDeleteConfirm;
                const deleteUrl = deleteConfirm.dataset.storyDeleteUrl;
                deleteConfirm.disabled = true;
                deleteStory(storyId, deleteUrl);
                return;
            }

            if (copyLink) {
                event.preventDefault();
                const url = copyLink.dataset.storyCopyLink || window.location.href;
                navigator.clipboard.writeText(url).then(function () {
                    const viewer = copyLink.closest('[data-story-viewer]');
                    const storyId = viewer?.dataset.storyId;
                    if (storyId) showStoryToast(storyId, 'Story link copied to clipboard!', false);
                    const menu = copyLink.closest('[data-story-options-menu]');
                    if (menu) menu.hidden = true;
                });
                return;
            }

            if (openButton) {
                openCreateModal();
                return;
            }

            if (createClose) {
                closeCreateModal(true);
                return;
            }

            if (viewerClose) {
                event.preventDefault();
                closeViewer();
                return;
            }

            if (navPrev) {
                event.preventDefault();
                event.stopPropagation();
                const viewer = navPrev.closest('[data-story-viewer]');
                const prevId = viewer?.dataset.storyPrevId;
                navigateToStory(prevId);
                return;
            }

            if (navNext) {
                event.preventDefault();
                event.stopPropagation();
                const viewer = navNext.closest('[data-story-viewer]');
                const nextId = viewer?.dataset.storyNextId;
                navigateToStory(nextId);
                return;
            }

            if (viewersOpen) {
                event.preventDefault();
                event.stopPropagation();
                const storyId = viewersOpen.dataset.storyViewersOpen;
                openViewersModal(storyId, viewersOpen);
                return;
            }

            if (viewersClose) {
                event.preventDefault();
                closeViewersModal();
                return;
            }

            if (reactorsOpen) {
                event.preventDefault();
                event.stopPropagation();
                const storyId = reactorsOpen.dataset.storyReactorsOpen;
                openReactorsModal(storyId, reactorsOpen);
                return;
            }

            if (reactorsClose) {
                event.preventDefault();
                closeReactorsModal();
                return;
            }

            if (repliesOpen) {
                event.preventDefault();
                event.stopPropagation();
                const storyId = repliesOpen.dataset.storyRepliesOpen;
                openRepliesModal(storyId, repliesOpen);
                return;
            }

            if (repliesClose) {
                event.preventDefault();
                closeRepliesModal();
                return;
            }

            if (replyDelete) {
                event.preventDefault();
                event.stopPropagation();
                const replyId = replyDelete.dataset.storyReplyDelete;
                deleteStoryReply(replyId, replyDelete);
                return;
            }

            if (reactBtn) {
                event.preventDefault();
                event.stopPropagation();
                const storyId = reactBtn.dataset.storyId;
                const reactionType = reactBtn.dataset.storyReactBtn;
                sendStoryReaction(storyId, reactionType);
                return;
            }

            if (likeBtn) {
                event.preventDefault();
                event.stopPropagation();
                const storyId = likeBtn.dataset.storyId;
                toggleLikeStory(storyId);
                return;
            }

            if (! storyLink || ! viewerMount) return;

            event.preventDefault();
            lastFocused = storyLink;

            try {
                const response = await fetch(storyLink.href, {
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });
                const data = await response.json();

                if (! response.ok || ! data.success) {
                    throw new Error('Story unavailable.');
                }

                if (data.story_id && data.views_count !== undefined) {
                    updateStoryViewCount(data.story_id, data.views_count);
                }

                cleanupViewer(true);
                viewerMount.innerHTML = data.html;
                document.body.classList.add('story-overlay-open');
                refreshIcons();
                const viewer = viewerMount.querySelector('[data-story-viewer]');
                initializeViewer(viewer);
                window.requestAnimationFrame(function () {
                    viewer?.classList.add('is-open');
                    viewer?.querySelector('.story-viewer__close')?.focus();
                });
            } catch (error) {
                window.location.assign(storyLink.href);
            }
        });

        let pickerHideTimeout = null;

        const showReactionPicker = function (storyId) {
            if (! storyId) return;
            window.clearTimeout(pickerHideTimeout);
            const picker = document.querySelector('[data-story-reaction-picker="' + storyId + '"]');
            if (picker) {
                picker.hidden = false;
            }
        };

        const scheduleHideReactionPicker = function (storyId) {
            window.clearTimeout(pickerHideTimeout);
            pickerHideTimeout = window.setTimeout(function () {
                const picker = document.querySelector('[data-story-reaction-picker="' + storyId + '"]');
                if (picker) {
                    const isHoveringLike = document.querySelector('[data-story-like-btn="' + storyId + '"]:hover');
                    const isHoveringPicker = picker.matches(':hover');
                    if (! isHoveringLike && ! isHoveringPicker) {
                        picker.hidden = true;
                    }
                }
            }, 250);
        };

        document.addEventListener('mouseover', function (event) {
            const likeBtn = event.target.closest('[data-story-like-btn]');
            const picker = event.target.closest('.story-reaction-picker');

            if (likeBtn) {
                showReactionPicker(likeBtn.dataset.storyId);
            } else if (picker) {
                window.clearTimeout(pickerHideTimeout);
            }
        });

        document.addEventListener('mouseout', function (event) {
            const likeBtn = event.target.closest('[data-story-like-btn]');
            const picker = event.target.closest('.story-reaction-picker');

            if (likeBtn) {
                const related = event.relatedTarget;
                if (! related || (! related.closest?.('[data-story-like-btn]') && ! related.closest?.('.story-reaction-picker'))) {
                    scheduleHideReactionPicker(likeBtn.dataset.storyId);
                }
            } else if (picker) {
                const related = event.relatedTarget;
                if (! related || (! related.closest?.('[data-story-like-btn]') && ! related.closest?.('.story-reaction-picker'))) {
                    const storyId = picker.dataset.storyReactionPicker;
                    scheduleHideReactionPicker(storyId);
                }
            }
        });

        document.addEventListener('click', function (event) {
            if (! event.target.closest('[data-story-like-btn]') && ! event.target.closest('.story-reaction-picker')) {
                const visiblePickers = document.querySelectorAll('.story-reaction-picker:not([hidden])');
                visiblePickers.forEach(function (p) {
                    p.hidden = true;
                });
            }
        });

        document.addEventListener('keydown', function (event) {
            if (document.querySelector('[data-story-replies-modal]')) {
                if (event.key === 'Escape') closeRepliesModal();
                return;
            }
            if (document.querySelector('[data-story-reactors-modal]')) {
                if (event.key === 'Escape') closeReactorsModal();
                return;
            }
            if (document.querySelector('[data-story-viewers-modal]')) {
                if (event.key === 'Escape') closeViewersModal();
                return;
            }

            const viewer = document.querySelector('[data-story-viewer]');
            if (! viewer) {
                if (event.key === 'Escape' && modal && ! modal.hidden) {
                    closeCreateModal(true);
                }
                return;
            }

            if (event.key === 'Escape') {
                closeViewer();
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                const nextId = viewer.dataset.storyNextId;
                navigateToStory(nextId);
            } else if (event.key === 'ArrowLeft') {
                event.preventDefault();
                const prevId = viewer.dataset.storyPrevId;
                navigateToStory(prevId);
            }
        });

        // Drag and drop support
        const dropzone = form?.querySelector('[data-story-dropzone]');
        if (dropzone) {
            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('is-dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('is-dragover');
                }, false);
            });

            dropzone.addEventListener('drop', function (e) {
                const dt = e.dataTransfer;
                const files = dt?.files;
                if (files && files.length && media) {
                    media.files = files;
                    media.dispatchEvent(new Event('change', { bubbles: true }));
                }
            }, false);
        }

        // Character counter support
        const captionInput = form?.querySelector('[data-story-caption-input]');
        const charCounter = form?.querySelector('[data-story-char-counter]');
        if (captionInput && charCounter) {
            captionInput.addEventListener('input', function () {
                charCounter.textContent = captionInput.value.length + ' / 500';
            });
        }

        media?.addEventListener('change', function () {
            const file = media.files?.[0];
            clearPreview(false);

            if (! file || ! preview || ! previewMedia) return;

            const extension = file.name.split('.').pop()?.toLowerCase() || '';
            const isImage = ['image/jpeg', 'image/png', 'image/webp'].includes(file.type);
            const isVideo = ['video/mp4', 'video/webm', 'video/quicktime'].includes(file.type)
                || (['', 'application/octet-stream'].includes(file.type) && ['mp4', 'mov'].includes(extension));

            if (! isImage && ! isVideo) {
                clearPreview(true);
                showFeedback('Upload a JPG, PNG, WebP, MP4, WebM, or MOV file.', true);
                return;
            }

            if ((isImage && file.size > 5 * 1024 * 1024) || (isVideo && file.size > 50 * 1024 * 1024)) {
                clearPreview(true);
                showFeedback(isImage ? 'The image must not be larger than 5 MB.' : 'The video must not be larger than 50 MB.', true);
                return;
            }

            previewUrl = URL.createObjectURL(file);
            const element = document.createElement(isImage ? 'img' : 'video');
            element.src = previewUrl;

            if (isImage) {
                element.alt = 'Selected Story image preview';
            } else {
                element.controls = true;
                element.preload = 'metadata';
                element.playsInline = true;
                element.setAttribute('aria-label', 'Selected Story video preview');
            }

            previewMedia.appendChild(element);
            preview.hidden = false;
            const uploadLabel = form?.querySelector('.story-upload');
            if (uploadLabel) uploadLabel.hidden = true;
            showFeedback('', false);
            updateSubmit();
        });

        form?.querySelector('[data-story-media-remove]')?.addEventListener('click', function () {
            clearPreview(true);
            media?.focus();
        });

        form?.addEventListener('submit', async function (event) {
            event.preventDefault();

            if (isSubmitting || ! media?.files?.length) {
                showFeedback('Please select an image or video.', true);
                return;
            }

            isSubmitting = true;
            updateSubmit();
            
            const submitIcon = submit?.querySelector('[data-story-submit-icon]');
            if (submitIcon) {
                submitIcon.dataset.lucide = 'loader-circle';
                submitIcon.classList.add('fb-spinner');
                refreshIcons();
            }

            if (submitLabel) submitLabel.textContent = 'Sharing…';
            showFeedback('Sharing your Story…', false);

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
                const contentType = response.headers.get('content-type') || '';
                const isJson = contentType.includes('application/json');
                const data = isJson ? await response.json() : null;

                if (response.status === 413) {
                    throw new Error('The video is larger than the server upload limit.');
                }

                if (response.status === 419) {
                    throw new Error('Your session expired. Refresh the page and try again.');
                }

                if (response.status === 401 || (response.redirected && response.url.includes('/member/login'))) {
                    throw new Error('Your session expired. Refresh the page and try again.');
                }

                if (response.status === 422 && data) {
                    const validationMessage = data.errors?.media?.[0]
                        || data.errors?.caption?.[0]
                        || Object.values(data.errors || {}).flat()[0];

                    throw new Error(validationMessage || 'Please check the Story details and try again.');
                }

                if (! response.ok || ! isJson || ! data?.success) {
                    throw new Error(data?.message || 'We could not upload the Story. Please try again.');
                }

                const row = document.querySelector('[data-story-row]');
                if (row && data.member_id) {
                    const existingMemberCard = row.querySelector('[data-story-member-id="' + data.member_id + '"]');
                    if (! existingMemberCard) {
                        const createCard = row.querySelector('[data-story-create-open]');
                        createCard?.insertAdjacentHTML('afterend', data.html);
                    }
                }

                const nextButton = row?.querySelector('[data-stories-next]');
                if (nextButton) {
                    nextButton.hidden = row.querySelectorAll('[data-story-link]').length <= 4;
                }

                isSubmitting = false;
                closeCreateModal(true);
                showPageMessage(data.message, false);
                refreshIcons();
            } catch (error) {
                const message = error instanceof TypeError || error instanceof SyntaxError
                    ? 'We could not upload the Story. Please try again.'
                    : error.message;
                showFeedback(message || 'We could not upload the Story. Please try again.', true);
                if (typeof window.showBizPostToast === 'function') {
                    window.showBizPostToast(message || 'We could not upload the Story. Please try again.', true);
                }
            } finally {
                isSubmitting = false;
                if (submitIcon) {
                    submitIcon.dataset.lucide = 'send';
                    submitIcon.classList.remove('fb-spinner');
                    refreshIcons();
                }
                updateSubmit();
                if (submitLabel) submitLabel.textContent = 'Share Story';
            }
        });

        document.querySelector('[data-stories-next]')?.addEventListener('click', function () {
            const row = document.querySelector('[data-story-row]');
            row?.scrollBy({ left: Math.max(260, row.clientWidth * .72), behavior: 'smooth' });
        });

        updateSubmit();
        if (modal?.dataset.openOnErrors === 'true') openCreateModal();
        const initialViewer = document.querySelector('[data-story-viewer]');
        if (initialViewer) {
            document.body.classList.add('story-overlay-open');
            initializeViewer(initialViewer);
            window.requestAnimationFrame(function () {
                initialViewer.classList.add('is-open');
                initialViewer.querySelector('.story-viewer__close')?.focus();
            });
        }
    });
}());
