<div class="profile-about-layout" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 18px;">
    <section class="member-card profile-about-card">
        <header class="member-card__header">
            <div>
                <h2>About</h2>
                <p>Your personal and contact information.</p>
            </div>
            @if (auth('member')->id() === $member->id)
                <button class="member-button member-button--secondary" type="button" data-profile-edit-open>
                    <i data-lucide="pencil" aria-hidden="true"></i>
                    <span>Edit</span>
                </button>
            @endif
        </header>

        <div class="about-grid">
            <div class="about-item">
                <span><i data-lucide="message-square" aria-hidden="true"></i></span>
                <div><small>Bio</small><strong>{{ $member->bio ?: 'Not added yet' }}</strong></div>
            </div>
            <div class="about-item">
                <span><i data-lucide="phone" aria-hidden="true"></i></span>
                <div><small>Phone</small><strong>{{ $member->phone ?: 'Not added yet' }}</strong></div>
            </div>
            <div class="about-item">
                <span><i data-lucide="cake" aria-hidden="true"></i></span>
                <div><small>Date of Birth</small><strong>{{ $member->date_of_birth?->format('F j, Y') ?: 'Not added yet' }}</strong></div>
            </div>
            <div class="about-item">
                <span><i data-lucide="user-round" aria-hidden="true"></i></span>
                <div><small>Gender</small><strong>{{ $member->gender ? \Illuminate\Support\Str::headline($member->gender) : 'Not added yet' }}</strong></div>
            </div>
            <div class="about-item">
                <span><i data-lucide="map-pin" aria-hidden="true"></i></span>
                <div><small>Location</small><strong>{{ collect([$member->city, $member->country])->filter()->implode(', ') ?: 'Not added yet' }}</strong></div>
            </div>
            <div class="about-item">
                <span><i data-lucide="link" aria-hidden="true"></i></span>
                <div>
                    <small>Website</small>
                    @if ($member->website)
                        <a href="{{ $member->website }}" target="_blank" rel="noopener noreferrer">{{ $member->website }}</a>
                    @else
                        <strong>Not added yet</strong>
                    @endif
                </div>
            </div>
        </div>
    </section>

    <section class="member-card profile-account-summary-card">
        <header class="member-card__header">
            <div>
                <h2>Account summary</h2>
                <p>Your secure account overview.</p>
            </div>
        </header>

        <div class="about-grid" style="grid-template-columns: 1fr;">
            <div class="about-item">
                <span><i data-lucide="mail" aria-hidden="true"></i></span>
                <div><small>Account Email</small><strong>{{ $member->email }}</strong></div>
            </div>
            <div class="about-item">
                <span><i data-lucide="key-round" aria-hidden="true"></i></span>
                <div><small>Login Method</small><strong>Email and password</strong></div>
            </div>
            <div class="about-item">
                <span><i data-lucide="calendar-days" aria-hidden="true"></i></span>
                <div><small>Member Since</small><strong>{{ $member->created_at->format('F j, Y') }}</strong></div>
            </div>
        </div>
    </section>
</div>
