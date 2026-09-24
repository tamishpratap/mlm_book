/**
 * MLM BOOK AI - Image Moderation Policy Configuration & Evaluator
 * Phase 4: Centralized Client-Side Content Moderation Policy
 *
 * Architecture Principles:
 * - Separation of Engine vs Policy: Engine returns raw model probabilities; this module determines platform action (ALLOW / BLOCK / REVIEW).
 * - Context Sensitivity: Different surfaces (Avatar vs Marketplace vs Feed) apply tailored thresholds based on platform risk tolerance.
 * - Standardized Decision Contract: Every evaluation returns an immutable, predictable decision object with user-facing explanations.
 * - Zero Unsafe Fallbacks: A scanner error or timeout never automatically converts into an ALLOW decision.
 */

/**
 * Standardized moderation policy actions
 */
export const POLICY_ACTIONS = Object.freeze({
  ALLOW: 'ALLOW',
  BLOCK: 'BLOCK',
  REVIEW: 'REVIEW',
});

/**
 * Moderation contexts mapped across the MLM BOOK AI platform
 */
export const MODERATION_CONTEXTS = Object.freeze({
  PROFILE_PHOTO: 'PROFILE_PHOTO',
  PROFILE_COVER: 'PROFILE_COVER',
  POST_IMAGE: 'POST_IMAGE',
  STORY_IMAGE: 'STORY_IMAGE',
  COMMUNITY_IMAGE: 'COMMUNITY_IMAGE',
  BUSINESS_IMAGE: 'BUSINESS_IMAGE',
  REVIEW_IMAGE: 'REVIEW_IMAGE',
  MARKETPLACE_IMAGE: 'MARKETPLACE_IMAGE',
  MESSAGE_IMAGE: 'MESSAGE_IMAGE',
  POST_VIDEO: 'POST_VIDEO',
  STORY_VIDEO: 'STORY_VIDEO',
  COMMUNITY_VIDEO: 'COMMUNITY_VIDEO',
  BUSINESS_VIDEO: 'BUSINESS_VIDEO',
  MARKETPLACE_VIDEO: 'MARKETPLACE_VIDEO',
});

/**
 * Standardized policy reason codes
 */
export const POLICY_REASON_CODES = Object.freeze({
  SAFE: 'SAFE',
  EXCEEDS_PORN_THRESHOLD: 'EXCEEDS_PORN_THRESHOLD',
  EXCEEDS_HENTAI_THRESHOLD: 'EXCEEDS_HENTAI_THRESHOLD',
  EXCEEDS_SEXY_THRESHOLD: 'EXCEEDS_SEXY_THRESHOLD',
  SCANNER_ERROR: 'SCANNER_ERROR',
  SCANNER_TIMEOUT: 'SCANNER_TIMEOUT',
  INVALID_INPUT: 'INVALID_INPUT',
  CANCELLED: 'CANCELLED',
});

/**
 * Centralized Platform Image Moderation Policies
 * Thresholds specify maximum permissible probability (0.0 - 1.0) before triggering an action.
 * Lower threshold = stricter enforcement.
 */
