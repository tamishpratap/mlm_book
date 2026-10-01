import 'dart:async';
import 'dart:math' as math;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../member/member_shell.dart';
import 'google_introducer_screen.dart';
import 'login_screen.dart';
import 'register_screen.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen>
    with TickerProviderStateMixin {
  late final AnimationController _introController;
  late final AnimationController _waveController;
  late final Animation<double> _scaleAnimation;
  late final Animation<double> _fadeAnimation;
  late final Animation<Offset> _slideAnimation;

  @override
  void initState() {
    super.initState();

    // 1. Entrance animation (1.6s)
    _introController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1600),
    );

    // 2. Continuous flowing color wave animation
    _waveController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 4000),
    )..repeat();

    _scaleAnimation = Tween<double>(begin: 0.72, end: 1.0).animate(
      CurvedAnimation(
        parent: _introController,
        curve: const Interval(0.0, 0.75, curve: Curves.easeOutBack),
      ),
    );

    _fadeAnimation = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(
        parent: _introController,
        curve: const Interval(0.0, 0.55, curve: Curves.easeIn),
      ),
    );

    _slideAnimation = Tween<Offset>(
      begin: const Offset(0, 0.35),
      end: Offset.zero,
    ).animate(
      CurvedAnimation(
        parent: _introController,
        curve: const Interval(0.25, 0.85, curve: Curves.easeOutCubic),
      ),
    );

    _introController.forward();
    _checkAuth();
  }

  @override
  void dispose() {
    _introController.dispose();
    _waveController.dispose();
    super.dispose();
  }

  Future<void> _checkAuth() async {
    final auth = context.read<AuthProvider>();
    await auth.initAuth();
    await Future.delayed(const Duration(milliseconds: 2000));

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
          _navigateTo(const MemberShell());
          return;
        } else {
          _navigateTo(LoginScreen(initialErrorMessage: auth.errorMessage));
          return;
        }
      }
    }

    // 2. Google OAuth new registration pending token return
    if (googleToken != null && googleToken.isNotEmpty) {
      _navigateTo(GoogleIntroducerScreen(
        token: googleToken,
        initialRef: ref,
      ));
      return;
    }

    // 3. Signup required error
    if (error == 'signup_required') {
      _navigateTo(LoginScreen(
        initialError: 'signup_required',
        email: email,
        ref: ref,
      ));
      return;
    }

    // 4. Account already exists error
    if (error == 'account_exists') {
      _navigateTo(RegisterScreen(
        initialError: 'account_exists',
        email: email,
        ref: ref,
      ));
      return;
    }

    // 5. Other general error
    if (error != null && error.isNotEmpty) {
      _navigateTo(LoginScreen(
        initialErrorMessage: error,
        email: email,
      ));
      return;
    }

    // 6. Regular standard auth check
    if (auth.isAuthenticated) {
      _navigateTo(const MemberShell());
    } else {
      _navigateTo(const LoginScreen());
    }
  }

  void _navigateTo(Widget screen) {
    Navigator.pushReplacement(
      context,
      PageRouteBuilder(
        pageBuilder: (context, animation, secondaryAnimation) => screen,
        transitionsBuilder: (context, animation, secondaryAnimation, child) {
          return FadeTransition(opacity: animation, child: child);
        },
        transitionDuration: const Duration(milliseconds: 400),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final size = MediaQuery.of(context).size;

    return Scaffold(
      backgroundColor: Colors.white,
      body: Stack(
        children: [
          // 1. Soft Ambient Radial Light Blobs on White
          Positioned(
            top: -80,
            left: -60,
            child: Container(
              width: 260,
              height: 260,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    const Color(0xFF00D2FF).withOpacity(0.12),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
          ),
          Positioned(
            top: size.height * 0.25,
            right: -80,
            child: Container(
              width: 260,
              height: 260,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                gradient: RadialGradient(
                  colors: [
                    const Color(0xFF9B51E0).withOpacity(0.08),
                    Colors.transparent,
                  ],
                ),
              ),
            ),
          ),

          // 2. Continuous Animated Fluid Color Wave at Bottom
          Positioned(
            left: 0,
            right: 0,
            bottom: 0,
            height: size.height * 0.32,
            child: AnimatedBuilder(
              animation: _waveController,
              builder: (context, child) {
                return CustomPaint(
                  size: Size(size.width, size.height * 0.32),
                  painter: _ColorWavePainter(
                    animationValue: _waveController.value,
                  ),
                );
              },
            ),
          ),

          // 3. Central Animated Branding & Typography (Full Width Centered)
          Positioned.fill(
            child: SafeArea(
              child: SizedBox.expand(
                child: Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 28),
                  child: Column(
                    mainAxisAlignment: MainAxisAlignment.center,
                    crossAxisAlignment: CrossAxisAlignment.center,
                    children: [
                      const Spacer(flex: 4),

                      // Logo with Entrance Scale, Fade, and Soft Glow
                      AnimatedBuilder(
                        animation: _introController,
                        builder: (context, child) {
                          return Center(
                            child: Transform.scale(
                              scale: _scaleAnimation.value,
                              child: Opacity(
                                opacity: _fadeAnimation.value,
                                child: Stack(
                                  alignment: Alignment.center,
                                  children: [
                                    // Soft brand shadow aura on pure white
                                    Container(
                                      width: 190,
                                      height: 190,
                                      decoration: BoxDecoration(
                                        shape: BoxShape.circle,
                                        boxShadow: [
                                          BoxShadow(
                                            color: const Color(0xFF00D2FF).withOpacity(0.18),
                                            blurRadius: 40,
                                            spreadRadius: 8,
                                          ),
                                          BoxShadow(
                                            color: const Color(0xFF9B51E0).withOpacity(0.14),
                                            blurRadius: 45,
                                            spreadRadius: 6,
                                          ),
                                        ],
                                      ),
                                    ),

                                    // High-Res MLM Book AI Logo
                                    Container(
                                      constraints: const BoxConstraints(
                                        maxWidth: 260,
                                        maxHeight: 190,
                                      ),
                                      child: Image.asset(
                                        'assets/images/logo.png',
                                        fit: BoxFit.contain,
                                        alignment: Alignment.center,
                                        errorBuilder: (_, _, _) => Container(
                                          width: 90,
                                          height: 90,
                                          decoration: BoxDecoration(
                                            gradient: const LinearGradient(
                                              colors: [Color(0xFF00D2FF), Color(0xFF9B51E0)],
                                            ),
                                            borderRadius: BorderRadius.circular(24),
                                          ),
                                          child: const Icon(
                                            Icons.auto_stories,
                                            color: Colors.white,
                                            size: 48,
                                          ),
                                        ),
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            ),
                          );
                        },
                      ),

                      const SizedBox(height: 20),

                      // Tagline with Light Gradient Badge & Smooth Slide-in
                      SlideTransition(
                        position: _slideAnimation,
                        child: FadeTransition(
                          opacity: _fadeAnimation,
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.center,
                            children: [
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 14,
                                  vertical: 6,
                                ),
                                decoration: BoxDecoration(
                                  gradient: LinearGradient(
                                    colors: [
                                      const Color(0xFF00D2FF).withOpacity(0.08),
                                      const Color(0xFF9B51E0).withOpacity(0.08),
                                    ],
                                  ),
                                  borderRadius: BorderRadius.circular(24),
                                  border: Border.all(
                                    color: const Color(0xFF00D2FF).withOpacity(0.22),
                                    width: 1.2,
                                  ),
                                ),
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: [
                                    Container(
                                      width: 7,
                                      height: 7,
                                      decoration: const BoxDecoration(
                                        shape: BoxShape.circle,
                                        gradient: LinearGradient(
                                          colors: [Color(0xFF00D2FF), Color(0xFF9B51E0)],
                                        ),
                                      ),
                                    ),
                                    const SizedBox(width: 8),
                                    const Text(
                                      'AI-POWERED SOCIAL COMMERCE',
                                      style: TextStyle(
                                        color: Color(0xFF0284C7),
                                        fontSize: 11.5,
                                        fontWeight: FontWeight.w800,
                                        letterSpacing: 1.1,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                              const SizedBox(height: 12),
                              const Text(
                                'Your Growth, Your Network, Our Commitment.',
                                textAlign: TextAlign.center,
                                style: TextStyle(
                                  color: Color(0xFF64748B),
                                  fontSize: 14,
                                  fontWeight: FontWeight.w600,
                                  letterSpacing: 0.2,
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),

                      const Spacer(flex: 5),

                      // Modern Minimal Loading Indicator & Version
                      FadeTransition(
                        opacity: _fadeAnimation,
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.center,
                          children: [
                            SizedBox(
                              width: 44,
                              child: ClipRRect(
                                borderRadius: BorderRadius.circular(4),
                                child: const LinearProgressIndicator(
                                  minHeight: 3.5,
                                  backgroundColor: Color(0xFFE2E8F0),
                                  valueColor: AlwaysStoppedAnimation<Color>(
                                    Color(0xFF0284C7),
                                  ),
                                ),
                              ),
                            ),
                            const SizedBox(height: 12),
                            const Text(
                              'v1.0.0',
                              textAlign: TextAlign.center,
                              style: TextStyle(
                                color: Color(0xFF94A3B8),
                                fontSize: 11.5,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),

                      const SizedBox(height: 20),
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

/// Custom painter to render fluid, multi-layered color waves with brand gradients
class _ColorWavePainter extends CustomPainter {
  final double animationValue;

  _ColorWavePainter({required this.animationValue});

  @override
  void paint(Canvas canvas, Size size) {
    final shift = animationValue * 2 * math.pi;

    // 1. Back Wave (Violet/Purple gentle wave)
    final backPaint = Paint()
      ..shader = LinearGradient(
        begin: Alignment.topLeft,
        end: Alignment.bottomRight,
        colors: [
          const Color(0xFF9B51E0).withOpacity(0.12),
          const Color(0xFF6366F1).withOpacity(0.08),
        ],
      ).createShader(Rect.fromLTWH(0, 0, size.width, size.height))
      ..style = PaintingStyle.fill;

    final backPath = Path()..moveTo(0, size.height);
    for (double x = 0; x <= size.width; x += 2) {
      final y = size.height * 0.45 +
          math.sin((x / size.width * 2 * math.pi) + shift) * 16 +
          math.cos((x / size.width * 4 * math.pi) + shift * 0.5) * 8;
      backPath.lineTo(x, y);
    }
    backPath.lineTo(size.width, size.height);
    backPath.close();
    canvas.drawPath(backPath, backPaint);

    // 2. Middle Wave (Cyan to Purple vibrant wave)
    final midPaint = Paint()
      ..shader = LinearGradient(
        begin: Alignment.centerLeft,
        end: Alignment.centerRight,
        colors: [
          const Color(0xFF00D2FF).withOpacity(0.15),
          const Color(0xFF9B51E0).withOpacity(0.13),
        ],
      ).createShader(Rect.fromLTWH(0, 0, size.width, size.height))
      ..style = PaintingStyle.fill;

    final midPath = Path()..moveTo(0, size.height);
    for (double x = 0; x <= size.width; x += 2) {
      final y = size.height * 0.55 +
          math.cos((x / size.width * 2 * math.pi) - shift * 0.8) * 20 +
          math.sin((x / size.width * 3 * math.pi) + shift) * 7;
      midPath.lineTo(x, y);
    }
    midPath.lineTo(size.width, size.height);
    midPath.close();
    canvas.drawPath(midPath, midPaint);

    // 3. Front Wave (Bright Cyan/Sky Blue translucent crest)
    final frontPaint = Paint()
      ..shader = LinearGradient(
        begin: Alignment.topCenter,
        end: Alignment.bottomCenter,
        colors: [
          const Color(0xFF00D2FF).withOpacity(0.20),
          const Color(0xFF38BDF8).withOpacity(0.05),
        ],
      ).createShader(Rect.fromLTWH(0, 0, size.width, size.height))
      ..style = PaintingStyle.fill;

    final frontPath = Path()..moveTo(0, size.height);
    for (double x = 0; x <= size.width; x += 2) {
      final y = size.height * 0.68 +
          math.sin((x / size.width * 2.5 * math.pi) + shift * 1.2) * 14 +
          math.cos((x / size.width * 1.5 * math.pi) - shift) * 6;
      frontPath.lineTo(x, y);
    }
    frontPath.lineTo(size.width, size.height);
    frontPath.close();
    canvas.drawPath(frontPath, frontPaint);
  }

  @override
  bool shouldRepaint(covariant _ColorWavePainter oldDelegate) {
    return oldDelegate.animationValue != animationValue;
  }
}

