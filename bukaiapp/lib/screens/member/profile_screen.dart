import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../core/api_client.dart';
import '../../core/app_toast.dart';
import '../../core/constants.dart';
import '../../models/post_model.dart';
import '../../providers/auth_provider.dart';
import '../../providers/feed_provider.dart';
import '../../widgets/profile_image_adjust_dialog.dart';
import '../../widgets/referral_program_dialog.dart';
import 'account_settings_screen.dart';
import 'edit_profile_screen.dart';
import 'messages_screen.dart';
import 'new_connections_screen.dart';

class ProfileScreen extends StatefulWidget {
  final int? memberId;
  final String initialTab;
  const ProfileScreen({super.key, this.memberId, this.initialTab = 'timeline'});

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final ImagePicker _picker = ImagePicker();

  Map<String, dynamic>? _profileData;
  List<Map<String, dynamic>> _friendsList = [];
  bool _isFollowing = false;

  final List<String> _tabKeys = ['timeline', 'about', 'photos', 'friends', 'referrals', 'saved'];

  @override
  void initState() {
    super.initState();
    int initialIdx = _tabKeys.indexOf(widget.initialTab);
    if (initialIdx == -1) initialIdx = 0;
    _tabController = TabController(length: _tabKeys.length, vsync: this, initialIndex: initialIdx);
    _fetchProfileDetails();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  Future<void> _fetchProfileDetails() async {
    final endpoint = widget.memberId != null ? '/profile/${widget.memberId}' : '/profile/me';
    final res = await ApiClient.get(endpoint);
    if (res.success && res.data is Map && res.data['profile'] is Map) {
      if (mounted) {
        setState(() {
          _profileData = res.data['profile'] as Map<String, dynamic>;
          if (_profileData?['is_following'] != null) {
            _isFollowing = _profileData!['is_following'] == true;
          }
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

  Future<void> _toggleFollowMember() async {
    if (widget.memberId == null) return;
    setState(() => _isFollowing = !_isFollowing);
    final res = await ApiClient.post('/friends/follow/${widget.memberId}', {});
    if (res.success && res.data is Map && res.data['is_following'] != null) {
      if (mounted) {
        setState(() {
          _isFollowing = res.data['is_following'] == true;
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
      AppToast.success(context, isAvatar ? 'Profile photo removed' : 'Cover photo removed');
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
          AppToast.success(context, isAvatar ? 'Profile photo updated!' : 'Cover photo updated!');
        }
      }
    } catch (e) {
      debugPrint('Image pick error: $e');
    }
  }

  void _showPhotoOptionsSheet(String type, String? currentPhotoUrl, String title) {
    final isAvatar = type == 'avatar';
    showModalBottomSheet(
      context: context,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) => SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 12),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Container(
                width: 40,
                height: 4,
                margin: const EdgeInsets.only(bottom: 12),
                decoration: BoxDecoration(
                  color: const Color(0xFFCBD5E1),
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 6),
                child: Row(
                  children: [
                    Text(
                      title,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                    ),
                  ],
                ),
              ),
              const Divider(height: 1, color: Color(0xFFF1F5F9)),
              if (currentPhotoUrl != null) ...[
                ListTile(
                  leading: const Icon(Icons.fullscreen, color: Color(0xFF2563EB)),
                  title: Text(isAvatar ? 'View Profile Photo' : 'View Cover Photo'),
                  onTap: () {
                    Navigator.pop(ctx);
                    _showImageLightbox(currentPhotoUrl, isAvatar ? 'Profile Photo' : 'Cover Photo');
                  },
                ),
              ],
              ListTile(
                leading: const Icon(Icons.photo_library_outlined, color: Color(0xFF2563EB)),
                title: Text(currentPhotoUrl != null ? 'Change Photo' : 'Upload Photo'),
                onTap: () {
                  Navigator.pop(ctx);
                  _pickImage(type);
                },
              ),
              if (currentPhotoUrl != null) ...[
                ListTile(
                  leading: const Icon(Icons.delete_outline, color: Color(0xFFEF4444)),
                  title: const Text('Remove Photo', style: TextStyle(color: Color(0xFFEF4444))),
                  onTap: () {
                    Navigator.pop(ctx);
                    _handleRemovePhoto(type);
                  },
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }

  void _showImageLightbox(String url, String title) {
    showDialog(
      context: context,
      builder: (ctx) => Dialog(
        backgroundColor: Colors.black,
        insetPadding: EdgeInsets.zero,
        child: Stack(
          alignment: Alignment.center,
          children: [
            InteractiveViewer(
              minScale: 0.8,
              maxScale: 4.0,
              child: Image.network(
                url,
                fit: BoxFit.contain,
                width: double.infinity,
                height: double.infinity,
              ),
            ),
            Positioned(
              top: 40,
              left: 16,
              right: 16,
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    title,
                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
                  ),
                  IconButton(
                    icon: const Icon(Icons.close, color: Colors.white),
                    onPressed: () => Navigator.pop(ctx),
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final feed = context.watch<FeedProvider>();
    final isOwnProfile = widget.memberId == null || (auth.currentMember != null && widget.memberId == auth.currentMember!.id);
    final member = isOwnProfile ? auth.currentMember : null;

    final name = isOwnProfile
        ? (member?.name.isNotEmpty == true ? member!.name : (_profileData?['name']?.toString() ?? 'Member'))
        : (_profileData?['name']?.toString() ?? 'Member');

    final userId = isOwnProfile
        ? (member?.userId.isNotEmpty == true ? member!.userId : (_profileData?['user_id']?.toString() ?? 'USER1234'))
        : (_profileData?['user_id']?.toString() ?? 'user');

    final bio = isOwnProfile
        ? (member?.bio?.isNotEmpty == true ? member!.bio! : (_profileData?['bio']?.toString() ?? 'No bio added yet.'))
        : (_profileData?['bio']?.toString() ?? 'No bio added yet.');

    final city = isOwnProfile ? (member?.city ?? _profileData?['city']?.toString()) : _profileData?['city']?.toString();
    final country = isOwnProfile ? (member?.country ?? _profileData?['country']?.toString()) : _profileData?['country']?.toString();
    final email = isOwnProfile ? (member?.email ?? _profileData?['email']?.toString() ?? '') : (_profileData?['email']?.toString() ?? '');
    final phone = isOwnProfile ? (member?.phone ?? _profileData?['phone']?.toString() ?? '') : (_profileData?['phone']?.toString() ?? '');
    final isVerified = isOwnProfile
        ? (member?.isVerified == true || _profileData?['is_verified'] == true || _profileData?['is_verified'] == 1)
        : (_profileData?['is_verified'] == true || _profileData?['is_verified'] == 1);

    final userPosts = feed.posts.where((p) {
      if (isOwnProfile) {
        return member != null && p.author.id == member.id;
      } else {
        return widget.memberId != null && p.author.id == widget.memberId;
      }
    }).toList();

    final photoPosts = userPosts.where((p) => p.mediaType == 'image' || (p.mediaUrl != null && !p.mediaUrl!.endsWith('.mp4'))).toList();
    final storiesCount = isOwnProfile
        ? feed.storyGroups.where((g) => member != null && g.memberId == member.id).fold<int>(0, (sum, g) => sum + g.storiesCount)
        : 0;

    final postsCount = userPosts.length;
    final photosCount = photoPosts.isNotEmpty ? photoPosts.length : 0;
    final connectionsCount = isOwnProfile
        ? _friendsList.length
        : (_profileData?['friends_count'] is int ? _profileData!['friends_count'] as int : 0);
    final storiesStat = storiesCount > 0 ? storiesCount : 0;
    final directReferralsCount = isOwnProfile
        ? (_profileData?['direct_referrals_count'] ?? member?.directReferrals ?? 0)
        : (_profileData?['direct_referrals_count'] is int ? _profileData!['direct_referrals_count'] as int : 0);

    final rawCoverUrl = isOwnProfile
        ? (member?.coverPhoto ?? _profileData?['cover_photo'] ?? _profileData?['cover_photo_url'])
        : (_profileData?['cover_photo_url'] ?? _profileData?['cover_photo']);
    final resolvedCoverUrl = AppConstants.resolveMediaUrl(rawCoverUrl?.toString(), ApiClient.baseUrl);

    final rawAvatarUrl = isOwnProfile
        ? (member?.avatarUrl ?? _profileData?['avatar_url'] ?? _profileData?['profile_photo'])
        : (_profileData?['avatar_url'] ?? _profileData?['profile_photo']);
    final resolvedAvatarUrl = AppConstants.resolveMediaUrl(rawAvatarUrl?.toString(), ApiClient.baseUrl);

    final initials = name.trim().split(' ').map((e) => e.isNotEmpty ? e[0] : '').take(2).join('').toUpperCase();
    final topPadding = MediaQuery.of(context).padding.top;

    const double coverHeight = 220.0;
    const double avatarRadius = 44.0;
    const double avatarDiameter = avatarRadius * 2;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      body: NestedScrollView(
        headerSliverBuilder: (context, innerBoxIsScrolled) {
          return [
            // 1. Unified Cover & Overlapping Avatar Header (Zero Clipping Seam)
            SliverToBoxAdapter(
              child: Stack(
                clipBehavior: Clip.none,
                children: [
                  // A. Cover Banner Box
                  Container(
                    height: coverHeight,
                    width: double.infinity,
                    color: const Color(0xFF1E293B),
                    child: Stack(
                      fit: StackFit.expand,
                      children: [
                        if (resolvedCoverUrl != null)
                          Image.network(
                            resolvedCoverUrl,
                            fit: BoxFit.cover,
                          )
                        else
                          Container(
                            decoration: const BoxDecoration(
                              gradient: LinearGradient(
                                colors: [Color(0xFF1E3A8A), Color(0xFF3B82F6), Color(0xFF6366F1)],
                                begin: Alignment.topLeft,
                                end: Alignment.bottomRight,
                              ),
                            ),
                          ),

                        // Subtle Gradient Overlay for Top & Bottom Contrast
                        Container(
                          decoration: BoxDecoration(
                            gradient: LinearGradient(
                              colors: [
                                Colors.black.withOpacity(0.6),
                                Colors.transparent,
                                Colors.black.withOpacity(0.4),
                              ],
                              begin: Alignment.topCenter,
                              end: Alignment.bottomCenter,
                              stops: const [0.0, 0.45, 1.0],
                            ),
                          ),
                        ),

                        // 1-Tap Edit Cover Pill Button (Lower Right of Banner - Own Profile Only)
                        if (isOwnProfile)
                          Positioned(
                            bottom: 48,
                            right: 14,
                            child: GestureDetector(
                              onTap: () => _showPhotoOptionsSheet('cover', resolvedCoverUrl, 'Cover Banner'),
                              child: Container(
                                padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 6),
                                decoration: BoxDecoration(
                                  color: Colors.black.withOpacity(0.65),
                                  borderRadius: BorderRadius.circular(20),
                                  border: Border.all(color: Colors.white24),
                                  boxShadow: [
                                    BoxShadow(color: Colors.black.withOpacity(0.2), blurRadius: 6, offset: const Offset(0, 2)),
                                  ],
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: const [
                                    Icon(Icons.camera_alt_outlined, color: Colors.white, size: 13),
                                    SizedBox(width: 5),
                                    Text(
                                      'Edit Cover',
                                      style: TextStyle(color: Colors.white, fontSize: 11.5, fontWeight: FontWeight.w600),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          ),

                        // Top Navigation Actions (Back, Share, Settings) - Positioned at Safe Area Top
                        Positioned(
                          top: topPadding > 0 ? topPadding + 6 : 14,
                          left: 12,
                          right: 12,
                          child: Row(
                            mainAxisAlignment: MainAxisAlignment.spaceBetween,
                            children: [
                              // Back Button
                              GestureDetector(
                                onTap: () => Navigator.maybePop(context),
                                child: Container(
                                  width: 38,
                                  height: 38,
                                  decoration: BoxDecoration(
                                    color: Colors.black.withOpacity(0.5),
                                    shape: BoxShape.circle,
                                    border: Border.all(color: Colors.white.withOpacity(0.25), width: 1),
                                    boxShadow: [
                                      BoxShadow(color: Colors.black.withOpacity(0.25), blurRadius: 8, offset: const Offset(0, 2)),
                                    ],
                                  ),
                                  child: const Icon(Icons.arrow_back, color: Colors.white, size: 19),
                                ),
                              ),

                              // Right Action Buttons (Share & Settings)
                              Row(
                                children: [
                                  GestureDetector(
                                    onTap: () {
                                      if (isOwnProfile) {
                                        showDialog(
                                          context: context,
                                          builder: (_) => const ReferralProgramDialog(),
                                        );
                                      } else {
                                        Clipboard.setData(ClipboardData(text: 'https://mlmbookai.com/member/$userId'));
                                        AppToast.info(context, 'Profile link copied to clipboard!');
                                      }
                                    },
                                    child: Container(
                                      width: 38,
                                      height: 38,
                                      decoration: BoxDecoration(
                                        color: Colors.black.withOpacity(0.5),
                                        shape: BoxShape.circle,
                                        border: Border.all(color: Colors.white.withOpacity(0.25), width: 1),
                                        boxShadow: [
                                          BoxShadow(color: Colors.black.withOpacity(0.25), blurRadius: 8, offset: const Offset(0, 2)),
                                        ],
                                      ),
                                      child: const Icon(Icons.share_outlined, color: Colors.white, size: 18),
                                    ),
                                  ),
                                  if (isOwnProfile) ...[
                                    const SizedBox(width: 8),
                                    GestureDetector(
                                      onTap: () {
                                        Navigator.push(context, MaterialPageRoute(builder: (_) => const AccountSettingsScreen()));
                                      },
                                      child: Container(
                                        width: 38,
                                        height: 38,
                                        decoration: BoxDecoration(
                                          color: Colors.black.withOpacity(0.5),
                                          shape: BoxShape.circle,
                                          border: Border.all(color: Colors.white.withOpacity(0.25), width: 1),
                                          boxShadow: [
                                            BoxShadow(color: Colors.black.withOpacity(0.25), blurRadius: 8, offset: const Offset(0, 2)),
                                          ],
                                        ),
                                        child: const Icon(Icons.settings_outlined, color: Colors.white, size: 18),
                                      ),
                                    ),
                                  ],
                                ],
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

                  // B. White Profile Sheet Body (Curved Top overlapping Cover)
                  Container(
                    margin: const EdgeInsets.only(top: coverHeight - 38),
                    decoration: const BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
                    ),
                    padding: const EdgeInsets.fromLTRB(16, 12, 16, 16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Row aligning the right action buttons alongside the avatar space
                        Row(
                          mainAxisAlignment: MainAxisAlignment.end,
                          children: isOwnProfile
                              ? [
                                  OutlinedButton.icon(
                                    onPressed: () {
                                      Navigator.push(context, MaterialPageRoute(builder: (_) => const EditProfileScreen()));
                                    },
                                    icon: const Icon(Icons.edit_outlined, size: 15),
                                    label: const Text('Edit Profile', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                                    style: OutlinedButton.styleFrom(
                                      foregroundColor: const Color(0xFF0F172A),
                                      side: const BorderSide(color: Color(0xFFCBD5E1)),
                                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  IconButton.filledTonal(
                                    onPressed: () {
                                      showDialog(context: context, builder: (_) => const ReferralProgramDialog());
                                    },
                                    icon: const Icon(Icons.card_giftcard, size: 17),
                                    style: IconButton.styleFrom(
                                      backgroundColor: const Color(0xFFEFF6FF),
                                      foregroundColor: const Color(0xFF2563EB),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                    ),
                                    tooltip: 'Referral Program',
                                  ),
                                ]
                              : [
                                  ElevatedButton.icon(
                                    onPressed: () {
                                      Navigator.push(
                                        context,
                                        MaterialPageRoute(
                                          builder: (_) => ChatConversationScreen(
                                            partnerId: widget.memberId ?? 0,
                                            partnerName: name,
                                          ),
                                        ),
                                      );
                                    },
                                    icon: const Icon(Icons.chat_bubble_outline_rounded, size: 15, color: Colors.white),
                                    label: const Text('Message', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                                    style: ElevatedButton.styleFrom(
                                      backgroundColor: const Color(0xFF2563EB),
                                      foregroundColor: Colors.white,
                                      elevation: 0,
                                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  OutlinedButton.icon(
                                    onPressed: _toggleFollowMember,
                                    icon: Icon(
                                      _isFollowing ? Icons.check : Icons.person_add_outlined,
                                      size: 15,
                                      color: _isFollowing ? const Color(0xFF16A34A) : const Color(0xFF2563EB),
                                    ),
                                    label: Text(
                                      _isFollowing ? 'Following' : 'Follow',
                                      style: TextStyle(
                                        fontWeight: FontWeight.w600,
                                        fontSize: 13,
                                        color: _isFollowing ? const Color(0xFF16A34A) : const Color(0xFF2563EB),
                                      ),
                                    ),
                                    style: OutlinedButton.styleFrom(
                                      side: BorderSide(color: _isFollowing ? const Color(0xFF86EFAC) : const Color(0xFFBFDBFE)),
                                      backgroundColor: _isFollowing ? const Color(0xFFF0FDF4) : const Color(0xFFEFF6FF),
                                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                    ),
                                  ),
                                ],
                        ),

                        const SizedBox(height: 10),

                        // Name & Verified Badge
                        Row(
                          children: [
                            Flexible(
                              child: Text(
                                name,
                                style: const TextStyle(
                                  fontSize: 21,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF0F172A),
                                  letterSpacing: -0.2,
                                ),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            if (isVerified) ...[
                              const SizedBox(width: 6),
                              const Icon(Icons.verified, color: Color(0xFF16A34A), size: 19),
                            ],
                          ],
                        ),
                        const SizedBox(height: 4),

                        // User ID Chip with Copy
                        GestureDetector(
                          onTap: () {
                            Clipboard.setData(ClipboardData(text: userId));
                            AppToast.info(context, 'User ID @$userId copied!');
                          },
                          child: Container(
                            padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                            decoration: BoxDecoration(
                              color: const Color(0xFFF1F5F9),
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Row(
                              mainAxisSize: MainAxisSize.min,
                              children: [
                                Text(
                                  '@$userId',
                                  style: const TextStyle(
                                    color: Color(0xFF2563EB),
                                    fontSize: 12.5,
                                    fontWeight: FontWeight.w600,
                                  ),
                                ),
                                const SizedBox(width: 5),
                                const Icon(Icons.copy, size: 12, color: Color(0xFF64748B)),
                              ],
                            ),
                          ),
                        ),

                        // Bio
                        if (bio.isNotEmpty) ...[
                          const SizedBox(height: 10),
                          Text(
                            bio,
                            style: const TextStyle(
                              fontSize: 13.5,
                              color: Color(0xFF334155),
                              height: 1.4,
                            ),
                          ),
                        ],

                        // Joined date & location metadata
                        const SizedBox(height: 10),
                        Wrap(
                          spacing: 12,
                          runSpacing: 6,
                          children: [
                            Row(
                              mainAxisSize: MainAxisSize.min,
                              children: const [
                                Icon(Icons.calendar_today_outlined, size: 13, color: Color(0xFF64748B)),
                                SizedBox(width: 5),
                                Text(
                                  'Joined September 2026',
                                  style: TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                                ),
                              ],
                            ),
                            if ([city, country].where((s) => s != null && s.toString().trim().isNotEmpty).isNotEmpty)
                              Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  const Icon(Icons.location_on_outlined, size: 14, color: Color(0xFF64748B)),
                                  const SizedBox(width: 4),
                                  Text(
                                    [city, country].where((s) => s != null && s.toString().trim().isNotEmpty).join(', '),
                                    style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                                  ),
                                ],
                              ),
                          ],
                        ),

                        const SizedBox(height: 16),

                        // 4-Column Metric Strip (Posts, Stories, Connections, Photos)
                        Container(
                          padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
                          decoration: BoxDecoration(
                            color: const Color(0xFFF8FAFC),
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: const Color(0xFFE2E8F0)),
                          ),
                          child: Row(
                            children: [
                              _buildNativeStat(
                                '$postsCount',
                                'Posts',
                                onTap: () => _tabController.animateTo(0),
                              ),
                              _buildStatDivider(),
                              _buildNativeStat(
                                '$storiesStat',
                                'Stories',
                                onTap: () => _tabController.animateTo(0),
                              ),
                              _buildStatDivider(),
                              _buildNativeStat(
                                '$connectionsCount',
                                'Connections',
                                onTap: () => _tabController.animateTo(3),
                              ),
                              _buildStatDivider(),
                              _buildNativeStat(
                                '$photosCount',
                                'Photos',
                                onTap: () => _tabController.animateTo(2),
                              ),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),

                  // C. High-Definition Avatar (Precisely Placed on Seam - Never Clipped)
                  Positioned(
                    top: coverHeight - 38 - (avatarDiameter / 2),
                    left: 16,
                    child: Stack(
                      children: [
                        GestureDetector(
                          onTap: () {
                            if (isOwnProfile) {
                              _showPhotoOptionsSheet('avatar', resolvedAvatarUrl, 'Profile Photo');
                            } else if (resolvedAvatarUrl != null) {
                              _showImageLightbox(resolvedAvatarUrl, '$name\'s Photo');
                            }
                          },
                          child: Container(
                            width: avatarDiameter,
                            height: avatarDiameter,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: const Color(0xFF2563EB),
                              border: Border.all(color: Colors.white, width: 3.5),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withOpacity(0.18),
                                  blurRadius: 10,
                                  offset: const Offset(0, 4),
                                ),
                              ],
                              image: resolvedAvatarUrl != null
                                  ? DecorationImage(
                                      image: NetworkImage(resolvedAvatarUrl),
                                      fit: BoxFit.cover,
                                    )
                                  : null,
                            ),
                            child: resolvedAvatarUrl == null
                                ? Center(
                                    child: Text(
                                      initials,
                                      style: const TextStyle(
                                        color: Colors.white,
                                        fontSize: 28,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                                  )
                                : null,
                          ),
                        ),

                        // Floating Camera Badge on Avatar (Own Profile Only)
                        if (isOwnProfile)
                          Positioned(
                            bottom: 2,
                            right: 2,
                            child: GestureDetector(
                              onTap: () => _showPhotoOptionsSheet('avatar', resolvedAvatarUrl, 'Profile Photo'),
                              child: Container(
                                padding: const EdgeInsets.all(6),
                                decoration: BoxDecoration(
                                  color: const Color(0xFF2563EB),
                                  shape: BoxShape.circle,
                                  border: Border.all(color: Colors.white, width: 2),
                                ),
                                child: const Icon(Icons.camera_alt, color: Colors.white, size: 12),
                              ),
                            ),
                          ),
                      ],
                    ),
                  ),
                ],
              ),
            ),

            // 2. Sticky Tab Bar
            SliverPersistentHeader(
              pinned: true,
              delegate: _SliverTabBarDelegate(
                TabBar(
                  controller: _tabController,
                  isScrollable: true,
                  tabAlignment: TabAlignment.start,
                  indicatorColor: const Color(0xFF2563EB),
                  indicatorWeight: 2.5,
                  indicatorSize: TabBarIndicatorSize.label,
                  labelColor: const Color(0xFF2563EB),
                  unselectedLabelColor: const Color(0xFF64748B),
                  labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                  unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.normal, fontSize: 13.5),
                  padding: const EdgeInsets.symmetric(horizontal: 8),
                  tabs: [
                    const Tab(text: 'Timeline'),
                    const Tab(text: 'About'),
                    Tab(text: 'Photos ($photosCount)'),
                    Tab(text: 'Connections ($connectionsCount)'),
                    Tab(text: 'My Referrals ($directReferralsCount)'),
                    const Tab(text: 'Saved'),
                  ],
                ),
              ),
            ),
          ];
        },
        body: TabBarView(
          controller: _tabController,
          children: [
            _buildTimelineTab(userPosts),
            _buildAboutTab(
              name: name,
              userId: userId,
              email: email,
              phone: phone,
              city: city,
              country: country,
              isVerified: isVerified,
            ),
            _buildPhotosTab(photoPosts),
            _buildConnectionsTab(),
            _buildReferralsTab(),
            _buildSavedTab(),
          ],
        ),
      ),
    );
  }

  // Stat item
  Widget _buildNativeStat(String count, String label, {VoidCallback? onTap}) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(8),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text(
                count,
                style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
              ),
              const SizedBox(height: 2),
              Text(
                label,
                style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), fontWeight: FontWeight.w500),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatDivider() {
    return Container(
      width: 1,
      height: 24,
      color: const Color(0xFFE2E8F0),
    );
  }

  // ==========================================
  // TAB VIEWS
  // ==========================================

  // 1. Timeline Tab
  Widget _buildTimelineTab(List<PostModel> posts) {
    if (posts.isEmpty) {
      return _buildEmptyState(
        icon: Icons.article_outlined,
        title: 'No Posts Yet',
        subtitle: 'Posts you share will appear on your timeline.',
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(12),
      itemCount: posts.length,
      itemBuilder: (ctx, i) {
        final p = posts[i];
        return Card(
          margin: const EdgeInsets.only(bottom: 12),
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(14),
            side: const BorderSide(color: Color(0xFFE2E8F0)),
          ),
          color: Colors.white,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    CircleAvatar(
                      radius: 18,
                      backgroundColor: const Color(0xFF2563EB),
                      child: Text(
                        (p.authorName.isNotEmpty ? p.authorName[0] : 'M').toUpperCase(),
                        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                      ),
                    ),
                    const SizedBox(width: 10),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(p.authorName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                          Text(
                            p.createdAt.toString().split(' ').first,
                            style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                if (p.body != null && p.body!.isNotEmpty) ...[
                  const SizedBox(height: 10),
                  Text(p.body!, style: const TextStyle(fontSize: 13.5, height: 1.4)),
                ],
                if (p.mediaUrl != null) ...[
                  const SizedBox(height: 10),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(10),
                    child: Image.network(p.mediaUrl!, fit: BoxFit.cover),
                  ),
                ],
              ],
            ),
          ),
        );
      },
    );
  }

  // 2. About Tab
  Widget _buildAboutTab({
    required String name,
    required String userId,
    required String email,
    required String phone,
    required String? city,
    required String? country,
    required bool isVerified,
  }) {
    final locationText = [city, country].where((s) => s != null && s.toString().trim().isNotEmpty).join(', ');

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          child: Column(
            children: [
              _buildAboutTile(Icons.person_outline, 'Full Name', name),
              const Divider(height: 1, indent: 52, color: Color(0xFFF1F5F9)),
              _buildAboutTile(Icons.alternate_email, 'User ID', '@$userId'),
              const Divider(height: 1, indent: 52, color: Color(0xFFF1F5F9)),
              _buildAboutTile(Icons.email_outlined, 'Account Email', email.isNotEmpty ? email : 'Not provided'),
              const Divider(height: 1, indent: 52, color: Color(0xFFF1F5F9)),
              _buildAboutTile(Icons.phone_outlined, 'Mobile Phone', phone.isNotEmpty ? phone : 'Not provided'),
              const Divider(height: 1, indent: 52, color: Color(0xFFF1F5F9)),
              _buildAboutTile(
                Icons.location_on_outlined,
                'Location',
                locationText.isNotEmpty ? locationText : 'Not added yet',
              ),
              const Divider(height: 1, indent: 52, color: Color(0xFFF1F5F9)),
              _buildAboutTile(
                Icons.shield_outlined,
                'Verification Status',
                isVerified ? 'Verified Member' : 'Standard Member',
                trailingColor: isVerified ? const Color(0xFF16A34A) : null,
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildAboutTile(IconData icon, String title, String value, {Color? trailingColor}) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Icon(icon, size: 18, color: const Color(0xFF64748B)),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8))),
                const SizedBox(height: 2),
                Text(
                  value,
                  style: TextStyle(
                    fontSize: 13.5,
                    fontWeight: FontWeight.w600,
                    color: trailingColor ?? const Color(0xFF1E293B),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  // 3. Photos Tab
  Widget _buildPhotosTab(List<PostModel> photos) {
    if (photos.isEmpty) {
      return _buildEmptyState(
        icon: Icons.image_outlined,
        title: 'No Photos Shared',
        subtitle: 'Photos you upload in posts will show up in this gallery.',
      );
    }

    return GridView.builder(
      padding: const EdgeInsets.all(12),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 3,
        crossAxisSpacing: 8,
        mainAxisSpacing: 8,
        childAspectRatio: 1.0,
      ),
      itemCount: photos.length,
      itemBuilder: (ctx, i) {
        final p = photos[i];
        final url = p.resolvedMediaUrl ?? p.mediaUrl;
        return GestureDetector(
          onTap: () {
            if (url != null) _showImageLightbox(url, 'Photo Preview');
          },
          child: ClipRRect(
            borderRadius: BorderRadius.circular(10),
            child: Container(
              color: const Color(0xFFF1F5F9),
              child: url != null
                  ? Image.network(url, fit: BoxFit.cover)
                  : const Icon(Icons.image, color: Color(0xFF94A3B8)),
            ),
          ),
        );
      },
    );
  }

  // 4. Connections Tab
  Widget _buildConnectionsTab() {
    if (_friendsList.isEmpty) {
      return _buildEmptyState(
        icon: Icons.people_outline,
        title: 'No Connections Yet',
        subtitle: 'Find and connect with other members across the platform.',
        action: ElevatedButton.icon(
          onPressed: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const NewConnectionsScreen())),
          icon: const Icon(Icons.person_add_outlined, size: 16),
          label: const Text('Find Connections'),
          style: ElevatedButton.styleFrom(
            backgroundColor: const Color(0xFF2563EB),
            foregroundColor: Colors.white,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          ),
        ),
      );
    }

    return ListView.separated(
      padding: const EdgeInsets.all(12),
      itemCount: _friendsList.length,
      separatorBuilder: (c, i) => const Divider(height: 1, color: Color(0xFFF1F5F9)),
      itemBuilder: (ctx, i) {
        final f = _friendsList[i];
        return ListTile(
          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
          leading: CircleAvatar(
            backgroundColor: const Color(0xFF2563EB),
            child: Text(
              (f['name']?[0] ?? 'M').toUpperCase(),
              style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
            ),
          ),
          title: Text(f['name'] ?? 'Member', style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
          subtitle: Text('@${f['user_id'] ?? ''}', style: const TextStyle(color: Color(0xFF2563EB), fontSize: 12)),
          trailing: IconButton(
            icon: const Icon(Icons.chat_bubble_outline, color: Color(0xFF2563EB), size: 18),
            onPressed: () {
              Navigator.push(
                context,
                MaterialPageRoute(
                  builder: (_) => ChatConversationScreen(partnerId: f['id'] ?? 0, partnerName: f['name'] ?? 'Member'),
                ),
              );
            },
          ),
        );
      },
    );
  }

  // 5. My Referrals Tab
  Widget _buildReferralsTab() {
    final introducer = _profileData?['introducer'] as Map<String, dynamic>?;
    final directReferrals = (_profileData?['direct_referrals'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    final directReferralsCount = _profileData?['direct_referrals_count'] ?? directReferrals.length;
    final member = context.read<AuthProvider>().currentMember;
    final userRefCode = member?.userId ?? _profileData?['user_id'] ?? 'USER1234';

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // A. INTRODUCED BY CARD
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: const [
                  Icon(Icons.how_to_reg_outlined, color: Color(0xFF2563EB), size: 18),
                  SizedBox(width: 8),
                  Text(
                    'Introduced By',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A)),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              if (introducer == null)
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF8FAFC),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: const [
                      Icon(Icons.info_outline, color: Color(0xFF64748B), size: 18),
                      SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          'You registered directly without an introducer.',
                          style: TextStyle(fontSize: 12.5, color: Color(0xFF64748B)),
                        ),
                      ),
                    ],
                  ),
                )
              else
                Builder(
                  builder: (context) {
                    final introducerAvatar = AppConstants.resolveMediaUrl(
                      introducer['avatar_url']?.toString(),
                      ApiClient.baseUrl,
                    );
                    return Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Row(
                        children: [
                          CircleAvatar(
                            radius: 20,
                            backgroundColor: const Color(0xFF2563EB),
                            backgroundImage: introducerAvatar != null ? NetworkImage(introducerAvatar) : null,
                            child: introducerAvatar == null
                                ? Text(
                                    (introducer['name'] ?? 'M')[0].toUpperCase(),
                                    style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold),
                                  )
                                : null,
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
                                        introducer['name'] ?? 'Introducer',
                                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                                        overflow: TextOverflow.ellipsis,
                                      ),
                                    ),
                                    if (introducer['is_verified'] == true) ...[
                                      const SizedBox(width: 4),
                                      const Icon(Icons.verified, color: Color(0xFF16A34A), size: 14),
                                    ],
                                  ],
                                ),
                                Text(
                                  '@${introducer['user_id'] ?? ''}',
                                  style: const TextStyle(fontSize: 12, color: Color(0xFF2563EB), fontWeight: FontWeight.w600),
                                ),
                              ],
                            ),
                          ),
                          if (introducer['id'] != null)
                            IconButton(
                              icon: const Icon(Icons.chat_bubble_outline, color: Color(0xFF2563EB), size: 18),
                              onPressed: () {
                                Navigator.push(
                                  context,
                                  MaterialPageRoute(
                                    builder: (_) => ChatConversationScreen(
                                      partnerId: introducer['id'] as int,
                                      partnerName: introducer['name']?.toString() ?? 'Introducer',
                                    ),
                                  ),
                                );
                              },
                            ),
                        ],
                      ),
                    );
                  },
                ),
            ],
          ),
        ),

        const SizedBox(height: 16),

        // B. REFERRAL PROGRAM QUICK CARD (Gradient)
        Container(
          padding: const EdgeInsets.all(18),
          decoration: BoxDecoration(
            gradient: const LinearGradient(
              colors: [Color(0xFF1E3A8A), Color(0xFF2563EB)],
              begin: Alignment.topLeft,
              end: Alignment.bottomRight,
            ),
            borderRadius: BorderRadius.circular(16),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF2563EB).withOpacity(0.25),
                blurRadius: 12,
                offset: const Offset(0, 4),
              ),
            ],
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  const Text(
                    'Your Referral Link & ID',
                    style: TextStyle(color: Colors.white70, fontSize: 12.5, fontWeight: FontWeight.w500),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                    decoration: BoxDecoration(
                      color: Colors.white24,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      'Direct: $directReferralsCount',
                      style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    userRefCode,
                    style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold, letterSpacing: 1.0),
                  ),
                  IconButton(
                    icon: const Icon(Icons.copy, color: Colors.white, size: 18),
                    onPressed: () {
                      Clipboard.setData(ClipboardData(text: 'https://mlmbookai.com/register?ref=$userRefCode'));
                      AppToast.success(context, 'Referral link copied to clipboard!');
                    },
                  ),
                ],
              ),
              const SizedBox(height: 12),
              ElevatedButton.icon(
                onPressed: () {
                  showDialog(context: context, builder: (_) => const ReferralProgramDialog());
                },
                icon: const Icon(Icons.card_giftcard, size: 16),
                label: const Text('Open Referral Program Center', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.white,
                  foregroundColor: const Color(0xFF2563EB),
                  elevation: 0,
                  minimumSize: const Size(double.infinity, 40),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
            ],
          ),
        ),

        const SizedBox(height: 16),

        // C. DIRECT REFERRALS LIST
        Container(
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
            border: Border.all(color: const Color(0xFFE2E8F0)),
          ),
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                'Direct Referrals ($directReferralsCount)',
                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A)),
              ),
              const SizedBox(height: 12),
              if (directReferrals.isEmpty)
                Padding(
                  padding: const EdgeInsets.symmetric(vertical: 20),
                  child: Center(
                    child: Column(
                      children: const [
                        Icon(Icons.group_add_outlined, size: 36, color: Color(0xFF94A3B8)),
                        SizedBox(height: 8),
                        Text(
                          'No direct referrals yet.',
                          style: TextStyle(fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
                        ),
                        SizedBox(height: 4),
                        Text(
                          'Invite friends to build your direct network!',
                          style: TextStyle(fontSize: 12, color: Color(0xFF94A3B8)),
                        ),
                      ],
                    ),
                  ),
                )
              else
                ListView.separated(
                  shrinkWrap: true,
                  physics: const NeverScrollableScrollPhysics(),
                  itemCount: directReferrals.length,
                  separatorBuilder: (c, i) => const Divider(height: 14, color: Color(0xFFF1F5F9)),
                  itemBuilder: (ctx, i) {
                    final ref = directReferrals[i];
                    final refAvatar = AppConstants.resolveMediaUrl(
                      ref['avatar_url']?.toString(),
                      ApiClient.baseUrl,
                    );
                    return Row(
                      children: [
                        CircleAvatar(
                          radius: 18,
                          backgroundColor: const Color(0xFF2563EB),
                          backgroundImage: refAvatar != null ? NetworkImage(refAvatar) : null,
                          child: refAvatar == null
                              ? Text(
                                  (ref['name'] ?? 'M')[0].toUpperCase(),
                                  style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 12),
                                )
                              : null,
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
                                      ref['name'] ?? 'Member',
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                                      overflow: TextOverflow.ellipsis,
                                    ),
                                  ),
                                  if (ref['is_verified'] == true) ...[
                                    const SizedBox(width: 4),
                                    const Icon(Icons.verified, color: Color(0xFF16A34A), size: 13),
                                  ],
                                ],
                              ),
                              Text(
                                '@${ref['user_id'] ?? ''}',
                                style: const TextStyle(fontSize: 11.5, color: Color(0xFF2563EB), fontWeight: FontWeight.w600),
                              ),
                            ],
                          ),
                        ),
                        if (ref['id'] != null)
                          IconButton(
                            icon: const Icon(Icons.chat_bubble_outline, color: Color(0xFF2563EB), size: 17),
                            onPressed: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (_) => ChatConversationScreen(
                                    partnerId: ref['id'] as int,
                                    partnerName: ref['name']?.toString() ?? 'Member',
                                  ),
                                ),
                              );
                            },
                          ),
                      ],
                    );
                  },
                ),
            ],
          ),
        ),
      ],
    );
  }

  // 6. Saved Posts Tab
  Widget _buildSavedTab() {
    return _buildEmptyState(
      icon: Icons.bookmark_border,
      title: 'No Saved Posts',
      subtitle: 'Bookmarks from feeds will be organized here.',
    );
  }

  // Common Empty State
  Widget _buildEmptyState({
    required IconData icon,
    required String title,
    required String subtitle,
    Widget? action,
  }) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: const BoxDecoration(
                color: Color(0xFFF1F5F9),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, size: 36, color: const Color(0xFF94A3B8)),
            ),
            const SizedBox(height: 16),
            Text(
              title,
              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 6),
            Text(
              subtitle,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 13, color: Color(0xFF64748B)),
            ),
            if (action != null) ...[
              const SizedBox(height: 16),
              action,
            ],
          ],
        ),
      ),
    );
  }
}

class _SliverTabBarDelegate extends SliverPersistentHeaderDelegate {
  final TabBar tabBar;
  _SliverTabBarDelegate(this.tabBar);

  @override
  double get minExtent => tabBar.preferredSize.height;
  @override
  double get maxExtent => tabBar.preferredSize.height;

  @override
  Widget build(BuildContext context, double shrinkOffset, bool overlapsContent) {
    return Container(
      color: Colors.white,
      child: tabBar,
    );
  }

  @override
  bool shouldRebuild(covariant _SliverTabBarDelegate oldDelegate) {
    return false;
  }
}
