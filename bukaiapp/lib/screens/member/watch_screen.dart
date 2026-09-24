import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../models/post_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/feed_provider.dart';
import '../../widgets/app_video_player.dart';

class WatchScreen extends StatefulWidget {
  const WatchScreen({super.key});

  @override
  State<WatchScreen> createState() => _WatchScreenState();
}

class _WatchScreenState extends State<WatchScreen> {
  bool _isLoading = false;
  String _activeFilter = 'all'; // 'all', 'trending', 'my_videos', 'saved'
  final Set<int> _hiddenPostIds = {};
  final Map<int, TextEditingController> _commentControllers = {};

  @override
  void initState() {
    super.initState();
    _loadVideos();
  }

  @override
  void dispose() {
    for (final ctrl in _commentControllers.values) {
      ctrl.dispose();
    }
    super.dispose();
  }

  TextEditingController _getCommentController(int postId) {
    return _commentControllers.putIfAbsent(postId, () => TextEditingController());
  }

  Future<void> _loadVideos({String? filter}) async {
    final targetFilter = filter ?? _activeFilter;
    setState(() => _isLoading = true);
    await context.read<FeedProvider>().fetchWatchVideos(filter: targetFilter);
    if (mounted) setState(() => _isLoading = false);
  }

  void _onFilterChanged(String newFilter) {
    if (_activeFilter == newFilter) return;
    setState(() => _activeFilter = newFilter);
    _loadVideos(filter: newFilter);
  }

  List<PostModel> _getFilteredPosts(List<PostModel> allPosts, dynamic currentMember) {
    var list = allPosts.where((p) => !_hiddenPostIds.contains(p.id)).toList();

    if (_activeFilter == 'my_videos') {
      final myId = currentMember?.id;
      return list.where((p) => myId != null && p.author.id == myId).toList();
    } else if (_activeFilter == 'saved') {
      return list.where((p) => p.isSaved).toList();
    } else if (_activeFilter == 'trending') {
      final sorted = List<PostModel>.from(list);
      sorted.sort((a, b) => (b.likesCount + b.commentsCount).compareTo(a.likesCount + a.commentsCount));
      return sorted;
    }
    return list;
  }

