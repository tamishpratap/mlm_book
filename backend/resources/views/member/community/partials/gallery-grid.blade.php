<div class="community-gallery-grid">
    @forelse ($items as $item)
        @php
            $author = $item->member;
            $hasAuthorPhoto = $author && $author->profile_photo
                && str_starts_with($author->profile_photo, 'uploads/profile/')
                && file_exists(public_path($author->profile_photo));
            $authorAvatar = $hasAuthorPhoto ? asset($author->profile_photo) : null;
            $mediaUrl = asset($item->media_path);
            $isVideo = $item->media_type === 'video';
        @endphp

        <div
            class="community-gallery-item"
            data-lightbox-media="{{ $mediaUrl }}"
            data-lightbox-type="{{ $item->media_type }}"
            data-lightbox-author="{{ $author->name ?? 'Member' }}"
            data-lightbox-date="{{ $item->created_at->format('M d, Y') }}"
            data-lightbox-body="{{ $item->body ?? '' }}"
        >
            @if ($isVideo)
                <video src="{{ $mediaUrl }}" preload="metadata" muted></video>
                <div class="community-gallery-item__play">
                    <i data-lucide="play" aria-hidden="true"></i>
                </div>
            @else
                <img src="{{ $mediaUrl }}" alt="Community photo" loading="lazy">
            @endif

            <div class="community-gallery-item__overlay">
                <div style="display: flex; align-items: center; gap: 8px;">
                    @if ($authorAvatar)
                        <img src="{{ $authorAvatar }}" alt="{{ $author->name }}" style="width: 24px; height: 24px; border-radius: 50%; object-fit: cover;">
                    @endif
                    <span style="font-size: 12px; font-weight: 700; color: #ffffff;">{{ $author->name ?? 'Member' }}</span>
                </div>
                <span style="font-size: 11px; color: rgba(255,255,255,0.8);">{{ $item->created_at->diffForHumans() }}</span>
            </div>
        </div>
    @empty
        <div class="fb-empty-state" style="grid-column: 1 / -1; width: 100%;">
            <div class="fb-empty-state__icon">
                <i data-lucide="image" aria-hidden="true"></i>
            </div>
            <h3>No Media Uploads Found</h3>
            <p>No photos or videos have been shared in this community yet.</p>
        </div>
    @endforelse
</div>

@if (method_exists($items, 'hasPages') && $items->hasPages())
    <div class="pagination-wrapper">
        {{ $items->links('member.search.pagination') }}
    </div>
@endif
