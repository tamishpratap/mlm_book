import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:url_launcher/url_launcher.dart';
import '../../core/constants.dart';
import '../../core/app_toast.dart';
import '../../providers/auth_provider.dart';
import '../../providers/wallet_provider.dart';
import '../../widgets/referral_program_dialog.dart';
import 'business_screen.dart';
import 'communities_screen.dart';
import 'feed_screen.dart';
import 'friends_screen.dart';
import 'wallet_screen.dart';

class DashboardScreen extends StatefulWidget {
  final VoidCallback? onNavigateToFeed;
  final VoidCallback? onNavigateToWatch;
  final VoidCallback? onNavigateToExplore;
  final VoidCallback? onNavigateToBusiness;
  final VoidCallback? onNavigateToWallet;

  const DashboardScreen({
    super.key,
    this.onNavigateToFeed,
    this.onNavigateToWatch,
    this.onNavigateToExplore,
    this.onNavigateToBusiness,
    this.onNavigateToWallet,
  });

  @override
  State<DashboardScreen> createState() => _DashboardScreenState();
}

class _DashboardScreenState extends State<DashboardScreen> {
  final PageController _videoPageCtrl = PageController(viewportFraction: 0.92);
  int _activeVideoIndex = 0;

  final List<Map<String, dynamic>> _videoPlaylist = [
    {
      'id': 'overview',
      'title': 'About MLM Book & Vision',
      'category': 'Introduction',
      'duration': '3:45',
      'videoId': 'L_LUpnjgPso',
      'badgeColor': const Color(0xFF2563EB),
      'badgeBg': const Color(0xFFEFF6FF),
      'description':
          'Learn what makes MLM Book the world\'s dedicated social and business ecosystem. Discover how we empower individuals, creators, and entrepreneurs to connect, collaborate, and scale.',
      'highlights': [
        'Bridge authentic personal networking with sustainable business growth.',
        'Connect with verified leaders, creators, and professionals globally.',
        'Completely transparent, privacy-first digital architecture.',
      ],
      'actionText': 'Explore Social Feed',
      'actionTarget': 'socials',
    },
    {
      'id': 'socials',
      'title': 'Social Stream & Stories',
      'category': 'Socials',
      'duration': '2:30',
      'videoId': 'dQw4w9WgXcQ',
      'badgeColor': const Color(0xFF4F46E5),
      'badgeBg': const Color(0xFFEEF2FF),
      'description':
          'Experience our fast, interactive social stream. Publish rich posts with images, videos, emotions, and attachments. Share 24-hour stories and engage with modern emoji reactions.',
      'highlights': [
        'Rich text editor with media attachments and location tagging.',
        'Interactive 24-hour stories with instant reactions.',
        'Granular privacy controls: Public, Friends-only, or Custom.',
      ],
      'actionText': 'Go to Social Feed',
      'actionTarget': 'socials',
    },
    {
      'id': 'rewards',
      'title': 'How to Earn Rewards',
      'category': 'Rewards',
      'duration': '3:20',
      'videoId': 'jNQXAC9IVRw',
      'badgeColor': const Color(0xFF059669),
      'badgeBg': const Color(0xFFECFDF5),
      'description':
          'Learn how eligible verified members earn USDT rewards by engaging with promoted posts, showing interest in campaigns, and managing their BEP-20 reward wallet.',
      'highlights': [
        'Earn real rewards by showing interest in verified business campaigns.',
        'Transparent calculation with real-time credited wallet ledger.',
        'Instant payout requests to your verified BEP-20 USDT wallet address.',
      ],
      'actionText': 'View Reward Wallet',
      'actionTarget': 'wallet',
    },
    {
      'id': 'business',
      'title': 'Pages & Advertising',
      'category': 'Business',
      'duration': '3:10',
      'videoId': 'M7lc1UVf-VE',
      'badgeColor': const Color(0xFF7C3AED),
      'badgeBg': const Color(0xFFF5F3FF),
      'description':
          'Establish your brand presence. Create verified business pages, invite team members with specific roles (Admin, Editor, Moderator), and launch advertising campaigns to reach real members.',
      'highlights': [
        'Showcase your company profile in the Global Business Directory.',
        'Manage multi-user team permissions with granular roles.',
        'Dedicated business messenger inbox and lead management.',
      ],
      'actionText': 'Manage Business Pages',
      'actionTarget': 'business',
    },
  ];

