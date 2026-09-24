import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../providers/admin_provider.dart';

class AdminDepositsScreen extends StatefulWidget {
  const AdminDepositsScreen({super.key});

  @override
  State<AdminDepositsScreen> createState() => _AdminDepositsScreenState();
}

class _AdminDepositsScreenState extends State<AdminDepositsScreen> {
  String _selectedStatus = 'pending';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AdminProvider>().fetchDeposits(status: _selectedStatus);
    });
  }

  void _filter(String status) {
    setState(() => _selectedStatus = status);
    context.read<AdminProvider>().fetchDeposits(status: status == 'all' ? null : status);
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
                _tab('pending', 'Pending Review'),
                const SizedBox(width: 8),
                _tab('approved', 'Approved'),
                const SizedBox(width: 8),
                _tab('all', 'All Deposits'),
              ],
            ),
          ),

          Expanded(
            child: RefreshIndicator(
              onRefresh: () => admin.fetchDeposits(status: _selectedStatus == 'all' ? null : _selectedStatus),
              color: const Color(0xFF7C3AED),
              child: admin.isLoading
                  ? const Center(child: CircularProgressIndicator(color: Color(0xFF7C3AED)))
                  : admin.deposits.isEmpty
                      ? const Center(child: Text('No deposit requests found.', style: TextStyle(color: AppColors.textMuted)))
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: admin.deposits.length,
                          itemBuilder: (ctx, i) {
                            final d = admin.deposits[i];
                            final isPending = d['status'] == 'pending';
                            final isApproved = d['status'] == 'approved';
                            final id = d['id'] is int ? d['id'] : int.tryParse(d['id'].toString()) ?? 0;

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
                                        '+${d['amount']} USDT',
                                        style: const TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.bold, fontSize: 18),
                                      ),
                                      Container(
                                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                        decoration: BoxDecoration(
                                          color: isApproved ? AppColors.accent.withOpacity(0.15) : AppColors.warning.withOpacity(0.15),
                                          borderRadius: BorderRadius.circular(8),
                                        ),
                                        child: Text(
                                          d['status']?.toString().toUpperCase() ?? '',
                                          style: TextStyle(
                                            color: isApproved ? AppColors.accent : AppColors.warning,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 10,
                                          ),
                                        ),
                                      ),
                                    ],
                                  ),
                                  const SizedBox(height: 6),
                                  Text(
                                    'User: ${d['member_name']} (ID: ${d['member_user_id']})',
                                    style: const TextStyle(color: AppColors.textSecondary, fontSize: 13),
                                  ),
                                  const SizedBox(height: 4),
                                  Text(
                                    'Tx Hash: ${d['tx_hash']}',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(color: AppColors.textMuted, fontSize: 11, fontFamily: 'monospace'),
                                  ),
                                  const SizedBox(height: 12),

                                  if (isPending) ...[
                                    Row(
                                      children: [
                                        // 1-Tap BSC On-chain Re-Verify
                                        Expanded(
                                          child: ElevatedButton.icon(
                                            onPressed: () async {
                                              ScaffoldMessenger.of(context).showSnackBar(
                                                const SnackBar(content: Text('Querying BNB Smart Chain RPC...')),
                                              );
                                              final res = await admin.reverifyDeposit(id);
                                              if (mounted) {
                                                ScaffoldMessenger.of(context).showSnackBar(
                                                  SnackBar(
                                                    content: Text(res['message']?.toString() ?? ''),
                                                    backgroundColor: res['success'] == true ? AppColors.accent : AppColors.danger,
                                                  ),
                                                );
                                              }
                                            },
                                            icon: const Icon(Icons.sync, size: 16, color: Colors.white),
                                            label: const Text('Re-Verify On-Chain', style: TextStyle(color: Colors.white, fontSize: 12)),
                                            style: ElevatedButton.styleFrom(
                                              backgroundColor: const Color(0xFF7C3AED),
                                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                            ),
                                          ),
                                        ),
                                        const SizedBox(width: 8),
                                        // Manual Approve
                                        ElevatedButton(
                                          onPressed: () async {
                                            final ok = await admin.approveDeposit(id);
                                            if (ok && mounted) {
                                              ScaffoldMessenger.of(context).showSnackBar(
                                                const SnackBar(content: Text('Deposit approved manually!')),
                                              );
                                            }
                                          },
                                          style: ElevatedButton.styleFrom(
                                            backgroundColor: AppColors.accent,
                                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                                          ),
                                          child: const Text('Approve', style: TextStyle(color: Colors.white, fontSize: 12, fontWeight: FontWeight.bold)),
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