export const IMAGE_MODERATION_POLICIES = Object.freeze({
  // Profile Avatars: Strictest policy due to high visibility across comments, feeds, and directory
  [MODERATION_CONTEXTS.PROFILE_PHOTO]: Object.freeze({
    context: MODERATION_CONTEXTS.PROFILE_PHOTO,
    label: 'Profile Photo',
    thresholds: Object.freeze({
      Porn: 0.50,
      Hentai: 0.50,
      Sexy: 0.70,
    }),
    reviewThresholds: Object.freeze({
      Porn: 0.40,
      Hentai: 0.40,
      Sexy: 0.60,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'Profile photos must follow community guidelines and cannot contain explicit or suggestive imagery.',
  }),

  // Profile Header Cover: High visibility banner on member profiles
  [MODERATION_CONTEXTS.PROFILE_COVER]: Object.freeze({
    context: MODERATION_CONTEXTS.PROFILE_COVER,
    label: 'Profile Cover Photo',
    thresholds: Object.freeze({
      Porn: 0.50,
      Hentai: 0.50,
      Sexy: 0.75,
    }),
    reviewThresholds: Object.freeze({
      Porn: 0.40,
      Hentai: 0.40,
      Sexy: 0.65,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'Profile cover banners cannot contain explicit or sexually suggestive imagery.',
  }),

  // Standard Social Feed Posts: General member community feed baseline
  [MODERATION_CONTEXTS.POST_IMAGE]: Object.freeze({
    context: MODERATION_CONTEXTS.POST_IMAGE,
    label: 'Feed Post Image',
    thresholds: Object.freeze({
      Porn: 0.60,
      Hentai: 0.60,
      Sexy: 0.80,
    }),
    reviewThresholds: Object.freeze({
      Porn: 0.50,
      Hentai: 0.50,
      Sexy: 0.70,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'This image cannot be published because it appears to contain restricted adult content.',
  }),

  // Ephemeral Story Images: Public 24-hour media
  [MODERATION_CONTEXTS.STORY_IMAGE]: Object.freeze({
    context: MODERATION_CONTEXTS.STORY_IMAGE,
    label: 'Story Image',
    thresholds: Object.freeze({
      Porn: 0.60,
      Hentai: 0.60,
      Sexy: 0.80,
    }),
    reviewThresholds: Object.freeze({
      Porn: 0.50,
      Hentai: 0.50,
      Sexy: 0.70,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'Stories cannot contain adult or sexually explicit content.',
  }),

  // Community Group Posts & Banners: Public and semi-private groups
  [MODERATION_CONTEXTS.COMMUNITY_IMAGE]: Object.freeze({
    context: MODERATION_CONTEXTS.COMMUNITY_IMAGE,
    label: 'Community Image',
    thresholds: Object.freeze({
      Porn: 0.60,
      Hentai: 0.60,
      Sexy: 0.80,
    }),
    reviewThresholds: Object.freeze({
      Porn: 0.50,
      Hentai: 0.50,
      Sexy: 0.70,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'Community imagery must comply with community safety standards.',
  }),

  // Business Page Images: Commercial representation
  [MODERATION_CONTEXTS.BUSINESS_IMAGE]: Object.freeze({
    context: MODERATION_CONTEXTS.BUSINESS_IMAGE,
    label: 'Business Page Image',
    thresholds: Object.freeze({
      Porn: 0.55,
      Hentai: 0.55,
      Sexy: 0.75,
    }),
    reviewThresholds: Object.freeze({
      Porn: 0.45,
      Hentai: 0.45,
      Sexy: 0.65,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'Business page media must maintain professional and family-safe standards.',
  }),

  // Customer Review Photos
  [MODERATION_CONTEXTS.REVIEW_IMAGE]: Object.freeze({
    context: MODERATION_CONTEXTS.REVIEW_IMAGE,
    label: 'Customer Review Photo',
    thresholds: Object.freeze({
      Porn: 0.60,
      Hentai: 0.60,
      Sexy: 0.80,
    }),
    reviewThresholds: Object.freeze({
      Porn: 0.50,
      Hentai: 0.50,
      Sexy: 0.70,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'Product and business review photos cannot contain adult or explicit imagery.',
  }),

  // Marketplace Products: Commercial listings viewed by all buyers
  [MODERATION_CONTEXTS.MARKETPLACE_IMAGE]: Object.freeze({
    context: MODERATION_CONTEXTS.MARKETPLACE_IMAGE,
    label: 'Marketplace Product Photo',
    thresholds: Object.freeze({
      Porn: 0.50,
      Hentai: 0.50,
      Sexy: 0.75,
    }),
    reviewThresholds: Object.freeze({
      Porn: 0.40,
      Hentai: 0.40,
      Sexy: 0.65,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'Marketplace listings cannot display adult items, nudity, or sexually suggestive products.',
  }),

  // Direct 1-on-1 Messages: Private member communications
  [MODERATION_CONTEXTS.MESSAGE_IMAGE]: Object.freeze({
    context: MODERATION_CONTEXTS.MESSAGE_IMAGE,
    label: 'Direct Message Image Attachment',
    thresholds: Object.freeze({
      Porn: 0.65,
      Hentai: 0.65,
      Sexy: 0.85,
    }),
    reviewThresholds: Object.freeze({
      Porn: 0.55,
      Hentai: 0.55,
      Sexy: 0.75,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'This image cannot be sent because it violates our direct messaging safety policy.',
  }),

  // Video Moderation Policies (aligned with backend content_moderation.php)
  [MODERATION_CONTEXTS.POST_VIDEO]: Object.freeze({
    context: MODERATION_CONTEXTS.POST_VIDEO,
    label: 'Feed Post Video',
    thresholds: Object.freeze({
      Porn: 0.60,
      Hentai: 0.60,
      Sexy: 0.80,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'This video cannot be published because it appears to contain prohibited adult content.',
  }),

  [MODERATION_CONTEXTS.STORY_VIDEO]: Object.freeze({
    context: MODERATION_CONTEXTS.STORY_VIDEO,
    label: 'Story Video',
    thresholds: Object.freeze({
      Porn: 0.60,
      Hentai: 0.60,
      Sexy: 0.80,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'Stories cannot contain adult or sexually explicit video content.',
  }),

  [MODERATION_CONTEXTS.COMMUNITY_VIDEO]: Object.freeze({
    context: MODERATION_CONTEXTS.COMMUNITY_VIDEO,
    label: 'Community Video',
    thresholds: Object.freeze({
      Porn: 0.60,
      Hentai: 0.60,
      Sexy: 0.80,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'Community video must comply with community safety standards.',
  }),

  [MODERATION_CONTEXTS.BUSINESS_VIDEO]: Object.freeze({
    context: MODERATION_CONTEXTS.BUSINESS_VIDEO,
    label: 'Business Page Video',
    thresholds: Object.freeze({
      Porn: 0.55,
      Hentai: 0.55,
      Sexy: 0.75,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'Business page video media must maintain professional and family-safe standards.',
  }),

  [MODERATION_CONTEXTS.MARKETPLACE_VIDEO]: Object.freeze({
    context: MODERATION_CONTEXTS.MARKETPLACE_VIDEO,
    label: 'Marketplace Product Video',
    thresholds: Object.freeze({
      Porn: 0.50,
      Hentai: 0.50,
      Sexy: 0.75,
    }),
    blockAction: POLICY_ACTIONS.BLOCK,
    userViolationMessage: 'Marketplace product videos cannot display adult items, nudity, or sexually suggestive products.',
  }),
});

/**
 * Returns the policy configuration for a given context with fallback to POST_IMAGE.
 *
 * @param {string} context Moderation context identifier
 * @returns {Object} Policy configuration
 */
export function getPolicyForContext(context) {
  return IMAGE_MODERATION_POLICIES[context] || IMAGE_MODERATION_POLICIES[MODERATION_CONTEXTS.POST_IMAGE];
}

/**
 * Evaluates a raw classification result against the platform policy for a given context.
 *
 * @param {Object} classificationResult Standardized engine output from classifyImage() or worker
 * @param {string} context One of MODERATION_CONTEXTS
 * @returns {Object} Standardized policy decision contract
 */
export function evaluateModerationPolicy(classificationResult, context = MODERATION_CONTEXTS.POST_IMAGE) {
  const policy = getPolicyForContext(context);

  // 1. Handle Engine / Operational Errors
  if (!classificationResult || !classificationResult.success) {
    const errorCode = classificationResult?.error?.code || 'UNKNOWN_ERROR';
    const isCancelled = errorCode === 'CANCELLED';
    const isTimeout = errorCode === 'TIMEOUT';

    return {
      action: POLICY_ACTIONS.BLOCK,
      category: null,
      score: 0,
      threshold: 0,
      reasonCode: isCancelled
        ? POLICY_REASON_CODES.CANCELLED
        : isTimeout
          ? POLICY_REASON_CODES.SCANNER_TIMEOUT
          : POLICY_REASON_CODES.SCANNER_ERROR,
      context: policy.context,
      userMessage: isCancelled
        ? 'Verification was cancelled.'
        : isTimeout
          ? 'Image safety check timed out. Please try again.'
          : 'Unable to verify image safety. Please check your connection and try again.',
      predictions: classificationResult?.predictions || [],
      probabilities: classificationResult?.probabilities || {},
      durationMs: classificationResult?.durationMs || 0,
      isSystemError: !isCancelled,
    };
  }

  const { probabilities = {}, predictions = [], durationMs = 0 } = classificationResult;

  const pornScore = probabilities.Porn ?? 0;
  const hentaiScore = probabilities.Hentai ?? 0;
  const sexyScore = probabilities.Sexy ?? 0;

  // 2. Evaluate Hard BLOCK Thresholds
  if (pornScore >= policy.thresholds.Porn) {
    return {
      action: POLICY_ACTIONS.BLOCK,
      category: 'Porn',
      score: pornScore,
      threshold: policy.thresholds.Porn,
      reasonCode: POLICY_REASON_CODES.EXCEEDS_PORN_THRESHOLD,
      context: policy.context,
      userMessage: policy.userViolationMessage,
      predictions,
      probabilities,
      durationMs,
      isSystemError: false,
    };
  }

  if (hentaiScore >= policy.thresholds.Hentai) {
    return {
      action: POLICY_ACTIONS.BLOCK,
      category: 'Hentai',
      score: hentaiScore,
      threshold: policy.thresholds.Hentai,
      reasonCode: POLICY_REASON_CODES.EXCEEDS_HENTAI_THRESHOLD,
      context: policy.context,
      userMessage: policy.userViolationMessage,
      predictions,
      probabilities,
      durationMs,
      isSystemError: false,
    };
  }

  if (sexyScore >= policy.thresholds.Sexy) {
    return {
      action: POLICY_ACTIONS.BLOCK,
      category: 'Sexy',
      score: sexyScore,
      threshold: policy.thresholds.Sexy,
      reasonCode: POLICY_REASON_CODES.EXCEEDS_SEXY_THRESHOLD,
      context: policy.context,
      userMessage: policy.userViolationMessage,
      predictions,
      probabilities,
      durationMs,
      isSystemError: false,
    };
  }

  // 3. Evaluate REVIEW Fallback Behavior
  // In Phase 4, there is no client-side admin review queue.
  // Sub-block scores are ALLOWED client-side to avoid false-positive lockouts.
  const isGreyZone =
    pornScore >= policy.reviewThresholds.Porn ||
    hentaiScore >= policy.reviewThresholds.Hentai ||
    sexyScore >= policy.reviewThresholds.Sexy;

  const highestRiskCategory = pornScore >= hentaiScore && pornScore >= sexyScore
    ? 'Porn'
    : hentaiScore >= sexyScore
      ? 'Hentai'
      : 'Sexy';

  const highestRiskScore = Math.max(pornScore, hentaiScore, sexyScore);

  return {
    action: POLICY_ACTIONS.ALLOW,
    category: isGreyZone ? highestRiskCategory : (classificationResult.dominantClass || 'Neutral'),
    score: highestRiskScore,
    threshold: policy.thresholds[highestRiskCategory] || 1.0,
    reasonCode: POLICY_REASON_CODES.SAFE,
    context: policy.context,
    userMessage: 'Image passed safety check.',
    predictions,
    probabilities,
    isSystemError: false,
    isReviewFlagged: isGreyZone,
  };
}

/**
 * Evaluates an aggregated video scan result from moderateVideo against platform policy.
 *
 * @param {Object} videoScanResult Output from moderateVideo()
 * @param {string} context Moderation context identifier
 * @returns {Object} Standardized policy decision contract
 */
export function evaluateVideoModerationPolicy(videoScanResult, context = MODERATION_CONTEXTS.POST_IMAGE) {
  const policy = getPolicyForContext(context);

  if (!videoScanResult || !videoScanResult.success) {
    const errorCode = videoScanResult?.error?.code || 'SCANNER_ERROR';
    const isCancelled = errorCode === 'CANCELLED';

    return {
      action: POLICY_ACTIONS.BLOCK,
      category: null,
      score: 0,
      threshold: 0,
      reasonCode: isCancelled ? POLICY_REASON_CODES.CANCELLED : POLICY_REASON_CODES.SCANNER_ERROR,
      context: policy.context,
      userMessage: isCancelled
        ? 'Video moderation scan was cancelled.'
        : 'Unable to complete video safety verification. Please try again.',
      isSystemError: !isCancelled,
      isReviewFlagged: false,
    };
  }

  const peakProbs = videoScanResult.summary?.peakProbabilities || {};
  const pornPeak = peakProbs.Porn || 0;
  const hentaiPeak = peakProbs.Hentai || 0;
  const sexyPeak = peakProbs.Sexy || 0;

  const frameResults = videoScanResult.frameResults || [];
  let repeatedSexyCount = 0;
  for (const f of frameResults) {
    if ((f.probabilities?.Sexy || 0) >= policy.thresholds.Sexy) {
      repeatedSexyCount++;
    }
  }

  // 1. Critical Single-Frame Violations (Porn or Hentai)
  if (pornPeak >= policy.thresholds.Porn) {
    return {
      action: POLICY_ACTIONS.BLOCK,
      category: 'Porn',
      score: pornPeak,
      threshold: policy.thresholds.Porn,
      reasonCode: POLICY_REASON_CODES.EXCEEDS_PORN_THRESHOLD,
      context: policy.context,
      userMessage: 'This video contains prohibited adult or sexually explicit content.',
      isSystemError: false,
      isReviewFlagged: false,
    };
  }

  if (hentaiPeak >= policy.thresholds.Hentai) {
    return {
      action: POLICY_ACTIONS.BLOCK,
      category: 'Hentai',
      score: hentaiPeak,
      threshold: policy.thresholds.Hentai,
      reasonCode: POLICY_REASON_CODES.EXCEEDS_HENTAI_THRESHOLD,
      context: policy.context,
      userMessage: 'This video contains prohibited animated/hentai content.',
      isSystemError: false,
      isReviewFlagged: false,
    };
  }

  // 2. Repeated Sexy Frame Threshold (>= 2 frames)
  if (repeatedSexyCount >= 2) {
    return {
      action: POLICY_ACTIONS.BLOCK,
      category: 'Sexy',
      score: sexyPeak,
      threshold: policy.thresholds.Sexy,
      reasonCode: POLICY_REASON_CODES.EXCEEDS_SEXY_THRESHOLD,
      context: policy.context,
      userMessage: 'This video contains excessive sexually suggestive imagery.',
      isSystemError: false,
      isReviewFlagged: false,
    };
  }

  return {
    action: POLICY_ACTIONS.ALLOW,
    category: videoScanResult.summary?.dominantClassOverall || 'Neutral',
    score: Math.max(pornPeak, hentaiPeak, sexyPeak),
    threshold: policy.thresholds.Porn,
    reasonCode: POLICY_REASON_CODES.SAFE,
    context: policy.context,
    userMessage: 'Video passed safety verification.',
    isSystemError: false,
    isReviewFlagged: false,
  };
}

