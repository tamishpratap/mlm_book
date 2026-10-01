import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import 'messages_screen.dart';
import 'profile_screen.dart';

class BusinessDirectoryScreen extends StatefulWidget {
  final VoidCallback? onBack;

  const BusinessDirectoryScreen({super.key, this.onBack});

  @override
  State<BusinessDirectoryScreen> createState() => _BusinessDirectoryScreenState();
}

class _BusinessDirectoryScreenState extends State<BusinessDirectoryScreen> {
  final TextEditingController _searchController = TextEditingController();
  bool _isLoading = true;
  bool _isRefreshing = false;
  String _selectedCountry = '';
  List<Map<String, dynamic>> _members = [];
  List<String> _countries = [];

  @override
  void initState() {
    super.initState();
    _fetchDirectory();
  }

  @override
  void dispose() {
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _fetchDirectory({bool isRefresh = false}) async {
    if (isRefresh) {
      setState(() => _isRefreshing = true);
    } else {
      setState(() => _isLoading = true);
    }

    final queryParams = <String, String>{};
    if (_searchController.text.trim().isNotEmpty) {
      queryParams['q'] = _searchController.text.trim();
    }
    if (_selectedCountry.isNotEmpty && _selectedCountry != 'All') {
      queryParams['country'] = _selectedCountry;
    }

    final queryString = queryParams.entries.map((e) => '${e.key}=${Uri.encodeComponent(e.value)}').join('&');
    final res = await ApiClient.get('/business-directory?$queryString');

    if (mounted) {
      setState(() {
        _isLoading = false;
        _isRefreshing = false;
        if (res.success && res.data is Map) {
          final data = res.data as Map<String, dynamic>;
          if (data['members'] is Map && data['members']['data'] is List) {
            _members = (data['members']['data'] as List).cast<Map<String, dynamic>>();
          } else if (data['members'] is List) {
            _members = (data['members'] as List).cast<Map<String, dynamic>>();
          }

          if (data['available_countries'] is List) {
            _countries = (data['available_countries'] as List).map((e) => e.toString()).toList();
          }
        }
      });
    }
  }

  void _onSearch(String query) {
    _fetchDirectory();
  }

  void _onCountrySelected(String country) {
    setState(() {
      _selectedCountry = (_selectedCountry == country || country == 'All') ? '' : country;
    });
    _fetchDirectory();
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF0F172A), size: 24),
          onPressed: widget.onBack ?? () => Navigator.maybePop(context),
        ),
        title: const Text(
          'Business Directory',
          style: TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 18,
            fontWeight: FontWeight.w700,
          ),
        ),
        actions: [
          IconButton(
            icon: _isRefreshing
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF2563EB)),
                  )
                : const Icon(Icons.refresh_rounded, color: Color(0xFF64748B), size: 22),
            tooltip: 'Refresh',
            onPressed: _isRefreshing ? null : () => _fetchDirectory(isRefresh: true),
          ),
        ],
      ),
      body: Column(
        children: [
          // 1. Search & Filter Bar
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Rounded Search Bar
                Container(
                  height: 42,
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(24),
                  ),
                  child: TextField(
                    controller: _searchController,
                    onSubmitted: _onSearch,
                    textInputAction: TextInputAction.search,
                    style: const TextStyle(fontSize: 13.5, color: Color(0xFF0F172A)),
                    decoration: InputDecoration(
                      hintText: 'Search name, user ID, email, country...',
                      hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                      prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF64748B), size: 20),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, color: Color(0xFF94A3B8), size: 18),
                              onPressed: () {
                                _searchController.clear();
                                _onSearch('');
                              },
                            )
                          : null,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(vertical: 10, horizontal: 14),
                    ),
                  ),
                ),

                const SizedBox(height: 10),
                // Modern Country Select Dropdown
                Container(
                  height: 44,
                  padding: const EdgeInsets.symmetric(horizontal: 14),
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: const Color(0xFFE2E8F0)),
                  ),
                  child: DropdownButtonHideUnderline(
                    child: DropdownButton<String>(
                      value: (_selectedCountry.isNotEmpty && _countries.contains(_selectedCountry))
                          ? _selectedCountry
                          : 'All',
                      isExpanded: true,
                      icon: const Icon(Icons.keyboard_arrow_down_rounded, color: Color(0xFF64748B), size: 22),
                      style: const TextStyle(fontSize: 13.5, color: Color(0xFF0F172A), fontWeight: FontWeight.w500),
                      dropdownColor: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      items: [
                        const DropdownMenuItem<String>(
                          value: 'All',
                          child: Row(
                            children: [
                              Icon(Icons.public, size: 16, color: Color(0xFF2563EB)),
                              SizedBox(width: 8),
                              Text('All Countries & Regions', style: TextStyle(fontWeight: FontWeight.w600)),
                            ],
                          ),
                        ),
                        ..._countries.map(
                          (c) => DropdownMenuItem<String>(
                            value: c,
                            child: Row(
                              children: [
                                const Icon(Icons.location_on_outlined, size: 16, color: Color(0xFF64748B)),
                                const SizedBox(width: 8),
                                Expanded(child: Text(c, overflow: TextOverflow.ellipsis)),
                              ],
                            ),
                          ),
                        ),
                      ],
                      onChanged: (val) {
                        if (val != null) {
                          _onCountrySelected(val);
                        }
                      },
                    ),
                  ),
                ),
              ],
            ),
          ),

          const Divider(height: 1, color: Color(0xFFE2E8F0)),

          // 2. Members Directory Grid / List
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFF2563EB)))
                : RefreshIndicator(
                    onRefresh: () => _fetchDirectory(isRefresh: true),
                    color: const Color(0xFF2563EB),
                    child: _members.isEmpty
                        ? _buildEmptyState()
                        : ListView.builder(
                            padding: const EdgeInsets.fromLTRB(16, 16, 16, 32),
                            itemCount: _members.length,
                            itemBuilder: (ctx, i) {
                              final m = _members[i];
                              return _buildMemberCard(m);
                            },
                          ),
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildMemberCard(Map<String, dynamic> member) {
    final memberId = member['id'] is int ? member['id'] : int.tryParse(member['id'].toString()) ?? 0;
    final name = member['name']?.toString() ?? 'Member';
    final userId = member['user_id']?.toString() ?? 'user';
    final email = member['email']?.toString() ?? '';
    final country = member['country']?.toString() ?? 'Global';
    final avatarUrl = AppConstants.resolveMediaUrl(member['avatar_url']?.toString());
    final isVerified = member['is_verified'] == true || member['is_verified'] == 1;

    final initials = name.isNotEmpty ? name.substring(0, name.length >= 2 ? 2 : 1).toUpperCase() : 'M';

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.02),
            blurRadius: 6,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Row 1: Avatar + Name + @User ID + Verified
          Row(
            children: [
              GestureDetector(
                onTap: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => ProfileScreen(memberId: memberId),
                    ),
                  );
                },
                child: Container(
                  width: 44,
                  height: 44,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    border: Border.all(color: const Color(0xFFE2E8F0), width: 1.5),
                  ),
                  child: ClipOval(
                    child: avatarUrl != null && avatarUrl.isNotEmpty
                        ? Image.network(
                            avatarUrl,
                            fit: BoxFit.cover,
                            errorBuilder: (ctx, err, stack) => _buildFallbackAvatar(initials),
                          )
                        : _buildFallbackAvatar(initials),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            name,
                            style: const TextStyle(
                              fontSize: 14.5,
                              fontWeight: FontWeight.w700,
                              color: Color(0xFF0F172A),
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        if (isVerified) ...[
                          const SizedBox(width: 4),
                          const Icon(Icons.verified, color: Color(0xFF16A34A), size: 15),
                        ],
                      ],
                    ),
                    const SizedBox(height: 1),
                    Text(
                      '@$userId',
                      style: const TextStyle(
                        fontSize: 12,
                        color: Color(0xFF2563EB),
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),

          const SizedBox(height: 8),

          // Row 2: Location & Email Compact Row
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
            decoration: BoxDecoration(
              color: const Color(0xFFF8FAFC),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Row(
              children: [
                const Icon(Icons.location_on_outlined, size: 13, color: Color(0xFF64748B)),
                const SizedBox(width: 3),
                Text(
                  country,
                  style: const TextStyle(fontSize: 11.5, fontWeight: FontWeight.w600, color: Color(0xFF334155)),
                ),
                if (email.isNotEmpty) ...[
                  const SizedBox(width: 8),
                  Container(width: 1, height: 10, color: const Color(0xFFCBD5E1)),
                  const SizedBox(width: 8),
                  const Icon(Icons.email_outlined, size: 12.5, color: Color(0xFF64748B)),
                  const SizedBox(width: 4),
                  Expanded(
                    child: Text(
                      email,
                      style: const TextStyle(fontSize: 11.5, color: Color(0xFF64748B)),
                      overflow: TextOverflow.ellipsis,
                    ),
                  ),
                ],
              ],
            ),
          ),

          const SizedBox(height: 8),

          // Row 3: Action Buttons (Send Direct Message + View Profile)
          Row(
            children: [
              Expanded(
                child: ElevatedButton.icon(
                  onPressed: () {
                    Navigator.push(
                      context,
                      MaterialPageRoute(
                        builder: (_) => ChatConversationScreen(partnerId: memberId, partnerName: name),
                      ),
                    );
                  },
                  icon: const Icon(Icons.chat_bubble_outline_rounded, size: 14, color: Colors.white),
                  label: const Text(
                    'Send Message',
                    style: TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2563EB),
                    foregroundColor: Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 8),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  ),
                ),
              ),
              const SizedBox(width: 8),
              OutlinedButton(
                onPressed: () {
                  Navigator.push(
                    context,
                    MaterialPageRoute(
                      builder: (_) => ProfileScreen(memberId: memberId),
                    ),
                  );
                },
                style: OutlinedButton.styleFrom(
                  padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
                  side: const BorderSide(color: Color(0xFFE2E8F0)),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  backgroundColor: const Color(0xFFF8FAFC),
                ),
                child: const Text(
                  'Profile',
                  style: TextStyle(color: Color(0xFF475569), fontWeight: FontWeight.w600, fontSize: 12.5),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _buildFallbackAvatar(String initials) {
    return Container(
      color: const Color(0xFF2563EB),
      alignment: Alignment.center,
      child: Text(
        initials,
        style: const TextStyle(color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16),
      ),
    );
  }

  Widget _buildEmptyState() {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: const BoxDecoration(
                color: Color(0xFFEFF6FF),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.people_outline_rounded, size: 40, color: Color(0xFF2563EB)),
            ),
            const SizedBox(height: 16),
            const Text(
              'No Members Found',
              style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 6),
            const Text(
              'Try changing your search keywords or country filter.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Color(0xFF64748B), fontSize: 13),
            ),
          ],
        ),
      ),
    );
  }
}
