<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\Community;
use App\Models\CommunityAuditLog;
use App\Models\CommunityBan;
use App\Models\CommunityMember;
use App\Models\CommunityMute;
use App\Models\CommunityReport;
use App\Models\CommunityWarning;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class CommunityModerationController extends Controller
{
    public function adminPanel(Request $request, Community $community)
    {
        $member = auth('member')->user();

        if (! $community->isModerator($member->id)) {
            abort(403, 'Unauthorized access to Community Admin Panel.');
        }

        $activeTab = $request->query('tab', 'overview');

        // Overview Stats
        $memberCount = $community->acceptedMembers()->count();
        $postCount = Post::where('community_id', $community->id)->count();
        $pendingRequestsCount = $community->pendingMembers()->count();
        $pendingReportsCount = $community->reports()->where('status', 'pending')->count();
        $bannedCount = $community->bans()->count();
        $mutedCount = $community->mutes()->where('expires_at', '>', now())->count();

        // Data queries based on tab
        $reports = collect();
        if ($activeTab === 'reports') {
            $reports = $community->reports()
                ->with(['reporter', 'resolvedBy'])
                ->latest()
                ->paginate(15)
                ->withQueryString();
        }

        $auditLogs = collect();
        if ($activeTab === 'audit_logs') {
            $auditLogs = $community->auditLogs()
                ->with('actor')
                ->latest()
                ->paginate(20)
                ->withQueryString();
        }

        $bannedMembers = $community->bans()->with(['member', 'bannedBy'])->latest()->get();
        $mutedMembers = $community->mutes()->with(['member', 'mutedBy'])->where('expires_at', '>', now())->latest()->get();
        $warnings = $community->warnings()->with(['member', 'warnedBy'])->latest()->take(30)->get();

        $isOwner = $community->isOwner($member->id);
        $isAdmin = $community->isAdmin($member->id);

        $community->load(['acceptedMembers.member']);

        if ($request->expectsJson() || $request->ajax() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'community' => $community,
                'active_tab' => $activeTab,
                'member_count' => $memberCount,
                'post_count' => $postCount,
                'pending_requests_count' => $pendingRequestsCount,
                'pending_reports_count' => $pendingReportsCount,
                'banned_count' => $bannedCount,
                'muted_count' => $mutedCount,
                'reports' => $reports,
                'audit_logs' => $auditLogs,
                'banned_members' => $bannedMembers,
                'muted_members' => $mutedMembers,
                'warnings' => $warnings,
                'is_owner' => $isOwner,
                'is_admin' => $isAdmin,
            ]);
        }

        return view('member.community.admin-panel', compact(
            'community',
            'activeTab',
            'memberCount',
            'postCount',
            'pendingRequestsCount',
            'pendingReportsCount',
            'bannedCount',
            'mutedCount',
            'reports',
            'auditLogs',
            'bannedMembers',
            'mutedMembers',
            'warnings',
            'isOwner',
            'isAdmin'
        ));
    }

    public function banMember(Request $request, Community $community)
    {
        $actor = auth('member')->user();

        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'duration' => ['required', 'string', 'in:permanent,1d,7d,30d'],
        ]);

        $targetMember = CommunityMember::where('community_id', $community->id)
            ->where('member_id', $validated['member_id'])
            ->firstOrFail();

        $this->authorize('banMember', [$community, $targetMember]);

        $isPermanent = $validated['duration'] === 'permanent';
        $expiresAt = null;

        if (! $isPermanent) {
            $days = match ($validated['duration']) {
                '1d' => 1,
                '7d' => 7,
                '30d' => 30,
                default => 1,
            };
            $expiresAt = now()->addDays($days);
        }

        CommunityBan::updateOrCreate(
            [
                'community_id' => $community->id,
                'member_id' => $validated['member_id'],
            ],
            [
                'banned_by_id' => $actor->id,
                'reason' => $validated['reason'] ?? 'Violation of community guidelines.',
                'expires_at' => $expiresAt,
                'is_permanent' => $isPermanent,
            ]
        );

        $targetMember->update(['status' => 'blocked']);

        CommunityAuditLog::record(
            $community->id,
            $actor->id,
            'member.banned',
            'Member',
            $validated['member_id'],
            ['reason' => $validated['reason'] ?? null, 'duration' => $validated['duration']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Member banned from ' . $community->name . '.',
        ]);
    }

    public function unbanMember(Request $request, Community $community)
    {
        $actor = auth('member')->user();

        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
        ]);

        if (! $community->isAdmin($actor->id)) {
            return response()->json(['success' => false, 'message' => 'Only admins can unban members.'], 403);
        }

        CommunityBan::where('community_id', $community->id)
            ->where('member_id', $validated['member_id'])
            ->delete();

        $targetMember = CommunityMember::where('community_id', $community->id)
            ->where('member_id', $validated['member_id'])
            ->first();

        if ($targetMember) {
            $targetMember->update(['status' => 'accepted']);
        }

        CommunityAuditLog::record(
            $community->id,
            $actor->id,
            'member.unbanned',
            'Member',
            $validated['member_id']
        );

        return response()->json([
            'success' => true,
            'message' => 'Member unbanned successfully.',
        ]);
    }

    public function muteMember(Request $request, Community $community)
    {
        $actor = auth('member')->user();

        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'reason' => ['nullable', 'string', 'max:500'],
            'duration' => ['required', 'string', 'in:1h,12h,24h,3d,7d,30d'],
        ]);

        $targetMember = CommunityMember::where('community_id', $community->id)
            ->where('member_id', $validated['member_id'])
            ->firstOrFail();

        $this->authorize('muteMember', [$community, $targetMember]);

        $expiresAt = match ($validated['duration']) {
            '1h' => now()->addHour(),
            '12h' => now()->addHours(12),
            '24h' => now()->addDay(),
            '3d' => now()->addDays(3),
            '7d' => now()->addDays(7),
            '30d' => now()->addDays(30),
            default => now()->addDay(),
        };

        CommunityMute::updateOrCreate(
            [
                'community_id' => $community->id,
                'member_id' => $validated['member_id'],
            ],
            [
                'muted_by_id' => $actor->id,
                'reason' => $validated['reason'] ?? 'Temporary mute issued by moderator.',
                'expires_at' => $expiresAt,
            ]
        );

        CommunityAuditLog::record(
            $community->id,
            $actor->id,
            'member.muted',
            'Member',
            $validated['member_id'],
            ['duration' => $validated['duration'], 'expires_at' => $expiresAt->toDateTimeString()]
        );

        return response()->json([
            'success' => true,
            'message' => 'Member muted until ' . $expiresAt->format('M d, H:i') . '.',
        ]);
    }

    public function unmuteMember(Request $request, Community $community)
    {
        $actor = auth('member')->user();

        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
        ]);

        if (! $community->isModerator($actor->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to unmute members.'], 403);
        }

        CommunityMute::where('community_id', $community->id)
            ->where('member_id', $validated['member_id'])
            ->delete();

        CommunityAuditLog::record(
            $community->id,
            $actor->id,
            'member.unmuted',
            'Member',
            $validated['member_id']
        );

        return response()->json([
            'success' => true,
            'message' => 'Member unmuted.',
        ]);
    }

    public function warnMember(Request $request, Community $community)
    {
        $actor = auth('member')->user();

        $validated = $request->validate([
            'member_id' => ['required', 'integer', 'exists:members,id'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $targetMember = CommunityMember::where('community_id', $community->id)
            ->where('member_id', $validated['member_id'])
            ->firstOrFail();

        $this->authorize('warnMember', [$community, $targetMember]);

        CommunityWarning::create([
            'community_id' => $community->id,
            'member_id' => $validated['member_id'],
            'warned_by_id' => $actor->id,
            'reason' => trim($validated['reason']),
        ]);

        CommunityAuditLog::record(
            $community->id,
            $actor->id,
            'member.warned',
            'Member',
            $validated['member_id'],
            ['reason' => $validated['reason']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Official warning issued to member.',
        ]);
    }

    public function storeReport(Request $request, Community $community)
    {
        $reporter = auth('member')->user();

        $validated = $request->validate([
            'reportable_type' => ['required', 'string', 'in:post,comment,community'],
            'reportable_id' => ['required', 'integer'],
            'reason' => ['required', 'string', 'max:255'],
            'details' => ['nullable', 'string', 'max:1000'],
        ]);

        $commReport = CommunityReport::create([
            'community_id' => $community->id,
            'reporter_id' => $reporter->id,
            'reportable_type' => $validated['reportable_type'],
            'reportable_id' => $validated['reportable_id'],
            'reason' => $validated['reason'],
            'details' => $validated['details'] ?? null,
            'status' => 'pending',
        ]);

        \App\Services\AdminNotificationService::notify(
            title: 'Community Content Reported',
            message: sprintf('%s in "%s" was reported for "%s" by %s.', ucfirst($validated['reportable_type']), $community->name, $validated['reason'], $reporter->name),
            icon: 'flag',
            sourceType: 'community_report',
            sourceId: (string) $commReport->id,
            actionUrl: '/admin/reports/community/' . $commReport->id,
            metadata: ['community_id' => $community->id, 'reason' => $validated['reason']]
        );

        CommunityAuditLog::record(
            $community->id,
            $reporter->id,
            'report.created',
            ucfirst($validated['reportable_type']),
            $validated['reportable_id'],
            ['reason' => $validated['reason']]
        );

        return response()->json([
            'success' => true,
            'message' => 'Report submitted to community moderators for review.',
        ]);
    }

    public function handleReport(Request $request, Community $community, CommunityReport $report)
    {
        $actor = auth('member')->user();

        if (! $community->isModerator($actor->id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized to manage reports.'], 403);
        }

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:approved,rejected,resolved'],
            'delete_content' => ['nullable', 'boolean'],
        ]);

        $report->update([
            'status' => $validated['status'],
            'resolved_by_id' => $actor->id,
        ]);

        if (! empty($validated['delete_content']) && $report->reportable_type === 'post') {
            Post::where('id', $report->reportable_id)->where('community_id', $community->id)->delete();
        }

        CommunityAuditLog::record(
            $community->id,
            $actor->id,
            'report.' . $validated['status'],
            'CommunityReport',
            $report->id
        );

        return response()->json([
            'success' => true,
            'message' => 'Report marked as ' . $validated['status'] . '.',
        ]);
    }

    public function updateSettings(Request $request, Community $community)
    {
        $actor = auth('member')->user();

        if (! $community->isAdmin($actor->id)) {
            return response()->json(['success' => false, 'message' => 'Only admins can update community settings.'], 403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'category' => ['required', 'string'],
            'visibility' => ['required', 'string', 'in:public,private,invite_only,secret'],
            'posting_permissions' => ['required', 'string', 'in:everyone,members_only,admins_only,moderators_admins,owner_only'],
            'join_approval_mode' => ['required', 'string', 'in:instant,approval_required,invite_only'],
            'rules' => ['nullable', 'string', 'max:3000'],
        ]);

        $community->update($validated);

        CommunityAuditLog::record(
            $community->id,
            $actor->id,
            'settings.updated',
            'Community',
            $community->id,
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => 'Community moderation & governance settings updated successfully.',
        ]);
    }

    public function transferOwnership(Request $request, Community $community)
    {
        $actor = auth('member')->user();

        if (! $community->isOwner($actor->id)) {
            return response()->json(['success' => false, 'message' => 'Only the owner can transfer ownership.'], 403);
        }

        $validated = $request->validate([
            'new_owner_id' => ['required', 'integer', 'exists:members,id'],
        ]);

        $newOwnerMember = CommunityMember::where('community_id', $community->id)
            ->where('member_id', $validated['new_owner_id'])
            ->firstOrFail();

        $oldOwnerMember = CommunityMember::where('community_id', $community->id)
            ->where('member_id', $actor->id)
            ->first();

        $community->update(['owner_id' => $validated['new_owner_id']]);
        $newOwnerMember->update(['role' => 'owner', 'status' => 'accepted']);

        if ($oldOwnerMember) {
            $oldOwnerMember->update(['role' => 'admin']);
        }

        CommunityAuditLog::record(
            $community->id,
            $actor->id,
            'ownership.transferred',
            'Member',
            $validated['new_owner_id']
        );

        return response()->json([
            'success' => true,
            'message' => 'Community ownership successfully transferred.',
        ]);
    }
}
