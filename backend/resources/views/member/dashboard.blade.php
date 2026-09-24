@extends('member.layouts.app')

@section('title', 'Home - About MLM Book')
@section('main-class', 'home-landing')
@section('main-id', 'home-landing')

@section('content')
<div class="home-container">
    <!-- Hero Banner Section -->
    <section class="card home-hero">
        <div class="home-hero__content">
            <div class="home-hero__badge">
                <i data-lucide="sparkles" aria-hidden="true"></i>
                <span>Official MLM Book Platform</span>
            </div>
            <h1 class="home-hero__title">Welcome to MLM Book, {{ $currentMember->name }}!</h1>
            <p class="home-hero__subtitle">
                An all-in-one social networking platform and digital ecosystem built to connect individuals, foster vibrant communities, empower business brands, and facilitate modern digital networking.
            </p>
            <div class="home-hero__actions">
                <a class="member-button member-button--primary" href="{{ route('member.socials') }}">
                    <i data-lucide="rss" aria-hidden="true"></i>
                    <span>Go to Socials</span>
                </a>
                <a class="member-button member-button--secondary" href="{{ route('member.community.index') }}">
                    <i data-lucide="users" aria-hidden="true"></i>
                    <span>Explore Communities</span>
                </a>
                <a class="member-button member-button--secondary" href="{{ route('member.business-pages.directory.index') }}">
                    <i data-lucide="compass" aria-hidden="true"></i>
                    <span>Business Directory</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Platform Purpose & Vision -->
    <section class="card home-section">
        <div class="home-section__header">
            <h2 class="home-section__title">
                <i data-lucide="compass" aria-hidden="true"></i>
                <span>About MLM Book & Our Purpose</span>
            </h2>
            <p class="home-section__desc">Fostering authentic social interactions and seamless digital connectivity.</p>
        </div>
        <div class="home-grid home-grid--2">
            <div class="home-info-card">
                <div class="home-info-card__icon home-info-card__icon--primary">
                    <i data-lucide="target" aria-hidden="true"></i>
                </div>
                <h3>Our Purpose</h3>
                <p>
                    MLM Book was created to bridge the gap between social interaction and professional collaboration. We empower members to build authentic connections, share moments, showcase business ventures, and engage in constructive discussions in a safe environment.
                </p>
            </div>

            <div class="home-info-card">
                <div class="home-info-card__icon home-info-card__icon--success">
                    <i data-lucide="users-round" aria-hidden="true"></i>
                </div>
                <h3>Our Community</h3>
                <p>
                    Our platform brings together individuals, entrepreneurs, content creators, and local businesses. Whether you are seeking new connections, exploring business directories, or joining interest-based groups, our community supports your growth.
                </p>
            </div>
        </div>
    </section>

    <!-- Core Features Section -->
    <section class="card home-section">
        <div class="home-section__header">
            <h2 class="home-section__title">
                <i data-lucide="layout-grid" aria-hidden="true"></i>
                <span>Core Platform Capabilities</span>
            </h2>
            <p class="home-section__desc">Everything members can explore and utilize across MLM Book.</p>
        </div>

        <div class="home-grid home-grid--3">
            <div class="home-feature-card">
                <div class="home-feature-card__icon">
                    <i data-lucide="rss" aria-hidden="true"></i>
                </div>
                <h3>Social Features & Smart Feed</h3>
                <p>Share posts, photo & video updates, 24-hour stories, and interact through likes, custom reactions, threaded comments, shares, and saved posts.</p>
                <a class="home-feature-card__link" href="{{ route('member.socials') }}">
                    <span>View Socials</span> <i data-lucide="arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="home-feature-card">
                <div class="home-feature-card__icon">
                    <i data-lucide="monitor-play" aria-hidden="true"></i>
                </div>
                <h3>Watch Video Platform</h3>
                <p>Discover video content uploaded by members, watch video posts seamlessly, and explore visual updates from across the network.</p>
                <a class="home-feature-card__link" href="{{ route('member.watch.index') }}">
                    <span>Watch Videos</span> <i data-lucide="arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="home-feature-card">
                <div class="home-feature-card__icon">
                    <i data-lucide="users" aria-hidden="true"></i>
                </div>
                <h3>Community Features</h3>
                <p>Create or join public and private groups, participate in discussion threads, invite peers via custom invite links, and manage group moderation.</p>
                <a class="home-feature-card__link" href="{{ route('member.community.index') }}">
                    <span>Browse Communities</span> <i data-lucide="arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="home-feature-card">
                <div class="home-feature-card__icon">
                    <i data-lucide="building-2" aria-hidden="true"></i>
                </div>
                <h3>Business Pages & Directory</h3>
                <p>Establish brand pages, manage customer reviews and inbox messages, track page analytics, and feature your organization in the Business Directory.</p>
                <a class="home-feature-card__link" href="{{ route('member.business-pages.directory.index') }}">
                    <span>Business Directory</span> <i data-lucide="arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="home-feature-card">
                <div class="home-feature-card__icon">
                    <i data-lucide="calendar-days" aria-hidden="true"></i>
                </div>
                <h3>Events Platform</h3>
                <p>Host upcoming events, manage attendee lists, track RSVPs, and stay updated on networking meetups and community gatherings.</p>
                <a class="home-feature-card__link" href="{{ route('member.events.index') }}">
                    <span>Discover Events</span> <i data-lucide="arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <div class="home-feature-card">
                <div class="home-feature-card__icon">
                    <i data-lucide="sparkles" aria-hidden="true"></i>
                </div>
                <h3>New Connections & Discovery</h3>
                <p>Discover recommended members based on mutual connections and geographic location, build your network, and manage your account privacy.</p>
                <a class="home-feature-card__link" href="{{ route('member.people.suggestions') }}">
                    <span>Find Connections</span> <i data-lucide="arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- Safe Networking & Connectivity -->
    <section class="card home-section">
        <div class="home-section__header">
            <h2 class="home-section__title">
                <i data-lucide="shield-check" aria-hidden="true"></i>
                <span>Safe Social Networking & Member Connectivity</span>
            </h2>
            <p class="home-section__desc">Your security, privacy, and connection management are built into every feature.</p>
        </div>

        <div class="home-grid home-grid--2">
            <div class="home-security-box">
                <ul>
                    <li>
                        <i data-lucide="at-sign" aria-hidden="true"></i>
                        <div>
                            <strong>Unique Member Handles</strong>
                            <p>Every member receives a normalized, verified handle (e.g. <code>{{ $currentMember->user_id ?? '@member' }}</code>) for precise searches and identification.</p>
                        </div>
                    </li>
                    <li>
                        <i data-lucide="user-check" aria-hidden="true"></i>
                        <div>
                            <strong>Connection Controls</strong>
                            <p>Manage incoming requests, accept or reject connections, view new recommendations, and keep your network strictly under your control.</p>
                        </div>
                    </li>
                </ul>
            </div>

            <div class="home-security-box">
                <ul>
                    <li>
                        <i data-lucide="lock" aria-hidden="true"></i>
                        <div>
                            <strong>Privacy & Disconnections</strong>
                            <p>Protect your profile with granular account settings and instant disconnection options to maintain a safe social experience.</p>
                        </div>
                    </li>
                    <li>
                        <i data-lucide="bell" aria-hidden="true"></i>
                        <div>
                            <strong>Real-Time Notifications</strong>
                            <p>Stay informed about connection requests, post interactions, community invites, and page updates directly from your header bar.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </section>

    <!-- What Members Can Do CTA Section -->
    <section class="card home-cta-banner">
        <div class="home-cta-banner__content">
            <h2>Ready to Connect & Discover?</h2>
            <p>Jump straight into your feed, update your profile, or explore new connections across MLM Book.</p>
            <div class="home-cta-banner__buttons">
                <a class="member-button member-button--primary" href="{{ route('member.socials') }}">
                    <i data-lucide="sparkles" aria-hidden="true"></i>
                    <span>Open Socials Feed</span>
                </a>
                <a class="member-button member-button--secondary" href="{{ route('member.profile.show') }}">
                    <i data-lucide="user-round" aria-hidden="true"></i>
                    <span>My Profile</span>
                </a>
                <a class="member-button member-button--secondary" href="{{ route('member.people.suggestions') }}">
                    <i data-lucide="sparkles" aria-hidden="true"></i>
                    <span>New Connections</span>
                </a>
            </div>
        </div>
    </section>
