import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../models/post_model.dart';
import '../models/story_model.dart';

class FeedProvider extends ChangeNotifier {
  bool _isLoadingFeed = false;
  bool _isLoadingStories = false;
  bool _isCreatingPost = false;
  List<PostModel> _posts = [];
  List<StoryGroupModel> _storyGroups = [];

  bool get isLoadingFeed => _isLoadingFeed;
  bool get isLoadingStories => _isLoadingStories;
  bool get isCreatingPost => _isCreatingPost;
  List<PostModel> get posts => _posts;
  List<StoryGroupModel> get storyGroups => _storyGroups;
  String? _lastError;
  String? get lastError => _lastError;

  // Fetch Feed Posts
  Future<void> fetchFeed({bool refresh = false}) async {
    if (_posts.isNotEmpty && !refresh) return;

    _isLoadingFeed = true;
    notifyListeners();

    final res = await ApiClient.get('/feed');
    _isLoadingFeed = false;

    if (res.success && res.data is Map && res.data['posts'] is List) {
      final list = res.data['posts'] as List;
      _posts = list.map((e) => PostModel.fromJson(e as Map<String, dynamic>)).toList();
    }
    notifyListeners();
  }

  // Fetch Stories
  Future<void> fetchStories() async {
    _isLoadingStories = true;
    notifyListeners();

    final res = await ApiClient.get('/stories');
    _isLoadingStories = false;

    if (res.success && res.data is Map && res.data['stories_groups'] is List) {
      final list = res.data['stories_groups'] as List;
      _storyGroups = list.map((e) => StoryGroupModel.fromJson(e as Map<String, dynamic>)).toList();
    }
    notifyListeners();
  }

  // Like / React to Post with custom reaction type (like, love, haha, wow, sad, angry)
  Future<void> toggleLike(PostModel post, {String reaction = 'like'}) async {
    final prevLiked = post.isLiked;
    final prevReaction = post.userReaction;
    final prevCount = post.likesCount;

    final isUnreacting = prevLiked && prevReaction == reaction;

    // Optimistic UI update
    post.isLiked = !isUnreacting;
    post.userReaction = isUnreacting ? null : reaction;
    if (post.isLiked) {
      if (!prevLiked) post.likesCount = prevCount + 1;
    } else {
      if (prevCount > 0) post.likesCount = prevCount - 1;
    }
    notifyListeners();

    final res = await ApiClient.post('/posts/${post.id}/react', {'reaction': reaction, 'type': reaction});
    if (!res.success) {
      // Rollback on failure
      post.isLiked = prevLiked;
      post.userReaction = prevReaction;
      post.likesCount = prevCount;
      notifyListeners();
    } else if (res.data is Map) {
      final data = res.data as Map;
      if (data['reacted'] != null) {
        post.isLiked = data['reacted'] == true;
      } else if (data['liked'] != null) {
        post.isLiked = data['liked'] == true;
      }
      post.userReaction = data['user_reaction']?.toString();
      if (data['likes_count'] != null) {
        post.likesCount = data['likes_count'] is int
            ? data['likes_count']
            : int.tryParse(data['likes_count'].toString()) ?? post.likesCount;
      }
      notifyListeners();
    }
  }

  // Share Post to personal feed
  Future<bool> sharePost(PostModel post, {String? message}) async {
    post.sharesCount++;
    notifyListeners();

    final res = await ApiClient.post('/posts/${post.id}/share', {
      if (message != null && message.trim().isNotEmpty) 'message': message.trim(),
    });
    if (res.success) {
      if (res.data is Map && res.data['shares_count'] != null) {
        post.sharesCount = res.data['shares_count'] is int
            ? res.data['shares_count']
            : int.tryParse(res.data['shares_count'].toString()) ?? post.sharesCount;
      }
      notifyListeners();
      return true;
    } else {
      if (post.sharesCount > 0) post.sharesCount--;
      notifyListeners();
      return false;
    }
  }

  // Add new post with optional photo / video file or URL
  Future<bool> createPost({
    required String body,
    List<int>? fileBytes,
    String? fileName,
    String? mediaUrl,
    String? mediaType,
  }) async {
    _isCreatingPost = true;
    notifyListeners();

    ApiResponse res;
    if (fileBytes != null && fileName != null) {
      final fields = <String, String>{'body': body};
      if (mediaType != null) fields['media_type'] = mediaType;

      res = await ApiClient.postMultipart(
        '/posts',
        fields: fields,
        fileBytes: fileBytes,
        fileName: fileName,
        fileField: 'media',
      );
    } else {
      final data = <String, dynamic>{'body': body};
      if (mediaUrl != null && mediaUrl.isNotEmpty) data['media_url'] = mediaUrl;
      if (mediaType != null) data['media_type'] = mediaType;

      res = await ApiClient.post('/posts', data);
    }

    _isCreatingPost = false;

    if (res.success && res.data is Map && res.data['post'] != null) {
      _lastError = null;
      final newPost = PostModel.fromJson(res.data['post'] as Map<String, dynamic>);
      _posts.insert(0, newPost);
      notifyListeners();
      return true;
    }

    _lastError = res.message ?? 'Failed to publish post. Please try again.';
    notifyListeners();
    return false;
  }

