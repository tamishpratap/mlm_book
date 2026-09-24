class NotificationModel {
  final String id;
  final String title;
  final String message;
  final String type;
  bool isRead;
  final String createdAt;

  NotificationModel({
    required this.id,
    required this.title,
    required this.message,
    required this.type,
    required this.isRead,
    required this.createdAt,
  });

  factory NotificationModel.fromJson(Map<String, dynamic> json) {
    return NotificationModel(
      id: json['id']?.toString() ?? '',
      title: json['title']?.toString() ?? 'Notification',
      message: json['message']?.toString() ?? '',
      type: json['type']?.toString() ?? 'general',
      isRead: json['is_read'] == true,
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}
