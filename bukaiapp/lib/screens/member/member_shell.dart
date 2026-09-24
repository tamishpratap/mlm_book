import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../providers/auth_provider.dart';
import '../../providers/notification_provider.dart';
import '../../widgets/member_sidebar_widget.dart';
import '../admin/admin_shell.dart';
import '../auth/login_screen.dart';
import 'account_settings_screen.dart';
import 'business_screen.dart';
import 'communities_screen.dart';
import 'dashboard_screen.dart';
import 'events_screen.dart';
import 'explore_screen.dart';
import 'feed_screen.dart';
import 'feedback_screen.dart';
import 'friends_screen.dart';
import 'messages_screen.dart';
import 'new_connections_screen.dart';
import 'notifications_screen.dart';
import 'profile_screen.dart';
import 'saved_posts_screen.dart';
import 'search_screen.dart';
import 'wallet_screen.dart';
import 'watch_screen.dart';

class MemberShell extends StatefulWidget {
  const MemberShell({super.key});

  @override
  State<MemberShell> createState() => _MemberShellState();
}

class _MemberShellState extends State<MemberShell> {
  int _currentIndex = 0;
  MemberSidebarItem _activeSidebarItem = MemberSidebarItem.home;

  late final List<Widget> _screens;

  @override
  void initState() {
    super.initState();
    _screens = [
      DashboardScreen(
        onNavigateToFeed: () => _updateIndex(1, MemberSidebarItem.socials),
        onNavigateToWatch: () => _updateIndex(2, MemberSidebarItem.watch),
        onNavigateToExplore: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const ExploreScreen())),
        onNavigateToBusiness: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const BusinessScreen(initialTab: 0))),
        onNavigateToWallet: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletScreen())),
      ),
      const FeedScreen(),
      const WatchScreen(),
      const BusinessScreen(),
      const WalletScreen(),
    ];

    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<NotificationProvider>().fetchNotifications();
    });
  }

  void _updateIndex(int index, MemberSidebarItem sidebarItem) {
    setState(() {
      _currentIndex = index;
      _activeSidebarItem = sidebarItem;
    });
  }

  void _handleSidebarSelect(MemberSidebarItem item, {bool fromDrawer = false}) {
    if (fromDrawer && Navigator.of(context).canPop()) {
      Navigator.of(context).pop();
    }
    setState(() => _activeSidebarItem = item);

    switch (item) {
      case MemberSidebarItem.home:
        setState(() => _currentIndex = 0);
        break;
      case MemberSidebarItem.socials:
        setState(() => _currentIndex = 1);
        break;
      case MemberSidebarItem.profile:
        Navigator.push(context, MaterialPageRoute(builder: (_) => const ProfileScreen()));
        break;
      case MemberSidebarItem.connections:
        Navigator.push(context, MaterialPageRoute(builder: (_) => const FriendsScreen(initialTab: 0)));
        break;
      case MemberSidebarItem.newConnections:
        Navigator.push(context, MaterialPageRoute(builder: (_) => const NewConnectionsScreen()));
        break;
      case MemberSidebarItem.savedPosts:
        Navigator.push(context, MaterialPageRoute(builder: (_) => const SavedPostsScreen()));
        break;
      case MemberSidebarItem.disconnections:
        Navigator.push(context, MaterialPageRoute(builder: (_) => const FriendsScreen(initialTab: 3)));
        break;
      case MemberSidebarItem.watch:
        setState(() => _currentIndex = 2);
        break;
      case MemberSidebarItem.community:
        Navigator.push(context, MaterialPageRoute(builder: (_) => const CommunitiesScreen()));
        break;
      case MemberSidebarItem.businessPages:
        Navigator.push(context, MaterialPageRoute(builder: (_) => const BusinessScreen(initialTab: 0)));
        break;
      case MemberSidebarItem.businessDirectory:
        Navigator.push(context, MaterialPageRoute(builder: (_) => const BusinessScreen(initialTab: 1)));
        break;
      case MemberSidebarItem.events:
        Navigator.push(context, MaterialPageRoute(builder: (_) => const EventsScreen()));
        break;
      case MemberSidebarItem.accountSettings:
        Navigator.push(context, MaterialPageRoute(builder: (_) => const AccountSettingsScreen()));
        break;
      case MemberSidebarItem.feedback:
        Navigator.push(context, MaterialPageRoute(builder: (_) => const FeedbackScreen()));
        break;
    }
  }

  void _handleOpenAdmin({bool fromDrawer = false}) {
    if (fromDrawer && Navigator.of(context).canPop()) {
      Navigator.of(context).pop();
    }
    Navigator.push(context, MaterialPageRoute(builder: (_) => const AdminShell()));
  }

  Future<void> _handleLogout({bool fromDrawer = false}) async {
    if (fromDrawer && Navigator.of(context).canPop()) {
      Navigator.of(context).pop();
    }
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => AlertDialog(
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Text('Log Out', style: TextStyle(fontWeight: FontWeight.bold)),
        content: const Text('Are you sure you want to log out of MLM Book?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx, false),
            child: const Text('Cancel', style: TextStyle(color: Color(0xFF64748B))),
          ),
          ElevatedButton(
            onPressed: () => Navigator.pop(ctx, true),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFFEF4444),
              foregroundColor: Colors.white,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            child: const Text('Log Out'),
          ),
        ],
      ),
    );

    if (confirmed == true && mounted) {
      await context.read<AuthProvider>().logout();
      if (mounted) {
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(builder: (_) => const LoginScreen()),
          (route) => false,
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final notifProvider = context.watch<NotificationProvider>();
    final member = auth.currentMember;
    final unreadCount = notifProvider.unreadCount;

    final screenWidth = MediaQuery.of(context).size.width;
    final isWideScreen = screenWidth >= 900;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        titleSpacing: isWideScreen ? 16 : 0,
        leading: isWideScreen
            ? null
            : Builder(
                builder: (ctx) => IconButton(
                  icon: const Icon(Icons.menu_rounded, color: AppColors.textPrimary, size: 22),
                  tooltip: 'Open Sidebar',
                  onPressed: () => Scaffold.of(ctx).openDrawer(),
                ),
              ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1.0),
          child: Container(color: AppColors.borderSoft, height: 1.0),
        ),
        title: Row(
          children: [
            ClipRRect(
              borderRadius: BorderRadius.circular(8),
              child: Image.asset(
                'assets/images/logo.png',
                width: 26,
                height: 26,
                fit: BoxFit.contain,
                errorBuilder: (context, error, stackTrace) => Container(
                  width: 26,
                  height: 26,
                  decoration: BoxDecoration(
                    gradient: AppColors.primaryGradient,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: const Icon(Icons.auto_stories, color: Colors.white, size: 15),
                ),
              ),
            ),
            const SizedBox(width: 8),
            const Expanded(
              child: Text(
                AppConstants.appName,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  color: AppColors.textHeading,
                  fontSize: 16,
                  fontWeight: FontWeight.bold,
                  letterSpacing: 0.2,
                ),
              ),
            ),
          ],
        ),
        actions: [
          // Search Icon
          IconButton(
            padding: const EdgeInsets.symmetric(horizontal: 2),
            constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
            visualDensity: VisualDensity.compact,
            icon: const Icon(Icons.search, color: AppColors.textPrimary, size: 20),
            tooltip: 'Global Search',
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const SearchScreen()));
            },
          ),

          // Direct Messages Icon
          IconButton(
            padding: const EdgeInsets.symmetric(horizontal: 2),
            constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
            visualDensity: VisualDensity.compact,
            icon: const Icon(Icons.chat_outlined, color: AppColors.textPrimary, size: 19),
            tooltip: 'Direct Messages',
            onPressed: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const MessagesScreen()));
            },
          ),

          // Notifications with Unread Count Badge
          Stack(
            alignment: Alignment.center,
            children: [
              IconButton(
                padding: const EdgeInsets.symmetric(horizontal: 2),
                constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
                visualDensity: VisualDensity.compact,
                icon: const Icon(Icons.notifications_none, color: AppColors.textPrimary, size: 20),
                tooltip: 'Notifications',
                onPressed: () {
                  Navigator.push(context, MaterialPageRoute(builder: (_) => const NotificationsScreen()));
                },
              ),
              if (unreadCount > 0)
                Positioned(
                  top: 2,
                  right: 2,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 1),
                    decoration: BoxDecoration(
                      color: AppColors.danger,
                      borderRadius: BorderRadius.circular(10),
                    ),
                    constraints: const BoxConstraints(minWidth: 15, minHeight: 15),
                    child: Text(
                      unreadCount > 99 ? '99+' : '$unreadCount',
                      style: const TextStyle(color: Colors.white, fontSize: 8, fontWeight: FontWeight.bold),
                      textAlign: TextAlign.center,
                    ),
                  ),
                ),
            ],
          ),

          // User Profile Avatar in Header
          Padding(
            padding: const EdgeInsets.only(right: 12, left: 2),
            child: GestureDetector(
              onTap: () {
                Navigator.push(context, MaterialPageRoute(builder: (_) => const ProfileScreen()));
              },
              child: CircleAvatar(
                radius: 14,
                backgroundColor: AppColors.primarySoft,
                child: Text(
                  member?.name.isNotEmpty == true ? member!.name[0].toUpperCase() : 'M',
                  style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.bold, fontSize: 12),
                ),
              ),
            ),
          ),
        ],
      ),

      // Mobile Slide-out Drawer (Matching 1:1 Member Sidebar)
      drawer: isWideScreen
          ? null
          : Drawer(
              backgroundColor: Colors.white,
              elevation: 16,
              child: MemberSidebarWidget(
                activeItem: _activeSidebarItem,
                onSelectItem: (item) => _handleSidebarSelect(item, fromDrawer: true),
                onOpenAdmin: () => _handleOpenAdmin(fromDrawer: true),
                onLogout: () => _handleLogout(fromDrawer: true),
                isDrawer: true,
              ),
            ),

      // Responsive Body: Pinned Sidebar on Desktop/Web, Full Content on Mobile
      body: isWideScreen
          ? Row(
              children: [
                MemberSidebarWidget(
                  activeItem: _activeSidebarItem,
                  onSelectItem: (item) => _handleSidebarSelect(item, fromDrawer: false),
                  onOpenAdmin: () => _handleOpenAdmin(fromDrawer: false),
                  onLogout: () => _handleLogout(fromDrawer: false),
                  isDrawer: false,
                ),
                Expanded(
                  child: IndexedStack(
                    index: _currentIndex,
                    children: _screens,
                  ),
                ),
              ],
            )
          : IndexedStack(
              index: _currentIndex,
              children: _screens,
            ),

      // Bottom Navigation Bar only shown on Mobile
      bottomNavigationBar: isWideScreen
          ? null
          : Container(
              decoration: const BoxDecoration(
                color: AppColors.surface,
                border: Border(top: BorderSide(color: AppColors.border, width: 0.5)),
              ),
              child: NavigationBar(
                selectedIndex: _currentIndex,
                onDestinationSelected: (idx) {
                  final sidebarItems = [
                    MemberSidebarItem.home,
                    MemberSidebarItem.socials,
                    MemberSidebarItem.watch,
                    MemberSidebarItem.businessPages,
                    MemberSidebarItem.accountSettings,
                  ];
                  _updateIndex(idx, sidebarItems[idx.clamp(0, sidebarItems.length - 1)]);
                },
                backgroundColor: AppColors.surface,
                indicatorColor: AppColors.primarySoft,
                elevation: 0,
                labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
                destinations: const [
                  NavigationDestination(
                    icon: Icon(Icons.home_outlined, color: AppColors.textMuted),
                    selectedIcon: Icon(Icons.home_rounded, color: AppColors.primary),
                    label: 'Home',
                  ),
                  NavigationDestination(
                    icon: Icon(Icons.auto_awesome_outlined, color: AppColors.textMuted),
                    selectedIcon: Icon(Icons.auto_awesome, color: AppColors.primary),
                    label: 'Socials',
                  ),
                  NavigationDestination(
                    icon: Icon(Icons.smart_display_outlined, color: AppColors.textMuted),
                    selectedIcon: Icon(Icons.smart_display_rounded, color: Colors.redAccent),
                    label: 'Watch',
                  ),
                  NavigationDestination(
                    icon: Icon(Icons.business_outlined, color: AppColors.textMuted),
                    selectedIcon: Icon(Icons.business_rounded, color: AppColors.secondary),
                    label: 'Business',
                  ),
                  NavigationDestination(
                    icon: Icon(Icons.account_balance_wallet_outlined, color: AppColors.textMuted),
                    selectedIcon: Icon(Icons.account_balance_wallet_rounded, color: AppColors.accent),
                    label: 'Wallet',
                  ),
                ],
              ),
            ),
    );
  }
}
