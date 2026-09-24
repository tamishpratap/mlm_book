<?php

namespace App\Services;

use App\Models\Member;

class ReferralRelationshipValidator
{
    public const DIRECT_REVERSE_MESSAGE = 'You have already introduced this user; they cannot be set as your introducer.';

    public const SELF_REFERRAL_MESSAGE = 'You cannot use your own ID as your introducer.';

    public const CYCLE_MESSAGE = 'Selecting this introducer would create an invalid circular referral relationship.';

    public const NOT_FOUND_MESSAGE = 'The selected Introducer ID does not exist.';

    public const UNVERIFIED_MESSAGE = 'This Member is not eligible to be an introducer until their mobile number is verified.';

    public const BLOCKED_MESSAGE = 'The selected Introducer account is suspended or blocked.';

    public function __construct(
        protected MemberUserIdService $userIdService,
    ) {}

    /**
     * Validate whether a candidate introducer can be assigned to the subject member.
     *
     * @param Member|string|null $subject The member or prospective user_id being assigned
     * @param Member|string $candidate The candidate introducer or candidate user_id
     * @param string|null $subjectEmail Optional email of the subject (for signup pre-validation)
     * @return array{valid: bool, code: string, message: string, introducer: ?Member}
     */
    public function validate(mixed $subject, mixed $candidate, ?string $subjectEmail = null): array
    {
        // 0. Upfront Self-referral check by user_id
        $subjectUserId = $subject instanceof Member ? $subject->user_id : (is_string($subject) ? $subject : null);
        $candidateUserId = $candidate instanceof Member ? $candidate->user_id : (is_string($candidate) ? $candidate : null);

        if (filled($subjectUserId) && filled($candidateUserId)) {
            $cleanSubject = $this->userIdService->normalize((string) $subjectUserId);
            $cleanCandidate = $this->userIdService->normalize((string) $candidateUserId);
            if (strtolower($cleanSubject) === strtolower($cleanCandidate)) {
                return [
                    'valid' => false,
                    'code' => 'SELF_REFERRAL',
                    'message' => self::SELF_REFERRAL_MESSAGE,
                    'introducer' => null,
                ];
            }
        }

        // 1. Resolve candidate member
        $candidateMember = null;
        if ($candidate instanceof Member) {
            $candidateMember = $candidate;
        } elseif (is_string($candidate) && filled($candidate)) {
            $cleanCandidateId = $this->userIdService->normalize($candidate);
            $candidateMember = Member::where('user_id', $cleanCandidateId)->first();
            if (! $candidateMember) {
                return [
                    'valid' => false,
                    'code' => 'NOT_FOUND',
                    'message' => self::NOT_FOUND_MESSAGE,
                    'introducer' => null,
                ];
            }
        } else {
            return [
                'valid' => false,
                'code' => 'NOT_FOUND',
                'message' => self::NOT_FOUND_MESSAGE,
                'introducer' => null,
            ];
        }

        // 2. Eligibility checks on candidate
        if (! $candidateMember->isMobileVerified()) {
            return [
                'valid' => false,
                'code' => 'UNVERIFIED',
                'message' => self::UNVERIFIED_MESSAGE,
                'introducer' => $candidateMember,
            ];
        }

        if ($candidateMember->isBlocked()) {
            return [
                'valid' => false,
                'code' => 'BLOCKED',
                'message' => self::BLOCKED_MESSAGE,
                'introducer' => $candidateMember,
            ];
        }

        // 3. Self-referral check
        if ($subject instanceof Member) {
            if ($subject->id === $candidateMember->id || strtolower($subject->user_id) === strtolower($candidateMember->user_id)) {
                return [
                    'valid' => false,
                    'code' => 'SELF_REFERRAL',
                    'message' => self::SELF_REFERRAL_MESSAGE,
                    'introducer' => $candidateMember,
                ];
            }
        } elseif (is_string($subject) && filled($subject)) {
            $cleanSubject = $this->userIdService->normalize($subject);
            if (strtolower($cleanSubject) === strtolower($candidateMember->user_id)) {
                return [
                    'valid' => false,
                    'code' => 'SELF_REFERRAL',
                    'message' => self::SELF_REFERRAL_MESSAGE,
                    'introducer' => $candidateMember,
                ];
            }
        }

        if ($subjectEmail && strtolower(trim($subjectEmail)) === strtolower(trim($candidateMember->email))) {
            return [
                'valid' => false,
                'code' => 'SELF_REFERRAL',
                'message' => self::SELF_REFERRAL_MESSAGE,
                'introducer' => $candidateMember,
            ];
        }

        // 4. If subject is an existing member, perform Direct Reverse and Ancestor/Descendant Cycle checks
        $subjectMember = null;
        if ($subject instanceof Member) {
            $subjectMember = $subject;
        } elseif (is_string($subject) && filled($subject)) {
            $subjectMember = Member::where('user_id', $this->userIdService->normalize($subject))->first();
        }

        if ($subjectMember) {
            // Direct Reverse check: Did subject directly introduce candidate?
            if ($candidateMember->introducer_id && strtolower($candidateMember->introducer_id) === strtolower($subjectMember->user_id)) {
                return [
                    'valid' => false,
                    'code' => 'DIRECT_REVERSE',
                    'message' => self::DIRECT_REVERSE_MESSAGE,
                    'introducer' => $candidateMember,
                ];
            }

            // Also check direct referrals of subject
            if ($subjectMember->directReferrals()->where('user_id', $candidateMember->user_id)->exists()) {
                return [
                    'valid' => false,
                    'code' => 'DIRECT_REVERSE',
                    'message' => self::DIRECT_REVERSE_MESSAGE,
                    'introducer' => $candidateMember,
                ];
            }

            // Ancestor / Descendant Cycle check:
            // Walk up the candidate's ancestry chain. If we encounter subjectMember, assigning candidate to subject would create a cycle.
            $visited = [];
            $currIntroducerId = $candidateMember->introducer_id;
            $depth = 0;
            $maxDepth = 100;

            while ($currIntroducerId && $depth < $maxDepth) {
                $cleanCurrent = strtolower($currIntroducerId);
                if (isset($visited[$cleanCurrent])) {
                    // Prevent infinite loop if dirty historical cycle exists in database
                    break;
                }
                $visited[$cleanCurrent] = true;

                if ($cleanCurrent === strtolower($subjectMember->user_id)) {
                    // Subject is in candidate's ancestry
                    if ($depth === 0) {
                        return [
                            'valid' => false,
                            'code' => 'DIRECT_REVERSE',
                            'message' => self::DIRECT_REVERSE_MESSAGE,
                            'introducer' => $candidateMember,
                        ];
                    }

                    return [
                        'valid' => false,
                        'code' => 'CYCLE',
                        'message' => self::CYCLE_MESSAGE,
                        'introducer' => $candidateMember,
                    ];
                }

                $parent = Member::where('user_id', $currIntroducerId)->first(['id', 'user_id', 'introducer_id']);
                $currIntroducerId = $parent ? $parent->introducer_id : null;
                $depth++;
            }
        }

        // All checks passed!
        return [
            'valid' => true,
            'code' => 'VALID',
            'message' => "Introduced by {$candidateMember->name}",
            'introducer' => $candidateMember,
        ];
    }
}
