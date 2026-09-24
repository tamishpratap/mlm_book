import '../core/constants.dart';
import '../core/api_client.dart';

class StoryGroupModel {
  final int memberId;
  final String authorName;
  final String? authorAvatar;
  final int storiesCount;
  final List<StoryItemModel> stories;

  StoryGroupModel({
    required this.memberId,
    required this.authorName,
    this.authorAvatar,
    required this.storiesCount,
    required this.stories,
  });

  factory StoryGroupModel.fromJson(Map<String, dynamic> json) {
    final list = (json['stories'] as List?)?.map((e) => StoryItemModel.fromJson(e as Map<String, dynamic>)).toList() ?? [];
    return StoryGroupModel(
      memberId: json['member_id'] is int ? json['member_id'] : int.tryParse(json['member_id']?.toString() ?? '0') ?? 0,
      authorName: json['author_name']?.toString() ?? 'Member',
      authorAvatar: json['author_avatar']?.toString(),
      storiesCount: json['stories_count'] is int ? json['stories_count'] : list.length,
      stories: list,
    );
  }
}

class StoryItemModel {
  final int id;
  final String mediaUrl;
  final String mediaType;
  final String? caption;
  final String createdAt;

  StoryItemModel({
    required this.id,
    required this.mediaUrl,
    required this.mediaType,
    this.caption,
    required this.createdAt,
  });

  String get resolvedMediaUrl => AppConstants.resolveMediaUrl(mediaUrl, ApiClient.baseUrl) ?? mediaUrl;

  factory StoryItemModel.fromJson(Map<String, dynamic> json) {
    return StoryItemModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      mediaUrl: json['media_url']?.toString() ?? '',
      mediaType: json['media_type']?.toString() ?? 'image',
      caption: json['caption']?.toString(),
      createdAt: json['created_at']?.toString() ?? '',
    );
  }
}
