@php
    $businessPage = $businessPage ?? $page ?? null;
    $hasLogo = $businessPage && $businessPage->logo && file_exists(public_path($businessPage->logo));
    $hasCover = $businessPage && $businessPage->cover_photo && file_exists(public_path($businessPage->cover_photo));
    $currentMemberId = auth('member')->id();
    $isOwner = $businessPage ? $businessPage->isOwner($currentMemberId) : false;
@endphp

<article class="biz-card">
    <div class="biz-card__cover">
        @if ($hasCover)
            <img src="{{ asset($businessPage->cover_photo) }}" alt="{{ $businessPage->page_name }} cover" loading="lazy">
        @endif
    </div>

    <div class="biz-card__body">
        <div class="biz-card__avatar">
            @if ($hasLogo)
                <img src="{{ asset($businessPage->logo) }}" alt="{{ $businessPage->page_name }} logo" loading="lazy">
            @else
                <span>{{ $businessPage->initials }}</span>
            @endif
        </div>

        <div class="biz-card__header">
            <h3 class="biz-card__title">
                <a href="{{ route('member.business-pages.show', $businessPage) }}">{{ $businessPage->page_name }}</a>
                @if ($businessPage->is_verified)
                    <i data-lucide="badge-check" style="width: 16px; height: 16px; color: #20c875;" title="Verified Business"></i>
                @endif
            </h3>
            <span class="biz-card__username">{{ '@' . $businessPage->page_username }}</span>
        </div>

        <div class="biz-card__badges">
            <span class="biz-badge biz-badge--category">
                <i data-lucide="tag" style="width: 12px; height: 12px;"></i> {{ $businessPage->category }}
            </span>
            <span class="biz-badge biz-badge--visibility">
                @if ($businessPage->visibility === 'private')
                    <i data-lucide="lock" style="width: 12px; height: 12px;"></i> Private
                @elseif ($businessPage->visibility === 'draft')
                    <i data-lucide="file-text" style="width: 12px; height: 12px;"></i> Draft
                @else
                    <i data-lucide="globe" style="width: 12px; height: 12px;"></i> Public
                @endif
            </span>
        </div>

        @if ($businessPage->description)
            <p class="biz-card__description">{{ $businessPage->description }}</p>
        @endif

        <div class="biz-card__meta">
            @if ($businessPage->formatted_location)
                <div class="biz-card__meta-item">
                    <i data-lucide="map-pin" style="width: 13px; height: 13px;"></i>
                    <span class="text-truncate">{{ $businessPage->formatted_location }}</span>
                </div>
            @endif
            <div class="biz-card__meta-item">
                <i data-lucide="calendar" style="width: 13px; height: 13px;"></i>
                <span>Created {{ $businessPage->created_at->format('M d, Y') }}</span>
            </div>
        </div>

        <div class="biz-card__actions">
            <a href="{{ route('member.business-pages.show', $businessPage) }}" class="member-button member-button--primary biz-card__btn-view">
                <i data-lucide="eye" aria-hidden="true"></i> <span>View</span>
            </a>

            @if ($isOwner)
                <a href="{{ route('member.business-pages.edit', $businessPage) }}" class="member-button member-button--secondary biz-card__action-btn biz-card__action-btn--edit" title="Edit Page" aria-label="Edit {{ $businessPage->page_name }}">
                    <i data-lucide="edit" aria-hidden="true"></i>
                </a>

                <form method="POST" action="{{ route('member.business-pages.destroy', $businessPage) }}" onsubmit="return confirm('Are you sure you want to delete this Business Page?');" class="biz-card__delete-form">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="member-button member-button--danger biz-card__action-btn biz-card__action-btn--delete" title="Delete Page" aria-label="Delete {{ $businessPage->page_name }}">
                        <i data-lucide="trash-2" aria-hidden="true"></i>
                    </button>
                </form>
            @endif
        </div>
    </div>
</article>
