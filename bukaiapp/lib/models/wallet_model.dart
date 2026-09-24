class WalletModel {
  final double rewardBalance;
  final String rewardBalanceFormatted;
  final double adBalance;
  final String adBalanceFormatted;
  final double totalEarned;
  final bool isMobileVerified;
  final int directVerifiedReferrals;
  final String currentTierLabel;
  final String currentTierRewardExact;
  final String? walletAddress;
  final String walletNetwork;
  final bool isWalletVerified;

  WalletModel({
    required this.rewardBalance,
    required this.rewardBalanceFormatted,
    required this.adBalance,
    required this.adBalanceFormatted,
    required this.totalEarned,
    required this.isMobileVerified,
    required this.directVerifiedReferrals,
    required this.currentTierLabel,
    required this.currentTierRewardExact,
    this.walletAddress,
    required this.walletNetwork,
    required this.isWalletVerified,
  });

  factory WalletModel.fromJson(Map<String, dynamic> json) {
    final tier = json['current_tier'] as Map<String, dynamic>? ?? {};

    return WalletModel(
      rewardBalance: (json['reward_balance'] as num?)?.toDouble() ?? 0.0,
      rewardBalanceFormatted: json['reward_balance_formatted']?.toString() ?? r'$0.0000 USD',
      adBalance: (json['ad_balance'] as num?)?.toDouble() ?? 0.0,
      adBalanceFormatted: json['ad_balance_formatted']?.toString() ?? r'$0.00 USDT',
      totalEarned: (json['total_earned'] as num?)?.toDouble() ?? 0.0,
      isMobileVerified: json['is_mobile_verified'] == true,
      directVerifiedReferrals: json['direct_verified_referrals'] is int 
          ? json['direct_verified_referrals'] 
          : int.tryParse(json['direct_verified_referrals']?.toString() ?? '0') ?? 0,
      currentTierLabel: tier['range_label']?.toString() ?? 'Standard',
      currentTierRewardExact: tier['reward_amount_exact']?.toString() ?? '0.0250',
      walletAddress: json['wallet_address']?.toString(),
      walletNetwork: json['wallet_network']?.toString() ?? 'BEP-20',
      isWalletVerified: json['is_wallet_verified'] == true,
    );
  }
}
