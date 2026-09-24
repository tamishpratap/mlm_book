import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../providers/auth_provider.dart';

class SecuritySettingsScreen extends StatefulWidget {
  const SecuritySettingsScreen({super.key});

  @override
  State<SecuritySettingsScreen> createState() => _SecuritySettingsScreenState();
}

class _SecuritySettingsScreenState extends State<SecuritySettingsScreen> {
  final _pwdFormKey = GlobalKey<FormState>();
  final TextEditingController _currentPwdCtrl = TextEditingController();
  final TextEditingController _newPwdCtrl = TextEditingController();
  final TextEditingController _confirmPwdCtrl = TextEditingController();

  bool _obscureCurrent = true;
  bool _obscureNew = true;
  bool _obscureConfirm = true;
  bool _isChangingPwd = false;

  @override
  void dispose() {
    _currentPwdCtrl.dispose();
    _newPwdCtrl.dispose();
    _confirmPwdCtrl.dispose();
    super.dispose();
  }

  Future<void> _changePassword() async {
    if (!_pwdFormKey.currentState!.validate()) return;

    if (_newPwdCtrl.text != _confirmPwdCtrl.text) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('New password and confirmation do not match.')),
      );
      return;
    }

    setState(() => _isChangingPwd = true);

    final res = await ApiClient.post('/account/password', {
      'current_password': _currentPwdCtrl.text,
      'new_password': _newPwdCtrl.text,
    });

    setState(() => _isChangingPwd = false);

    if (res.success) {
      _currentPwdCtrl.clear();
      _newPwdCtrl.clear();
      _confirmPwdCtrl.clear();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res.message ?? 'Password updated successfully!')),
        );
      }
    } else {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text(res.message ?? 'Failed to update password. Verify your current password.')),
        );
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final member = context.watch<AuthProvider>().currentMember;

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.surface,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        title: const Text('Security & Verification', style: TextStyle(color: AppColors.textPrimary, fontSize: 18, fontWeight: FontWeight.bold)),
      ),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // Account Verification Status Card
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.border),
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(8),
                      decoration: BoxDecoration(
                        color: member?.isVerified == true ? AppColors.accentSoft : AppColors.primarySoft,
                        shape: BoxShape.circle,
                      ),
                      child: Icon(
                        member?.isVerified == true ? Icons.verified_user : Icons.gpp_maybe,
                        color: member?.isVerified == true ? AppColors.accent : AppColors.primary,
                        size: 24,
                      ),
                    ),
                    const SizedBox(width: 12),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            member?.isVerified == true ? 'Account Verified' : 'Standard Member Account',
                            style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 15, color: AppColors.textPrimary),
                          ),
                          Text(
                            'User ID: ${member?.userId ?? 'N/A'}',
                            style: const TextStyle(color: AppColors.textMuted, fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: member?.isVerified == true ? AppColors.accent.withOpacity(0.12) : AppColors.secondarySoft,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        member?.isVerified == true ? 'ACTIVE' : 'TIER 1',
                        style: TextStyle(
                          color: member?.isVerified == true ? AppColors.accent : AppColors.secondary,
                          fontWeight: FontWeight.bold,
                          fontSize: 11,
                        ),
                      ),
                    ),
                  ],
                ),
                const Divider(height: 24, color: AppColors.border),
                _buildStatusRow(Icons.email_outlined, 'Registered Email', member?.email ?? 'N/A', isVerified: true),
                const SizedBox(height: 10),
                _buildStatusRow(Icons.phone_android_outlined, 'Phone / SMS OTP', member?.phone?.isNotEmpty == true ? member!.phone! : 'Not linked', isVerified: member?.phone?.isNotEmpty == true),
                const SizedBox(height: 10),
                _buildStatusRow(Icons.currency_bitcoin, 'Web3 USDT BEP-20', member?.web3WalletAddress?.isNotEmpty == true ? 'Linked (${member!.web3WalletAddress!.substring(0, 6)}...)' : 'Not linked', isVerified: member?.web3WalletAddress?.isNotEmpty == true),
              ],
            ),
          ),
          const SizedBox(height: 24),

          // Change Password Card
          Container(
            padding: const EdgeInsets.all(18),
            decoration: BoxDecoration(
              color: AppColors.surface,
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: AppColors.border),
            ),
            child: Form(
              key: _pwdFormKey,
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Row(
                    children: [
                      Icon(Icons.lock_reset, color: AppColors.primary, size: 22),
                      SizedBox(width: 8),
                      Text('Change Password', style: TextStyle(fontWeight: FontWeight.bold, fontSize: 16, color: AppColors.textHeading)),
                    ],
                  ),
                  const SizedBox(height: 6),
                  const Text('Ensure your account is using a strong and unique password.', style: TextStyle(color: AppColors.textMuted, fontSize: 12)),
                  const SizedBox(height: 18),

                  // Current Password
                  _buildLabel('Current Password *'),
                  TextFormField(
                    controller: _currentPwdCtrl,
                    obscureText: _obscureCurrent,
                    validator: (v) => (v == null || v.isEmpty) ? 'Enter current password' : null,
                    decoration: _inputDecoration(
                      'Enter current password',
                      Icons.lock_outline,
                      suffix: IconButton(
                        icon: Icon(_obscureCurrent ? Icons.visibility_off : Icons.visibility, color: AppColors.textMuted, size: 20),
                        onPressed: () => setState(() => _obscureCurrent = !_obscureCurrent),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // New Password
                  _buildLabel('New Password (min 6 characters) *'),
                  TextFormField(
                    controller: _newPwdCtrl,
                    obscureText: _obscureNew,
                    validator: (v) {
                      if (v == null || v.length < 6) return 'Password must be at least 6 characters';
                      return null;
                    },
                    decoration: _inputDecoration(
                      'Enter new password',
                      Icons.vpn_key_outlined,
                      suffix: IconButton(
                        icon: Icon(_obscureNew ? Icons.visibility_off : Icons.visibility, color: AppColors.textMuted, size: 20),
                        onPressed: () => setState(() => _obscureNew = !_obscureNew),
                      ),
                    ),
                  ),
                  const SizedBox(height: 14),

                  // Confirm New Password
                  _buildLabel('Confirm New Password *'),
                  TextFormField(
                    controller: _confirmPwdCtrl,
                    obscureText: _obscureConfirm,
                    validator: (v) {
                      if (v == null || v.isEmpty) return 'Confirm your new password';
                      if (v != _newPwdCtrl.text) return 'Passwords do not match';
                      return null;
                    },
                    decoration: _inputDecoration(
                      'Re-enter new password',
                      Icons.check_circle_outline,
                      suffix: IconButton(
                        icon: Icon(_obscureConfirm ? Icons.visibility_off : Icons.visibility, color: AppColors.textMuted, size: 20),
                        onPressed: () => setState(() => _obscureConfirm = !_obscureConfirm),
                      ),
                    ),
                  ),
                  const SizedBox(height: 22),

                  // Submit Button
                  SizedBox(
                    width: double.infinity,
                    height: 48,
                    child: ElevatedButton(
                      onPressed: _isChangingPwd ? null : _changePassword,
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        foregroundColor: Colors.white,
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                        elevation: 0,
                      ),
                      child: _isChangingPwd
                          ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2))
                          : const Text('Update Password', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildStatusRow(IconData icon, String label, String value, {required bool isVerified}) {
    return Row(
      children: [
        Icon(icon, size: 18, color: AppColors.textMuted),
        const SizedBox(width: 10),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(label, style: const TextStyle(fontSize: 11, color: AppColors.textMuted)),
              Text(value, style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600, color: AppColors.textPrimary)),
            ],
          ),
        ),
        Icon(
          isVerified ? Icons.check_circle : Icons.radio_button_unchecked,
          color: isVerified ? AppColors.accent : AppColors.textMuted,
          size: 18,
        ),
      ],
    );
  }

  Widget _buildLabel(String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Text(text, style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13, color: AppColors.textPrimary)),
    );
  }

  InputDecoration _inputDecoration(String hint, IconData icon, {Widget? suffix}) {
    return InputDecoration(
      hintText: hint,
      hintStyle: const TextStyle(color: AppColors.textMuted, fontSize: 13),
      prefixIcon: Icon(icon, color: AppColors.textMuted, size: 18),
      suffixIcon: suffix,
      filled: true,
      fillColor: AppColors.background,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      border: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.border)),
      enabledBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.border)),
      focusedBorder: OutlineInputBorder(borderRadius: BorderRadius.circular(12), borderSide: const BorderSide(color: AppColors.primary, width: 1.5)),
    );
  }
}
