@extends('member.layouts.app')

@section('title', 'Edit Profile')

@section('content')
    @php
        $hasProfilePhoto = $member->profile_photo
            && str_starts_with($member->profile_photo, 'uploads/profile/')
            && ! str_contains($member->profile_photo, '..')
            && file_exists(public_path($member->profile_photo));
        $hasCoverPhoto = $member->cover_photo
            && str_starts_with($member->cover_photo, 'uploads/cover/')
            && ! str_contains($member->cover_photo, '..')
            && file_exists(public_path($member->cover_photo));
        $cacheVersion = $member->updated_at?->timestamp ?? now()->timestamp;
        $profilePhotoUrl = $hasProfilePhoto ? asset($member->profile_photo).'?v='.$cacheVersion : null;
        $coverPhotoUrl = $hasCoverPhoto ? asset($member->cover_photo).'?v='.$cacheVersion : null;
        $initials = collect(preg_split('/\s+/', trim($member->name)))
            ->filter()
            ->take(2)
            ->map(fn ($part) => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($part, 0, 1)))
            ->implode('') ?: 'M';
    @endphp

    <header class="member-page-heading">
        <div>
            <h1>Edit Profile</h1>
            <p>Keep your personal details accurate and up to date.</p>
        </div>
        <a class="member-button member-button--secondary" href="{{ route('member.profile.show') }}">
            <i data-lucide="arrow-left" aria-hidden="true"></i>
            Back to profile
        </a>
    </header>

    <div class="profile-edit-grid">
        <aside class="member-card profile-preview">
            <div class="profile-preview__cover">
                @if ($coverPhotoUrl)
                    <img src="{{ $coverPhotoUrl }}" alt="{{ $member->name }}'s cover photo" loading="lazy">
                @endif
            </div>
            <div class="profile-preview__body">
                <div class="profile-preview__avatar-wrap">
                    @if ($profilePhotoUrl)
                        <img class="profile-preview__avatar-img" src="{{ $profilePhotoUrl }}" alt="{{ $member->name }}'s profile photo" loading="lazy">
                    @else
                        <div class="profile-preview__avatar--initials" role="img" aria-label="{{ $member->name }} initials">{{ $initials }}</div>
                    @endif
                </div>
                <h2 class="profile-preview__name">{{ $member->name }}</h2>
                @if (filled($member->user_id))
                    <span class="profile-preview__username">{{ '@'.$member->user_id }}</span>
                @endif
                <p class="profile-preview__email">
                    <i data-lucide="mail" aria-hidden="true" style="width: 14px; height: 14px; flex-shrink: 0; color: #94a3b8;"></i>
                    <span>{{ $member->email }}</span>
                </p>
            </div>
        </aside>

        <section class="member-card">
            <header class="member-card__header">
                <div>
                    <h2>Profile details</h2>
                    <p>These details appear on your Member profile.</p>
                </div>
            </header>

            <form class="member-form" method="POST" action="{{ route('member.profile.update') }}">
                @csrf
                @method('PUT')

                <div class="form-grid">
                    <div class="form-field form-field--full">
                        <label for="name">Full name</label>
                        <input class="form-control @error('name') is-invalid @enderror" id="name" name="name" type="text" value="{{ old('name', $member->name) }}" maxlength="255" required @error('name') aria-invalid="true" aria-describedby="name-error" @enderror>
                        @error('name')<p class="form-error" id="name-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="date_of_birth">Date of birth</label>
                        <input class="form-control @error('date_of_birth') is-invalid @enderror" id="date_of_birth" name="date_of_birth" type="date" value="{{ old('date_of_birth', $member->date_of_birth?->format('Y-m-d')) }}" max="{{ now()->format('Y-m-d') }}" @error('date_of_birth') aria-invalid="true" aria-describedby="date-of-birth-error" @enderror>
                        @error('date_of_birth')<p class="form-error" id="date-of-birth-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="gender">Gender</label>
                        <select class="form-control @error('gender') is-invalid @enderror" id="gender" name="gender" @error('gender') aria-invalid="true" aria-describedby="gender-error" @enderror>
                            <option value="">Select an option</option>
                            <option value="male" @selected(old('gender', $member->gender) === 'male')>Male</option>
                            <option value="female" @selected(old('gender', $member->gender) === 'female')>Female</option>
                            <option value="other" @selected(old('gender', $member->gender) === 'other')>Other</option>
                            <option value="prefer_not_to_say" @selected(old('gender', $member->gender) === 'prefer_not_to_say')>Prefer not to say</option>
                        </select>
                        @error('gender')<p class="form-error" id="gender-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="city">City</label>
                        <input class="form-control @error('city') is-invalid @enderror" id="city" name="city" type="text" value="{{ old('city', $member->city) }}" maxlength="100" placeholder="Your city" @error('city') aria-invalid="true" aria-describedby="city-error" @enderror>
                        @error('city')<p class="form-error" id="city-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="country">Country</label>
                        <input class="form-control @error('country') is-invalid @enderror" id="country" name="country" type="text" value="{{ old('country', $member->country) }}" maxlength="100" placeholder="Your country" @error('country') aria-invalid="true" aria-describedby="country-error" @enderror>
                        @error('country')<p class="form-error" id="country-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field">
                        <label for="website">Website</label>
                        <input class="form-control @error('website') is-invalid @enderror" id="website" name="website" type="url" value="{{ old('website', $member->website) }}" maxlength="255" placeholder="https://example.com" @error('website') aria-invalid="true" aria-describedby="website-error" @enderror>
                        @error('website')<p class="form-error" id="website-error">{{ $message }}</p>@enderror
                    </div>

                    <div class="form-field form-field--full">
                        <label for="bio">Bio</label>
                        <textarea class="form-control @error('bio') is-invalid @enderror" id="bio" name="bio" maxlength="500" placeholder="Share a little about yourself..." @error('bio') aria-invalid="true" aria-describedby="bio-error" @enderror>{{ old('bio', $member->bio) }}</textarea>
                        <p class="form-help">Maximum 500 characters.</p>
                        @error('bio')<p class="form-error" id="bio-error">{{ $message }}</p>@enderror
                    </div>
                </div>

                <div class="form-actions">
                    <a class="member-button member-button--secondary" href="{{ route('member.profile.show') }}">Cancel</a>
                    <button class="member-button member-button--primary" type="submit">
                        <i data-lucide="save" aria-hidden="true"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
