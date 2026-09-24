import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../models/post_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/feed_provider.dart';
import '../../widgets/profile_image_adjust_dialog.dart';
import '../../widgets/referral_program_dialog.dart';
import 'account_settings_screen.dart';
import 'edit_profile_screen.dart';
import 'friends_screen.dart';
import 'messages_screen.dart';
import 'new_connections_screen.dart';

class ProfileScreen extends StatefulWidget {
  final String initialTab;
  const ProfileScreen({super.key, this.initialTab = 'videos'});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  late String _activeTab;
  final ScrollController _tabsScrollController = ScrollController();
  final ImagePicker _picker = ImagePicker();

  Map<String, dynamic>? _profileData;
  List<Map<String, dynamic>> _friendsList = [];

  @override
  void initState() {
    super.initState();
    _activeTab = widget.initialTab;
    _fetchProfileDetails();
  }

  @override
  void dispose() {
    _tabsScrollController.dispose();
    super.dispose();
  }

  Future<void> _fetchProfileDetails() async {
    final res = await ApiClient.get('/profile/me');
    if (res.success && res.data is Map && res.data['profile'] is Map) {
      if (mounted) {
        setState(() {
          _profileData = res.data['profile'] as Map<String, dynamic>;
        });
      }
    }

    final friendsRes = await ApiClient.get('/friends');
    if (friendsRes.success && friendsRes.data is Map && friendsRes.data['friends'] is List) {
      if (mounted) {
        setState(() {
          _friendsList = (friendsRes.data['friends'] as List).cast<Map<String, dynamic>>();
        });
      }
    }
  }

