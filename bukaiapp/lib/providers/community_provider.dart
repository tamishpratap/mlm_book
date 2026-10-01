import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../models/community_model.dart';

class CommunityProvider extends ChangeNotifier {
  bool _isLoading = false;
  List<CommunityModel> _communities = [];
  String _activeTab = 'all'; // 'all', 'joined', 'my', 'discover'
  int _joinedCount = 0;
  int _myCount = 0;
  List<String> _categories = [
    'Technology',
    'Business',
    'Crypto',
    'Marketing',
    'Gaming',
    'Education',
    'Lifestyle',
    'Health & Wellness',
    'E-Commerce',
    'MLM & Direct Sales',
  ];
  Map<String, dynamic> _visibilities = {
    'public': 'Public (Anyone can join)',
    'private': 'Private (Requires approval)',
    'invite_only': 'Invite Only',
  };

  String _searchQuery = '';
  String? _selectedCategory;

  bool get isLoading => _isLoading;
  List<CommunityModel> get communities => _communities;
  String get activeTab => _activeTab;
  int get joinedCount => _joinedCount;
  int get myCount => _myCount;
  List<String> get categories => _categories;
  Map<String, dynamic> get visibilities => _visibilities;
  String get searchQuery => _searchQuery;
  String? get selectedCategory => _selectedCategory;

  Future<void> fetchCommunities({
    String? tab,
    String? search,
    String? category,
    bool clearFilters = false,
  }) async {
    if (tab != null) _activeTab = tab;
    if (search != null) _searchQuery = search;
    if (clearFilters) {
      _searchQuery = '';
      _selectedCategory = null;
    } else if (category != null) {
      _selectedCategory = (category.isEmpty || category == 'All') ? null : category;
    }

    _isLoading = true;
    notifyListeners();

    final queryParams = <String>[];
    queryParams.add('tab=$_activeTab');
    if (_searchQuery.trim().isNotEmpty) {
      queryParams.add('search=${Uri.encodeComponent(_searchQuery.trim())}');
    }
    if (_selectedCategory != null && _selectedCategory!.isNotEmpty && _selectedCategory != 'All') {
      queryParams.add('category=${Uri.encodeComponent(_selectedCategory!)}');
    }

    final queryStr = queryParams.isNotEmpty ? '?${queryParams.join('&')}' : '';
    final res = await ApiClient.get('/communities$queryStr');
    _isLoading = false;

    if (res.success && res.data is Map) {
      final data = res.data as Map<String, dynamic>;

      if (data['communities'] is Map && data['communities']['data'] != null) {
        final list = (data['communities']['data'] as List?) ?? [];
        _communities = list.map((e) => CommunityModel.fromJson(e as Map<String, dynamic>)).toList();
      } else if (data['communities'] is List) {
        final list = (data['communities'] as List);
        _communities = list.map((e) => CommunityModel.fromJson(e as Map<String, dynamic>)).toList();
      }

      if (data['joined_count'] is int) {
        _joinedCount = data['joined_count'] as int;
      } else if (data['joined_communities'] is List) {
        _joinedCount = (data['joined_communities'] as List).length;
      }

      if (data['my_count'] is int) {
        _myCount = data['my_count'] as int;
      } else if (data['my_communities'] is List) {
        _myCount = (data['my_communities'] as List).length;
      }

      if (data['categories'] is List && (data['categories'] as List).isNotEmpty) {
        _categories = (data['categories'] as List).map((e) => e.toString()).toList();
      }

      if (data['visibilities'] is Map) {
        _visibilities = Map<String, dynamic>.from(data['visibilities'] as Map);
      }
    }
    notifyListeners();
  }

  Future<Map<String, dynamic>> createCommunity({
    required String name,
    String? description,
    String category = 'Technology',
    String visibility = 'public',
    String? rules,
    String? tags,
    List<int>? logoBytes,
    String? logoFileName,
    List<int>? coverBytes,
    String? coverFileName,
  }) async {
    final fields = <String, String>{
      'name': name.trim(),
      if (description != null && description.trim().isNotEmpty) 'description': description.trim(),
      'category': category,
      'visibility': visibility,
      if (rules != null && rules.trim().isNotEmpty) 'rules': rules.trim(),
      if (tags != null && tags.trim().isNotEmpty) 'tags': tags.trim(),
    };

    ApiResponse res;
    if (logoBytes != null && logoFileName != null) {
      res = await ApiClient.postMultipart(
        '/communities',
        fields: fields,
        fileBytes: logoBytes,
        fileName: logoFileName,
        fileField: 'logo',
      );
    } else {
      res = await ApiClient.post('/communities', fields);
    }

    if (res.success) {
      await fetchCommunities(tab: _activeTab);
      return {'success': true, 'message': res.message ?? 'Community created successfully!'};
    }
    return {'success': false, 'message': res.message ?? 'Failed to create community.'};
  }

  Future<bool> toggleJoin(CommunityModel community) async {
    final prevJoined = community.isMember;
    community.isMember = !prevJoined;
    notifyListeners();

    final res = await ApiClient.post('/communities/${community.slug}/join');
    if (!res.success) {
      community.isMember = prevJoined;
      notifyListeners();
      return false;
    }
    // Refresh list count in background
    fetchCommunities(tab: _activeTab);
    return true;
  }
}

