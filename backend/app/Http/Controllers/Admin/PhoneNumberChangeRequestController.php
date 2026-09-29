<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\PhoneNumberChangeRequest;
use App\Notifications\SystemNotification;
use App\Services\MemberPhoneNumberService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PhoneNumberChangeRequestController extends Controller
{
    /**
     * Display a listing of Phone Number Change Requests with filters and metrics.
     */
    public function index(Request $request)
    {
        $status = $request->input('status', 'all');
        $search = trim((string) $request->input('q'));
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = PhoneNumberChangeRequest::with(['member', 'approver', 'rejecter']);

        if ($status && in_array($status, ['pending', 'approved', 'rejected'])) {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('old_phone', 'like', "%{$search}%")
                  ->orWhere('new_phone', 'like', "%{$search}%")
                  ->orWhereHas('member', function ($mq) use ($search) {
                      $mq->where('name', 'like', "%{$search}%")
                         ->orWhere('user_id', 'like', "%{$search}%")
                         ->orWhere('email', 'like', "%{$search}%");
                  });
            });
        }

        if (!empty($dateFrom)) {
            $startDate = Carbon::parse($dateFrom)->startOfDay();
            $endDate = !empty($dateTo) ? Carbon::parse($dateTo)->endOfDay() : Carbon::parse($dateFrom)->endOfDay();
            $query->whereBetween('created_at', [$startDate, $endDate]);
        } elseif (!empty($dateTo)) {
            $query->where('created_at', '<=', Carbon::parse($dateTo)->endOfDay());
        }

        if ($status === 'pending') {
            $query->orderBy('created_at', 'asc');
        } else {
            $query->orderBy('created_at', 'desc');
        }

        $perPage = $request->integer('per_page', 15);
        $requests = $query->paginate($perPage)->withQueryString();

        // Metrics Summary
        $totalCount = PhoneNumberChangeRequest::count();
        $pendingCount = PhoneNumberChangeRequest::where('status', 'pending')->count();
        $approvedCount = PhoneNumberChangeRequest::where('status', 'approved')->count();
        $rejectedCount = PhoneNumberChangeRequest::where('status', 'rejected')->count();

        return response()->json([
            'success' => true,
            'requests' => $requests,
            'totalCount' => $totalCount,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'rejectedCount' => $rejectedCount,
            'metrics' => [
                'totalCount' => $totalCount,
                'pendingCount' => $pendingCount,
                'approvedCount' => $approvedCount,
                'rejectedCount' => $rejectedCount,
            ],
        ]);
    }

    /**
     * Show details of a specific change request.
     */
    public function show($id)
    {
        $changeRequest = PhoneNumberChangeRequest::with(['member', 'approver', 'rejecter'])->findOrFail($id);

        return response()->json([
            'success' => true,
            'request' => $changeRequest,
        ]);
    }

    /**
     * Atomically approve a pending phone number change request.
     */
    public function approve(Request $request, $id)
    {
        return DB::transaction(function () use ($id) {
            /** @var PhoneNumberChangeRequest $changeRequest */
            $changeRequest = PhoneNumberChangeRequest::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($changeRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => "Request is already {$changeRequest->status} and cannot be approved again.",
                    'request' => $changeRequest,
                ], 422);
            }

            /** @var Member|null $member */
            $member = Member::where('id', $changeRequest->member_id)->lockForUpdate()->first();
            if (! $member) {
                return response()->json([
                    'success' => false,
                    'message' => 'Associated member account not found.',
                ], 404);
            }

            // Verify current phone matches old_phone
            if ($member->phone !== $changeRequest->old_phone) {
                return response()->json([
                    'success' => false,
                    'message' => 'Member current phone number does not match the old phone on this request.',
                ], 422);
            }

            // Verify new phone number is not taken by another member
            $duplicate = Member::where('phone', $changeRequest->new_phone)
                ->where('id', '!=', $member->id)
                ->exists();

            if ($duplicate) {
                return response()->json([
                    'success' => false,
                    'message' => 'The new phone number is already registered to another member account.',
                ], 422);
            }

            $now = now();
            $admin = auth('admin')->user();

            // 1. Update member with new phone and verify timestamp
            $member->update([
                'phone' => $changeRequest->new_phone,
                'mobile_verified_at' => $now,
            ]);

            // 2. Mark request as approved
            $changeRequest->update([
                'status' => 'approved',
                'approved_by' => $admin?->id,
                'approved_at' => $now,
            ]);

            // 3. Notify member
            try {
                $member->notify(new SystemNotification(
                    'Phone Number Change Approved',
                    "Your phone number has been updated to {$changeRequest->new_phone}. Your account remains verified.",
                    '/account/settings'
                ));
            } catch (\Throwable $e) {
                Log::warning('Failed to send phone change approval notification: ' . $e->getMessage());
            }

            Log::info('Admin approved phone number change request', [
                'change_request_id' => $changeRequest->id,
                'member_id' => $member->id,
                'user_id' => $member->user_id,
                'old_phone' => $changeRequest->old_phone,
                'new_phone' => $changeRequest->new_phone,
                'admin_id' => $admin?->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Phone number change request #{$changeRequest->id} for {$member->name} ({$member->user_id}) has been approved.",
                'request' => $changeRequest->fresh(['member', 'approver']),
                'member' => $member->fresh(),
            ]);
        });
    }

    /**
     * Atomically reject a pending phone number change request.
     */
    public function reject(Request $request, $id)
    {
        return DB::transaction(function () use ($id, $request) {
            /** @var PhoneNumberChangeRequest $changeRequest */
            $changeRequest = PhoneNumberChangeRequest::where('id', $id)->lockForUpdate()->firstOrFail();

            if ($changeRequest->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => "Request is already {$changeRequest->status} and cannot be rejected.",
                    'request' => $changeRequest,
                ], 422);
            }

            $reason = trim((string) $request->input('reason', 'Verification details could not be validated.'));
            $admin = auth('admin')->user();
            $now = now();

            $changeRequest->update([
                'status' => 'rejected',
                'rejected_by' => $admin?->id,
                'rejected_at' => $now,
                'rejection_reason' => $reason,
            ]);

            /** @var Member|null $member */
            $member = Member::find($changeRequest->member_id);
            if ($member) {
                try {
                    $member->notify(new SystemNotification(
                        'Phone Number Change Request Update',
                        "Your phone number change request was rejected. Reason: {$reason}. Your current verified number remains active.",
                        '/account/settings'
                    ));
                } catch (\Throwable $e) {
                    Log::warning('Failed to send phone change rejection notification: ' . $e->getMessage());
                }
            }

            Log::info('Admin rejected phone number change request', [
                'change_request_id' => $changeRequest->id,
                'member_id' => $changeRequest->member_id,
                'admin_id' => $admin?->id,
                'reason' => $reason,
            ]);

            return response()->json([
                'success' => true,
                'message' => "Phone number change request #{$changeRequest->id} has been rejected.",
                'request' => $changeRequest->fresh(['member', 'rejecter']),
            ]);
        });
    }
}
