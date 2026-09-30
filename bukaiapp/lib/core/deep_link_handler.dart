import 'dart:async';
import 'package:app_links/app_links.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import '../screens/auth/google_introducer_screen.dart';
import '../screens/auth/login_screen.dart';
import '../screens/auth/register_screen.dart';
import '../screens/member/member_shell.dart';

class DeepLinkHandler {
  static final GlobalKey<NavigatorState> navigatorKey = GlobalKey<NavigatorState>();
  static AppLinks? _appLinks;
  static StreamSubscription<Uri>? _linkSubscription;

  static void init(BuildContext context) {
    _appLinks = AppLinks();

    // Check initial launch link (cold start)
    _appLinks?.getInitialLink().then((uri) {
      if (uri != null) {
        handleUri(uri);
      }
    });

    // Listen to incoming links while app is open / resumed
    _linkSubscription = _appLinks?.uriLinkStream.listen((uri) {
      handleUri(uri);
    });
  }

  static void dispose() {
    _linkSubscription?.cancel();
  }

  static Future<void> handleUri(Uri uri) async {
    if (kDebugMode) {
      debugPrint('[DeepLinkHandler] Received URI: $uri');
    }

    final queryParams = uri.queryParameters;
    if (queryParams.isEmpty) return;

    final authSuccess = queryParams['auth_success'] == '1';
    final mobileToken = queryParams['mobile_token'] ?? queryParams['token'];
    final googleToken = queryParams['google_token'];
    final error = queryParams['error'];
    final email = queryParams['email'];
    final ref = queryParams['ref'] ?? queryParams['introducer'];

    final context = navigatorKey.currentContext;
    if (context == null) return;

    final auth = context.read<AuthProvider>();

    // 1. Google OAuth successful login
    if (authSuccess && mobileToken != null && mobileToken.isNotEmpty) {
      final success = await auth.loginWithMobileToken(
        mobileToken,
        initialMemberData: {
          if (queryParams['user_id'] != null) 'user_id': queryParams['user_id'],
          if (queryParams['email'] != null) 'email': queryParams['email'],
          if (queryParams['name'] != null) 'name': queryParams['name'],
        },
      );

      if (success) {
        navigatorKey.currentState?.pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => const MemberShell()),
          (route) => false,
        );
      } else {
        navigatorKey.currentState?.pushAndRemoveUntil(
          MaterialPageRoute(builder: (_) => LoginScreen(initialErrorMessage: auth.errorMessage)),
          (route) => false,
        );
      }
      return;
    }

    // 2. Google OAuth new registration pending token
    if (googleToken != null && googleToken.isNotEmpty) {
      navigatorKey.currentState?.push(
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
      navigatorKey.currentState?.pushAndRemoveUntil(
        MaterialPageRoute(
          builder: (_) => LoginScreen(
            initialError: 'signup_required',
            email: email,
            ref: ref,
          ),
        ),
        (route) => false,
      );
      return;
    }

    // 4. Account already exists error
    if (error == 'account_exists') {
      navigatorKey.currentState?.pushAndRemoveUntil(
        MaterialPageRoute(
          builder: (_) => RegisterScreen(
            initialError: 'account_exists',
            email: email,
            ref: ref,
          ),
        ),
        (route) => false,
      );
      return;
    }

    // 5. General error / Account blocked
    if (error != null && error.isNotEmpty) {
      navigatorKey.currentState?.pushAndRemoveUntil(
        MaterialPageRoute(
          builder: (_) => LoginScreen(
            initialErrorMessage: error,
            email: email,
          ),
        ),
        (route) => false,
      );
      return;
    }
  }
}
