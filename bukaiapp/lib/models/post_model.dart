import '../core/constants.dart';
import '../core/api_client.dart';

class PostModel {
  final int id;
  final String? body;
  final String? mediaType; // 'image' or 'video'
  final String? mediaUrl;
  final String createdAt;
  int likesCount;
  int commentsCount;
  int sharesCount;
  int savesCount;
  bool isLiked;
  bool isSaved;
  String? userReaction;
  final AuthorModel author;
  List<Map<String, dynamic>> recentComments;

  PostModel({
    required this.id,
    this.body,
    this.mediaType,
    this.mediaUrl,
    required this.createdAt,
    required this.likesCount,
    required this.commentsCount,
    this.sharesCount = 0,
    this.savesCount = 0,
    required this.isLiked,
    this.isSaved = false,
    this.userReaction,
    required this.author,
    this.recentComments = const [],
  });

  String get authorName => author.name;
  String get createdAtFormatted => createdAt;
  String? get resolvedMediaUrl => AppConstants.resolveMediaUrl(mediaUrl, ApiClient.baseUrl);

  static const Map<String, String> reactionEmojis = {
    'like': '👍',
    'love': '❤️',
    'haha': '😂',
    'wow': '😮',
    'sad': '😢',
    'angry': '😡',
  };

  static const Map<String, String> reactionLabels = {
    'like': 'Like',
    'love': 'Love',
    'haha': 'Haha',
    'wow': 'Wow',
    'sad': 'Sad',
    'angry': 'Angry',
  };

  static const Map<String, int> reactionColors = {
    'like': 0xFF2563EB,
    'love': 0xFFEF4444,
    'haha': 0xFFF59E0B,
    'wow': 0xFFF59E0B,
    'sad': 0xFFF59E0B,
    'angry': 0xFFEA580C,
  };

  String get activeReactionEmoji => userReaction != null ? (reactionEmojis[userReaction] ?? '👍') : '👍';
  String get activeReactionLabel => userReaction != null ? (reactionLabels[userReaction] ?? 'Like') : 'Like';
  int get activeReactionColor => userReaction != null ? (reactionColors[userReaction] ?? 0xFF2563EB) : 0xFF475569;

  factory PostModel.fromJson(Map<String, dynamic> json) {
    final isSaved = json['is_saved'] == true;
    final mediaUrl = json['media_url']?.toString() ?? json['video_url']?.toString();
    final mediaType = json['media_type']?.toString() ?? (json['video_url'] != null ? 'video' : null);

    return PostModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      body: json['body']?.toString() ?? json['title']?.toString(),
      mediaType: mediaType,
      mediaUrl: mediaUrl,
      createdAt: json['created_at']?.toString() ?? '',
      likesCount: json['likes_count'] is int ? json['likes_count'] : int.tryParse(json['likes_count']?.toString() ?? '0') ?? 0,
      commentsCount: json['comments_count'] is int ? json['comments_count'] : int.tryParse(json['comments_count']?.toString() ?? '0') ?? 0,
      sharesCount: json['shares_count'] is int ? json['shares_count'] : int.tryParse(json['shares_count']?.toString() ?? '0') ?? 0,
      savesCount: json['saves_count'] is int ? json['saves_count'] : int.tryParse(json['saves_count']?.toString() ?? '0') ?? (isSaved ? 1 : 0),
      isLiked: json['is_liked'] == true,
      isSaved: isSaved,
      userReaction: json['user_reaction']?.toString(),
      author: AuthorModel.fromJson(json['author'] is Map ? json['author'] as Map<String, dynamic> : {}),
      recentComments: (json['recent_comments'] as List?)?.cast<Map<String, dynamic>>() ??
          (json['comments'] as List?)?.cast<Map<String, dynamic>>() ??
          [],
    );
  }
}

class AuthorModel {
  final int id;
  final String name;
  final String userId;
  final String? avatarUrl;
  final bool isVerified;

  AuthorModel({
    required this.id,
    required this.name,
    required this.userId,
    this.avatarUrl,
    required this.isVerified,
  });

  factory AuthorModel.fromJson(Map<String, dynamic> json) {
    return AuthorModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      name: json['name']?.toString() ?? 'Member',
      userId: json['user_id']?.toString() ?? '',
      avatarUrl: json['avatar_url']?.toString(),
      isVerified: json['is_verified'] == true,
    );
  }
}
