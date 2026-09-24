@extends('member.layouts.app')

@section('title', 'Create Business Page')

@section('content')
<div class="biz-page">
    <header class="biz-header">
        <div class="biz-header__info">
            <h1><i data-lucide="plus-circle" aria-hidden="true"></i> Create Business Page</h1>
            <p>Establish your official enterprise or personal brand page on MLM Book.</p>
        </div>
        <div class="biz-header__actions">
            <a href="{{ route('member.business-pages.index') }}" class="member-button member-button--secondary">
                <i data-lucide="arrow-left" aria-hidden="true"></i> Back to Business Pages
            </a>
        </div>
    </header>

    <div class="biz-create-grid">
        <!-- Form Section -->
        <form
            method="POST"
            action="{{ route('member.business-pages.store') }}"
            enctype="multipart/form-data"
            style="background: #ffffff; border: 1px solid #e7ecf4; border-radius: 18px; padding: 24px; display: flex; flex-direction: column; gap: 20px; box-shadow: 0 2px 8px rgba(34, 49, 78, 0.035);"
        >
            @csrf

            <div style="border-bottom: 1px solid #e7ecf4; padding-bottom: 12px; margin-bottom: 4px;">
                <h2 style="font-size: 17px; font-weight: 700; color: #1d2738; margin: 0 0 4px 0;">Page Information</h2>
                <p style="font-size: 13px; color: #687386; margin: 0;">Fill in your core business details.</p>
            </div>

            <!-- Business Name & Username Row -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="page_name" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                        Business Name <span style="color: red;">*</span>
                    </label>
                    <input
                        type="text"
                        id="page_name"
                        name="page_name"
                        value="{{ old('page_name') }}"
                        class="biz-search-input @error('page_name') is-invalid @enderror"
                        placeholder="e.g. Apex Global Solutions"
                        required
                        minlength="3"
                        maxlength="255"
                        oninput="updatePreviewName(this.value)"
                        style="@error('page_name') border-color: #ef4444; @enderror"
                    >
                    @error('page_name')
                        <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="page_username" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                        Username <span style="font-weight: 400; color: #98a2b3;">(Optional - Auto Generated)</span>
                    </label>
                    <input
                        type="text"
                        id="page_username"
                        name="page_username"
                        value="{{ old('page_username') }}"
                        class="biz-search-input @error('page_username') is-invalid @enderror"
                        placeholder="e.g. apex_global"
                        maxlength="100"
                        oninput="updatePreviewUsername(this.value)"
                        style="@error('page_username') border-color: #ef4444; @enderror"
                    >
                    @error('page_username')
                        <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Category & Visibility Row -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="category" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                        Category <span style="color: red;">*</span>
                    </label>
                    <select id="category" name="category" class="biz-filter-select @error('category') is-invalid @enderror" style="width: 100%; @error('category') border-color: #ef4444; @enderror" required onchange="updatePreviewCategory(this.value)">
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category')
                        <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="visibility" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                        Visibility <span style="color: red;">*</span>
                    </label>
                    <select id="visibility" name="visibility" class="biz-filter-select @error('visibility') is-invalid @enderror" style="width: 100%; @error('visibility') border-color: #ef4444; @enderror" required>
                        @foreach ($visibilities as $key => $label)
                            <option value="{{ $key }}" {{ old('visibility') === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('visibility')
                        <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <!-- Description -->
            <div class="form-group">
                <label for="description" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                    Description <span style="color: red;">*</span> <span style="font-size: 12px; font-weight: 400; color: #64748b;">(Minimum 20 characters)</span>
                </label>
                <textarea
                    id="description"
                    name="description"
                    rows="3"
                    class="biz-search-input @error('description') is-invalid @enderror"
                    style="height: auto; padding: 12px 16px; @error('description') border-color: #ef4444; @enderror"
                    placeholder="Describe your products, services, or organization (min 20 characters)..."
                    required
                    minlength="20"
                    maxlength="2000"
                    oninput="updatePreviewDesc(this.value)"
                >{{ old('description') }}</textarea>
                @error('description')
                    <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span>
                @enderror
            </div>

            <!-- Contact Information Header -->
            <div style="border-bottom: 1px solid #e7ecf4; padding-bottom: 8px; margin-top: 8px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1d2738; margin: 0;">Contact & Location Details</h3>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="website" style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Website URL <span style="font-weight: 400; color: #98a2b3;">(Optional)</span></label>
                    <input
                        type="text"
                        id="website"
                        name="website"
                        value="{{ old('website') }}"
                        class="biz-search-input @error('website') is-invalid @enderror"
                        placeholder="https://example.com or www.example.com"
                        style="@error('website') border-color: #ef4444; @enderror"
                    >
                    @error('website') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="email" style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Business Email <span style="color: red;">*</span></label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        class="biz-search-input @error('email') is-invalid @enderror"
                        placeholder="contact@example.com"
                        required
                        style="@error('email') border-color: #ef4444; @enderror"
                    >
                    @error('email') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="phone_number" style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Phone Number <span style="color: red;">*</span></label>
                    <div style="display: flex; gap: 8px;">
                        <select
                            name="phone_country_code"
                            id="phone_country_code"
                            class="biz-filter-select"
                            style="flex: 0 0 110px; padding: 6px 8px; font-size: 13px;"
                        >
                            @foreach ($dialingCodes as $dCode)
                                <option value="{{ $dCode['code'] }}" {{ old('phone_country_code', '+91') === $dCode['code'] ? 'selected' : '' }}>
                                    {{ $dCode['flag'] }} {{ $dCode['code'] }}
                                </option>
                            @endforeach
                        </select>
                        <input
                            type="text"
                            id="phone_number"
                            name="phone_number"
                            value="{{ old('phone_number') }}"
                            class="biz-search-input @error('phone') is-invalid @enderror"
                            placeholder="9876543210"
                            required
                            pattern="[0-9]+"
                            oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                            style="flex: 1; @error('phone') border-color: #ef4444; @enderror"
                        >
                    </div>
                    @error('phone') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="address" style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Street Address <span style="font-weight: 400; color: #98a2b3;">(Optional)</span></label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="{{ old('address') }}"
                        class="biz-search-input @error('address') is-invalid @enderror"
                        placeholder="123 Business Way"
                        style="@error('address') border-color: #ef4444; @enderror"
                    >
                    @error('address') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="city" style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">City <span style="font-weight: 400; color: #98a2b3;">(Optional)</span></label>
                    <input
                        type="text"
                        id="city"
                        name="city"
                        value="{{ old('city') }}"
                        class="biz-search-input @error('city') is-invalid @enderror"
                        placeholder="New York"
                        style="@error('city') border-color: #ef4444; @enderror"
                    >
                    @error('city') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="state" style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">State / Region <span style="font-weight: 400; color: #98a2b3;">(Optional)</span></label>
                    <input
                        type="text"
                        id="state"
                        name="state"
                        value="{{ old('state') }}"
                        class="biz-search-input @error('state') is-invalid @enderror"
                        placeholder="NY"
                        style="@error('state') border-color: #ef4444; @enderror"
                    >
                    @error('state') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="country" style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Country <span style="color: red;">*</span></label>
                    <select
                        id="country"
                        name="country"
                        class="biz-filter-select @error('country') is-invalid @enderror"
                        style="width: 100%; @error('country') border-color: #ef4444; @enderror"
                        required
                    >
                        <option value="" disabled {{ old('country') ? '' : 'selected' }}>Select Country</option>
                        @foreach ($countries as $c)
                            <option value="{{ $c }}" {{ old('country') === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                    @error('country') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Media Upload Header -->
            <div style="border-bottom: 1px solid #e7ecf4; padding-bottom: 8px; margin-top: 8px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1d2738; margin: 0;">Branding & Media</h3>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="logo" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                        Business Logo <span style="font-weight: 400; color: #64748b;">(JPG, PNG, WEBP - Max 2MB)</span>
                    </label>
                    <input
                        type="file"
                        id="logo"
                        name="logo"
                        accept="image/jpeg,image/png,image/webp"
                        class="biz-search-input @error('logo') is-invalid @enderror"
                        style="padding: 8px 12px; font-size: 12px; @error('logo') border-color: #ef4444; @enderror"
                    >
                    @error('logo') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="cover_photo" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                        Cover Banner <span style="font-weight: 400; color: #64748b;">(JPG, PNG, WEBP - Max 5MB)</span>
                    </label>
                    <input
                        type="file"
                        id="cover_photo"
                        name="cover_photo"
                        accept="image/jpeg,image/png,image/webp"
                        class="biz-search-input @error('cover_photo') is-invalid @enderror"
                        style="padding: 8px 12px; font-size: 12px; @error('cover_photo') border-color: #ef4444; @enderror"
                    >
                    @error('cover_photo') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Submit Buttons -->
            <div style="display: flex; gap: 12px; margin-top: 12px; justify-content: flex-end;">
                <a href="{{ route('member.business-pages.index') }}" class="member-button member-button--secondary">Cancel</a>
                <button type="submit" class="member-button member-button--primary">
                    <i data-lucide="check-circle" aria-hidden="true"></i> Create Business Page
                </button>
            </div>
        </form>

        <!-- Live Preview Sidebar Card -->
        <aside>
            <div style="position: sticky; top: 90px; display: flex; flex-direction: column; gap: 12px;">
                <h3 style="font-size: 14px; font-weight: 700; color: #687386; margin: 0; text-transform: uppercase; letter-spacing: 0.5px;">Live Card Preview</h3>
                <article class="biz-card">
                    <div class="biz-card__cover"></div>
                    <div class="biz-card__body">
                        <div class="biz-card__avatar">
                            <span id="previewInitials">BP</span>
                        </div>
                        <div class="biz-card__header">
                            <h3 class="biz-card__title" id="previewTitle">Business Name</h3>
                            <span class="biz-card__username" id="previewUsername">@username</span>
                        </div>
                        <div class="biz-card__badges">
                            <span class="biz-badge biz-badge--category" id="previewCategory">Technology</span>
                        </div>
                        <p class="biz-card__description" id="previewDesc">Description preview will appear here as you type...</p>
                    </div>
                </article>
            </div>
        </aside>
    </div>
</div>

<script>
function updatePreviewName(val) {
    document.getElementById('previewTitle').innerText = val.trim() || 'Business Name';
    const parts = val.trim().split(/\s+/).filter(Boolean).slice(0, 2);
    const initials = parts.map(p => p[0].toUpperCase()).join('') || 'BP';
    document.getElementById('previewInitials').innerText = initials;
}
function updatePreviewUsername(val) {
    document.getElementById('previewUsername').innerText = '@' + (val.trim() || 'username');
}
function updatePreviewCategory(val) {
    document.getElementById('previewCategory').innerText = val;
}
function updatePreviewDesc(val) {
    document.getElementById('previewDesc').innerText = val.trim() || 'Description preview will appear here as you type...';
}
</script>
@endsection
