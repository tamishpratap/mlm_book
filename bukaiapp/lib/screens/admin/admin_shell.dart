import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../providers/auth_provider.dart';
import '../member/member_shell.dart';
import 'admin_campaigns_screen.dart';
import 'admin_dashboard_screen.dart';
import 'admin_deposits_screen.dart';
import 'admin_members_screen.dart';

class AdminShell extends StatefulWidget {
  const AdminShell({super.key});

  @override
  State<AdminShell> createState() => _AdminShellState();
}

class _AdminShellState extends State<AdminShell> {
  int _currentIndex = 0;

  final List<Widget> _screens = const [
    AdminDashboardScreen(),
    AdminMembersScreen(),
    AdminCampaignsScreen(),
    AdminDepositsScreen(),
  ];

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: const Color(0xFF1E1B4B), // Deep Indigo for Admin
        elevation: 0,
        titleSpacing: 0,
        title: Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(6),
              decoration: BoxDecoration(
                color: const Color(0xFF7C3AED),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Icon(Icons.admin_panel_settings, color: Colors.white, size: 20),
            ),
            const SizedBox(width: 8),
            const Flexible(
              child: Text(
                'Admin Control Center',
                overflow: TextOverflow.ellipsis,
                maxLines: 1,
                style: TextStyle(color: Colors.white, fontSize: 16, fontWeight: FontWeight.bold),
              ),
            ),
          ],
        ),
        actions: [
          // Switch to Member Portal Mode
          TextButton.icon(
            onPressed: () {
              context.read<AuthProvider>().switchRole('member');
              Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const MemberShell()));
            },
            icon: const Icon(Icons.swap_horiz, color: Color(0xFFA78BFA), size: 18),
            label: const Text('Member App', style: TextStyle(color: Color(0xFFA78BFA), fontSize: 12, fontWeight: FontWeight.bold)),
          ),
          const SizedBox(width: 8),
        ],
      ),
      body: IndexedStack(
        index: _currentIndex,
        children: _screens,
      ),
      bottomNavigationBar: Container(
        decoration: const BoxDecoration(
          color: Color(0xFF1E1B4B),
          border: Border(top: BorderSide(color: Color(0xFF312E81), width: 0.5)),
        ),
        child: NavigationBar(
          selectedIndex: _currentIndex,
          onDestinationSelected: (idx) => setState(() => _currentIndex = idx),
          backgroundColor: const Color(0xFF1E1B4B),
          indicatorColor: const Color(0xFF7C3AED).withOpacity(0.3),
          elevation: 0,
          labelBehavior: NavigationDestinationLabelBehavior.alwaysShow,
          destinations: const [
            NavigationDestination(
              icon: Icon(Icons.dashboard_outlined, color: AppColors.textMuted),
              selectedIcon: Icon(Icons.dashboard, color: Color(0xFFA78BFA)),
              label: 'Overview',
            ),
            NavigationDestination(
              icon: Icon(Icons.people_alt_outlined, color: AppColors.textMuted),
              selectedIcon: Icon(Icons.people_alt, color: Color(0xFFA78BFA)),
              label: 'Members',
            ),
            NavigationDestination(
              icon: Icon(Icons.campaign_outlined, color: AppColors.textMuted),
              selectedIcon: Icon(Icons.campaign, color: Color(0xFFA78BFA)),
              label: 'Ads Review',
            ),
            NavigationDestination(
              icon: Icon(Icons.account_balance_outlined, color: AppColors.textMuted),
              selectedIcon: Icon(Icons.account_balance, color: Color(0xFFA78BFA)),
              label: 'Deposits',
            ),
          ],
        ),
      ),
    );
  }
}
