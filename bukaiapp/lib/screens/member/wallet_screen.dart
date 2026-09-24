import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
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
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) {
        bool otpSent = false;

        return StatefulBuilder(
          builder: (context, setModalState) {
            return Padding(
              padding: EdgeInsets.only(
                left: 20,
                right: 20,
                top: 20,
                bottom: MediaQuery.of(context).viewInsets.bottom + 20,
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const Text(
                    'Link Destination USDT (BEP-20) Wallet',
                    style: TextStyle(color: AppColors.textPrimary, fontSize: 17, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Enter your Binance Smart Chain BEP-20 wallet address. A 6-digit verification code will be sent to your email to confirm.',
                    style: TextStyle(color: AppColors.textMuted, fontSize: 13),
                  ),
                  const SizedBox(height: 16),
                  if (!otpSent) ...[
                    TextField(
                      controller: _walletAddressController,
                      style: const TextStyle(color: AppColors.textPrimary, fontSize: 13),
                      decoration: InputDecoration(
                        labelText: 'BEP-20 Wallet Address (0x...)',
                        labelStyle: const TextStyle(color: AppColors.textSecondary),
                        filled: true,
                        fillColor: AppColors.background,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
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
                          } else {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Failed to send verification email.')),
                            );
                          }
                        } else {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Please enter a valid 42-char 0x... BEP-20 address.')),
                          );
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.accent,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      child: const Text('Send Verification Code', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                    ),
                  ] else ...[
                    TextField(
                      controller: _otpController,
                      keyboardType: TextInputType.number,
                      textAlign: TextAlign.center,
                      maxLength: 6,
                      style: const TextStyle(color: AppColors.textPrimary, fontSize: 24, letterSpacing: 8),
                      decoration: InputDecoration(
                        labelText: 'Enter 6-digit Code',
                        labelStyle: const TextStyle(color: AppColors.textSecondary),
                        counterText: '',
                        filled: true,
                        fillColor: AppColors.background,
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                    const SizedBox(height: 16),
                    ElevatedButton(
                      onPressed: () async {
                        final otp = _otpController.text.trim();
                        if (otp.length == 6) {
                          final ok = await context.read<WalletProvider>().verifyWalletLinkOtp(otp);
                          if (ok && mounted) {
                            Navigator.pop(ctx);
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Wallet address successfully linked!')),
                            );
                          } else {
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Invalid or expired code.')),
                            );
                          }
                        }
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.accent,
                        padding: const EdgeInsets.symmetric(vertical: 12),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                      child: const Text('Verify & Activate Wallet', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
                    ),
                  ],
                ],
              ),
            );
          },
        );
      },
    );
  }

  void _showDepositDialog() {
    final wp = context.read<WalletProvider>();
    final adminAddress = wp.depositConfig?['crypto_wallet_address']?.toString() ?? '0x1234567890abcdef1234567890abcdef12345678';

    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      backgroundColor: AppColors.surface,
      shape: const RoundedRectangleBorder(borderRadius: BorderRadius.vertical(top: Radius.circular(20))),
      builder: (ctx) {
        return Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 20,
            bottom: MediaQuery.of(ctx).viewInsets.bottom + 20,
          ),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.stretch,
            children: [
              const Text(
                'Deposit Advertising Funds (USDT)',
                style: TextStyle(color: AppColors.textPrimary, fontSize: 18, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 6),
              const Text(
                'Transfer USDT using the BEP-20 (BNB Smart Chain) network to the address below, then submit your transaction hash.',
                style: TextStyle(color: AppColors.textMuted, fontSize: 13),
              ),
              const SizedBox(height: 14),

              // Admin Address Container
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppColors.background,
                  borderRadius: BorderRadius.circular(10),
                  border: Border.all(color: AppColors.border),
                ),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('Deposit Address (BEP-20 USDT):', style: TextStyle(color: AppColors.textMuted, fontSize: 11)),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            adminAddress,
                            style: const TextStyle(color: AppColors.accent, fontSize: 12, fontFamily: 'monospace'),
                          ),
                        ),
                        IconButton(
                          icon: const Icon(Icons.copy, color: AppColors.accent, size: 18),
                          onPressed: () {
                            Clipboard.setData(ClipboardData(text: adminAddress));
                            ScaffoldMessenger.of(context).showSnackBar(
                              const SnackBar(content: Text('Address copied to clipboard!')),
                            );
                          },
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const SizedBox(height: 14),

              // Amount input
              TextField(
                controller: _depositAmountController,
                keyboardType: TextInputType.number,
                style: const TextStyle(color: AppColors.textPrimary),
                decoration: InputDecoration(
                  labelText: 'Amount (USDT)',
                  labelStyle: const TextStyle(color: AppColors.textSecondary),
                  filled: true,
                  fillColor: AppColors.background,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 12),

              // Transaction Hash input
              TextField(
                controller: _depositTxHashController,
                style: const TextStyle(color: AppColors.textPrimary),
                decoration: InputDecoration(
                  labelText: 'Blockchain Transaction Hash (0x...)',
                  labelStyle: const TextStyle(color: AppColors.textSecondary),
                  filled: true,
                  fillColor: AppColors.background,
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
              ),
              const SizedBox(height: 16),

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
                      Navigator.pop(ctx);
                      _depositAmountController.clear();
                      _depositTxHashController.clear();
                      ScaffoldMessenger.of(context).showSnackBar(
                        SnackBar(
                          content: Text(res['message']?.toString() ?? ''),
                          backgroundColor: res['success'] == true ? AppColors.accent : AppColors.danger,
                        ),
                      );
                    }
                  } else {
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Please enter valid amount and transaction hash.')),
                    );
                  }
                },
                style: ElevatedButton.styleFrom(
                  backgroundColor: AppColors.primary,
                  padding: const EdgeInsets.symmetric(vertical: 12),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
                child: const Text('Submit & Verify Deposit', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              ),
            ],
          ),
        );
      },
    );
  }

  @override
  Widget build(BuildContext context) {
    final wp = context.watch<WalletProvider>();
    final wallet = wp.wallet;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        title: const Text('Web3 & Reward Wallet', style: TextStyle(color: AppColors.textPrimary, fontSize: 18)),
      ),
      body: RefreshIndicator(
        onRefresh: () async {
          await wp.fetchWallet();
          await wp.fetchDepositConfig();
          await wp.fetchDepositHistory();
        },
        color: AppColors.accent,
        child: ListView(
          padding: const EdgeInsets.all(16),
          children: [
            // Master Wallet Balance Card (Emerald Gradient)
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: AppColors.walletGradient,
                borderRadius: BorderRadius.circular(20),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.accent.withOpacity(0.35),
                    blurRadius: 20,
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
                      const Text(
                        'Total Rewards Balance',
                        style: TextStyle(color: Colors.white70, fontSize: 13, fontWeight: FontWeight.w500),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.black26,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Text(
                          wallet != null ? 'Tier: ${wallet.currentTierLabel}' : 'Standard Tier',
                          style: const TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Text(
                    wallet?.rewardBalanceFormatted ?? '\$0.0000 USD',
                    style: const TextStyle(color: Colors.white, fontSize: 30, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 16),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          const Text('Verified Referrals', style: TextStyle(color: Colors.white70, fontSize: 11)),
                          Text(
                            '${wallet?.directVerifiedReferrals ?? 0} Members',
                            style: const TextStyle(color: Colors.white, fontSize: 14, fontWeight: FontWeight.bold),
                          ),
                        ],
                      ),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.end,
                        children: [
                          const Text('Ad Balance (Funds)', style: TextStyle(color: Colors.white70, fontSize: 11)),
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

            // Destination Wallet Address Card
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppColors.surface,
                borderRadius: BorderRadius.circular(16),
                border: Border.all(color: AppColors.border),
              ),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text(
                        'Destination USDT (BEP-20) Address',
                        style: TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.bold, fontSize: 14),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                        decoration: BoxDecoration(
                          color: wallet?.isWalletVerified == true ? AppColors.accent.withOpacity(0.2) : AppColors.warning.withOpacity(0.2),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text(
                          wallet?.isWalletVerified == true ? 'Verified' : 'Unlinked',
                          style: TextStyle(
                            color: wallet?.isWalletVerified == true ? AppColors.accent : AppColors.warning,
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
                        : 'No destination wallet linked yet.',
                    style: const TextStyle(color: AppColors.textMuted, fontSize: 12, fontFamily: 'monospace'),
                  ),
                  const SizedBox(height: 12),
                  SizedBox(
                    width: double.infinity,
                    child: OutlinedButton.icon(
                      onPressed: _showLinkWalletDialog,
                      icon: const Icon(Icons.link, color: AppColors.accent, size: 18),
                      label: Text(
                        wallet?.walletAddress != null ? 'Change Wallet Address' : 'Link Web3 Wallet',
                        style: const TextStyle(color: AppColors.accent, fontWeight: FontWeight.bold),
                      ),
                      style: OutlinedButton.styleFrom(
                        side: const BorderSide(color: AppColors.accent),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            const SizedBox(height: 16),

            // Deposit Ad Funds CTA
            ElevatedButton.icon(
              onPressed: _showDepositDialog,
              icon: const Icon(Icons.add_card, color: Colors.white),
              label: const Text('Deposit Advertising Funds (USDT BEP-20)', style: TextStyle(color: Colors.white, fontWeight: FontWeight.bold)),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                padding: const EdgeInsets.symmetric(vertical: 14),
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(14)),
              ),
            ),
            const SizedBox(height: 20),

            // Deposit Transactions History
            const Text(
              'Deposit History',
              style: TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.bold, fontSize: 16),
            ),
            const SizedBox(height: 10),

            if (wp.deposits.isEmpty)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 20),
                child: Center(
                  child: Text('No deposit transactions found.', style: TextStyle(color: AppColors.textMuted)),
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
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: AppColors.border),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Row(
            children: [
              CircleAvatar(
                backgroundColor: isApproved ? AppColors.accent.withOpacity(0.15) : AppColors.warning.withOpacity(0.15),
                child: Icon(
                  isApproved ? Icons.check : Icons.hourglass_top,
                  color: isApproved ? AppColors.accent : AppColors.warning,
                  size: 20,
                ),
              ),
              const SizedBox(width: 12),
              Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '+${d.amount} USDT',
                    style: const TextStyle(color: AppColors.textPrimary, fontWeight: FontWeight.bold, fontSize: 14),
                  ),
                  Text(d.date, style: const TextStyle(color: AppColors.textMuted, fontSize: 11)),
                ],
              ),
            ],
          ),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
            decoration: BoxDecoration(
              color: isApproved ? AppColors.accent.withOpacity(0.15) : AppColors.warning.withOpacity(0.15),
              borderRadius: BorderRadius.circular(8),
            ),
            child: Text(
              d.status.toUpperCase(),
              style: TextStyle(
                color: isApproved ? AppColors.accent : AppColors.warning,
                fontWeight: FontWeight.bold,
                fontSize: 11,
              ),
            ),
          ),
        ],
      ),
    );
  }
}
