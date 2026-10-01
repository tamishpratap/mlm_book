class BusinessPageModel {
  final int id;
  final String pageName;
  final String pageUsername;
  final String slug;
  final String category;
  final String? description;
  final String? website;
  final String? email;
  final String? phone;
  final String? city;
  final String? state;
  final String? country;
  final String? avatarUrl;
  final String? bannerUrl;
  final String visibility;
  final bool isVerified;
  final bool isOwner;
  final String? createdAt;
  final Map<String, dynamic>? owner;

  const BusinessPageModel({
    required this.id,
    required this.pageName,
    required this.pageUsername,
    required this.slug,
    required this.category,
    this.description,
    this.website,
    this.email,
    this.phone,
    this.city,
    this.state,
    this.country,
    this.avatarUrl,
    this.bannerUrl,
    this.visibility = 'public',
    this.isVerified = false,
    this.isOwner = false,
    this.createdAt,
    this.owner,
  });

  factory BusinessPageModel.fromJson(Map<String, dynamic> json) {
    return BusinessPageModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      pageName: json['page_name']?.toString() ?? json['name']?.toString() ?? 'Business Page',
      pageUsername: json['page_username']?.toString() ?? json['username']?.toString() ?? 'page',
      slug: json['slug']?.toString() ?? '',
      category: json['category']?.toString() ?? 'General',
      description: json['description']?.toString() ?? json['bio']?.toString(),
      website: json['website']?.toString(),
      email: json['email']?.toString(),
      phone: json['phone']?.toString(),
      city: json['city']?.toString(),
      state: json['state']?.toString(),
      country: json['country']?.toString(),
      avatarUrl: json['avatar_url']?.toString() ?? json['logo']?.toString(),
      bannerUrl: json['banner_url']?.toString() ?? json['cover_photo']?.toString(),
      visibility: json['visibility']?.toString() ?? 'public',
      isVerified: json['is_verified'] == true || json['is_verified'] == 1,
      isOwner: json['is_owner'] == true,
      createdAt: json['created_at']?.toString(),
      owner: json['owner'] is Map<String, dynamic> ? json['owner'] as Map<String, dynamic> : null,
    );
  }

  String get locationString {
    final parts = [city, state, country].where((e) => e != null && e.trim().isNotEmpty).toList();
    return parts.join(', ');
  }

  String get initials {
    if (pageName.isEmpty) return 'BP';
    final parts = pageName.trim().split(RegExp(r'\s+'));
    if (parts.length > 1) {
      return '${parts[0][0]}${parts[1][0]}'.toUpperCase();
    }
    return pageName.substring(0, pageName.length >= 2 ? 2 : 1).toUpperCase();
  }
}
