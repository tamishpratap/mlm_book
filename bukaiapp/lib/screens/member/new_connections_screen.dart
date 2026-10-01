import 'dart:async';
import 'package:flutter/material.dart';
import '../../core/api_client.dart';
import '../../core/app_toast.dart';
import '../../core/countries_data.dart';
import 'friends_screen.dart';
import 'profile_screen.dart';

class NewConnectionsScreen extends StatefulWidget {
  final bool isEmbedded;
  const NewConnectionsScreen({super.key, this.isEmbedded = false});

  @override
  State<NewConnectionsScreen> createState() => _NewConnectionsScreenState();
}

class _NewConnectionsScreenState extends State<NewConnectionsScreen> {
  final TextEditingController _searchController = TextEditingController();
  Timer? _debounceTimer;

  List<Map<String, dynamic>> _suggestions = [];
  List<String> _availableCountries = [];
  String _selectedCountry = '';
  String _activeFilter = 'all'; // 'all', 'new', 'mutual', 'nearby'
  int _currentPage = 1;
  int _totalPages = 1;

  bool _isLoading = true;
  bool _isRefreshing = false;
  String? _errorMessage;

  // Local state tracking for connect button states: memberId -> 'none' | 'pending_sent' | 'friends'
  final Map<int, String> _friendshipStates = {};
  final Map<int, bool> _loadingMemberIds = {};

  @override
  void initState() {
    super.initState();
    _fetchCountries();
    _fetchSuggestions();
  }

  @override
  void dispose() {
    _debounceTimer?.cancel();
    _searchController.dispose();
    super.dispose();
  }

  Future<void> _fetchCountries() async {
    try {
      final res = await ApiClient.get('/countries');
      if (res.success && res.data is Map && res.data['countries'] is List) {
        final list = (res.data['countries'] as List)
            .map((c) => c is Map ? (c['name']?.toString() ?? '') : c.toString())
            .where((c) => c.isNotEmpty)
            .toList();
        if (mounted && list.isNotEmpty) {
          setState(() {
            _availableCountries = list;
          });
        }
      }
    } catch (_) {}
  }

  void _onSearchChanged(String value) {
    setState(() {}); // refresh clear icon
    _debounceTimer?.cancel();
    _debounceTimer = Timer(const Duration(milliseconds: 350), () {
      if (mounted) {
        setState(() => _currentPage = 1);
        _fetchSuggestions(page: 1);
      }
    });
  }

  String _resolveImageUrl(String? path) {
    if (path == null || path.isEmpty) return '';
    if (path.startsWith('http://') || path.startsWith('https://')) return path;
    final clean = path.startsWith('/') ? path : '/$path';
    return '${ApiClient.baseUrl}$clean';
  }