  @override
  Widget build(BuildContext context) {
    final feed = context.watch<FeedProvider>();
    final auth = context.watch<AuthProvider>();
    final currentMember = auth.currentMember;

    // Use watchPosts (parsed PostModel list) or fallback from watchVideos
    List<PostModel> posts = feed.watchPosts;
    if (posts.isEmpty && feed.watchVideos.isNotEmpty) {
      posts = feed.watchVideos.map((v) => PostModel.fromJson(v)).toList();
    }

    final filteredPosts = _getFilteredPosts(posts, currentMember);
    final screenWidth = MediaQuery.of(context).size.width;
    final isDesktop = screenWidth >= 1024;

    final myVideosCount = feed.myVideosCount > 0
        ? feed.myVideosCount
        : posts.where((p) => currentMember != null && p.author.id == currentMember.id).length;
    final savedVideosCount = feed.savedVideosCount > 0
        ? feed.savedVideosCount
        : posts.where((p) => p.isSaved).length;

    Widget feedContent;
    if (_isLoading) {
      feedContent = const Center(
        child: Padding(
          padding: EdgeInsets.all(40),
          child: CircularProgressIndicator(color: Color(0xFF2563EB)),
        ),
      );
    } else if (filteredPosts.isEmpty) {
      feedContent = Center(
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          child: Container(
            padding: const EdgeInsets.all(32),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(22),
                  decoration: const BoxDecoration(
                    color: Color(0xFFEFF6FF),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.videocam_off_rounded, size: 48, color: Color(0xFF2563EB)),
                ),
                const SizedBox(height: 18),
                const Text(
                  'No video posts found',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                ),
                const SizedBox(height: 8),
                Text(
                  _activeFilter == 'my_videos'
                      ? "You haven't uploaded any video posts yet."
                      : _activeFilter == 'saved'
                          ? "You haven't saved any video posts yet."
                          : _activeFilter == 'trending'
                              ? 'No trending video posts available at the moment.'
                              : 'No video posts available right now in your feed. Share a video to get started!',
                  textAlign: TextAlign.center,
                  style: const TextStyle(color: Color(0xFF64748B), fontSize: 13, height: 1.4),
                ),
                const SizedBox(height: 16),
                if (_activeFilter != 'all')
                  ElevatedButton.icon(
                    onPressed: () => _onFilterChanged('all'),
                    icon: const Icon(Icons.arrow_back_rounded, size: 16),
                    label: const Text('Back to Home Feed'),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF2563EB),
                      foregroundColor: Colors.white,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    ),
                  ),
              ],
            ),
          ),
        ),
      );
    } else {
      feedContent = ListView.builder(
        padding: const EdgeInsets.only(bottom: 24),
        itemCount: filteredPosts.length + (_activeFilter != 'all' ? 1 : 0),
        itemBuilder: (ctx, i) {
          if (_activeFilter != 'all') {
            if (i == 0) {
              return _buildFilterBanner(myVideosCount, savedVideosCount);
            }
            final post = filteredPosts[i - 1];
            return _buildWatchCard(post, feed, currentMember);
          }
          final post = filteredPosts[i];
          return _buildWatchCard(post, feed, currentMember);
        },
      );
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: RefreshIndicator(
        onRefresh: () => _loadVideos(),
        color: const Color(0xFF2563EB),
        child: Column(
          children: [
            // Mobile Filter Tabs (hidden on Desktop)
            if (!isDesktop) _buildMobileTabs(myVideosCount, savedVideosCount),

            // Main Content Area
            Expanded(
              child: isDesktop
                  ? Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Center Column: Video Feed
                        Expanded(
                          child: Align(
                            alignment: Alignment.topCenter,
                            child: ConstrainedBox(
                              constraints: const BoxConstraints(maxWidth: 780),
                              child: feedContent,
                            ),
                          ),
                        ),

                        // Right Column: Watch Navigation & Widgets
                        SizedBox(
                          width: 320,
                          child: SingleChildScrollView(
                            padding: const EdgeInsets.fromLTRB(8, 12, 20, 24),
                            child: Column(
                              children: [
                                _buildWatchNavigationCard(myVideosCount, savedVideosCount),
                                const SizedBox(height: 16),
                                _buildTrendingVideosCard(feed),
                                const SizedBox(height: 16),
                                _buildRecentVideosCard(feed),
                              ],
                            ),
                          ),
                        ),
                      ],
                    )
                  : feedContent,
            ),
          ],
        ),
      ),
    );
  }

  // -------------------------------------------------------------
  // Mobile Filter Tabs Bar (Matching React's watch-mobile-tabs)
  // -------------------------------------------------------------
  Widget _buildMobileTabs(int myVideosCount, int savedVideosCount) {
    return Container(
      color: Colors.white,
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      child: SingleChildScrollView(
        scrollDirection: Axis.horizontal,
        child: Row(
          children: [
            _buildMobileTabItem(
              icon: Icons.auto_awesome_rounded,
              label: 'Home Feed',
              isActive: _activeFilter == 'all',
              onTap: () => _onFilterChanged('all'),
            ),
            const SizedBox(width: 8),
            _buildMobileTabItem(
              icon: Icons.local_fire_department_rounded,
              label: 'Trending',
              isActive: _activeFilter == 'trending',
              onTap: () => _onFilterChanged('trending'),
            ),
            const SizedBox(width: 8),
            _buildMobileTabItem(
              icon: Icons.videocam_rounded,
              label: 'My Videos',
              count: myVideosCount,
              isActive: _activeFilter == 'my_videos',
              onTap: () => _onFilterChanged('my_videos'),
            ),
            const SizedBox(width: 8),
            _buildMobileTabItem(
              icon: Icons.bookmark_rounded,
              label: 'Saved',
              count: savedVideosCount,
              isActive: _activeFilter == 'saved',
              onTap: () => _onFilterChanged('saved'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMobileTabItem({
    required IconData icon,
    required String label,
    int? count,
    required bool isActive,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        decoration: BoxDecoration(
          color: isActive ? const Color(0xFF2563EB) : const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(10),
          boxShadow: isActive
              ? [BoxShadow(color: const Color(0xFF2563EB).withOpacity(0.25), blurRadius: 6, offset: const Offset(0, 2))]
              : null,
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(icon, size: 15, color: isActive ? Colors.white : const Color(0xFF64748B)),
            const SizedBox(width: 6),
            Text(
              label,
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.w600,
                color: isActive ? Colors.white : const Color(0xFF475569),
              ),
            ),
            if (count != null && count > 0) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1),
                decoration: BoxDecoration(
                  color: isActive ? Colors.white.withOpacity(0.25) : const Color(0xFFCBD5E1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Text(
                  '$count',
                  style: TextStyle(
                    fontSize: 11,
                    fontWeight: FontWeight.bold,
                    color: isActive ? Colors.white : const Color(0xFF1E293B),
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }

  // -------------------------------------------------------------
  // Filter Banner (Matching React's watch-filter-banner)
  // -------------------------------------------------------------
  Widget _buildFilterBanner(int myVideosCount, int savedVideosCount) {
    String title = '';
    String sub = '';
    if (_activeFilter == 'trending') {
      title = '🔥 Trending Videos';
    } else if (_activeFilter == 'my_videos') {
      title = '📹 My Videos';
      sub = '($myVideosCount ${myVideosCount == 1 ? 'video' : 'videos'})';
    } else if (_activeFilter == 'saved') {
      title = '🔖 Saved Videos';
      sub = '($savedVideosCount ${savedVideosCount == 1 ? 'video' : 'videos'})';
    }

    return Container(
      margin: const EdgeInsets.fromLTRB(16, 12, 16, 6),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              Text(
                title,
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A)),
              ),
              if (sub.isNotEmpty) ...[
                const SizedBox(width: 6),
                Text(
                  sub,
                  style: const TextStyle(fontSize: 12.5, color: Color(0xFF64748B)),
                ),
              ],
            ],
          ),
          OutlinedButton(
            onPressed: () => _onFilterChanged('all'),
            style: OutlinedButton.styleFrom(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              minimumSize: Size.zero,
              side: const BorderSide(color: Color(0xFFCBD5E1)),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text(
              'Back to Home Feed',
              style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF475569)),
            ),
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // Watch Navigation Card (Right Sidebar on Desktop / Web)
  // -------------------------------------------------------------
  Widget _buildWatchNavigationCard(int myVideosCount, int savedVideosCount) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Header
          Row(
            children: const [
              Icon(Icons.explore_outlined, color: Color(0xFF2563EB), size: 20),
              SizedBox(width: 8),
              Text(
                'Watch Navigation',
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF0F172A),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          // Nav Items
          _buildSidebarNavItem(
            icon: Icons.tv_rounded,
            label: 'Home Feed',
            isActive: _activeFilter == 'all',
            onTap: () => _onFilterChanged('all'),
          ),
          const SizedBox(height: 4),
          _buildSidebarNavItem(
            icon: Icons.local_fire_department_rounded,
            label: 'Trending Videos',
            isActive: _activeFilter == 'trending',
            onTap: () => _onFilterChanged('trending'),
          ),
          const SizedBox(height: 4),
          _buildSidebarNavItem(
            icon: Icons.videocam_rounded,
            label: 'My Videos ($myVideosCount)',
            isActive: _activeFilter == 'my_videos',
            onTap: () => _onFilterChanged('my_videos'),
          ),
          const SizedBox(height: 4),
          _buildSidebarNavItem(
            icon: Icons.bookmark_rounded,
            label: 'Saved Videos ($savedVideosCount)',
            isActive: _activeFilter == 'saved',
            onTap: () => _onFilterChanged('saved'),
          ),
        ],
      ),
    );
  }

  Widget _buildSidebarNavItem({
    required IconData icon,
    required String label,
    required bool isActive,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(12),
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
        decoration: BoxDecoration(
          color: isActive ? const Color(0xFFEFF6FF) : Colors.transparent,
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
          children: [
            Icon(
              icon,
              size: 19,
              color: isActive ? const Color(0xFF2563EB) : const Color(0xFF64748B),
            ),
            const SizedBox(width: 12),
            Text(
              label,
              style: TextStyle(
                fontSize: 13.5,
                fontWeight: isActive ? FontWeight.w600 : FontWeight.w500,
                color: isActive ? const Color(0xFF2563EB) : const Color(0xFF334155),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // -------------------------------------------------------------
  // Trending Videos Card (Real items from backend or skeleton bars)
  // -------------------------------------------------------------
  Widget _buildTrendingVideosCard(FeedProvider feed) {
    final list = feed.trendingVideos;

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.local_fire_department_rounded, color: Color(0xFFF59E0B), size: 20),
              SizedBox(width: 8),
              Text(
                'Trending Videos',
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF0F172A),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          if (list.isNotEmpty)
            ...list.take(3).map((item) => _buildSidebarVideoItem(item))
          else ...[
            Container(
              width: double.infinity,
              height: 16,
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(999),
              ),
            ),
            const SizedBox(height: 10),
            Container(
              width: double.infinity,
              height: 16,
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(999),
              ),
            ),
          ],
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // Recent Videos Card (Real items from backend or skeleton bar)
  // -------------------------------------------------------------
  Widget _buildRecentVideosCard(FeedProvider feed) {
    final list = feed.recentVideos;

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.access_time_rounded, color: Color(0xFF2563EB), size: 20),
              SizedBox(width: 8),
              Text(
                'Recent Videos',
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.bold,
                  color: Color(0xFF0F172A),
                ),
              ),
            ],
          ),
          const SizedBox(height: 14),

          if (list.isNotEmpty)
            ...list.take(3).map((item) => _buildSidebarVideoItem(item))
          else ...[
            Container(
              width: double.infinity,
              height: 16,
              decoration: BoxDecoration(
                color: const Color(0xFF1E293B),
                borderRadius: BorderRadius.circular(999),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildSidebarVideoItem(Map<String, dynamic> item) {
    final title = item['body']?.toString() ?? 'Video';
    final member = item['member'] as Map<String, dynamic>? ?? {};
    final creatorName = member['name']?.toString() ?? 'Creator';
    final meta = item['reactions_count'] != null
        ? '$creatorName · ${item['reactions_count']} reactions'
        : (item['created_at'] != null ? '$creatorName · ${item['created_at']}' : creatorName);

    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: Row(
        children: [
          Container(
            width: 64,
            height: 44,
            decoration: BoxDecoration(
              color: const Color(0xFF0F172A),
              borderRadius: BorderRadius.circular(8),
            ),
            child: const Center(
              child: Icon(Icons.play_circle_fill_rounded, color: Colors.white, size: 22),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: Color(0xFF0F172A)),
                ),
                const SizedBox(height: 2),
                Text(
                  meta,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // Watch Video Card (1:1 with React Member Panel's WatchVideoCard)
  // -------------------------------------------------------------
  Widget _buildWatchCard(PostModel post, FeedProvider feed, dynamic currentMember) {
    final mediaUrl = post.resolvedMediaUrl ?? post.mediaUrl;
    final hasVideo = mediaUrl != null && mediaUrl.isNotEmpty;
    final commentCtrl = _getCommentController(post.id);

    final currentMemberId = currentMember?.id;
    final isAuthor = currentMemberId != null && post.author.id == currentMemberId;

    final authorAvatarUrl = post.author.avatarUrl != null
        ? AppConstants.resolveMediaUrl(post.author.avatarUrl)
        : null;

    final authorInitials = post.author.name.trim().isNotEmpty
        ? post.author.name.trim().split(' ').map((e) => e.isNotEmpty ? e[0].toUpperCase() : '').take(2).join()
        : 'M';

    final userAvatarUrl = currentMember?.avatarUrl != null
        ? AppConstants.resolveMediaUrl(currentMember.avatarUrl)
        : null;

    final userInitials = currentMember != null && currentMember.name.toString().isNotEmpty
        ? currentMember.name.toString().trim().split(' ').map((e) => e.isNotEmpty ? e[0].toUpperCase() : '').take(2).join()
        : 'U';

    final postText = post.body ?? '';

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 12,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // 1. Post Header (Author Avatar, Name, Handle, Time, Options Menu)
          Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(20),
                child: authorAvatarUrl != null && authorAvatarUrl.isNotEmpty
                    ? Image.network(
                        authorAvatarUrl,
                        width: 40,
                        height: 40,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => _buildFallbackUserAvatar(authorInitials),
                      )
                    : _buildFallbackUserAvatar(authorInitials),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            post.author.name,
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (post.author.isVerified) ...[
                          const SizedBox(width: 4),
                          const Icon(Icons.verified_rounded, size: 14, color: Color(0xFF20C875)),
                        ],
                        if (post.author.userId.isNotEmpty) ...[
                          const SizedBox(width: 6),
                          Text(
                            '@${post.author.userId}',
                            style: const TextStyle(color: Color(0xFF64748B), fontSize: 12),
                          ),
                        ],
                      ],
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        Text(
                          post.createdAt.isNotEmpty ? post.createdAt : 'just now',
                          style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                        ),
                        const SizedBox(width: 4),
                        const Text('·', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
                        const SizedBox(width: 4),
                        const Icon(Icons.videocam_outlined, size: 13, color: Color(0xFF94A3B8)),
                      ],
                    ),
                  ],
                ),
              ),
              IconButton(
                icon: const Icon(Icons.more_horiz_rounded, color: Color(0xFF64748B), size: 20),
                onPressed: () => _showPostOptionsMenu(context, post, feed),
              ),
            ],
          ),

          const SizedBox(height: 12),

          // 2. Post Caption / Description
          if (postText.trim().isNotEmpty) ...[
            Text(
              postText,
              style: const TextStyle(
                color: Color(0xFF0F172A),
                fontSize: 13.5,
                height: 1.45,
                fontWeight: FontWeight.normal,
              ),
            ),
            const SizedBox(height: 12),
          ],

          // 3. Video Player: 16:9 Black container with native HTML5 / custom controls
          ClipRRect(
            borderRadius: BorderRadius.circular(12),
            child: Container(
              width: double.infinity,
              color: Colors.black,
              child: AspectRatio(
                aspectRatio: 16 / 9,
                child: hasVideo
                    ? AppVideoPlayer(
                        videoUrl: mediaUrl,
                        autoPlay: false,
                        loop: false,
                        isMuted: false,
                      )
                    : Container(
                        color: const Color(0xFF090D16),
                        child: const Center(
                          child: Icon(Icons.play_circle_outline, color: Colors.white54, size: 56),
                        ),
                      ),
              ),
            ),
          ),

          // 4. Private Owner Reaction Banner (If current user created video & has reactions)
          if (isAuthor && post.likesCount > 0) ...[
            const SizedBox(height: 10),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: BoxDecoration(
                color: const Color(0xFFEFF6FF),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFBFDBFE)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.thumb_up_rounded, color: Color(0xFF2563EB), size: 14),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      '${post.likesCount} ${post.likesCount == 1 ? 'member' : 'members'} reacted to your video',
                      style: const TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600, color: Color(0xFF1E40AF)),
                    ),
                  ),
                  InkWell(
                    onTap: () => _showReactorsModal(context, post),
                    child: const Text(
                      'View Reactions',
                      style: TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF2563EB)),
                    ),
                  ),
                ],
              ),
            ),
          ],

          const SizedBox(height: 14),

          // 5. Post Actions Row (Pills: Like, Reactions count, Comments, Save, Share, View video)
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                // [ 👍 Like ] Pill (with reaction picker on long press)
                GestureDetector(
                  onLongPress: () => _showReactionPicker(context, post, feed),
                  child: _buildActionPill(
                    iconWidget: post.isLiked
                        ? Text(post.activeReactionEmoji, style: const TextStyle(fontSize: 13))
                        : null,
                    icon: post.isLiked ? null : Icons.thumb_up_alt_outlined,
                    label: post.isLiked ? post.activeReactionLabel : 'Like',
                    color: post.isLiked ? Color(post.activeReactionColor) : const Color(0xFF475569),
                    isActive: post.isLiked,
                    onTap: () => feed.toggleLike(post),
                  ),
                ),
                const SizedBox(width: 8),

                // [ 👍 0 ] Count Pill (tap opens reactors)
                _buildActionPill(
                  iconWidget: Text(post.isLiked ? post.activeReactionEmoji : '👍', style: const TextStyle(fontSize: 13)),
                  label: '${post.likesCount}',
                  color: const Color(0xFF475569),
                  onTap: () => _showReactorsModal(context, post),
                ),
                const SizedBox(width: 8),

                // [ 💬 Comments (X) ] Pill
                _buildActionPill(
                  icon: Icons.chat_bubble_outline,
                  label: 'Comments (${post.commentsCount})',
                  color: const Color(0xFF475569),
                  onTap: () => _showCommentsModal(context, post, feed),
                ),
                const SizedBox(width: 8),

                // [ 🔖 Save (X) ] Pill
                _buildActionPill(
                  icon: post.isSaved ? Icons.bookmark : Icons.bookmark_border,
                  label: post.isSaved ? 'Saved (${post.savesCount > 0 ? post.savesCount : 1})' : 'Save (${post.savesCount})',
                  color: post.isSaved ? const Color(0xFF2563EB) : const Color(0xFF475569),
                  isActive: post.isSaved,
                  onTap: () async {
                    await feed.toggleSavePost(post.id);
                    setState(() {});
                  },
                ),
                const SizedBox(width: 8),

                // [ ↗ Share ] Pill
                _buildActionPill(
                  icon: Icons.share_outlined,
                  label: 'Share',
                  color: const Color(0xFF475569),
                  onTap: () => _showShareModal(context, post, feed),
                ),
                const SizedBox(width: 14),

                // Far-right "View video ➔"
                InkWell(
                  onTap: () => _showCommentsModal(context, post, feed),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: const [
                      Text(
                        'View video',
                        style: TextStyle(
                          color: Color(0xFF2563EB),
                          fontWeight: FontWeight.w600,
                          fontSize: 12.5,
                        ),
                      ),
                      SizedBox(width: 4),
                      Icon(Icons.arrow_forward_rounded, color: Color(0xFF2563EB), size: 14),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          const SizedBox(height: 12),

          // 6. Comments Section
          if (post.commentsCount > 0) ...[
            InkWell(
              onTap: () => _showCommentsModal(context, post, feed),
              child: const Text(
                'View previous comments',
                style: TextStyle(
                  color: Color(0xFF2563EB),
                  fontWeight: FontWeight.w600,
                  fontSize: 13,
                ),
              ),
            ),
            const SizedBox(height: 10),
          ],

          // Inline Recent Comments (if available)
          if (post.recentComments.isNotEmpty) ...[
            ...post.recentComments.take(2).map((c) {
              final author = c['member'] as Map<String, dynamic>? ?? {};
              final cName = author['name']?.toString() ?? 'Member';
              final cBody = c['comment']?.toString() ?? c['body']?.toString() ?? '';
              final cTime = c['created_at']?.toString() ?? '';

              return Padding(
                padding: const EdgeInsets.only(bottom: 8),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    CircleAvatar(
                      radius: 14,
                      backgroundColor: const Color(0xFFE2E8F0),
                      child: Text(
                        cName.isNotEmpty ? cName[0].toUpperCase() : 'M',
                        style: const TextStyle(fontSize: 11, fontWeight: FontWeight.bold, color: Color(0xFF475569)),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF1F5F9),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(cName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF0F172A))),
                                Text(cTime, style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11)),
                              ],
                            ),
                            const SizedBox(height: 3),
                            Text(cBody, style: const TextStyle(fontSize: 12.5, color: Color(0xFF334155))),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
              );
            }),
            const SizedBox(height: 6),
          ] else ...[
            const Text(
              'Be the first to comment on this video.',
              style: TextStyle(
                fontStyle: FontStyle.italic,
                color: Color(0xFF94A3B8),
                fontSize: 13,
              ),
            ),
            const SizedBox(height: 12),
          ],

          // 7. Interactive Comment Composer Bar (Avatar + Pill TextField + Circular Blue Send Button)
          Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(18),
                child: userAvatarUrl != null && userAvatarUrl.isNotEmpty
                    ? Image.network(
                        userAvatarUrl,
                        width: 36,
                        height: 36,
                        fit: BoxFit.cover,
                        errorBuilder: (context, error, stackTrace) => _buildFallbackUserAvatar(userInitials),
                      )
                    : _buildFallbackUserAvatar(userInitials),
              ),
              const SizedBox(width: 10),

              // Rounded Pill TextField with Circular Send Button inside
              Expanded(
                child: Container(
                  height: 42,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(24),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  padding: const EdgeInsets.only(left: 14, right: 4),
                  child: Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: commentCtrl,
                          style: const TextStyle(fontSize: 13, color: Color(0xFF1E293B)),
                          decoration: const InputDecoration(
                            hintText: 'Write a comment...',
                            hintStyle: TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                            border: InputBorder.none,
                            isDense: true,
                          ),
                          onSubmitted: (val) async {
                            final text = val.trim();
                            if (text.isNotEmpty) {
                              commentCtrl.clear();
                              await feed.addComment(post, text);
                              setState(() {});
                            }
                          },
                        ),
                      ),
                      InkWell(
                        onTap: () async {
                          final text = commentCtrl.text.trim();
                          if (text.isNotEmpty) {
                            commentCtrl.clear();
                            await feed.addComment(post, text);
                            setState(() {});
                          }
                        },
                        borderRadius: BorderRadius.circular(20),
                        child: Container(
                          width: 32,
                          height: 32,
                          decoration: const BoxDecoration(
                            shape: BoxShape.circle,
                            color: Color(0xFF2563EB),
                          ),
                          child: const Icon(Icons.near_me_rounded, color: Colors.white, size: 16),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // Action Pill Component
  // -------------------------------------------------------------
  Widget _buildActionPill({
    Widget? iconWidget,
    IconData? icon,
    required String label,
    required Color color,
    bool isActive = false,
    VoidCallback? onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(20),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(
          color: isActive ? color.withOpacity(0.08) : const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(20),
          border: Border.all(
            color: isActive ? color.withOpacity(0.3) : const Color(0xFFE2E8F0),
            width: 1,
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (iconWidget != null)
              iconWidget
            else if (icon != null)
              Icon(icon, size: 15, color: color),
            const SizedBox(width: 6),
            Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 12.5,
                fontWeight: isActive ? FontWeight.w600 : FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFallbackUserAvatar(String initials) {
    return Container(
      width: 36,
      height: 36,
      decoration: const BoxDecoration(
        color: Color(0xFFEFF6FF),
        shape: BoxShape.circle,
      ),
      child: Center(
        child: Text(
          initials,
          style: const TextStyle(color: Color(0xFF2563EB), fontWeight: FontWeight.bold, fontSize: 13),
        ),
      ),
    );
  }

  // -------------------------------------------------------------
  // Options Menu (Save, Hide, Report)
  // -------------------------------------------------------------
  void _showPostOptionsMenu(BuildContext context, PostModel post, FeedProvider feed) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => SafeArea(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              margin: const EdgeInsets.only(top: 10, bottom: 6),
              width: 40,
              height: 4,
              decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
            ),
            ListTile(
              leading: Icon(post.isSaved ? Icons.bookmark_remove_rounded : Icons.bookmark_add_rounded, color: const Color(0xFF2563EB)),
              title: Text(post.isSaved ? 'Unsave Video' : 'Save Video'),
              onTap: () async {
                Navigator.pop(ctx);
                await feed.toggleSavePost(post.id);
                setState(() {});
              },
            ),
            ListTile(
              leading: const Icon(Icons.visibility_off_outlined, color: Color(0xFF64748B)),
              title: const Text('Hide Video'),
              onTap: () {
                Navigator.pop(ctx);
                setState(() {
                  _hiddenPostIds.add(post.id);
                });
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('Video hidden from your feed.')),
                );
              },
            ),
            ListTile(
              leading: const Icon(Icons.flag_outlined, color: Color(0xFFDC2626)),
              title: const Text('Report Video', style: TextStyle(color: Color(0xFFDC2626))),
              onTap: () {
                Navigator.pop(ctx);
                _showReportModal(context, post);
              },
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  // -------------------------------------------------------------
  // Reaction Picker Popup
  // -------------------------------------------------------------
  void _showReactionPicker(BuildContext context, PostModel post, FeedProvider feed) {
    final reactions = [
      {'type': 'like', 'emoji': '👍', 'label': 'Like', 'color': 0xFF2563EB},
      {'type': 'love', 'emoji': '❤️', 'label': 'Love', 'color': 0xFFDC2626},
      {'type': 'haha', 'emoji': '😂', 'label': 'Haha', 'color': 0xFFD97706},
      {'type': 'wow', 'emoji': '😮', 'label': 'Wow', 'color': 0xFF7C3AED},
      {'type': 'sad', 'emoji': '😢', 'label': 'Sad', 'color': 0xFF2563EB},
      {'type': 'angry', 'emoji': '😡', 'label': 'Angry', 'color': 0xFFEA580C},
    ];

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) => Container(
        margin: const EdgeInsets.all(16),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(32),
          boxShadow: [
            BoxShadow(color: Colors.black.withOpacity(0.12), blurRadius: 16, offset: const Offset(0, 4)),
          ],
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.spaceEvenly,
          children: reactions.map((r) {
            return InkWell(
              onTap: () {
                Navigator.pop(ctx);
                feed.toggleLike(post, reaction: r['type'] as String);
              },
              borderRadius: BorderRadius.circular(24),
              child: Padding(
                padding: const EdgeInsets.all(8.0),
                child: Text(
                  r['emoji'] as String,
                  style: const TextStyle(fontSize: 28),
                ),
              ),
            );
          }).toList(),
        ),
      ),
    );
  }

  // -------------------------------------------------------------
  // Reactors Modal
  // -------------------------------------------------------------
  void _showReactorsModal(BuildContext context, PostModel post) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        bool isLoading = true;
        List<Map<String, dynamic>> reactors = [];

        return StatefulBuilder(
          builder: (modalCtx, setModalState) {
            if (isLoading) {
              ApiClient.get('/posts/${post.id}/reactors').then((res) {
                if (res.success && res.data is Map && res.data['reactors'] is List) {
                  final list = (res.data['reactors'] as List).cast<Map<String, dynamic>>();
                  setModalState(() {
                    reactors = list;
                    isLoading = false;
                  });
                } else {
                  setModalState(() => isLoading = false);
                }
              });
            }

            return Container(
              height: MediaQuery.of(context).size.height * 0.55,
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Column(
                children: [
                  Container(
                    margin: const EdgeInsets.only(top: 10, bottom: 6),
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'People who reacted (${post.likesCount})',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close, size: 20),
                          onPressed: () => Navigator.of(ctx).pop(),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 1),
                  Expanded(
                    child: isLoading
                        ? const Center(child: CircularProgressIndicator(color: Color(0xFF2563EB)))
                        : reactors.isEmpty
                            ? const Center(child: Text('No reactions yet.', style: TextStyle(color: Colors.grey)))
                            : ListView.separated(
                                padding: const EdgeInsets.symmetric(vertical: 8),
                                itemCount: reactors.length,
                                separatorBuilder: (context, index) => const Divider(height: 1, indent: 64),
                                itemBuilder: (_, idx) {
                                  final r = reactors[idx];
                                  final name = r['name']?.toString() ?? 'Member';
                                  final emoji = r['emoji']?.toString() ?? '👍';
                                  return ListTile(
                                    leading: CircleAvatar(
                                      backgroundColor: const Color(0xFFEFF6FF),
                                      child: Text(name.isNotEmpty ? name[0].toUpperCase() : 'M', style: const TextStyle(color: Color(0xFF2563EB), fontWeight: FontWeight.bold)),
                                    ),
                                    title: Text(name, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                                    trailing: Text(emoji, style: const TextStyle(fontSize: 18)),
                                  );
                                },
                              ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  // -------------------------------------------------------------
  // Comments Modal
  // -------------------------------------------------------------
  void _showCommentsModal(BuildContext context, PostModel post, FeedProvider feed) {
    final textCtrl = TextEditingController();
    bool isLoading = true;
    List<Map<String, dynamic>> commentsList = List.from(post.recentComments);

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (modalCtx, setModalState) {
            if (isLoading) {
              ApiClient.get('/posts/${post.id}/comments').then((res) {
                if (res.success && res.data is Map && res.data['comments'] is List) {
                  final list = (res.data['comments'] as List).cast<Map<String, dynamic>>();
                  setModalState(() {
                    commentsList = list;
                    isLoading = false;
                  });
                } else {
                  setModalState(() => isLoading = false);
                }
              });
            }

            return Container(
              height: MediaQuery.of(context).size.height * 0.75,
              padding: EdgeInsets.only(bottom: MediaQuery.of(context).viewInsets.bottom),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Column(
                children: [
                  Container(
                    margin: const EdgeInsets.only(top: 10, bottom: 6),
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
                  ),
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        Text(
                          'Comments (${post.commentsCount})',
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close, size: 20),
                          onPressed: () => Navigator.of(ctx).pop(),
                        ),
                      ],
                    ),
                  ),
                  const Divider(height: 1),
                  Expanded(
                    child: isLoading
                        ? const Center(child: CircularProgressIndicator(color: Color(0xFF2563EB)))
                        : commentsList.isEmpty
                            ? const Center(
                                child: Text('Be the first to comment on this video.', style: TextStyle(color: Color(0xFF94A3B8), fontStyle: FontStyle.italic)),
                              )
                            : ListView.separated(
                                padding: const EdgeInsets.all(16),
                                itemCount: commentsList.length,
                                separatorBuilder: (context, index) => const SizedBox(height: 12),
                                itemBuilder: (_, idx) {
                                  final c = commentsList[idx];
                                  final author = c['member'] as Map<String, dynamic>? ?? {};
                                  final cName = author['name']?.toString() ?? 'Member';
                                  final cBody = c['comment']?.toString() ?? c['body']?.toString() ?? '';
                                  final cTime = c['created_at']?.toString() ?? '';

                                  return Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      CircleAvatar(
                                        radius: 16,
                                        backgroundColor: const Color(0xFFE2E8F0),
                                        child: Text(
                                          cName.isNotEmpty ? cName[0].toUpperCase() : 'M',
                                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF475569)),
                                        ),
                                      ),
                                      const SizedBox(width: 10),
                                      Expanded(
                                        child: Container(
                                          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                          decoration: BoxDecoration(
                                            color: const Color(0xFFF1F5F9),
                                            borderRadius: BorderRadius.circular(12),
                                          ),
                                          child: Column(
                                            crossAxisAlignment: CrossAxisAlignment.start,
                                            children: [
                                              Row(
                                                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                children: [
                                                  Text(cName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF0F172A))),
                                                  Text(cTime, style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11)),
                                                ],
                                              ),
                                              const SizedBox(height: 4),
                                              Text(cBody, style: const TextStyle(fontSize: 13, color: Color(0xFF334155))),
                                            ],
                                          ),
                                        ),
                                      ),
                                    ],
                                  );
                                },
                              ),
                  ),
                  const Divider(height: 1),
                  Padding(
                    padding: const EdgeInsets.all(12),
                    child: Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: textCtrl,
                            decoration: InputDecoration(
                              hintText: 'Write a comment...',
                              hintStyle: const TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                              filled: true,
                              fillColor: const Color(0xFFF8FAFC),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                              focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: const BorderSide(color: Color(0xFF2563EB))),
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        IconButton(
                          onPressed: () async {
                            final text = textCtrl.text.trim();
                            if (text.isNotEmpty) {
                              textCtrl.clear();
                              final ok = await feed.addComment(post, text);
                              if (ok) {
                                setModalState(() {
                                  commentsList.insert(0, {
                                    'comment': text,
                                    'created_at': 'Just now',
                                    'member': {'name': 'You'},
                                  });
                                });
                                setState(() {});
                              }
                            }
                          },
                          icon: const Icon(Icons.send_rounded, color: Color(0xFF2563EB)),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }

  // -------------------------------------------------------------
  // Share Modal
  // -------------------------------------------------------------
  void _showShareModal(BuildContext context, PostModel post, FeedProvider feed) {
    final msgCtrl = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Share Video Post', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Add a message to your shared video:', style: TextStyle(fontSize: 13, color: Color(0xFF64748B))),
            const SizedBox(height: 10),
            TextField(
              controller: msgCtrl,
              maxLines: 3,
              decoration: InputDecoration(
                hintText: 'Say something about this video...',
                hintStyle: const TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
              ),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel', style: TextStyle(color: Color(0xFF64748B))),
          ),
          ElevatedButton(
            onPressed: () async {
              Navigator.pop(ctx);
              await feed.sharePost(post, message: msgCtrl.text.trim());
              if (mounted) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Video shared successfully to your feed!'),
                    backgroundColor: Color(0xFF1E293B),
                  ),
                );
              }
            },
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF2563EB),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text('Share Now'),
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // Report Modal
  // -------------------------------------------------------------
  void _showReportModal(BuildContext context, PostModel post) {
    String selectedReason = 'Spam';
    final reasons = ['Spam', 'Harassment', 'False Information', 'Violence or Hate', 'Inappropriate Content'];

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (dialogCtx, setDialogState) => AlertDialog(
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
          title: const Text('Report Video', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('Why are you reporting this video?', style: TextStyle(fontSize: 13, color: Color(0xFF64748B))),
              const SizedBox(height: 8),
              ...reasons.map((r) => RadioListTile<String>(
                    dense: true,
                    contentPadding: EdgeInsets.zero,
                    title: Text(r, style: const TextStyle(fontSize: 13)),
                    value: r,
                    groupValue: selectedReason,
                    onChanged: (val) {
                      if (val != null) setDialogState(() => selectedReason = val);
                    },
                  )),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('Cancel', style: TextStyle(color: Color(0xFF64748B))),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.pop(ctx);
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Thank you. We have received your report.'),
                    backgroundColor: Color(0xFF1E293B),
                  ),
                );
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFFDC2626),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              ),
              child: const Text('Submit Report'),
            ),
          ],
        ),
      ),
    );
  }
}
