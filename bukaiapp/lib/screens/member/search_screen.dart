import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../models/community_model.dart';
import '../../providers/search_provider.dart';
import 'community_detail_screen.dart';
import 'new_connections_screen.dart';
import 'profile_screen.dart';

class SearchScreen extends StatefulWidget {
  const SearchScreen({super.key});

  @override
  State<SearchScreen> createState() => _SearchScreenState();
}

class _SearchScreenState extends State<SearchScreen> {
  final TextEditingController _searchCtrl = TextEditingController();
  Timer? _debounceTimer;
  String _selectedType = 'all';

  // Tracking connection states for members in search results: memberId -> 'none' | 'pending_sent' | 'friends'
  final Map<int, String> _connectionStates = {};
  final Set<int> _loadingConnectionIds = {};

  final List<Map<String, String>> _filterTypes = [
    {'id': 'all', 'label': 'All Results'},
    {'id': 'new_connections', 'label': 'New Connections'},
    {'id': 'members', 'label': 'People'},
    {'id': 'communities', 'label': 'Communities'},
    {'id': 'pages', 'label': 'Business Pages'},
    {'id': 'posts', 'label': 'Posts'},
  ];

  @override
  void dispose() {
    _searchCtrl.dispose();
    _debounceTimer?.cancel();
    super.dispose();
  }

  void _onSearchChanged(String query) {
    _debounceTimer?.cancel();
    _debounceTimer = Timer(const Duration(milliseconds: 350), () {
      if (mounted) {
        if (_selectedType != 'new_connections') {
          context.read<SearchProvider>().search(query, type: _selectedType);
        }
      }
    });
  }

  void _onTypeChanged(String type) {
    setState(() => _selectedType = type);
    if (type != 'new_connections') {
      context.read<SearchProvider>().search(_searchCtrl.text, type: type);
    }
  }

  String _resolveImageUrl(String? path) {
    if (path == null || path.isEmpty) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    final clean = path.startsWith('/') ? path : '/$path';
    return '${ApiClient.baseUrl}$clean';
  }

  Future<void> _handleConnect(Map<String, dynamic> member) async {
    final memberId = member['id'] is int ? member['id'] as int : int.tryParse(member['id'].toString()) ?? 0;
    if (memberId == 0 || _loadingConnectionIds.contains(memberId)) return;

    setState(() {
      _loadingConnectionIds.add(memberId);
      _connectionStates[memberId] = 'pending_sent';
    });

    final res = await ApiClient.post('/friends/request/$memberId');
    if (!mounted) return;

    setState(() {
      _loadingConnectionIds.remove(memberId);
      if (res.success) {
        _connectionStates[memberId] = 'pending_sent';
      } else {
        _connectionStates[memberId] = 'none';
      }
    });

    if (res.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Connection request sent to ${member['name'] ?? 'member'}!'),
          backgroundColor: const Color(0xFF10B981),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
    } else {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text(res.message ?? 'Failed to send connection request.'),
          backgroundColor: const Color(0xFFEF4444),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
    }
  }

