@extends('member.layouts.app')

@section('title', $community->name . ' — Admin Panel & Moderation')

@push('styles')
<link rel="stylesheet" href="{{ asset('member_assets/css/member-community.css') }}?v={{ file_exists(public_path('member_assets/css/member-community.css')) ? filemtime(public_path('member_assets/css/member-community.css')) : time() }}">
@endpush

@section('content')
<div class="community-page community-admin-container">
    <!-- Admin Header Banner -->
    <div class="community-admin-header">
        <div class="community-admin-header__top">
            <a href="{{ route('member.community.show', $community) }}" class="community-admin-btn community-admin-btn--secondary community-admin-btn--sm">
                <i data-lucide="arrow-left" aria-hidden="true"></i> Back to Community
            </a>

            <div style="display: flex; gap: 10px; align-items: center;">
                <a href="{{ route('member.community.show', $community) }}" target="_blank" class="community-admin-btn community-admin-btn--secondary community-admin-btn--sm" title="View Public Community Page">
                    <i data-lucide="external-link" aria-hidden="true"></i> View Community
                </a>
                <button type="button" class="community-admin-btn community-admin-btn--primary community-admin-btn--sm" data-share-modal-open="{{ $community->id }}">
                    <i data-lucide="share-2" aria-hidden="true"></i> Share Link
                </button>
            </div>
        </div>

        <div class="community-admin-header__title-area">
            <div class="community-admin-header__title-row">
                <h1 class="community-admin-header__title">{{ $community->name }}</h1>
                <span class="community-admin-badge">
                    <i data-lucide="shield-check" aria-hidden="true" style="width: 14px; height: 14px;"></i> Enterprise Admin & Governance
                </span>
            </div>
            <p class="community-admin-header__desc">
                Manage community access, member permissions, moderation reports, audit logs, and global governance settings.
            </p>
        </div>
    </div>

    <!-- Overview Stats Grid -->
    <div class="community-admin-stats-grid">
        <!-- Members Stat -->
        <div class="community-admin-stat-card community-admin-stat-card--blue">
            <div class="community-admin-stat-card__header">
                <span class="community-admin-stat-card__label">Total Members</span>
                <div class="community-admin-stat-card__icon-wrap">
                    <i data-lucide="users" aria-hidden="true"></i>
                </div>
            </div>
            <div class="community-admin-stat-card__value">{{ number_format($memberCount) }}</div>
        </div>

        <!-- Posts Stat -->
        <div class="community-admin-stat-card community-admin-stat-card--indigo">
            <div class="community-admin-stat-card__header">
                <span class="community-admin-stat-card__label">Total Posts</span>
                <div class="community-admin-stat-card__icon-wrap">
                    <i data-lucide="file-text" aria-hidden="true"></i>
                </div>
            </div>
            <div class="community-admin-stat-card__value">{{ number_format($postCount) }}</div>
        </div>

        <!-- Pending Requests Stat -->
        <div class="community-admin-stat-card community-admin-stat-card--amber">
            <div class="community-admin-stat-card__header">
                <span class="community-admin-stat-card__label">Pending Requests</span>
                <div class="community-admin-stat-card__icon-wrap">
                    <i data-lucide="clock" aria-hidden="true"></i>
                </div>
            </div>
            <div class="community-admin-stat-card__value">{{ number_format($pendingRequestsCount) }}</div>
        </div>

        <!-- Pending Reports Stat -->
        <div class="community-admin-stat-card community-admin-stat-card--rose">
            <div class="community-admin-stat-card__header">
                <span class="community-admin-stat-card__label">Pending Reports</span>
                <div class="community-admin-stat-card__icon-wrap">
                    <i data-lucide="flag" aria-hidden="true"></i>
                </div>
            </div>
            <div class="community-admin-stat-card__value">{{ number_format($pendingReportsCount) }}</div>
        </div>

        <!-- Banned Members Stat -->
        <div class="community-admin-stat-card community-admin-stat-card--slate">
            <div class="community-admin-stat-card__header">
                <span class="community-admin-stat-card__label">Banned Members</span>
                <div class="community-admin-stat-card__icon-wrap">
                    <i data-lucide="user-x" aria-hidden="true"></i>
                </div>
            </div>
            <div class="community-admin-stat-card__value">{{ number_format($bannedCount) }}</div>
        </div>

        <!-- Muted Members Stat -->
        <div class="community-admin-stat-card community-admin-stat-card--purple">
            <div class="community-admin-stat-card__header">
                <span class="community-admin-stat-card__label">Muted Members</span>
                <div class="community-admin-stat-card__icon-wrap">
                    <i data-lucide="volume-x" aria-hidden="true"></i>
                </div>
            </div>
            <div class="community-admin-stat-card__value">{{ number_format($mutedCount) }}</div>
        </div>
    </div>

    <!-- Admin Navigation Tabs -->
    <nav class="community-admin-nav" aria-label="Admin panel navigation">
        <a class="community-admin-tab {{ $activeTab === 'overview' ? 'is-active' : '' }}" href="{{ route('member.community.admin', [$community, 'tab' => 'overview']) }}">
            <i data-lucide="layout-dashboard" aria-hidden="true"></i> Overview
        </a>

        <a class="community-admin-tab {{ $activeTab === 'reports' ? 'is-active' : '' }}" href="{{ route('member.community.admin', [$community, 'tab' => 'reports']) }}">
            <i data-lucide="flag" aria-hidden="true"></i> Reports Queue
            <span class="community-admin-tab__count">{{ $pendingReportsCount }}</span>
        </a>

        <a class="community-admin-tab {{ $activeTab === 'audit_logs' ? 'is-active' : '' }}" href="{{ route('member.community.admin', [$community, 'tab' => 'audit_logs']) }}">
            <i data-lucide="shield-alert" aria-hidden="true"></i> Audit Logs
        </a>

        <a class="community-admin-tab {{ $activeTab === 'bans' ? 'is-active' : '' }}" href="{{ route('member.community.admin', [$community, 'tab' => 'bans']) }}">
            <i data-lucide="user-x" aria-hidden="true"></i> Banned Members
            <span class="community-admin-tab__count">{{ $bannedCount }}</span>
        </a>

        <a class="community-admin-tab {{ $activeTab === 'mutes' ? 'is-active' : '' }}" href="{{ route('member.community.admin', [$community, 'tab' => 'mutes']) }}">
            <i data-lucide="volume-x" aria-hidden="true"></i> Muted Members
            <span class="community-admin-tab__count">{{ $mutedCount }}</span>
        </a>

        @if ($isAdmin)
            <a class="community-admin-tab {{ $activeTab === 'settings' ? 'is-active' : '' }}" href="{{ route('member.community.admin', [$community, 'tab' => 'settings']) }}">
                <i data-lucide="settings" aria-hidden="true"></i> Governance & Settings
            </a>
        @endif
    </nav>

    <!-- Tab 1: Overview Dashboard -->
    @if ($activeTab === 'overview')
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 24px;">
            <!-- Governance Summary -->
            <section class="community-admin-card">
                <header class="community-admin-card__header">
                    <div>
                        <h2 class="community-admin-card__title">
                            <i data-lucide="shield-check" aria-hidden="true"></i> Governance & Permissions
                        </h2>
                        <p class="community-admin-card__subtitle">Current posting, join mode, and privacy configuration.</p>
                    </div>
                </header>
                <div style="display: flex; flex-direction: column; gap: 16px; font-size: 14px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                        <span style="color: #64748b; font-weight: 500;">Posting Permissions</span>
                        <span style="background: #eff6ff; color: #2563eb; font-weight: 700; padding: 4px 12px; border-radius: 20px; font-size: 12.5px;">
                            {{ ucfirst(str_replace('_', ' ', $community->posting_permissions ?: 'everyone')) }}
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px;">
                        <span style="color: #64748b; font-weight: 500;">Join Approval Mode</span>
                        <span style="background: #f0fdf4; color: #16a34a; font-weight: 700; padding: 4px 12px; border-radius: 20px; font-size: 12.5px;">
                            {{ ucfirst(str_replace('_', ' ', $community->join_approval_mode ?: 'instant')) }}
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="color: #64748b; font-weight: 500;">Community Privacy</span>
                        <span style="background: #f8fafc; color: #475569; font-weight: 700; padding: 4px 12px; border-radius: 20px; font-size: 12.5px; border: 1px solid #e2e8f0;">
                            {{ ucfirst($community->visibility) }}
                        </span>
                    </div>
                </div>
            </section>

            <!-- Quick Action Shortcuts -->
            <section class="community-admin-card">
                <header class="community-admin-card__header">
                    <div>
                        <h2 class="community-admin-card__title">
                            <i data-lucide="zap" aria-hidden="true"></i> Quick Shortcuts
                        </h2>
                        <p class="community-admin-card__subtitle">Fast access to moderation actions and member queues.</p>
                    </div>
                </header>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                    <a href="{{ route('member.community.show', [$community, 'tab' => 'requests']) }}" class="community-admin-btn community-admin-btn--secondary" style="height: auto; padding: 14px; flex-direction: column; text-align: center; gap: 6px;">
                        <i data-lucide="user-check" style="width: 20px; height: 20px; color: #4f7df3;"></i>
                        <span style="font-size: 13px;">Review Requests</span>
                        <small style="color: #64748b; font-size: 11px;">({{ $pendingRequestsCount }} pending)</small>
                    </a>
                    <a href="{{ route('member.community.admin', [$community, 'tab' => 'reports']) }}" class="community-admin-btn community-admin-btn--secondary" style="height: auto; padding: 14px; flex-direction: column; text-align: center; gap: 6px; border-color: #fecaca; background: #fff5f5;">
                        <i data-lucide="flag" style="width: 20px; height: 20px; color: #ef4444;"></i>
                        <span style="font-size: 13px; color: #dc2626;">Review Reports</span>
                        <small style="color: #991b1b; font-size: 11px;">({{ $pendingReportsCount }} pending)</small>
                    </a>
                    <a href="{{ route('member.community.show', [$community, 'tab' => 'members']) }}" class="community-admin-btn community-admin-btn--secondary" style="height: auto; padding: 14px; flex-direction: column; text-align: center; gap: 6px;">
                        <i data-lucide="users" style="width: 20px; height: 20px; color: #6366f1;"></i>
                        <span style="font-size: 13px;">Manage Roles</span>
                        <small style="color: #64748b; font-size: 11px;">Active Members</small>
                    </a>
                    <button type="button" class="community-admin-btn community-admin-btn--secondary" style="height: auto; padding: 14px; flex-direction: column; text-align: center; gap: 6px;" data-share-modal-open="{{ $community->id }}">
                        <i data-lucide="share-2" style="width: 20px; height: 20px; color: #10b981;"></i>
                        <span style="font-size: 13px;">Share Link</span>
                        <small style="color: #64748b; font-size: 11px;">Invite Friends</small>
                    </button>
                </div>
            </section>
        </div>

    <!-- Tab 2: Reports Queue -->
    @elseif ($activeTab === 'reports')
        <section class="community-admin-card">
            <header class="community-admin-card__header">
                <div>
                    <h2 class="community-admin-card__title">
                        <i data-lucide="flag" aria-hidden="true" style="color: #ef4444;"></i> Moderation Reports Queue
                    </h2>
                    <p class="community-admin-card__subtitle">Review and resolve safety reports submitted by community members.</p>
                </div>
            </header>

            <div class="community-admin-table-wrap">
                <table class="community-admin-table">
                    <thead>
                        <tr>
                            <th>Reporter</th>
                            <th>Target Content</th>
                            <th>Reason & Details</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($reports as $report)
                            <tr>
                                <td>
                                    <strong style="color: #0f172a;">{{ $report->reporter->name ?? 'Member' }}</strong>
                                </td>
                                <td>
                                    <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 8px; font-size: 11.5px; font-weight: 700;">
                                        {{ ucfirst($report->reportable_type) }} #{{ $report->reportable_id }}
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #1e293b; display: block;">{{ $report->reason }}</strong>
                                    @if ($report->details)
                                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">{{ $report->details }}</div>
                                    @endif
                                </td>
                                <td>
                                    <span style="font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 20px;
                                        @if ($report->status === 'pending') background: #ffe4e6; color: #e11d48;
                                        @elseif ($report->status === 'resolved' || $report->status === 'approved') background: #dcfce7; color: #166534;
                                        @else background: #f1f5f9; color: #64748b;
                                        @endif">
                                        {{ ucfirst($report->status) }}
                                    </span>
                                </td>
                                <td style="color: #64748b;">
                                    {{ $report->created_at->diffForHumans() }}
                                </td>
                                <td style="text-align: right;">
                                    @if ($report->status === 'pending')
                                        <div style="display: inline-flex; gap: 6px;">
                                            <button
                                                type="button"
                                                class="community-admin-btn community-admin-btn--primary community-admin-btn--sm"
                                                data-community-report-handle="{{ route('member.community.reports.handle', [$community, $report]) }}"
                                                data-status="resolved"
                                            >
                                                Resolve
                                            </button>
                                            <button
                                                type="button"
                                                class="community-admin-btn community-admin-btn--danger community-admin-btn--sm"
                                                data-community-report-handle="{{ route('member.community.reports.handle', [$community, $report]) }}"
                                                data-status="approved"
                                                data-delete-content="1"
                                            >
                                                Delete Content
                                            </button>
                                        </div>
                                    @else
                                        <span style="font-size: 12px; color: #94a3b8;">Resolved by {{ $report->resolvedBy->name ?? 'Admin' }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" style="padding: 36px; text-align: center; color: #64748b;">
                                    <i data-lucide="check-circle" style="width: 32px; height: 32px; color: #10b981; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;"></i>
                                    <strong>No pending moderation reports.</strong>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($reports, 'hasPages') && $reports->hasPages())
                <div class="pagination-wrapper" style="margin-top: 20px;">
                    {{ $reports->links('member.search.pagination') }}
                </div>
            @endif
        </section>

    <!-- Tab 3: Audit Logs -->
    @elseif ($activeTab === 'audit_logs')
        <section class="community-admin-card">
            <header class="community-admin-card__header">
                <div>
                    <h2 class="community-admin-card__title">
                        <i data-lucide="shield-alert" aria-hidden="true"></i> Moderation Audit Logs
                    </h2>
                    <p class="community-admin-card__subtitle">Complete historical record of admin actions and governance changes.</p>
                </div>
            </header>

            <div class="community-admin-table-wrap">
                <table class="community-admin-table">
                    <thead>
                        <tr>
                            <th>Actor</th>
                            <th>Action Recorded</th>
                            <th>Target Reference</th>
                            <th>Timestamp</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($auditLogs as $log)
                            <tr>
                                <td>
                                    <strong style="color: #0f172a;">{{ $log->actor->name ?? 'System' }}</strong>
                                </td>
                                <td>
                                    <span style="background: #eff6ff; color: #2563eb; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700;">
                                        {{ $log->action }}
                                    </span>
                                </td>
                                <td style="color: #64748b;">
                                    {{ $log->target_type ? $log->target_type . ' #' . $log->target_id : 'N/A' }}
                                </td>
                                <td style="color: #64748b;">
                                    {{ $log->created_at->format('M d, Y H:i:s') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="padding: 36px; text-align: center; color: #64748b;">
                                    <i data-lucide="info" style="width: 32px; height: 32px; color: #94a3b8; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;"></i>
                                    No audit log entries recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if (method_exists($auditLogs, 'hasPages') && $auditLogs->hasPages())
                <div class="pagination-wrapper" style="margin-top: 20px;">
                    {{ $auditLogs->links('member.search.pagination') }}
                </div>
            @endif
        </section>

    <!-- Tab 4: Banned Members -->
    @elseif ($activeTab === 'bans')
        <section class="community-admin-card">
            <header class="community-admin-card__header">
                <div>
                    <h2 class="community-admin-card__title">
                        <i data-lucide="user-x" aria-hidden="true" style="color: #64748b;"></i> Banned Members
                    </h2>
                    <p class="community-admin-card__subtitle">Members restricted from joining or interacting with this community.</p>
                </div>
            </header>

            <div class="community-admin-table-wrap">
                <table class="community-admin-table">
                    <thead>
                        <tr>
                            <th>Banned Member</th>
                            <th>Banned By</th>
                            <th>Reason</th>
                            <th>Ban Duration</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bannedMembers as $ban)
                            <tr>
                                <td>
                                    <strong style="color: #0f172a;">{{ $ban->member->name ?? 'Member' }}</strong>
                                </td>
                                <td style="color: #64748b;">
                                    {{ $ban->bannedBy->name ?? 'Admin' }}
                                </td>
                                <td>
                                    {{ $ban->reason }}
                                </td>
                                <td>
                                    <span style="background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 8px; font-size: 12px; font-weight: 700;">
                                        {{ $ban->is_permanent ? 'Permanent' : $ban->expires_at?->format('M d, Y') }}
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    @if ($isAdmin)
                                        <button
                                            type="button"
                                            class="community-admin-btn community-admin-btn--secondary community-admin-btn--sm"
                                            data-community-unban-btn="{{ route('member.community.moderation.unban', $community) }}"
                                            data-member-id="{{ $ban->member_id }}"
                                        >
                                            Unban Member
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: 36px; text-align: center; color: #64748b;">
                                    <i data-lucide="smile" style="width: 32px; height: 32px; color: #10b981; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;"></i>
                                    No banned members in this community.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    <!-- Tab 5: Muted Members -->
    @elseif ($activeTab === 'mutes')
        <section class="community-admin-card">
            <header class="community-admin-card__header">
                <div>
                    <h2 class="community-admin-card__title">
                        <i data-lucide="volume-x" aria-hidden="true" style="color: #8b5cf6;"></i> Muted Members
                    </h2>
                    <p class="community-admin-card__subtitle">Members restricted from posting new content or comments.</p>
                </div>
            </header>

            <div class="community-admin-table-wrap">
                <table class="community-admin-table">
                    <thead>
                        <tr>
                            <th>Muted Member</th>
                            <th>Muted By</th>
                            <th>Reason</th>
                            <th>Mute Expires</th>
                            <th style="text-align: right;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($mutedMembers as $mute)
                            <tr>
                                <td>
                                    <strong style="color: #0f172a;">{{ $mute->member->name ?? 'Member' }}</strong>
                                </td>
                                <td style="color: #64748b;">
                                    {{ $mute->mutedBy->name ?? 'Moderator' }}
                                </td>
                                <td>
                                    {{ $mute->reason }}
                                </td>
                                <td style="color: #8b5cf6; font-weight: 700;">
                                    {{ $mute->expires_at->diffForHumans() }}
                                </td>
                                <td style="text-align: right;">
                                    <button
                                        type="button"
                                        class="community-admin-btn community-admin-btn--secondary community-admin-btn--sm"
                                        data-community-unmute-btn="{{ route('member.community.moderation.unmute', $community) }}"
                                        data-member-id="{{ $mute->member_id }}"
                                    >
                                        Unmute
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" style="padding: 36px; text-align: center; color: #64748b;">
                                    <i data-lucide="volume-2" style="width: 32px; height: 32px; color: #10b981; margin-bottom: 8px; display: block; margin-left: auto; margin-right: auto;"></i>
                                    No muted members in this community.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

    <!-- Tab 6: Governance & Settings -->
    @elseif ($activeTab === 'settings' && $isAdmin)
        <section class="community-admin-card">
            <header class="community-admin-card__header">
                <div>
                    <h2 class="community-admin-card__title">
                        <i data-lucide="settings" aria-hidden="true"></i> Community Governance & Settings
                    </h2>
                    <p class="community-admin-card__subtitle">Configure community name, category, posting rules, join approval, and guidelines.</p>
                </div>
            </header>

            <form method="POST" action="{{ route('member.community.settings.update', $community) }}" data-community-settings-form>
                @csrf
                @method('PUT')

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;" class="community-admin-form-group">
                    <div>
                        <label class="community-admin-label">Community Name</label>
                        <input type="text" name="name" class="community-admin-input" value="{{ $community->name }}" required>
                    </div>

                    <div>
                        <label class="community-admin-label">Category</label>
                        <select name="category" class="community-admin-select">
                            @foreach (\App\Models\Community::CATEGORIES as $cat)
                                <option value="{{ $cat }}" {{ $community->category === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;" class="community-admin-form-group">
                    <div>
                        <label class="community-admin-label">Posting Permissions</label>
                        <select name="posting_permissions" class="community-admin-select">
                            <option value="everyone" {{ ($community->posting_permissions ?? 'everyone') === 'everyone' ? 'selected' : '' }}>Everyone</option>
                            <option value="members_only" {{ ($community->posting_permissions ?? '') === 'members_only' ? 'selected' : '' }}>Members Only</option>
                            <option value="moderators_admins" {{ ($community->posting_permissions ?? '') === 'moderators_admins' ? 'selected' : '' }}>Moderators & Admins Only</option>
                            <option value="admins_only" {{ ($community->posting_permissions ?? '') === 'admins_only' ? 'selected' : '' }}>Admins Only</option>
                            <option value="owner_only" {{ ($community->posting_permissions ?? '') === 'owner_only' ? 'selected' : '' }}>Owner Only</option>
                        </select>
                    </div>

                    <div>
                        <label class="community-admin-label">Join Approval Mode</label>
                        <select name="join_approval_mode" class="community-admin-select">
                            <option value="instant" {{ ($community->join_approval_mode ?? 'instant') === 'instant' ? 'selected' : '' }}>Instant Join</option>
                            <option value="approval_required" {{ ($community->join_approval_mode ?? '') === 'approval_required' ? 'selected' : '' }}>Approval Required by Admin</option>
                            <option value="invite_only" {{ ($community->join_approval_mode ?? '') === 'invite_only' ? 'selected' : '' }}>Invite Only</option>
                        </select>
                    </div>
                </div>

                <div class="community-admin-form-group">
                    <label class="community-admin-label">Community Description</label>
                    <textarea name="description" class="community-admin-textarea" rows="4">{{ $community->description }}</textarea>
                </div>

                <div class="community-admin-form-group" style="margin-bottom: 32px;">
                    <label class="community-admin-label">Community Guidelines & Rules</label>
                    <textarea name="rules" class="community-admin-textarea" rows="5" placeholder="1. Be respectful&#10;2. No spam or self-promotion">{{ $community->rules }}</textarea>
                </div>

                <button type="submit" class="community-admin-btn community-admin-btn--primary">
                    <i data-lucide="save" aria-hidden="true"></i> Save Governance Settings
                </button>
            </form>
        </section>

        @if ($isOwner)
            <section class="community-admin-card" style="border-color: #fecaca; background: #fffdfd;">
                <header class="community-admin-card__header" style="border-bottom-color: #fee2e2;">
                    <div>
                        <h2 class="community-admin-card__title" style="color: #dc2626;">
                            <i data-lucide="shield-alert" aria-hidden="true" style="color: #dc2626;"></i> Transfer Community Ownership
                        </h2>
                        <p class="community-admin-card__subtitle">Transfer full administrative ownership of {{ $community->name }} to another active member.</p>
                    </div>
                </header>

                <form method="POST" action="{{ route('member.community.transfer-ownership', $community) }}" data-community-transfer-form>
                    @csrf
                    <div style="display: flex; gap: 16px; align-items: center; max-width: 540px; flex-wrap: wrap;">
                        <select name="new_owner_id" class="community-admin-select" style="flex: 1; min-width: 220px;" required>
                            <option value="">Select New Owner...</option>
                            @foreach ($community->acceptedMembers as $membership)
                                @if ((int) $membership->member_id !== (int) $community->owner_id)
                                    <option value="{{ $membership->member_id }}">{{ $membership->member->name ?? 'Member' }} ({{ ucfirst($membership->role) }})</option>
                                @endif
                            @endforeach
                        </select>
                        <button type="submit" class="community-admin-btn community-admin-btn--danger">
                            Transfer Ownership
                        </button>
                    </div>
                </form>
            </section>
        @endif
    @endif
</div>

@push('modals')
@include('member.community.partials.share-modal', ['community' => $community])
@endpush
@endsection

@push('scripts')
<script src="{{ asset('member_assets/js/member-community.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>
@endpush
