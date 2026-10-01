import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../models/business_page_model.dart';
import '../models/campaign_model.dart';

class BusinessProvider extends ChangeNotifier {
  bool _isLoading = false;
  bool _isRefreshing = false;
  String? _lastError;

  List<BusinessPageModel> _pages = [];
  List<BusinessPageModel> _myPages = [];
  int _myCount = 0;
  List<String> _categories = [
    'Technology & IT',
    'Financial Services',
    'E-Commerce & Retail',
    'Health & Wellness',
    'Real Estate',
    'Education & Training',
    'Marketing & Advertising',
    'Direct Selling & MLM',
    'Entertainment',
    'Food & Beverages',
    'Travel & Tourism',
    'Consulting',
  ];
  List<Map<String, dynamic>> _countries = [];

  String _selectedTab = 'all';
  String _selectedCategory = '';
  String _searchQuery = '';
  int _currentPage = 1;
  int _lastPage = 1;

  List<CampaignModel> _sponsoredAds = [];
  List<Map<String, dynamic>> _events = [];

  bool get isLoading => _isLoading;
  bool get isRefreshing => _isRefreshing;
  String? get lastError => _lastError;

  List<BusinessPageModel> get pages => _pages;
  List<BusinessPageModel> get myPages => _myPages;
  int get myCount => _myCount;
  List<String> get categories => _categories;
  List<Map<String, dynamic>> get countries => _countries;
  String get selectedTab => _selectedTab;
  String get selectedCategory => _selectedCategory;
  String get searchQuery => _searchQuery;
  int get currentPage => _currentPage;
  int get lastPage => _lastPage;

  List<CampaignModel> get sponsoredAds => _sponsoredAds;
  List<Map<String, dynamic>> get events => _events;

  // Fetch Business Pages with filtering
  Future<void> fetchBusinessPages({
    String? tab,
    String? category,
    String? search,
    int page = 1,
    bool isRefresh = false,
  }) async {
    if (tab != null) _selectedTab = tab;
    if (category != null) _selectedCategory = category;
    if (search != null) _searchQuery = search;
    _currentPage = page;

    if (isRefresh) {
      _isRefreshing = true;
    } else {
      _isLoading = true;
    }
    _lastError = null;
    notifyListeners();

    final queryParams = <String, String>{
      'tab': _selectedTab,
      'page': _currentPage.toString(),
    };
    if (_selectedCategory.isNotEmpty && _selectedCategory != 'All') {
      queryParams['category'] = _selectedCategory;
    }
    if (_searchQuery.trim().isNotEmpty) {
      queryParams['search'] = _searchQuery.trim();
    }

    final queryString = queryParams.entries.map((e) => '${e.key}=${Uri.encodeComponent(e.value)}').join('&');
    final res = await ApiClient.get('/business-pages?$queryString');

    _isLoading = false;
    _isRefreshing = false;

    if (res.success && res.data is Map) {
      final data = res.data as Map<String, dynamic>;
      
      // Parse main paginated pages list
      if (data['pages'] is Map && data['pages']['data'] is List) {
        final list = data['pages']['data'] as List;
        _pages = list.map((e) => BusinessPageModel.fromJson(e as Map<String, dynamic>)).toList();
        _lastPage = data['pages']['last_page'] is int ? data['pages']['last_page'] : 1;
      } else if (data['pages'] is List) {
        final list = data['pages'] as List;
        _pages = list.map((e) => BusinessPageModel.fromJson(e as Map<String, dynamic>)).toList();
      }

      // Parse my_pages
      if (data['my_pages'] is List) {
        final myList = data['my_pages'] as List;
        _myPages = myList.map((e) => BusinessPageModel.fromJson(e as Map<String, dynamic>)).toList();
      }

      if (data['my_count'] != null) {
        _myCount = int.tryParse(data['my_count'].toString()) ?? _myPages.length;
      } else {
        _myCount = _myPages.length;
      }

      // Parse categories
      if (data['categories'] is List && (data['categories'] as List).isNotEmpty) {
        _categories = (data['categories'] as List).map((e) => e.toString()).toList();
      }

      // Parse countries
      if (data['countries'] is List && (data['countries'] as List).isNotEmpty) {
        _countries = (data['countries'] as List).cast<Map<String, dynamic>>();
      }
    } else {
      _lastError = res.message ?? 'Failed to load business pages.';
    }

    notifyListeners();
  }

