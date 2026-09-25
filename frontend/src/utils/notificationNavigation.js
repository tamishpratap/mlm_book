/**
 * Centralized Notification Navigation Service
 *
 * Deterministically resolves the correct destination route for every notification
 * based on type, category, reference_type, entity IDs, and context payload.
 */

/**
 * Sanitizes an identifier to ensure it is a valid non-empty string/number
 * and not 'undefined', 'null', or '[object Object]'.
 */
function sanitizeIdentifier(val) {
  if (val === null || val === undefined) return null;
  const str = String(val).trim();
  if (!str || str === 'undefined' || str === 'null' || str === '[object Object]') {
    return null;
  }
  return str;
}

/**
 * Normalizes and extracts safe internal path from a URL string if applicable.
 * Translates known legacy or backend URLs to their canonical React frontend routes.
 */
export function normalizeNotificationUrl(rawUrl) {
  if (!rawUrl || typeof rawUrl !== 'string') return null;

  let pathname;
  let search;
  let hash;

  try {
    if (rawUrl.startsWith('http://') || rawUrl.startsWith('https://')) {
      const parsed = new URL(rawUrl);
      pathname = parsed.pathname || '';
      search = parsed.search || '';
      hash = parsed.hash || '';
    } else if (rawUrl.startsWith('/')) {
      const dummy = new URL(rawUrl, 'http://localhost');
      pathname = dummy.pathname || '';
      search = dummy.search || '';
      hash = dummy.hash || '';
    } else {
      return null;
    }
  } catch {
    return null;
  }

  // Remove trailing slashes (except root '/')
  if (pathname.length > 1 && pathname.endsWith('/')) {
    pathname = pathname.slice(0, -1);
  }

  // Rewrite legacy or mismatched backend routes to React equivalents
  if (pathname === '/member/friends/requests' || pathname === '/member/requests') {
    return '/member/friend-requests';
  }

  const groupSlugMatch = pathname.match(/^\/member\/groups\/([^/]+)/);
  if (groupSlugMatch) {
    return `/member/community/${groupSlugMatch[1]}`;
  }

  if (pathname === '/member/groups') {
    return '/member/community';
  }

  const videoMatch = pathname.match(/^\/member\/videos\/create/);
  if (videoMatch) {
    return '/member/create-video';
  }

  return `${pathname}${search}${hash}`;
}

/**
 * Determines the canonical frontend destination for a notification.
 *
 * @param {Object} notification - The raw or formatted notification object
 * @returns {string} The canonical route path (e.g., '/member/friend-requests')
 */