</div>
@endsection

@section('right-sidebar')
<section class="card widget platform-widget">
    <div class="section-heading">
        <h2>Quick Shortcuts</h2>
    </div>
    <div class="shortcut-list">
        <a class="shortcut" href="{{ route('member.socials') }}">
            <span class="shortcut-icon-badge"><i data-lucide="rss" aria-hidden="true"></i></span>
            <span>Socials Feed</span>
        </a>
        <a class="shortcut" href="{{ route('member.friends.index') }}">
            <span class="shortcut-icon-badge"><i data-lucide="users-round" aria-hidden="true"></i></span>
            <span>My Connections</span>
        </a>
        <a class="shortcut" href="{{ route('member.people.suggestions') }}">
            <span class="shortcut-icon-badge"><i data-lucide="sparkles" aria-hidden="true"></i></span>
            <span>New Connections</span>
        </a>
        <a class="shortcut" href="{{ route('member.watch.index') }}">
            <span class="shortcut-icon-badge"><i data-lucide="monitor-play" aria-hidden="true"></i></span>
            <span>Watch Videos</span>
        </a>
        <a class="shortcut" href="{{ route('member.community.index') }}">
            <span class="shortcut-icon-badge"><i data-lucide="users" aria-hidden="true"></i></span>
            <span>Community Groups</span>
        </a>
        <a class="shortcut" href="{{ route('member.business-pages.directory.index') }}">
            <span class="shortcut-icon-badge"><i data-lucide="compass" aria-hidden="true"></i></span>
            <span>Business Directory</span>
        </a>
        <a class="shortcut" href="{{ route('member.events.index') }}">
            <span class="shortcut-icon-badge"><i data-lucide="calendar" aria-hidden="true"></i></span>
            <span>Upcoming Events</span>
        </a>
    </div>
</section>

<section class="card widget profile-summary-widget">
    <div class="section-heading">
        <h2>Member Account</h2>
    </div>
    <div style="padding: 10px 0;">
        <p style="margin: 0 0 6px 0; font-weight: 600; color: var(--color-text);">{{ $currentMember->name }}</p>
        @if ($currentMember->user_id)
            <span class="member-search-results__badge" style="margin-bottom: 10px; display: inline-block;">{{ '@'.$currentMember->user_id }}</span>
        @endif
        <div style="display: flex; flex-direction: column; gap: 6px; margin-top: 8px;">
            <a class="soft-cta" href="{{ route('member.profile.show') }}" style="text-decoration: none;">View My Profile</a>
            <a class="soft-cta" href="{{ route('member.account.settings') }}" style="text-decoration: none; color: var(--color-text-secondary);">Account Settings</a>
        </div>
    </div>
</section>
@endsection
