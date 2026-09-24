import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../models/post_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/feed_provider.dart';
import '../../widgets/app_video_player.dart';

class SavedPostsScreen extends StatefulWidget {
  const SavedPostsScreen({super.key});

  @override
  State<SavedPostsScreen> createState() => _SavedPostsScreenState();
}

class _SavedPostsScreenState extends State<SavedPostsScreen> {
  bool _isLoading = false;
  final Map<int, TextEditingController> _commentControllers = {};

  @override
  void initState() {
    super.initState();
    _loadSavedPosts();
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

  Future<void> _loadSavedPosts() async {
    setState(() => _isLoading = true);
    await context.read<FeedProvider>().fetchSavedPosts();
    if (mounted) setState(() => _isLoading = false);
  }

  // Full-screen image preview dialog
  void _showImagePreviewDialog(String imageUrl) {
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.transparent,
        insetPadding: const EdgeInsets.all(12),
        child: Stack(
          alignment: Alignment.topRight,
          children: [
            Center(
              child: ClipRRect(
                borderRadius: BorderRadius.circular(16),
                child: Image.network(
                  imageUrl,
                  fit: BoxFit.contain,
                  loadingBuilder: (c, child, p) => p == null
                      ? child
                      : const SizedBox(
                          height: 200,
                          child: Center(child: CircularProgressIndicator(color: Colors.white)),
                        ),
                  errorBuilder: (c, err, st) => Container(
                    padding: const EdgeInsets.all(24),
                    color: Colors.black87,
                    child: const Text('Unable to preview full image', style: TextStyle(color: Colors.white70)),
                  ),
                ),
              ),
            ),
            Positioned(
              top: 16,
              right: 16,
              child: IconButton(
                onPressed: () => Navigator.of(ctx).pop(),
                icon: const Icon(Icons.close, color: Colors.white, size: 26),
                style: IconButton.styleFrom(backgroundColor: Colors.black54),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Comments Bottom Sheet Modal
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
                  setModalState(() {
                    isLoading = false;
                  });
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
                        ? const Center(child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF2563EB)))
                        : commentsList.isEmpty
                            ? const Center(
                                child: Text('No comments yet. Write the first comment below!', style: TextStyle(color: Color(0xFF94A3B8))),
                              )
                            : ListView.builder(
                                padding: const EdgeInsets.all(16),
                                itemCount: commentsList.length,
                                itemBuilder: (cCtx, i) {
                                  final item = commentsList[i];
                                  final author = item['author'] is Map ? item['author'] : {};
                                  final name = author['name']?.toString() ?? 'Member';
                                  final comment = item['comment']?.toString() ?? '';
                                  final createdAt = item['created_at']?.toString() ?? '';
                                  final avatarUrl = author['avatar_url'] != null
                                      ? AppConstants.resolveMediaUrl(author['avatar_url']?.toString())
                                      : null;

                                  return Padding(
                                    padding: const EdgeInsets.only(bottom: 14),
                                    child: Row(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        CircleAvatar(
                                          radius: 16,
                                          backgroundColor: AppColors.primarySoft,
                                          backgroundImage: avatarUrl != null ? NetworkImage(avatarUrl) : null,
                                          child: avatarUrl == null
                                              ? Text(
                                                  name.isNotEmpty ? name[0].toUpperCase() : 'M',
                                                  style: const TextStyle(fontSize: 12, color: AppColors.primary, fontWeight: FontWeight.bold),
                                                )
                                              : null,
                                        ),
                                        const SizedBox(width: 10),
                                        Expanded(
                                          child: Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                            decoration: BoxDecoration(
                                              color: const Color(0xFFF8FAFC),
                                              borderRadius: BorderRadius.circular(12),
                                              border: Border.all(color: const Color(0xFFF1F5F9)),
                                            ),
                                            child: Column(
                                              crossAxisAlignment: CrossAxisAlignment.start,
                                              children: [
                                                Row(
                                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                                  children: [
                                                    Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF1E293B))),
                                                    Text(createdAt, style: const TextStyle(fontSize: 10.5, color: Color(0xFF94A3B8))),
                                                  ],
                                                ),
                                                const SizedBox(height: 3),
                                                Text(comment, style: const TextStyle(fontSize: 13, color: Color(0xFF334155), height: 1.35)),
                                              ],
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  );
                                },
                              ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      border: Border(top: BorderSide(color: Color(0xFFF1F5F9))),
                    ),
                    child: Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: textCtrl,
                            decoration: InputDecoration(
                              hintText: 'Write a comment...',
                              hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                              filled: true,
                              fillColor: const Color(0xFFF8FAFC),
                              border: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                              enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                              focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(24), borderSide: const BorderSide(color: Color(0xFF2563EB))),
                            ),
                            onSubmitted: (val) async {
                              final text = val.trim();
                              if (text.isNotEmpty) {
                                textCtrl.clear();
                                final ok = await feed.addComment(post, text);
                                if (ok) {
                                  setModalState(() {
                                    commentsList.insert(0, {
                                      'id': DateTime.now().millisecondsSinceEpoch,
                                      'comment': text,
                                      'created_at': 'Just now',
                                      'author': {'name': 'You'},
                                    });
                                  });
                                }
                              }
                            },
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
                                    'id': DateTime.now().millisecondsSinceEpoch,
                                    'comment': text,
                                    'created_at': 'Just now',
                                    'author': {'name': 'You'},
                                  });
                                });
                              }
                            }
                          },
                          icon: const Icon(Icons.near_me_rounded, color: Colors.white, size: 18),
                          style: IconButton.styleFrom(backgroundColor: const Color(0xFF2563EB)),
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

  // Reactors Bottom Sheet Modal
  void _showReactorsModal(BuildContext context, PostModel post) {
    bool isLoading = true;
    List<Map<String, dynamic>> reactorsList = [];

    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return StatefulBuilder(
          builder: (modalCtx, setModalState) {
            if (isLoading) {
              ApiClient.get('/posts/${post.id}/reactors').then((res) {
                if (res.success && res.data is Map && res.data['reactors'] is List) {
                  setModalState(() {
                    reactorsList = (res.data['reactors'] as List).cast<Map<String, dynamic>>();
                    isLoading = false;
                  });
                } else {
                  setModalState(() {
                    isLoading = false;
                  });
                }
              });
            }

            return Container(
              height: 380,
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
              decoration: const BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
              ),
              child: Column(
                children: [
                  Container(
                    width: 40,
                    height: 4,
                    margin: const EdgeInsets.only(bottom: 12),
                    decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
                  ),
                  Row(
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
                  const Divider(height: 1),
                  Expanded(
                    child: isLoading
                        ? const Center(child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF2563EB)))
                        : reactorsList.isEmpty
                            ? Center(
                                child: Text('${post.likesCount} members liked this post.', style: const TextStyle(color: Color(0xFF64748B))),
                              )
                            : ListView.separated(
                                padding: const EdgeInsets.symmetric(vertical: 12),
                                itemCount: reactorsList.length,
                                separatorBuilder: (_, __) => const SizedBox(height: 10),
                                itemBuilder: (_, i) {
                                  final r = reactorsList[i];
                                  final name = r['name']?.toString() ?? 'Member';
                                  final reaction = r['reaction']?.toString() ?? 'like';
                                  final emoji = PostModel.reactionEmojis[reaction] ?? '👍';

                                  return Row(
                                    children: [
                                      Stack(
                                        clipBehavior: Clip.none,
                                        children: [
                                          CircleAvatar(
                                            radius: 18,
                                            backgroundColor: const Color(0xFF3B82F6),
                                            child: Text(
                                              name.isNotEmpty ? name[0].toUpperCase() : 'M',
                                              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                                            ),
                                          ),
                                          Positioned(
                                            bottom: -2,
                                            right: -2,
                                            child: Container(
                                              padding: const EdgeInsets.all(1),
                                              decoration: const BoxDecoration(color: Colors.white, shape: BoxShape.circle),
                                              child: Text(emoji, style: const TextStyle(fontSize: 12)),
                                            ),
                                          ),
                                        ],
                                      ),
                                      const SizedBox(width: 12),
                                      Expanded(
                                        child: Text(name, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF1E293B))),
                                      ),
                                    ],
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

  // Share post modal
  void _showShareModal(BuildContext context, PostModel post, FeedProvider feed) {
    final captionCtrl = TextEditingController();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          padding: EdgeInsets.only(
            bottom: MediaQuery.of(context).viewInsets.bottom + 16,
            left: 16,
            right: 16,
            top: 10,
          ),
          decoration: const BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Center(
                child: Container(
                  width: 40,
                  height: 4,
                  margin: const EdgeInsets.only(bottom: 12),
                  decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
                ),
              ),
              const Text('Share Post', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A))),
              const SizedBox(height: 14),
              TextField(
                controller: captionCtrl,
                decoration: InputDecoration(
                  hintText: 'Say something about this post... (optional)',
                  hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                  filled: true,
                  fillColor: const Color(0xFFF8FAFC),
                  contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                  enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                ),
                maxLines: 2,
              ),
              const SizedBox(height: 10),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () async {
                    Navigator.pop(ctx);
                    final ok = await feed.sharePost(post, message: captionCtrl.text);
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Row(
                            children: [
                              Icon(ok ? Icons.check_circle : Icons.info, color: Colors.white, size: 18),
                              const SizedBox(width: 8),
                              Text(ok ? 'Post shared to your feed!' : 'Failed to share post'),
                            ],
                          ),
                          behavior: SnackBarBehavior.floating,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          backgroundColor: ok ? const Color(0xFF16A34A) : const Color(0xFFEF4444),
                        ),
                      );
                    }
                  },
                  icon: const Icon(Icons.repeat, size: 18, color: Colors.white),
                  label: const Text('Share to My Feed', style: TextStyle(fontWeight: FontWeight.bold, color: Colors.white)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2563EB),
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  ),
                ),
              ),
              const SizedBox(height: 12),
              ListTile(
                contentPadding: EdgeInsets.zero,
                leading: Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(color: const Color(0xFFEFF6FF), borderRadius: BorderRadius.circular(10)),
                  child: const Icon(Icons.link, color: Color(0xFF2563EB)),
                ),
                title: const Text('Copy Post Link', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                subtitle: const Text('Share link anywhere', style: TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                onTap: () {
                  Navigator.pop(ctx);
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: const Row(
                        children: [
                          Icon(Icons.check_circle_outline, color: Colors.white, size: 18),
                          SizedBox(width: 8),
                          Text('Post link copied to clipboard!'),
                        ],
                      ),
                      behavior: SnackBarBehavior.floating,
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      backgroundColor: const Color(0xFF1E293B),
                    ),
                  );
                },
              ),
            ],
          ),
        );
      },
    );
  }

  // Pill Action Button Helper
  Widget _buildActionPill({
    IconData? icon,
    Widget? iconWidget,
    required String label,
    required Color color,
    bool isActive = false,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(
          color: isActive ? const Color(0xFFEFF6FF) : Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isActive ? const Color(0xFFBFDBFE) : const Color(0xFFE2E8F0),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (iconWidget != null) ...[
              iconWidget,
              const SizedBox(width: 6),
            ] else if (icon != null) ...[
              Icon(icon, size: 15, color: color),
              const SizedBox(width: 6),
            ],
            Text(
              label,
              style: TextStyle(
                fontSize: 12.5,
                fontWeight: FontWeight.w600,
                color: color,
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final feed = context.watch<FeedProvider>();
    final auth = context.watch<AuthProvider>();
    final savedPosts = feed.savedPosts;
    final currentMember = auth.currentMember;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: const Text('Saved Posts & Bookmarks', style: TextStyle(color: Color(0xFF0F172A), fontSize: 18, fontWeight: FontWeight.bold)),
      ),
      body: _isLoading
          ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
          : savedPosts.isEmpty
              ? RefreshIndicator(
                  onRefresh: _loadSavedPosts,
                  color: AppColors.primary,
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    child: Container(
                      height: MediaQuery.of(context).size.height * 0.75,
                      alignment: Alignment.center,
                      padding: const EdgeInsets.all(32),
                      child: Column(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Container(
                            padding: const EdgeInsets.all(24),
                            decoration: BoxDecoration(
                              color: AppColors.primarySoft,
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(Icons.bookmark_border_rounded, size: 56, color: AppColors.primary),
                          ),
                          const SizedBox(height: 20),
                          const Text('No Saved Posts Yet', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.textPrimary)),
                          const SizedBox(height: 8),
                          const Text(
                            'Click the bookmark icon on any post in your social feed to save it here for later reading.',
                            textAlign: TextAlign.center,
                            style: TextStyle(color: AppColors.textMuted, fontSize: 13, height: 1.4),
                          ),
                        ],
                      ),
                    ),
                  ),
                )
              : RefreshIndicator(
                  onRefresh: _loadSavedPosts,
                  color: AppColors.primary,
                  child: ListView.separated(
                    padding: const EdgeInsets.symmetric(vertical: 12),
                    itemCount: savedPosts.length,
                    separatorBuilder: (context, index) => const SizedBox(height: 6),
                    itemBuilder: (ctx, i) {
                      final post = savedPosts[i];
                      return _buildCompletePostCard(post, feed, currentMember);
                    },
                  ),
                ),
    );
  }

  // -------------------------------------------------------------
  // Complete Rich Post Card Matching the Exact Feed Screen & Website Design
  // -------------------------------------------------------------
  Widget _buildCompletePostCard(PostModel post, FeedProvider feed, dynamic currentMember) {
    final authorName = post.authorName;
    final initials = authorName.isNotEmpty
        ? authorName.trim().split(' ').map((e) => e.isNotEmpty ? e[0].toUpperCase() : '').take(2).join()
        : 'M';

    final commentCtrl = _getCommentController(post.id);
    final mediaUrl = post.resolvedMediaUrl;
    final hasMedia = mediaUrl != null && mediaUrl.trim().isNotEmpty;
    final isVideo = hasMedia && (post.mediaType == 'video' ||
        mediaUrl.toLowerCase().endsWith('.mp4') ||
        mediaUrl.toLowerCase().endsWith('.mov') ||
        mediaUrl.toLowerCase().endsWith('.webm') ||
        mediaUrl.toLowerCase().endsWith('.avi') ||
        mediaUrl.toLowerCase().endsWith('.mkv') ||
        mediaUrl.toLowerCase().contains('/videos/'));

    final authorAvatarUrl = post.author.avatarUrl != null
        ? AppConstants.resolveMediaUrl(post.author.avatarUrl)
        : null;

    final userAvatarUrl = currentMember?.avatarUrl != null
        ? AppConstants.resolveMediaUrl(currentMember.avatarUrl)
        : null;

    final userInitials = currentMember != null && currentMember.name.toString().isNotEmpty
        ? currentMember.name.toString().trim().split(' ').map((e) => e.isNotEmpty ? e[0].toUpperCase() : '').take(2).join()
        : 'You';

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // 1. Header Row: Avatar + Name + Verification + Time + 3 Dots Menu
          Row(
            children: [
              ClipRRect(
                borderRadius: BorderRadius.circular(22),
                child: authorAvatarUrl != null && authorAvatarUrl.isNotEmpty
                    ? Image.network(
                        authorAvatarUrl,
                        width: 44,
                        height: 44,
                        fit: BoxFit.cover,
                        errorBuilder: (_, __, ___) => _buildFallbackAvatar(initials),
                      )
                    : _buildFallbackAvatar(initials),
              ),
              const SizedBox(width: 12),

              // Author Name + Time
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Flexible(
                          child: Text(
                            authorName,
                            style: const TextStyle(
                              color: Color(0xFF0F172A),
                              fontWeight: FontWeight.bold,
                              fontSize: 15,
                            ),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (post.author.isVerified) ...[
                          const SizedBox(width: 6),
                          const Icon(Icons.check_circle, color: Color(0xFF10B981), size: 16),
                        ],
                      ],
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        Text(
                          post.createdAtFormatted,
                          style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                        ),
                        const SizedBox(width: 4),
                        const Text('·', style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12)),
                        const SizedBox(width: 4),
                        const Icon(Icons.people, color: Color(0xFF94A3B8), size: 14),
                      ],
                    ),
                  ],
                ),
              ),

              // 3 Dots More Menu
              PopupMenuButton<String>(
                icon: const Icon(Icons.more_horiz, color: Color(0xFF64748B)),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                onSelected: (val) async {
                  if (val == 'save') {
                    await feed.toggleSavePost(post.id);
                  } else if (val == 'share') {
                    _showShareModal(context, post, feed);
                  }
                },
                itemBuilder: (ctx) => [
                  PopupMenuItem(
                    value: 'save',
                    child: Row(
                      children: [
                        Icon(post.isSaved ? Icons.bookmark_remove : Icons.bookmark_add, size: 18, color: AppColors.primary),
                        const SizedBox(width: 8),
                        Text(post.isSaved ? 'Remove from Saved' : 'Save Post'),
                      ],
                    ),
                  ),
                  const PopupMenuItem(
                    value: 'share',
                    child: Row(
                      children: [
                        Icon(Icons.share_outlined, size: 18, color: Color(0xFF64748B)),
                        SizedBox(width: 8),
                        Text('Share Post'),
                      ],
                    ),
                  ),
                ],
              ),
            ],
          ),

          // 2. Post Body Text
          if (post.body != null && post.body!.trim().isNotEmpty) ...[
            const SizedBox(height: 12),
            Text(
              post.body!,
              style: const TextStyle(
                color: Color(0xFF1E293B),
                fontSize: 14.5,
                height: 1.45,
              ),
            ),
          ],

          // 3. Post Media (Image or Video)
          if (hasMedia) ...[
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(16),
              child: Container(
                width: double.infinity,
                constraints: const BoxConstraints(maxHeight: 380),
                decoration: BoxDecoration(
                  color: Colors.black12,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: isVideo
                    ? Container(
                        height: 280,
                        color: Colors.black,
                        child: AppVideoPlayer(
                          videoUrl: mediaUrl,
                          autoPlay: false,
                          loop: false,
                          isMuted: false,
                        ),
                      )
                    : GestureDetector(
                        onTap: () => _showImagePreviewDialog(mediaUrl),
                        child: Image.network(
                          mediaUrl,
                          fit: BoxFit.cover,
                          loadingBuilder: (context, child, loadingProgress) {
                            if (loadingProgress == null) return child;
                            return Container(
                              height: 220,
                              color: Colors.grey.shade100,
                              alignment: Alignment.center,
                              child: const CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary),
                            );
                          },
                          errorBuilder: (context, error, stackTrace) {
                            return Container(
                              height: 160,
                              color: Colors.grey.shade100,
                              alignment: Alignment.center,
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Icon(Icons.image_outlined, size: 32, color: Color(0xFF94A3B8)),
                                  const SizedBox(height: 6),
                                  Text('Image shared by $authorName', style: const TextStyle(color: Color(0xFF64748B), fontSize: 13)),
                                ],
                              ),
                            );
                          },
                        ),
                      ),
              ),
            ),
          ],

          // 4. Reaction Banner (Green Box: "1 member reacted to your post" | "View Reactions")
          if (post.likesCount > 0) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: const Color(0xFFF0FDF4),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFBBF7D0)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.thumb_up_alt_outlined, color: Color(0xFF16A34A), size: 16),
                  const SizedBox(width: 8),
                  Expanded(
                    child: RichText(
                      text: TextSpan(
                        text: '${post.likesCount} ${post.likesCount == 1 ? "member" : "members"} ',
                        style: const TextStyle(
                          fontWeight: FontWeight.bold,
                          color: Color(0xFF15803D),
                          fontSize: 13,
                        ),
                        children: const [
                          TextSpan(
                            text: 'reacted to your post',
                            style: TextStyle(fontWeight: FontWeight.normal),
                          ),
                        ],
                      ),
                    ),
                  ),
                  InkWell(
                    onTap: () => _showReactorsModal(context, post),
                    child: const Text(
                      'View Reactions',
                      style: TextStyle(
                        color: Color(0xFF16A34A),
                        fontWeight: FontWeight.bold,
                        fontSize: 12.5,
                        decoration: TextDecoration.underline,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ],

          const SizedBox(height: 14),

          // 5. Action Row: Pill outline buttons matching Image
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                // [ 👍 Like ] Pill
                _buildActionPill(
                  iconWidget: post.isLiked
                      ? Text(post.activeReactionEmoji, style: const TextStyle(fontSize: 14))
                      : null,
                  icon: post.isLiked ? null : Icons.thumb_up_alt_outlined,
                  label: post.isLiked ? post.activeReactionLabel : 'Like',
                  color: post.isLiked ? Color(post.activeReactionColor) : const Color(0xFF475569),
                  isActive: post.isLiked,
                  onTap: () => feed.toggleLike(post),
                ),
                const SizedBox(width: 8),

                // [ 👍 1 ] Count Pill -> Opens Reactors Modal
                _buildActionPill(
                  iconWidget: Text(post.isLiked ? post.activeReactionEmoji : '👍', style: const TextStyle(fontSize: 13)),
                  label: '${post.likesCount}',
                  color: const Color(0xFF475569),
                  onTap: () => _showReactorsModal(context, post),
                ),
                const SizedBox(width: 8),

                // [ 💬 Comments (3) ] Pill -> Opens Comments Sheet
                _buildActionPill(
                  icon: Icons.chat_bubble_outline,
                  label: 'Comments (${post.commentsCount})',
                  color: const Color(0xFF475569),
                  onTap: () => _showCommentsModal(context, post, feed),
                ),
                const SizedBox(width: 8),

                // [ 🔖 Saved (1) ] Pill -> Toggles Saved
                _buildActionPill(
                  icon: post.isSaved ? Icons.bookmark : Icons.bookmark_border,
                  label: post.isSaved ? 'Saved (${post.savesCount > 0 ? post.savesCount : 1})' : 'Save (${post.savesCount})',
                  color: post.isSaved ? const Color(0xFF2563EB) : const Color(0xFF475569),
                  isActive: post.isSaved,
                  onTap: () async {
                    await feed.toggleSavePost(post.id);
                    if (context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Row(
                            children: [
                              Icon(post.isSaved ? Icons.bookmark_added : Icons.bookmark_remove, color: Colors.white, size: 18),
                              const SizedBox(width: 8),
                              Text(post.isSaved ? 'Post saved to bookmarks!' : 'Post removed from bookmarks'),
                            ],
                          ),
                          behavior: SnackBarBehavior.floating,
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                          backgroundColor: const Color(0xFF1E293B),
                          duration: const Duration(seconds: 2),
                        ),
                      );
                    }
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
                const SizedBox(width: 8),

                // [ 🔁 0 Shares ] Pill
                _buildActionPill(
                  icon: Icons.repeat,
                  label: '${post.sharesCount} Shares',
                  color: const Color(0xFF475569),
                  onTap: () => _showShareModal(context, post, feed),
                ),
              ],
            ),
          ),

          const SizedBox(height: 14),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          const SizedBox(height: 12),

          // 6. Comments Section Header
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
            const SizedBox(height: 8),
          ],

          const Text(
            'Be the first to comment.',
            style: TextStyle(
              fontStyle: FontStyle.italic,
              color: Color(0xFF94A3B8),
              fontSize: 13,
            ),
          ),

          const SizedBox(height: 14),

          // 7. Interactive Comment Input Bar (Avatar + Pill TextField + Send Button)
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
                        errorBuilder: (_, __, ___) => _buildFallbackUserAvatar(userInitials),
                      )
                    : _buildFallbackUserAvatar(userInitials),
              ),
              const SizedBox(width: 10),

              // Rounded Pill TextField with Send Button
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

  Widget _buildFallbackAvatar(String initials) {
    return Container(
      width: 44,
      height: 44,
      decoration: const BoxDecoration(
        shape: BoxShape.circle,
        gradient: LinearGradient(
          colors: [Color(0xFF3B82F6), Color(0xFF6366F1)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Text(
          initials,
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
        ),
      ),
    );
  }

  Widget _buildFallbackUserAvatar(String initials) {
    return Container(
      width: 36,
      height: 36,
      decoration: const BoxDecoration(
        shape: BoxShape.circle,
        gradient: LinearGradient(
          colors: [Color(0xFF3B82F6), Color(0xFF6366F1)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Text(
          initials,
          style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
        ),
      ),
    );
  }
}
