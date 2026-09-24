import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../providers/admin_provider.dart';

class AdminCampaignsScreen extends StatefulWidget {
  const AdminCampaignsScreen({super.key});

  @override
  State<AdminCampaignsScreen> createState() => _AdminCampaignsScreenState();
}

class _AdminCampaignsScreenState extends State<AdminCampaignsScreen> {
  String _selectedStatus = 'pending_review';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AdminProvider>().fetchCampaigns(status: _selectedStatus);
    });
  }

  void _filter(String status) {
    setState(() => _selectedStatus = status);
    context.read<AdminProvider>().fetchCampaigns(status: status == 'all' ? null : status);
  }

  @override
  Widget build(BuildContext context) {
    final admin = context.watch<AdminProvider>();

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          // Filter Tabs
          Container(
            padding: const EdgeInsets.all(16),
            color: AppColors.surface,
            child: Row(
              children: [
                _tab('pending_review', 'Pending Review'),
                const SizedBox(width: 8),
                _tab('active', 'Active'),
                const SizedBox(width: 8),
                _tab('rejected', 'Rejected'),
              ],
            ),
          ),

          Expanded(
            child: RefreshIndicator(
              onRefresh: () => admin.fetchCampaigns(status: _selectedStatus == 'all' ? null : _selectedStatus),
              color: const Color(0xFF7C3AED),
              child: admin.isLoading
                  ? const Center(child: CircularProgressIndicator(color: Color(0xFF7C3AED)))
                  : admin.campaigns.isEmpty
                      ? const Center(child: Text('No campaigns matching filter.', style: TextStyle(color: AppColors.textMuted)))
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: admin.campaigns.length,
                          itemBuilder: (ctx, i) {
                            final c = admin.campaigns[i];
                            final isPending = c['status'] == 'pending_review' || c['approval_status'] == 'pending';
                            final id = c['id'] is int ? c['id'] : int.tryParse(c['id'].toString()) ?? 0;

                            return Container(
                              margin: const EdgeInsets.only(bottom: 14),
                              padding: const EdgeInsets.all(16),
                              decoration: BoxDecoration(
                                color: AppColors.surface,
                                borderRadius: BorderRadius.circular(16),
                                border: Border.all(color: AppColors.border),
                              ),
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Row(
                                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                                    children: [
                                      Text(
                                        c['campaign_name']?.toString() ?? 'Campaign',
                                        style: const TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.bold, fontSize: 16),
                                      ),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: isPending ? AppColors.warning.withOpacity(0.15) : AppColors.accent.withOpacity(0.15),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Text(
                                          c['status']?.toString().toUpperCase() ?? '',
                                          style: TextStyle(
                                            color: isPending ? AppColors.warning : AppColors.accent,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 10,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    'Advertiser: ${c['advertiser_name']} • Page: ${c['business_name']}',
                                    style: const TextStyle(color: AppColors.textMuted, fontSize: 13),
                                  ),
                                  const SizedBox(height: 10),
                                  Row(
                                    children: [
                                      Text('Budget: \$${c['budget']} USDT', style: const TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.w600, fontSize: 13)),
                                      const SizedBox(width: 16),
                                      Text('Remaining: \$${c['remaining_amount']} USDT', style: const TextStyle(color: AppColors.accent, fontSize: 13)),
                                    ],
                                  ),
                                  if (isPending) ...[
                                    const SizedBox(height: 14),
                                    Row(
                                      children: [
                                        Expanded(
                                          child: ElevatedButton.icon(
                                            onPressed: () async {
                                              final ok = await admin.reviewCampaign(id, 'approve');
                                              if (ok && mounted) {
                                                ScaffoldMessenger.of(context).showSnackBar(
                                                  const SnackBar(content: Text('Campaign approved!')),
                                                );
                                              }
                                            },
                                            icon: const Icon(Icons.check, size: 16, color: Colors.white),
                                            label: const Text('Approve', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                                            style: ElevatedButton.styleFrom(
                                              backgroundColor: AppColors.accent,
                                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                            ),
                                          ),
                                        ),
                                        const SizedBox(width: 12),
                                        Expanded(
                                          child: OutlinedButton.icon(
                                            onPressed: () async {
                                              final ok = await admin.reviewCampaign(id, 'reject', 'Policy violation');
                                              if (ok && mounted) {
                                                ScaffoldMessenger.of(context).showSnackBar(
                                                  const SnackBar(content: Text('Campaign rejected.')),
                                                );
                                              }
                                            },
                                            icon: const Icon(Icons.close, size: 16, color: AppColors.danger),
                                            label: const Text('Reject', style: TextStyle(color: AppColors.danger, fontWeight: FontWeight.bold)),
                                            style: OutlinedButton.styleFrom(
                                              side: const BorderSide(color: AppColors.danger),
                                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                            ),
                                          ),
                                        ),
                                      ],
                                    ),
                                  ],
                                ],
                              ),
                            );
                          },
                        ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _tab(String key, String label) {
    final isSelected = _selectedStatus == key;
    return GestureDetector(
      onTap: () => _filter(key),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF7C3AED) : AppColors.background,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: isSelected ? const Color(0xFF7C3AED) : AppColors.border),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: isSelected ? Colors.white : AppColors.textSecondary,
            fontSize: 12,
            fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
          ),
        ),
      ),
    );
  }
}