  Future<void> _fetchSuggestions({int page = 1, bool isRefresh = false}) async {
    if (isRefresh) {
      setState(() => _isRefreshing = true);
    } else {
      setState(() {
        _isLoading = true;
        _errorMessage = null;
      });
    }

    final queryParams = <String, String>{
      'page': page.toString(),
      'filter': _activeFilter,
    };
    if (_searchController.text.trim().isNotEmpty) {
      queryParams['search'] = _searchController.text.trim();
    }
    if (_selectedCountry.isNotEmpty) {
      queryParams['country'] = _selectedCountry;
    }

    final queryString = queryParams.entries.map((e) => '${e.key}=${Uri.encodeComponent(e.value)}').join('&');
    final res = await ApiClient.get('/friends/suggestions?$queryString');

    if (!mounted) return;

    if (res.success && res.data is Map) {
      final data = res.data as Map<String, dynamic>;
      final dynamic suggestionsRaw = data['suggestions'];
      List<Map<String, dynamic>> rawList = [];
      if (suggestionsRaw is List) {
        rawList = suggestionsRaw.whereType<Map<String, dynamic>>().toList();
      } else if (suggestionsRaw is Map && suggestionsRaw['data'] is List) {
        rawList = (suggestionsRaw['data'] as List).whereType<Map<String, dynamic>>().toList();
      }

      // Update available countries if provided
      if (data['available_countries'] is List && (data['available_countries'] as List).isNotEmpty) {
        final serverCountries = (data['available_countries'] as List).map((e) => e.toString()).where((e) => e.isNotEmpty).toList();
        if (serverCountries.isNotEmpty) {
          _availableCountries = serverCountries;
        }
      }

      // Update pagination
      if (data['pagination'] is Map) {
        final pag = data['pagination'] as Map<String, dynamic>;
        _currentPage = pag['current_page'] is int ? pag['current_page'] : int.tryParse(pag['current_page'].toString()) ?? 1;
        _totalPages = pag['last_page'] is int ? pag['last_page'] : int.tryParse(pag['last_page'].toString()) ?? 1;
      } else if (suggestionsRaw is Map && suggestionsRaw['last_page'] != null) {
        _currentPage = suggestionsRaw['current_page'] is int ? suggestionsRaw['current_page'] : int.tryParse(suggestionsRaw['current_page'].toString()) ?? 1;
        _totalPages = suggestionsRaw['last_page'] is int ? suggestionsRaw['last_page'] : int.tryParse(suggestionsRaw['last_page'].toString()) ?? 1;
      }

      setState(() {
        _suggestions = rawList;
        _isLoading = false;
        _isRefreshing = false;
        _errorMessage = null;

        // Initialize friendship states
        for (final m in rawList) {
          final id = m['id'] is int ? m['id'] as int : int.tryParse(m['id'].toString()) ?? 0;
          if (!_friendshipStates.containsKey(id)) {
            _friendshipStates[id] = m['friendship_state']?.toString() ?? 'none';
          }
        }
      });
    } else {
      setState(() {
        _isLoading = false;
        _isRefreshing = false;
        _errorMessage = res.message ?? 'Failed to load recommended connections.';
      });
    }
  }

  Future<void> _handleConnect(Map<String, dynamic> member) async {
    final memberId = member['id'] is int ? member['id'] as int : int.tryParse(member['id'].toString()) ?? 0;
    if (memberId == 0 || (_loadingMemberIds[memberId] == true)) return;

    setState(() => _loadingMemberIds[memberId] = true);
    setState(() => _friendshipStates[memberId] = 'pending_sent');

    final res = await ApiClient.post('/friends/request/$memberId');
    if (!mounted) return;

    setState(() => _loadingMemberIds[memberId] = false);

    if (res.success) {
      final newStatus = (res.data is Map && res.data['status'] != null) ? res.data['status'].toString() : 'pending_sent';
      setState(() => _friendshipStates[memberId] = newStatus);
      AppToast.success(context, 'Connection request sent to ${member['name'] ?? 'member'}!');
    } else {
      setState(() => _friendshipStates[memberId] = 'none');
      AppToast.error(context, res.message ?? 'Failed to send connection request.');
    }
  }

  Future<void> _handleCancel(Map<String, dynamic> member) async {
    final memberId = member['id'] is int ? member['id'] as int : int.tryParse(member['id'].toString()) ?? 0;
    if (memberId == 0 || (_loadingMemberIds[memberId] == true)) return;

    setState(() => _loadingMemberIds[memberId] = true);
    setState(() => _friendshipStates[memberId] = 'none');

    final res = await ApiClient.post('/friends/requests/$memberId/cancel');
    if (!mounted) return;

    setState(() => _loadingMemberIds[memberId] = false);

    if (res.success) {
      AppToast.info(context, 'Connection request cancelled.');
    } else {
      setState(() => _friendshipStates[memberId] = 'pending_sent');
      AppToast.error(context, res.message ?? 'Could not cancel request.');
    }
  }

