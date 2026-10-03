import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../models/campaign_model.dart';
import '../models/wallet_model.dart';
import '../models/withdrawal_model.dart';

class WalletProvider extends ChangeNotifier {
  bool _isLoading = false;
  WalletModel? _wallet;
  Map<String, dynamic>? _depositConfig;
  List<DepositModel> _deposits = [];

  // Withdrawals State
  Map<String, dynamic>? _withdrawalConfig;
  Map<String, dynamic>? _withdrawalStats;
  List<WithdrawalModel> _withdrawals = [];
  bool _isWithdrawalLoading = false;

  bool get isLoading => _isLoading;
  WalletModel? get wallet => _wallet;
  Map<String, dynamic>? get depositConfig => _depositConfig;
  List<DepositModel> get deposits => _deposits;

  Map<String, dynamic>? get withdrawalConfig => _withdrawalConfig;
  Map<String, dynamic>? get withdrawalStats => _withdrawalStats;
  List<WithdrawalModel> get withdrawals => _withdrawals;
  bool get isWithdrawalLoading => _isWithdrawalLoading;

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

  // Fetch Member Withdrawals, Limits & History
  Future<void> fetchWithdrawals() async {
    _isWithdrawalLoading = true;
    notifyListeners();

    final res = await ApiClient.get('/withdrawals');
    _isWithdrawalLoading = false;

    if (res.success && res.data is Map) {
      if (res.data['config'] != null) {
        _withdrawalConfig = res.data['config'] as Map<String, dynamic>;
      }
      if (res.data['stats'] != null) {
        _withdrawalStats = res.data['stats'] as Map<String, dynamic>;
      }
      if (res.data['withdrawals'] is List) {
        final list = res.data['withdrawals'] as List;
        _withdrawals = list.map((e) => WithdrawalModel.fromJson(e as Map<String, dynamic>)).toList();
      }
    }
    notifyListeners();
  }

  // Submit Earning Wallet Withdrawal Request
  Future<Map<String, dynamic>> submitWithdrawal({
    required double amount,
    String? walletAddress,
    String? remarks,
  }) async {
    final res = await ApiClient.post('/withdrawals', {
      'gross_amount': amount,
      if (walletAddress != null && walletAddress.isNotEmpty) 'wallet_address': walletAddress.trim(),
      if (remarks != null && remarks.isNotEmpty) 'remarks': remarks.trim(),
    });

    if (res.success) {
      await fetchWallet();
      await fetchWithdrawals();
      return {
        'success': true,
        'message': res.message ?? 'Withdrawal request submitted successfully.',
      };
    } else {
      return {
        'success': false,
        'message': res.message ?? 'Failed to submit withdrawal request.',
      };
    }
  }

  // Submit Zero-Fee Fund Wallet Withdrawal Request
  Future<Map<String, dynamic>> submitFundWalletWithdrawal() async {
    final res = await ApiClient.post('/withdrawals/fund-wallet', {});

    if (res.success) {
      await fetchWallet();
      await fetchWithdrawals();
      return {
        'success': true,
        'message': res.message ?? 'Fund wallet withdrawal request submitted successfully.',
      };
    } else {
      return {
        'success': false,
        'message': res.message ?? 'Failed to submit fund wallet withdrawal request.',
      };
    }
  }
}

