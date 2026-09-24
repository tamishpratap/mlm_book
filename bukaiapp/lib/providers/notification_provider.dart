import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../models/notification_model.dart';

class NotificationProvider extends ChangeNotifier {
  bool _isLoading = false;
  int _unreadCount = 0;
  List<NotificationModel> _notifications = [];

  bool get isLoading => _isLoading;
  int get unreadCount => _unreadCount;
  List<NotificationModel> get notifications => _notifications;

  Future<void> fetchNotifications() async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiClient.get('/notifications');
    _isLoading = false;

    if (res.success && res.data is Map) {
      _unreadCount = res.data['unread_count'] is int ? res.data['unread_count'] : 0;
      final list = (res.data['notifications']['data'] as List?) ?? [];
      _notifications = list.map((e) => NotificationModel.fromJson(e as Map<String, dynamic>)).toList();
    }
    notifyListeners();
  }

  Future<void> markAsRead(NotificationModel notification) async {
    notification.isRead = true;
    if (_unreadCount > 0) _unreadCount--;
    notifyListeners();

    await ApiClient.post('/notifications/${notification.id}/read');
  }

  Future<void> markAllAsRead() async {
    for (var n in _notifications) {
      n.isRead = true;
    }
    _unreadCount = 0;
    notifyListeners();

    await ApiClient.post('/notifications/read-all');
  }
}
