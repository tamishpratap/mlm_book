class WithdrawalModel {
  final int id;
  final String? memberId;
  final String? memberUserId;
  final double grossAmount;
  final double netAmount;
  final double serviceCharge;
  final String walletAddress;
  final String status;
  final String? remarks;
  final String? adminRemarks;
  final DateTime? createdAt;

  WithdrawalModel({
    required this.id,
    this.memberId,
    this.memberUserId,
    required this.grossAmount,
    required this.netAmount,
    required this.serviceCharge,
    required this.walletAddress,
    required this.status,
    this.remarks,
    this.adminRemarks,
    this.createdAt,
  });

  factory WithdrawalModel.fromJson(Map<String, dynamic> json) {
    return WithdrawalModel(
      id: json['id'] is int ? json['id'] : int.tryParse(json['id']?.toString() ?? '0') ?? 0,
      memberId: json['member_id']?.toString(),
      memberUserId: json['memberid']?.toString() ?? json['member_user_id']?.toString(),
      grossAmount: (json['gross_amount'] as num?)?.toDouble() ?? 0.0,
      netAmount: (json['net_amount'] as num?)?.toDouble() ?? 0.0,
      serviceCharge: (json['service_charge'] as num?)?.toDouble() ?? 0.0,
      walletAddress: json['wallet_address']?.toString() ?? '',
      status: json['status']?.toString() ?? 'pending',
      remarks: json['remarks']?.toString(),
      adminRemarks: json['admin_remarks']?.toString(),
      createdAt: json['created_at'] != null ? DateTime.tryParse(json['created_at'].toString()) : null,
    );
  }

  bool get isPending => status.toLowerCase() == 'pending';
  bool get isApproved => status.toLowerCase() == 'approved' || status.toLowerCase() == 'verified';
  bool get isRejected => status.toLowerCase() == 'rejected' || status.toLowerCase() == 'cancelled';
}
