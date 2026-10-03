import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../core/app_bottom_sheet.dart';
import '../../core/app_toast.dart';
import '../../models/withdrawal_model.dart';
import '../../providers/wallet_provider.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> with SingleTickerProviderStateMixin {
  final _walletAddressController = TextEditingController();
  final _otpController = TextEditingController();
  final _depositAmountController = TextEditingController();
  final _depositTxHashController = TextEditingController();

  final _withdrawalAmountController = TextEditingController();
  final _withdrawalRemarksController = TextEditingController();

  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final wp = context.read<WalletProvider>();
      wp.fetchWallet();
      wp.fetchDepositConfig();
      wp.fetchDepositHistory();
      wp.fetchWithdrawals();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    _walletAddressController.dispose();
    _otpController.dispose();
    _depositAmountController.dispose();
    _depositTxHashController.dispose();
    _withdrawalAmountController.dispose();
    _withdrawalRemarksController.dispose();
    super.dispose();
  }

  void _showLinkWalletDialog() {
    bool otpSent = false;
    AppBottomSheet.show(
      context,
      title: 'Link BEP-20 Wallet',
      child: StatefulBuilder(
        builder: (context, setModalState) {
          return Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Enter your BNB Smart Chain (BEP-20) address. A 6-digit verification code will be sent to your email to verify.',
                  style: TextStyle(color: Color(0xFF64748B), fontSize: 13, height: 1.4),
                ),
                const SizedBox(height: 16),
                if (!otpSent) ...[
                  TextField(
                    controller: _walletAddressController,
                    style: const TextStyle(color: Color(0xFF0F172A), fontSize: 13.5),
                    decoration: InputDecoration(
                      labelText: 'BEP-20 Wallet Address (0x...)',
                      labelStyle: const TextStyle(color: Color(0xFF64748B)),
                      filled: true,
                      fillColor: const Color(0xFFF8FAFC),
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                      ),
                      enabledBorder: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () async {
                      final addr = _walletAddressController.text.trim();
                      if (addr.startsWith('0x') && addr.length == 42) {
                        final ok = await context.read<WalletProvider>().sendWalletLinkOtp(addr);
                        if (ok) {
                          setModalState(() => otpSent = true);
                          AppToast.info(context, 'Verification code sent to email');
                        } else {
                          AppToast.error(context, 'Failed to send verification code.');
                        }
                      } else {
                        AppToast.error(context, 'Please enter a valid 42-char 0x... BEP-20 address.');
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF2563EB),
                      foregroundColor: Colors.white,
                      elevation: 0,
                      padding: const EdgeInsets.symmetric(vertical: 13),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: const Text('Send Verification Code', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  ),
                ] else ...[
                  TextField(
                    controller: _otpController,
                    keyboardType: TextInputType.number,
                    maxLength: 6,
                    textAlign: TextAlign.center,
                    style: const TextStyle(fontSize: 20, letterSpacing: 8, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
                    decoration: InputDecoration(
                      labelText: '6-Digit Code',
                      labelStyle: const TextStyle(color: Color(0xFF64748B)),
                      filled: true,
                      fillColor: const Color(0xFFF8FAFC),
                      counterText: '',
                      border: OutlineInputBorder(
                        borderRadius: BorderRadius.circular(14),
                        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () async {
                      final otp = _otpController.text.trim();
                      if (otp.length == 6) {
                        final ok = await context.read<WalletProvider>().verifyWalletLinkOtp(otp);
                        if (ok && mounted) {
                          Navigator.pop(context);
                          AppToast.success(context, 'Wallet address linked successfully!');
                        } else {
                          AppToast.error(context, 'Invalid or expired code.');
                        }
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF16A34A),
                      foregroundColor: Colors.white,
                      elevation: 0,
                      padding: const EdgeInsets.symmetric(vertical: 13),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    ),
                    child: const Text('Verify & Activate Wallet', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
                  ),
                ],
              ],
            ),
          );
        },
      ),
    );
  }

  void _showDepositDialog() {
    final wp = context.read<WalletProvider>();
    final adminAddress = wp.depositConfig?['crypto_wallet_address']?.toString() ?? '0x1234567890abcdef1234567890abcdef12345678';

    AppBottomSheet.show(
      context,
      title: 'Deposit USDT (BEP-20)',
      child: Padding(
        padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Send USDT via BNB Smart Chain (BEP-20) to the address below, then submit the transaction hash.',
              style: TextStyle(color: Color(0xFF64748B), fontSize: 13, height: 1.4),
            ),
            const SizedBox(height: 14),

            // Deposit address box
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(14),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('Deposit Address (BEP-20):', style: TextStyle(color: Color(0xFF64748B), fontSize: 11, fontWeight: FontWeight.w500)),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          adminAddress,
                          style: const TextStyle(color: Color(0xFF2563EB), fontSize: 12, fontFamily: 'monospace', fontWeight: FontWeight.w600),
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.copy, color: Color(0xFF2563EB), size: 18),
                        onPressed: () {
                          Clipboard.setData(ClipboardData(text: adminAddress));
                          AppToast.success(context, 'Address copied to clipboard!');
                        },
                      ),
                    ],
                  ),
                ],
              ),
            ),
            const SizedBox(height: 14),

            // Deposit Amount input
            TextField(
              controller: _depositAmountController,
              keyboardType: const TextInputType.numberWithOptions(decimal: true),
              style: const TextStyle(color: Color(0xFF0F172A)),
              decoration: InputDecoration(
                labelText: 'Deposit Amount (USDT)',
                labelStyle: const TextStyle(color: Color(0xFF64748B)),
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                prefixText: '\$ ',
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
              ),
            ),
            const SizedBox(height: 12),

            // Tx Hash input
            TextField(
              controller: _depositTxHashController,
              style: const TextStyle(color: Color(0xFF0F172A), fontSize: 12.5),
              decoration: InputDecoration(
                labelText: 'Blockchain Transaction Hash (0x...)',
                labelStyle: const TextStyle(color: Color(0xFF64748B)),
                filled: true,
                fillColor: const Color(0xFFF8FAFC),
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
              ),
            ),
            const SizedBox(height: 18),

            ElevatedButton(
              onPressed: () async {
                final amount = double.tryParse(_depositAmountController.text.trim());
                final txHash = _depositTxHashController.text.trim();

                if (amount != null && amount >= 1.0 && txHash.isNotEmpty) {
                  final res = await context.read<WalletProvider>().submitDeposit(
                        amount: amount,
                        txHash: txHash,
                      );
                  if (mounted) {
                    Navigator.pop(context);
                    _depositAmountController.clear();
                    _depositTxHashController.clear();
                    if (res['success'] == true) {
                      AppToast.success(context, res['message']?.toString() ?? 'Deposit submitted!');
                    } else {
                      AppToast.error(context, res['message']?.toString() ?? 'Failed to submit deposit.');
                    }
                  }
                } else {
                  AppToast.error(context, 'Please enter a valid amount and transaction hash.');
                }
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: const Color(0xFF2563EB),
                foregroundColor: Colors.white,
                elevation: 0,
                padding: const EdgeInsets.symmetric(vertical: 13),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: const Text('Submit & Verify Deposit', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14)),
            ),
          ],
        ),
      ),
    );
  }

  void _showWithdrawalDialog() {
    final wp = context.read<WalletProvider>();
    final wallet = wp.wallet;
    final config = wp.withdrawalConfig ?? {};

    final minAmount = (config['minimum_amount'] as num?)?.toDouble() ?? 5.0;
    final maxAmount = (config['maximum_amount'] as num?)?.toDouble() ?? 10000.0;
    final feePercent = (config['service_charge_percent'] as num?)?.toDouble() ?? 0.0;
    final linkedAddress = wallet?.walletAddress ?? '';

    _withdrawalAmountController.clear();
    _withdrawalRemarksController.clear();

    double feeAmount = 0.0;
    double netPayout = 0.0;

    AppBottomSheet.show(
      context,
      title: 'Withdraw Earnings',
      child: StatefulBuilder(
        builder: (context, setModalState) {
          void updateCalculations(String val) {
            final parsed = double.tryParse(val.trim()) ?? 0.0;
            setModalState(() {
              feeAmount = (parsed * feePercent) / 100.0;
              netPayout = parsed - feeAmount;
              if (netPayout < 0) netPayout = 0;
            });
          }

          return Padding(
            padding: const EdgeInsets.fromLTRB(20, 8, 20, 24),
            child: SingleChildScrollView(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // Available Balance Box
                  Container(
                    padding: const EdgeInsets.all(14),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF1F5F9),
                      borderRadius: BorderRadius.circular(14),
                    ),
                    child: Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text('Available Earning Balance:', style: TextStyle(color: Color(0xFF64748B), fontSize: 12.5, fontWeight: FontWeight.w500)),
                        Text(
                          wallet?.rewardBalanceFormatted ?? '\$0.00 USD',
                          style: const TextStyle(color: Color(0xFF0F172A), fontSize: 15, fontWeight: FontWeight.bold),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 14),

                  // Destination Wallet Address Box
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: linkedAddress.isNotEmpty ? const Color(0xFFF0FDF4) : const Color(0xFFFEF2F2),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(
                        color: linkedAddress.isNotEmpty ? const Color(0xFFBBF7D0) : const Color(0xFFFECACA),
                      ),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Payout Destination (BEP-20):', style: TextStyle(color: Color(0xFF475569), fontSize: 11, fontWeight: FontWeight.bold)),
                            Text(
                              linkedAddress.isNotEmpty ? 'Verified' : 'Not Linked',
                              style: TextStyle(
                                color: linkedAddress.isNotEmpty ? const Color(0xFF16A34A) : const Color(0xFFDC2626),
                                fontSize: 11,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ],
                        ),
                        const SizedBox(height: 4),
                        Text(
                          linkedAddress.isNotEmpty ? linkedAddress : 'Please link your BEP-20 wallet address first before submitting withdrawal.',
                          style: TextStyle(
                            color: linkedAddress.isNotEmpty ? const Color(0xFF15803D) : const Color(0xFFB91C1C),
                            fontSize: 12,
                            fontFamily: 'monospace',
                          ),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 14),

                  // Amount Input
                  TextField(
                    controller: _withdrawalAmountController,
                    keyboardType: const TextInputType.numberWithOptions(decimal: true),
                    onChanged: updateCalculations,
                    style: const TextStyle(color: Color(0xFF0F172A), fontSize: 16, fontWeight: FontWeight.bold),
                    decoration: InputDecoration(
                      labelText: 'Withdrawal Amount (USD)',
                      labelStyle: const TextStyle(color: Color(0xFF64748B), fontSize: 13),
                      helperText: 'Min: \$${minAmount.toStringAsFixed(2)} | Max: \$${maxAmount.toStringAsFixed(2)}',
                      filled: true,
                      fillColor: const Color(0xFFF8FAFC),
                      prefixText: '\$ ',
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(14), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                    ),
                  ),

                  const SizedBox(height: 12),

                  // Fee & Net Calculation Breakdown
                  Container(
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: const Color(0xFFF8FAFC),
                      borderRadius: BorderRadius.circular(12),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Column(
                      children: [
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            Text('Service Fee (${feePercent.toStringAsFixed(2)}%):', style: const TextStyle(color: Color(0xFF64748B), fontSize: 12)),
                            Text('\$${feeAmount.toStringAsFixed(4)} USD', style: const TextStyle(color: Color(0xFF64748B), fontSize: 12, fontWeight: FontWeight.w600)),
                          ],
                        ),
                        const Divider(height: 12),
                        Row(
                          mainAxisAlignment: MainAxisAlignment.spaceBetween,
                          children: [
                            const Text('Net Payout Amount:', style: TextStyle(color: Color(0xFF0F172A), fontSize: 13, fontWeight: FontWeight.bold)),
                            Text(
                              '\$${netPayout.toStringAsFixed(4)} USDT',
                              style: const TextStyle(color: Color(0xFF16A34A), fontSize: 15, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 12),

                  // Remarks Input
                  TextField(
                    controller: _withdrawalRemarksController,
                    style: const TextStyle(color: Color(0xFF0F172A), fontSize: 13),
                    decoration: InputDecoration(
                      labelText: 'Remarks / Notes (Optional)',
                      labelStyle: const TextStyle(color: Color(0xFF64748B)),
                      filled: true,
                      fillColor: const Color(0xFFF8FAFC),
                      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: Color(0xFFE2E8F0))),
                    ),
                  ),

                  const SizedBox(height: 18),

                  ElevatedButton(
                    onPressed: () async {
                      if (linkedAddress.isEmpty) {
                        AppToast.error(context, 'Please link your BEP-20 wallet address first.');
                        return;
                      }

                      final amount = double.tryParse(_withdrawalAmountController.text.trim());
                      if (amount == null || amount < minAmount) {
                        AppToast.error(context, 'Minimum withdrawal amount is \$${minAmount.toStringAsFixed(2)}');
                        return;
                      }

                      if (amount > (wallet?.rewardBalance ?? 0.0)) {
                        AppToast.error(context, 'Insufficient balance for this withdrawal request.');
                        return;
                      }

                      final res = await context.read<WalletProvider>().submitWithdrawal(
                            amount: amount,
                            walletAddress: linkedAddress,
                            remarks: _withdrawalRemarksController.text.trim(),
                          );

                      if (mounted) {
                        Navigator.pop(context);
                        if (res['success'] == true) {
                          AppToast.success(context, res['message']?.toString() ?? 'Withdrawal request submitted!');
                        } else {
                          AppToast.error(context, res['message']?.toString() ?? 'Failed to submit withdrawal request.');
                        }
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF16A34A),
                      foregroundColor: Colors.white,
                      elevation: 0,
                      padding: const EdgeInsets.symmetric(vertical: 14),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                    child: const Text('Submit Withdrawal Request', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 14.5)),
                  ),

                  const SizedBox(height: 10),

                  // Fund Wallet Zero Fee Transfer Option
                  if ((wallet?.adBalance ?? 0.0) > 0)
                    OutlinedButton.icon(
                      onPressed: () async {
                        final confirm = await showDialog<bool>(
                          context: context,
                          builder: (ctx) => AlertDialog(
                            title: const Text('Withdraw Fund Wallet (0% Fee)', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
                            content: Text(
                              'Are you sure you want to withdraw your entire Fund Wallet balance (${wallet?.adBalanceFormatted})? Zero service fee will be charged.',
                            ),
                            actions: [
                              TextButton(onPressed: () => Navigator.pop(ctx, false), child: const Text('Cancel')),
                              ElevatedButton(
                                onPressed: () => Navigator.pop(ctx, true),
                                style: ElevatedButton.styleFrom(backgroundColor: const Color(0xFF2563EB), foregroundColor: Colors.white),
                                child: const Text('Confirm'),
                              ),
                            ],
                          ),
                        );

                        if (confirm == true && mounted) {
                          final res = await context.read<WalletProvider>().submitFundWalletWithdrawal();
                          if (mounted) {
                            Navigator.pop(context);
                            if (res['success'] == true) {
                              AppToast.success(context, res['message']?.toString() ?? 'Fund wallet withdrawal submitted!');
                            } else {
                              AppToast.error(context, res['message']?.toString() ?? 'Failed to submit request.');
                            }
                          }
                        }
                      },
                      icon: const Icon(Icons.flash_on, size: 16, color: Color(0xFF2563EB)),
                      label: Text('Withdraw Fund Wallet (${wallet?.adBalanceFormatted}) - 0% Fee', style: const TextStyle(color: Color(0xFF2563EB), fontSize: 12.5, fontWeight: FontWeight.bold)),
                      style: OutlinedButton.styleFrom(
                        side: const BorderSide(color: Color(0xFFBFDBFE)),
                        padding: const EdgeInsets.symmetric(vertical: 11),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final wp = context.watch<WalletProvider>();
    final wallet = wp.wallet;

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: const Text(
          'Wallet & Earnings',
          style: TextStyle(color: Color(0xFF0F172A), fontSize: 18, fontWeight: FontWeight.bold),
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1.0),
          child: Container(color: const Color(0xFFE2E8F0), height: 1.0),
        ),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await wp.fetchWallet();
          await wp.fetchDepositConfig();
          await wp.fetchDepositHistory();
          await wp.fetchWithdrawals();
        },
        color: const Color(0xFF2563EB),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // 1. Main Wallet Card (Gradient)
            Container(
              padding: const EdgeInsets.all(22),
              decoration: BoxDecoration(
                gradient: const LinearGradient(
                  colors: [Color(0xFF0F172A), Color(0xFF1E293B), Color(0xFF334155)],
                  begin: Alignment.topLeft,
                  end: Alignment.bottomRight,
                ),
                borderRadius: BorderRadius.circular(22),
                boxShadow: [
                  BoxShadow(
                    color: const Color(0xFF0F172A).withOpacity(0.3),
                    blurRadius: 18,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: const [
                          Icon(Icons.account_balance_wallet_outlined, color: Colors.white70, size: 18),
                          SizedBox(width: 8),
                          Text(
                            'Main Reward Balance',
                            style: TextStyle(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.w500),
                          ),
                        ],
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 3),
                        decoration: BoxDecoration(
                          color: Colors.white.withOpacity(0.12),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          wallet != null ? wallet.currentTierLabel : 'Standard Tier',
                          style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 14),
                  Text(
                    wallet?.rewardBalanceFormatted ?? '\$0.0000 USD',
                    style: const TextStyle(color: Colors.white, fontSize: 32, fontWeight: FontWeight.bold, letterSpacing: -0.5),
                  ),
                  const SizedBox(height: 18),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Verified Referrals', style: TextStyle(color: Colors.white60, fontSize: 11)),
                          const SizedBox(height: 2),
                          Text(
                            '${wallet?.directVerifiedReferrals ?? 0} Members',
                            style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text('Fund / Ad Balance', style: TextStyle(color: Colors.white60, fontSize: 11)),
                          const SizedBox(height: 2),
                          Text(
                            wallet?.adBalanceFormatted ?? '\$0.00 USDT',
                            style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                    ],
                  ),
                ],
              ),
            ),

            const SizedBox(height: 16),

            // 2. Action Buttons Row (Deposit USDT, Withdraw, Link Wallet)
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _showDepositDialog,
                    icon: const Icon(Icons.add, size: 16),
                    label: const Text('Deposit', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF2563EB),
                      foregroundColor: Colors.white,
                      elevation: 0,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _showWithdrawalDialog,
                    icon: const Icon(Icons.arrow_upward, size: 16),
                    label: const Text('Withdraw', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF16A34A),
                      foregroundColor: Colors.white,
                      elevation: 0,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                  ),
                ),
                const SizedBox(width: 8),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _showLinkWalletDialog,
                    icon: const Icon(Icons.link, size: 16),
                    label: Text(
                      wallet?.walletAddress != null ? 'Wallet' : 'Link',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13),
                    ),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFF0F172A),
                      side: const BorderSide(color: Color(0xFFCBD5E1)),
                      backgroundColor: Colors.white,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                  ),
                ),
              ],
            ),

            const SizedBox(height: 16),

            // 3. Destination Wallet Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Linked BEP-20 Wallet',
                        style: TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: wallet?.isWalletVerified == true ? const Color(0xFFDCFCE7) : const Color(0xFFFEF3C7),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          wallet?.isWalletVerified == true ? 'Verified' : 'Unlinked',
                          style: TextStyle(
                            color: wallet?.isWalletVerified == true ? const Color(0xFF16A34A) : const Color(0xFFD97706),
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    wallet?.walletAddress != null && wallet!.walletAddress!.isNotEmpty
                        ? wallet.walletAddress!
                        : 'No destination wallet linked yet. Link your BEP-20 address to receive automated reward payouts.',
                    style: const TextStyle(color: Color(0xFF64748B), fontSize: 12.5, fontFamily: 'monospace'),
                  ),
                ],
              ),
            ),

            const SizedBox(height: 20),

            // 4. TabBar for Deposit History vs Withdrawal History
            Container(
              decoration: BoxDecoration(
                color: const Color(0xFFE2E8F0),
                borderRadius: BorderRadius.circular(12),
              ),
              child: TabBar(
                controller: _tabController,
                indicator: BoxDecoration(
                  borderRadius: BorderRadius.circular(10),
                  color: Colors.white,
                ),
                indicatorSize: TabBarIndicatorSize.tab,
                labelColor: const Color(0xFF0F172A),
                unselectedLabelColor: const Color(0xFF64748B),
                labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
                tabs: const [
                  Tab(text: 'Withdrawal History'),
                  Tab(text: 'Deposit History'),
                ],
              ),
            ),

            const SizedBox(height: 14),

            // TabBarView content inside ListView container
            SizedBox(
              height: 350,
              child: TabBarView(
                controller: _tabController,
                children: [
                  // Tab 1: Withdrawal History
                  _buildWithdrawalHistoryList(wp),

                  // Tab 2: Deposit History
                  _buildDepositHistoryList(wp),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildWithdrawalHistoryList(WalletProvider wp) {
    if (wp.isWithdrawalLoading) {
      return const Center(child: CircularProgressIndicator(color: Color(0xFF2563EB)));
    }

    if (wp.withdrawals.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: const Center(
          child: Text(
            'No withdrawal history yet.',
            style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
          ),
        ),
      );
    }

    return ListView.builder(
      physics: const BouncingScrollPhysics(),
      itemCount: wp.withdrawals.length,
      itemBuilder: (context, index) {
        final w = wp.withdrawals[index];
        return _buildWithdrawalItem(w);
      },
    );
  }

  Widget _buildWithdrawalItem(WithdrawalModel w) {
    Color badgeBg;
    Color badgeText;
    IconData statusIcon;

    if (w.isApproved) {
      badgeBg = const Color(0xFFDCFCE7);
      badgeText = const Color(0xFF16A34A);
      statusIcon = Icons.check_circle;
    } else if (w.isRejected) {
      badgeBg = const Color(0xFFFEE2E2);
      badgeText = const Color(0xFFDC2626);
      statusIcon = Icons.cancel;
    } else {
      badgeBg = const Color(0xFFFEF3C7);
      badgeText = const Color(0xFFD97706);
      statusIcon = Icons.hourglass_top;
    }

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: [
                  CircleAvatar(
                    backgroundColor: badgeBg,
                    radius: 16,
                    child: Icon(statusIcon, color: badgeText, size: 18),
                  ),
                  const SizedBox(width: 10),
                  Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        '-\$${w.grossAmount.toStringAsFixed(2)} USD',
                        style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14.5, color: Color(0xFF0F172A)),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        'Net: \$${w.netAmount.toStringAsFixed(2)} USDT (Fee: \$${w.serviceCharge.toStringAsFixed(2)})',
                        style: const TextStyle(color: Color(0xFF64748B), fontSize: 11.5),
                      ),
                    ],
                  ),
                ],
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                decoration: BoxDecoration(
                  color: badgeBg,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  w.status.toUpperCase(),
                  style: TextStyle(color: badgeText, fontSize: 10.5, fontWeight: FontWeight.bold),
                ),
              ),
            ],
          ),
          if (w.createdAt != null) ...[
            const SizedBox(height: 6),
            Text(
              'Date: ${w.createdAt.toString().split('.').first}',
              style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildDepositHistoryList(WalletProvider wp) {
    if (wp.deposits.isEmpty) {
      return Container(
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFE2E8F0)),
        ),
        child: const Center(
          child: Text(
            'No deposit transactions yet.',
            style: TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
          ),
        ),
      );
    }

    return ListView.builder(
      physics: const BouncingScrollPhysics(),
      itemCount: wp.deposits.length,
      itemBuilder: (context, index) {
        final d = wp.deposits[index];
        return _buildDepositItem(d);
      },
    );
  }

  Widget _buildDepositItem(dynamic d) {
    final isApproved = d.status == 'approved';
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.all(14),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              CircleAvatar(
                backgroundColor: isApproved ? const Color(0xFFDCFCE7) : const Color(0xFFFEF3C7),
                child: Icon(
                  isApproved ? Icons.check_circle : Icons.hourglass_top,
                  color: isApproved ? const Color(0xFF16A34A) : const Color(0xFFD97706),
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '+${d.amount} USDT',
                    style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14, color: Color(0xFF0F172A)),
                  ),
                  const SizedBox(height: 2),
                  Text(
                    d.createdAt?.toString().split('T').first ?? '',
                    style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 11.5),
                  ),
                ],
              ),
            ],
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
            decoration: BoxDecoration(
              color: isApproved ? const Color(0xFFDCFCE7) : const Color(0xFFFEF3C7),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              d.status?.toString().toUpperCase() ?? 'PENDING',
              style: TextStyle(
                color: isApproved ? const Color(0xFF16A34A) : const Color(0xFFD97706),
                fontSize: 11,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