  // Add comment to a post
  Future<bool> addComment(PostModel post, String commentText) async {
    if (commentText.trim().isEmpty) return false;

    // Optimistic UI update
    post.commentsCount++;
    post.recentComments.insert(0, {
      'id': DateTime.now().millisecondsSinceEpoch,
      'comment': commentText.trim(),
      'created_at': 'Just now',
      'author': {'name': 'You'},
    });
    notifyListeners();

    final res = await ApiClient.post('/posts/${post.id}/comments', {'comment': commentText.trim()});
    if (!res.success) {
      // Rollback on failure
      if (post.commentsCount > 0) post.commentsCount--;
      if (post.recentComments.isNotEmpty) post.recentComments.removeAt(0);
      notifyListeners();
      return false;
    } else if (res.data is Map && res.data['comment'] != null) {
      if (post.recentComments.isNotEmpty) {
        post.recentComments[0] = Map<String, dynamic>.from(res.data['comment'] as Map);
      }
      notifyListeners();
    }
    return true;
  }

  // Create Story
  Future<bool> createStory({
    List<int>? fileBytes,
    String? fileName,
    String? mediaUrl,
    String mediaType = 'image',
    String? caption,
  }) async {
    ApiResponse res;
    if (fileBytes != null && fileName != null) {
      final fields = <String, String>{'media_type': mediaType};
      if (caption != null && caption.trim().isNotEmpty) {
        fields['caption'] = caption.trim();
      }

      res = await ApiClient.postMultipart(
        '/stories',
        fields: fields,
        fileBytes: fileBytes,
        fileName: fileName,
        fileField: 'media',
      );
    } else {
      final data = <String, dynamic>{'media_type': mediaType};
      if (mediaUrl != null) data['media_url'] = mediaUrl;
      if (caption != null && caption.trim().isNotEmpty) {
        data['caption'] = caption.trim();
      }

      res = await ApiClient.post('/stories', data);
    }

    if (res.success) {
      await fetchStories();
      return true;
    }
    _lastError = res.message ?? 'Failed to upload story.';
    notifyListeners();
    return false;
  }

  // Delete Story
  Future<bool> deleteStory(int storyId) async {
    final res = await ApiClient.delete('/stories/$storyId');
    if (res.success) {
      await fetchStories();
      return true;
    }
    _lastError = res.message ?? 'Failed to delete story.';
    notifyListeners();
    return false;
  }

  List<PostModel> _savedPosts = [];
  List<PostModel> get savedPosts => _savedPosts;

  // Fetch Saved Posts
  Future<void> fetchSavedPosts() async {
    final res = await ApiClient.get('/saved-posts');
    if (res.success && res.data is Map && res.data['posts'] is List) {
      final list = res.data['posts'] as List;
      _savedPosts = list.map((e) => PostModel.fromJson(e as Map<String, dynamic>)).toList();
      notifyListeners();
    }
  }

  // Toggle Save Post
  Future<bool> toggleSavePost(int postId) async {
    // Find post in _posts and _watchPosts and toggle
    for (var p in _posts) {
      if (p.id == postId) {
        p.isSaved = !p.isSaved;
        p.savesCount = p.isSaved ? p.savesCount + 1 : (p.savesCount > 0 ? p.savesCount - 1 : 0);
        break;
      }
    }
    for (var p in _watchPosts) {
      if (p.id == postId) {
        p.isSaved = !p.isSaved;
        p.savesCount = p.isSaved ? p.savesCount + 1 : (p.savesCount > 0 ? p.savesCount - 1 : 0);
        break;
      }
    }
    notifyListeners();

    final res = await ApiClient.post('/posts/$postId/save');
    if (res.success) {
      if (res.data is Map && res.data['saved'] != null) {
        final isSaved = res.data['saved'] == true;
        for (var p in _posts) {
          if (p.id == postId) {
            p.isSaved = isSaved;
            break;
          }
        }
        for (var p in _watchPosts) {
          if (p.id == postId) {
            p.isSaved = isSaved;
            break;
          }
        }
        notifyListeners();
      }
      await fetchSavedPosts();
      return true;
    }
    return false;
  }

  List<PostModel> _watchPosts = [];
  List<PostModel> get watchPosts => _watchPosts;
  List<Map<String, dynamic>> _watchVideos = [];
  List<Map<String, dynamic>> get watchVideos => _watchVideos;

  List<Map<String, dynamic>> _trendingVideos = [];
  List<Map<String, dynamic>> get trendingVideos => _trendingVideos;

  List<Map<String, dynamic>> _recentVideos = [];
  List<Map<String, dynamic>> get recentVideos => _recentVideos;

  int _myVideosCount = 0;
  int get myVideosCount => _myVideosCount;

  int _savedVideosCount = 0;
  int get savedVideosCount => _savedVideosCount;

  String _watchFilter = 'all';
  String get watchFilter => _watchFilter;

  // Fetch Watch Videos
  Future<void> fetchWatchVideos({String filter = 'all'}) async {
    _watchFilter = filter;
    final res = await ApiClient.get('/watch?filter=$filter');
    if (res.success && res.data is Map) {
      if (res.data['videos'] is List) {
        _watchVideos = (res.data['videos'] as List).cast<Map<String, dynamic>>();
      }
      final rawPosts = res.data['posts'] ?? res.data['videos'];
      if (rawPosts is List) {
        _watchPosts = rawPosts.map((e) => PostModel.fromJson(e as Map<String, dynamic>)).toList();
      }
      if (res.data['trending_videos'] is List) {
        _trendingVideos = (res.data['trending_videos'] as List).cast<Map<String, dynamic>>();
      }
      if (res.data['recent_videos'] is List) {
        _recentVideos = (res.data['recent_videos'] as List).cast<Map<String, dynamic>>();
      }
      if (res.data['my_videos_count'] != null) {
        _myVideosCount = int.tryParse(res.data['my_videos_count'].toString()) ?? 0;
      }
      if (res.data['saved_videos_count'] != null) {
        _savedVideosCount = int.tryParse(res.data['saved_videos_count'].toString()) ?? 0;
      }
      notifyListeners();
    }
  }
}
