class FeedbackModel {
  final int id;
  final String type;
  final String subject;
  final String message;
  final String status; // 'new', 'in_progress', 'resolved', 'closed'
  final String? adminResponse;
  final String createdAt;

  FeedbackModel({
    required this.id,
    required this.type,
    required this.subject,
    required this.message,
    required this.status,
    this.adminResponse,
    required this.createdAt,
  });

  factory FeedbackModel.fromJson(Map<String, dynamic> json) {
    return FeedbackModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      type: json['type']?.toString() ?? 'feedback',
      subject: json['subject']?.toString() ?? '',
      message: json['message']?.toString() ?? '',
      status: json['status']?.toString() ?? 'new',
      adminResponse: json['admin_response']?.toString(),
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}
