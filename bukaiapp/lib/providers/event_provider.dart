import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../models/event_model.dart';

class EventProvider extends ChangeNotifier {
  bool _isLoading = false;
  List<EventModel> _events = [];

  bool get isLoading => _isLoading;
  List<EventModel> get events => _events;

  Future<void> fetchEvents() async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiClient.get('/events');
    _isLoading = false;

    if (res.success && res.data is Map && res.data['events'] != null) {
      final list = (res.data['events']['data'] as List?) ?? [];
      _events = list.map((e) => EventModel.fromJson(e as Map<String, dynamic>)).toList();
    }
    notifyListeners();
  }

  Future<bool> createEvent({
    required String title,
    required String description,
    required String location,
    required String startTime,
  }) async {
    final res = await ApiClient.post('/events', {
      'title': title.trim(),
      'description': description.trim(),
      'location': location.trim(),
      'start_time': startTime,
    });

    if (res.success) {
      await fetchEvents();
      return true;
    }
    return false;
  }

  Future<bool> respondEvent(EventModel event, String responseType) async {
    final prevResponse = event.myResponse;
    event.myResponse = responseType;
    notifyListeners();

    final res = await ApiClient.post('/events/${event.id}/respond', {
      'response': responseType,
    });

    if (!res.success) {
      event.myResponse = prevResponse;
      notifyListeners();
      return false;
    }
    return true;
  }
}
