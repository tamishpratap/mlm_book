@php
    $hasLogo = $businessPage->logo && file_exists(public_path($businessPage->logo));
@endphp

<div class="card composer-card" style="margin-bottom: 20px;">
    <form id="bizPostComposerForm" method="POST" action="{{ route('member.business-pages.posts.store', $businessPage) }}" enctype="multipart/form-data">
        @csrf
        <div style="display: flex; gap: 14px; align-items: flex-start;">
            <div style="width: 44px; height: 44px; border-radius: 12px; overflow: hidden; background: #edf3ff; flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-weight: 700; color: #4f7df3;">
                @if ($hasLogo)
                    <img src="{{ asset($businessPage->logo) }}" alt="{{ $businessPage->page_name }}" style="width: 100%; height: 100%; object-fit: cover;">
                @else
                    <span>{{ $businessPage->initials }}</span>
                @endif
            </div>

            <div style="flex: 1; min-width: 0; width: 100%;">
                <textarea
                    name="body"
                    id="bizPostComposerBody"
                    rows="3"
                    class="biz-search-input"
                    style="width: 100%; height: auto; min-height: 90px; padding: 12px; font-size: 14px; border-radius: 12px; box-sizing: border-box;"
                    placeholder="Create a post for {{ $businessPage->page_name }}..."
                    maxlength="5000"
                ></textarea>

                <!-- Media Preview Box -->
                <div id="bizMediaPreviewBox" style="margin-top: 10px; display: none; position: relative;">
                    <button type="button" onclick="clearBizMediaInput()" style="position: absolute; top: 8px; right: 8px; background: rgba(0,0,0,0.6); color: #fff; border: 0; border-radius: 50%; width: 24px; height: 24px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
                        <i data-lucide="x" style="width: 14px; height: 14px;"></i>
                    </button>
                    <div id="bizMediaPreviewContent"></div>
                </div>

                <!-- Post Options Row -->
                <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-top: 12px; flex-wrap: wrap;">
                    <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                        <!-- Media Picker -->
                        <label class="member-button member-button--secondary" style="cursor: pointer; height: 46px; padding-inline: 22px; display: inline-flex; align-items: center; gap: 10px;">
                            <i data-lucide="image" aria-hidden="true" style="color: #20c875;"></i>
                            <span>Photo / Video</span>
                            <input type="file" name="media" id="bizMediaInput" accept="image/jpeg,image/png,image/webp,video/mp4,video/webm,video/quicktime" style="display: none;" onchange="handleBizMediaChange(this)">
                        </label>

                        <!-- Announcement Toggle -->
                        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #687386; cursor: pointer;">
                            <input type="checkbox" name="is_announcement" value="1" style="border-radius: 4px;">
                            <span><i data-lucide="megaphone" style="width: 14px; height: 14px; color: #f7b940;"></i> Announcement</span>
                        </label>

                        <!-- Featured Toggle -->
                        <label style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; color: #687386; cursor: pointer;">
                            <input type="checkbox" name="is_featured" value="1" style="border-radius: 4px;">
                            <span><i data-lucide="sparkles" style="width: 14px; height: 14px; color: #8a2be2;"></i> Featured</span>
                        </label>
                    </div>

                    <button type="submit" id="bizPostSubmitBtn" class="member-button member-button--primary">
                        <i data-lucide="send" aria-hidden="true"></i> Publish Post
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function handleBizMediaChange(input) {
    const box = document.getElementById('bizMediaPreviewBox');
    const content = document.getElementById('bizMediaPreviewContent');
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const reader = new FileReader();
        reader.onload = function(e) {
            box.style.display = 'block';
            if (file.type.startsWith('image/')) {
                content.innerHTML = `<img src="${e.target.result}" style="max-height: 200px; border-radius: 12px; object-fit: cover;">`;
            } else if (file.type.startsWith('video/')) {
                content.innerHTML = `<video src="${e.target.result}" controls style="max-height: 200px; border-radius: 12px; width: 100%;"></video>`;
            }
        };
        reader.readAsDataURL(file);
    }
}

function clearBizMediaInput() {
    const input = document.getElementById('bizMediaInput');
    input.value = '';
    document.getElementById('bizMediaPreviewBox').style.display = 'none';
    document.getElementById('bizMediaPreviewContent').innerHTML = '';
}

document.getElementById('bizPostComposerForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    const form = this;
    const btn = document.getElementById('bizPostSubmitBtn');
    const originalText = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<i data-lucide="loader-circle"></i> Publishing...';
    
    const formData = new FormData(form);
    
    fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Accept': 'application/json'
        }
    })
    .then(res => res.json())
    .then(data => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        if (data.success) {
            form.reset();
            clearBizMediaInput();
            
            const emptyState = document.getElementById('bizTimelineEmptyState');
            if (emptyState) {
                emptyState.remove();
            }
            
            const feed = document.getElementById('bizTimelineFeed');
            if (feed) {
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = data.html;
                feed.insertBefore(tempDiv.firstElementChild, feed.firstElementChild);
                if (window.lucide) { window.lucide.createIcons(); }
            }
        } else {
            alert(data.message || 'Failed to publish post.');
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = originalText;
        alert('An error occurred while publishing the post.');
    });
});
</script>
