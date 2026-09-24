@extends('admin.layouts.master')
@section('title', 'Edit Business Page: ' . $businessPage->page_name)
@section('page-subtitle', 'Modify business page identity, contact information, location, and media assets.')

@section('content')
<x-admin.card title="Edit Business Page: {{ $businessPage->page_name }}">
    <x-slot name="headerAction">
        <a href="{{ route('admin.business-pages.show', $businessPage) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i data-feather="arrow-left" style="width: 14px; height: 14px;"></i> Back to Page Detail
        </a>
    </x-slot>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
            <h6 class="alert-heading fw-bold mb-1"><i class="fa fa-exclamation-triangle me-1"></i> Please fix the following errors:</h6>
            <ul class="mb-0 ps-3 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <form action="{{ route('admin.business-pages.update', $businessPage) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- SECTION 1: BUSINESS IDENTITY -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-building-o me-1"></i> 1. Business Information & Identity
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin.form.group label="Business Name" for="page_name" :required="true">
                        <x-admin.form.input name="page_name" :value="$businessPage->page_name" placeholder="e.g. MLM Staking" required />
                    </x-admin.form.group>
                </div>

                <div class="col-md-6">
                    <x-admin.form.group label="Username / Handle" for="page_username">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">@</span>
                            <input type="text" name="page_username" id="page_username" class="form-control {{ $errors->has('page_username') ? 'is-invalid' : '' }}" value="{{ old('page_username', $businessPage->page_username) }}" placeholder="unique_handle">
                        </div>
                        @error('page_username')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="MLM Category" for="category" :required="true">
                        <x-admin.form.select name="category" :selected="$businessPage->category" required>
                            @if(!in_array($businessPage->category, $categories) && filled($businessPage->category))
                                <option value="{{ $businessPage->category }}" selected>{{ $businessPage->category }}</option>
                            @endif
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category', $businessPage->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Visibility" for="visibility" :required="true">
                        <x-admin.form.select name="visibility" :selected="$businessPage->visibility" required>
                            @foreach($visibilities as $val => $label)
                                <option value="{{ $val }}" {{ old('visibility', $businessPage->visibility) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Status" for="status" :required="true">
                        <x-admin.form.select name="status" :selected="$businessPage->status" required>
                            <option value="active" {{ old('status', $businessPage->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ old('status', $businessPage->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-12">
                    <div class="form-check form-switch p-2 bg-light rounded border">
                        <input class="form-check-input ms-0 me-2" type="checkbox" role="switch" id="is_verified" name="is_verified" value="1" {{ old('is_verified', $businessPage->is_verified) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark" for="is_verified">
                            <i class="fa fa-check-circle text-success me-1"></i> Verified Business Page Badge
                        </label>
                        <div class="text-muted small ms-4">Grant this business page an official verified badge across the platform.</div>
                    </div>
                </div>

                <div class="col-12">
                    <x-admin.form.group label="Business Description" for="description">
                        <x-admin.form.textarea name="description" :value="$businessPage->description" rows="4" placeholder="Comprehensive summary of products, compensation plans, and services offered..." />
                    </x-admin.form.group>
                </div>
            </div>
        </div>

        <!-- SECTION 2: CONTACT & LOCATION -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-map-marker me-1"></i> 2. Contact & Location Information
            </h6>
            <div class="row g-3">
                <div class="col-md-4">
                    <x-admin.form.group label="Website URL" for="website">
                        <x-admin.form.input type="url" name="website" :value="$businessPage->website" placeholder="https://example.com" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Business Email" for="email">
                        <x-admin.form.input type="email" name="email" :value="$businessPage->email" placeholder="contact@example.com" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Phone Number" for="phone">
                        <x-admin.form.input name="phone" :value="$businessPage->phone" placeholder="+1234567890" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Country" for="country">
                        <x-admin.form.select name="country" :selected="$businessPage->country" placeholder="-- Select Country --">
                            @foreach($countries as $c)
                                <option value="{{ $c }}" {{ old('country', $businessPage->country) === $c ? 'selected' : '' }}>{{ $c }}</option>
                            @endforeach
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="State / Region" for="state">
                        <x-admin.form.input name="state" :value="$businessPage->state" placeholder="e.g. California / Maharashtra" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="City" for="city">
                        <x-admin.form.input name="city" :value="$businessPage->city" placeholder="e.g. Los Angeles / Mumbai" />
                    </x-admin.form.group>
                </div>

                <div class="col-12">
                    <x-admin.form.group label="Street Address" for="address">
                        <x-admin.form.input name="address" :value="$businessPage->address" placeholder="Suite, Street number, building name..." />
                    </x-admin.form.group>
                </div>
            </div>
        </div>

        <!-- SECTION 3: MEDIA & BRANDING -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-image me-1"></i> 3. Branding & Media Assets
            </h6>
            <div class="row g-4">
                <!-- Logo -->
                <div class="col-md-6">
                    <div class="card h-100 border p-3 bg-light">
                        <label class="form-label fw-bold text-dark mb-2">Business Logo (Square/Circle, Max 2MB)</label>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            @if($businessPage->logo && file_exists(public_path($businessPage->logo)))
                                <img src="{{ asset($businessPage->logo) }}" alt="Logo" class="rounded-circle border shadow-sm" style="width: 70px; height: 70px; object-fit: cover; background: #fff;">
                                <div>
                                    <div class="small fw-semibold text-dark">Current Logo File</div>
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                                        <label class="form-check-label text-danger small" for="remove_logo">Remove existing logo</label>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-circle border bg-white shadow-sm d-flex align-items-center justify-content-center text-muted" style="width: 70px; height: 70px; font-size: 20px;">
                                    <i class="fa fa-picture-o"></i>
                                </div>
                                <div class="small text-muted">No custom logo uploaded yet.</div>
                            @endif
                        </div>
                        <input type="file" name="logo" class="form-control {{ $errors->has('logo') ? 'is-invalid' : '' }}" accept="image/jpeg,image/png,image/webp,image/jpg">
                        @error('logo')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <!-- Cover Photo -->
                <div class="col-md-6">
                    <div class="card h-100 border p-3 bg-light">
                        <label class="form-label fw-bold text-dark mb-2">Cover Banner (Wide, Max 5MB)</label>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            @if($businessPage->cover_photo && file_exists(public_path($businessPage->cover_photo)))
                                <img src="{{ asset($businessPage->cover_photo) }}" alt="Cover" class="rounded border shadow-sm" style="width: 120px; height: 70px; object-fit: cover;">
                                <div>
                                    <div class="small fw-semibold text-dark">Current Cover Photo</div>
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" name="remove_cover" value="1" id="remove_cover">
                                        <label class="form-check-label text-danger small" for="remove_cover">Remove existing cover</label>
                                    </div>
                                </div>
                            @else
                                <div class="rounded border bg-white shadow-sm d-flex align-items-center justify-content-center text-muted" style="width: 120px; height: 70px; font-size: 20px;">
                                    <i class="fa fa-picture-o"></i>
                                </div>
                                <div class="small text-muted">No custom cover banner uploaded.</div>
                            @endif
                        </div>
                        <input type="file" name="cover_photo" class="form-control {{ $errors->has('cover_photo') ? 'is-invalid' : '' }}" accept="image/jpeg,image/png,image/webp,image/jpg">
                        @error('cover_photo')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- FORM ACTION BUTTONS -->
        <div class="d-flex justify-content-between align-items-center pt-3 border-top mt-4">
            <a href="{{ route('admin.business-pages.show', $businessPage) }}" class="btn btn-outline-secondary px-4">
                <i class="fa fa-times me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm d-inline-flex align-items-center gap-1">
                <i data-feather="save" style="width: 15px; height: 15px;"></i> Save Changes
            </button>
        </div>
    </form>
</x-admin.card>
@endsection
