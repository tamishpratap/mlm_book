import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../core/session_manager.dart';
import '../../providers/auth_provider.dart';
import '../admin/admin_shell.dart';
import '../member/member_shell.dart';
import 'register_screen.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _emailController = TextEditingController();
  final _passwordController = TextEditingController();
  bool _obscurePassword = true;
  bool _isAdminMode = false;

  @override
  void dispose() {
    _emailController.dispose();
    _passwordController.dispose();
    super.dispose();
  }

  void _showServerConfigDialog() {
    final customUrlController = TextEditingController(text: ApiClient.baseUrl);

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        backgroundColor: AppColors.surface,
        title: const Text('API Server Settings', style: TextStyle(color: AppColors.textHeading, fontSize: 16)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Select backend server endpoint:',
              style: TextStyle(color: AppColors.textSecondary, fontSize: 13),
            ),
            const SizedBox(height: 12),
            ListTile(
              dense: true,
              contentPadding: EdgeInsets.zero,
              title: const Text('Localhost (Web / Desktop)', style: TextStyle(fontSize: 13)),
              subtitle: const Text('http://localhost:8000/api/v1/mobile', style: TextStyle(fontSize: 11)),
              onTap: () async {
                await SessionManager.setBaseUrl(AppConstants.localApiUrl);
                if (mounted) setState(() {});
                if (ctx.mounted) Navigator.pop(ctx);
              },
            ),
            ListTile(
              dense: true,
              contentPadding: EdgeInsets.zero,
              title: const Text('Android Emulator', style: TextStyle(fontSize: 13)),
              subtitle: const Text('http://10.0.2.2:8000/api/v1/mobile', style: TextStyle(fontSize: 11)),
              onTap: () async {
                await SessionManager.setBaseUrl(AppConstants.androidEmulatorApiUrl);
                if (mounted) setState(() {});
                if (ctx.mounted) Navigator.pop(ctx);
              },
            ),
            ListTile(
              dense: true,
              contentPadding: EdgeInsets.zero,
              title: const Text('Production Live Server', style: TextStyle(fontSize: 13)),
              subtitle: const Text('https://mlmbookai.com/backend/public/api/v1/mobile', style: TextStyle(fontSize: 11)),
              onTap: () async {
                await SessionManager.setBaseUrl(AppConstants.liveProductionApiUrl);
                if (mounted) setState(() {});
                if (ctx.mounted) Navigator.pop(ctx);
              },
            ),
            const SizedBox(height: 8),
            TextField(
              controller: customUrlController,
              decoration: const InputDecoration(
                labelText: 'Custom Server URL',
                hintText: 'http://192.168.1.X:8000/api/v1/mobile',
                isDense: true,
              ),
              style: const TextStyle(fontSize: 12),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel'),
          ),
          ElevatedButton(
            onPressed: () async {
              final newUrl = customUrlController.text.trim();
              if (newUrl.isNotEmpty) {
                await SessionManager.setBaseUrl(newUrl);
                if (mounted) setState(() {});
              }
              if (ctx.mounted) Navigator.pop(ctx);
            },
            child: const Text('Save'),
          ),
        ],
      ),
    );
  }

  Future<void> _handleLogin() async {
    final auth = context.read<AuthProvider>();
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    if (email.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Please enter both email and password.')),
      );
      return;
    }

    final success = _isAdminMode
        ? await auth.loginAdmin(email, password)
        : await auth.loginMember(email, password);

    if (success && mounted) {
      if (_isAdminMode) {
        Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const AdminShell()));
      } else {
        Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const MemberShell()));
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      backgroundColor: AppColors.background,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Server switch chip
                Row(
                  mainAxisAlignment: MainAxisAlignment.end,
                  children: [
                    InkWell(
                      onTap: _showServerConfigDialog,
                      borderRadius: BorderRadius.circular(16),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppColors.primarySoft,
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: AppColors.border),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            const Icon(Icons.dns_rounded, size: 14, color: AppColors.primary),
                            const SizedBox(width: 6),
                            Text(
                              Uri.tryParse(ApiClient.baseUrl)?.host ?? 'Server',
                              style: const TextStyle(fontSize: 11, color: AppColors.primary, fontWeight: FontWeight.bold),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                // Branding Header
                Center(
                  child: _isAdminMode
                      ? Container(
                          width: 72,
                          height: 72,
                          decoration: BoxDecoration(
                            gradient: const LinearGradient(colors: [Color(0xFF7C3AED), Color(0xFF9333EA)]),
                            borderRadius: BorderRadius.circular(20),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.purple.withOpacity(0.3),
                                blurRadius: 18,
                                offset: const Offset(0, 6),
                              ),
                            ],
                          ),
                          child: const Icon(
                            Icons.admin_panel_settings,
                            color: Colors.white,
                            size: 38,
                          ),
                        )
                      : ClipRRect(
                          borderRadius: BorderRadius.circular(16),
                          child: Image.asset(
                            'assets/images/logo.png',
                            width: 72,
                            height: 72,
                            fit: BoxFit.contain,
                            errorBuilder: (context, error, stackTrace) => Container(
                              width: 72,
                              height: 72,
                              decoration: BoxDecoration(
                                gradient: AppColors.primaryGradient,
                                borderRadius: BorderRadius.circular(16),
                              ),
                              child: const Icon(Icons.auto_stories, color: Colors.white, size: 38),
                            ),
                          ),
                        ),
                ),
                const SizedBox(height: 20),
                Center(
                  child: Text(
                    _isAdminMode ? 'Admin Control Center' : 'Welcome to MLM Book',
                    style: const TextStyle(
                      color: AppColors.textPrimary,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                ),
                const SizedBox(height: 6),
                Center(
                  child: Text(
                    _isAdminMode ? 'Sign in with administrator credentials' : 'Connect, collaborate, and earn rewards',
                    style: const TextStyle(color: AppColors.textMuted, fontSize: 13),
                  ),
                ),
                const SizedBox(height: 28),

                // Mode Toggle (Member / Admin)
                Container(
                  padding: const EdgeInsets.all(4),
                  decoration: BoxDecoration(
                    color: AppColors.surface,
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: AppColors.border),
                  ),
                  child: Row(
                    children: [
                      Expanded(
                        child: GestureDetector(
                          onTap: () => setState(() => _isAdminMode = false),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            decoration: BoxDecoration(
                              color: !_isAdminMode ? AppColors.primary : Colors.transparent,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Center(
                              child: Text(
                                'Member Login',
                                style: TextStyle(
                                  color: !_isAdminMode ? Colors.white : AppColors.textSecondary,
                                  fontWeight: FontWeight.w600,
                                  fontSize: 14,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                      Expanded(
                        child: GestureDetector(
                          onTap: () => setState(() => _isAdminMode = true),
                          child: Container(
                            padding: const EdgeInsets.symmetric(vertical: 10),
                            decoration: BoxDecoration(
                              color: _isAdminMode ? const Color(0xFF7C3AED) : Colors.transparent,
                              borderRadius: BorderRadius.circular(8),
                            ),
                            child: Center(
                              child: Text(
                                'Admin Portal',
                                style: TextStyle(
                                  color: _isAdminMode ? Colors.white : AppColors.textSecondary,
                                  fontWeight: FontWeight.w600,
                                  fontSize: 14,
                                ),
                              ),
                            ),
                          ),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 24),

                // Error Banner
                if (auth.errorMessage != null)
                  Container(
                    margin: const EdgeInsets.only(bottom: 20),
                    padding: const EdgeInsets.all(12),
                    decoration: BoxDecoration(
                      color: AppColors.danger.withOpacity(0.15),
                      border: Border.all(color: AppColors.danger.withOpacity(0.5)),
                      borderRadius: BorderRadius.circular(10),
                    ),
                    child: Text(
                      auth.errorMessage!,
                      style: const TextStyle(color: AppColors.danger, fontSize: 13),
                    ),
                  ),

                // Email / User ID Input
                TextField(
                  controller: _emailController,
                  keyboardType: TextInputType.text,
                  style: const TextStyle(color: AppColors.textPrimary),
                  decoration: InputDecoration(
                    labelText: _isAdminMode ? 'Admin Email Address' : 'Email or User ID',
                    labelStyle: const TextStyle(color: AppColors.textSecondary),
                    prefixIcon: const Icon(Icons.email_outlined, color: AppColors.textMuted),
                    filled: true,
                    fillColor: AppColors.surface,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: AppColors.border),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: AppColors.border),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(
                        color: _isAdminMode ? const Color(0xFF7C3AED) : AppColors.primary,
                        width: 1.5,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // Password Input
                TextField(
                  controller: _passwordController,
                  obscureText: _obscurePassword,
                  style: const TextStyle(color: AppColors.textPrimary),
                  decoration: InputDecoration(
                    labelText: 'Password',
                    labelStyle: const TextStyle(color: AppColors.textSecondary),
                    prefixIcon: const Icon(Icons.lock_outline, color: AppColors.textMuted),
                    suffixIcon: IconButton(
                      icon: Icon(
                        _obscurePassword ? Icons.visibility_off : Icons.visibility,
                        color: AppColors.textMuted,
                      ),
                      onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                    ),
                    filled: true,
                    fillColor: AppColors.surface,
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: AppColors.border),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: AppColors.border),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(
                        color: _isAdminMode ? const Color(0xFF7C3AED) : AppColors.primary,
                        width: 1.5,
                      ),
                    ),
                  ),
                ),
                const SizedBox(height: 24),

                // Submit Button
                ElevatedButton(
                  onPressed: auth.isLoading ? null : _handleLogin,
                  style: ElevatedButton.styleFrom(
                    backgroundColor: _isAdminMode ? const Color(0xFF7C3AED) : AppColors.primary,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                    elevation: 4,
                  ),
                  child: auth.isLoading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : Text(
                          _isAdminMode ? 'Login to Admin Center' : 'Sign In',
                          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.white),
                        ),
                ),
                const SizedBox(height: 24),

                // Register Link (for Members)
                if (!_isAdminMode)
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Text(
                        "Don't have an account? ",
                        style: TextStyle(color: AppColors.textMuted, fontSize: 14),
                      ),
                      GestureDetector(
                        onTap: () {
                          Navigator.push(context, MaterialPageRoute(builder: (_) => const RegisterScreen()));
                        },
                        child: const Text(
                          'Register Now',
                          style: TextStyle(
                            color: AppColors.primary,
                            fontWeight: FontWeight.bold,
                            fontSize: 14,
                          ),
                        ),
                      ),
                    ],
                  ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
