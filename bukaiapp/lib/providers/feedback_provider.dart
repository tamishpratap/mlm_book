import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../models/feedback_model.dart';

class FeedbackProvider extends ChangeNotifier {
  bool _isLoading = false;
  List<FeedbackModel> _feedbacks = [];

  bool get isLoading => _isLoading;
  List<FeedbackModel> get feedbacks => _feedbacks;

  Future<void> fetchFeedbackList() async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiClient.get('/feedback');
    _isLoading = false;

    if (res.success && res.data is Map && res.data['feedbacks'] is List) {
      final list = res.data['feedbacks'] as List;
      _feedbacks = list.map((e) => FeedbackModel.fromJson(e as Map<String, dynamic>)).toList();
    }
    notifyListeners();
  }

  Future<bool> submitFeedback({
    required String type,
    required String subject,
    required String message,
  }) async {
    final res = await ApiClient.post('/feedback', {
      'type': type,
      'subject': subject.trim(),
      'message': message.trim(),
    });

    if (res.success) {
      await fetchFeedbackList();
      return true;
    }
    return false;
  }
}
