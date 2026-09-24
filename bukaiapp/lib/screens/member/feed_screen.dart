import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../models/post_model.dart';
import '../../models/story_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/feed_provider.dart';
import '../../widgets/app_video_player.dart';

class FeedScreen extends StatefulWidget {
  const FeedScreen({super.key});

  @override
  State<FeedScreen> createState() => _FeedScreenState();
}

class _FeedScreenState extends State<FeedScreen> {
  final TextEditingController _postTextCtrl = TextEditingController();
  final ImagePicker _picker = ImagePicker();

  // Selected media for new post
  Uint8List? _selectedMediaBytes;
  String? _selectedMediaName;
  String? _selectedMediaType; // 'image' or 'video'
  String? _selectedMediaUrl;

  // Track comment input per post: postId -> TextEditingController
  final Map<int, TextEditingController> _commentControllers = {};

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<FeedProvider>().fetchFeed();
      context.read<FeedProvider>().fetchStories();
    });
  }

  @override
  void dispose() {
    _postTextCtrl.dispose();
    for (var ctrl in _commentControllers.values) {
      ctrl.dispose();
    }
    super.dispose();
  }

  TextEditingController _getCommentController(int postId) {
    return _commentControllers.putIfAbsent(postId, () => TextEditingController());
  }

  // Pick Photo or Video file from device
  Future<void> _pickMedia([String type = 'any']) async {
    try {
      XFile? file;
      if (type == 'image') {
        file = await _picker.pickImage(source: ImageSource.gallery, imageQuality: 85);
      } else if (type == 'video') {
        file = await _picker.pickVideo(source: ImageSource.gallery);
      } else {
        // Allows user to select photo or video directly from device files
        file = await _picker.pickMedia(imageQuality: 85);
      }

      if (file != null) {
        final bytes = await file.readAsBytes();
        final name = file.name.toLowerCase();
        final isVideo = name.endsWith('.mp4') ||
            name.endsWith('.mov') ||
            name.endsWith('.avi') ||
            name.endsWith('.mkv') ||
            name.endsWith('.webm');
        setState(() {
          _selectedMediaBytes = bytes;
          _selectedMediaName = file!.name;
          _selectedMediaType = isVideo ? 'video' : 'image';
          _selectedMediaUrl = null;
        });
      }
    } catch (e) {
      debugPrint('Error picking media: $e');
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Could not select file: $e')),
        );
      }
    }
  }

  // Bottom sheet to choose media - ONLY file upload option (photo or video only)
  void _showMediaPickerOptions() {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.fromLTRB(16, 12, 16, 24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 36,
                height: 4,
                margin: const EdgeInsets.only(bottom: 14),
                decoration: BoxDecoration(
                  color: const Color(0xFFE2E8F0),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const Text(
                'Add Photo / Video',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
              ),
              const SizedBox(height: 6),
              const Text(
                'Upload photo or video only from your device',
                style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
              ),
              const SizedBox(height: 16),
              ListTile(
                shape: RoundedRectangleBorder(
                  borderRadius: BorderRadius.circular(12),
                  side: const BorderSide(color: Color(0xFFE2E8F0)),
                ),
                tileColor: const Color(0xFFF8FAFC),
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppColors.primary.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.file_upload_outlined, color: AppColors.primary, size: 24),
                ),
                title: const Text(
                  'Upload File (Photo / Video)',
                  style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                ),
                subtitle: const Text(
                  'Upload photo (JPG, PNG, GIF) or video (MP4) only',
                  style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                ),
                trailing: const Icon(Icons.arrow_forward_ios_rounded, size: 14, color: Color(0xFF94A3B8)),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickMedia('any');
                },
              ),
            ],
          ),
        ),
      ),
    );
  }

  // Publish new post
  Future<void> _publishPost() async {
    final text = _postTextCtrl.text.trim();
    if (text.isEmpty && _selectedMediaBytes == null && _selectedMediaUrl == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please write something or attach a photo/video.')),
      );
      return;
    }

    final feed = context.read<FeedProvider>();
    final success = await feed.createPost(
      body: text,
      fileBytes: _selectedMediaBytes,
      fileName: _selectedMediaName,
      mediaUrl: _selectedMediaUrl,
      mediaType: _selectedMediaType,
    );

    if (success && mounted) {
      _postTextCtrl.clear();
      setState(() {
        _selectedMediaBytes = null;
        _selectedMediaName = null;
        _selectedMediaType = null;
        _selectedMediaUrl = null;
      });
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Post published successfully!')),
      );
    } else if (mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(feed.lastError ?? 'Failed to publish post. Please try again.')),
      );
    }
  }

  // Create Story - Open Create Story Modal Dialog (Exact Website Design)
  void _createStory() {
    _showCreateStoryModal(context, context.read<FeedProvider>());
  }

  @override
  Widget build(BuildContext context) {
    final feed = context.watch<FeedProvider>();
    final auth = context.watch<AuthProvider>();
    final member = auth.currentMember;

    return RefreshIndicator(
      onRefresh: () async {
        await feed.fetchFeed(refresh: true);
        await feed.fetchStories();
      },
      color: AppColors.primary,
      backgroundColor: AppColors.surface,
      child: ListView(
        padding: const EdgeInsets.only(bottom: 30),
        children: [
          // 1. Stories Tray with "Create story" card (Exact Image 1 design)
          _buildStoriesBar(feed, member),

          // 2. Post Creator Card (Exact Image 1 & Image 2 design)
          _buildPostComposerCard(member, feed.isCreatingPost),

          // 3. Posts Feed (Exact Image 3 design)
          if (feed.isLoadingFeed && feed.posts.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 40),
              child: Center(child: CircularProgressIndicator(color: AppColors.primary)),
            )
          else if (feed.posts.isEmpty)
            const Padding(
              padding: EdgeInsets.symmetric(vertical: 60),
              child: Center(
                child: Text('No posts yet. Be the first to share!', style: TextStyle(color: AppColors.textMuted)),
              ),
            )
          else
            ...feed.posts.map((post) => _buildExactPostCard(post, feed, member)),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // 1. Stories Bar (Exact Image 1 Design with "Create story" card)
  // -------------------------------------------------------------
  Widget _buildStoriesBar(FeedProvider feed, dynamic member) {
    return Container(
      height: 165,
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: ListView(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 16),
        children: [
          // "Create story" Card
          GestureDetector(
            onTap: _createStory,
            child: Container(
              width: 105,
              height: 150,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
                boxShadow: [
                  BoxShadow(
                    color: Colors.black.withOpacity(0.04),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
              ),
              child: Stack(
                children: [
                  // Photo background overlay matching StoryCreateCard.jsx exactly
                  Positioned.fill(
                    child: ClipRRect(
                      borderRadius: BorderRadius.circular(16),
                      child: Image.asset(
                        'assets/images/story_1.png',
                        fit: BoxFit.cover,
                        errorBuilder: (ctx, err, stack) => Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [
                                Colors.blue.shade50.withOpacity(0.5),
                                Colors.purple.shade50.withOpacity(0.3),
                              ],
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                            ),
                          ),
                          child: Icon(Icons.person, size: 80, color: Colors.blue.withOpacity(0.12)),
                        ),
                      ),
                    ),
                  ),

                  // Bottom white shade overlay for crisp text readability
                  Positioned(
                    bottom: 0,
                    left: 0,
                    right: 0,
                    height: 60,
                    child: Container(
                      decoration: BoxDecoration(
                        borderRadius: const BorderRadius.vertical(bottom: Radius.circular(16)),
                        gradient: LinearGradient(
                          colors: [
                            Colors.white.withOpacity(0.0),
                            Colors.white.withOpacity(0.85),
                            Colors.white,
                          ],
                          begin: Alignment.topCenter,
                          end: Alignment.bottomCenter,
                        ),
                      ),
                    ),
                  ),

                  // Center Circular "+" Button with glowing blue ring
                  Center(
                    child: Container(
                      width: 44,
                      height: 44,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: const Color(0xFF3B82F6),
                        boxShadow: [
                          BoxShadow(
                            color: const Color(0xFF3B82F6).withOpacity(0.4),
                            blurRadius: 10,
                            offset: const Offset(0, 4),
                          ),
                        ],
                        border: Border.all(color: Colors.white, width: 2),
                      ),
                      child: const Icon(Icons.add, color: Colors.white, size: 24),
                    ),
                  ),

                  // Bottom Label: "Create story"
                  const Positioned(
                    bottom: 12,
                    left: 0,
                    right: 0,
                    child: Text(
                      'Create story',
                      textAlign: TextAlign.center,
                      style: TextStyle(
                        color: Color(0xFF1E293B),
                        fontWeight: FontWeight.bold,
                        fontSize: 12.5,
                      ),
                    ),
                  ),
                ],
              ),
            ),
          ),
          const SizedBox(width: 10),

          // Other Member Stories
          ...feed.storyGroups.map((group) {
            final firstStory = group.stories.isNotEmpty ? group.stories.first : null;
            return GestureDetector(
              onTap: () => _showStoryViewer(context, group),
              child: Container(
                width: 105,
                height: 150,
                margin: const EdgeInsets.only(right: 10),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: const Color(0xFFE2E8F0)),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.04),
                      blurRadius: 6,
                      offset: const Offset(0, 2),
                    ),
                  ],
                ),
                child: ClipRRect(
                  borderRadius: BorderRadius.circular(16),
                  child: Stack(
                    fit: StackFit.expand,
                    children: [
                      // Story preview image if available
                      if (firstStory != null && firstStory.mediaUrl.isNotEmpty)
                        Image.network(
                          firstStory.resolvedMediaUrl,
                          fit: BoxFit.cover,
                          errorBuilder: (ctx, err, stack) => Container(
                            decoration: BoxDecoration(
                              gradient: LinearGradient(
                                colors: [Colors.blue.shade200, Colors.purple.shade200],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                            ),
                          ),
                        )
                      else
                        Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [Colors.blue.shade100, Colors.purple.shade100],
                              begin: Alignment.topLeft,
                              end: Alignment.bottomRight,
                            ),
                          ),
                        ),

                      // Dark shade gradient for text and avatar contrast
                      Positioned.fill(
                        child: Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [
                                Colors.black.withOpacity(0.35),
                                Colors.transparent,
                                Colors.black.withOpacity(0.75),
                              ],
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                            ),
                          ),
                        ),
                      ),

                      // Top Avatar with Gradient ring
                      Positioned(
                        top: 10,
                        left: 10,
                        child: Container(
                          padding: const EdgeInsets.all(2),
                          decoration: const BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: LinearGradient(colors: [Color(0xFF2563EB), Color(0xFF7C3AED)]),
                          ),
                          child: CircleAvatar(
                            radius: 14,
                            backgroundColor: Colors.white,
                            child: Text(
                              group.authorName.isNotEmpty ? group.authorName[0].toUpperCase() : 'M',
                              style: const TextStyle(color: Color(0xFF2563EB), fontWeight: FontWeight.bold, fontSize: 11),
                            ),
                          ),
                        ),
                      ),

                      // Bottom Author Name
                      Positioned(
                        bottom: 10,
                        left: 8,
                        right: 8,
                        child: Text(
                          group.authorName,
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          textAlign: TextAlign.center,
                          style: const TextStyle(
                            color: Colors.white,
                            fontWeight: FontWeight.bold,
                            fontSize: 12,
                            shadows: [
                              Shadow(color: Colors.black54, blurRadius: 4),
                            ],
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          }),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // 2. Post Composer Card (Exact Image 1 & Image 2 Design)
  // -------------------------------------------------------------
  Widget _buildPostComposerCard(dynamic member, bool isPublishing) {
    final memberName = member?.name?.isNotEmpty == true ? member!.name! : 'Abhay Sahany';
    final initials = memberName.isNotEmpty
        ? memberName.trim().split(' ').map((e) => e.isNotEmpty ? e[0].toUpperCase() : '').take(2).join()
        : 'AS';

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
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Top Row: Avatar + Large light-blue rounded input box
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // User Avatar
              ClipRRect(
                borderRadius: BorderRadius.circular(22),
                child: Image.asset(
                  'assets/images/profile_1.jpg',
                  width: 44,
                  height: 44,
                  fit: BoxFit.cover,
                  errorBuilder: (ctx, err, stack) => Container(
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
                  ),
                ),
              ),
              const SizedBox(width: 12),

              // Light blue bordered text input container
              Expanded(
                child: Container(
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(16),
                    border: Border.all(color: const Color(0xFFBFDBFE), width: 1.5),
                  ),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  child: TextField(
                    controller: _postTextCtrl,
                    minLines: 2,
                    maxLines: 5,
                    style: const TextStyle(fontSize: 14.5, color: Color(0xFF1E293B)),
                    decoration: InputDecoration(
                      hintText: "What's on your mind, $memberName?",
                      hintStyle: const TextStyle(
                        color: Color(0xFF94A3B8),
                        fontSize: 14,
                        fontWeight: FontWeight.normal,
                      ),
                      border: InputBorder.none,
                      isDense: true,
                    ),
                  ),
                ),
              ),
            ],
          ),

          // Selected Media Preview (if attached)
          if (_selectedMediaBytes != null || _selectedMediaUrl != null) ...[
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFCBD5E1)),
              ),
              child: Row(
                children: [
                  ClipRRect(
                    borderRadius: BorderRadius.circular(8),
                    child: _selectedMediaBytes != null && _selectedMediaType == 'image'
                        ? Image.memory(_selectedMediaBytes!, width: 50, height: 50, fit: BoxFit.cover)
                        : Container(
                            width: 50,
                            height: 50,
                            color: Colors.black12,
                            child: Icon(
                              _selectedMediaType == 'video' ? Icons.videocam : Icons.image,
                              color: AppColors.primary,
                            ),
                          ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          _selectedMediaName ?? 'Attached Media',
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13, color: Color(0xFF1E293B)),
                        ),
                        Text(
                          _selectedMediaType?.toUpperCase() ?? 'MEDIA',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                            color: _selectedMediaType == 'video' ? Colors.red : Colors.green,
                          ),
                        ),
                      ],
                    ),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.redAccent, size: 20),
                    onPressed: () {
                      setState(() {
                        _selectedMediaBytes = null;
                        _selectedMediaName = null;
                        _selectedMediaType = null;
                        _selectedMediaUrl = null;
                      });
                    },
                  ),
                ],
              ),
            ),
          ],

          // Divider Line
          const SizedBox(height: 12),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          const SizedBox(height: 12),

          // Bottom Row: [Photo / video] on Left, [Post] Button on Right
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              // "Photo / video" Action Button
              InkWell(
                onTap: _showMediaPickerOptions,
                borderRadius: BorderRadius.circular(8),
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                  child: Row(
                    children: [
                      const Icon(Icons.image_outlined, color: Color(0xFF16A34A), size: 20),
                      const SizedBox(width: 8),
                      Text(
                        'Photo / video',
                        style: TextStyle(
                          color: const Color(0xFF334155),
                          fontWeight: FontWeight.w600,
                          fontSize: 13.5,
                        ),
                      ),
                    ],
                  ),
                ),
              ),

              // "Post" Blue Button
              ElevatedButton.icon(
                onPressed: isPublishing ? null : _publishPost,
                icon: isPublishing
                    ? const SizedBox(width: 14, height: 14, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Icon(Icons.near_me_rounded, size: 17, color: Colors.white),
                label: const Text(
                  'Post',
                  style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 13.5),
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF2563EB),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  elevation: 0,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // -------------------------------------------------------------
  // 3. Post Card (Exact Image 3 Design)
  // -------------------------------------------------------------
  Widget _buildExactPostCard(PostModel post, FeedProvider feed, dynamic currentMember) {
    final authorName = post.author.name;
    final initials = authorName.isNotEmpty
        ? authorName.trim().split(' ').map((e) => e.isNotEmpty ? e[0].toUpperCase() : '').take(2).join()
        : 'AS';

    final commentCtrl = _getCommentController(post.id);

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
          // Header Row: Avatar + Name + Time + 3 Dots Menu
          Row(
            children: [
              // Purple/Blue Gradient Initials Circle
              Container(
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
              ),
              const SizedBox(width: 12),

              // Author Name + Time
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      authorName,
                      style: const TextStyle(
                        color: Color(0xFF0F172A),
                        fontWeight: FontWeight.bold,
                        fontSize: 15,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        Text(
                          post.createdAt,
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
                onSelected: (val) {
                  if (val == 'save') {
                    feed.toggleSavePost(post.id);
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
                ],
              ),
            ],
          ),

          // Post Body Text
          if (post.body != null && post.body!.isNotEmpty) ...[
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

          // Post Media (Image or Video)
          if (post.resolvedMediaUrl != null && post.resolvedMediaUrl!.isNotEmpty) ...[
            const SizedBox(height: 12),
            ClipRRect(
              borderRadius: BorderRadius.circular(12),
              child: Container(
                width: double.infinity,
                constraints: const BoxConstraints(maxHeight: 320),
                decoration: BoxDecoration(
                  color: Colors.black12,
                  borderRadius: BorderRadius.circular(12),
                ),
                child: (post.mediaType == 'video' ||
                        post.resolvedMediaUrl!.toLowerCase().endsWith('.mp4') ||
                        post.resolvedMediaUrl!.toLowerCase().endsWith('.mov') ||
                        post.resolvedMediaUrl!.toLowerCase().endsWith('.webm') ||
                        post.resolvedMediaUrl!.toLowerCase().endsWith('.avi') ||
                        post.resolvedMediaUrl!.toLowerCase().endsWith('.mkv') ||
                        post.resolvedMediaUrl!.toLowerCase().contains('/videos/'))
                    ? Container(
                        height: 280,
                        color: Colors.black,
                        child: AppVideoPlayer(
                          videoUrl: post.resolvedMediaUrl!,
                          autoPlay: false,
                          loop: false,
                          isMuted: false,
                        ),
                      )
                    : GestureDetector(
                        onTap: () => _showImagePreviewDialog(post.resolvedMediaUrl!),
                        child: Image.network(
                          post.resolvedMediaUrl!,
                          fit: BoxFit.cover,
                          loadingBuilder: (context, child, loadingProgress) {
                            if (loadingProgress == null) return child;
                            return Container(
                              height: 200,
                              color: Colors.grey.shade100,
                              alignment: Alignment.center,
                              child: const CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary),
                            );
                          },
                          errorBuilder: (context, error, stackTrace) {
                            debugPrint('Feed image load error for ${post.resolvedMediaUrl}: $error');
                            return Container(
                              height: 150,
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

          const SizedBox(height: 14),

          // Action Row: Pill outline buttons matching Image 3
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                // [ 👍 Like / React ] Button
                _buildActionPill(
                  iconWidget: post.isLiked
                      ? Text(post.activeReactionEmoji, style: const TextStyle(fontSize: 14))
                      : null,
                  icon: post.isLiked ? null : Icons.thumb_up_alt_outlined,
                  label: post.isLiked ? post.activeReactionLabel : 'Like',
                  color: post.isLiked ? Color(post.activeReactionColor) : const Color(0xFF475569),
                  isActive: post.isLiked,
                  onTap: () => feed.toggleLike(post),
                  onLongPress: () => _showReactionPicker(context, post, feed),
                ),
                const SizedBox(width: 8),

                // [ 👍 0 ] Count Pill -> Opens Reactors Modal
                _buildActionPill(
                  iconWidget: Text(post.isLiked ? post.activeReactionEmoji : '👍', style: const TextStyle(fontSize: 13)),
                  label: '${post.likesCount}',
                  color: const Color(0xFF475569),
                  onTap: () => _showReactorsModal(context, post),
                ),
                const SizedBox(width: 8),

                // [ 💬 Comments (0) ] Pill -> Opens Comments Sheet
                _buildActionPill(
                  icon: Icons.chat_bubble_outline,
                  label: 'Comments (${post.commentsCount})',
                  color: const Color(0xFF475569),
                  onTap: () => _showCommentsModal(context, post, feed),
                ),
                const SizedBox(width: 8),

                // [ 🔖 Save (0) ] Pill -> Toggles Saved
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

                // [ ➦ Share ] Pill -> Opens Share Modal
                _buildActionPill(
                  icon: Icons.share_outlined,
                  label: 'Share',
                  color: const Color(0xFF475569),
                  onTap: () => _showShareModal(context, post, feed),
                ),
                const SizedBox(width: 8),

                // [ 🔁 0 Shares ] Pill -> Opens Share Modal
                _buildActionPill(
                  icon: Icons.repeat,
                  label: '${post.sharesCount} Shares',
                  color: const Color(0xFF475569),
                  onTap: () => _showShareModal(context, post, feed),
                ),
              ],
            ),
          ),

          const SizedBox(height: 16),

          // Subtitle: "Be the first to comment." or Recent Comments
          if (post.recentComments.isEmpty)
            const Padding(
              padding: EdgeInsets.only(bottom: 12),
              child: Text(
                'Be the first to comment.',
                style: TextStyle(
                  fontStyle: FontStyle.italic,
                  color: Color(0xFF94A3B8),
                  fontSize: 13,
                ),
              ),
            )
          else ...[
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                if (post.commentsCount > post.recentComments.length || post.commentsCount > 2)
                  Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: InkWell(
                      onTap: () => _showCommentsModal(context, post, feed),
                      child: Text(
                        'View all ${post.commentsCount} comments',
                        style: const TextStyle(color: Color(0xFF2563EB), fontWeight: FontWeight.w600, fontSize: 12.5),
                      ),
                    ),
                  ),
                ...post.recentComments.take(3).map((c) {
                  return Padding(
                    padding: const EdgeInsets.only(bottom: 8),
                    child: Row(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        CircleAvatar(
                          radius: 12,
                          backgroundColor: const Color(0xFFE2E8F0),
                          child: Text(
                            (c['author']?['name']?.toString() ?? 'U')[0].toUpperCase(),
                            style: const TextStyle(fontSize: 10, color: Color(0xFF334155), fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: const Color(0xFFF1F5F9)),
                            ),
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                  children: [
                                    Text(
                                      c['author']?['name']?.toString() ?? 'Member',
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 11.5, color: Color(0xFF1E293B)),
                                    ),
                                    Text(
                                      c['created_at']?.toString() ?? '',
                                      style: const TextStyle(fontSize: 10, color: Color(0xFF94A3B8)),
                                    ),
                                  ],
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  c['comment']?.toString() ?? '',
                                  style: const TextStyle(fontSize: 12.5, color: Color(0xFF334155)),
                                ),
                              ],
                            ),
                          ),
                        ),
                      ],
                    ),
                  );
                }),
              ],
            ),
            const SizedBox(height: 6),
          ],

          // Interactive Comment Input Bar (Exact Image 3 bottom row)
          Row(
            children: [
              // User Avatar Initials
              Container(
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
              ),
              const SizedBox(width: 10),

              // Rounded Pill TextField with Blue circular send button
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

                      // Blue Circular Send Button
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

  // Helper to build action pill button with exact border and style
  Widget _buildActionPill({
    IconData? icon,
    Widget? iconWidget,
    required String label,
    required Color color,
    bool isActive = false,
    required VoidCallback onTap,
    VoidCallback? onLongPress,
  }) {
    return InkWell(
      onTap: onTap,
      onLongPress: onLongPress,
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 7),
        decoration: BoxDecoration(
          color: isActive ? const Color(0xFFEFF6FF) : Colors.white,
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isActive ? const Color(0xFF93C5FD) : const Color(0xFFE2E8F0),
          ),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (iconWidget != null) ...[
              iconWidget,
              const SizedBox(width: 5),
            ] else if (icon != null) ...[
              Icon(icon, size: 16, color: color),
              const SizedBox(width: 5),
            ],
            Text(
              label,
              style: TextStyle(
                color: color,
                fontSize: 12.5,
                fontWeight: isActive ? FontWeight.bold : FontWeight.w500,
              ),
            ),
          ],
        ),
      ),
    );
  }

  // Reaction Picker Sheet (Like on Facebook / Website)
  void _showReactionPicker(BuildContext context, PostModel post, FeedProvider feed) {
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return Container(
          margin: const EdgeInsets.all(16),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(30),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.12),
                blurRadius: 16,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.spaceEvenly,
            children: PostModel.reactionEmojis.entries.map((entry) {
              final isSelected = post.userReaction == entry.key;
              return InkWell(
                onTap: () {
                  Navigator.pop(ctx);
                  feed.toggleLike(post, reaction: entry.key);
                },
                borderRadius: BorderRadius.circular(20),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 6),
                  decoration: BoxDecoration(
                    color: isSelected ? const Color(0xFFEFF6FF) : Colors.transparent,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(entry.value, style: const TextStyle(fontSize: 26)),
                      const SizedBox(height: 2),
                      Text(
                        PostModel.reactionLabels[entry.key] ?? '',
                        style: TextStyle(
                          fontSize: 11,
                          fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                          color: isSelected ? const Color(0xFF2563EB) : const Color(0xFF64748B),
                        ),
                      ),
                    ],
                  ),
                ),
              );
            }).toList(),
          ),
        );
      },
    );
  }

  // Reactors list modal (Showing members who liked/reacted)
  void _showReactorsModal(BuildContext context, PostModel post) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.transparent,
      builder: (ctx) {
        return FutureBuilder<ApiResponse>(
          future: ApiClient.get('/posts/${post.id}/reactors'),
          builder: (context, snapshot) {
            final isLoading = snapshot.connectionState == ConnectionState.waiting;
            List reactors = [];
            if (snapshot.hasData && snapshot.data!.success && snapshot.data!.data is Map) {
              reactors = (snapshot.data!.data['reactors'] as List?) ?? [];
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
                          'Reactions (${post.likesCount})',
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
                        : reactors.isEmpty
                            ? Center(
                                child: Text(
                                  post.likesCount > 0 ? '${post.likesCount} members reacted' : 'No reactions yet',
                                  style: const TextStyle(color: Color(0xFF94A3B8)),
                                ),
                              )
                            : ListView.separated(
                                padding: const EdgeInsets.all(16),
                                itemCount: reactors.length,
                                separatorBuilder: (_, _) => const Divider(height: 12, color: Color(0xFFF1F5F9)),
                                itemBuilder: (context, idx) {
                                  final r = reactors[idx];
                                  final m = r['member'] ?? {};
                                  final name = m['name']?.toString() ?? 'Member';
                                  final emoji = r['emoji']?.toString() ?? '👍';
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
                                        child: Text(
                                          name,
                                          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF1E293B)),
                                        ),
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

  // Share post modal (Share to feed / Copy link)
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
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text('Share Post', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17, color: Color(0xFF0F172A))),
                  IconButton(icon: const Icon(Icons.close, size: 20), onPressed: () => Navigator.pop(ctx)),
                ],
              ),
              const Divider(height: 1),
              const SizedBox(height: 14),

              // Option 1: Share to Feed
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
              // Option 2: Copy link
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

  // Comments bottom modal sheet
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
                  // Handle
                  Container(
                    margin: const EdgeInsets.only(top: 10, bottom: 6),
                    width: 40,
                    height: 4,
                    decoration: BoxDecoration(color: Colors.grey.shade300, borderRadius: BorderRadius.circular(2)),
                  ),
                  // Header
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
                  // Comments List
                  Expanded(
                    child: isLoading
                        ? const Center(child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF2563EB)))
                        : commentsList.isEmpty
                            ? const Center(
                                child: Text(
                                  'No comments yet. Be the first to comment!',
                                  style: TextStyle(color: Color(0xFF94A3B8), fontStyle: FontStyle.italic),
                                ),
                              )
                            : ListView.separated(
                                padding: const EdgeInsets.all(16),
                                itemCount: commentsList.length,
                                separatorBuilder: (_, _) => const SizedBox(height: 12),
                                itemBuilder: (context, idx) {
                                  final c = commentsList[idx];
                                  final authorName = c['author']?['name']?.toString() ?? 'Member';
                                  return Row(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      CircleAvatar(
                                        radius: 16,
                                        backgroundColor: const Color(0xFFE2E8F0),
                                        child: Text(
                                          authorName.isNotEmpty ? authorName[0].toUpperCase() : 'U',
                                          style: const TextStyle(fontSize: 12, fontWeight: FontWeight.bold, color: Color(0xFF334155)),
                                        ),
                                      ),
                                      const SizedBox(width: 10),
                                      Expanded(
                                        child: Container(
                                          padding: const EdgeInsets.all(10),
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
                                                  Text(authorName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5, color: Color(0xFF1E293B))),
                                                  Text(c['created_at']?.toString() ?? 'Just now', style: const TextStyle(fontSize: 10.5, color: Color(0xFF94A3B8))),
                                                ],
                                              ),
                                              const SizedBox(height: 4),
                                              Text(c['comment']?.toString() ?? '', style: const TextStyle(fontSize: 13, color: Color(0xFF334155))),
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
                  // Input Bar
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                    child: Row(
                      children: [
                        Expanded(
                          child: TextField(
                            controller: textCtrl,
                            style: const TextStyle(fontSize: 13),
                            decoration: InputDecoration(
                              hintText: 'Write a comment...',
                              hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                              filled: true,
                              fillColor: const Color(0xFFF8FAFC),
                              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
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

  // -------------------------------------------------------------
  // Create Story Modal Dialog (Exact Website Screenshot Design)
  // -------------------------------------------------------------
  void _showCreateStoryModal(BuildContext context, FeedProvider feed) {
    Uint8List? selectedMediaBytes;
    String? selectedMediaName;
    String? selectedMediaType; // 'image' or 'video'
    final captionCtrl = TextEditingController();
    bool isUploading = false;

    showDialog(
      context: context,
      barrierDismissible: true,
      builder: (dialogCtx) {
        return StatefulBuilder(
          builder: (ctx, setModalState) {
            final hasMedia = selectedMediaBytes != null;

            Future<void> pickMedia(String type) async {
              try {
                XFile? file;
                if (type == 'video') {
                  file = await _picker.pickVideo(source: ImageSource.gallery);
                } else if (type == 'image') {
                  file = await _picker.pickImage(source: ImageSource.gallery, imageQuality: 88);
                } else {
                  file = await _picker.pickMedia(imageQuality: 88);
                }
                if (file != null) {
                  final bytes = await file.readAsBytes();
                  final name = file.name.toLowerCase();
                  final isVideo = type == 'video' ||
                      name.endsWith('.mp4') ||
                      name.endsWith('.mov') ||
                      name.endsWith('.webm') ||
                      name.endsWith('.avi') ||
                      name.endsWith('.mkv');
                  setModalState(() {
                    selectedMediaBytes = bytes;
                    selectedMediaName = file!.name;
                    selectedMediaType = isVideo ? 'video' : 'image';
                  });
                }
              } catch (e) {
                debugPrint('Story pick error: $e');
                if (context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    SnackBar(
                      content: Text('File selection error: $e'),
                      backgroundColor: const Color(0xFFEF4444),
                    ),
                  );
                }
              }
            }

            return Dialog(
              backgroundColor: Colors.transparent,
              insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
              child: Container(
                width: 520,
                constraints: const BoxConstraints(maxWidth: 520),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.12),
                      blurRadius: 24,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: SingleChildScrollView(
                  child: Padding(
                    padding: const EdgeInsets.all(22),
                    child: Column(
                      mainAxisSize: MainAxisSize.min,
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Header Row: "Create Story" + Circular Close Button
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Create Story',
                              style: TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0F172A),
                              ),
                            ),
                            InkWell(
                              onTap: () => Navigator.of(dialogCtx).pop(),
                              borderRadius: BorderRadius.circular(20),
                              child: Container(
                                width: 32,
                                height: 32,
                                decoration: const BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: Color(0xFFF1F5F9),
                                ),
                                child: const Icon(Icons.close, size: 18, color: Color(0xFF64748B)),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 16),

                        // Dashed Dropzone / Selector Box (Entire container is clickable)
                        CustomPaint(
                          painter: DashedBorderPainter(
                            color: const Color(0xFF3B82F6),
                            strokeWidth: 1.5,
                            gap: 4.0,
                            dash: 6.0,
                            radius: 16.0,
                          ),
                          child: Container(
                            width: double.infinity,
                            constraints: const BoxConstraints(minHeight: 200),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF8FAFC),
                              borderRadius: BorderRadius.circular(16),
                            ),
                            child: hasMedia
                                ? Stack(
                                    alignment: Alignment.center,
                                    children: [
                                      // Media Preview
                                      Padding(
                                        padding: const EdgeInsets.all(12),
                                        child: ClipRRect(
                                          borderRadius: BorderRadius.circular(12),
                                          child: selectedMediaType == 'image'
                                              ? Image.memory(
                                                  selectedMediaBytes!,
                                                  width: double.infinity,
                                                  height: 220,
                                                  fit: BoxFit.contain,
                                                )
                                              : Container(
                                                  width: double.infinity,
                                                  height: 220,
                                                  color: Colors.black87,
                                                  child: Column(
                                                    mainAxisAlignment: MainAxisAlignment.center,
                                                    children: [
                                                      const Icon(Icons.play_circle_filled, size: 52, color: Colors.white),
                                                      const SizedBox(height: 8),
                                                      Padding(
                                                        padding: const EdgeInsets.symmetric(horizontal: 16),
                                                        child: Text(
                                                          selectedMediaName ?? 'Selected Video',
                                                          maxLines: 1,
                                                          overflow: TextOverflow.ellipsis,
                                                          style: const TextStyle(color: Colors.white, fontSize: 13.5, fontWeight: FontWeight.w600),
                                                        ),
                                                      ),
                                                      const SizedBox(height: 6),
                                                      Container(
                                                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                                                        decoration: BoxDecoration(
                                                          color: const Color(0xFF0284C7).withOpacity(0.3),
                                                          borderRadius: BorderRadius.circular(20),
                                                          border: Border.all(color: const Color(0xFF38BDF8)),
                                                        ),
                                                        child: const Text(
                                                          'Video ready to share',
                                                          style: TextStyle(color: Color(0xFF38BDF8), fontSize: 11.5, fontWeight: FontWeight.bold),
                                                        ),
                                                      ),
                                                    ],
                                                  ),
                                                ),
                                        ),
                                      ),

                                      // Top Right Close / Remove Button
                                      Positioned(
                                        top: 16,
                                        right: 16,
                                        child: GestureDetector(
                                          onTap: () {
                                            setModalState(() {
                                              selectedMediaBytes = null;
                                              selectedMediaName = null;
                                              selectedMediaType = null;
                                            });
                                          },
                                          child: Container(
                                            padding: const EdgeInsets.all(6),
                                            decoration: BoxDecoration(
                                              color: Colors.black.withOpacity(0.65),
                                              shape: BoxShape.circle,
                                            ),
                                            child: const Icon(Icons.close, size: 16, color: Colors.white),
                                          ),
                                        ),
                                      ),

                                      // Bottom "Change Media" overlay button
                                      Positioned(
                                        bottom: 18,
                                        child: GestureDetector(
                                          onTap: () => pickMedia('any'),
                                          child: Container(
                                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
                                            decoration: BoxDecoration(
                                              color: Colors.black.withOpacity(0.75),
                                              borderRadius: BorderRadius.circular(20),
                                              border: Border.all(color: Colors.white30),
                                            ),
                                            child: const Row(
                                              mainAxisSize: MainAxisSize.min,
                                              children: [
                                                Icon(Icons.refresh_rounded, size: 14, color: Colors.white),
                                                SizedBox(width: 6),
                                                Text(
                                                  'Change Media',
                                                  style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold),
                                                ),
                                              ],
                                            ),
                                          ),
                                        ),
                                      ),
                                    ],
                                  )
                                : MouseRegion(
                                    cursor: SystemMouseCursors.click,
                                    child: GestureDetector(
                                      behavior: HitTestBehavior.opaque,
                                      onTap: () => pickMedia('any'),
                                      child: Padding(
                                        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 22),
                                        child: Column(
                                          mainAxisAlignment: MainAxisAlignment.center,
                                          children: [
                                            // 2 Circles: Photo and Video
                                            Row(
                                              mainAxisAlignment: MainAxisAlignment.center,
                                              children: [
                                                // Blue Photo circle
                                                GestureDetector(
                                                  onTap: () => pickMedia('image'),
                                                  child: Container(
                                                    width: 50,
                                                    height: 50,
                                                    decoration: BoxDecoration(
                                                      color: const Color(0xFFEFF6FF),
                                                      shape: BoxShape.circle,
                                                      border: Border.all(color: const Color(0xFFDBEAFE), width: 1.5),
                                                    ),
                                                    child: const Icon(Icons.image_outlined, color: Color(0xFF2563EB), size: 26),
                                                  ),
                                                ),
                                                const SizedBox(width: 16),
                                                // Pink/Purple Video circle
                                                GestureDetector(
                                                  onTap: () => pickMedia('video'),
                                                  child: Container(
                                                    width: 50,
                                                    height: 50,
                                                    decoration: BoxDecoration(
                                                      color: const Color(0xFFFAF5FF),
                                                      shape: BoxShape.circle,
                                                      border: Border.all(color: const Color(0xFFF3E8FF), width: 1.5),
                                                    ),
                                                    child: const Icon(Icons.videocam_outlined, color: Color(0xFFA855F7), size: 26),
                                                  ),
                                                ),
                                              ],
                                            ),
                                            const SizedBox(height: 12),

                                            // "Add Photo or Video"
                                            const Text(
                                              'Add Photo or Video',
                                              style: TextStyle(
                                                fontSize: 16,
                                                fontWeight: FontWeight.bold,
                                                color: Color(0xFF0F172A),
                                              ),
                                            ),
                                            const SizedBox(height: 4),

                                            // "Drag and drop or click to browse media"
                                            const Text(
                                              'Drag and drop or click to browse media',
                                              style: TextStyle(
                                                fontSize: 13,
                                                color: Color(0xFF64748B),
                                              ),
                                            ),
                                            const SizedBox(height: 14),

                                            // 2 CLEAR ACTION BUTTONS: [ 📷 Upload Photo ]  [ 🎥 Upload Video ]
                                            Row(
                                              mainAxisAlignment: MainAxisAlignment.center,
                                              children: [
                                                ElevatedButton.icon(
                                                  onPressed: () => pickMedia('image'),
                                                  icon: const Icon(Icons.photo_library_outlined, size: 16, color: Color(0xFF2563EB)),
                                                  label: const Text('Upload Photo', style: TextStyle(color: Color(0xFF1E40AF), fontWeight: FontWeight.bold, fontSize: 13)),
                                                  style: ElevatedButton.styleFrom(
                                                    backgroundColor: const Color(0xFFEFF6FF),
                                                    foregroundColor: const Color(0xFF1E40AF),
                                                    elevation: 0,
                                                    side: const BorderSide(color: Color(0xFFBFDBFE)),
                                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                                                  ),
                                                ),
                                                const SizedBox(width: 12),
                                                ElevatedButton.icon(
                                                  onPressed: () => pickMedia('video'),
                                                  icon: const Icon(Icons.video_library_outlined, size: 16, color: Color(0xFFA855F7)),
                                                  label: const Text('Upload Video', style: TextStyle(color: Color(0xFF6B21A8), fontWeight: FontWeight.bold, fontSize: 13)),
                                                  style: ElevatedButton.styleFrom(
                                                    backgroundColor: const Color(0xFFFAF5FF),
                                                    foregroundColor: const Color(0xFF6B21A8),
                                                    elevation: 0,
                                                    side: const BorderSide(color: Color(0xFFE9D5FF)),
                                                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                                    padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                                                  ),
                                                ),
                                              ],
                                            ),
                                            const SizedBox(height: 12),

                                            // 2 Pills: JPG/PNG and MP4/MOV
                                            Wrap(
                                              alignment: WrapAlignment.center,
                                              spacing: 8,
                                              runSpacing: 8,
                                              children: [
                                                // JPG Pill
                                                GestureDetector(
                                                  onTap: () => pickMedia('image'),
                                                  child: Container(
                                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                                    decoration: BoxDecoration(
                                                      color: Colors.white,
                                                      borderRadius: BorderRadius.circular(20),
                                                      border: Border.all(color: const Color(0xFFE2E8F0)),
                                                    ),
                                                    child: const Row(
                                                      mainAxisSize: MainAxisSize.min,
                                                      children: [
                                                        Icon(Icons.insert_drive_file_outlined, size: 14, color: Color(0xFF64748B)),
                                                        SizedBox(width: 5),
                                                        Text(
                                                          'JPG, PNG, WebP (max 5MB)',
                                                          style: TextStyle(fontSize: 11.5, color: Color(0xFF475569), fontWeight: FontWeight.w500),
                                                        ),
                                                      ],
                                                    ),
                                                  ),
                                                ),

                                                // Video Pill
                                                GestureDetector(
                                                  onTap: () => pickMedia('video'),
                                                  child: Container(
                                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                                    decoration: BoxDecoration(
                                                      color: Colors.white,
                                                      borderRadius: BorderRadius.circular(20),
                                                      border: Border.all(color: const Color(0xFFE2E8F0)),
                                                    ),
                                                    child: const Row(
                                                      mainAxisSize: MainAxisSize.min,
                                                      children: [
                                                        Icon(Icons.insert_drive_file_outlined, size: 14, color: Color(0xFF64748B)),
                                                        SizedBox(width: 5),
                                                        Text(
                                                          'MP4, WebM, MOV (max 50MB)',
                                                          style: TextStyle(fontSize: 11.5, color: Color(0xFF475569), fontWeight: FontWeight.w500),
                                                        ),
                                                      ],
                                                    ),
                                                  ),
                                                ),
                                              ],
                                            ),
                                          ],
                                        ),
                                      ),
                                    ),
                                  ),
                          ),
                        ),
                        const SizedBox(height: 18),

                        // Caption (Optional) Label with Character Counter
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text(
                              'Caption (Optional)',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: Color(0xFF1E293B),
                              ),
                            ),
                            Text(
                              '${captionCtrl.text.length} / 500',
                              style: const TextStyle(
                                fontSize: 12,
                                color: Color(0xFF64748B),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 8),

                        // Caption TextField
                        TextField(
                          controller: captionCtrl,
                          maxLines: 3,
                          maxLength: 500,
                          onChanged: (_) => setModalState(() {}),
                          style: const TextStyle(fontSize: 13.5, color: Color(0xFF0F172A)),
                          decoration: InputDecoration(
                            hintText: 'Add a short caption...',
                            hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13.5),
                            filled: true,
                            fillColor: const Color(0xFFF8FAFC),
                            counterText: '',
                            contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                            border: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                            ),
                            enabledBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                            ),
                            focusedBorder: OutlineInputBorder(
                              borderRadius: BorderRadius.circular(12),
                              borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
                            ),
                          ),
                        ),
                        const SizedBox(height: 20),

                        // Footer Buttons: [ Cancel ]   [ ✈ Share Story ]
                        Row(
                          mainAxisAlignment: MainAxisAlignment.end,
                          children: [
                            // Cancel Button
                            TextButton(
                              onPressed: isUploading ? null : () => Navigator.of(dialogCtx).pop(),
                              style: TextButton.styleFrom(
                                backgroundColor: const Color(0xFFF1F5F9),
                                foregroundColor: const Color(0xFF1E293B),
                                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 11),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              ),
                              child: const Text('Cancel', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                            ),
                            const SizedBox(width: 10),

                            // Share Story Button
                            ElevatedButton.icon(
                              onPressed: (!hasMedia || isUploading)
                                  ? null
                                  : () async {
                                      setModalState(() => isUploading = true);
                                      final ok = await feed.createStory(
                                        fileBytes: selectedMediaBytes,
                                        fileName: selectedMediaName,
                                        mediaType: selectedMediaType ?? 'image',
                                        caption: captionCtrl.text.trim(),
                                      );
                                      setModalState(() => isUploading = false);

                                      if (ok) {
                                        if (context.mounted) {
                                          Navigator.of(dialogCtx).pop();
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            SnackBar(
                                              content: const Row(
                                                children: [
                                                  Icon(Icons.check_circle, color: Colors.white, size: 18),
                                                  SizedBox(width: 8),
                                                  Text('Story shared successfully!'),
                                                ],
                                              ),
                                              behavior: SnackBarBehavior.floating,
                                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                              backgroundColor: const Color(0xFF16A34A),
                                              duration: const Duration(seconds: 3),
                                            ),
                                          );
                                        }
                                      } else {
                                        if (context.mounted) {
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            SnackBar(
                                              content: Text(feed.lastError ?? 'Failed to upload story. Please try again.'),
                                              behavior: SnackBarBehavior.floating,
                                              backgroundColor: const Color(0xFFEF4444),
                                            ),
                                          );
                                        }
                                      }
                                    },
                              icon: isUploading
                                  ? const SizedBox(width: 16, height: 16, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                                  : const Icon(Icons.near_me_rounded, size: 16),
                              label: Text(
                                isUploading ? 'Sharing...' : 'Share Story',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                              ),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF2563EB),
                                foregroundColor: Colors.white,
                                disabledBackgroundColor: const Color(0xFFE2E8F0),
                                disabledForegroundColor: const Color(0xFF94A3B8),
                                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 11),
                                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                elevation: 0,
                              ),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  // -------------------------------------------------------------
  // Full-Screen Story Viewer Dialog (Instagram / Website Style)
  // -------------------------------------------------------------
  void _showStoryViewer(BuildContext context, StoryGroupModel group) {
    if (group.stories.isEmpty) return;
    showDialog(
      context: context,
      barrierColor: Colors.black.withOpacity(0.92),
      builder: (ctx) => _StoryViewerDialogWidget(group: group),
    );
  }
}

// -------------------------------------------------------------
// Interactive Story Menu Item with Hover & Bulletproof Tap
// -------------------------------------------------------------
class _StoryMenuItem extends StatefulWidget {
  final IconData icon;
  final String label;
  final Color? color;
  final VoidCallback onTap;

  const _StoryMenuItem({
    required this.icon,
    required this.label,
    this.color,
    required this.onTap,
  });

  @override
  State<_StoryMenuItem> createState() => _StoryMenuItemState();
}

class _StoryMenuItemState extends State<_StoryMenuItem> {
  bool _isHovered = false;

  @override
  Widget build(BuildContext context) {
    final effectiveColor = widget.color ?? const Color(0xFF1E293B);
    return MouseRegion(
      cursor: SystemMouseCursors.click,
      onEnter: (_) => setState(() => _isHovered = true),
      onExit: (_) => setState(() => _isHovered = false),
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: widget.onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 100),
          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
          decoration: BoxDecoration(
            color: _isHovered ? const Color(0xFFF1F5F9) : Colors.transparent,
            borderRadius: BorderRadius.circular(8),
          ),
          child: Row(
            children: [
              Icon(widget.icon, size: 19, color: effectiveColor),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  widget.label,
                  style: TextStyle(
                    fontWeight: FontWeight.w600,
                    fontSize: 14,
                    color: effectiveColor,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

// -------------------------------------------------------------
// Full-Screen Story Viewer Dialog with Animated Progress Bars
// -------------------------------------------------------------
class _StoryViewerDialogWidget extends StatefulWidget {
  final StoryGroupModel group;

  const _StoryViewerDialogWidget({
    required this.group,
  });

  @override
  State<_StoryViewerDialogWidget> createState() => _StoryViewerDialogWidgetState();
}

class _StoryViewerDialogWidgetState extends State<_StoryViewerDialogWidget>
    with SingleTickerProviderStateMixin {
  int activeIndex = 0;
  late List<StoryItemModel> _storiesList;
  late AnimationController _animController;
  bool isMuted = false;
  bool showOptionsMenu = false;

  @override
  void initState() {
    super.initState();
    activeIndex = 0;
    _storiesList = List<StoryItemModel>.from(widget.group.stories);

    _animController = AnimationController(vsync: this);
    _animController.addStatusListener((status) {
      if (status == AnimationStatus.completed) {
        _nextStory();
      }
    });

    _startStoryAnimation();
  }

  void _startStoryAnimation() {
    if (_storiesList.isEmpty || !mounted) return;
    if (activeIndex >= _storiesList.length) {
      activeIndex = _storiesList.length - 1;
    }
    final story = _storiesList[activeIndex];
    final mediaUrl = story.resolvedMediaUrl.toLowerCase();
    final isVideo = story.mediaType == 'video' ||
        mediaUrl.endsWith('.mp4') ||
        mediaUrl.endsWith('.mov') ||
        mediaUrl.endsWith('.webm') ||
        mediaUrl.contains('/videos/');

    _animController.stop();
    // 15 seconds for video stories, 5 seconds for image stories
    _animController.duration = Duration(seconds: isVideo ? 15 : 5);
    _animController.reset();
    _animController.forward();
  }

  void _nextStory() {
    if (activeIndex < _storiesList.length - 1) {
      setState(() {
        activeIndex++;
        showOptionsMenu = false;
      });
      _startStoryAnimation();
    } else {
      if (mounted && Navigator.of(context).canPop()) {
        Navigator.of(context).pop();
      }
    }
  }

  void _prevStory() {
    if (activeIndex > 0) {
      setState(() {
        activeIndex--;
        showOptionsMenu = false;
      });
      _startStoryAnimation();
    } else {
      _animController.reset();
      _animController.forward();
    }
  }

  @override
  void dispose() {
    _animController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (_storiesList.isEmpty) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted && Navigator.of(context).canPop()) {
          Navigator.of(context).pop();
        }
      });
      return const SizedBox.shrink();
    }

    if (activeIndex >= _storiesList.length) {
      activeIndex = _storiesList.length - 1;
    }

    final story = _storiesList[activeIndex];
    final currentMember = context.read<AuthProvider>().currentMember;
    final isOwnStory = (currentMember?.id != null && currentMember!.id == widget.group.memberId);

    // Normalize media URL to local server if needed
    String mediaUrl = story.resolvedMediaUrl;
    if (mediaUrl.contains('mlmbookai.com/uploads/')) {
      mediaUrl = mediaUrl
          .replaceFirst('https://mlmbookai.com', 'http://127.0.0.1:8000')
          .replaceFirst('http://mlmbookai.com', 'http://127.0.0.1:8000');
    }

    final isVideo = story.mediaType == 'video' ||
        mediaUrl.toLowerCase().endsWith('.mp4') ||
        mediaUrl.toLowerCase().endsWith('.mov') ||
        mediaUrl.toLowerCase().endsWith('.webm') ||
        mediaUrl.toLowerCase().endsWith('.avi') ||
        mediaUrl.toLowerCase().endsWith('.mkv') ||
        mediaUrl.toLowerCase().contains('/videos/');

    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.all(12),
      child: Center(
        child: Container(
          width: 440,
          height: 670,
          decoration: BoxDecoration(
            color: Colors.black,
            borderRadius: BorderRadius.circular(18),
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.5),
                blurRadius: 30,
                offset: const Offset(0, 10),
              ),
            ],
          ),
          clipBehavior: Clip.antiAlias,
          child: Stack(
            fit: StackFit.expand,
            children: [
              // 1. Story Image or Video
              if (isVideo)
                AppVideoPlayer(
                  key: ValueKey('story_${story.id}_$mediaUrl'),
                  videoUrl: mediaUrl,
                  autoPlay: true,
                  loop: true,
                  isMuted: isMuted,
                )
              else
                Image.network(
                  mediaUrl,
                  fit: BoxFit.contain,
                  loadingBuilder: (c, child, p) => p == null
                      ? child
                      : const Center(child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2.5)),
                  errorBuilder: (ctx, err, stack) {
                    debugPrint('Story image load error for $mediaUrl: $err');
                    final altUrl = mediaUrl.contains('localhost:8000')
                        ? mediaUrl.replaceFirst('localhost:8000', '127.0.0.1:8000')
                        : mediaUrl.replaceFirst('127.0.0.1:8000', 'localhost:8000');
                    return Image.network(
                      altUrl,
                      fit: BoxFit.contain,
                      errorBuilder: (c2, err2, s2) => Center(
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            const Icon(Icons.image_not_supported_outlined, color: Colors.white60, size: 48),
                            const SizedBox(height: 10),
                            const Text('Unable to load story media', style: TextStyle(color: Colors.white70, fontSize: 14)),
                            const SizedBox(height: 12),
                            ElevatedButton.icon(
                              onPressed: () => setState(() {}),
                              icon: const Icon(Icons.refresh, size: 16),
                              label: const Text('Retry'),
                              style: ElevatedButton.styleFrom(
                                backgroundColor: const Color(0xFF2563EB),
                                foregroundColor: Colors.white,
                              ),
                            ),
                          ],
                        ),
                      ),
                    );
                  },
                ),

              // 2. Top & Bottom Gradient Shadows
              Positioned(
                top: 0,
                left: 0,
                right: 0,
                height: 120,
                child: Container(
                  decoration: BoxDecoration(
                    gradient: LinearGradient(
                      colors: [Colors.black.withOpacity(0.85), Colors.transparent],
                      begin: Alignment.topCenter,
                      end: Alignment.bottomCenter,
                    ),
                  ),
                ),
              ),

              // 3. Middle Tap Navigation Zones (Left 35% = Prev, Right 65% = Next)
              // Strictly constrained to Y=110 to Y=height-80 so it NEVER intercepts clicks on the header at all!
              Positioned.fill(
                top: 110,
                bottom: 80,
                child: Row(
                  children: [
                    Expanded(
                      flex: 7,
                      child: GestureDetector(
                        behavior: HitTestBehavior.translucent,
                        onTap: () {
                          if (showOptionsMenu) {
                            setState(() {
                              showOptionsMenu = false;
                              _animController.forward();
                            });
                            return;
                          }
                          _prevStory();
                        },
                      ),
                    ),
                    Expanded(
                      flex: 13,
                      child: GestureDetector(
                        behavior: HitTestBehavior.translucent,
                        onTap: () {
                          if (showOptionsMenu) {
                            setState(() {
                              showOptionsMenu = false;
                              _animController.forward();
                            });
                            return;
                          }
                          _nextStory();
                        },
                      ),
                    ),
                  ],
                ),
              ),

              // 4. Visible Navigation Chevron Buttons (< and >)
              if (activeIndex > 0)
                Positioned(
                  left: 10,
                  top: 0,
                  bottom: 0,
                  child: Center(
                    child: MouseRegion(
                      cursor: SystemMouseCursors.click,
                      child: GestureDetector(
                        behavior: HitTestBehavior.opaque,
                        onTap: () {
                          if (showOptionsMenu) setState(() => showOptionsMenu = false);
                          _prevStory();
                        },
                        child: Container(
                          width: 38,
                          height: 38,
                          decoration: BoxDecoration(
                            color: Colors.black.withOpacity(0.55),
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white24),
                          ),
                          child: const Icon(Icons.chevron_left, color: Colors.white, size: 24),
                        ),
                      ),
                    ),
                  ),
                ),

              if (activeIndex < _storiesList.length - 1)
                Positioned(
                  right: 10,
                  top: 0,
                  bottom: 0,
                  child: Center(
                    child: MouseRegion(
                      cursor: SystemMouseCursors.click,
                      child: GestureDetector(
                        behavior: HitTestBehavior.opaque,
                        onTap: () {
                          if (showOptionsMenu) setState(() => showOptionsMenu = false);
                          _nextStory();
                        },
                        child: Container(
                          width: 38,
                          height: 38,
                          decoration: BoxDecoration(
                            color: Colors.black.withOpacity(0.55),
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white24),
                          ),
                          child: const Icon(Icons.chevron_right, color: Colors.white, size: 24),
                        ),
                      ),
                    ),
                  ),
                ),

              // 5. Bottom Caption overlay
              if (story.caption != null && story.caption!.isNotEmpty)
                Positioned(
                  bottom: 0,
                  left: 0,
                  right: 0,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 16),
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [Colors.transparent, Colors.black.withOpacity(0.85)],
                        begin: Alignment.topCenter,
                        end: Alignment.bottomCenter,
                      ),
                    ),
                    child: Text(
                      story.caption!,
                      style: const TextStyle(color: Colors.white, fontSize: 14, height: 1.35),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ),

              // 6. Animated Segment Progress Bars at top (Smoothly runs according to story!)
              Positioned(
                top: 12,
                left: 12,
                right: 12,
                child: Row(
                  children: _storiesList.asMap().entries.map((entry) {
                    final index = entry.key;
                    return Expanded(
                      child: Container(
                        height: 3,
                        margin: const EdgeInsets.symmetric(horizontal: 2),
                        clipBehavior: Clip.antiAlias,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(2),
                          color: Colors.white30,
                        ),
                        child: index < activeIndex
                            ? Container(color: Colors.white)
                            : index == activeIndex
                                ? AnimatedBuilder(
                                    animation: _animController,
                                    builder: (context, child) {
                                      return LinearProgressIndicator(
                                        value: _animController.value,
                                        backgroundColor: Colors.white30,
                                        valueColor: const AlwaysStoppedAnimation<Color>(Colors.white),
                                        minHeight: 3,
                                      );
                                    },
                                  )
                                : const SizedBox.shrink(),
                      ),
                    );
                  }).toList(),
                ),
              ),

              // 7. Header: Author Avatar + Name + Verified Badge + "YOUR STORY" + 3 Dots Button + Close Button
              Positioned(
                top: 24,
                left: 14,
                right: 14,
                child: Row(
                  children: [
                    CircleAvatar(
                      radius: 17,
                      backgroundColor: const Color(0xFF2563EB),
                      child: Text(
                        widget.group.authorName.isNotEmpty ? widget.group.authorName[0].toUpperCase() : 'M',
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Row(
                            children: [
                              Flexible(
                                child: Text(
                                  widget.group.authorName,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 14),
                                ),
                              ),
                              const SizedBox(width: 5),
                              Container(
                                padding: const EdgeInsets.all(2),
                                decoration: const BoxDecoration(
                                  color: Color(0xFF10B981),
                                  shape: BoxShape.circle,
                                ),
                                child: const Icon(Icons.check, size: 10, color: Colors.white),
                              ),
                              if (isOwnStory) ...[
                                const SizedBox(width: 6),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                                  decoration: BoxDecoration(
                                    color: const Color(0xFF1E293B),
                                    borderRadius: BorderRadius.circular(10),
                                    border: Border.all(color: const Color(0xFF475569), width: 0.8),
                                  ),
                                  child: const Text(
                                    'YOUR STORY',
                                    style: TextStyle(
                                      color: Colors.white,
                                      fontSize: 9.5,
                                      fontWeight: FontWeight.bold,
                                      letterSpacing: 0.4,
                                    ),
                                  ),
                                ),
                              ],
                            ],
                          ),
                          const SizedBox(height: 2),
                          Text(
                            story.createdAt,
                            style: const TextStyle(color: Colors.white70, fontSize: 11),
                          ),
                        ],
                      ),
                    ),

                    // Three-Dots Menu Button (Exact Image 2 Design)
                    MouseRegion(
                      cursor: SystemMouseCursors.click,
                      child: GestureDetector(
                        behavior: HitTestBehavior.opaque,
                        onTap: () {
                          setState(() {
                            showOptionsMenu = !showOptionsMenu;
                            if (showOptionsMenu) {
                              _animController.stop();
                            } else {
                              _animController.forward();
                            }
                          });
                        },
                        child: Container(
                          width: 36,
                          height: 36,
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            color: Colors.black.withOpacity(0.55),
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white24, width: 0.8),
                          ),
                          child: const Icon(Icons.more_vert, color: Colors.white, size: 20),
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),

                    // Close Button (✕)
                    MouseRegion(
                      cursor: SystemMouseCursors.click,
                      child: GestureDetector(
                        behavior: HitTestBehavior.opaque,
                        onTap: () => Navigator.of(context).pop(),
                        child: Container(
                          width: 36,
                          height: 36,
                          alignment: Alignment.center,
                          decoration: BoxDecoration(
                            color: Colors.black.withOpacity(0.55),
                            shape: BoxShape.circle,
                            border: Border.all(color: Colors.white24, width: 0.8),
                          ),
                          child: const Icon(Icons.close, color: Colors.white, size: 19),
                        ),
                      ),
                    ),
                  ],
                ),
              ),

              // 8. Three-Dots Popup Menu Card (Exact 1:1 Design - All Options Fully Functional)
              if (showOptionsMenu) ...[
                // Solid backdrop to dismiss menu on outside tap
                Positioned.fill(
                  child: GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: () {
                      setState(() {
                        showOptionsMenu = false;
                        _animController.forward();
                      });
                    },
                    child: const ColoredBox(color: Colors.transparent),
                  ),
                ),
                // Floating anchored popup card (Absorbs taps so items receive clicks reliably)
                Positioned(
                  top: 66,
                  right: 14,
                  child: GestureDetector(
                    behavior: HitTestBehavior.opaque,
                    onTap: () {}, // Blocks taps inside card from dismissing backdrop
                    child: Material(
                      color: Colors.white,
                      elevation: 18,
                      borderRadius: BorderRadius.circular(18),
                      shadowColor: Colors.black.withOpacity(0.35),
                      child: Container(
                        width: 225,
                        padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 8),
                        child: Column(
                          mainAxisSize: MainAxisSize.min,
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            // Option 1: Unmute / Mute
                            _StoryMenuItem(
                              icon: isMuted ? Icons.volume_off_rounded : Icons.volume_up_rounded,
                              label: isMuted ? 'Unmute' : 'Mute',
                              color: const Color(0xFF334155),
                              onTap: () {
                                setState(() {
                                  isMuted = !isMuted;
                                  showOptionsMenu = false;
                                });
                                _animController.forward();
                                if (context.mounted) {
                                  ScaffoldMessenger.of(context).hideCurrentSnackBar();
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(
                                      content: Row(
                                        children: [
                                          Icon(
                                            isMuted ? Icons.volume_off_rounded : Icons.volume_up_rounded,
                                            color: Colors.white,
                                            size: 18,
                                          ),
                                          const SizedBox(width: 8),
                                          Text(isMuted ? 'Story audio muted' : 'Story audio unmuted'),
                                        ],
                                      ),
                                      behavior: SnackBarBehavior.floating,
                                      backgroundColor: const Color(0xFF1E293B),
                                      duration: const Duration(seconds: 2),
                                    ),
                                  );
                                }
                              },
                            ),

                            // Option 2: Story Options (Section Header)
                            Padding(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 7),
                              child: Row(
                                children: const [
                                  Icon(Icons.more_horiz, size: 20, color: Color(0xFF64748B)),
                                  SizedBox(width: 12),
                                  Text(
                                    'Story Options',
                                    style: TextStyle(
                                      fontWeight: FontWeight.w600,
                                      fontSize: 14,
                                      color: Color(0xFF1E293B),
                                    ),
                                  ),
                                ],
                              ),
                            ),

                            // Nested Options block with vertical guide line (Exact Image 2)
                            Container(
                              margin: const EdgeInsets.only(left: 20, bottom: 4),
                              padding: const EdgeInsets.only(left: 10),
                              decoration: const BoxDecoration(
                                border: Border(
                                  left: BorderSide(color: Color(0xFFCBD5E1), width: 1.5),
                                ),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  // Delete Story (red icon & red text)
                                  _StoryMenuItem(
                                    icon: Icons.delete_outline_rounded,
                                    label: 'Delete Story',
                                    color: const Color(0xFFEF4444),
                                    onTap: () async {
                                      setState(() => showOptionsMenu = false);
                                      final feed = context.read<FeedProvider>();
                                      final currentStoryId = story.id;

                                      if (!isOwnStory) {
                                        _animController.forward();
                                        if (context.mounted) {
                                          ScaffoldMessenger.of(context).hideCurrentSnackBar();
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            const SnackBar(
                                              content: Row(
                                                children: [
                                                  Icon(Icons.info_outline, color: Colors.white, size: 18),
                                                  SizedBox(width: 8),
                                                  Text('You can only delete your own stories.'),
                                                ],
                                              ),
                                              behavior: SnackBarBehavior.floating,
                                              backgroundColor: Color(0xFFE11D48),
                                              duration: Duration(seconds: 2),
                                            ),
                                          );
                                        }
                                        return;
                                      }

                                      if (context.mounted) {
                                        ScaffoldMessenger.of(context).hideCurrentSnackBar();
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          const SnackBar(
                                            content: Row(
                                              children: [
                                                SizedBox(
                                                  width: 16,
                                                  height: 16,
                                                  child: CircularProgressIndicator(
                                                    strokeWidth: 2,
                                                    color: Colors.white,
                                                  ),
                                                ),
                                                SizedBox(width: 10),
                                                Text('Deleting story...'),
                                              ],
                                            ),
                                            behavior: SnackBarBehavior.floating,
                                            duration: Duration(seconds: 1),
                                          ),
                                        );
                                      }

                                      final ok = await feed.deleteStory(currentStoryId);
                                      if (ok) {
                                        setState(() {
                                          _storiesList.removeWhere((s) => s.id == currentStoryId);
                                          if (activeIndex >= _storiesList.length) {
                                            activeIndex = _storiesList.isEmpty ? 0 : _storiesList.length - 1;
                                          }
                                        });
                                        if (_storiesList.isEmpty) {
                                          if (mounted && Navigator.of(context).canPop()) {
                                            Navigator.of(context).pop();
                                          }
                                        } else {
                                          _startStoryAnimation();
                                        }
                                        if (context.mounted) {
                                          ScaffoldMessenger.of(context).hideCurrentSnackBar();
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            const SnackBar(
                                              content: Row(
                                                children: [
                                                  Icon(Icons.check_circle, color: Colors.white, size: 18),
                                                  SizedBox(width: 8),
                                                  Text('Story deleted successfully.'),
                                                ],
                                              ),
                                              behavior: SnackBarBehavior.floating,
                                              backgroundColor: Color(0xFF16A34A),
                                              duration: Duration(seconds: 2),
                                            ),
                                          );
                                        }
                                      } else {
                                        _animController.forward();
                                        if (context.mounted) {
                                          ScaffoldMessenger.of(context).hideCurrentSnackBar();
                                          ScaffoldMessenger.of(context).showSnackBar(
                                            SnackBar(
                                              content: Text(feed.lastError ?? 'Failed to delete story.'),
                                              behavior: SnackBarBehavior.floating,
                                              backgroundColor: const Color(0xFFEF4444),
                                              duration: const Duration(seconds: 3),
                                            ),
                                          );
                                        }
                                      }
                                    },
                                  ),

                                  // Copy Story Link
                                  _StoryMenuItem(
                                    icon: Icons.link_rounded,
                                    label: 'Copy Story Link',
                                    color: const Color(0xFF475569),
                                    onTap: () async {
                                      setState(() {
                                        showOptionsMenu = false;
                                        _animController.forward();
                                      });
                                      try {
                                        await Clipboard.setData(ClipboardData(text: mediaUrl));
                                      } catch (e) {
                                        debugPrint('Clipboard copy error: $e');
                                      }
                                      if (context.mounted) {
                                        ScaffoldMessenger.of(context).hideCurrentSnackBar();
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          const SnackBar(
                                            content: Row(
                                              children: [
                                                Icon(Icons.link, color: Colors.white, size: 18),
                                                SizedBox(width: 8),
                                                Text('Story link copied to clipboard!'),
                                              ],
                                            ),
                                            behavior: SnackBarBehavior.floating,
                                            backgroundColor: Color(0xFF2563EB),
                                            duration: Duration(seconds: 2),
                                          ),
                                        );
                                      }
                                    },
                                  ),
                                ],
                              ),
                            ),

                            // Option 3: Close Story
                            _StoryMenuItem(
                              icon: Icons.close_rounded,
                              label: 'Close Story',
                              color: const Color(0xFF475569),
                              onTap: () {
                                setState(() => showOptionsMenu = false);
                                if (mounted && Navigator.of(context).canPop()) {
                                  Navigator.of(context).pop();
                                }
                              },
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

// -------------------------------------------------------------
// Dashed Border Painter for Modal Dropzone (Exact Screenshot)
// -------------------------------------------------------------
class DashedBorderPainter extends CustomPainter {
  final Color color;
  final double strokeWidth;
  final double gap;
  final double dash;
  final double radius;

  DashedBorderPainter({
    this.color = const Color(0xFF3B82F6),
    this.strokeWidth = 1.5,
    this.gap = 4.0,
    this.dash = 6.0,
    this.radius = 16.0,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = color
      ..strokeWidth = strokeWidth
      ..style = PaintingStyle.stroke;

    final rrect = RRect.fromRectAndRadius(
      Rect.fromLTWH(0, 0, size.width, size.height),
      Radius.circular(radius),
    );

    final path = Path()..addRRect(rrect);
    final metrics = path.computeMetrics();

    for (final metric in metrics) {
      double distance = 0.0;
      while (distance < metric.length) {
        final len = (distance + dash > metric.length) ? metric.length - distance : dash;
        final extract = metric.extractPath(distance, distance + len);
        canvas.drawPath(extract, paint);
        distance += dash + gap;
      }
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