  final List<Map<String, dynamic>> _socialLinks = [
    {
      'name': 'YouTube',
      'url': 'https://www.youtube.com/@MLMBookOfficail',
      'color': const Color(0xFFFF0000),
      'icon': Icons.smart_display_rounded,
    },
    {
      'name': 'Instagram',
      'url': 'https://www.instagram.com/mlmbook29/',
      'color': const Color(0xFFE4405F),
      'icon': Icons.camera_alt_rounded,
    },
    {
      'name': 'Facebook',
      'url': 'https://www.facebook.com/profile.php?id=61593794263511',
      'color': const Color(0xFF1877F2),
      'icon': Icons.facebook_rounded,
    },
    {
      'name': 'LinkedIn',
      'url': 'https://linkedin.com/in/mlm-book-248045423',
      'color': const Color(0xFF0A66C2),
      'icon': Icons.business_center_rounded,
    },
  ];

  @override
  void dispose() {
    _videoPageCtrl.dispose();
    super.dispose();
  }

  Future<void> _launchExternalUrl(String url) async {
    final uri = Uri.parse(url);
    try {
      if (await canLaunchUrl(uri)) {
        await launchUrl(uri, mode: LaunchMode.externalApplication);
      }
    } catch (e) {
      debugPrint('Could not launch URL $url: $e');
    }
  }

