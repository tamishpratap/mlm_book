import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../providers/auth_provider.dart';
import '../../providers/wallet_provider.dart';

class DashboardScreen extends StatelessWidget {
  final VoidCallback onNavigateToFeed;
  final VoidCallback onNavigateToWatch;
  final VoidCallback onNavigateToExplore;
  final VoidCallback onNavigateToBusiness;
  final VoidCallback onNavigateToWallet;

  const DashboardScreen({
    super.key,
    required this.onNavigateToFeed,
    required this.onNavigateToWatch,
    required this.onNavigateToExplore,
    required this.onNavigateToBusiness,
    required this.onNavigateToWallet,
  });

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final wallet = context.watch<WalletProvider>();
    final member = auth.currentMember;

    final String referralCode = member?.userId ?? '';
    final String referralLink = 'https://mlmbookai.com/member/register?ref=$referralCode';

    return Scaffold(
      backgroundColor: AppColors.background,
      body: RefreshIndicator(
        color: AppColors.primary,
        onRefresh: () async {
          await wallet.fetchWallet();
        },
        child: SingleChildScrollView(
          physics: const AlwaysScrollableScrollPhysics(),
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              // Hero Welcome Banner
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  gradient: AppColors.primaryGradient,
                  borderRadius: BorderRadius.circular(20),
                  boxShadow: [
                    BoxShadow(
                      color: AppColors.primary.withOpacity(0.3),
                      blurRadius: 16,
                      offset: const Offset(0, 6),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        CircleAvatar(
                          radius: 24,
                          backgroundColor: Colors.white.withOpacity(0.2),
                          child: Text(
                            member?.name.isNotEmpty == true ? member!.name[0].toUpperCase() : 'M',
                            style: const TextStyle(color: Colors.white, fontSize: 20, fontWeight: FontWeight.bold),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              Text(
                                'Welcome back,',
                                style: TextStyle(color: Colors.white.withOpacity(0.85), fontSize: 13),
                              ),
                              Text(
                                member?.name ?? 'Member',
                                style: const TextStyle(color: Colors.white, fontSize: 18, fontWeight: FontWeight.bold),
                                overflow: TextOverflow.ellipsis,
                              ),
                            ],
                          ),
                        ),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                          decoration: BoxDecoration(
                            color: Colors.white.withOpacity(0.2),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            member?.userId != null ? '#${member!.userId}' : 'ID: --',
                            style: const TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.w600),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                    Text(
                      'Your Decentralized Social & MLM Growth Hub',
                      style: TextStyle(color: Colors.white.withOpacity(0.95), fontSize: 14, fontWeight: FontWeight.w500),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 16),

              // Referral Link Share Card
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border),
                  boxShadow: [
                    BoxShadow(
                      color: Colors.black.withOpacity(0.03),
                      blurRadius: 10,
                      offset: const Offset(0, 4),
                    ),
                  ],
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        const Icon(Icons.share_rounded, color: AppColors.secondary, size: 18),
                        const SizedBox(width: 6),
                        const Expanded(
                          child: Text(
                            'Your Referral Link',
                            style: TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textHeading),
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                          decoration: BoxDecoration(
                            color: AppColors.primarySoft,
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            referralCode,
                            style: const TextStyle(color: AppColors.primary, fontSize: 11, fontWeight: FontWeight.bold),
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 10),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      decoration: BoxDecoration(
                        color: AppColors.background,
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: AppColors.border),
                      ),
                      child: Row(
                        children: [
                          Expanded(
                            child: Text(
                              referralLink,
                              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                          const SizedBox(width: 8),
                          InkWell(
                            onTap: () {
                              Clipboard.setData(ClipboardData(text: referralLink));
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(
                                  content: Text('Referral link copied to clipboard!'),
                                  backgroundColor: AppColors.success,
                                ),
                              );
                            },
                            child: const Padding(
                              padding: EdgeInsets.all(4.0),
                              child: Icon(Icons.copy_rounded, color: AppColors.primary, size: 18),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(height: 16),

              // KPI Summary Cards
              Row(
                children: [
                  Expanded(
                    child: _buildKpiCard(
                      title: 'Reward Wallet',
                      value: '${AppConstants.currencySymbol}${(wallet.wallet?.rewardBalance ?? member?.rewardBalance ?? 0.0).toStringAsFixed(2)}',
                      icon: Icons.account_balance_wallet_rounded,
                      iconColor: AppColors.accent,
                      bgColor: const Color(0xFFE8F9EE),
                      onTap: onNavigateToWallet,
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: _buildKpiCard(
                      title: 'Current Tier',
                      value: wallet.wallet?.currentTierLabel ?? 'Standard',
                      icon: Icons.military_tech_rounded,
                      iconColor: AppColors.secondary,
                      bgColor: const Color(0xFFF3EDFD),
                      onTap: onNavigateToWallet,
                    ),
                  ),
                ],
              ),

              const SizedBox(height: 20),

              // Quick Actions Section
              const Text(
                'Quick Actions',
                style: TextStyle(fontSize: 17, fontWeight: FontWeight.bold, color: AppColors.textHeading),
              ),
              const SizedBox(height: 12),

              GridView.count(
                shrinkWrap: true,
                physics: const NeverScrollableScrollPhysics(),
                crossAxisCount: 3,
                mainAxisSpacing: 12,
                crossAxisSpacing: 12,
                childAspectRatio: 0.95,
                children: [
                  _buildActionTile(
                    title: 'Socials Feed',
                    icon: Icons.dynamic_feed_rounded,
                    color: AppColors.primary,
                    onTap: onNavigateToFeed,
                  ),
                  _buildActionTile(
                    title: 'Watch Videos',
                    icon: Icons.smart_display_rounded,
                    color: Colors.redAccent,
                    onTap: onNavigateToWatch,
                  ),
                  _buildActionTile(
                    title: 'Communities',
                    icon: Icons.groups_rounded,
                    color: AppColors.secondary,
                    onTap: onNavigateToExplore,
                  ),
                  _buildActionTile(
                    title: 'Business Ads',
                    icon: Icons.campaign_rounded,
                    color: Colors.orange,
                    onTap: onNavigateToBusiness,
                  ),
                  _buildActionTile(
                    title: 'Deposit Funds',
                    icon: Icons.add_card_rounded,
                    color: AppColors.accent,
                    onTap: onNavigateToWallet,
                  ),
                  _buildActionTile(
                    title: 'Web3 Wallet',
                    icon: Icons.account_balance_wallet_rounded,
                    color: const Color(0xFF0284C7),
                    onTap: onNavigateToWallet,
                  ),
                ],
              ),

              const SizedBox(height: 20),

              // MLM Book Workflow Steps Card
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: AppColors.surface,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'How MLM Book Works',
                      style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textHeading),
                    ),
                    const SizedBox(height: 12),
                    _buildStepRow('1', 'Connect & Engage', 'Share posts, stories, and videos with your network.'),
                    const Divider(height: 16, color: AppColors.borderSoft),
                    _buildStepRow('2', 'Grow Network', 'Invite friends using your referral link to earn tier bonuses.'),
                    const Divider(height: 16, color: AppColors.borderSoft),
                    _buildStepRow('3', 'View Ads & Earn', 'Interact with sponsored advertiser campaigns to claim rewards.'),
                    const Divider(height: 16, color: AppColors.borderSoft),
                    _buildStepRow('4', 'Web3 Payouts', 'Link your BSC USDT BEP-20 address for secure crypto withdrawals.'),
                  ],
                ),
              ),

              const SizedBox(height: 24),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildKpiCard({
    required String title,
    required String value,
    required IconData icon,
    required Color iconColor,
    required Color bgColor,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        padding: const EdgeInsets.all(14),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: AppColors.border),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.02),
              blurRadius: 8,
              offset: const Offset(0, 3),
            ),
          ],
        ),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Container(
              padding: const EdgeInsets.all(8),
              decoration: BoxDecoration(
                color: bgColor,
                borderRadius: BorderRadius.circular(10),
              ),
              child: Icon(icon, color: iconColor, size: 20),
            ),
            const SizedBox(height: 10),
            Text(
              title,
              style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
            ),
            const SizedBox(height: 4),
            Text(
              value,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textHeading),
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildActionTile({
    required String title,
    required IconData icon,
    required Color color,
    required VoidCallback onTap,
  }) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12, horizontal: 8),
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.border),
        ),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(10),
              decoration: BoxDecoration(
                color: color.withOpacity(0.12),
                shape: BoxShape.circle,
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(height: 8),
            Text(
              title,
              textAlign: TextAlign.center,
              style: const TextStyle(fontSize: 11, fontWeight: FontWeight.w600, color: AppColors.textHeading),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildStepRow(String number, String title, String desc) {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          width: 22,
          height: 22,
          decoration: const BoxDecoration(
            color: AppColors.primarySoft,
            shape: BoxShape.circle,
          ),
          alignment: Alignment.center,
          child: Text(
            number,
            style: const TextStyle(color: AppColors.primary, fontSize: 11, fontWeight: FontWeight.bold),
          ),
        ),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                title,
                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.textHeading),
              ),
              const SizedBox(height: 2),
              Text(
                desc,
                style: const TextStyle(fontSize: 11, color: AppColors.textSecondary),
              ),
            ],
          ),
        ),
      ],
    );
  }
}
