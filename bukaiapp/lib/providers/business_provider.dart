import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../models/campaign_model.dart';

class BusinessProvider extends ChangeNotifier {
  bool _isLoading = false;
  List<Map<String, dynamic>> _myPages = [];
  List<Map<String, dynamic>> _explorePages = [];
  List<CampaignModel> _sponsoredAds = [];
  List<Map<String, dynamic>> _events = [];

  bool get isLoading => _isLoading;
  List<Map<String, dynamic>> get myPages => _myPages;
  List<Map<String, dynamic>> get explorePages => _explorePages;
  List<CampaignModel> get sponsoredAds => _sponsoredAds;
  List<Map<String, dynamic>> get events => _events;

  // Fetch Business Pages
  Future<void> fetchBusinessPages() async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiClient.get('/business-pages');
    _isLoading = false;

    if (res.success && res.data is Map) {
      _myPages = (res.data['my_pages'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      _explorePages = (res.data['explore_pages'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    }
    notifyListeners();
  }

  // Create Business Page
  Future<bool> createBusinessPage({required String name, String? bio, String? phone}) async {
    final res = await ApiClient.post('/business-pages', {
      'name': name.trim(),
      'bio': bio?.trim(),
      'phone': phone?.trim(),
    });
    if (res.success) {
      await fetchBusinessPages();
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
