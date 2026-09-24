<div class="group-media-tab card">
    <h2>Community Photos & Videos ({{ $mediaPosts->count() }})</h2>

    <div class="marketplace-grid">
        @forelse ($mediaPosts as $post)
            <div class="product-card card">
                <div class="product-card__image">
                    @if ($post->media_type === 'video')
                        <video src="{{ asset($post->media_path) }}" controls preload="metadata"></video>
                    @else
                        <img src="{{ asset($post->media_path) }}" alt="Community Media">
                    @endif
                </div>
            </div>
        @empty
            <p style="color: #667085; text-align: center; padding: 20px; grid-column: 1 / -1;">No media shared in this community yet.</p>
        @endforelse
    </div>
</div>
