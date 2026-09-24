class MemberModel {
  final int id;
  final String name;
  final String email;
  final String userId;
  final String? phone;
  final String? bio;
  final String? city;
  final String? country;
  final String? avatarUrl;
  final String? web3WalletAddress;
  final bool isVerified;
  final int directReferrals;
  final double adBalance;
  final double rewardBalance;

  final String? dateOfBirth;
  final String? gender;
  final String? website;
  final String? coverPhoto;

  MemberModel({
    required this.id,
    required this.name,
    required this.email,
    required this.userId,
    this.phone,
    this.bio,
    this.city,
    this.country,
    this.avatarUrl,
    this.web3WalletAddress,
    required this.isVerified,
    required this.directReferrals,
    required this.adBalance,
    required this.rewardBalance,
    this.dateOfBirth,
    this.gender,
    this.website,
    this.coverPhoto,
  });

  factory MemberModel.fromJson(Map<String, dynamic> json) {
    return MemberModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id'].toString()) ?? 0,
      name: json['name']?.toString() ?? '',
      email: json['email']?.toString() ?? '',
      userId: json['user_id']?.toString() ?? '',
      phone: json['phone']?.toString(),
      bio: json['bio']?.toString(),
      city: json['city']?.toString(),
      country: json['country']?.toString(),
      avatarUrl: json['avatar_url']?.toString() ?? json['profile_photo']?.toString(),
      coverPhoto: json['cover_photo']?.toString(),
      web3WalletAddress: json['web3_wallet_address']?.toString(),
      isVerified: json['is_verified'] == true || json['is_verified'] == 1,
      directReferrals: json['direct_referral_count'] is int 
          ? json['direct_referral_count'] 
          : int.tryParse(json['direct_referral_count']?.toString() ?? '0') ?? 0,
      adBalance: (json['ad_balance'] as num?)?.toDouble() ?? 0.0,
      rewardBalance: (json['reward_balance'] as num?)?.toDouble() ?? 0.0,
      dateOfBirth: json['date_of_birth']?.toString(),
      gender: json['gender']?.toString(),
      website: json['website']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'name': name,
      'email': email,
      'user_id': userId,
      'phone': phone,
      'bio': bio,
      'city': city,
      'country': country,
      'avatar_url': avatarUrl,
      'cover_photo': coverPhoto,
      'web3_wallet_address': web3WalletAddress,
      'is_verified': isVerified,
      'direct_referral_count': directReferrals,
      'ad_balance': adBalance,
      'reward_balance': rewardBalance,
      'date_of_birth': dateOfBirth,
      'gender': gender,
      'website': website,
    };
  }
}
