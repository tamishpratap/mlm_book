import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../core/session_manager.dart';
import '../../providers/auth_provider.dart';
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
  bool _isGoogleLoading = false;

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
        backgroundColor: Colors.white,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
        title: const Row(
          children: [
            Icon(Icons.dns_rounded, color: Color(0xFF2563EB), size: 20),
            SizedBox(width: 8),
            Text(
              'API Server Settings',
              style: TextStyle(
                color: Color(0xFF0F172A),
                fontSize: 16,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            const Text(
              'Select backend server endpoint:',
              style: TextStyle(color: Color(0xFF64748B), fontSize: 13),
            ),
            const SizedBox(height: 12),
            ListTile(
              dense: true,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
              title: const Text('Localhost (Web / Desktop)', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
              subtitle: const Text('http://localhost:8000/api/v1/mobile', style: TextStyle(fontSize: 11)),
              onTap: () async {
                await SessionManager.setBaseUrl(AppConstants.localApiUrl);
                if (mounted) setState(() {});
                if (ctx.mounted) Navigator.pop(ctx);
              },
            ),
            ListTile(
              dense: true,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
              title: const Text('Android Emulator', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
              subtitle: const Text('http://10.0.2.2:8000/api/v1/mobile', style: TextStyle(fontSize: 11)),
              onTap: () async {
                await SessionManager.setBaseUrl(AppConstants.androidEmulatorApiUrl);
                if (mounted) setState(() {});
                if (ctx.mounted) Navigator.pop(ctx);
              },
            ),
            ListTile(
              dense: true,
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
              contentPadding: const EdgeInsets.symmetric(horizontal: 10, vertical: 2),
              title: const Text('Production Live Server', style: TextStyle(fontSize: 13, fontWeight: FontWeight.w600)),
              subtitle: const Text('https://mlmbookai.com/backend/public/api/v1/mobile', style: TextStyle(fontSize: 11)),
              onTap: () async {
                await SessionManager.setBaseUrl(AppConstants.liveProductionApiUrl);
                if (mounted) setState(() {});
                if (ctx.mounted) Navigator.pop(ctx);
              },
            ),
            const SizedBox(height: 12),
            TextField(
              controller: customUrlController,
              decoration: InputDecoration(
                labelText: 'Custom Server URL',
                hintText: 'http://192.168.1.X:8000/api/v1/mobile',
                isDense: true,
                border: OutlineInputBorder(borderRadius: BorderRadius.circular(10)),
              ),
              style: const TextStyle(fontSize: 12),
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('Cancel', style: TextStyle(color: Color(0xFF64748B))),
          ),
          ElevatedButton(
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF2563EB),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
            ),
            onPressed: () async {
              final newUrl = customUrlController.text.trim();
              if (newUrl.isNotEmpty) {
                await SessionManager.setBaseUrl(newUrl);
                if (mounted) setState(() {});
              }
              if (ctx.mounted) Navigator.pop(ctx);
            },
            child: const Text('Save', style: TextStyle(color: Colors.white)),
          ),
        ],
      ),
    );
  }

  void _showForgotPasswordDialog() {
    final resetEmailController = TextEditingController(text: _emailController.text.trim());
    bool isSending = false;

    showDialog(
      context: context,
      builder: (ctx) => StatefulBuilder(
        builder: (context, setModalState) {
          return AlertDialog(
            backgroundColor: Colors.white,
            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
            title: const Row(
              children: [
                Icon(Icons.lock_reset_rounded, color: Color(0xFF2563EB), size: 24),
                SizedBox(width: 10),
                Text(
                  'Reset Password',
                  style: TextStyle(
                    color: Color(0xFF0F172A),
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                  ),
                ),
              ],
            ),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const Text(
                  'Enter your registered email address to receive password reset instructions.',
                  style: TextStyle(color: Color(0xFF64748B), fontSize: 13, height: 1.4),
                ),
                const SizedBox(height: 16),
                TextField(
                  controller: resetEmailController,
                  keyboardType: TextInputType.emailAddress,
                  decoration: InputDecoration(
                    labelText: 'Email address',
                    hintText: 'name@example.com',
                    prefixIcon: const Icon(Icons.mail_outline_rounded, color: Color(0xFF2563EB)),
                    filled: true,
                    fillColor: const Color(0xFFF1F5F9),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide.none,
                    ),
                  ),
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx),
                child: const Text('Cancel', style: TextStyle(color: Color(0xFF64748B))),
              ),
              ElevatedButton(
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF2563EB),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                  padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 10),
                ),
                onPressed: isSending
                    ? null
                    : () async {
                        final email = resetEmailController.text.trim();
                        if (email.isEmpty) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('Please enter your email address.')),
                          );
                          return;
                        }

                        setModalState(() => isSending = true);
                        final auth = context.read<AuthProvider>();
                        final sent = await auth.sendPasswordReset(email);
                        setModalState(() => isSending = false);

                        if (ctx.mounted) Navigator.pop(ctx);
                        if (mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            SnackBar(
                              content: Text(sent
                                  ? 'Password reset instructions sent to $email.'
                                  : (auth.errorMessage ?? 'Unable to send reset email.')),
                              backgroundColor: sent ? const Color(0xFF10B981) : const Color(0xFFEF4444),
                              behavior: SnackBarBehavior.floating,
                            ),
                          );
                        }
                      },
                child: isSending
                    ? const SizedBox(
                        width: 16,
                        height: 16,
                        child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                      )
                    : const Text('Send Link', style: TextStyle(color: Colors.white, fontWeight: FontWeight.w600)),
              ),
            ],
          );
        },
      ),
    );
  }

  Future<void> _handleLogin() async {
    final email = _emailController.text.trim();
    final password = _passwordController.text;

    if (email.isEmpty || password.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Row(
            children: [
              Icon(Icons.info_outline, color: Colors.white, size: 18),
              SizedBox(width: 8),
              Text('Please enter your email address and password.'),
            ],
          ),
          behavior: SnackBarBehavior.floating,
          backgroundColor: const Color(0xFF1E293B),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
        ),
      );
      return;
    }

    final auth = context.read<AuthProvider>();
    final success = await auth.loginMember(email, password);

    if (success && mounted) {
      Navigator.pushReplacement(
        context,
        MaterialPageRoute(builder: (_) => const MemberShell()),
      );
    }
  }

  Future<void> _handleGoogleLogin() async {
    setState(() => _isGoogleLoading = true);
    try {
      // Prompt quick information / Google authorization message
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: const Row(
            children: [
              SizedBox(
                width: 16,
                height: 16,
                child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
              ),
              SizedBox(width: 12),
              Text('Connecting to Google Sign-In service...'),
            ],
          ),
          behavior: SnackBarBehavior.floating,
          backgroundColor: const Color(0xFF1E293B),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
          duration: const Duration(seconds: 2),
        ),
      );

      // In real deployment, triggers Google OAuth redirect endpoint
      await Future.delayed(const Duration(seconds: 1));
    } finally {
      if (mounted) {
        setState(() => _isGoogleLoading = false);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();

    return Scaffold(
      backgroundColor: const Color(0xFFF9FAFD),
      body: Stack(
        children: [
          // Ambient Background Decorative Shapes
          const Positioned.fill(
            child: _LoginBackgroundDecorations(),
          ),

          SafeArea(
            child: Center(
              child: SingleChildScrollView(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
                child: ConstrainedBox(
                  constraints: const BoxConstraints(maxWidth: 440),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.stretch,
                    children: [
                      // Top Row: Logo & Server Config Chip
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          // App Branding Logo
                          Row(
                            children: [
                              ClipRRect(
                                borderRadius: BorderRadius.circular(12),
                                child: Image.asset(
                                  'assets/images/logo.png',
                                  width: 48,
                                  height: 48,
                                  fit: BoxFit.contain,
                                  errorBuilder: (_, _, _) => Container(
                                    width: 48,
                                    height: 48,
                                    decoration: BoxDecoration(
                                      gradient: const LinearGradient(
                                        colors: [Color(0xFF2563EB), Color(0xFF7C3AED)],
                                      ),
                                      borderRadius: BorderRadius.circular(12),
                                    ),
                                    child: const Icon(Icons.auto_stories, color: Colors.white, size: 24),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              const Text(
                                'MLM Book',
                                style: TextStyle(
                                  fontSize: 18,
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFF0F172A),
                                  letterSpacing: -0.3,
                                ),
                              ),
                            ],
                          ),

                          // Server endpoint badge
                          InkWell(
                            onTap: _showServerConfigDialog,
                            borderRadius: BorderRadius.circular(20),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(20),
                                border: Border.all(color: const Color(0xFFE2E8F0)),
                                boxShadow: [
                                  BoxShadow(
                                    color: Colors.black.withOpacity(0.02),
                                    blurRadius: 4,
                                    offset: const Offset(0, 2),
                                  ),
                                ],
                              ),
                              child: Row(
                                mainAxisSize: MainAxisSize.min,
                                children: [
                                  Container(
                                    width: 7,
                                    height: 7,
                                    decoration: const BoxDecoration(
                                      color: Color(0xFF10B981),
                                      shape: BoxShape.circle,
                                    ),
                                  ),
                                  const SizedBox(width: 6),
                                  Text(
                                    Uri.tryParse(ApiClient.baseUrl)?.host ?? 'Server',
                                    style: const TextStyle(
                                      fontSize: 11,
                                      color: Color(0xFF64748B),
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 36),

                      // Heading Title & Subtitle
                      const Text(
                        'Welcome back!',
                        style: TextStyle(
                          fontSize: 32,
                          fontWeight: FontWeight.w800,
                          color: Color(0xFF0F172A),
                          letterSpacing: -0.8,
                        ),
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Enter your details to access your account.',
                        style: TextStyle(
                          fontSize: 14,
                          color: Color(0xFF64748B),
                          fontWeight: FontWeight.w400,
                        ),
                      ),
                      const SizedBox(height: 32),

                      // Error message if any
                      if (auth.errorMessage != null)
                        Container(
                          margin: const EdgeInsets.only(bottom: 20),
                          padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                          decoration: BoxDecoration(
                            color: const Color(0xFFFEE2E2),
                            border: Border.all(color: const Color(0xFFFCA5A5)),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Row(
                            children: [
                              const Icon(Icons.error_outline_rounded, color: Color(0xFFEF4444), size: 18),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Text(
                                  auth.errorMessage!,
                                  style: const TextStyle(
                                    color: Color(0xFFB91C1C),
                                    fontSize: 13,
                                    fontWeight: FontWeight.w500,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),

                      // Email Address Input Section
                      const Text(
                        'Email address',
                        style: TextStyle(
                          fontSize: 13,
                          fontWeight: FontWeight.w600,
                          color: Color(0xFF1E293B),
                        ),
                      ),
                      const SizedBox(height: 8),
                      Container(
                        decoration: BoxDecoration(
                          color: const Color(0xFFEEF3FF),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(
                            color: const Color(0xFF3B82F6).withOpacity(0.8),
                            width: 1.4,
                          ),
                        ),
                        child: TextField(
                          controller: _emailController,
                          keyboardType: TextInputType.emailAddress,
                          textInputAction: TextInputAction.next,
                          style: const TextStyle(
                            color: Color(0xFF0F172A),
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                          ),
                          decoration: const InputDecoration(
                            hintText: 'pro@cpanel.com',
                            hintStyle: TextStyle(color: Color(0xFF94A3B8), fontSize: 14),
                            prefixIcon: Icon(
                              Icons.mail_outline_rounded,
                              color: Color(0xFF2563EB),
                              size: 20,
                            ),
                            border: InputBorder.none,
                            contentPadding: EdgeInsets.symmetric(horizontal: 16, vertical: 15),
                          ),
                        ),
                      ),
                      const SizedBox(height: 20),

                      // Password Input Section
                      Row(
                        mainAxisAlignment: MainAxisAlignment.spaceBetween,
                        children: [
                          const Text(
                            'Password',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.w600,
                              color: Color(0xFF1E293B),
                            ),
                          ),
                          GestureDetector(
                            onTap: _showForgotPasswordDialog,
                            child: const Text(
                              'Forgot password?',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                                color: Color(0xFF2563EB),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      Container(
                        decoration: BoxDecoration(
                          color: const Color(0xFFEEF3FF),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(
                            color: Colors.transparent,
                            width: 1.4,
                          ),
                        ),
                        child: TextField(
                          controller: _passwordController,
                          obscureText: _obscurePassword,
                          textInputAction: TextInputAction.done,
                          onSubmitted: (_) => _handleLogin(),
                          style: const TextStyle(
                            color: Color(0xFF0F172A),
                            fontSize: 14,
                            fontWeight: FontWeight.w500,
                          ),
                          decoration: InputDecoration(
                            hintText: '••••••',
                            hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 14),
                            prefixIcon: const Icon(
                              Icons.lock_outline_rounded,
                              color: Color(0xFF94A3B8),
                              size: 20,
                            ),
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscurePassword ? Icons.visibility_outlined : Icons.visibility_off_outlined,
                                color: const Color(0xFF94A3B8),
                                size: 20,
                              ),
                              onPressed: () => setState(() => _obscurePassword = !_obscurePassword),
                            ),
                            border: InputBorder.none,
                            contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 15),
                          ),
                        ),
                      ),
                      const SizedBox(height: 24),

                      // Modern Vibrant Gradient Login Button
                      Container(
                        height: 52,
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFF2563EB), Color(0xFF7C3AED)],
                            begin: Alignment.centerLeft,
                            end: Alignment.centerRight,
                          ),
                          borderRadius: BorderRadius.circular(14),
                          boxShadow: [
                            BoxShadow(
                              color: const Color(0xFF3B82F6).withOpacity(0.35),
                              blurRadius: 18,
                              offset: const Offset(0, 6),
                            ),
                          ],
                        ),
                        child: Material(
                          color: Colors.transparent,
                          child: InkWell(
                            onTap: auth.isLoading ? null : _handleLogin,
                            borderRadius: BorderRadius.circular(14),
                            child: Center(
                              child: auth.isLoading
                                  ? const SizedBox(
                                      width: 22,
                                      height: 22,
                                      child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                                    )
                                  : const Row(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        Icon(Icons.login_rounded, color: Colors.white, size: 20),
                                        SizedBox(width: 8),
                                        Text(
                                          'Login',
                                          style: TextStyle(
                                            fontSize: 16,
                                            fontWeight: FontWeight.w700,
                                            color: Colors.white,
                                            letterSpacing: 0.2,
                                          ),
                                        ),
                                      ],
                                    ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 26),

                      // Divider "or continue with"
                      Row(
                        children: [
                          Expanded(
                            child: Container(
                              height: 1,
                              color: const Color(0xFFE2E8F0),
                            ),
                          ),
                          const Padding(
                            padding: EdgeInsets.symmetric(horizontal: 14),
                            child: Text(
                              'or continue with',
                              style: TextStyle(
                                color: Color(0xFF94A3B8),
                                fontSize: 13,
                                fontWeight: FontWeight.w400,
                              ),
                            ),
                          ),
                          Expanded(
                            child: Container(
                              height: 1,
                              color: const Color(0xFFE2E8F0),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 22),

                      // "Login with Google" Button
                      Container(
                        height: 52,
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.03),
                              blurRadius: 10,
                              offset: const Offset(0, 3),
                            ),
                          ],
                        ),
                        child: Material(
                          color: Colors.transparent,
                          child: InkWell(
                            onTap: _isGoogleLoading ? null : _handleGoogleLogin,
                            borderRadius: BorderRadius.circular(14),
                            child: Center(
                              child: _isGoogleLoading
                                  ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF2563EB)),
                                    )
                                  : const Row(
                                      mainAxisAlignment: MainAxisAlignment.center,
                                      children: [
                                        _GoogleLogoIcon(size: 20),
                                        SizedBox(width: 10),
                                        Text(
                                          'Login with Google',
                                          style: TextStyle(
                                            fontSize: 15,
                                            fontWeight: FontWeight.w600,
                                            color: Color(0xFF1E293B),
                                          ),
                                        ),
                                      ],
                                    ),
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 36),

                      // Security Guarantee
                      const Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          Icon(
                            Icons.shield_outlined,
                            color: Color(0xFF10B981),
                            size: 18,
                          ),
                          SizedBox(width: 8),
                          Text(
                            'Your data is protected and secure with us.',
                            style: TextStyle(
                              color: Color(0xFF64748B),
                              fontSize: 13,
                              fontWeight: FontWeight.w400,
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 14),

                      // Register Link
                      Row(
                        mainAxisAlignment: MainAxisAlignment.center,
                        children: [
                          const Text(
                            "Don't have an account? ",
                            style: TextStyle(
                              color: Color(0xFF64748B),
                              fontSize: 14,
                              fontWeight: FontWeight.w400,
                            ),
                          ),
                          GestureDetector(
                            onTap: () {
                              Navigator.push(
                                context,
                                MaterialPageRoute(builder: (_) => const RegisterScreen()),
                              );
                            },
                            child: const Text(
                              'Create account',
                              style: TextStyle(
                                color: Color(0xFF2563EB),
                                fontWeight: FontWeight.w700,
                                fontSize: 14,
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 12),
                    ],
                  ),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

/// Official Multi-Color Google G Logo
class _GoogleLogoIcon extends StatelessWidget {
  final double size;
  const _GoogleLogoIcon({this.size = 20});

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      size: Size(size, size),
      painter: _GoogleGLogoPainter(),
    );
  }
}

class _GoogleGLogoPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    final center = Offset(size.width / 2, size.height / 2);
    final radius = size.width / 2;
    final strokeWidth = size.width * 0.22;

    final paintBlue = Paint()
      ..color = const Color(0xFF4285F4)
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.butt;

    final paintGreen = Paint()
      ..color = const Color(0xFF34A853)
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.butt;

    final paintYellow = Paint()
      ..color = const Color(0xFFFBBC05)
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.butt;

    final paintRed = Paint()
      ..color = const Color(0xFFEA4335)
      ..style = PaintingStyle.stroke
      ..strokeWidth = strokeWidth
      ..strokeCap = StrokeCap.butt;

    final rect = Rect.fromCircle(center: center, radius: radius - strokeWidth / 2);

    // Blue arc + bar
    canvas.drawArc(rect, -math.pi / 4, math.pi / 2, false, paintBlue);

    // Green arc
    canvas.drawArc(rect, math.pi / 4, math.pi / 2, false, paintGreen);

    // Yellow arc
    canvas.drawArc(rect, 3 * math.pi / 4, math.pi / 2, false, paintYellow);

    // Red arc
    canvas.drawArc(rect, -3 * math.pi / 4, math.pi / 2, false, paintRed);

    // Center horizontal bar for Google G
    final barPaint = Paint()
      ..color = const Color(0xFF4285F4)
      ..style = PaintingStyle.fill;

    final barRect = Rect.fromLTRB(
      center.dx,
      center.dy - strokeWidth / 2,
      size.width - strokeWidth * 0.1,
      center.dy + strokeWidth / 2,
    );
    canvas.drawRect(barRect, barPaint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}

/// Decorative Vector and Ring Background
class _LoginBackgroundDecorations extends StatelessWidget {
  const _LoginBackgroundDecorations();

  @override
  Widget build(BuildContext context) {
    return CustomPaint(
      painter: _BackgroundAccentsPainter(),
    );
  }
}

class _BackgroundAccentsPainter extends CustomPainter {
  @override
  void paint(Canvas canvas, Size size) {
    // Top-Right Outer Ring
    final topCircleCenter = Offset(size.width * 1.05, size.height * 0.08);
    final topRingPaint = Paint()
      ..color = const Color(0xFF6366F1).withOpacity(0.08)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 24;
    canvas.drawCircle(topCircleCenter, size.width * 0.45, topRingPaint);

    final topInnerRingPaint = Paint()
      ..color = const Color(0xFF818CF8).withOpacity(0.06)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 14;
    canvas.drawCircle(topCircleCenter, size.width * 0.30, topInnerRingPaint);

    // Top-Right Glowing Purple Dot
    final topDotCenter = Offset(size.width * 0.92, size.height * 0.07);
    final topDotPaint = Paint()
      ..color = const Color(0xFF7C3AED)
      ..style = PaintingStyle.fill;
    canvas.drawCircle(topDotCenter, 7, topDotPaint);

    // Bottom-Right Concentric Rings
    final bottomCenter = Offset(size.width * 0.96, size.height * 0.95);
    final bottomRingPaint1 = Paint()
      ..color = const Color(0xFF6366F1).withOpacity(0.07)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 36;
    canvas.drawCircle(bottomCenter, size.width * 0.46, bottomRingPaint1);

    final bottomRingPaint2 = Paint()
      ..color = const Color(0xFF818CF8).withOpacity(0.05)
      ..style = PaintingStyle.stroke
      ..strokeWidth = 16;
    canvas.drawCircle(bottomCenter, size.width * 0.28, bottomRingPaint2);

    // Bottom-Right Glowing Purple Dot
    final bottomDotCenter = Offset(size.width * 0.94, size.height * 0.94);
    final bottomDotPaint = Paint()
      ..color = const Color(0xFF7C3AED)
      ..style = PaintingStyle.fill;
    canvas.drawCircle(bottomDotCenter, 8, bottomDotPaint);

    // Dot Matrix Pattern at Bottom Right
    final dotMatrixPaint = Paint()
      ..color = const Color(0xFF6366F1).withOpacity(0.22)
      ..style = PaintingStyle.fill;

    const rows = 6;
    const cols = 6;
    const spacing = 14.0;
    final startX = size.width * 0.72;
    final startY = size.height * 0.88;

    for (int r = 0; r < rows; r++) {
      for (int c = 0; c < cols; c++) {
        canvas.drawCircle(
          Offset(startX + (c * spacing), startY + (r * spacing)),
          1.8,
          dotMatrixPaint,
        );
      }
    }
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