export function getNotificationDestination(notification) {
  if (!notification) {
    return '/member/notifications';
  }

  // Parse data if it is a JSON string
  let data = notification.data || {};
  if (typeof data === 'string') {
    try {
      data = JSON.parse(data);
    } catch {
      data = {};
    }
  }

  // Extract type identifiers from multiple potential payload properties
  const rawType = (notification.type || notification.notification_type || data.type || '').toLowerCase();
  const category = (data.category || '').toLowerCase();
  const referenceType = (data.reference_type || '').toLowerCase();
  const title = (data.title || '').toLowerCase();
  const message = (data.message || data.body || '').toLowerCase();
  const icon = (data.icon || '').toLowerCase();
  const action = (data.action || '').toLowerCase();

  // Extract candidate URL from payload if provided
  const candidateUrl = data.url || data.target_url || data.action_url || notification.url || null;
  const normalizedCandidateUrl = candidateUrl ? normalizeNotificationUrl(candidateUrl) : null;

  // Extract entity identifiers from candidate URL if available
  let urlPostId = null;
  let urlStoryId = null;
  let urlEventId = null;
  let urlCommunitySlug = null;
  let urlBusinessSlug = null;
  let urlActorId = null;

  if (normalizedCandidateUrl) {
    const postMatch = normalizedCandidateUrl.match(/^\/member\/posts\/([^/?#]+)/);
    if (postMatch) urlPostId = postMatch[1];

    const storyMatch = normalizedCandidateUrl.match(/^\/member\/stories\/([^/?#]+)/);
    if (storyMatch) urlStoryId = storyMatch[1];

    const eventMatch = normalizedCandidateUrl.match(/^\/member\/events\/([^/?#]+)/);
    if (eventMatch && eventMatch[1] !== 'create' && eventMatch[1] !== 'add-fund') urlEventId = eventMatch[1];

    const communityMatch = normalizedCandidateUrl.match(/^\/member\/community\/([^/?#]+)/);
    if (communityMatch && communityMatch[1] !== 'discover' && communityMatch[1] !== 'create' && communityMatch[1] !== 'notifications') {
      urlCommunitySlug = communityMatch[1];
    }

    const businessMatch = normalizedCandidateUrl.match(/^\/member\/business-pages\/([^/?#]+)/);
    if (businessMatch && businessMatch[1] !== 'create') {
      urlBusinessSlug = businessMatch[1];
    }

    const peopleMatch = normalizedCandidateUrl.match(/^\/member\/(?:people|profile)\/([^/?#]+)/);
    if (peopleMatch && peopleMatch[1] !== 'edit' && peopleMatch[1] !== 'visitors') {
      urlActorId = peopleMatch[1];
    }
  }

  // Extract entity identifiers with sanitation
  const postId = sanitizeIdentifier(data.post_id || (referenceType === 'post' ? data.reference_id : null) || data.post?.id || urlPostId);
  const storyId = sanitizeIdentifier(data.story_id || (referenceType === 'story' ? data.reference_id : null) || data.story?.id || urlStoryId);
  const eventId = sanitizeIdentifier(data.event_id || (referenceType === 'event' ? data.reference_id : null) || data.event?.id || urlEventId);
  const communitySlug = sanitizeIdentifier(data.community_slug || data.group_slug || data.slug || (referenceType === 'community' || referenceType === 'group' ? data.reference_id : null) || urlCommunitySlug);
  const businessPageSlug = sanitizeIdentifier(data.business_page_slug || data.page_slug || data.slug || (referenceType === 'business_page' || referenceType === 'page' ? data.reference_id : null) || urlBusinessSlug);
  const actorId = sanitizeIdentifier(data.actor_id || data.actor?.id || (referenceType === 'member' ? data.reference_id : null) || urlActorId);
  const memberId = sanitizeIdentifier(data.member_id || data.sender_id || actorId);

  // -------------------------------------------------------------------------
  // 1. CONNECTION / FRIEND REQUEST NOTIFICATIONS
  // -------------------------------------------------------------------------
  // Incoming / Received connection requests MUST navigate to the canonical requests page
  const isFriendRequestReceived =
    rawType.includes('friendrequestreceivednotification') ||
    rawType === 'friend_request_received' ||
    rawType === 'friend_request' ||
    rawType === 'connection_request' ||
    icon === 'user-plus' ||
    (category === 'friends' && (title.includes('request') || message.includes('connection request') || message.includes('friend request')));

  if (isFriendRequestReceived) {
    return '/member/friend-requests';
  }

  // Accepted connection requests
  const isFriendRequestAccepted =
    rawType.includes('friendrequestacceptednotification') ||
    rawType === 'friend_request_accepted' ||
    rawType === 'connection_accepted' ||
    icon === 'user-check' ||
    (category === 'friends' && (title.includes('accepted') || message.includes('accepted your')));

  if (isFriendRequestAccepted) {
    if (actorId) {
      return `/member/people/${actorId}`;
    }
    if (normalizedCandidateUrl && normalizedCandidateUrl.startsWith('/member/people/')) {
      return normalizedCandidateUrl;
    }
    return '/member/friends';
  }

  // Declined / Rejected connection requests
  const isFriendRequestRejected =
    rawType.includes('friendrequestrejectednotification') ||
    rawType === 'friend_request_rejected' ||
    rawType === 'connection_declined' ||
    icon === 'user-x' ||
    (category === 'friends' && (title.includes('declined') || title.includes('rejected')));

  if (isFriendRequestRejected) {
    return '/member/friend-requests';
  }

  // General connection / friendship fallback
  if (category === 'friends' || referenceType === 'friendship') {
    if (actorId) return `/member/people/${actorId}`;
    if (normalizedCandidateUrl && normalizedCandidateUrl.startsWith('/member/people/')) {
      return normalizedCandidateUrl;
    }
    return '/member/friends';
  }

  // -------------------------------------------------------------------------
  // 2. MESSAGES / CHAT NOTIFICATIONS
  // -------------------------------------------------------------------------
  const isMessage =
    rawType.includes('messagenotification') ||
    rawType === 'message' ||
    rawType === 'direct_message' ||
    rawType === 'chat' ||
    category === 'messages' ||
    category === 'chat' ||
    referenceType === 'message' ||
    referenceType === 'conversation';

  if (isMessage) {
    // Check if message belongs to a business page inbox
    if (businessPageSlug) {
      return `/member/business-pages/${businessPageSlug}/inbox`;
    }
    if (memberId) {
      return `/member/messages/${memberId}`;
    }
    return '/member/messages';
  }

  // -------------------------------------------------------------------------
  // 3. COMMENTS & REPLIES ON POSTS
  // -------------------------------------------------------------------------
  const isComment =
    rawType.includes('postcommentnotification') ||
    rawType.includes('commentreplynotification') ||
    rawType.includes('commentreactionnotification') ||
    rawType === 'comment' ||
    rawType === 'comment_reply' ||
    rawType === 'comment_reaction' ||
    category === 'comments' ||
    referenceType === 'comment';

  if (isComment) {
    if (postId) {
      return `/member/posts/${postId}`;
    }
    if (normalizedCandidateUrl && normalizedCandidateUrl.startsWith('/member/posts/')) {
      return normalizedCandidateUrl;
    }
    return '/member/socials';
  }

  // -------------------------------------------------------------------------
  // 4. POST REACTIONS & LIKES
  // -------------------------------------------------------------------------
  const isPostReaction =
    rawType.includes('postlikenotification') ||
    rawType.includes('postreactionnotification') ||
    rawType === 'post_like' ||
    rawType === 'post_reaction' ||
    rawType === 'like' ||
    rawType === 'reaction' ||
    (category === 'posts' && (title.includes('liked') || title.includes('reaction')));

  if (isPostReaction) {
    if (postId) {
      return `/member/posts/${postId}`;
    }
    if (normalizedCandidateUrl && normalizedCandidateUrl.startsWith('/member/posts/')) {
      return normalizedCandidateUrl;
    }
    return '/member/socials';
  }

  // -------------------------------------------------------------------------
  // 5. POST SHARES & NEW FRIEND POSTS
  // -------------------------------------------------------------------------
  const isPostContent =
    rawType.includes('friendcreatedpostnotification') ||
    rawType.includes('postsharenotification') ||
    rawType.includes('sendposttofriendnotification') ||
    rawType === 'post_share' ||
    rawType === 'new_post' ||
    category === 'posts' ||
    referenceType === 'post';

  if (isPostContent) {
    if (postId) {
      return `/member/posts/${postId}`;
    }
    if (normalizedCandidateUrl && normalizedCandidateUrl.startsWith('/member/posts/')) {
      return normalizedCandidateUrl;
    }
    return '/member/socials';
  }

  // -------------------------------------------------------------------------
  // 6. STORIES NOTIFICATIONS
  // -------------------------------------------------------------------------
  const isStory =
    rawType.includes('story') ||
    category === 'stories' ||
    referenceType === 'story';

  if (isStory) {
    if (storyId) {
      return `/member/stories/${storyId}`;
    }
    if (normalizedCandidateUrl && normalizedCandidateUrl.startsWith('/member/stories/')) {
      return normalizedCandidateUrl;
    }
    return '/member/stories';
  }

  // -------------------------------------------------------------------------
  // 7. EVENT NOTIFICATIONS
  // -------------------------------------------------------------------------
  const isEvent =
    rawType.includes('event') ||
    category === 'event' ||
    category === 'events' ||
    referenceType === 'event' ||
    title.includes('event');

  if (isEvent) {
    // Event module temporarily disabled: fallback to notifications page
    return '/member/notifications';
  }

  // -------------------------------------------------------------------------
  // 8. BUSINESS PAGE NOTIFICATIONS
  // -------------------------------------------------------------------------
  const isBusinessPage =
    rawType.includes('business') ||
    category === 'business' ||
    category === 'business_page' ||
    category === 'pages' ||
    referenceType === 'business_page' ||
    referenceType === 'page';

  if (isBusinessPage) {
    if (businessPageSlug) {
      if (action === 'inbox' || action === 'message') {
        return `/member/business-pages/${businessPageSlug}/inbox`;
      }
      if (action === 'notifications') {
        return `/member/business-pages/${businessPageSlug}/notifications`;
      }
      if (action === 'analytics') {
        return `/member/business-pages/${businessPageSlug}/analytics`;
      }
      return `/member/business-pages/${businessPageSlug}`;
    }
    if (normalizedCandidateUrl && normalizedCandidateUrl.startsWith('/member/business-pages/')) {
      return normalizedCandidateUrl;
    }
    return '/member/business-pages';
  }

  // -------------------------------------------------------------------------
  // 9. COMMUNITY / GROUP NOTIFICATIONS
  // -------------------------------------------------------------------------
  const isCommunity =
    rawType.includes('community') ||
    rawType.includes('group') ||
    category === 'community' ||
    category === 'groups' ||
    referenceType === 'community' ||
    referenceType === 'group' ||
    title.includes('community');

  if (isCommunity) {
    if (communitySlug) {
      if (action === 'requests' || action === 'admin') {
        return `/member/community/${communitySlug}/admin`;
      }
      if (action === 'notifications') {
        return `/member/community/${communitySlug}/notifications`;
      }
      return `/member/community/${communitySlug}`;
    }
    if (normalizedCandidateUrl && normalizedCandidateUrl.startsWith('/member/community/')) {
      return normalizedCandidateUrl;
    }
    return '/member/community';
  }

  // -------------------------------------------------------------------------
  // 10. REFERRAL NOTIFICATIONS
  // -------------------------------------------------------------------------
  const isReferral =
    rawType.includes('referral') ||
    category === 'referral' ||
    category === 'referrals' ||
    referenceType === 'referral' ||
    title.includes('referral');

  if (isReferral) {
    // Open referral modal and navigate to web3 wallet or dashboard
    return '/member/web3-wallet';
  }

  // -------------------------------------------------------------------------
  // 11. PROFILE VISITOR NOTIFICATIONS
  // -------------------------------------------------------------------------
  const isProfileVisitor =
    rawType.includes('visitor') ||
    referenceType === 'profile_visitor' ||
    referenceType === 'visitor' ||
    title.includes('profile visitor') ||
    title.includes('viewed your profile');

  if (isProfileVisitor) {
    return '/member/profile/visitors';
  }

  // -------------------------------------------------------------------------
  // 12. ADMIN ANNOUNCEMENT NOTIFICATIONS
  // -------------------------------------------------------------------------
  const isAnnouncement =
    rawType === 'admin_announcement' ||
    rawType === 'announcement' ||
    rawType.includes('adminbroadcast');

  if (isAnnouncement) {
    if (
      normalizedCandidateUrl &&
      normalizedCandidateUrl !== '/member/dashboard' &&
      normalizedCandidateUrl !== '/member/home' &&
      normalizedCandidateUrl !== '/'
    ) {
      return normalizedCandidateUrl;
    }
    return '/member/notifications';
  }

  // -------------------------------------------------------------------------
  // 13. EXPLICIT URL NORMALIZATION (IF PROVIDED BY BACKEND PAYLOAD)
  // -------------------------------------------------------------------------
  if (
    normalizedCandidateUrl &&
    normalizedCandidateUrl !== '/member/dashboard' &&
    normalizedCandidateUrl !== '/member/home' &&
    normalizedCandidateUrl !== '/'
  ) {
    return normalizedCandidateUrl;
  }

  // -------------------------------------------------------------------------
  // 13. SAFE FALLBACK (UNKNOWN / UNRECOGNIZED TYPES)
  // -------------------------------------------------------------------------
  // Unknown notification types safely remain in the notifications center
  return '/member/notifications';
}

/**
 * Handles notification click actions and navigates cleanly.
 * Dispatches any required side-effect events (e.g. opening the referral modal).
 *
 * @param {Object} notification - The notification object
 * @param {Function} navigate - React Router navigate function
 */
export function handleNotificationNavigation(notification, navigate) {
  if (!notification || !navigate) return;

  let data = notification.data || {};
  if (typeof data === 'string') {
    try {
      data = JSON.parse(data);
    } catch {
      data = {};
    }
  }

  const rawType = (notification.type || notification.notification_type || data.type || '').toLowerCase();
  const category = (data.category || '').toLowerCase();
  const title = (data.title || '').toLowerCase();

  // If notification is referral-related, dispatch global modal event
  const isReferral =
    rawType.includes('referral') ||
    category === 'referral' ||
    category === 'referrals' ||
    title.includes('referral');

  if (isReferral && typeof window !== 'undefined') {
    window.dispatchEvent(new CustomEvent('open-referral-modal'));
  }

  const destination = getNotificationDestination(notification);
  navigate(destination);
}