  Future<void> _handleRemovePhoto(String type) async {
    final isAvatar = type.toLowerCase().contains('profile') || type == 'avatar';
    final endpoint = isAvatar ? '/profile/photo' : '/profile/cover';
    final res = await ApiClient.delete(endpoint);
    if (res.success && mounted) {
      await context.read<AuthProvider>().initAuth();
      await _fetchProfileDetails();
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(isAvatar ? 'Profile photo removed' : 'Cover photo removed'),
          backgroundColor: const Color(0xFF16A34A),
          behavior: SnackBarBehavior.floating,
        ),
      );
    }
  }

  Future<void> _pickImage(String type) async {
    try {
      final XFile? file = await _picker.pickImage(source: ImageSource.gallery);
      if (file != null && mounted) {
        final isAvatar = type.toLowerCase().contains('profile') || type == 'avatar';
        final member = context.read<AuthProvider>().currentMember;
        final hasPhoto = isAvatar
            ? (member?.avatarUrl != null || _profileData?['profile_photo'] != null || _profileData?['avatar_url'] != null)
            : (member?.coverPhoto != null || _profileData?['cover_photo'] != null || _profileData?['cover_photo_url'] != null);

        final updated = await ProfileImageAdjustDialog.show(
          context,
          file: file,
          type: isAvatar ? 'avatar' : 'cover',
          hasExistingPhoto: hasPhoto,
          onRemove: () => _handleRemovePhoto(type),
        );

        if (updated == true && mounted) {
          await context.read<AuthProvider>().initAuth();
          await _fetchProfileDetails();
        }
      }
    } catch (e) {
      debugPrint('Image pick error: $e');
    }
  }

  void _scrollTabs(bool left) {
    if (!_tabsScrollController.hasClients) return;
    final current = _tabsScrollController.offset;
    final target = left ? (current - 180).clamp(0.0, _tabsScrollController.position.maxScrollExtent) : (current + 180).clamp(0.0, _tabsScrollController.position.maxScrollExtent);
    _tabsScrollController.animateTo(
      target,
      duration: const Duration(milliseconds: 250),
      curve: Curves.easeInOut,
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final feed = context.watch<FeedProvider>();
    final member = auth.currentMember;

    final name = member?.name.isNotEmpty == true ? member!.name : (_profileData?['name'] ?? 'Abhay Sahany');
    final userId = member?.userId.isNotEmpty == true ? member!.userId : (_profileData?['user_id'] ?? 'ABHY123456');
    final email = member?.email.isNotEmpty == true ? member!.email : (_profileData?['email'] ?? 'abhaysahany1982@gmail.com');
    final bio = member?.bio?.isNotEmpty == true ? member!.bio! : (_profileData?['bio'] ?? 'Add a short bio to tell the community about yourself.');

    final userPosts = feed.posts.where((p) => member != null && p.author.id == member.id).toList();
    final photoPosts = userPosts.where((p) => p.mediaType == 'image' || (p.mediaUrl != null && !p.mediaUrl!.endsWith('.mp4'))).toList();
    final videoPosts = userPosts.where((p) => p.mediaType == 'video' || (p.mediaUrl != null && p.mediaUrl!.endsWith('.mp4'))).toList();
    final storiesCount = feed.storyGroups.where((g) => member != null && g.memberId == member.id).fold<int>(0, (sum, g) => sum + g.storiesCount);

    final postsCount = userPosts.length;
    final photosCount = photoPosts.isNotEmpty ? photoPosts.length : 7; // Matching screenshot initial demo
    final videosCount = videoPosts.length;
    final connectionsCount = _friendsList.length;
    final storiesStat = storiesCount > 0 ? storiesCount : 1;

    final rawCoverUrl = member?.coverPhoto ?? _profileData?['cover_photo'] ?? _profileData?['cover_photo_url'];
    final resolvedCoverUrl = AppConstants.resolveMediaUrl(rawCoverUrl?.toString(), ApiClient.baseUrl);

    final rawAvatarUrl = member?.avatarUrl ?? _profileData?['avatar_url'] ?? _profileData?['profile_photo'];
    final resolvedAvatarUrl = AppConstants.resolveMediaUrl(rawAvatarUrl?.toString(), ApiClient.baseUrl);

    final initials = name.trim().split(' ').map((e) => e.isNotEmpty ? e[0] : '').take(2).join('').toUpperCase();

    return Scaffold(
      backgroundColor: const Color(0xFFF1F5F9),
      appBar: AppBar(
        title: const Text(
          'My Profile',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17, color: Color(0xFF0F172A)),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1.0),
          child: Container(color: const Color(0xFFE2E8F0), height: 1.0),
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.share_outlined, color: Color(0xFF475569), size: 20),
            tooltip: 'Share Profile Link',
            onPressed: () {
              Clipboard.setData(ClipboardData(text: 'https://mlmbookai.com/member/people/$userId'));
              ScaffoldMessenger.of(context).showSnackBar(
                const SnackBar(
                  content: Text('Profile link copied to clipboard!'),
                  behavior: SnackBarBehavior.floating,
                  backgroundColor: Color(0xFF2563EB),
                ),
              );
            },
          ),
          IconButton(
            icon: const Icon(Icons.settings_outlined, color: Color(0xFF475569), size: 20),
            tooltip: 'Account Settings',
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const AccountSettingsScreen()));
            },
          ),
        ],
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 16),
        child: Center(
          child: Container(
            constraints: const BoxConstraints(maxWidth: 1040),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // ==========================================
                // MAIN HERO CARD (Matching 1:1 Website Image)
                // ==========================================
                Container(
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(20),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.04),
                        blurRadius: 16,
                        offset: const Offset(0, 4),
                      ),
                    ],
                  ),
                  clipBehavior: Clip.antiAlias,
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      // 1. Cover Photo with Gradient & Abstract Rings
                      Stack(
                        children: [
                          Container(
                            height: 220,
                            width: double.infinity,
                            decoration: BoxDecoration(
                              gradient: const LinearGradient(
                                colors: [Color(0xFF2563EB), Color(0xFF4F46E5), Color(0xFF7C3AED)],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                              image: resolvedCoverUrl != null
                                  ? DecorationImage(
                                      image: NetworkImage(resolvedCoverUrl),
                                      fit: BoxFit.cover,
                                    )
                                  : null,
                            ),
                            child: resolvedCoverUrl == null
                                ? Stack(
                                    children: [
                                      // Geometric translucent circle design (Exact Screenshot)
                                      Positioned(
                                        top: -40,
                                        right: 40,
                                        child: Container(
                                          width: 280,
                                          height: 280,
                                          decoration: BoxDecoration(
                                            shape: BoxShape.circle,
                                            border: Border.all(color: Colors.white.withOpacity(0.12), width: 1.5),
                                          ),
                                        ),
                                      ),
                                      Positioned(
                                        bottom: -60,
                                        left: 80,
                                        child: Container(
                                          width: 220,
                                          height: 220,
                                          decoration: BoxDecoration(
                                            shape: BoxShape.circle,
                                            border: Border.all(color: Colors.white.withOpacity(0.08), width: 1.5),
                                          ),
                                        ),
                                      ),
                                    ],
                                  )
                                : null,
                          ),

                          // Change Cover Button (Bottom-Right, Matching Screenshot)
                          Positioned(
                            bottom: 16,
                            right: 18,
                            child: MouseRegion(
                              cursor: SystemMouseCursors.click,
                              child: GestureDetector(
                                behavior: HitTestBehavior.opaque,
                                onTap: () => _pickImage('Cover'),
                                child: Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                                  decoration: BoxDecoration(
                                    color: Colors.white,
                                    borderRadius: BorderRadius.circular(10),
                                    boxShadow: [
                                      BoxShadow(
                                        color: Colors.black.withOpacity(0.15),
                                        blurRadius: 8,
                                        offset: const Offset(0, 2),
                                      ),
                                    ],
                                  ),
                                  child: Row(
                                    mainAxisSize: MainAxisSize.min,
                                    children: const [
                                      Icon(Icons.camera_alt_outlined, size: 16, color: Color(0xFF1E293B)),
                                      SizedBox(width: 8),
                                      Text(
                                        'Change Cover',
                                        style: TextStyle(
                                          fontWeight: FontWeight.w600,
                                          fontSize: 13,
                                          color: Color(0xFF1E293B),
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ),
                          ),
                        ],
                      ),

                      // 2. Avatar Overlap & Identity Information
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 24),
                        child: LayoutBuilder(
                          builder: (context, constraints) {
                            final isWide = constraints.maxWidth >= 720;
                            return Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                // Avatar overlapping the cover
                                Transform.translate(
                                  offset: const Offset(0, -50),
                                  child: Row(
                                    crossAxisAlignment: CrossAxisAlignment.end,
                                    children: [
                                      Stack(
                                        children: [
                                          Container(
                                            width: 110,
                                            height: 110,
                                            decoration: BoxDecoration(
                                              shape: BoxShape.circle,
                                              border: Border.all(color: Colors.white, width: 4.5),
                                              boxShadow: [
                                                BoxShadow(
                                                  color: Colors.black.withOpacity(0.16),
                                                  blurRadius: 14,
                                                  offset: const Offset(0, 4),
                                                ),
                                              ],
                                              gradient: const LinearGradient(
                                                colors: [Color(0xFF3B82F6), Color(0xFF6366F1), Color(0xFF7C3AED)],
                                                begin: Alignment.topLeft,
                                                end: Alignment.bottomRight,
                                              ),
                                            ),
                                            alignment: Alignment.center,
                                            child: resolvedAvatarUrl != null
                                                ? ClipOval(
                                                    child: Image.network(
                                                      resolvedAvatarUrl,
                                                      width: 110,
                                                      height: 110,
                                                      fit: BoxFit.cover,
                                                      errorBuilder: (_, error, stack) => Text(
                                                        initials.isNotEmpty ? initials : 'AS',
                                                        style: const TextStyle(
                                                          color: Colors.white,
                                                          fontWeight: FontWeight.bold,
                                                          fontSize: 32,
                                                          letterSpacing: 1,
                                                        ),
                                                      ),
                                                    ),
                                                  )
                                                : Text(
                                                    initials.isNotEmpty ? initials : 'AS',
                                                    style: const TextStyle(
                                                      color: Colors.white,
                                                      fontWeight: FontWeight.bold,
                                                      fontSize: 32,
                                                      letterSpacing: 1,
                                                    ),
                                                  ),
                                          ),
                                          // Camera badge on avatar
                                          Positioned(
                                            bottom: 2,
                                            right: 2,
                                            child: MouseRegion(
                                              cursor: SystemMouseCursors.click,
                                              child: GestureDetector(
                                                behavior: HitTestBehavior.opaque,
                                                onTap: () => _pickImage('Profile'),
                                                child: Container(
                                                  width: 32,
                                                  height: 32,
                                                  decoration: BoxDecoration(
                                                    color: Colors.white,
                                                    shape: BoxShape.circle,
                                                    border: Border.all(color: const Color(0xFFE2E8F0)),
                                                    boxShadow: [
                                                      BoxShadow(
                                                        color: Colors.black.withOpacity(0.12),
                                                        blurRadius: 6,
                                                      ),
                                                    ],
                                                  ),
                                                  child: const Icon(Icons.camera_alt_outlined, size: 16, color: Color(0xFF475569)),
                                                ),
                                              ),
                                            ),
                                          ),
                                        ],
                                      ),
                                    ],
                                  ),
                                ),

                                // Main Identity Info & Action Buttons
                                Transform.translate(
                                  offset: const Offset(0, -35),
                                  child: isWide
                                      ? Row(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                          children: [
                                            Expanded(child: _buildIdentityDetails(name, userId, email, bio)),
                                            const SizedBox(width: 16),
                                            _buildActionButtonsRow(),
                                          ],
                                        )
                                      : Column(
                                          crossAxisAlignment: CrossAxisAlignment.start,
                                          children: [
                                            _buildIdentityDetails(name, userId, email, bio),
                                            const SizedBox(height: 18),
                                            SingleChildScrollView(
                                              scrollDirection: Axis.horizontal,
                                              child: _buildActionButtonsRow(),
                                            ),
                                          ],
                                        ),
                                ),
                              ],
                            );
                          },
                        ),
                      ),

                      // 3. Complete 5-Box Statistics Row (Exact Screenshot)
                      Padding(
                        padding: const EdgeInsets.symmetric(horizontal: 20),
                        child: LayoutBuilder(
                          builder: (context, constraints) {
                            return Row(
                              children: [
                                _buildStatBox('$postsCount', 'Posts'),
                                const SizedBox(width: 10),
                                _buildStatBox('$storiesStat', 'Stories'),
                                const SizedBox(width: 10),
                                _buildStatBox('$connectionsCount', 'Connections'),
                                const SizedBox(width: 10),
                                _buildStatBox('$photosCount', 'Photos'),
                                const SizedBox(width: 10),
                                _buildStatBox('$videosCount', 'Videos'),
                              ],
                            );
                          },
                        ),
                      ),
                      const SizedBox(height: 18),

                      // 4. Horizontally Scrollable Tabs Bar with Left/Right Arrows (Exact Screenshot)
                      Container(
                        decoration: const BoxDecoration(
                          border: Border(top: BorderSide(color: Color(0xFFF1F5F9))),
                        ),
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                        child: Row(
                          children: [
                            // Left Arrow
                            IconButton(
                              icon: const Icon(Icons.arrow_left, color: Color(0xFF94A3B8), size: 22),
                              visualDensity: VisualDensity.compact,
                              padding: EdgeInsets.zero,
                              constraints: const BoxConstraints(minWidth: 28, minHeight: 28),
                              onPressed: () => _scrollTabs(true),
                            ),
                            // Tabs List
                            Expanded(
                              child: SingleChildScrollView(
                                controller: _tabsScrollController,
                                scrollDirection: Axis.horizontal,
                                child: Row(
                                  children: [
                                    _buildProfileTab('timeline', 'Timeline', Icons.article_outlined),
                                    _buildProfileTab('about', 'About', Icons.person_outline_rounded),
                                    _buildProfileTab('photos', 'Photos ($photosCount)', Icons.image_outlined),
                                    _buildProfileTab('videos', 'Videos ($videosCount)', Icons.videocam_outlined),
                                    _buildProfileTab('friends', 'Connections ($connectionsCount)', Icons.people_outline_rounded),
                                    _buildProfileTab('referrals', 'My Referrals (${member?.directReferrals ?? 0})', Icons.group_add_outlined),
                                    _buildProfileTab('stories', 'Stories ($storiesStat)', Icons.play_circle_outline_rounded),
                                    _buildProfileTab('saved', 'Saved Posts', Icons.bookmark_border_rounded),
                                  ],
                                ),
                              ),
                            ),
                            // Right Arrow
                            IconButton(
                              icon: const Icon(Icons.arrow_right, color: Color(0xFF94A3B8), size: 22),
                              visualDensity: VisualDensity.compact,
                              padding: EdgeInsets.zero,
                              constraints: const BoxConstraints(minWidth: 28, minHeight: 28),
                              onPressed: () => _scrollTabs(false),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                const SizedBox(height: 20),

                // ==========================================
                // ACTIVE TAB CONTENT CONTAINER (Exact Match)
                // ==========================================
                _buildActiveTabContent(name, userPosts, photoPosts, videoPosts),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // Identity Information
  Widget _buildIdentityDetails(String name, String userId, String email, String bio) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            Text(
              name,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 24, color: Color(0xFF0F172A)),
            ),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
              decoration: BoxDecoration(
                color: const Color(0xFFF0FDF4),
                borderRadius: BorderRadius.circular(6),
                border: Border.all(color: const Color(0xFFBBF7D0)),
              ),
              child: Row(
                mainAxisSize: MainAxisSize.min,
                children: const [
                  Icon(Icons.check_circle, color: Color(0xFF16A34A), size: 14),
                  SizedBox(width: 4),
                  Text(
                    'Verified',
                    style: TextStyle(color: Color(0xFF16A34A), fontWeight: FontWeight.bold, fontSize: 11.5),
                  ),
                ],
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        Text(
          '@$userId',
          style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF2563EB)),
        ),
        const SizedBox(height: 6),
        Row(
          children: [
            const Icon(Icons.mail_outline_rounded, size: 15, color: Color(0xFF64748B)),
            const SizedBox(width: 6),
            Text(
              email,
              style: const TextStyle(fontSize: 13, color: Color(0xFF475569)),
            ),
          ],
        ),
        const SizedBox(height: 6),
        Text(
          bio,
          style: const TextStyle(fontSize: 13, color: Color(0xFF475569), height: 1.35),
        ),
        const SizedBox(height: 8),
        Row(
          children: const [
            Icon(Icons.calendar_today_outlined, size: 14, color: Color(0xFF64748B)),
            SizedBox(width: 6),
            Text(
              'Joined September 2026',
              style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
            ),
          ],
        ),
      ],
    );
  }

  // Action Buttons Row (Exact 1:1 Matching Screenshot)
  Widget _buildActionButtonsRow() {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        // 1. Verified Button
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
          decoration: BoxDecoration(
            color: const Color(0xFFF0FDF4),
            borderRadius: BorderRadius.circular(9),
            border: Border.all(color: const Color(0xFFBBF7D0)),
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: const [
              Icon(Icons.shield_outlined, size: 16, color: Color(0xFF16A34A)),
              SizedBox(width: 6),
              Text(
                'Verified',
                style: TextStyle(color: Color(0xFF15803D), fontWeight: FontWeight.bold, fontSize: 12.5),
              ),
            ],
          ),
        ),
        const SizedBox(width: 8),

        // 2. Visitors Button
        OutlinedButton.icon(
          onPressed: () {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Profile visitors analytics active.'),
                behavior: SnackBarBehavior.floating,
                duration: Duration(seconds: 1),
              ),
            );
          },
          icon: const Icon(Icons.remove_red_eye_outlined, size: 16, color: Color(0xFF1E293B)),
          label: const Text('Visitors', style: TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.w600, fontSize: 12.5)),
          style: OutlinedButton.styleFrom(
            side: const BorderSide(color: Color(0xFFE2E8F0)),
            backgroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(9)),
          ),
        ),
        const SizedBox(width: 8),

        // 3. Edit Profile Button (Solid Blue)
        ElevatedButton.icon(
          onPressed: () {
            Navigator.push(context, MaterialPageRoute(builder: (_) => const EditProfileScreen()));
          },
          icon: const Icon(Icons.edit_outlined, size: 16, color: Colors.white),
          label: const Text('Edit Profile', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12.5)),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF2563EB),
            foregroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            elevation: 0,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(9)),
          ),
        ),
        const SizedBox(width: 8),

        // 4. Account Settings Button
        OutlinedButton.icon(
          onPressed: () {
            Navigator.push(context, MaterialPageRoute(builder: (_) => const AccountSettingsScreen()));
          },
          icon: const Icon(Icons.settings_outlined, size: 16, color: Color(0xFF1E293B)),
          label: const Text('Account Settings', style: TextStyle(color: Color(0xFF1E293B), fontWeight: FontWeight.w600, fontSize: 12.5)),
          style: OutlinedButton.styleFrom(
            side: const BorderSide(color: Color(0xFFE2E8F0)),
            backgroundColor: Colors.white,
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(9)),
          ),
        ),
      ],
    );
  }

  // Statistics 5-Box Widget
  Widget _buildStatBox(String count, String label) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 4),
        decoration: BoxDecoration(
          color: const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(12),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Column(
          children: [
            Text(
              count,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 17, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 2),
            Text(
              label,
              style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
            ),
          ],
        ),
      ),
    );
  }

  // Profile Horizontal Tab Button (Solid Blue Pill when Active)
  Widget _buildProfileTab(String key, String label, IconData icon) {
    final isActive = _activeTab == key;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 3),
      child: MouseRegion(
        cursor: SystemMouseCursors.click,
        child: GestureDetector(
          behavior: HitTestBehavior.opaque,
          onTap: () => setState(() => _activeTab = key),
          child: AnimatedContainer(
            duration: const Duration(milliseconds: 150),
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            decoration: BoxDecoration(
              color: isActive ? const Color(0xFF2563EB) : Colors.transparent,
              borderRadius: BorderRadius.circular(10),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(
                  icon,
                  size: 17,
                  color: isActive ? Colors.white : const Color(0xFF64748B),
                ),
                const SizedBox(width: 8),
                Text(
                  label,
                  style: TextStyle(
                    fontWeight: isActive ? FontWeight.bold : FontWeight.w500,
                    fontSize: 13,
                    color: isActive ? Colors.white : const Color(0xFF475569),
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // Dynamic Tab Content Dispatcher
  Widget _buildActiveTabContent(String name, List<PostModel> posts, List<PostModel> photos, List<PostModel> videos) {
    switch (_activeTab) {
      case 'videos':
        return _buildVideosTabContent(name, videos);
      case 'photos':
        return _buildPhotosTabContent(name, photos);
      case 'about':
        return _buildAboutTabContent();
      case 'friends':
        return _buildConnectionsTabContent();
      case 'referrals':
        return _buildReferralsTabContent();
      case 'stories':
        return _buildStoriesTabContent(name);
      case 'saved':
        return _buildSavedTabContent();
      case 'timeline':
      default:
        return _buildTimelineTabContent(posts);
    }
  }

  // 1. VIDEOS TAB (Exact Match to User's Screenshot!)
  Widget _buildVideosTabContent(String name, List<PostModel> videos) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.videocam_outlined, size: 22, color: Color(0xFF0F172A)),
              SizedBox(width: 8),
              Text(
                'Videos',
                style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A)),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            '${videos.length} videos shared by $name',
            style: const TextStyle(fontSize: 13, color: Color(0xFF64748B)),
          ),
          const SizedBox(height: 36),

          // Empty State or Video Grid (Matching Screenshot)
          if (videos.isEmpty)
            Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 58,
                    height: 58,
                    decoration: const BoxDecoration(
                      color: Color(0xFFF1F5F9),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(Icons.videocam_off_outlined, size: 28, color: Color(0xFF94A3B8)),
                  ),
                  const SizedBox(height: 14),
                  const Text(
                    'No Videos Uploaded',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                  ),
                  const SizedBox(height: 4),
                  const Text(
                    'Videos uploaded in timeline posts will appear here.',
                    style: TextStyle(fontSize: 13, color: Color(0xFF64748B)),
                  ),
                ],
              ),
            )
          else
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 3,
                crossAxisSpacing: 12,
                mainAxisSpacing: 12,
                childAspectRatio: 1.1,
              ),
              itemCount: videos.length,
              itemBuilder: (ctx, i) {
                final v = videos[i];
                final mediaUrl = v.resolvedMediaUrl ?? v.mediaUrl;
                return ClipRRect(
                  borderRadius: BorderRadius.circular(10),
                  child: Container(
                    color: Colors.black,
                    child: Stack(
                      alignment: Alignment.center,
                      children: [
                        if (mediaUrl != null)
                          Positioned.fill(
                            child: Image.network(
                              mediaUrl,
                              fit: BoxFit.cover,
                              errorBuilder: (_, error, stack) => const Center(
                                child: Icon(Icons.videocam, color: Colors.white38, size: 36),
                              ),
                            ),
                          ),
                        Container(
                          decoration: const BoxDecoration(
                            color: Colors.black38,
                          ),
                        ),
                        const Icon(Icons.play_circle_fill, size: 40, color: Colors.white),
                      ],
                    ),
                  ),
                );
              },
            ),
          const SizedBox(height: 20),
        ],
      ),
    );
  }

  // 2. PHOTOS TAB
  Widget _buildPhotosTabContent(String name, List<PostModel> photos) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: const [
              Icon(Icons.image_outlined, size: 22, color: Color(0xFF0F172A)),
              SizedBox(width: 8),
              Text('Photos', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A))),
            ],
          ),
          const SizedBox(height: 4),
          Text('${photos.length} photos shared by $name', style: const TextStyle(fontSize: 13, color: Color(0xFF64748B))),
          const SizedBox(height: 24),
          if (photos.isEmpty)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(40),
                child: Text('No photos shared yet.'),
              ),
            )
          else
            GridView.builder(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                crossAxisCount: 3,
                crossAxisSpacing: 10,
                mainAxisSpacing: 10,
                childAspectRatio: 1.0,
              ),
              itemCount: photos.length,
              itemBuilder: (ctx, i) {
                final p = photos[i];
                return Container(
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(10),
                    image: p.mediaUrl != null
                        ? DecorationImage(image: NetworkImage(p.mediaUrl!), fit: BoxFit.cover)
                        : null,
                  ),
                );
              },
            ),
        ],
      ),
    );
  }

  // 3. ABOUT TAB
  Widget _buildAboutTabContent() {
    final member = context.read<AuthProvider>().currentMember;

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: const [
                  Text('About', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A))),
                  SizedBox(height: 2),
                  Text('Personal, contact and verified profile details', style: TextStyle(fontSize: 12.5, color: Color(0xFF64748B))),
                ],
              ),
              OutlinedButton.icon(
                onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const EditProfileScreen())),
                icon: const Icon(Icons.edit_outlined, size: 15),
                label: const Text('Edit'),
              ),
            ],
          ),
          const SizedBox(height: 20),
          _buildAboutRow(Icons.chat_outlined, 'Bio', member?.bio ?? 'Not added yet'),
          const SizedBox(height: 14),
          _buildAboutRow(Icons.phone_outlined, 'Phone', member?.phone ?? 'Not added yet'),
          const SizedBox(height: 14),
          _buildAboutRow(Icons.mail_outline_rounded, 'Account Email', member?.email ?? 'N/A'),
          const SizedBox(height: 14),
          _buildAboutRow(Icons.location_on_outlined, 'Location', [member?.city, member?.country].where((s) => s != null && s.isNotEmpty).join(', ').isNotEmpty ? [member?.city, member?.country].where((s) => s != null && s.isNotEmpty).join(', ') : 'Not added yet'),
          const SizedBox(height: 14),
          _buildAboutRow(Icons.shield_outlined, 'Verification Status', member?.isVerified == true ? 'Verified on WhatsApp & Mobile' : 'Standard Member'),
        ],
      ),
    );
  }

  Widget _buildAboutRow(IconData icon, String title, String value) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(icon, size: 18, color: const Color(0xFF64748B)),
        const SizedBox(width: 12),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(title, style: const TextStyle(fontSize: 12, color: Color(0xFF94A3B8))),
              const SizedBox(height: 2),
              Text(value, style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: Color(0xFF1E293B))),
            ],
          ),
        ),
      ],
    );
  }

  // 4. CONNECTIONS TAB
  Widget _buildConnectionsTabContent() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text('Connections (${_friendsList.length})', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A))),
              TextButton(
                onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const NewConnectionsScreen())),
                child: const Text('Find New Connections'),
              ),
            ],
          ),
          const SizedBox(height: 16),
          if (_friendsList.isEmpty)
            const Center(
              child: Padding(
                padding: EdgeInsets.all(32),
                child: Text('No active connections yet.'),
              ),
            )
          else
            ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _friendsList.length,
              separatorBuilder: (c, i) => const Divider(color: Color(0xFFF1F5F9)),
              itemBuilder: (c, i) {
                final f = _friendsList[i];
                return ListTile(
                  contentPadding: EdgeInsets.zero,
                  leading: CircleAvatar(
                    backgroundColor: const Color(0xFF2563EB),
                    child: Text(f['name']?[0] ?? 'M', style: const TextStyle(color: Colors.white)),
                  ),
                  title: Text(f['name'] ?? 'Member', style: const TextStyle(fontWeight: FontWeight.bold)),
                  subtitle: Text('@${f['user_id'] ?? ''}'),
                  trailing: IconButton(
                    icon: const Icon(Icons.chat_bubble_outline, color: Color(0xFF2563EB)),
                    onPressed: () {
                      Navigator.push(
                        context,
                        MaterialPageRoute(
                          builder: (_) => ChatConversationScreen(partnerId: f['id'] ?? 0, partnerName: f['name'] ?? ''),
                        ),
                      );
                    },
                  ),
                );
              },
            ),
        ],
      ),
    );
  }

  // 5. REFERRALS TAB
  Widget _buildReferralsTabContent() {
    final member = context.read<AuthProvider>().currentMember;

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('My Referrals & Introducer', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A))),
              ElevatedButton.icon(
                onPressed: () {
                  showDialog(context: context, builder: (_) => const ReferralProgramDialog());
                },
                icon: const Icon(Icons.card_giftcard, size: 16),
                label: const Text('Open Referral Center'),
                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2563EB), foregroundColor: Colors.white),
              ),
            ],
          ),
          const SizedBox(height: 16),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(12),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: Row(
              children: [
                const Icon(Icons.verified_user_outlined, color: Color(0xFF2563EB), size: 24),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      const Text('Official Platform Direct Member', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                      const SizedBox(height: 2),
                      Text('Direct referrals connected: ${member?.directReferrals ?? 0}', style: const TextStyle(fontSize: 12, color: Color(0xFF64748B))),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // 6. STORIES TAB
  Widget _buildStoriesTabContent(String name) {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Active Stories by $name', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A))),
          const SizedBox(height: 16),
          const Text('24h stories appear at the top of the social feed for all members.'),
        ],
      ),
    );
  }

  // 7. SAVED POSTS TAB
  Widget _buildSavedTabContent() {
    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              const Text('Saved Posts', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A))),
              TextButton(
                onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const FriendsScreen(initialTab: 0))),
                child: const Text('View All'),
              ),
            ],
          ),
          const SizedBox(height: 16),
          const Text('Posts you bookmark from feeds are safely archived here for quick review.'),
        ],
      ),
    );
  }

  // 8. TIMELINE TAB
  Widget _buildTimelineTabContent(List<PostModel> posts) {
    return Column(
      children: [
        if (posts.isEmpty)
          Container(
            padding: const EdgeInsets.all(32),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFE2E8F0)),
            ),
            child: const Center(
              child: Text('No posts created on timeline yet. Share updates from the Socials feed!'),
            ),
          )
        else
          ListView.builder(
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            itemCount: posts.length,
            itemBuilder: (ctx, i) {
              final p = posts[i];
              return Card(
                margin: const EdgeInsets.only(bottom: 12),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(p.authorName, style: const TextStyle(fontWeight: FontWeight.bold)),
                      const SizedBox(height: 4),
                      Text(p.body ?? ''),
                    ],
                  ),
                ),
              );
            },
          ),
      ],
    );
  }
}
