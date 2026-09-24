import 'package:flutter/material.dart';
import '../core/api_client.dart';

class AdminProvider extends ChangeNotifier {
  bool _isLoading = false;
  Map<String, dynamic> _metrics = {};
  List<Map<String, dynamic>> _members = [];
  List<Map<String, dynamic>> _campaigns = [];
  List<Map<String, dynamic>> _deposits = [];
  List<Map<String, dynamic>> _reports = [];

  bool get isLoading => _isLoading;
  Map<String, dynamic> get metrics => _metrics;
  List<Map<String, dynamic>> get members => _members;
  List<Map<String, dynamic>> get campaigns => _campaigns;
  List<Map<String, dynamic>> get deposits => _deposits;
  List<Map<String, dynamic>> get reports => _reports;

  // Fetch Dashboard Metrics
  Future<void> fetchDashboard() async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiClient.get('/admin/dashboard');
    _isLoading = false;

    if (res.success && res.data is Map && res.data['metrics'] != null) {
      _metrics = res.data['metrics'] as Map<String, dynamic>;
    }
    notifyListeners();
  }

  // Fetch Members
  Future<void> fetchMembers({String? filter, String? query}) async {
    _isLoading = true;
    notifyListeners();

    String url = '/admin/members?';
    if (filter != null) url += 'filter=$filter&';
    if (query != null && query.isNotEmpty) url += 'q=$query&';

    final res = await ApiClient.get(url);
    _isLoading = false;

    if (res.success && res.data is Map && res.data['members'] is Map && res.data['members']['data'] is List) {
      _members = (res.data['members']['data'] as List).cast<Map<String, dynamic>>();
    }
    notifyListeners();
  }

  // Toggle Block Member
  Future<bool> toggleBlockMember(int memberId) async {
    final res = await ApiClient.post('/admin/members/$memberId/toggle-block');
    if (res.success) {
      await fetchMembers();
      return true;
    }
    return false;
  }

  // Fetch Campaigns for review
  Future<void> fetchCampaigns({String? status}) async {
    _isLoading = true;
    notifyListeners();

    String url = '/admin/campaigns';
    if (status != null) url += '?status=$status';

    final res = await ApiClient.get(url);
    _isLoading = false;

    if (res.success && res.data is Map && res.data['campaigns'] is Map && res.data['campaigns']['data'] is List) {
      _campaigns = (res.data['campaigns']['data'] as List).cast<Map<String, dynamic>>();
    }
    notifyListeners();
  }

  // Review Campaign (Approve or Reject)
  Future<bool> reviewCampaign(int campaignId, String action, [String? reason]) async {
    final Map<String, dynamic> payload = {'action': action};
    if (reason != null && reason.isNotEmpty) {
      payload['reason'] = reason;
    }
    final res = await ApiClient.post('/admin/campaigns/$campaignId/review', payload);
    if (res.success) {
      await fetchCampaigns();
      return true;
    }
    return false;
  }

  // Fetch Deposits
  Future<void> fetchDeposits({String? status}) async {
    _isLoading = true;
    notifyListeners();

    String url = '/admin/deposits';
    if (status != null) url += '?status=$status';

    final res = await ApiClient.get(url);
    _isLoading = false;

    if (res.success && res.data is Map && res.data['deposits'] is Map && res.data['deposits']['data'] is List) {
      _deposits = (res.data['deposits']['data'] as List).cast<Map<String, dynamic>>();
    }
    notifyListeners();
  }

  // Manually Approve Deposit
  Future<bool> approveDeposit(int depositId) async {
    final res = await ApiClient.post('/admin/deposits/$depositId/approve');
    if (res.success) {
      await fetchDeposits();
      return true;
    }
    return false;
  }

  // Re-verify Deposit on-chain (BSC RPC)
  Future<Map<String, dynamic>> reverifyDeposit(int depositId) async {
    final res = await ApiClient.post('/admin/deposits/$depositId/reverify');
    await fetchDeposits();
    return {
      'success': res.success,
      'message': res.message ?? 'Verification processed.',
    };
  }

  // Fetch Reports
  Future<void> fetchReports() async {
    final res = await ApiClient.get('/admin/reports');
    if (res.success && res.data is Map && res.data['reports'] is Map && res.data['reports']['data'] is List) {
      _reports = (res.data['reports']['data'] as List).cast<Map<String, dynamic>>();
      notifyListeners();
    }
  }
}
