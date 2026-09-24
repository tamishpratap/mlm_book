@extends('member.layouts.app')

@section('title', 'Create Community')
@section('body-class', 'page-community-create')

@section('content')
<div class="community-page community-create-page">
    <header class="community-header">
        <div class="community-header__info">
            <h1><i data-lucide="plus-circle" aria-hidden="true"></i> Create New Community</h1>
            <p>Set up a new space for members to connect, learn, and grow together.</p>
        </div>
        <div class="community-header__actions">
            <a href="{{ route('member.community.index') }}" class="member-button member-button--secondary">
                <i data-lucide="arrow-left" aria-hidden="true"></i> Back to Communities
            </a>
        </div>
    </header>

    <div class="row g-4 align-items-start">
        <!-- Form Section Column -->
        <div class="col-lg-7 col-xl-8">
            <form
                method="POST"
                action="{{ route('member.community.store') }}"
                enctype="multipart/form-data"
                class="card community-create-card"
            >
                @csrf

                <div class="fb-section-card__header" style="margin-bottom: 24px; padding-bottom: 16px; border-bottom: 1px solid var(--color-border-soft, #e2e8f0);">
                    <div>
                        <h2 class="community-form-section-title">
                            <i data-lucide="info" aria-hidden="true"></i> Community Details
                        </h2>
                        <p style="font-size: 0.875rem; color: var(--color-text-muted, #64748b); margin: 4px 0 0 0;">Provide key details and custom branding for your new community.</p>
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
                            value="{{ old('name') }}"
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
                        >{{ old('description') }}</textarea>
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
                                    <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
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
                                    <option value="{{ $key }}" {{ old('visibility') === $key ? 'selected' : '' }}>{{ $label }}</option>
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
                                Community Logo Avatar
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
                                <span class="community-upload-prompt">Click to upload Avatar Logo</span>
                                <span class="community-upload-subtext">JPG, PNG, WebP (Max 5MB)</span>
                                <div class="community-upload-file-status" id="logo_file_status" hidden>
                                    <i data-lucide="check" aria-hidden="true"></i> <span id="logo_filename">File selected</span>
                                </div>
                            </div>
                            @error('logo')
                                <span style="color: #ef4444; font-size: 0.8rem; margin-top: 6px; display: block;">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Cover Banner Dropzone -->
                        <div class="col-md-6 form-group">
                            <label class="community-form-label">
                                Cover Photo Banner
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
                                <span class="community-upload-prompt">Click to upload Cover Banner</span>
                                <span class="community-upload-subtext">JPG, PNG, WebP (Max 5MB)</span>
                                <div class="community-upload-file-status" id="cover_file_status" hidden>
                                    <i data-lucide="check" aria-hidden="true"></i> <span id="cover_filename">File selected</span>
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
                            rows="3"
                            class="form-control community-form-control"
                            style="height: auto; padding: 12px 16px;"
                            placeholder="1. Be respectful to all members&#10;2. No spam or self-promotion..."
                            maxlength="3000"
                        >{{ old('rules') }}</textarea>
                    </div>

                    <!-- Submit Action Bar -->
                    <div style="margin-top: 12px; padding-top: 16px; border-top: 1px solid var(--color-border-soft, #e2e8f0); display: flex; align-items: center; justify-content: flex-end; gap: 12px;">
                        <a href="{{ route('member.community.index') }}" class="member-button member-button--secondary">Cancel</a>
                        <button type="submit" class="member-button member-button--primary">
                            <i data-lucide="check-circle" aria-hidden="true"></i> Create Community
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
                    <img id="preview_cover_img" src="" alt="Cover Preview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                    <div class="community-card__badges">
                        <span class="community-badge community-badge--category" data-preview-bind="category">Technology</span>
                        <span class="community-badge community-badge--visibility" data-preview-bind="visibility">
                            <i data-lucide="globe" aria-hidden="true"></i> Public
                        </span>
                    </div>
                </div>

                <div class="community-card__body">
                    <div class="community-card__avatar">
                        <img id="preview_logo_img" src="" alt="Logo Preview" style="display: none; width: 100%; height: 100%; object-fit: cover;">
                        <div class="community-avatar-initials" id="preview_initials_wrap" data-preview-bind="initials">C</div>
                    </div>

                    <h3 class="community-card__title" data-preview-bind="name">Your Community Name</h3>

                    <div class="community-card__owner">
                        <span>Created by</span>
                        <strong>{{ auth('member')->user()->name }}</strong>
                    </div>

                    <p class="community-card__description" data-preview-bind="desc">
                        Your community description will appear here...
                    </p>

                    <div class="community-card__footer">
                        <div class="community-card__meta">
                            <span>
                                <i data-lucide="users" aria-hidden="true"></i> 1 member
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
