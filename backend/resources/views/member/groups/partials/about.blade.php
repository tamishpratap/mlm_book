<div class="group-about-box card">
    <h2>About {{ $group->name }}</h2>
    <p>{!! nl2br(e($group->description ?? 'No description provided for this community.')) !!}</p>

    <div class="group-meta-list" style="margin-top: 20px;">
        <div class="product-meta-item">
            <span><i data-lucide="{{ $group->privacy === 'public' ? 'globe' : 'lock' }}" aria-hidden="true"></i> Privacy</span>
            <strong>{{ ucfirst($group->privacy) }} Community</strong>
        </div>
        <div class="product-meta-item">
            <span><i data-lucide="tag" aria-hidden="true"></i> Category</span>
            <strong>{{ $group->category }}</strong>
        </div>
        <div class="product-meta-item">
            <span><i data-lucide="users" aria-hidden="true"></i> Members</span>
            <strong>{{ $group->members_count }} Active Members</strong>
        </div>
        <div class="product-meta-item">
            <span><i data-lucide="calendar" aria-hidden="true"></i> Created</span>
            <strong>{{ $group->created_at->format('M d, Y') }}</strong>
        </div>
    </div>

    @if ($group->rules)
        <div class="group-rules-box" style="margin-top: 24px;">
            <h3><i data-lucide="shield" aria-hidden="true"></i> Community Rules</h3>
            <p>{!! nl2br(e($group->rules)) !!}</p>
        </div>
    @endif
</div>
