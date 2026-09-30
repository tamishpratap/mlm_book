import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../providers/auth_provider.dart';
import '../admin/admin_shell.dart';
import '../member/member_shell.dart';
import 'google_introducer_screen.dart';
import 'login_screen.dart';
import 'register_screen.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _checkAuth();
  }

  Future<void> _checkAuth() async {
    final auth = context.read<AuthProvider>();
    await auth.initAuth();
    await Future.delayed(const Duration(milliseconds: 1200));

    if (!mounted) return;

    final uri = Uri.base;
    final queryParams = uri.queryParameters;

    final authSuccess = queryParams['auth_success'] == '1';
    final mobileToken = queryParams['mobile_token'] ?? queryParams['token'];
    final googleToken = queryParams['google_token'];
    final error = queryParams['error'];
    final email = queryParams['email'];
    final ref = queryParams['ref'] ?? queryParams['introducer'];

    // 1. Google OAuth successful login token return
    if (authSuccess && mobileToken != null && mobileToken.isNotEmpty) {
      final success = await auth.loginWithMobileToken(
        mobileToken,
        initialMemberData: {
          if (queryParams['user_id'] != null) 'user_id': queryParams['user_id'],
          if (queryParams['email'] != null) 'email': queryParams['email'],
          if (queryParams['name'] != null) 'name': queryParams['name'],
        },
      );
      if (mounted) {
        if (success) {
          Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const MemberShell()));
          return;
        } else {
          Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (_) => LoginScreen(initialErrorMessage: auth.errorMessage)),
          );
          return;
        }
      }
    }

    // 2. Google OAuth new registration pending token return
    if (googleToken != null && googleToken.isNotEmpty) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => GoogleIntroducerScreen(
            token: googleToken,
            initialRef: ref,
          ),
        ),
      );
      return;
    }

    // 3. Signup required error
    if (error == 'signup_required') {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => LoginScreen(
            initialError: 'signup_required',
            email: email,
            ref: ref,
          ),
        ),
      );
      return;
    }

    // 4. Account already exists error
    if (error == 'account_exists') {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => RegisterScreen(
            initialError: 'account_exists',
            email: email,
            ref: ref,
          ),
        ),
      );
      return;
    }

    // 5. Other general error (e.g. account blocked or cancelled)
    if (error != null && error.isNotEmpty) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(
          builder: (_) => LoginScreen(
            initialErrorMessage: error,
            email: email,
          ),
        ),
      );
      return;
    }

    // 6. Regular standard auth check
    if (auth.isAuthenticated) {
      if (auth.activeRole == 'admin') {
        Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const AdminShell()));
      } else {
        Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const MemberShell()));
      }
    } else {
      Navigator.pushReplacement(context, MaterialPageRoute(builder: (_) => const LoginScreen()));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.background,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 90,
              height: 90,
              decoration: BoxDecoration(
                gradient: AppColors.primaryGradient,
                borderRadius: BorderRadius.circular(24),
                boxShadow: [
                  BoxShadow(
                    color: AppColors.primary.withOpacity(0.4),
                    blurRadius: 24,
                    offset: const Offset(0, 8),
                  ),
                ],
              ),
              child: const Icon(Icons.auto_stories, color: Colors.white, size: 48),
            ),
            const SizedBox(height: 24),
            const Text(
              AppConstants.appName,
              style: TextStyle(
                color: AppColors.textPrimary,
                fontSize: 28,
                fontWeight: FontWeight.bold,
                letterSpacing: 1.2,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'Enterprise Social Commerce & Web3 Ad Network',
              style: TextStyle(
                color: AppColors.textMuted,
                fontSize: 13,
              ),
            ),
            const SizedBox(height: 36),
            const SizedBox(
              width: 24,
              height: 24,
              child: CircularProgressIndicator(
                strokeWidth: 2.5,
                color: AppColors.primary,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
