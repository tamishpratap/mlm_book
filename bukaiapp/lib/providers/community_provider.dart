import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../models/community_model.dart';

class CommunityProvider extends ChangeNotifier {
  bool _isLoading = false;
  List<CommunityModel> _communities = [];
  String _activeTab = 'all'; // 'all', 'joined', 'discover'

  bool get isLoading => _isLoading;
  List<CommunityModel> get communities => _communities;
  String get activeTab => _activeTab;

  Future<void> fetchCommunities({String tab = 'all'}) async {
    _activeTab = tab;
    _isLoading = true;
    notifyListeners();

    final res = await ApiClient.get('/communities?tab=$tab');
    _isLoading = false;

    if (res.success && res.data is Map && res.data['communities'] != null) {
      final list = (res.data['communities']['data'] as List?) ?? [];
      _communities = list.map((e) => CommunityModel.fromJson(e as Map<String, dynamic>)).toList();
    }
    notifyListeners();
  }

  Future<bool> createCommunity({
    required String name,
    String? description,
    String category = 'General',
    String visibility = 'public',
  }) async {
    final res = await ApiClient.post('/communities', {
      'name': name.trim(),
      if (description != null) 'description': description.trim(),
      'category': category,
      'visibility': visibility,
    });

    if (res.success) {
      await fetchCommunities(tab: _activeTab);
      return true;
    }
    return false;
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
    return true;
  }
}
