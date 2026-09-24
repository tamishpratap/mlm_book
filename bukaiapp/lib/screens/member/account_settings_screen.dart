import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import 'edit_profile_screen.dart';
import 'friends_screen.dart';
import 'saved_posts_screen.dart';
import 'security_settings_screen.dart';
import 'wallet_screen.dart';

class AccountSettingsScreen extends StatefulWidget {
  const AccountSettingsScreen({super.key});

  @override
  State<AccountSettingsScreen> createState() => _AccountSettingsScreenState();
}

class _AccountSettingsScreenState extends State<AccountSettingsScreen> {
  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final member = auth.currentMember;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text(
          'Account Settings',
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
      body: SingleChildScrollView(
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
        child: Center(
          child: Container(
            constraints: const BoxConstraints(maxWidth: 680),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // 1. Profile Information Card
                _buildCard(
                  title: 'Profile Information',
                  subtitle: 'Update your personal details, bio and public profile',
                  icon: Icons.person_outline_rounded,
                  iconColor: const Color(0xFF2563EB),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          CircleAvatar(
                            radius: 30,
                            backgroundColor: const Color(0xFF2563EB),
                            child: Text(
                              member?.name.isNotEmpty == true ? member!.name[0].toUpperCase() : 'M',
                              style: const TextStyle(color: Colors.white, fontSize: 24, fontWeight: FontWeight.bold),
                            ),
                          ),
                          const SizedBox(width: 16),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Row(
                                  children: [
                                    Text(
                                      member?.name ?? 'Member',
                                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
                                    ),
                                    if (member?.isVerified == true) ...[
                                      const SizedBox(width: 6),
                                      const Icon(Icons.verified, color: Color(0xFF2563EB), size: 16),
                                    ],
                                  ],
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  '@${member?.userId ?? 'member'} • ${member?.email ?? ''}',
                                  style: const TextStyle(fontSize: 12.5, color: Color(0xFF64748B)),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 16),
                      const Divider(color: Color(0xFFE2E8F0), height: 1),
                      const SizedBox(height: 14),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          OutlinedButton.icon(
                            onPressed: () {
                              Navigator.push(context, MaterialPageRoute(builder: (_) => const EditProfileScreen()));
                            },
                            icon: const Icon(Icons.edit_outlined, size: 16),
                            label: const Text('Edit Profile Details'),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFF2563EB),
                              side: const BorderSide(color: Color(0xFF2563EB)),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 18),

                // 2. Security & Password Card
                _buildCard(
                  title: 'Security & Password',
                  subtitle: 'Manage your password, login sessions and account protection',
                  icon: Icons.shield_outlined,
                  iconColor: const Color(0xFF16A34A),
                  child: Column(
                    children: [
                      _buildDetailRow(
                        label: 'Password Protection',
                        value: 'Secured • Last changed recently',
                        trailingIcon: Icons.lock_outline,
                      ),
                      const SizedBox(height: 14),
                      _buildDetailRow(
                        label: 'Web3 & Wallet Security',
                        value: member?.web3WalletAddress != null ? 'Linked' : 'Not linked yet',
                        trailingIcon: Icons.account_balance_wallet_outlined,
                      ),
                      const SizedBox(height: 16),
                      const Divider(color: Color(0xFFE2E8F0), height: 1),
                      const SizedBox(height: 14),
                      Row(
                        mainAxisAlignment: MainAxisAlignment.end,
                        children: [
                          OutlinedButton.icon(
                            onPressed: () {
                              Navigator.push(context, MaterialPageRoute(builder: (_) => const SecuritySettingsScreen()));
                            },
                            icon: const Icon(Icons.key_rounded, size: 16),
                            label: const Text('Change Password / Security'),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFF16A34A),
                              side: const BorderSide(color: Color(0xFF16A34A)),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 18),

                // 3. Verification Status Card
                _buildCard(
                  title: 'Account Verification',
                  subtitle: 'Complete verification to unlock higher withdrawal limits',
                  icon: Icons.badge_outlined,
                  iconColor: const Color(0xFFD97706),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: member?.isVerified == true ? const Color(0xFFDCFCE7) : const Color(0xFFFEF3C7),
                              borderRadius: BorderRadius.circular(6),
                            ),
                            child: Row(
                              children: [
                                Icon(
                                  member?.isVerified == true ? Icons.check_circle_rounded : Icons.pending_rounded,
                                  size: 14,
                                  color: member?.isVerified == true ? const Color(0xFF16A34A) : const Color(0xFFD97706),
                                ),
                                const SizedBox(width: 6),
                                Text(
                                  member?.isVerified == true ? 'Fully Verified Member' : 'Standard Member Account',
                                  style: TextStyle(
                                    fontWeight: FontWeight.bold,
                                    fontSize: 12,
                                    color: member?.isVerified == true ? const Color(0xFF16A34A) : const Color(0xFFD97706),
                                  ),
                                ),
                              ],
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                      const Text(
                        'Verified accounts enjoy zero fee deposit verification, premium blue badge recognition on feed posts and increased community reach.',
                        style: TextStyle(fontSize: 12.5, color: Color(0xFF64748B), height: 1.4),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 18),

                // 4. Quick Account Actions
                _buildCard(
                  title: 'Quick Account Preferences',
                  subtitle: 'Manage saved items, connected wallets and privacy',
                  icon: Icons.tune_rounded,
                  iconColor: const Color(0xFF7C3AED),
                  child: Column(
                    children: [
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.account_balance_wallet_outlined, color: Color(0xFF2563EB)),
                        title: const Text('Web3 & MLM Wallet', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                        subtitle: const Text('View balances, transactions & payouts', style: TextStyle(fontSize: 12)),
                        trailing: const Icon(Icons.chevron_right, color: Color(0xFF94A3B8)),
                        onTap: () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const WalletScreen()));
                        },
                      ),
                      const Divider(color: Color(0xFFF1F5F9), height: 1),
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.bookmark_outline_rounded, color: Color(0xFF0F172A)),
                        title: const Text('Saved Posts & Bookmarks', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                        subtitle: const Text('Review all posts saved from feeds', style: TextStyle(fontSize: 12)),
                        trailing: const Icon(Icons.chevron_right, color: Color(0xFF94A3B8)),
                        onTap: () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const SavedPostsScreen()));
                        },
                      ),
                      const Divider(color: Color(0xFFF1F5F9), height: 1),
                      ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: const Icon(Icons.person_off_outlined, color: Color(0xFFEF4444)),
                        title: const Text('Disconnections & Blocked Users', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 14)),
                        subtitle: const Text('Manage blocked member accounts', style: TextStyle(fontSize: 12)),
                        trailing: const Icon(Icons.chevron_right, color: Color(0xFF94A3B8)),
                        onTap: () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const FriendsScreen(initialTab: 3)));
                        },
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  static Widget _buildCard({
    required String title,
    required String subtitle,
    required IconData icon,
    required Color iconColor,
    required Widget child,
  }) {
    return Container(
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                width: 36,
                height: 36,
                decoration: BoxDecoration(
                  color: iconColor.withOpacity(0.1),
                  borderRadius: BorderRadius.circular(10),
                ),
                child: Icon(icon, color: iconColor, size: 20),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      title,
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: Color(0xFF0F172A)),
                    ),
                    const SizedBox(height: 2),
                    Text(
                      subtitle,
                      style: const TextStyle(fontSize: 12, color: Color(0xFF64748B)),
                    ),
                  ],
                ),
              ),
            ],
          ),
          const SizedBox(height: 18),
          child,
        ],
      ),
    );
  }

  static Widget _buildDetailRow({
    required String label,
    required String value,
    required IconData trailingIcon,
  }) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            Icon(trailingIcon, size: 16, color: const Color(0xFF64748B)),
            const SizedBox(width: 8),
            Text(label, style: const TextStyle(fontSize: 13, color: Color(0xFF1E293B), fontWeight: FontWeight.w500)),
          ],
        ),
        Text(value, style: const TextStyle(fontSize: 12.5, color: Color(0xFF64748B))),
      ],
    );
  }
}
