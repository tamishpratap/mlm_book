@extends('member.layouts.app')

@section('title', 'Edit ' . $businessPage->page_name)

@section('content')
<div class="biz-page">
    <header class="biz-header">
        <div class="biz-header__info">
            <h1><i data-lucide="edit-3" aria-hidden="true"></i> Edit {{ $businessPage->page_name }}</h1>
            <p>Update your business information, contact details, and branding.</p>
        </div>
        <div class="biz-header__actions">
            <a href="{{ route('member.business-pages.show', $businessPage) }}" class="member-button member-button--secondary">
                <i data-lucide="arrow-left" aria-hidden="true"></i> Back to Profile
            </a>
        </div>
    </header>

    <div style="max-width: 900px; margin: 0 auto; width: 100%;">
        <form
            method="POST"
            action="{{ route('member.business-pages.update', $businessPage) }}"
            enctype="multipart/form-data"
            style="background: #ffffff; border: 1px solid #e7ecf4; border-radius: 18px; padding: 28px; display: flex; flex-direction: column; gap: 20px; box-shadow: 0 2px 8px rgba(34, 49, 78, 0.035);"
        >
            @csrf
            @method('PUT')

            <div style="border-bottom: 1px solid #e7ecf4; padding-bottom: 12px;">
                <h2 style="font-size: 17px; font-weight: 700; color: #1d2738; margin: 0 0 4px 0;">Core Page Information</h2>
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
                        value="{{ old('page_name', $businessPage->page_name) }}"
                        class="biz-search-input @error('page_name') is-invalid @enderror"
                        required
                        minlength="3"
                        maxlength="255"
                        style="@error('page_name') border-color: #ef4444; @enderror"
                    >
                    @error('page_name') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="page_username" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                        Username <span style="color: red;">*</span>
                    </label>
                    <input
                        type="text"
                        id="page_username"
                        name="page_username"
                        value="{{ old('page_username', $businessPage->page_username) }}"
                        class="biz-search-input @error('page_username') is-invalid @enderror"
                        required
                        maxlength="100"
                        style="@error('page_username') border-color: #ef4444; @enderror"
                    >
                    @error('page_username') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>
            </div>

            <!-- Category & Visibility Row -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px;">
                <div class="form-group">
                    <label for="category" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                        Category <span style="color: red;">*</span>
                    </label>
                    <select id="category" name="category" class="biz-filter-select @error('category') is-invalid @enderror" style="width: 100%; @error('category') border-color: #ef4444; @enderror" required>
                        @foreach ($categories as $cat)
                            <option value="{{ $cat }}" {{ old('category', $businessPage->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                        @endforeach
                    </select>
                    @error('category') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="visibility" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                        Visibility <span style="color: red;">*</span>
                    </label>
                    <select id="visibility" name="visibility" class="biz-filter-select @error('visibility') is-invalid @enderror" style="width: 100%; @error('visibility') border-color: #ef4444; @enderror" required>
                        @foreach ($visibilities as $key => $label)
                            <option value="{{ $key }}" {{ old('visibility', $businessPage->visibility) === $key ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('visibility') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
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
                    rows="4"
                    class="biz-search-input @error('description') is-invalid @enderror"
                    style="height: auto; padding: 12px 16px; @error('description') border-color: #ef4444; @enderror"
                    required
                    minlength="20"
                    maxlength="2000"
                >{{ old('description', $businessPage->description) }}</textarea>
                @error('description') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
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
                        value="{{ old('website', $businessPage->website) }}"
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
                        value="{{ old('email', $businessPage->email) }}"
                        class="biz-search-input @error('email') is-invalid @enderror"
                        placeholder="contact@example.com"
                        required
                        style="@error('email') border-color: #ef4444; @enderror"
                    >
                    @error('email') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>

                @php
                    $rawPhone = $businessPage->phone ?? '';
                    $selectedCode = '+91';
                    $phoneDigits = '';
                    if (filled($rawPhone)) {
                        foreach ($dialingCodes as $d) {
                            if (str_starts_with($rawPhone, $d['code'])) {
                                $selectedCode = $d['code'];
                                $phoneDigits = substr($rawPhone, strlen($d['code']));
                                break;
                            }
                        }
                        if (empty($phoneDigits)) {
                            $phoneDigits = preg_replace('/[^0-9]/', '', $rawPhone);
                        }
                    }
                    $selectedCode = old('phone_country_code', $selectedCode);
                    $phoneDigits = old('phone_number', $phoneDigits);
                @endphp
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
                                <option value="{{ $dCode['code'] }}" {{ $selectedCode === $dCode['code'] ? 'selected' : '' }}>
                                    {{ $dCode['flag'] }} {{ $dCode['code'] }}
                                </option>
                            @endforeach
                        </select>
                        <input
                            type="text"
                            id="phone_number"
                            name="phone_number"
                            value="{{ $phoneDigits }}"
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
                    <label for="address" style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Street Address <span style="color: red;">*</span></label>
                    <input
                        type="text"
                        id="address"
                        name="address"
                        value="{{ old('address', $businessPage->address) }}"
                        class="biz-search-input @error('address') is-invalid @enderror"
                        placeholder="123 Business Way"
                        required
                        minlength="3"
                        style="@error('address') border-color: #ef4444; @enderror"
                    >
                    @error('address') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="city" style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">City <span style="color: red;">*</span></label>
                    <input
                        type="text"
                        id="city"
                        name="city"
                        value="{{ old('city', $businessPage->city) }}"
                        class="biz-search-input @error('city') is-invalid @enderror"
                        placeholder="New York"
                        required
                        minlength="2"
                        style="@error('city') border-color: #ef4444; @enderror"
                    >
                    @error('city') <span style="color: #ef4444; font-size: 12px; margin-top: 4px; display: block;">{{ $message }}</span> @enderror
                </div>
                <div class="form-group">
                    <label for="state" style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">State / Region <span style="color: red;">*</span></label>
                    <input
                        type="text"
                        id="state"
                        name="state"
                        value="{{ old('state', $businessPage->state) }}"
                        class="biz-search-input @error('state') is-invalid @enderror"
                        placeholder="NY"
                        required
                        minlength="2"
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
                        @foreach ($countries as $c)
                            <option value="{{ $c }}" {{ old('country', $businessPage->country) === $c ? 'selected' : '' }}>{{ $c }}</option>
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
                        Replace Business Logo <span style="font-weight: 400; color: #64748b;">(JPG, PNG, WEBP - Max 2MB)</span>
                    </label>
                    @if ($businessPage->logo_url)
                        <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                            <img src="{{ $businessPage->logo_url }}" alt="Current Logo" style="width: 44px; height: 44px; border-radius: 8px; object-fit: cover;">
                            <span style="font-size: 12px; color: #687386;">Current Logo</span>
                        </div>
                    @endif
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
                        Replace Cover Banner <span style="font-weight: 400; color: #64748b;">(JPG, PNG, WEBP - Max 5MB)</span>
                    </label>
                    @if ($businessPage->cover_url)
                        <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px;">
                            <img src="{{ $businessPage->cover_url }}" alt="Current Cover" style="width: 80px; height: 44px; border-radius: 8px; object-fit: cover;">
                            <span style="font-size: 12px; color: #687386;">Current Cover Banner</span>
                        </div>
                    @endif
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

            <!-- SEO & Open Graph Settings Header -->
            <div style="border-bottom: 1px solid #e7ecf4; padding-bottom: 8px; margin-top: 8px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1d2738; margin: 0;">SEO & Search Engine Optimization</h3>
            </div>

            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div class="form-group">
                    <label for="seo_title" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                        SEO Meta Title (Optional)
                    </label>
                    <input type="text" id="seo_title" name="seo_title" value="{{ old('seo_title', $businessPage->seo_title) }}" class="biz-search-input" placeholder="e.g. Acme Corp - Leading Technology Provider in NY" maxlength="255">
                </div>

                <div class="form-group">
                    <label for="meta_description" style="font-size: 13.5px; font-weight: 700; margin-bottom: 6px; display: block;">
                        SEO Meta Description (Optional)
                    </label>
                    <textarea id="meta_description" name="meta_description" rows="2" class="biz-search-input" style="height: auto; padding: 10px;" placeholder="Brief summary of your business for search engine results..." maxlength="1000">{{ old('meta_description', $businessPage->meta_description) }}</textarea>
                </div>
            </div>

            <!-- Social Links Header -->
            <div style="border-bottom: 1px solid #e7ecf4; padding-bottom: 8px; margin-top: 8px;">
                <h3 style="font-size: 15px; font-weight: 700; color: #1d2738; margin: 0;">Social Media Profiles</h3>
            </div>

            @php $socials = $businessPage->social_links ?? []; @endphp
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px;">
                <div class="form-group">
                    <label style="font-size: 12.5px; font-weight: 600;">Facebook URL</label>
                    <input type="url" name="facebook" value="{{ old('facebook', $socials['facebook'] ?? '') }}" class="biz-search-input" placeholder="https://facebook.com/yourpage">
                </div>
                <div class="form-group">
                    <label style="font-size: 12.5px; font-weight: 600;">Twitter / X URL</label>
                    <input type="url" name="twitter" value="{{ old('twitter', $socials['twitter'] ?? '') }}" class="biz-search-input" placeholder="https://x.com/yourhandle">
                </div>
                <div class="form-group">
                    <label style="font-size: 12.5px; font-weight: 600;">Instagram URL</label>
                    <input type="url" name="instagram" value="{{ old('instagram', $socials['instagram'] ?? '') }}" class="biz-search-input" placeholder="https://instagram.com/yourhandle">
                </div>
                <div class="form-group">
                    <label style="font-size: 12.5px; font-weight: 600;">LinkedIn URL</label>
                    <input type="url" name="linkedin" value="{{ old('linkedin', $socials['linkedin'] ?? '') }}" class="biz-search-input" placeholder="https://linkedin.com/company/yourpage">
                </div>
            </div>

            <!-- Business Verification Banner -->
            <div style="padding: 16px; border-radius: 14px; background: rgba(32, 200, 117, 0.08); border: 1px solid rgba(32, 200, 117, 0.2); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; margin-top: 8px;">
                <div>
                    <strong style="font-size: 14px; color: #1d2738; display: block;">Official Business Verification</strong>
                    <span style="font-size: 12.5px; color: #687386;">Gain official trust badge and priority search ranking by verifying your business.</span>
                </div>
                <a href="{{ route('member.business-pages.verification.index', $businessPage) }}" class="member-button member-button--primary" style="padding: 6px 14px; font-size: 13px;">
                    <i data-lucide="badge-check"></i> Verification Portal
                </a>
            </div>

            <!-- Submit Buttons -->
            <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 12px;">
                <a href="{{ route('member.business-pages.show', $businessPage) }}" class="member-button member-button--secondary">
                    Cancel
                </a>
                <button type="submit" class="member-button member-button--primary">
                    <i data-lucide="save" aria-hidden="true"></i> Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
