class CommunityModel {
  final int id;
  final String name;
  final String slug;
  final String? description;
  final String category;
  final String visibility;
  final int membersCount;
  final String? avatarUrl;
  final String? bannerUrl;
  final String? ownerName;
  final int? ownerId;
  final String? ownerAvatarUrl;
  final String? rules;
  final String? tags;
  bool isMember;
  final bool isOwner;
  final bool isPending;
  final String memberRole;

  CommunityModel({
    required this.id,
    required this.name,
    required this.slug,
    this.description,
    required this.category,
    required this.visibility,
    required this.membersCount,
    this.avatarUrl,
    this.bannerUrl,
    this.ownerName,
    this.ownerId,
    this.ownerAvatarUrl,
    this.rules,
    this.tags,
    required this.isMember,
    required this.isOwner,
    this.isPending = false,
    this.memberRole = 'member',
  });

  factory CommunityModel.fromJson(Map<String, dynamic> json) {
    // Owner data extraction
    String? ownerName;
    int? ownerId;
    String? ownerAvatarUrl;
    if (json['owner'] is Map) {
      final owner = json['owner'] as Map<String, dynamic>;
      ownerName = owner['name']?.toString();
      ownerId = owner['id'] is int ? owner['id'] : int.tryParse(owner['id']?.toString() ?? '');
      ownerAvatarUrl = owner['profile_photo']?.toString() ?? owner['avatar_url']?.toString();
    } else {
      ownerName = json['owner_name']?.toString();
    }

    final logoPath = json['logo']?.toString() ?? json['avatar_url']?.toString();
    final coverPath = json['cover_photo']?.toString() ?? json['banner_url']?.toString();

    return CommunityModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      description: json['description']?.toString(),
      category: json['category']?.toString() ?? 'Technology',
      visibility: json['visibility']?.toString() ?? 'public',
      membersCount: json['members_count'] is int
          ? json['members_count']
          : int.tryParse(json['members_count']?.toString() ?? '0') ?? 0,
      avatarUrl: logoPath,
      bannerUrl: coverPath,
      ownerName: ownerName,
      ownerId: ownerId,
      ownerAvatarUrl: ownerAvatarUrl,
      rules: json['rules']?.toString(),
      tags: json['tags']?.toString(),
      isMember: json['is_member'] == true || json['is_member'] == 1,
      isOwner: json['is_owner'] == true || json['is_owner'] == 1,
      isPending: json['is_pending'] == true || json['is_pending'] == 1,
      memberRole: json['member_role']?.toString() ?? 'member',
    );
  }
}

