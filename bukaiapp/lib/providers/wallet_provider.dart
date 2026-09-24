import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../models/campaign_model.dart';
import '../models/wallet_model.dart';

class WalletProvider extends ChangeNotifier {
  bool _isLoading = false;
  WalletModel? _wallet;
  Map<String, dynamic>? _depositConfig;
  List<DepositModel> _deposits = [];

  bool get isLoading => _isLoading;
  WalletModel? get wallet => _wallet;
  Map<String, dynamic>? get depositConfig => _depositConfig;
  List<DepositModel> get deposits => _deposits;

  // Fetch Wallet Overview
  Future<void> fetchWallet() async {
    _isLoading = true;
    notifyListeners();

    final res = await ApiClient.get('/wallet');
    _isLoading = false;

    if (res.success && res.data is Map && res.data['wallet'] != null) {
      _wallet = WalletModel.fromJson(res.data['wallet'] as Map<String, dynamic>);
    }
    notifyListeners();
  }

  // Request OTP to link Web3 USDT address
  Future<bool> sendWalletLinkOtp(String walletAddress) async {
    final res = await ApiClient.post('/wallet/link-address/send-otp', {
      'wallet_address': walletAddress.trim(),
    });
    return res.success;
  }

  // Verify OTP and link address
  Future<bool> verifyWalletLinkOtp(String otp) async {
    final res = await ApiClient.post('/wallet/link-address/verify', {
      'otp': otp.trim(),
    });
    if (res.success) {
      await fetchWallet();
      return true;
    }
    return false;
  }

  // Fetch Deposit Config (Admin Wallet & QR)
  Future<void> fetchDepositConfig() async {
    final res = await ApiClient.get('/deposits/config');
    if (res.success && res.data is Map && res.data['config'] != null) {
      _depositConfig = res.data['config'] as Map<String, dynamic>;
      notifyListeners();
    }
  }

  // Submit Crypto Deposit (USDT BEP-20)
  Future<Map<String, dynamic>> submitDeposit({
    required double amount,
    required String txHash,
  }) async {
    final res = await ApiClient.post('/deposits/submit', {
      'amount_usdt': amount,
      'transaction_hash': txHash.trim(),
    });

    if (res.success) {
      await fetchWallet();
      await fetchDepositHistory();
      return {
        'success': true,
        'message': res.message ?? 'Deposit submitted successfully.',
        'auto_credited': res.data is Map && res.data['is_auto_credited'] == true,
      };
    } else {
      return {
        'success': false,
        'message': res.message ?? 'Deposit submission failed.',
      };
    }
  }

  // Fetch Deposit History
  Future<void> fetchDepositHistory() async {
    final res = await ApiClient.get('/deposits/history');
    if (res.success && res.data is Map && res.data['deposits'] is Map && res.data['deposits']['data'] is List) {
      final list = res.data['deposits']['data'] as List;
      _deposits = list.map((e) => DepositModel.fromJson(e as Map<String, dynamic>)).toList();
      notifyListeners();
    }
  }
}
