import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../providers/admin_provider.dart';

class AdminDashboardScreen extends StatefulWidget {
  const AdminDashboardScreen({super.key});

  @override
  State<AdminDashboardScreen> createState() => _AdminDashboardScreenState();
}

class _AdminDashboardScreenState extends State<AdminDashboardScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AdminProvider>().fetchDashboard();
    });
  }

  @override
  Widget build(BuildContext context) {
    final admin = context.watch<AdminProvider>();
    final m = admin.metrics;

    return Scaffold(
      backgroundColor: AppColors.background,
      body: RefreshIndicator(
        onRefresh: () => admin.fetchDashboard(),
        color: const Color(0xFF7C3AED),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Admin Banner
            Container(
              padding: const EdgeInsets.all(18),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF6D28D9), Color(0xFF7C3AED), Color(0xFF4F46E5)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(18),
              ),
              child: const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Icon(Icons.shield_outlined, color: Colors.white, size: 22),
                      SizedBox(width: 8),
                      Text('Admin Intelligence Hub', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
                    ],
                  ),
                  SizedBox(height: 8),
                  Text(
                    'Real-time overview of members, advertising campaigns, and on-chain deposits.',
                    style: TextStyle(color: Colors.white70, fontSize: 12),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 20),

            const Text('Platform Statistics', style: TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.bold, fontSize: 16)),
            const SizedBox(height: 12),

            // Metrics Grid
            GridView.count(
              crossAxisCount: 2,
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              crossAxisSpacing: 12,
              mainAxisSpacing: 12,
              childAspectRatio: 1.35,
              children: [
                _buildMetricCard(
                  title: 'Total Members',
                  value: '${m['total_members'] ?? 0}',
                  subtitle: '${m['active_members'] ?? 0} active',
                  icon: Icons.people,
                  color: AppColors.primary,
                ),
                _buildMetricCard(
                  title: 'Pending Approvals',
                  value: '${m['pending_campaigns'] ?? 0}',
                  subtitle: 'Ad campaigns',
                  icon: Icons.pending_actions,
                  color: AppColors.warning,
                ),
                _buildMetricCard(
                  title: 'Pending Deposits',
                  value: '${m['pending_deposits_count'] ?? 0}',
                  subtitle: '\$${m['pending_deposits_volume'] ?? 0} USDT',
                  icon: Icons.account_balance,
                  color: AppColors.accent,
                ),
                _buildMetricCard(
                  title: 'Active Campaigns',
                  value: '${m['active_campaigns'] ?? 0}',
                  subtitle: 'Live across platform',
                  icon: Icons.campaign,
                  color: const Color(0xFF8B5CF6),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildMetricCard({
    required String title,
    required String value,
    required String subtitle,
    required IconData icon,
    required Color color,
  }) {
    return Container(
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Text(title, style: const TextStyle(color: AppColors.textMuted, fontSize: 12, fontWeight: FontWeight.w500)),
              CircleAvatar(
                radius: 14,
                backgroundColor: color.withOpacity(0.15),
                child: Icon(icon, color: color, size: 16),
              ),
            ],
          ),
          Text(value, style: const TextStyle(color: AppColors.textPrimary, fontSize: 22, fontWeight: FontWeight.bold)),
          Text(subtitle, style: const TextStyle(color: AppColors.textSecondary, fontSize: 11)),
        ],
      ),
    );
  }
}