  // Create Business Page
  Future<bool> createBusinessPage({
    required String name,
    String? username,
    required String category,
    String? description,
    String? website,
    required String email,
    String? phoneCountryCode,
    required String phoneNumber,
    String? address,
    String? city,
    String? state,
    required String country,
    String visibility = 'public',
    List<int>? logoBytes,
    List<int>? coverBytes,
  }) async {
    final fields = <String, String>{
      'page_name': name.trim(),
      'category': category.trim(),
      'visibility': visibility,
      'email': email.trim(),
      'phone_number': phoneNumber.trim(),
      'country': country.trim(),
    };

    if (phoneCountryCode != null && phoneCountryCode.isNotEmpty) {
      fields['phone_country_code'] = phoneCountryCode.trim();
    }
    if (username != null && username.trim().isNotEmpty) {
      fields['page_username'] = username.trim();
    }
    if (description != null && description.trim().isNotEmpty) {
      fields['description'] = description.trim();
    }
    if (website != null && website.trim().isNotEmpty) {
      fields['website'] = website.trim();
    }
    if (address != null && address.trim().isNotEmpty) {
      fields['address'] = address.trim();
    }
    if (city != null && city.trim().isNotEmpty) {
      fields['city'] = city.trim();
    }
    if (state != null && state.trim().isNotEmpty) {
      fields['state'] = state.trim();
    }

    ApiResponse res;
    if (logoBytes != null || coverBytes != null) {
      final files = <String, List<int>>{};
      if (logoBytes != null) files['logo'] = logoBytes;
      if (coverBytes != null) files['cover_photo'] = coverBytes;
      res = await ApiClient.postMultiFiles('/business-pages', fields: fields, files: files);
    } else {
      res = await ApiClient.post('/business-pages', fields);
    }

    if (res.success) {
      await fetchBusinessPages(isRefresh: true);
      return true;
    } else {
      _lastError = res.message ?? 'Failed to create business page.';
      notifyListeners();
      return false;
    }
  }

  // Delete Business Page
  Future<bool> deleteBusinessPage(String slug) async {
    final res = await ApiClient.delete('/business-pages/$slug');
    if (res.success) {
      _pages.removeWhere((p) => p.slug == slug);
      _myPages.removeWhere((p) => p.slug == slug);
      _myCount = (_myCount - 1).clamp(0, 99999);
      notifyListeners();
      return true;
    }
    return false;
  }

  // Fetch Sponsored Ads Feed (with rewards)
  Future<void> fetchSponsoredAds() async {
    final res = await ApiClient.get('/sponsored-feed');
    if (res.success && res.data is Map && res.data['sponsored'] is List) {
      final list = res.data['sponsored'] as List;
      _sponsoredAds = list.map((e) => CampaignModel.fromJson(e as Map<String, dynamic>)).toList();
      notifyListeners();
    }
  }

  // Claim Reward for ad visit
  Future<Map<String, dynamic>> claimAdReward(CampaignModel ad) async {
    final res = await ApiClient.post('/sponsored/${ad.id}/claim');
    if (res.success) {
      ad.isClaimed = true;
      notifyListeners();
      return {
        'success': true,
        'message': res.message ?? 'Reward credited to your wallet!',
      };
    } else {
      return {
        'success': false,
        'message': res.message ?? 'Reward claim failed.',
      };
    }
  }

  // Fetch Events
  Future<void> fetchEvents() async {
    final res = await ApiClient.get('/events');
    if (res.success && res.data is Map && res.data['events'] is Map && res.data['events']['data'] is List) {
      _events = (res.data['events']['data'] as List).cast<Map<String, dynamic>>();
      notifyListeners();
    }
  }

  // Respond Event (Going / Interested)
  Future<void> respondEvent(int eventId, String response) async {
    await ApiClient.post('/events/$eventId/respond', {'response': response});
    await fetchEvents();
  }
}
