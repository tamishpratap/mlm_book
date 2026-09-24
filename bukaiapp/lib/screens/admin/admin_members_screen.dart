import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../providers/admin_provider.dart';

class AdminMembersScreen extends StatefulWidget {
  const AdminMembersScreen({super.key});

  @override
  State<AdminMembersScreen> createState() => _AdminMembersScreenState();
}

class _AdminMembersScreenState extends State<AdminMembersScreen> {
  final _searchController = TextEditingController();
  String _selectedFilter = 'all';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<AdminProvider>().fetchMembers();
    });
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  void _applyFilter(String filter) {
    setState(() => _selectedFilter = filter);
    context.read<AdminProvider>().fetchMembers(
          filter: filter == 'all' ? null : filter,
          query: _searchController.text.trim(),
        );
  }

  @override
  Widget build(BuildContext context) {
    final admin = context.watch<AdminProvider>();

    return Scaffold(
      backgroundColor: AppColors.background,
      body: Column(
        children: [
          // Search & Filter Header
          Container(
            padding: const EdgeInsets.all(16),
            color: AppColors.surface,
            child: Column(
              children: [
                TextField(
                  controller: _searchController,
                  style: const TextStyle(color: AppColors.textPrimary),
                  decoration: InputDecoration(
                    hintText: 'Search members by name, email, or user ID...',
                    hintStyle: const TextStyle(color: AppColors.textMuted, fontSize: 13),
                    prefixIcon: const Icon(Icons.search, color: AppColors.textMuted),
                    filled: true,
                    fillColor: AppColors.background,
                    border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                    contentPadding: const EdgeInsets.symmetric(vertical: 10),
                  ),
                  onSubmitted: (q) => _applyFilter(_selectedFilter),
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    _filterChip('all', 'All'),
                    const SizedBox(width: 8),
                    _filterChip('active', 'Active'),
                    const SizedBox(width: 8),
                    _filterChip('pending', 'Pending'),
                    const SizedBox(width: 8),
                    _filterChip('blocked', 'Blocked'),
                  ],
                ),
              ],
            ),
          ),

          // Members List
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => admin.fetchMembers(
                filter: _selectedFilter == 'all' ? null : _selectedFilter,
                query: _searchController.text.trim(),
              ),
              color: const Color(0xFF7C3AED),
              child: admin.isLoading
                  ? const Center(child: CircularProgressIndicator(color: Color(0xFF7C3AED)))
                  : admin.members.isEmpty
                      ? const Center(child: Text('No members found.', style: TextStyle(color: AppColors.textMuted)))
                      : ListView.builder(
                          padding: const EdgeInsets.all(16),
                          itemCount: admin.members.length,
                          itemBuilder: (ctx, i) {
                            final m = admin.members[i];
                            final isBlocked = m['is_blocked'] == true;
                            final isVerified = m['is_verified'] == true;

                            return Container(
                              margin: const EdgeInsets.only(bottom: 12),
                              padding: const EdgeInsets.all(14),
                              decoration: BoxDecoration(
                                color: AppColors.surface,
                                borderRadius: BorderRadius.circular(14),
                                border: Border.all(color: AppColors.border),
                              ),
                              child: Row(
                                children: [
                                  CircleAvatar(
                                    backgroundColor: AppColors.primary.withOpacity(0.2),
                                    child: Text(
                                      (m['name']?.toString() ?? 'M')[0].toUpperCase(),
                                      style: const TextStyle(color: AppColors.primary, fontWeight: FontWeight.bold),
                                    ),
                                  ),
                                  const SizedBox(width: 12),
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Row(
                                          children: [
                                            Text(
                                              m['name']?.toString() ?? 'Member',
                                              style: const TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.bold, fontSize: 14),
                                            ),
                                            if (isVerified) ...[
                                              const SizedBox(width: 4),
                                              const Icon(Icons.verified, color: AppColors.accent, size: 14),
                                            ],
                                          ],
                                        ),
                                        Text(
                                          'ID: ${m['user_id']} • ${m['email']}',
                                          style: const TextStyle(color: AppColors.textMuted, fontSize: 12),
                                        ),
                                        const SizedBox(height: 4),
                                        Text(
                                          'Referrals: ${m['direct_referrals']} • Ad Bal: \$${m['ad_balance']}',
                                          style: const TextStyle(color: AppColors.textSecondary, fontSize: 11),
                                        ),
                                      ],
                                    ),
                                  ),
                                  // Block / Unblock Button
                                  IconButton(
                                    icon: Icon(
                                      isBlocked ? Icons.lock_open : Icons.block,
                                      color: isBlocked ? AppColors.accent : AppColors.danger,
                                    ),
                                    tooltip: isBlocked ? 'Unblock Member' : 'Block Member',
                                    onPressed: () async {
                                      final id = m['id'] is int ? m['id'] : int.tryParse(m['id'].toString()) ?? 0;
                                      final ok = await admin.toggleBlockMember(id);
                                      if (ok && mounted) {
                                        ScaffoldMessenger.of(context).showSnackBar(
                                          SnackBar(content: Text(isBlocked ? 'Member unblocked' : 'Member blocked')),
                                        );
                                      }
                                    },
                                  ),
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

  Widget _filterChip(String key, String label) {
    final isSelected = _selectedFilter == key;
    return GestureDetector(
      onTap: () => _applyFilter(key),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFF7C3AED) : AppColors.background,
          borderRadius: BorderRadius.circular(16),
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