  void _showCountrySelector() {
    // Combine dataset with any dynamic countries
    final allNames = <String>{};
    if (_availableCountries.isNotEmpty) {
      allNames.addAll(_availableCountries);
    }
    for (final c in CountriesData.all) {
      allNames.add(c.name);
    }
    final fullList = allNames.toList()..sort((a, b) => a.compareTo(b));

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(24)),
      ),
      builder: (ctx) {
        String filterQuery = '';

        return StatefulBuilder(
          builder: (bottomSheetContext, setModalState) {
            final displayList = fullList.where((c) {
              if (filterQuery.isEmpty) return true;
              return c.toLowerCase().contains(filterQuery.toLowerCase());
            }).toList();

            return Container(
              height: MediaQuery.of(context).size.height * 0.75,
              padding: EdgeInsets.only(
                bottom: MediaQuery.of(bottomSheetContext).viewInsets.bottom,
              ),
              child: Column(
                children: [
                  // Top Drag Handle
                  Container(
                    margin: const EdgeInsets.only(top: 10, bottom: 6),
                    width: 38,
                    height: 4,
                    decoration: BoxDecoration(
                      color: const Color(0xFFCBD5E1),
                      borderRadius: BorderRadius.circular(2),
                    ),
                  ),

                  // Header
                  Padding(
                    padding: const EdgeInsets.fromLTRB(20, 8, 16, 12),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'Select Country',
                          style: TextStyle(
                            fontWeight: FontWeight.w800,
                            fontSize: 17,
                            color: Color(0xFF0F172A),
                            letterSpacing: -0.2,
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.close_rounded, size: 22, color: Color(0xFF64748B)),
                          onPressed: () => Navigator.pop(ctx),
                        ),
                      ],
                    ),
                  ),

                  // Search Field
                  Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    child: Container(
                      height: 44,
                      decoration: BoxDecoration(
                        color: const Color(0xFFF1F5F9),
                        borderRadius: BorderRadius.circular(12),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: TextField(
                        autofocus: false,
                        style: const TextStyle(fontSize: 13.5, color: Color(0xFF0F172A)),
                        decoration: const InputDecoration(
                          hintText: 'Search country by name...',
                          hintStyle: TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
                          prefixIcon: Icon(Icons.search, size: 18, color: Color(0xFF94A3B8)),
                          border: InputBorder.none,
                          contentPadding: EdgeInsets.symmetric(vertical: 11),
                        ),
                        onChanged: (val) {
                          setModalState(() {
                            filterQuery = val.trim();
                          });
                        },
                      ),
                    ),
                  ),

                  const SizedBox(height: 10),
                  const Divider(height: 1, color: Color(0xFFE2E8F0)),

                  // Country List
                  Expanded(
                    child: ListView(
                      padding: const EdgeInsets.symmetric(vertical: 6),
                      children: [
                        // "All Countries" Option at top
                        ListTile(
                          contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 2),
                          leading: Container(
                            width: 32,
                            height: 32,
                            decoration: const BoxDecoration(
                              color: Color(0xFFEFF6FF),
                              shape: BoxShape.circle,
                            ),
                            child: const Center(
                              child: Text('🌐', style: TextStyle(fontSize: 18)),
                            ),
                          ),
                          title: const Text(
                            'All Countries (Global)',
                            style: TextStyle(
                              fontWeight: FontWeight.w700,
                              fontSize: 14,
                              color: Color(0xFF0F172A),
                            ),
                          ),
                          trailing: _selectedCountry.isEmpty
                              ? const Icon(Icons.check_circle_rounded, color: Color(0xFF2563EB), size: 20)
                              : null,
                          onTap: () {
                            Navigator.pop(ctx);
                            setState(() {
                              _selectedCountry = '';
                              _currentPage = 1;
                            });
                            _fetchSuggestions(page: 1);
                          },
                        ),
                        const Divider(height: 1, indent: 20, endIndent: 20, color: Color(0xFFF1F5F9)),

                        ...displayList.map((countryName) {
                          final isSelected = _selectedCountry == countryName;
                          final flag = CountriesData.getFlag(countryName);

                          return ListTile(
                            contentPadding: const EdgeInsets.symmetric(horizontal: 20, vertical: 2),
                            leading: Container(
                              width: 32,
                              height: 32,
                              decoration: BoxDecoration(
                                color: const Color(0xFFF8FAFC),
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: const Color(0xFFE2E8F0)),
                              ),
                              child: Center(
                                child: Text(flag, style: const TextStyle(fontSize: 18)),
                              ),
                            ),
                            title: Text(
                              countryName,
                              style: TextStyle(
                                fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                                fontSize: 13.5,
                                color: isSelected ? const Color(0xFF2563EB) : const Color(0xFF1E293B),
                              ),
                            ),
                            trailing: isSelected
                                ? const Icon(Icons.check_circle_rounded, color: Color(0xFF2563EB), size: 20)
                                : null,
                            onTap: () {
                              Navigator.pop(ctx);
                              setState(() {
                                _selectedCountry = countryName;
                                _currentPage = 1;
                              });
                              _fetchSuggestions(page: 1);
                            },
                          );
                        }),
                      ],
                    ),
                  ),
                ],
              ),
            );
          },
        );
      },
    );
  }


  void _onFilterChanged(String filter) {
    if (_activeFilter == filter) return;
    setState(() {
      _activeFilter = filter;
      _currentPage = 1;
    });
    _fetchSuggestions(page: 1);
  }

  void _onSearchSubmitted() {
    _debounceTimer?.cancel();
    setState(() => _currentPage = 1);
    _fetchSuggestions(page: 1);
  }

  void _onResetFilters() {
    _debounceTimer?.cancel();
    setState(() {
      _searchController.clear();
      _selectedCountry = '';
      _activeFilter = 'all';
      _currentPage = 1;
    });
    _fetchSuggestions(page: 1);
  }

  @override
  Widget build(BuildContext context) {
    final content = RefreshIndicator(
      onRefresh: () => _fetchSuggestions(page: _currentPage, isRefresh: true),
      color: const Color(0xFF2563EB),
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // 1. Header Card matching uploaded screenshot
            _buildHeaderCard(),

            const SizedBox(height: 16),

            // 2. Search & Filter Bar Card matching uploaded screenshot
            _buildFilterCard(),

            const SizedBox(height: 16),

            // 3. Recommended Connections List Card matching uploaded screenshot
            _buildConnectionsListCard(),

            // 4. Pagination
            if (_totalPages > 1 && !_isLoading) ...[
              const SizedBox(height: 20),
              _buildPaginationBar(),
            ],

            const SizedBox(height: 32),
          ],
        ),
      ),
    );

    if (widget.isEmbedded) {
      return content;
    }

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: Navigator.canPop(context)
            ? IconButton(
                icon: const Icon(Icons.arrow_back, color: Color(0xFF0F172A)),
                onPressed: () => Navigator.pop(context),
              )
            : null,
        title: const Text(
          'Discover Connections',
          style: TextStyle(color: Color(0xFF0F172A), fontSize: 17, fontWeight: FontWeight.bold),
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1),
          child: Container(color: const Color(0xFFE2E8F0), height: 1),
        ),
      ),
      body: content,
    );
  }

  // ==========================================
  // SECTION 1: HEADER CARD
  // ==========================================
  Widget _buildHeaderCard() {
    return Container(
      padding: const EdgeInsets.all(20),
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
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Title & Eyebrow
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'DISCOVER',
                      style: TextStyle(
                        color: Color(0xFF5F48DC),
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                        letterSpacing: 1.1,
                      ),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'New Connections',
                      style: TextStyle(
                        color: Color(0xFF0F172A),
                        fontSize: 22,
                        fontWeight: FontWeight.w800,
                        letterSpacing: -0.5,
                      ),
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Recommended connections based on mutual connections, registration, and location.',
                      style: TextStyle(
                        color: Color(0xFF64748B),
                        fontSize: 13,
                        height: 1.35,
                      ),
                    ),
                  ],
                ),
              ),

              const SizedBox(width: 12),

              // Action buttons on top right
              Row(
                mainAxisSize: MainAxisSize.min,
                children: [
                  // Refresh icon button
                  Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        borderRadius: BorderRadius.circular(12),
                        onTap: _isRefreshing ? null : () => _fetchSuggestions(page: _currentPage, isRefresh: true),
                        child: Center(
                          child: _isRefreshing
                              ? const SizedBox(
                                  width: 16,
                                  height: 16,
                                  child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF2563EB)),
                                )
                              : const Icon(Icons.refresh, size: 18, color: Color(0xFF334155)),
                        ),
                      ),
                    ),
                  ),

                  const SizedBox(width: 8),

                  // "My Connections" button
                  Container(
                    height: 40,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Material(
                      color: Colors.transparent,
                      child: InkWell(
                        borderRadius: BorderRadius.circular(12),
                        onTap: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(builder: (_) => const FriendsScreen(initialTab: 0)),
                          );
                        },
                        child: const Padding(
                          padding: EdgeInsets.symmetric(horizontal: 14),
                          child: Row(
                            mainAxisSize: MainAxisSize.min,
                            children: [
                              Icon(Icons.people_outline, size: 17, color: Color(0xFF0F172A)),
                              SizedBox(width: 8),
                              Text(
                                'My Connections',
                                style: TextStyle(
                                  color: Color(0xFF0F172A),
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ],
          ),
        ],
      ),
    );
  }

  // ==========================================
  // SECTION 2: FILTER CARD
  // ==========================================
  Widget _buildFilterCard() {
    return Container(
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
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Row 1: Search box, Country select, and Search Button
          LayoutBuilder(
            builder: (context, constraints) {
              final isNarrow = constraints.maxWidth < 600;

              if (isNarrow) {
                return Column(
                  children: [
                    // Search box
                    _buildSearchInput(),
                    const SizedBox(height: 10),
                    Row(
                      children: [
                        // Country dropdown
                        Expanded(child: _buildCountrySelectorButton()),
                        const SizedBox(width: 10),
                        // Search Button
                        _buildSearchButton(),
                      ],
                    ),
                  ],
                );
              }

              return Row(
                children: [
                  Expanded(flex: 5, child: _buildSearchInput()),
                  const SizedBox(width: 10),
                  Expanded(flex: 3, child: _buildCountrySelectorButton()),
                  const SizedBox(width: 10),
                  _buildSearchButton(),
                ],
              );
            },
          ),

          const SizedBox(height: 16),
          const Divider(height: 1, color: Color(0xFFF1F5F9)),
          const SizedBox(height: 14),

          // Row 2: Quick Filter Pills
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                _buildFilterPill(
                  id: 'all',
                  label: 'All',
                  icon: Icons.people_outline,
                ),
                const SizedBox(width: 8),
                _buildFilterPill(
                  id: 'new',
                  label: 'New Members',
                  icon: Icons.auto_awesome_outlined,
                ),
                const SizedBox(width: 8),
                _buildFilterPill(
                  id: 'mutual',
                  label: 'Mutual Connections',
                  icon: Icons.person_add_alt_outlined,
                ),
                const SizedBox(width: 8),
                _buildFilterPill(
                  id: 'nearby',
                  label: 'Nearby',
                  icon: Icons.location_on_outlined,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildSearchInput() {
    return Container(
      height: 46,
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: TextField(
        controller: _searchController,
        style: const TextStyle(fontSize: 13.5, color: Color(0xFF0F172A)),
        textInputAction: TextInputAction.search,
        onChanged: _onSearchChanged,
        onSubmitted: (_) {
          _debounceTimer?.cancel();
          _onSearchSubmitted();
        },
        decoration: InputDecoration(
          isDense: true,
          contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
          border: InputBorder.none,
          hintText: 'Search by name, handle, city, country, or bio...',
          hintStyle: const TextStyle(fontSize: 13, color: Color(0xFF94A3B8)),
          prefixIcon: const Icon(Icons.search, size: 18, color: Color(0xFF94A3B8)),
          suffixIcon: _searchController.text.isNotEmpty
              ? IconButton(
                  icon: const Icon(Icons.clear, size: 16, color: Color(0xFF94A3B8)),
                  onPressed: () {
                    _debounceTimer?.cancel();
                    _searchController.clear();
                    setState(() {});
                    _onSearchSubmitted();
                  },
                )
              : null,
        ),
      ),
    );
  }

  Widget _buildCountrySelectorButton() {
    final hasSelection = _selectedCountry.isNotEmpty;
    final displayText = hasSelection ? _selectedCountry : 'All Countries';
    final flag = hasSelection ? CountriesData.getFlag(_selectedCountry) : '🌐';

    return InkWell(
      onTap: _showCountrySelector,
      borderRadius: BorderRadius.circular(14),
      child: Container(
        height: 46,
        padding: const EdgeInsets.symmetric(horizontal: 12),
        decoration: BoxDecoration(
          color: hasSelection ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(14),
          border: Border.all(
            color: hasSelection ? const Color(0xFF93C5FD) : const Color(0xFFE2E8F0),
            width: 1.2,
          ),
        ),
        child: Row(
          children: [
            Text(flag, style: const TextStyle(fontSize: 16)),
            const SizedBox(width: 8),
            Expanded(
              child: Text(
                displayText,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: hasSelection ? FontWeight.w700 : FontWeight.w500,
                  color: hasSelection ? const Color(0xFF1D4ED8) : const Color(0xFF1E293B),
                ),
              ),
            ),
            if (hasSelection)
              GestureDetector(
                onTap: () {
                  setState(() {
                    _selectedCountry = '';
                    _currentPage = 1;
                  });
                  _fetchSuggestions(page: 1);
                },
                child: const Padding(
                  padding: EdgeInsets.symmetric(horizontal: 4),
                  child: Icon(Icons.close_rounded, size: 16, color: Color(0xFF3B82F6)),
                ),
              )
            else
              const Icon(Icons.keyboard_arrow_down_rounded, size: 18, color: Color(0xFF94A3B8)),
          ],
        ),
      ),
    );
  }

  Widget _buildSearchButton() {
    return Container(
      height: 46,
      decoration: BoxDecoration(
        color: const Color(0xFF2563EB),
        borderRadius: BorderRadius.circular(14),
        boxShadow: const [
          BoxShadow(
            color: Color(0x332563EB),
            blurRadius: 8,
            offset: Offset(0, 3),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(14),
          onTap: _onSearchSubmitted,
          child: const Padding(
            padding: EdgeInsets.symmetric(horizontal: 20),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.search, size: 16, color: Colors.white),
                SizedBox(width: 8),
                Text(
                  'Search',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 13.5,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildFilterPill({
    required String id,
    required String label,
    required IconData icon,
  }) {
    final isActive = _activeFilter == id;

    return Material(
      color: Colors.transparent,
      child: InkWell(
        borderRadius: BorderRadius.circular(999),
        onTap: () => _onFilterChanged(id),
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 180),
          height: 38,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          decoration: BoxDecoration(
            color: isActive ? const Color(0xFFEDF3FF) : const Color(0xFFF8FAFC),
            borderRadius: BorderRadius.circular(999),
            border: Border.all(
              color: isActive ? const Color(0xFF3B82F6).withOpacity(0.5) : const Color(0xFFE2E8F0),
              width: 1,
            ),
            boxShadow: isActive
                ? const [
                    BoxShadow(
                      color: Color(0x1A2563EB),
                      blurRadius: 6,
                      offset: Offset(0, 2),
                    ),
                  ]
                : null,
          ),
          child: Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(
                icon,
                size: 15,
                color: isActive ? const Color(0xFF2563EB) : const Color(0xFF64748B),
              ),
              const SizedBox(width: 7),
              Text(
                label,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: isActive ? FontWeight.w700 : FontWeight.w500,
                  color: isActive ? const Color(0xFF2563EB) : const Color(0xFF64748B),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  // ==========================================
  // SECTION 3: RECOMMENDED CONNECTIONS LIST
  // ==========================================
  Widget _buildConnectionsListCard() {
    if (_isLoading) {
      return Container(
        height: 240,
        alignment: Alignment.center,
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: const Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            CircularProgressIndicator(color: Color(0xFF2563EB), strokeWidth: 2.5),
            SizedBox(height: 14),
            Text('Loading suggestions...', style: TextStyle(color: Color(0xFF64748B), fontSize: 13)),
          ],
        ),
      );
    }

    if (_errorMessage != null) {
      return Container(
        padding: const EdgeInsets.all(32),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Column(
          children: [
            const Icon(Icons.error_outline, size: 48, color: Color(0xFFEF4444)),
            const SizedBox(height: 12),
            const Text(
              'Error Loading Suggestions',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 6),
            Text(
              _errorMessage!,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Color(0xFF64748B), fontSize: 13),
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () => _fetchSuggestions(page: _currentPage),
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF2563EB),
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
              ),
              child: const Text('Try Again'),
            ),
          ],
        ),
      );
    }

    if (_suggestions.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(36),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(20),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: Column(
          children: [
            Container(
              width: 64,
              height: 64,
              decoration: const BoxDecoration(
                color: Color(0xFFF1F5F9),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.people_outline, size: 32, color: Color(0xFF94A3B8)),
            ),
            const SizedBox(height: 16),
            const Text(
              'No suggestions found',
              style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 6),
            const Text(
              'Try adjusting your search criteria or removing filters to discover more members.',
              textAlign: TextAlign.center,
              style: TextStyle(color: Color(0xFF64748B), fontSize: 13, height: 1.4),
            ),
            const SizedBox(height: 18),
            OutlinedButton(
              onPressed: _onResetFilters,
              style: OutlinedButton.styleFrom(
                foregroundColor: const Color(0xFF2563EB),
                side: const BorderSide(color: Color(0xFFCBD5E1)),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
              ),
              child: const Text('Reset Filters'),
            ),
          ],
        ),
      );
    }

    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: const [
          BoxShadow(
            color: Color(0x060F172A),
            blurRadius: 12,
            offset: Offset(0, 4),
          ),
        ],
      ),
      child: ClipRRect(
        borderRadius: BorderRadius.circular(20),
        child: ListView.separated(
          shrinkWrap: true,
          physics: const NeverScrollableScrollPhysics(),
          itemCount: _suggestions.length,
          separatorBuilder: (context, index) => const Divider(height: 1, thickness: 1, color: Color(0xFFF1F5F9)),
          itemBuilder: (context, index) {
            final member = _suggestions[index];
            return _buildMemberRow(member);
          },
        ),
      ),
    );
  }

  Widget _buildMemberRow(Map<String, dynamic> member) {
    final memberId = member['id'] is int ? member['id'] as int : int.tryParse(member['id'].toString()) ?? 0;
    final name = member['name']?.toString() ?? 'Member';
    final handle = member['user_id'] != null ? '@${member['user_id']}' : '@member';
    final isVerified = member['is_verified'] == true || member['is_verified'] == 1 || member['mobile_verified_at'] != null;

    final avatarUrl = _resolveImageUrl(member['avatar_url']?.toString() ?? member['profile_photo']?.toString());
    final mutualCount = member['mutual_count'] is int ? member['mutual_count'] as int : int.tryParse(member['mutual_count']?.toString() ?? '0') ?? 0;
    final location = [member['city'], member['country']].where((e) => e != null && e.toString().trim().isNotEmpty).join(', ');

    final metaText = mutualCount > 0
        ? '$mutualCount mutual connection${mutualCount != 1 ? 's' : ''}'
        : (location.isNotEmpty ? location : 'No mutual connections');

    final state = _friendshipStates[memberId] ?? 'none';
    final isLoadingAction = _loadingMemberIds[memberId] == true;

    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
      child: Row(
        children: [
          // 1. Avatar (Clickable to view Profile)
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
                        errorBuilder: (_, _, _) => _buildAvatarFallback(name),
                      )
                    : _buildAvatarFallback(name),
              ),
            ),
          ),

          const SizedBox(width: 14),

          // 2. Member Info (Name + Verified Badge + Handle + Mutual Info)
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisSize: MainAxisSize.min,
              children: [
                // Name + Green Circle Check Badge
                Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    Flexible(
                      child: Text(
                        name,
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                        style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 14.5,
                          color: Color(0xFF0F172A),
                        ),
                      ),
                    ),
                    if (isVerified) ...[
                      const SizedBox(width: 6),
                      Container(
                        width: 16,
                        height: 16,
                        decoration: const BoxDecoration(
                          color: Color(0xFF10B981),
                          shape: BoxShape.circle,
                        ),
                        child: const Center(
                          child: Icon(Icons.check, size: 10.5, color: Colors.white),
                        ),
                      ),
                    ],
                  ],
                ),

                const SizedBox(height: 2),

                // Username / Handle
                Text(
                  handle,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 12.5,
                    color: Color(0xFF64748B),
                    fontWeight: FontWeight.w400,
                  ),
                ),

                const SizedBox(height: 2),

                // Meta: Mutual Connections count or location
                Text(
                  metaText,
                  maxLines: 1,
                  overflow: TextOverflow.ellipsis,
                  style: const TextStyle(
                    fontSize: 12,
                    color: Color(0xFF94A3B8),
                    fontWeight: FontWeight.w400,
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(width: 12),

          // 3. Connect Button matching uploaded screenshot
          _buildActionButton(member, state, isLoadingAction),
        ],
      ),
    );
  }

  Widget _buildAvatarFallback(String name) {
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

  Widget _buildActionButton(Map<String, dynamic> member, String state, bool isLoading) {
    if (isLoading) {
      return Container(
        height: 38,
        width: 100,
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

    if (state == 'pending_sent') {
      return Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(10),
          onTap: () => _handleCancel(member),
          child: Container(
            height: 38,
            padding: const EdgeInsets.symmetric(horizontal: 14),
            decoration: BoxDecoration(
              color: const Color(0xFFF1F5F9),
              borderRadius: BorderRadius.circular(10),
              border: Border.all(color: const Color(0xFFCBD5E1)),
            ),
            child: const Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.person_remove_outlined, size: 15, color: Color(0xFF475569)),
                SizedBox(width: 6),
                Text(
                  'Requested',
                  style: TextStyle(
                    color: Color(0xFF475569),
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ),
      );
    }

    if (state == 'friends') {
      return Container(
        height: 38,
        padding: const EdgeInsets.symmetric(horizontal: 14),
        decoration: BoxDecoration(
          color: const Color(0xFFECFDF5),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(color: const Color(0xFFA7F3D0)),
        ),
        child: const Row(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.check, size: 15, color: Color(0xFF059669)),
            SizedBox(width: 6),
            Text(
              'Connected',
              style: TextStyle(
                color: Color(0xFF059669),
                fontSize: 12.5,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
      );
    }

    // Default: Solid Blue Connect Button
    return Container(
      height: 38,
      decoration: BoxDecoration(
        color: const Color(0xFF2563EB),
        borderRadius: BorderRadius.circular(10),
        boxShadow: const [
          BoxShadow(
            color: Color(0x332563EB),
            blurRadius: 8,
            offset: Offset(0, 3),
          ),
        ],
      ),
      child: Material(
        color: Colors.transparent,
        child: InkWell(
          borderRadius: BorderRadius.circular(10),
          onTap: () => _handleConnect(member),
          child: const Padding(
            padding: EdgeInsets.symmetric(horizontal: 16),
            child: Row(
              mainAxisSize: MainAxisSize.min,
              children: [
                Icon(Icons.person_add_alt_1, size: 15, color: Colors.white),
                SizedBox(width: 6),
                Text(
                  'Connect',
                  style: TextStyle(
                    color: Colors.white,
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  // ==========================================
  // SECTION 4: PAGINATION BAR
  // ==========================================
  Widget _buildPaginationBar() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        // Previous Button
        IconButton(
          icon: const Icon(Icons.chevron_left),
          color: const Color(0xFF0F172A),
          disabledColor: const Color(0xFFCBD5E1),
          onPressed: _currentPage > 1 ? () => _fetchSuggestions(page: _currentPage - 1) : null,
        ),

        const SizedBox(width: 8),

        // Page Number Pills
        Wrap(
          spacing: 6,
          children: List.generate(_totalPages, (i) {
            final pageNum = i + 1;
            final isCurrent = pageNum == _currentPage;

            // Only display around current page if there are many pages
            if (_totalPages > 7) {
              if (pageNum != 1 && pageNum != _totalPages && (pageNum < _currentPage - 1 || pageNum > _currentPage + 1)) {
                if (pageNum == _currentPage - 2 || pageNum == _currentPage + 2) {
                  return const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 4),
                    child: Text('...', style: TextStyle(color: Color(0xFF64748B))),
                  );
                }
                return const SizedBox.shrink();
              }
            }

            return Material(
              color: Colors.transparent,
              child: InkWell(
                borderRadius: BorderRadius.circular(8),
                onTap: isCurrent ? null : () => _fetchSuggestions(page: pageNum),
                child: Container(
                  width: 36,
                  height: 36,
                  alignment: Alignment.center,
                  decoration: BoxDecoration(
                    color: isCurrent ? const Color(0xFF2563EB) : Colors.white,
                    borderRadius: BorderRadius.circular(8),
                    border: Border.all(
                      color: isCurrent ? const Color(0xFF2563EB) : const Color(0xFFE2E8F0),
                    ),
                  ),
                  child: Text(
                    '$pageNum',
                    style: TextStyle(
                      color: isCurrent ? Colors.white : const Color(0xFF0F172A),
                      fontWeight: isCurrent ? FontWeight.bold : FontWeight.w500,
                      fontSize: 13,
                    ),
                  ),
                ),
              ),
            );
          }),
        ),

        const SizedBox(width: 8),

        // Next Button
        IconButton(
          icon: const Icon(Icons.chevron_right),
          color: const Color(0xFF0F172A),
          disabledColor: const Color(0xFFCBD5E1),
          onPressed: _currentPage < _totalPages ? () => _fetchSuggestions(page: _currentPage + 1) : null,
        ),
      ],
    );
  }
}
