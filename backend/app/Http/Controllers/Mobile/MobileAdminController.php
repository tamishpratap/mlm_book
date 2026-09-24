<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\AdDeposit;
use App\Models\Admin;
use App\Models\Member;
use App\Models\ReportedPost;
use App\Models\Setting;
use App\Services\BscTransactionVerifierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MobileAdminController extends Controller
{
    /**
     * Mobile Admin Dashboard overview metrics.
     */
    public function dashboard(Request $request): JsonResponse
    {
        $totalMembers = Member::count();
        $activeMembers = Member::whereNotNull('mobile_verified_at')->whereNull('blocked_at')->count();
        $pendingMembers = Member::whereNull('mobile_verified_at')->count();
        $blockedMembers = Member::whereNotNull('blocked_at')->count();

        $activeCampaigns = AdCampaign::where('status', AdCampaign::STATUS_ACTIVE)->count();
        $pendingCampaigns = AdCampaign::where('status', AdCampaign::STATUS_PENDING_REVIEW)->count();

        $pendingDeposits = AdDeposit::where('status', AdDeposit::STATUS_PENDING)->count();
        $pendingDepositsVolume = (float) AdDeposit::where('status', AdDeposit::STATUS_PENDING)->sum('submitted_amount');
        $approvedDepositsVolume = (float) AdDeposit::where('status', AdDeposit::STATUS_APPROVED)->sum('submitted_amount');

        $reportsCount = ReportedPost::where('status', 'pending')->count();

        return response()->json([
            'success' => true,
            'metrics' => [
                'total_members' => $totalMembers,
                'active_members' => $activeMembers,
                'pending_members' => $pendingMembers,
                'blocked_members' => $blockedMembers,
                'active_campaigns' => $activeCampaigns,
                'pending_campaigns' => $pendingCampaigns,
                'pending_deposits_count' => $pendingDeposits,
                'pending_deposits_volume' => round($pendingDepositsVolume, 2),
                'approved_deposits_volume' => round($approvedDepositsVolume, 2),
                'pending_reports_count' => $reportsCount,
            ],
        ]);
    }

    /**
     * Members management list.
     */
    public function members(Request $request): JsonResponse
    {
        $query = Member::query()->latest();

        if ($request->filled('filter')) {
            $f = $request->filter;
            if ($f === 'active') {
                $query->whereNotNull('mobile_verified_at')->whereNull('blocked_at');
            } elseif ($f === 'pending') {
                $query->whereNull('mobile_verified_at');
            } elseif ($f === 'blocked') {
                $query->whereNotNull('blocked_at');
            }
        }

        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('user_id', 'like', "%{$q}%");
            });
        }

        $members = $query->paginate(20)->through(function (Member $m) {
            return [
                'id' => $m->id,
                'name' => $m->name,
                'user_id' => $m->user_id,
                'email' => $m->email,
                'avatar_url' => $m->avatar_url,
                'status' => $m->status,
                'is_verified' => $m->isMobileVerified(),
                'is_blocked' => $m->isBlocked(),
                'direct_referrals' => (int) $m->direct_referral_count,
                'ad_balance' => (float) $m->ad_balance,
                'reward_balance' => (float) $m->reward_balance,
                'joined_at' => $m->created_at?->format('M d, Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'members' => $members,
        ]);
    }

    /**
     * Block or unblock member.
     */
    public function toggleMemberBlock(Request $request, int $memberId): JsonResponse
    {
        $member = Member::findOrFail($memberId);

        if ($member->isBlocked()) {
            $member->update(['blocked_at' => null]);
            $msg = 'Member unblocked successfully.';
            $isBlocked = false;
        } else {
            $member->update(['blocked_at' => now()]);
            $msg = 'Member blocked successfully.';
            $isBlocked = true;
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'is_blocked' => $isBlocked,
        ]);
    }

    /**
     * Ad campaigns management list for admin.
     */
    public function campaigns(Request $request): JsonResponse
    {
        $query = AdCampaign::with(['businessPage:id,name,slug', 'member:id,name,user_id'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $campaigns = $query->paginate(15)->through(function (AdCampaign $c) {
            return [
                'id' => $c->id,
                'campaign_id' => $c->campaign_id,
                'campaign_name' => $c->campaign_name,
                'business_name' => $c->businessPage?->name ?? 'Page',
                'advertiser_name' => $c->member?->name ?? 'Advertiser',
                'budget' => (float) $c->budget,
                'remaining_amount' => (float) $c->remaining_amount,
                'spent_amount' => (float) $c->spent_amount,
                'status' => $c->status,
                'approval_status' => $c->approval_status,
                'rejection_reason' => $c->rejection_reason,
                'created_at' => $c->created_at?->format('M d, Y'),
            ];
        });

        return response()->json([
            'success' => true,
            'campaigns' => $campaigns,
        ]);
    }

    /**
     * Approve or reject an ad campaign.
     */
    public function reviewCampaign(Request $request, int $campaignId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();
        $campaign = AdCampaign::findOrFail($campaignId);

        $validated = $request->validate([
            'action' => ['required', 'in:approve,reject'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        if ($validated['action'] === 'approve') {
            $campaign->update([
                'approval_status' => AdCampaign::APPROVAL_APPROVED,
                'status' => AdCampaign::STATUS_ACTIVE,
                'approved_by' => $admin?->id,
                'approved_at' => now(),
            ]);
            $msg = 'Campaign approved and set to active.';
        } else {
            $campaign->update([
                'approval_status' => AdCampaign::APPROVAL_REJECTED,
                'status' => AdCampaign::STATUS_REJECTED,
                'rejection_reason' => $validated['reason'] ?? 'Not compliant with advertising guidelines.',
            ]);
            $msg = 'Campaign rejected.';
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'status' => $campaign->status,
            'approval_status' => $campaign->approval_status,
        ]);
    }

    /**
     * Crypto deposits review list for admin.
     */
    public function deposits(Request $request): JsonResponse
    {
        $query = AdDeposit::with(['member:id,name,user_id,email'])->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $deposits = $query->paginate(20)->through(function (AdDeposit $d) {
            return [
                'id' => $d->id,
                'deposit_id' => $d->deposit_id,
                'member_name' => $d->member?->name ?? 'User',
                'member_user_id' => $d->member?->user_id ?? '',
                'amount' => (float) ($d->submitted_amount ?? $d->expected_usd_amount ?? 0),
                'currency' => 'USDT',
                'tx_hash' => $d->transaction_hash,
                'status' => $d->status,
                'verification_status' => $d->verification_status,
                'submitted_at' => $d->submitted_at?->format('M d, Y H:i'),
            ];
        });

        return response()->json([
            'success' => true,
            'deposits' => $deposits,
        ]);
    }

    /**
     * Manually approve deposit and credit member's ad balance.
     */
    public function approveDeposit(Request $request, int $depositId): JsonResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();
        $deposit = AdDeposit::findOrFail($depositId);

        if ($deposit->status === AdDeposit::STATUS_APPROVED) {
            return response()->json(['message' => 'Deposit is already approved.'], 422);
        }

        $amount = (float) ($deposit->submitted_amount ?? $deposit->expected_usd_amount ?? 0);

        $deposit->update([
            'status' => AdDeposit::STATUS_APPROVED,
            'verified_amount' => $amount,
            'verified_at' => now(),
            'verified_by' => $admin?->id,
            'verification_status' => 'manually_approved',
        ]);

        if ($deposit->member) {
            $deposit->member->creditAdBalance($amount);
        }

        return response()->json([
            'success' => true,
            'message' => "Deposit approved. \${$amount} USDT credited to member's ad balance.",
        ]);
    }

    /**
     * 1-Tap on-chain reverify of transaction hash via BSC RPC.
     */
    public function reverifyDeposit(Request $request, int $depositId, BscTransactionVerifierService $verifier): JsonResponse
    {
        $deposit = AdDeposit::findOrFail($depositId);
        $adminWallet = Setting::get('deposit_crypto_wallet_address', '');

        if (empty($adminWallet)) {
            return response()->json(['message' => 'Admin wallet address is not configured in settings.'], 422);
        }

        $expectedAmount = (float) ($deposit->submitted_amount ?? $deposit->expected_usd_amount ?? 0);
        $result = $verifier->verifyTransaction($deposit->transaction_hash, $expectedAmount, $adminWallet);

        if ($result['verified'] ?? false) {
            $deposit->update([
                'status' => AdDeposit::STATUS_APPROVED,
                'verified_amount' => $result['verified_amount'] ?? $expectedAmount,
                'verified_at' => now(),
                'verification_status' => 'verified_onchain',
            ]);
            if ($deposit->member) {
                $deposit->member->creditAdBalance($expectedAmount);
            }

            return response()->json([
                'success' => true,
                'message' => 'On-chain verification PASSED! Deposit approved and funds credited.',
                'verified' => true,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'On-chain verification FAILED: ' . ($result['message'] ?? 'Transaction not confirmed or invalid.'),
            'verified' => false,
        ], 422);
    }

    /**
     * Moderation reports queue.
     */
    public function reports(Request $request): JsonResponse
    {
        $reports = ReportedPost::with(['post', 'member:id,name,user_id'])->latest()->paginate(15)->through(function (ReportedPost $r) {
            return [
                'id' => $r->id,
                'reporter_name' => $r->member?->name ?? 'Member',
                'reason' => $r->reason ?? 'Spam/Inappropriate',
                'post_snippet' => $r->post?->body ? \Illuminate\Support\Str::limit($r->post->body, 60) : 'Post Media',
                'status' => $r->status ?? 'pending',
                'created_at' => $r->created_at?->diffForHumans(),
            ];
        });

        return response()->json([
            'success' => true,
            'reports' => $reports,
        ]);
    }
}
