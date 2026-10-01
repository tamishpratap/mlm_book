import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/app_dialog.dart';
import '../../core/constants.dart';
import '../../providers/auth_provider.dart';
import '../auth/login_screen.dart';
import '../../widgets/referral_program_dialog.dart';
import 'edit_profile_screen.dart';
import 'friends_screen.dart';
import 'profile_screen.dart';
import 'saved_posts_screen.dart';
import 'security_settings_screen.dart';
import 'wallet_screen.dart';

class AccountSettingsScreen extends StatefulWidget {
  const AccountSettingsScreen({super.key});

  @override
  State<AccountSettingsScreen> createState() => _AccountSettingsScreenState();
}

class _AccountSettingsScreenState extends State<AccountSettingsScreen> {
  Future<void> _handleLogout() async {
    final confirmed = await AppDialog.confirm(
      context,
      title: 'Log Out',
      message: 'Are you sure you want to log out of your account?',
      confirmText: 'Log Out',
      isDestructive: true,
      icon: Icons.logout_rounded,
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
    final member = auth.currentMember;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text(
          'Settings',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 18, color: Color(0xFF0F172A)),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1.0),
          child: Container(color: const Color(0xFFE2E8F0), height: 1.0),
        ),
      ),
      body: ListView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        children: [
          // 1. Profile Header Card
          GestureDetector(
            onTap: () {
              Navigator.push(context, MaterialPageRoute(builder: (_) => const ProfileScreen()));
            },
            child: Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(18),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 26,
                    backgroundColor: const Color(0xFF2563EB),
                    backgroundImage: member?.avatarUrl != null ? NetworkImage(member!.avatarUrl!) : null,
                    child: member?.avatarUrl == null
                        ? Text(
                            (member?.name.isNotEmpty == true ? member!.name[0] : 'M').toUpperCase(),
                            style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                          )
                        : null,
                  ),
                  const SizedBox(width: 14),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          children: [
                            Flexible(
                              child: Text(
                                member?.name ?? 'Member',
                                style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ),
                            if (member?.isVerified == true) ...[
                              const SizedBox(width: 6),
                              const Icon(Icons.verified, color: Color(0xFF16A34A), size: 16),
                            ],
                          ],
                        ),
                        const SizedBox(height: 2),
                        Text(
                          '@${member?.userId ?? 'user'} • View Profile',
                          style: const TextStyle(fontSize: 12.5, color: Color(0xFF2563EB), fontWeight: FontWeight.w500),
                        ),
                      ],
                    ),
                  ),
                  const Icon(Icons.chevron_right, color: Color(0xFF94A3B8), size: 22),
                ],
              ),
            ),
          ),

          const SizedBox(height: 20),

          // Section 1: Account & Security
          const Padding(
            padding: EdgeInsets.only(left: 4, bottom: 8),
            child: Text(
              'ACCOUNT & SECURITY',
              style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B), letterSpacing: 0.6),
            ),
          ),
          _buildSettingsGroup([
            _buildSettingsTile(
              icon: Icons.person_outline_rounded,
              iconColor: const Color(0xFF2563EB),
              title: 'Edit Profile Information',
              subtitle: 'Name, bio, location and contact details',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const EditProfileScreen())),
            ),
            _buildSettingsTile(
              icon: Icons.shield_outlined,
              iconColor: const Color(0xFF16A34A),
              title: 'Security & Password',
              subtitle: 'Change password and account protection',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const SecuritySettingsScreen())),
            ),
            _buildSettingsTile(
              icon: Icons.account_balance_wallet_outlined,
              iconColor: const Color(0xFF9333EA),
              title: 'Web3 & Reward Wallet',
              subtitle: 'Linked BEP-20 address and deposits',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletScreen())),
              isLast: true,
            ),
          ]),

          const SizedBox(height: 20),

          // Section 2: Social & Network
          const Padding(
            padding: EdgeInsets.only(left: 4, bottom: 8),
            child: Text(
              'SOCIAL & NETWORK',
              style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B), letterSpacing: 0.6),
            ),
          ),
          _buildSettingsGroup([
            _buildSettingsTile(
              icon: Icons.people_outline_rounded,
              iconColor: const Color(0xFF0284C7),
              title: 'Connections',
              subtitle: 'View and manage your network connections',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const FriendsScreen(initialTab: 0))),
            ),
            _buildSettingsTile(
              icon: Icons.card_giftcard_rounded,
              iconColor: const Color(0xFFEA580C),
              title: 'Referral Program Center',
              subtitle: 'Your referral link and direct network stats',
              onTap: () => showDialog(context: context, builder: (_) => const ReferralProgramDialog()),
            ),
            _buildSettingsTile(
              icon: Icons.bookmark_outline_rounded,
              iconColor: const Color(0xFFD97706),
              title: 'Saved Posts',
              subtitle: 'Posts bookmarked from feeds',
              onTap: () => Navigator.push(context, MaterialPageRoute(builder: (_) => const SavedPostsScreen())),
              isLast: true,
            ),
          ]),

          const SizedBox(height: 20),

          // Section 3: App & Support
          const Padding(
            padding: EdgeInsets.only(left: 4, bottom: 8),
            child: Text(
              'APP & SUPPORT',
              style: TextStyle(fontSize: 11.5, fontWeight: FontWeight.bold, color: Color(0xFF64748B), letterSpacing: 0.6),
            ),
          ),
          _buildSettingsGroup([
            _buildSettingsTile(
              icon: Icons.info_outline_rounded,
              iconColor: const Color(0xFF64748B),
              title: 'About ${AppConstants.appName}',
              subtitle: 'Version 1.0.0 • Digital Business & Social Ecosystem',
              onTap: () {},
              isLast: true,
            ),
          ]),

          const SizedBox(height: 28),

          // Log Out Button
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFFEE2E2)),
            ),
            child: ListTile(
              onTap: _handleLogout,
              leading: Container(
                padding: const EdgeInsets.all(8),
                decoration: BoxDecoration(
                  color: const Color(0xFFFEF2F2),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: const Icon(Icons.logout_rounded, color: Color(0xFFEF4444), size: 18),
              ),
              title: const Text(
                'Log Out',
                style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFFEF4444), fontSize: 14.5),
              ),
              trailing: const Icon(Icons.chevron_right, color: Color(0xFFEF4444), size: 20),
            ),
          ),

          const SizedBox(height: 24),
        ],
      ),
    );
  }

  Widget _buildSettingsGroup(List<Widget> children) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(children: children),
    );
  }

  Widget _buildSettingsTile({
    required IconData icon,
    required Color iconColor,
    required String title,
    required String subtitle,
    required VoidCallback onTap,
    bool isLast = false,
  }) {
    return Column(
      children: [
        ListTile(
          onTap: onTap,
          contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
          leading: Container(
            padding: const EdgeInsets.all(8),
            decoration: BoxDecoration(
              color: iconColor.withOpacity(0.12),
              borderRadius: BorderRadius.circular(10),
            ),
            child: Icon(icon, color: iconColor, size: 20),
          ),
          title: Text(
            title,
            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
          ),
          subtitle: Text(
            subtitle,
            style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
          ),
          trailing: const Icon(Icons.chevron_right, color: Color(0xFF94A3B8), size: 20),
        ),
        if (!isLast) const Divider(height: 1, indent: 64, color: Color(0xFFF1F5F9)),
      ],
    );
  }
}
