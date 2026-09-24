import 'package:flutter/material.dart';
import '../core/api_client.dart';

class SearchProvider extends ChangeNotifier {
  bool _isLoading = false;
  String _query = '';
  String _activeType = 'all'; // 'all', 'members', 'communities', 'pages', 'posts'

  List<Map<String, dynamic>> _members = [];
  List<Map<String, dynamic>> _communities = [];
  List<Map<String, dynamic>> _pages = [];
  List<Map<String, dynamic>> _posts = [];

  bool get isLoading => _isLoading;
  String get query => _query;
  String get activeType => _activeType;

  List<Map<String, dynamic>> get members => _members;
  List<Map<String, dynamic>> get communities => _communities;
  List<Map<String, dynamic>> get pages => _pages;
  List<Map<String, dynamic>> get posts => _posts;

  Future<void> search(String query, {String type = 'all'}) async {
    _query = query.trim();
    _activeType = type;

    if (_query.isEmpty) {
      _members = [];
      _communities = [];
      _pages = [];
      _posts = [];
      notifyListeners();
      return;
    }

    _isLoading = true;
    notifyListeners();

    final res = await ApiClient.get('/search?q=${Uri.encodeComponent(_query)}&type=$type');
    _isLoading = false;

    if (res.success && res.data is Map) {
      _members = (res.data['members'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      _communities = (res.data['communities'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      _pages = (res.data['pages'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      _posts = (res.data['posts'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    }
    notifyListeners();
  }
}
