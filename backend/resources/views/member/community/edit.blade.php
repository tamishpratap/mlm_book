@extends('member.layouts.app')

@section('title', 'Edit Community - ' . $community->name)
@section('body-class', 'page-community-edit')

@section('content')
@php
    $hasLogo = $community->logo && file_exists(public_path($community->logo));
    $hasCover = $community->cover_photo && file_exists(public_path($community->cover_photo));
    $initials = collect(preg_split('/\s+/', trim($community->name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
        ->implode('') ?: 'C';
@endphp

<div class="community-page community-create-page">
    <header class="community-header">
        <div class="community-header__info">
            <h1><i data-lucide="edit-3" aria-hidden="true"></i> Edit Community</h1>
            <p>Update settings, rules, appearance, and guidelines for {{ $community->name }}.</p>
        </div>
        <div class="community-header__actions">
            <a href="{{ route('member.community.show', $community) }}" class="member-button member-button--secondary">
                <i data-lucide="arrow-left" aria-hidden="true"></i> Back to Community
            </a>
        </div>
    </header>

    <div class="row g-4 align-items-start">
        <!-- Form Section Column -->
        <div class="col-lg-7 col-xl-8">
            <form
                method="POST"
                action="{{ route('member.community.update', $community) }}"
                enctype="multipart/form-data"
                class="card community-create-card"
            >
                @csrf
                @method('PUT')

                <div class="fb-section-card__header" style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--color-border-soft, #e2e8f0);">
                    <div>
                        <h2 class="community-form-section-title">
                            <i data-lucide="sliders" aria-hidden="true"></i> Community Settings
                        </h2>
                        <p style="font-size: 0.875rem; color: var(--color-text-muted, #64748b); margin: 4px 0 0 0;">Modify community metadata, privacy options, and branding.</p>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 20px;">
                    <!-- Community Name -->
                    <div class="form-group">
                        <label for="comm_name" class="community-form-label">
                            Community Name <span class="required-star">*</span>
                        </label>
                        <input
                            type="text"
                            id="comm_name"
                            name="name"
                            value="{{ old('name', $community->name) }}"
                            class="form-control community-form-control"
                            placeholder="e.g. Laravel Developers Network"
                            required
                            maxlength="255"
                            data-preview-target="name"
                        >
                        @error('name')
                            <span style="color: #ef4444; font-size: 0.8rem; margin-top: 6px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div class="form-group">
                        <label for="comm_desc" class="community-form-label">
                            Description
                        </label>
                        <textarea
                            id="comm_desc"
                            name="description"
                            rows="4"
                            class="form-control community-form-control"
                            style="height: auto; padding: 12px 16px;"
                            placeholder="Describe the purpose, topic, or goals of your community..."
                            maxlength="2000"
                            data-preview-target="desc"
                        >{{ old('description', $community->description) }}</textarea>
                        @error('description')
                            <span style="color: #ef4444; font-size: 0.8rem; margin-top: 6px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Category & Visibility Row -->
                    <div class="row g-3">
                        <div class="col-md-6 form-group">
                            <label for="comm_cat" class="community-form-label">
                                Category <span class="required-star">*</span>
                            </label>
                            <select id="comm_cat" name="category" class="form-select community-form-select" required data-preview-target="category">
                                @foreach ($categories as $cat)
                                    <option value="{{ $cat }}" {{ old('category', $community->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                                @endforeach
                            </select>
                            @error('category')
                                <span style="color: #ef4444; font-size: 0.8rem; margin-top: 6px; display: block;">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="comm_vis" class="community-form-label">
                                Privacy / Visibility <span class="required-star">*</span>
                            </label>
                            <select id="comm_vis" name="visibility" class="form-select community-form-select" required data-preview-target="visibility">
                                @foreach ($visibilities as $key => $label)
                                    <option value="{{ $key }}" {{ old('visibility', $community->visibility) === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('visibility')
                                <span style="color: #ef4444; font-size: 0.8rem; margin-top: 6px; display: block;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- File Upload Dropzones Row -->
                    <div class="row g-3">
                        <!-- Logo Avatar Dropzone -->
                        <div class="col-md-6 form-group">
                            <label class="community-form-label">
                                Replace Logo Avatar
                            </label>
                            <div class="community-upload-dropzone">
                                <input
                                    type="file"
                                    id="comm_logo"
                                    name="logo"
                                    accept="image/jpeg,image/png,image/webp"
                                >
                                <div class="community-upload-icon">
                                    <i data-lucide="image" aria-hidden="true"></i>
                                </div>
                                <span class="community-upload-prompt">Click to replace Avatar Logo</span>
                                <span class="community-upload-subtext">JPG, PNG, WebP (Max 2MB)</span>
                                <div class="community-upload-file-status" id="logo_file_status" @if(! $hasLogo) hidden @endif>
                                    <i data-lucide="check" aria-hidden="true"></i> <span id="logo_filename">{{ $hasLogo ? 'Current Logo Saved' : 'File selected' }}</span>
                                </div>
                            </div>
                            @error('logo')
                                <span style="color: #ef4444; font-size: 0.8rem; margin-top: 6px; display: block;">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Cover Banner Dropzone -->
                        <div class="col-md-6 form-group">
                            <label class="community-form-label">
                                Replace Cover Photo Banner
                            </label>
                            <div class="community-upload-dropzone">
                                <input
                                    type="file"
                                    id="comm_cover"
                                    name="cover_photo"
                                    accept="image/jpeg,image/png,image/webp"
                                >
                                <div class="community-upload-icon">
                                    <i data-lucide="landscape" aria-hidden="true"></i>
                                </div>
                                <span class="community-upload-prompt">Click to replace Cover Banner</span>
                                <span class="community-upload-subtext">JPG, PNG, WebP (Max 5MB)</span>
                                <div class="community-upload-file-status" id="cover_file_status" @if(! $hasCover) hidden @endif>
                                    <i data-lucide="check" aria-hidden="true"></i> <span id="cover_filename">{{ $hasCover ? 'Current Cover Saved' : 'File selected' }}</span>
                                </div>
                            </div>
                            @error('cover_photo')
                                <span style="color: #ef4444; font-size: 0.8rem; margin-top: 6px; display: block;">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <!-- Rules & Guidelines -->
                    <div class="form-group">
                        <label for="comm_rules" class="community-form-label">
                            Community Rules & Guidelines
                        </label>
                        <textarea
                            id="comm_rules"
                            name="rules"
                            rows="4"
                            class="form-control community-form-control"
                            style="height: auto; padding: 12px 16px;"
                            placeholder="1. Be respectful to all members&#10;2. No spam or self-promotion..."
                            maxlength="3000"
                        >{{ old('rules', $community->rules) }}</textarea>
                        @error('rules')
                            <span style="color: #ef4444; font-size: 0.8rem; margin-top: 6px; display: block;">{{ $message }}</span>
                        @enderror
                    </div>

                    <!-- Submit Action Bar -->
                    <div style="margin-top: 12px; padding-top: 16px; border-top: 1px solid var(--color-border-soft, #e2e8f0); display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                        <a href="{{ route('member.community.show', $community) }}" class="member-button member-button--secondary">Cancel</a>
                        <button type="submit" class="member-button member-button--primary">
                            <i data-lucide="check-circle" aria-hidden="true"></i> Save Changes
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Live Preview Card Column -->
        <div class="col-lg-5 col-xl-4 community-preview-sticky">
            <div class="community-preview-title">
                <i data-lucide="eye" style="width: 18px; height: 18px; color: var(--color-primary, #20c875);"></i>
                <span>Live Card Preview</span>
            </div>

            <article class="community-card community-preview-card">
                <div class="community-card__cover" id="preview_cover_bg">
                    <img id="preview_cover_img" src="{{ $hasCover ? asset($community->cover_photo) : '' }}" alt="Cover Preview" style="{{ $hasCover ? 'display: block;' : 'display: none;' }} width: 100%; height: 100%; object-fit: cover;">
                    <div class="community-card__badges">
                        <span class="community-badge community-badge--category" data-preview-bind="category">{{ $community->category }}</span>
                        <span class="community-badge community-badge--visibility" data-preview-bind="visibility">
                            <i data-lucide="{{ $community->visibility === 'public' ? 'globe' : 'lock' }}" aria-hidden="true"></i> {{ ucfirst(str_replace('_', ' ', $community->visibility)) }}
                        </span>
                    </div>
                </div>

                <div class="community-card__body">
                    <div class="community-card__avatar">
                        <img id="preview_logo_img" src="{{ $hasLogo ? asset($community->logo) : '' }}" alt="Logo Preview" style="{{ $hasLogo ? 'display: block;' : 'display: none;' }} width: 100%; height: 100%; object-fit: cover;">
                        <div class="community-avatar-initials" id="preview_initials_wrap" data-preview-bind="initials" style="{{ $hasLogo ? 'display: none;' : '' }}">{{ $initials }}</div>
                    </div>

                    <h3 class="community-card__title" data-preview-bind="name">{{ $community->name }}</h3>

                    <div class="community-card__owner">
                        <span>Created by</span>
                        <strong>{{ $community->owner->name ?? 'Member' }}</strong>
                    </div>

                    <p class="community-card__description" data-preview-bind="desc">
                        {{ $community->description ?: 'Your community description will appear here...' }}
                    </p>

                    <div class="community-card__footer">
                        <div class="community-card__meta">
                            <span>
                                <i data-lucide="users" aria-hidden="true"></i> {{ number_format($community->member_count ?? 1) }} {{ \Illuminate\Support\Str::plural('member', $community->member_count ?? 1) }}
                            </span>
                        </div>

                        <button type="button" class="community-btn--disabled" disabled>
                            <i data-lucide="user-plus" aria-hidden="true"></i> Join
                        </button>
                    </div>
                </div>
            </article>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const nameInput = document.getElementById('comm_name');
    const descInput = document.getElementById('comm_desc');
    const catInput = document.getElementById('comm_cat');
    const visInput = document.getElementById('comm_vis');
    const logoInput = document.getElementById('comm_logo');
    const coverInput = document.getElementById('comm_cover');

    const previewName = document.querySelector('[data-preview-bind="name"]');
    const previewDesc = document.querySelector('[data-preview-bind="desc"]');
    const previewCat = document.querySelector('[data-preview-bind="category"]');
    const previewVis = document.querySelector('[data-preview-bind="visibility"]');
    const previewInitials = document.querySelector('[data-preview-bind="initials"]');
    const previewInitialsWrap = document.getElementById('preview_initials_wrap');
    const previewLogoImg = document.getElementById('preview_logo_img');
    const previewCoverImg = document.getElementById('preview_cover_img');

    const logoStatus = document.getElementById('logo_file_status');
    const logoFilename = document.getElementById('logo_filename');
    const coverStatus = document.getElementById('cover_file_status');
    const coverFilename = document.getElementById('cover_filename');

    function updatePreview() {
        if (nameInput && previewName) {
            const val = nameInput.value.trim();
            previewName.textContent = val || 'Your Community Name';
            if (previewInitials) {
                const initials = val.split(/\s+/).slice(0, 2).map(s => s.charAt(0).toUpperCase()).join('') || 'C';
                previewInitials.textContent = initials;
            }
        }
        if (descInput && previewDesc) {
            previewDesc.textContent = descInput.value.trim() || 'Your community description will appear here...';
        }
        if (catInput && previewCat) {
            previewCat.textContent = catInput.value;
        }
        if (visInput && previewVis) {
            const visVal = visInput.value;
            const visText = visVal.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
            previewVis.innerHTML = `<i data-lucide="${visVal === 'public' ? 'globe' : 'lock'}" aria-hidden="true"></i> ${visText}`;
            if (window.lucide) { window.lucide.createIcons(); }
        }
    }

    if (logoInput) {
        logoInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (logoStatus && logoFilename) {
                    logoFilename.textContent = file.name;
                    logoStatus.hidden = false;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (previewLogoImg) {
                        previewLogoImg.src = e.target.result;
                        previewLogoImg.style.display = 'block';
                    }
                    if (previewInitialsWrap) {
                        previewInitialsWrap.style.display = 'none';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    if (coverInput) {
        coverInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                if (coverStatus && coverFilename) {
                    coverFilename.textContent = file.name;
                    coverStatus.hidden = false;
                }
                const reader = new FileReader();
                reader.onload = function (e) {
                    if (previewCoverImg) {
                        previewCoverImg.src = e.target.result;
                        previewCoverImg.style.display = 'block';
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    }

    [nameInput, descInput, catInput, visInput].forEach(el => {
        if (el) {
            el.addEventListener('input', updatePreview);
            el.addEventListener('change', updatePreview);
        }
    });

    updatePreview();
});
</script>
@endpush
@endsection