  void _handleTargetNavigation(String target) {
    switch (target) {
      case 'socials':
        if (widget.onNavigateToFeed != null) {
          widget.onNavigateToFeed!();
        } else {
          Navigator.push(context, MaterialPageRoute(builder: (_) => const FeedScreen()));
        }
        break;
      case 'communities':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const CommunitiesScreen()));
        break;
      case 'business':
        if (widget.onNavigateToBusiness != null) {
          widget.onNavigateToBusiness!();
        } else {
          Navigator.push(context, MaterialPageRoute(builder: (_) => const BusinessScreen(initialTab: 0)));
        }
        break;
      case 'directory':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const BusinessScreen(initialTab: 1)));
        break;
      case 'connections':
        Navigator.push(context, MaterialPageRoute(builder: (_) => const FriendsScreen(initialTab: 0)));
        break;
      case 'wallet':
        if (widget.onNavigateToWallet != null) {
          widget.onNavigateToWallet!();
        } else {
          Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletScreen()));
        }
        break;
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final wallet = context.watch<WalletProvider>();
    final member = auth.currentMember;

    final String referralCode = member?.userId ?? '';
    final String referralLink = 'https://mlmbookai.com/member/register?ref=$referralCode';

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text(
          'Home',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A)),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: const Color(0xFFE2E8F0), height: 1),
        ),
      ),
      body: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: () async {
          await wallet.fetchWallet();
        },
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // -------------------------------------------------------------
              // 1. Hero Introduction Card (Exact 1:1 HomeHero.jsx match)
              // -------------------------------------------------------------
              _buildHeroSection(member),

              const SizedBox(height: 18),

              // -------------------------------------------------------------
              // 2. Interactive Video Learning Center (Carousel for Videos)
              // -------------------------------------------------------------
              _buildVideoHubSection(),

              const SizedBox(height: 18),

              // -------------------------------------------------------------
              // 3. Core Platform Capabilities (Grid / Feature Modules)
              // -------------------------------------------------------------
              _buildPlatformCapabilitiesSection(),

              const SizedBox(height: 18),

              // -------------------------------------------------------------
              // 4. Action & Community Next Steps CTA Banner
              // -------------------------------------------------------------
              _buildCtaBannerSection(),

              const SizedBox(height: 18),

              // -------------------------------------------------------------
              // 5. Quick Referral Link Card
              // -------------------------------------------------------------
              _buildReferralCard(referralCode, referralLink),

              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  // =========================================================================
  // 1. HERO SECTION WIDGET
  // =========================================================================
  Widget _buildHeroSection(dynamic member) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1E293B).withOpacity(0.04),
            blurRadius: 14,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Top Floating Badge
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
            decoration: BoxDecoration(
              color: const Color(0xFFEFF6FF),
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: const Color(0xFFDBEAFE)),
            ),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                const Icon(Icons.auto_awesome, size: 14, color: Color(0xFF2563EB)),
                const SizedBox(width: 6),
                const Flexible(
                  child: Text(
                    'Digital Social & Business Ecosystem',
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: TextStyle(
                      fontSize: 11.5,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF1E40AF),
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 14),

          // Main Headline
          RichText(
            text: const TextSpan(
              style: TextStyle(
                fontSize: 22,
                fontWeight: FontWeight.w800,
                color: Color(0xFF0F172A),
                height: 1.25,
                letterSpacing: -0.5,
              ),
              children: [
                TextSpan(text: 'Connect. Create. Grow. \n'),
                TextSpan(
                  text: 'Earn Rewards.',
                  style: TextStyle(
                    color: Color(0xFF2563EB),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 10),

          // Subtitle Paragraph
          const Text(
            'MLM Book is a unified social and digital business ecosystem where people connect, communities grow, businesses promote their brands, creators share content, and eligible members earn rewards through qualifying campaign interactions.',
            style: TextStyle(
              fontSize: 13,
              height: 1.45,
              color: Color(0xFF475569),
            ),
          ),
          const SizedBox(height: 18),

          // Primary & Secondary Redirection Buttons
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              ElevatedButton.icon(
                onPressed: () => _handleTargetNavigation('socials'),
                icon: const Icon(Icons.rss_feed_rounded, size: 17, color: Colors.white),
                label: const Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Text('Explore Social Feed', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    SizedBox(width: 6),
                    Icon(Icons.arrow_forward_rounded, size: 14, color: Colors.white70),
                  ],
                ),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF2563EB),
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  elevation: 0,
                ),
              ),
              OutlinedButton.icon(
                onPressed: () => _handleTargetNavigation('communities'),
                icon: const Icon(Icons.groups_rounded, size: 17, color: Color(0xFF334155)),
                label: const Text('Communities', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFF334155),
                  side: const BorderSide(color: Color(0xFFCBD5E1)),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              OutlinedButton.icon(
                onPressed: () => _handleTargetNavigation('business'),
                icon: const Icon(Icons.storefront_rounded, size: 17, color: Color(0xFF334155)),
                label: const Text('Businesses', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13)),
                style: OutlinedButton.styleFrom(
                  foregroundColor: const Color(0xFF334155),
                  side: const BorderSide(color: Color(0xFFCBD5E1)),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
            ],
          ),

          const SizedBox(height: 18),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          const SizedBox(height: 14),

          // Official Social Channels Row
          Row(
            children: [
              const Text(
                'Follow Us:',
                style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600, color: Color(0xFF64748B)),
              ),
              const SizedBox(width: 10),
              ..._socialLinks.map((social) {
                return Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: InkWell(
                    onTap: () => _launchExternalUrl(social['url'] as String),
                    borderRadius: BorderRadius.circular(8),
                    child: Container(
                      width: 32,
                      height: 32,
                      decoration: BoxDecoration(
                        color: (social['color'] as Color).withOpacity(0.1),
                        borderRadius: BorderRadius.circular(8),
                        border: Border.all(color: (social['color'] as Color).withOpacity(0.2)),
                      ),
                      child: Icon(
                        social['icon'] as IconData,
                        size: 16,
                        color: social['color'] as Color,
                      ),
                    ),
                  ),
                );
              }),
            ],
          ),
        ],
      ),
    );
  }

  // =========================================================================
  // 2. INTERACTIVE VIDEO LEARNING CENTER (CAROUSEL)
  // =========================================================================
  Widget _buildVideoHubSection() {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      padding: const EdgeInsets.symmetric(vertical: 18),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Section Header
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 18),
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(8),
                  decoration: BoxDecoration(
                    color: const Color(0xFFEFF6FF),
                    borderRadius: BorderRadius.circular(10),
                  ),
                  child: const Icon(Icons.ondemand_video_rounded, color: Color(0xFF2563EB), size: 20),
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        'Interactive Video Learning Center',
                        style: TextStyle(
                          fontWeight: FontWeight.w800,
                          fontSize: 15.5,
                          color: Color(0xFF0F172A),
                        ),
                      ),
                      Text(
                        'Learn how to maximize your network & earn rewards',
                        style: TextStyle(
                          fontSize: 12,
                          color: Color(0xFF64748B),
                        ),
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),

          // Video Carousel (PageView)
          SizedBox(
            height: 380,
            child: PageView.builder(
              controller: _videoPageCtrl,
              itemCount: _videoPlaylist.length,
              onPageChanged: (idx) => setState(() => _activeVideoIndex = idx),
              itemBuilder: (ctx, index) {
                final item = _videoPlaylist[index];
                return _buildVideoCard(item);
              },
            ),
          ),

          const SizedBox(height: 12),

          // Carousel Dot Indicators
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(_videoPlaylist.length, (idx) {
              final isSelected = _activeVideoIndex == idx;
              return AnimatedContainer(
                duration: const Duration(milliseconds: 200),
                margin: const EdgeInsets.symmetric(horizontal: 3.5),
                height: 6,
                width: isSelected ? 20 : 6,
                decoration: BoxDecoration(
                  color: isSelected ? const Color(0xFF2563EB) : const Color(0xFFCBD5E1),
                  borderRadius: BorderRadius.circular(3),
                ),
              );
            }),
          ),
        ],
      ),
    );
  }

  Widget _buildVideoCard(Map<String, dynamic> item) {
    final videoId = item['videoId'] as String;
    final category = item['category'] as String;
    final duration = item['duration'] as String;
    final title = item['title'] as String;
    final description = item['description'] as String;
    final highlights = (item['highlights'] as List).cast<String>();
    final actionText = item['actionText'] as String;
    final actionTarget = item['actionTarget'] as String;
    final badgeColor = item['badgeColor'] as Color;
    final badgeBg = item['badgeBg'] as Color;

    final videoUrl = 'https://www.youtube.com/watch?v=$videoId';

    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 6, vertical: 4),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // Video Thumbnail Header with Play Button
            GestureDetector(
              onTap: () => _launchExternalUrl(videoUrl),
              child: Stack(
                children: [
                  Container(
                    height: 145,
                    width: double.infinity,
                    decoration: BoxDecoration(
                      gradient: LinearGradient(
                        colors: [
                          const Color(0xFF0F172A),
                          badgeColor.withOpacity(0.75),
                        ],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                    ),
                    child: Center(
                      child: Container(
                        width: 48,
                        height: 48,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          shape: BoxShape.circle,
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.3),
                              blurRadius: 10,
                              offset: const Offset(0, 4),
                            ),
                          ],
                        ),
                        child: Icon(Icons.play_arrow_rounded, color: badgeColor, size: 30),
                      ),
                    ),
                  ),

                  // Category & Duration Badges
                  Positioned(
                    top: 10,
                    left: 10,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: badgeBg,
                        borderRadius: BorderRadius.circular(6),
                        border: Border.all(color: badgeColor.withOpacity(0.3)),
                      ),
                      child: Text(
                        category,
                        style: TextStyle(color: badgeColor, fontWeight: FontWeight.bold, fontSize: 10.5),
                      ),
                    ),
                  ),
                  Positioned(
                    bottom: 10,
                    right: 10,
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 3),
                      decoration: BoxDecoration(
                        color: Colors.black.withOpacity(0.75),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          const Icon(Icons.access_time_rounded, color: Colors.white, size: 11),
                          const SizedBox(width: 4),
                          Text(
                            duration,
                            style: const TextStyle(color: Colors.white, fontSize: 10.5, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Video Details & Highlights
            Expanded(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(14, 10, 14, 10),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        fontWeight: FontWeight.bold,
                        fontSize: 14.5,
                        color: Color(0xFF0F172A),
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      description,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B), height: 1.3),
                    ),
                    const SizedBox(height: 8),

                    // Highlights
                    ...highlights.take(2).map((hl) => Padding(
                          padding: const EdgeInsets.only(bottom: 4),
                          child: Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Icon(Icons.check_circle_rounded, size: 13, color: badgeColor),
                              const SizedBox(width: 6),
                              Expanded(
                                child: Text(
                                  hl,
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                  style: const TextStyle(fontSize: 11.5, color: Color(0xFF334155), fontWeight: FontWeight.w500),
                                ),
                              ),
                            ],
                          ),
                        )),

                    const Spacer(),

                    // Redirection Action Button
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton.icon(
                        onPressed: () => _handleTargetNavigation(actionTarget),
                        icon: const Icon(Icons.arrow_forward_rounded, size: 14),
                        label: Text(actionText, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
                        style: OutlinedButton.styleFrom(
                          foregroundColor: badgeColor,
                          side: BorderSide(color: badgeColor.withOpacity(0.4)),
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        ),
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // =========================================================================
  // 3. CORE PLATFORM CAPABILITIES SECTION
  // =========================================================================
  Widget _buildPlatformCapabilitiesSection() {
    final List<Map<String, dynamic>> features = [
      {
        'title': 'Socials',
        'tag': 'Social Feed',
        'desc': 'Post updates, attach photos, react, comment and connect with your network.',
        'icon': Icons.rss_feed_rounded,
        'color': const Color(0xFF2563EB),
        'bg': const Color(0xFFEFF6FF),
        'target': 'socials',
        'btnText': 'Open Socials',
      },
      {
        'title': 'Communities',
        'tag': 'Groups',
        'desc': 'Build or join focused communities and participate in interactive discussions.',
        'icon': Icons.groups_rounded,
        'color': const Color(0xFF4F46E5),
        'bg': const Color(0xFFEEF2FF),
        'target': 'communities',
        'btnText': 'Browse Groups',
      },
      {
        'title': 'Business Pages',
        'tag': 'Brands',
        'desc': 'Create and manage a professional verified Business Page for your brand.',
        'icon': Icons.storefront_rounded,
        'color': const Color(0xFF7C3AED),
        'bg': const Color(0xFFF5F3FF),
        'target': 'business',
        'btnText': 'Manage Pages',
      },
      {
        'title': 'Business Directory',
        'tag': 'Discovery',
        'desc': 'Discover global verified businesses and make your brand easier to find.',
        'icon': Icons.explore_rounded,
        'color': const Color(0xFFD97706),
        'bg': const Color(0xFFFFFBEB),
        'target': 'directory',
        'btnText': 'View Directory',
      },
      {
        'title': 'Connections',
        'tag': 'Network',
        'desc': 'Build your personal and professional digital connections seamlessly.',
        'icon': Icons.person_add_alt_1_rounded,
        'color': const Color(0xFF059669),
        'bg': const Color(0xFFECFDF5),
        'target': 'connections',
        'btnText': 'Connections',
      },
    ];

    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Section Heading
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFEFF6FF),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.grid_view_rounded, color: Color(0xFF2563EB), size: 18),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Core Platform Capabilities',
                      style: TextStyle(fontWeight: FontWeight.w800, fontSize: 15.5, color: Color(0xFF0F172A)),
                    ),
                    Text(
                      'Explore essential features designed for members & brands',
                      style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),

          // Features List Cards
          ...features.map((feat) {
            final color = feat['color'] as Color;
            final bg = feat['bg'] as Color;
            return Container(
              margin: const EdgeInsets.only(bottom: 12),
              padding: const EdgeInsets.all(14),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: bg,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: color.withOpacity(0.3)),
                    ),
                    child: Icon(feat['icon'] as IconData, color: color, size: 20),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Text(
                              feat['title'] as String,
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5, color: Color(0xFF0F172A)),
                            ),
                            const SizedBox(width: 6),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 1.5),
                              decoration: BoxDecoration(
                                color: bg,
                                borderRadius: BorderRadius.circular(4),
                              ),
                              child: Text(
                                feat['tag'] as String,
                                style: TextStyle(fontSize: 10, color: color, fontWeight: FontWeight.bold),
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 3),
                        Text(
                          feat['desc'] as String,
                          style: const TextStyle(fontSize: 12, color: Color(0xFF64748B), height: 1.3),
                        ),
                        const SizedBox(height: 8),
                        InkWell(
                          onTap: () => _handleTargetNavigation(feat['target'] as String),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Text(
                                feat['btnText'] as String,
                                style: TextStyle(color: color, fontWeight: FontWeight.bold, fontSize: 12),
                              ),
                              const SizedBox(width: 4),
                              Icon(Icons.arrow_forward_rounded, color: color, size: 13),
                            ],
                          ),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            );
          }),
        ],
      ),
    );
  }

  // =========================================================================
  // 4. ACTION CTA BANNER SECTION
  // =========================================================================
  Widget _buildCtaBannerSection() {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF1E3A8A), Color(0xFF1E40AF), Color(0xFF3B82F6)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF2563EB).withOpacity(0.35),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
            decoration: BoxDecoration(
              color: Colors.white.withOpacity(0.15),
              borderRadius: BorderRadius.circular(16),
            ),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.rocket_launch_rounded, color: Colors.white, size: 13),
                SizedBox(width: 5),
                Text(
                  'Start Your Digital Journey Today',
                  style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w700),
                ),
              ],
            ),
          ),
          const SizedBox(height: 12),
          const Text(
            'Ready to Build Your Network and Grow Your Digital Presence?',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.w800,
              color: Colors.white,
              height: 1.25,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            'Join MLM Book, connect with your community, build your brand and explore the entire platform.',
            style: TextStyle(
              fontSize: 12.5,
              color: Colors.white.withOpacity(0.9),
              height: 1.4,
            ),
          ),
          const SizedBox(height: 16),
          Wrap(
            spacing: 10,
            runSpacing: 10,
            children: [
              ElevatedButton.icon(
                onPressed: () => _handleTargetNavigation('socials'),
                icon: const Icon(Icons.rss_feed_rounded, size: 16, color: Color(0xFF1E40AF)),
                label: const Text('Explore Socials', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.white,
                  foregroundColor: const Color(0xFF1E40AF),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  elevation: 0,
                ),
              ),
              OutlinedButton.icon(
                onPressed: () => _handleTargetNavigation('business'),
                icon: const Icon(Icons.storefront_rounded, size: 16, color: Colors.white),
                label: const Text('Create Business Page', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5)),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.white,
                  side: const BorderSide(color: Colors.white54),
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  // =========================================================================
  // 5. REFERRAL CARD
  // =========================================================================
  Widget _buildReferralCard(String referralCode, String referralLink) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFEFF6FF),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.card_giftcard_rounded, color: Color(0xFF2563EB), size: 18),
              ),
              const SizedBox(width: 10),
              const Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      'Your Referral Code',
                      style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                    ),
                    Text(
                      'Share your invite link to expand your network',
                      style: TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                    ),
                  ],
                ),
              ),
              TextButton(
                onPressed: () {
                  showDialog(
                    context: context,
                    builder: (_) => const ReferralProgramDialog(),
                  );
                },
                child: const Text('Details', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12)),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFCBD5E1)),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    referralLink,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 12, color: Color(0xFF334155), fontFamily: 'monospace'),
                  ),
                ),
                const SizedBox(width: 8),
                InkWell(
                  onTap: () {
                    Clipboard.setData(ClipboardData(text: referralLink));
                    AppToast.success(context, 'Referral link copied to clipboard!');
                  },
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: const Color(0xFF2563EB),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: const Text(
                      'Copy',
                      style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 11),
                    ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
