class AdminModel {
  final int id;
  final String name;
  final String email;
  final String? profilePhoto;
  final List<String> roles;
  final List<String> permissions;
  final bool isSuperAdmin;

  AdminModel({
    required this.id,
    required this.name,
    required this.email,
    this.profilePhoto,
    required this.roles,
    required this.permissions,
    required this.isSuperAdmin,
  });

  factory AdminModel.fromJson(Map<String, dynamic> json) {
    return AdminModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      profilePhoto: json['profile_photo']?.toString(),
      roles: (json['roles'] as List?)?.map((e) => e.toString()).toList() ?? [],
      permissions: (json['permissions'] as List?)?.map((e) => e.toString()).toList() ?? [],
      isSuperAdmin: json['is_super_admin'] == true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'profile_photo': profilePhoto,
      'roles': roles,
      'permissions': permissions,
      'is_super_admin': isSuperAdmin,
    };
  }
}
