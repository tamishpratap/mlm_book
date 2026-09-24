@extends('admin.layouts.master')
@section('title', 'Edit Member: ' . $member->name)
@section('page-subtitle', 'Update profile details, contact information, verification state, and media assets.')

@section('content')
<x-admin.card title="Edit Member: {{ $member->name }}">
    <x-slot name="headerAction">
        <a href="{{ route('admin.members.show', $member) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i data-feather="arrow-left" style="width: 14px; height: 14px;"></i> Back to Member Profile
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

    <form action="{{ route('admin.members.update', $member) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- SECTION 1: BASIC INFORMATION & ACCOUNT -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-user me-1"></i> 1. Account & Personal Information
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin.form.group label="Full Name" for="name" :required="true">
                        <x-admin.form.input name="name" :value="$member->name" placeholder="e.g. John Doe" required />
                    </x-admin.form.group>
                </div>

                <div class="col-md-6">
                    <x-admin.form.group label="Username / Handle" for="user_id" :required="true">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">@</span>
                            <input type="text" name="user_id" id="user_id" class="form-control {{ $errors->has('user_id') ? 'is-invalid' : '' }}" value="{{ old('user_id', ltrim($member->user_id, '@')) }}" placeholder="username" required>
                        </div>
                        @error('user_id')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </x-admin.form.group>
                </div>

                <div class="col-md-6">
                    <x-admin.form.group label="Email Address" for="email" :required="true">
                        <x-admin.form.input type="email" name="email" :value="$member->email" placeholder="e.g. user@example.com" required />
                    </x-admin.form.group>
                </div>

                <div class="col-md-6">
                    <x-admin.form.group label="Phone Number" for="phone">
                        <x-admin.form.input name="phone" :value="$member->phone" placeholder="e.g. +1 555-0199" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-6">
                    <x-admin.form.group label="Gender" for="gender">
                        <x-admin.form.select name="gender" :selected="$member->gender">
                            <option value="">-- Select Gender --</option>
                            <option value="male" {{ old('gender', $member->gender) === 'male' ? 'selected' : '' }}>Male</option>
                            <option value="female" {{ old('gender', $member->gender) === 'female' ? 'selected' : '' }}>Female</option>
                            <option value="other" {{ old('gender', $member->gender) === 'other' ? 'selected' : '' }}>Other</option>
                            <option value="prefer_not_to_say" {{ old('gender', $member->gender) === 'prefer_not_to_say' ? 'selected' : '' }}>Prefer not to say</option>
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-6">
                    <x-admin.form.group label="Date of Birth" for="date_of_birth">
                        <input type="date" name="date_of_birth" id="date_of_birth" class="form-control {{ $errors->has('date_of_birth') ? 'is-invalid' : '' }}" value="{{ old('date_of_birth', $member->date_of_birth?->format('Y-m-d')) }}">
                        @error('date_of_birth')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </x-admin.form.group>
                </div>
            </div>
        </div>

        <!-- SECTION 2: LOCATION & BIOGRAPHY -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-map-marker me-1"></i> 2. Location & Biography
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin.form.group label="City" for="city">
                        <x-admin.form.input name="city" :value="$member->city" placeholder="e.g. New York" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-6">
                    <x-admin.form.group label="Country" for="country">
                        <x-admin.form.input name="country" :value="$member->country" placeholder="e.g. United States" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-12">
                    <x-admin.form.group label="Website / Portfolio URL" for="website">
                        <x-admin.form.input name="website" :value="$member->website" placeholder="https://example.com" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-12">
                    <x-admin.form.group label="Bio / About Member" for="bio">
                        <x-admin.form.textarea name="bio" :value="$member->bio" rows="4" placeholder="Short introduction, background, or overview of the member..." />
                    </x-admin.form.group>
                </div>
            </div>
        </div>

        <!-- SECTION 3: VERIFICATION STATE -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-shield me-1"></i> 3. Verification State
            </h6>
            <div class="row g-3">
                <div class="col-md-12">
                    <div class="form-check form-switch p-2 ps-5 bg-light rounded border">
                        <input type="hidden" name="is_verified" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_verified" name="is_verified" value="1" {{ old('is_verified', $member->mobile_verified_at ? 1 : 0) ? 'checked' : '' }}>
                        <label class="form-check-label fw-bold text-dark" for="is_verified">
                            Verified Member Account
                        </label>
                        <div class="text-muted small">
                            When enabled, this member account is considered fully verified and approved on the platform.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- SECTION 4: PROFILE & COVER PHOTOS -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-picture-o me-1"></i> 4. Media & Branding Assets
            </h6>
            <div class="row g-4">
                <!-- Profile Avatar -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-dark">Profile Avatar Photo</label>
                    <div class="d-flex align-items-center gap-3 mb-2 p-3 bg-light rounded border">
                        <img id="profilePhotoPreview" src="{{ $member->avatar_url }}" alt="Profile Photo" class="rounded-circle border shadow-sm" style="width: 70px; height: 70px; object-fit: cover; background: #fff;">
                        <div class="flex-grow-1">
                            <input type="file" name="profile_photo" id="profile_photo" class="form-control form-control-sm {{ $errors->has('profile_photo') ? 'is-invalid' : '' }}" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'profilePhotoPreview')">
                            <div class="form-text text-muted small mt-1">Recommended 400x400px (JPG, PNG, WebP up to 5MB)</div>
                            @error('profile_photo')
                                <div class="text-danger small">{{ $message }}</div>
                            @enderror
                            @if($member->profile_photo)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" name="remove_profile_photo" id="remove_profile_photo" value="1">
                                    <label class="form-check-label text-danger small fw-semibold" for="remove_profile_photo">
                                        <i class="fa fa-trash me-1"></i> Remove current profile avatar
                                    </label>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Cover Banner -->
                <div class="col-md-6">
                    <label class="form-label fw-semibold text-dark">Profile Cover Banner</label>
                    <div class="p-3 bg-light rounded border">
                        <div class="mb-2 position-relative rounded overflow-hidden" style="height: 70px; background: #1e1b4b;">
                            <img id="coverPhotoPreview" src="{{ $member->cover_photo_url ?: 'data:image/svg+xml;charset=UTF-8,%3Csvg%20width%3D%22600%22%20height%3D%22200%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20viewBox%3D%220%200%20600%20200%22%20preserveAspectRatio%3D%22none%22%3E%3Cdefs%3E%3Cstyle%20type%3D%22text%2Fcss%22%3E%23holder_1%20text%20%7B%20fill%3Argba(255%2C255%2C255%2C.5)%3Bfont-weight%3Anormal%3Bfont-family%3AHelvetica%2C%20monospace%3Bfont-size%3A16pt%20%7D%20%3C%2Fstyle%3E%3C%2Fdefs%3E%3Cg%20id%3D%22holder_1%22%3E%3Crect%20width%3D%22600%22%20height%3D%22200%22%20fill%3D%22%23312e81%22%3E%3C%2Frect%3E%3Cg%3E%3Ctext%20x%3D%22220%22%20y%3D%22105%22%3ENo%20Cover%20Image%3C%2Ftext%3E%3C%2Fg%3E%3C%2Fg%3E%3C%2Fsvg%3E' }}" alt="Cover Banner" class="w-100 h-100" style="object-fit: cover;">
                        </div>
                        <input type="file" name="cover_photo" id="cover_photo" class="form-control form-control-sm {{ $errors->has('cover_photo') ? 'is-invalid' : '' }}" accept="image/jpeg,image/png,image/jpg,image/webp" onchange="previewImage(this, 'coverPhotoPreview')">
                        <div class="form-text text-muted small mt-1">Recommended 1200x400px (JPG, PNG, WebP up to 10MB)</div>
                        @error('cover_photo')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                        @if($member->cover_photo)
                            <div class="form-check mt-2">
                                <input class="form-check-input" type="checkbox" name="remove_cover_photo" id="remove_cover_photo" value="1">
                                <label class="form-check-label text-danger small fw-semibold" for="remove_cover_photo">
                                    <i class="fa fa-trash me-1"></i> Remove current cover banner
                                </label>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- FORM ACTIONS -->
        <div class="d-flex align-items-center justify-content-end gap-2 border-top pt-3">
            <a href="{{ route('admin.members.show', $member) }}" class="btn btn-outline-secondary px-4">
                <i data-feather="x" style="width: 15px; height: 15px;" class="me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm">
                <i data-feather="save" style="width: 15px; height: 15px;" class="me-1"></i> Save Changes
            </button>
        </div>
    </form>
</x-admin.card>

<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById(previewId);
            if (preview) {
                preview.src = e.target.result;
            }
        }
        reader.readAsDataURL(input.files[0]);
    }
}
</script>
@endsection
