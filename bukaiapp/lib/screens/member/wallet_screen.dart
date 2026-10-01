import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../core/app_bottom_sheet.dart';
import '../../core/app_toast.dart';
import '../../providers/wallet_provider.dart';

class WalletScreen extends StatefulWidget {
  const WalletScreen({super.key});

  @override
  State<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends State<WalletScreen> {
  final _walletAddressController = TextEditingController();
  final _otpController = TextEditingController();
  final _depositAmountController = TextEditingController();
  final _depositTxHashController = TextEditingController();

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<WalletProvider>().fetchWallet();
      context.read<WalletProvider>().fetchDepositConfig();
      context.read<WalletProvider>().fetchDepositHistory();
    });
  }

  @override
  void dispose() {
    _walletAddressController.dispose();
    _otpController.dispose();
    _depositAmountController.dispose();
    _depositTxHashController.dispose();
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
        },
        color: const Color(0xFF2563EB),
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // 1. Apple Wallet / Revolut Style Card (Gradient)
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
                          const Text('Ad Balance (USDT)', style: TextStyle(color: Colors.white60, fontSize: 11)),
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

            // 2. Action Buttons Row (Deposit Funds & Link Web3 Wallet)
            Row(
              children: [
                Expanded(
                  child: ElevatedButton.icon(
                    onPressed: _showDepositDialog,
                    icon: const Icon(Icons.add, size: 18),
                    label: const Text('Deposit USDT', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5)),
                    style: ElevatedButton.styleFrom(
                      backgroundColor: const Color(0xFF2563EB),
                      foregroundColor: Colors.white,
                      elevation: 0,
                      padding: const EdgeInsets.symmetric(vertical: 12),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: OutlinedButton.icon(
                    onPressed: _showLinkWalletDialog,
                    icon: const Icon(Icons.link, size: 18),
                    label: Text(
                      wallet?.walletAddress != null ? 'Change Wallet' : 'Link Wallet',
                      style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
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

            // 4. Deposit History Header
            const Text(
              'Deposit History',
              style: TextStyle(color: Color(0xFF0F172A), fontWeight: FontWeight.bold, fontSize: 16),
            ),
            const SizedBox(height: 10),

            if (wp.deposits.isEmpty)
              Container(
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
              )
            else
              ...wp.deposits.map((d) => _buildDepositItem(d)),
          ],
        ),
      ),
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