  Future<void> _handleCancelConnect(Map<String, dynamic> member) async {
    final memberId = member['id'] is int ? member['id'] as int : int.tryParse(member['id'].toString()) ?? 0;
    if (memberId == 0 || _loadingConnectionIds.contains(memberId)) return;

    setState(() {
      _loadingConnectionIds.add(memberId);
      _connectionStates[memberId] = 'none';
    });

    final res = await ApiClient.post('/friends/requests/$memberId/cancel');
    if (!mounted) return;

    setState(() {
      _loadingConnectionIds.remove(memberId);
    });

    if (res.success) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('Connection request to ${member['name'] ?? 'member'} cancelled.'),
          backgroundColor: const Color(0xFF64748B),
          behavior: SnackBarBehavior.floating,
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final search = context.watch<SearchProvider>();

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: Container(
          height: 42,
          decoration: BoxDecoration(
            color: AppColors.background,
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: AppColors.border),
          ),
          child: TextField(
            controller: _searchCtrl,
            autofocus: true,
            onChanged: _onSearchChanged,
            decoration: InputDecoration(
              hintText: 'Search members, groups, pages, posts...',
              hintStyle: const TextStyle(color: AppColors.textMuted, fontSize: 13),
              prefixIcon: const Icon(Icons.search, color: AppColors.primary, size: 20),
              suffixIcon: _searchCtrl.text.isNotEmpty
                  ? IconButton(
                      icon: const Icon(Icons.clear, color: AppColors.textMuted, size: 18),
                      onPressed: () {
                        _searchCtrl.clear();
                        if (_selectedType != 'new_connections') {
                          search.search('');
                        }
                        setState(() {});
                      },
                    )
                  : null,
              border: InputBorder.none,
              contentPadding: const EdgeInsets.symmetric(vertical: 10),
            ),
          ),
        ),
      ),
      body: Column(
        children: [
          // Filter Tabs
          Container(
            height: 48,
            padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 12),
            decoration: const BoxDecoration(
              color: AppColors.surface,
              border: Border(bottom: BorderSide(color: AppColors.border, width: 0.5)),
            ),
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              itemCount: _filterTypes.length,
              separatorBuilder: (context, index) => const SizedBox(width: 8),
              itemBuilder: (ctx, i) {
                final item = _filterTypes[i];
                final isSelected = item['id'] == _selectedType;
                return ChoiceChip(
                  label: Text(
                    item['label']!,
                    style: TextStyle(
                      color: isSelected ? Colors.white : AppColors.textSecondary,
                      fontWeight: isSelected ? FontWeight.bold : FontWeight.normal,
                      fontSize: 12,
                    ),
                  ),
                  selected: isSelected,
                  selectedColor: AppColors.primary,
                  backgroundColor: AppColors.background,
                  side: BorderSide(color: isSelected ? AppColors.primary : AppColors.border),
                  onSelected: (_) => _onTypeChanged(item['id']!),
                );
              },
            ),
          ),

          // Search Results or New Connections tab
          Expanded(
            child: _selectedType == 'new_connections'
                ? const NewConnectionsScreen(isEmbedded: true)
                : search.isLoading
                    ? const Center(child: CircularProgressIndicator(color: AppColors.primary))
                    : _searchCtrl.text.trim().isEmpty
                        ? _buildEmptyPrompt()
                        : _buildResultsList(search),
          ),
        ],
      ),
    );
  }

  Widget _buildEmptyPrompt() {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Discover New Connections Header Card matching the website & screenshot!
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              border: Border.all(color: const Color(0xFFE2E8F0)),
              boxShadow: const [
                BoxShadow(
                  color: Color(0x060F172A),
                  blurRadius: 10,
                  offset: Offset(0, 3),
                ),
              ],
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                      decoration: BoxDecoration(
                        color: const Color(0xFF5F48DC).withOpacity(0.1),
                        borderRadius: BorderRadius.circular(6),
                      ),
                      child: const Text(
                        'DISCOVER',
                        style: TextStyle(
                          color: Color(0xFF5F48DC),
                          fontSize: 11,
                          fontWeight: FontWeight.w800,
                          letterSpacing: 1.1,
                        ),
                      ),
                    ),
                    const Spacer(),
                    const Icon(Icons.auto_awesome, size: 18, color: Color(0xFF5F48DC)),
                  ],
                ),
                const SizedBox(height: 8),
                const Text(
                  'New Connections',
                  style: TextStyle(
                    color: Color(0xFF0F172A),
                    fontSize: 20,
                    fontWeight: FontWeight.w800,
                    letterSpacing: -0.4,
                  ),
                ),
                const SizedBox(height: 6),
                const Text(
                  'Recommended connections based on mutual connections, registration, and location.',
                  style: TextStyle(color: Color(0xFF64748B), fontSize: 13, height: 1.35),
                ),
                const SizedBox(height: 14),
                ElevatedButton.icon(
                  onPressed: () {
                    _onTypeChanged('new_connections');
                  },
                  icon: const Icon(Icons.person_search, size: 16, color: Colors.white),
                  label: const Text('Discover New Connections', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2563EB),
                    foregroundColor: Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 36),

          // Central Explore Guide
          Center(
            child: Column(
              children: [
                Container(
                  width: 64,
                  height: 64,
                  decoration: const BoxDecoration(
                    color: AppColors.primarySoft,
                    shape: BoxShape.circle,
                  ),
                  child: const Icon(Icons.search, size: 32, color: AppColors.primary),
                ),
                const SizedBox(height: 14),
                const Text(
                  'Explore the MLM Book Network',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: AppColors.textPrimary),
                ),
                const SizedBox(height: 6),
                const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 30),
                  child: Text(
                    'Search for top network members, official communities, business pages, or social posts.',
                    textAlign: TextAlign.center,
                    style: TextStyle(fontSize: 13, color: AppColors.textMuted),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildResultsList(SearchProvider search) {
    final hasMembers = search.members.isNotEmpty;
    final hasCommunities = search.communities.isNotEmpty;
    final hasPages = search.pages.isNotEmpty;
    final hasPosts = search.posts.isNotEmpty;

    if (!hasMembers && !hasCommunities && !hasPages && !hasPosts) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.find_in_page_outlined, size: 54, color: AppColors.textMuted),
            const SizedBox(height: 12),
            Text(
              'No results found for "${_searchCtrl.text}"',
              style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.textPrimary),
            ),
            const SizedBox(height: 6),
            const Text('Try adjusting your search query or switching categories.', style: TextStyle(color: AppColors.textMuted, fontSize: 13)),
          ],
        ),
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Members Section
        if (hasMembers) ...[
          _buildSectionHeader('Members (${search.members.length})', Icons.people_outline),
          ...search.members.map((m) => _buildMemberCard(m)),
          const SizedBox(height: 16),
        ],

        // Communities Section
        if (hasCommunities) ...[
          _buildSectionHeader('Communities (${search.communities.length})', Icons.groups_outlined),
          ...search.communities.map((c) => _buildCommunityCard(c)),
          const SizedBox(height: 16),
        ],

        // Business Pages Section
        if (hasPages) ...[
          _buildSectionHeader('Business Pages (${search.pages.length})', Icons.storefront_outlined),
          ...search.pages.map((p) => _buildPageCard(p)),
          const SizedBox(height: 16),
        ],

        // Posts Section
        if (hasPosts) ...[
          _buildSectionHeader('Posts (${search.posts.length})', Icons.article_outlined),
          ...search.posts.map((post) => _buildPostCard(post)),
        ],
      ],
    );
  }

  Widget _buildSectionHeader(String title, IconData icon) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 10, top: 4),
      child: Row(
        children: [
          Icon(icon, size: 18, color: AppColors.primary),
          const SizedBox(width: 8),
          Text(
            title,
            style: const TextStyle(fontSize: 14, fontWeight: FontWeight.bold, color: AppColors.textHeading),
          ),
        ],
      ),
    );
  }

  Widget _buildMemberCard(Map<String, dynamic> m) {
    final memberId = m['id'] is int ? m['id'] as int : int.tryParse(m['id'].toString()) ?? 0;
    final name = m['name']?.toString() ?? 'Member';
    final handle = m['user_id'] != null ? '@${m['user_id']}' : '@member';
    final isVerified = m['is_verified'] == true || m['is_verified'] == 1;
    final avatarUrl = _resolveImageUrl(m['avatar_url']?.toString());
    final mutualCount = m['mutual_count'] is int ? m['mutual_count'] as int : int.tryParse(m['mutual_count']?.toString() ?? '0') ?? 0;
    final location = [m['city'], m['country']].where((e) => e != null && e.toString().trim().isNotEmpty).join(', ');
    final metaText = mutualCount > 0
        ? '$mutualCount mutual connection${mutualCount != 1 ? 's' : ''}'
        : (location.isNotEmpty ? location : 'Member');

    final state = _connectionStates[memberId] ?? m['friendship_state']?.toString() ?? 'none';
    final isLoadingAction = _loadingConnectionIds.contains(memberId);

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x040F172A),
            blurRadius: 8,
            offset: Offset(0, 2),
          ),
        ],
      ),
      child: Row(
        children: [
          // Avatar (Clickable to view profile)
          GestureDetector(
            onTap: () {
              Navigator.push(
                context,
                MaterialPageRoute(builder: (_) => const ProfileScreen()),
              );
            },
            child: Container(
              width: 46,
              height: 46,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                border: Border.all(color: const Color(0xFFE2E8F0), width: 1.5),
              ),
              child: ClipOval(
                child: avatarUrl.isNotEmpty
                    ? Image.network(
                        avatarUrl,
                        fit: BoxFit.cover,
                        errorBuilder: (_, _, _) => _buildSearchAvatarFallback(name),
                      )
                    : _buildSearchAvatarFallback(name),
              ),
            ),
          ),
          const SizedBox(width: 12),

          // Info (Name + Verified Badge + Handle + Meta)
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Flexible(
                      child: Text(
                        name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14.5, color: Color(0xFF0F172A)),
                      ),
                    ),
                    if (isVerified) ...[
                      const SizedBox(width: 5),
                      Container(
                        width: 15,
                        height: 15,
                        decoration: const BoxDecoration(
                          color: Color(0xFF10B981),
                          shape: BoxShape.circle,
                        ),
                        child: const Center(
                          child: Icon(Icons.check, size: 9.5, color: Colors.white),
                        ),
                      ),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  handle,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: Color(0xFF64748B), fontSize: 12.5),
                ),
                const SizedBox(height: 2),
                Text(
                  metaText,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11.5),
                ),
              ],
            ),
          ),

          const SizedBox(width: 8),

          // Connect Action Button
          _buildSearchConnectButton(m, state, isLoadingAction),
        ],
      ),
    );
  }

  Widget _buildSearchAvatarFallback(String name) {
    final initial = name.trim().isNotEmpty ? name.trim()[0].toUpperCase() : 'M';
    return Container(
      alignment: Alignment.center,
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFF2563EB), Color(0xFF7C3AED)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Text(
        initial,
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.bold,
          fontSize: 18,
        ),
      ),
    );
  }

  Widget _buildSearchConnectButton(Map<String, dynamic> member, String state, bool isLoading) {
    if (isLoading) {
      return Container(
        height: 36,
        width: 88,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: const SizedBox(
          width: 16,
          height: 16,
          child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF2563EB)),
        ),
      );
    }

    if (state == 'friends') {
      return Container(
        height: 36,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(
          color: const Color(0xFFF1F5F9),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: const Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.check, size: 14, color: Color(0xFF10B981)),
            SizedBox(width: 4),
            Text(
              'Connected',
              style: TextStyle(color: Color(0xFF10B981), fontSize: 12, fontWeight: FontWeight.bold),
            ),
          ],
        ),
      );
    }

    if (state == 'pending_sent') {
      return InkWell(
        onTap: () => _handleCancelConnect(member),
        borderRadius: BorderRadius.circular(10),
        child: Container(
          height: 36,
          padding: const EdgeInsets.symmetric(horizontal: 10),
          decoration: BoxDecoration(
            color: const Color(0xFFF8FAFC),
            borderRadius: BorderRadius.circular(10),
            border: Border.all(color: const Color(0xFFCBD5E1)),
          ),
          child: const Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.schedule, size: 14, color: Color(0xFF64748B)),
              SizedBox(width: 4),
              Text(
                'Requested',
                style: TextStyle(color: Color(0xFF64748B), fontSize: 12, fontWeight: FontWeight.w600),
              ),
            ],
          ),
        ),
      );
    }

    return ElevatedButton.icon(
      onPressed: () => _handleConnect(member),
      icon: const Icon(Icons.person_add_alt_1, size: 15, color: Colors.white),
      label: const Text(
        'Connect',
        style: TextStyle(color: Colors.white, fontSize: 12.5, fontWeight: FontWeight.bold),
      ),
      style: ElevatedButton.styleFrom(
        backgroundColor: const Color(0xFF2563EB),
        foregroundColor: Colors.white,
        elevation: 0,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 0),
        minimumSize: const Size(0, 36),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
      ),
    );
  }

  Widget _buildCommunityCard(Map<String, dynamic> c) {
    final name = c['name']?.toString() ?? 'Community';
    final desc = c['description']?.toString() ?? '';
    final membersCount = c['members_count'] ?? 0;

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: InkWell(
        onTap: () {
          final community = CommunityModel.fromJson(c);
          Navigator.push(context, MaterialPageRoute(builder: (_) => CommunityDetailScreen(community: community)));
        },
        child: Row(
          children: [
            Container(
              width: 42,
              height: 42,
              decoration: BoxDecoration(
                color: AppColors.secondarySoft,
                borderRadius: BorderRadius.circular(10),
              ),
              child: const Icon(Icons.groups, color: AppColors.secondary, size: 22),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(name, style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.textPrimary, fontSize: 14)),
                  if (desc.isNotEmpty)
                    Text(desc, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.textMuted, fontSize: 12)),
                  Text('$membersCount members', style: const TextStyle(color: AppColors.accent, fontSize: 11, fontWeight: FontWeight.w600)),
                ],
              ),
            ),
            const Icon(Icons.chevron_right, color: AppColors.textMuted, size: 20),
          ],
        ),
      ),
    );
  }

  Widget _buildPageCard(Map<String, dynamic> p) {
    final name = p['name']?.toString() ?? 'Business Page';
    final category = p['category']?.toString() ?? 'Business';
    final website = p['website']?.toString() ?? '';

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        children: [
          Container(
            width: 42,
            height: 42,
            decoration: BoxDecoration(
              color: AppColors.accentSoft,
              borderRadius: BorderRadius.circular(10),
            ),
            child: const Icon(Icons.storefront, color: AppColors.accent, size: 22),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(name, style: const TextStyle(fontWeight: FontWeight.bold, color: AppColors.textPrimary, fontSize: 14)),
                Text(category, style: const TextStyle(color: AppColors.textMuted, fontSize: 12)),
                if (website.isNotEmpty)
                  Text(website, maxLines: 1, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.primary, fontSize: 11)),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPostCard(Map<String, dynamic> post) {
    final body = post['body']?.toString() ?? '';
    final member = post['member'] as Map<String, dynamic>?;
    final authorName = member?['name']?.toString() ?? 'Member';

    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              CircleAvatar(
                radius: 12,
                backgroundColor: AppColors.primarySoft,
                child: Text(authorName.isNotEmpty ? authorName[0].toUpperCase() : 'M', style: const TextStyle(fontSize: 10, color: AppColors.primary)),
              ),
              const SizedBox(width: 8),
              Text(authorName, style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12, color: AppColors.textPrimary)),
            ],
          ),
          const SizedBox(height: 6),
          Text(body, maxLines: 2, overflow: TextOverflow.ellipsis, style: const TextStyle(color: AppColors.textSecondary, fontSize: 13)),
        ],
      ),
    );
  }
}
