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
  bool isMember;
  final bool isOwner;

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
    required this.isMember,
    required this.isOwner,
  });

  factory CommunityModel.fromJson(Map<String, dynamic> json) {
    return CommunityModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      name: json['name']?.toString() ?? '',
      slug: json['slug']?.toString() ?? '',
      description: json['description']?.toString(),
      category: json['category']?.toString() ?? 'General',
      visibility: json['visibility']?.toString() ?? 'public',
      membersCount: json['members_count'] is int 
          ? json['members_count'] 
          : int.tryParse(json['members_count']?.toString() ?? '0') ?? 0,
      avatarUrl: json['avatar_url']?.toString(),
      bannerUrl: json['banner_url']?.toString(),
      isMember: json['is_member'] == true,
      isOwner: json['is_owner'] == true,
    );
  }
}
