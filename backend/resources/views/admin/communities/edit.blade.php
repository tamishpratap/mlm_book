@extends('admin.layouts.master')
@section('title', 'Edit Community: ' . $community->name)
@section('page-subtitle', 'Modify community identity, access rules, posting policies, and media assets.')

@section('content')
<x-admin.card title="Edit Community: {{ $community->name }}">
    <x-slot name="headerAction">
        <a href="{{ route('admin.communities.show', $community) }}" class="btn btn-sm btn-outline-secondary d-inline-flex align-items-center gap-1">
            <i data-feather="arrow-left" style="width: 14px; height: 14px;"></i> Back to Community Detail
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

    <form action="{{ route('admin.communities.update', $community) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <!-- SECTION 1: COMMUNITY IDENTITY & PERMISSIONS -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-users me-1"></i> 1. Community Information & Policies
            </h6>
            <div class="row g-3">
                <div class="col-md-6">
                    <x-admin.form.group label="Community Name" for="name" :required="true">
                        <x-admin.form.input name="name" :value="$community->name" placeholder="e.g. Crypto Leaders Network" required />
                    </x-admin.form.group>
                </div>

                <div class="col-md-6">
                    <x-admin.form.group label="Slug / Identifier" for="slug">
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted">c/</span>
                            <input type="text" name="slug" id="slug" class="form-control {{ $errors->has('slug') ? 'is-invalid' : '' }}" value="{{ old('slug', $community->slug) }}" placeholder="community-slug">
                        </div>
                        @error('slug')
                            <div class="text-danger small mt-1">{{ $message }}</div>
                        @enderror
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Category" for="category" :required="true">
                        <x-admin.form.select name="category" :selected="$community->category" required>
                            @if(!in_array($community->category, $categories) && filled($community->category))
                                <option value="{{ $community->category }}" selected>{{ $community->category }}</option>
                            @endif
                            @foreach($categories as $cat)
                                <option value="{{ $cat }}" {{ old('category', $community->category) === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Visibility" for="visibility" :required="true">
                        <x-admin.form.select name="visibility" :selected="$community->visibility" required>
                            @foreach($visibilities as $val => $label)
                                <option value="{{ $val }}" {{ old('visibility', $community->visibility) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Status" for="status" :required="true">
                        <x-admin.form.select name="status" :selected="$community->status" required>
                            <option value="active" {{ old('status', $community->status) === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="suspended" {{ old('status', $community->status) === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Join Approval Mode" for="join_approval_mode" :required="true">
                        <x-admin.form.select name="join_approval_mode" :selected="$community->join_approval_mode ?: 'auto'" required>
                            <option value="auto" {{ old('join_approval_mode', $community->join_approval_mode) === 'auto' ? 'selected' : '' }}>Auto Approval (Open Join)</option>
                            <option value="manual" {{ old('join_approval_mode', $community->join_approval_mode) === 'manual' ? 'selected' : '' }}>Manual Approval (Admin/Mod Review)</option>
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Posting Permissions" for="posting_permissions" :required="true">
                        <x-admin.form.select name="posting_permissions" :selected="$community->posting_permissions ?: 'everyone'" required>
                            <option value="everyone" {{ old('posting_permissions', $community->posting_permissions) === 'everyone' ? 'selected' : '' }}>Everyone (Members & Guests)</option>
                            <option value="members_only" {{ old('posting_permissions', $community->posting_permissions) === 'members_only' ? 'selected' : '' }}>Members Only</option>
                            <option value="moderators_admins" {{ old('posting_permissions', $community->posting_permissions) === 'moderators_admins' ? 'selected' : '' }}>Moderators & Admins Only</option>
                            <option value="admins_only" {{ old('posting_permissions', $community->posting_permissions) === 'admins_only' ? 'selected' : '' }}>Admins Only</option>
                            <option value="owner_only" {{ old('posting_permissions', $community->posting_permissions) === 'owner_only' ? 'selected' : '' }}>Owner Only</option>
                        </x-admin.form.select>
                    </x-admin.form.group>
                </div>

                <div class="col-md-4">
                    <x-admin.form.group label="Tags (Comma-separated)" for="tags">
                        <x-admin.form.input name="tags" :value="$community->tags" placeholder="e.g. mlm, crypto, passive-income" />
                    </x-admin.form.group>
                </div>

                <div class="col-md-6">
                    <x-admin.form.group label="Community Description" for="description">
                        <x-admin.form.textarea name="description" :value="$community->description" rows="4" placeholder="Detailed purpose and focus of this community..." />
                    </x-admin.form.group>
                </div>

                <div class="col-md-6">
                    <x-admin.form.group label="Community Rules" for="rules">
                        <x-admin.form.textarea name="rules" :value="$community->rules" rows="4" placeholder="Guidelines, etiquette, and conduct expectations..." />
                    </x-admin.form.group>
                </div>
            </div>
        </div>

        <!-- SECTION 2: MEDIA & BRANDING -->
        <div class="mb-4">
            <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">
                <i class="fa fa-image me-1"></i> 2. Branding & Media Assets
            </h6>
            <div class="row g-4">
                <!-- Logo -->
                <div class="col-md-6">
                    <div class="card h-100 border p-3 bg-light">
                        <label class="form-label fw-bold text-dark mb-2">Community Logo / Avatar (Square/Circle, Max 2MB)</label>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            @if($community->logo && file_exists(public_path($community->logo)))
                                <img src="{{ asset($community->logo) }}" alt="Logo" class="rounded-circle border shadow-sm" style="width: 70px; height: 70px; object-fit: cover; background: #fff;">
                                <div>
                                    <div class="small fw-semibold text-dark">Current Logo File</div>
                                    <div class="form-check mt-1">
                                        <input class="form-check-input" type="checkbox" name="remove_logo" value="1" id="remove_logo">
                                        <label class="form-check-label text-danger small" for="remove_logo">Remove existing logo</label>
                                    </div>
                                </div>
                            @else
                                <div class="rounded-circle border bg-white shadow-sm d-flex align-items-center justify-content-center text-muted" style="width: 70px; height: 70px; font-size: 20px;">
                                    <i class="fa fa-users"></i>
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
                            @if($community->cover_photo && file_exists(public_path($community->cover_photo)))
                                <img src="{{ asset($community->cover_photo) }}" alt="Cover" class="rounded border shadow-sm" style="width: 120px; height: 70px; object-fit: cover;">
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
            <a href="{{ route('admin.communities.show', $community) }}" class="btn btn-outline-secondary px-4">
                <i class="fa fa-times me-1"></i> Cancel
            </a>
            <button type="submit" class="btn btn-primary px-4 shadow-sm d-inline-flex align-items-center gap-1">
                <i data-feather="save" style="width: 15px; height: 15px;"></i> Save Changes
            </button>
        </div>
    </form>
</x-admin.card>
@endsection
