/**
 * Watch Module JavaScript - MLM Book
 * Handles custom video player controls, viewport auto-play, and infinite scroll pagination.
 */
document.addEventListener('DOMContentLoaded', function () {
    initWatchVideoPlayers();
    initWatchAutoPlayObserver();
    initWatchInfiniteScroll();
});

/**
 * Initialize custom video player controls on all watch video elements.
 */
function initWatchVideoPlayers(scope) {
    const root = scope || document;
    const players = root.querySelectorAll('[data-watch-player]');

    players.forEach(function (player) {
        if (player.dataset.watchInitialized === 'true') return;
        player.dataset.watchInitialized = 'true';

        const video = player.querySelector('video');
        const overlayPlay = player.querySelector('[data-watch-overlay-play]');
        const playBtn = player.querySelector('[data-watch-play-btn]');
        const muteBtn = player.querySelector('[data-watch-mute-btn]');
        const volumeSlider = player.querySelector('[data-watch-volume-slider]');
        const progressFill = player.querySelector('[data-watch-progress-fill]');
        const progressBar = player.querySelector('[data-watch-progress-bar]');
        const timeDisplay = player.querySelector('[data-watch-time]');
        const fullscreenBtn = player.querySelector('[data-watch-fullscreen-btn]');

        if (!video) return;

        function togglePlay() {
            if (video.paused || video.ended) {
                // Pause other playing videos first
                document.querySelectorAll('video').forEach(function (v) {
                    if (v !== video && !v.paused) {
                        v.pause();
                        const p = v.closest('[data-watch-player]');
                        if (p) {
                            p.classList.remove('is-playing');
                            p.classList.add('is-paused');
                        }
                    }
                });

                video.play().then(function () {
                    player.classList.add('is-playing');
                    player.classList.remove('is-paused');
                    updatePlayIcons(true);
                }).catch(function (err) {
                    console.log('Video play error:', err);
                });
            } else {
                video.pause();
                player.classList.remove('is-playing');
                player.classList.add('is-paused');
                updatePlayIcons(false);
            }
        }

        function updatePlayIcons(isPlaying) {
            if (playBtn) {
                const icon = playBtn.querySelector('i');
                if (icon) {
                    icon.setAttribute('data-lucide', isPlaying ? 'pause' : 'play');
                    if (window.lucide) window.lucide.createIcons();
                }
            }
        }

        if (overlayPlay) {
            overlayPlay.addEventListener('click', function (e) {
                e.stopPropagation();
                togglePlay();
            });
        }

        if (playBtn) {
            playBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                togglePlay();
            });
        }

        video.addEventListener('click', function () {
            togglePlay();
        });

        // Time and Progress Updates
        video.addEventListener('timeupdate', function () {
            if (video.duration) {
                const percent = (video.currentTime / video.duration) * 100;
                if (progressFill) progressFill.style.width = percent + '%';
                if (timeDisplay) {
                    timeDisplay.textContent = formatTime(video.currentTime) + ' / ' + formatTime(video.duration);
                }
            }
        });

        video.addEventListener('ended', function () {
            player.classList.remove('is-playing');
            player.classList.add('is-paused');
            if (progressFill) progressFill.style.width = '100%';
            updatePlayIcons(false);
        });

        // Progress Bar Click Seeking
        if (progressBar) {
            progressBar.addEventListener('click', function (e) {
                const rect = progressBar.getBoundingClientRect();
                const pos = (e.clientX - rect.left) / rect.width;
                if (video.duration) {
                    video.currentTime = pos * video.duration;
                }
            });
        }

        // Mute & Volume
        if (muteBtn) {
            muteBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                video.muted = !video.muted;
                const icon = muteBtn.querySelector('i');
                if (icon) {
                    icon.setAttribute('data-lucide', video.muted ? 'volume-x' : 'volume-2');
                    if (window.lucide) window.lucide.createIcons();
                }
                if (volumeSlider) {
                    volumeSlider.value = video.muted ? 0 : (video.volume || 1);
                }
            });
        }

        if (volumeSlider) {
            volumeSlider.addEventListener('input', function (e) {
                const val = parseFloat(e.target.value);
                video.volume = val;
                video.muted = (val === 0);
                if (muteBtn) {
                    const icon = muteBtn.querySelector('i');
                    if (icon) {
                        icon.setAttribute('data-lucide', video.muted ? 'volume-x' : 'volume-2');
                        if (window.lucide) window.lucide.createIcons();
                    }
                }
            });
        }

        // Fullscreen
        if (fullscreenBtn) {
            fullscreenBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (player.requestFullscreen) {
                    player.requestFullscreen();
                } else if (player.webkitRequestFullscreen) {
                    player.webkitRequestFullscreen();
                } else if (video.requestFullscreen) {
                    video.requestFullscreen();
                }
            });
        }
    });
}

/**
 * Auto-play muted video when scrolled into viewport.
 */
function initWatchAutoPlayObserver() {
    if (!('IntersectionObserver' in window)) return;

    const observer = new IntersectionObserver(function (entries) {
        entries.forEach(function (entry) {
            const player = entry.target;
            const video = player.querySelector('video');
            if (!video) return;

            if (entry.isIntersecting) {
                // Auto play muted video if not user-paused
                if (video.paused && !player.dataset.userPaused) {
                    video.muted = true;
                    video.play().then(function () {
                        player.classList.add('is-playing');
                        player.classList.remove('is-paused');
                    }).catch(function () {});
                }
            } else {
                if (!video.paused) {
                    video.pause();
                    player.classList.remove('is-playing');
                    player.classList.add('is-paused');
                }
            }
        });
    }, { threshold: 0.6 });

    document.querySelectorAll('[data-watch-player]').forEach(function (player) {
        observer.observe(player);
    });
}

/**
 * Handles Watch infinite scroll feed pagination.
 */
function initWatchInfiniteScroll() {
    const feedContainer = document.querySelector('[data-watch-feed]');
    if (!feedContainer) return;

    let isLoading = false;

    window.addEventListener('scroll', function () {
        if (isLoading) return;

        const nextPageUrl = feedContainer.dataset.nextPage;
        if (!nextPageUrl) return;

        const scrollPosition = window.innerHeight + window.scrollY;
        const threshold = document.body.offsetHeight - 600;

        if (scrollPosition >= threshold) {
            loadNextWatchPage(feedContainer);
        }
    });
}

function loadNextWatchPage(feedContainer) {
    const nextPageUrl = feedContainer.dataset.nextPage;
    if (!nextPageUrl) return;

    const loader = document.querySelector('[data-watch-loader]');
    if (loader) loader.hidden = false;

    fetch(nextPageUrl, {
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    })
    .then(function (res) { return res.json(); })
    .then(function (data) {
        if (data.success && data.html) {
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data.html;

            const newCards = Array.from(tempDiv.children);
            newCards.forEach(function (card) {
                feedContainer.appendChild(card);
            });

            if (data.has_more) {
                const currentUrl = new URL(nextPageUrl, window.location.origin);
                currentUrl.searchParams.set('page', data.next_page);
                feedContainer.dataset.nextPage = currentUrl.toString();
            } else {
                delete feedContainer.dataset.nextPage;
            }

            initWatchVideoPlayers(feedContainer);
            if (window.lucide) window.lucide.createIcons();
        }
    })
    .catch(function (err) {
        console.error('Failed to load next watch page:', err);
    })
    .finally(function () {
        if (loader) loader.hidden = true;
    });
}

function formatTime(seconds) {
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return mins + ':' + (secs < 10 ? '0' : '') + secs;
}
