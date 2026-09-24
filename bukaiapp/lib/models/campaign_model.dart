class CampaignModel {
  final int id;
  final String campaignId;
  final String campaignName;
  final String businessName;
  final String? businessAvatar;
  final String? postBody;
  final String? mediaUrl;
  final String potentialRewardUsd;
  bool isClaimed;

  CampaignModel({
    required this.id,
    required this.campaignId,
    required this.campaignName,
    required this.businessName,
    this.businessAvatar,
    this.postBody,
    this.mediaUrl,
    required this.potentialRewardUsd,
    required this.isClaimed,
  });

  factory CampaignModel.fromJson(Map<String, dynamic> json) {
    return CampaignModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      campaignId: json['campaign_id']?.toString() ?? '',
      campaignName: json['campaign_name']?.toString() ?? 'Campaign',
      businessName: json['business_name']?.toString() ?? 'Sponsored',
      businessAvatar: json['business_avatar']?.toString(),
      postBody: json['post_body']?.toString(),
      mediaUrl: json['media_url']?.toString(),
      potentialRewardUsd: json['potential_reward_usd']?.toString() ?? '0.0250',
      isClaimed: json['is_claimed'] == true,
    );
  }
}

class DepositModel {
  final int id;
  final String depositId;
  final double amount;
  final String currency;
  final String txHash;
  final String status;
  final String date;

  DepositModel({
    required this.id,
    required this.depositId,
    required this.amount,
    required this.currency,
    required this.txHash,
    required this.status,
    required this.date,
  });

  factory DepositModel.fromJson(Map<String, dynamic> json) {
    return DepositModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      depositId: json['deposit_id']?.toString() ?? '',
      amount: (json['amount'] as num?)?.toDouble() ?? 0.0,
      currency: json['currency']?.toString() ?? 'USDT',
      txHash: json['tx_hash']?.toString() ?? '',
      status: json['status']?.toString() ?? 'pending',
      date: json['date']?.toString() ?? '',
    );
  }
}
