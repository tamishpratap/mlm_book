import 'package:flutter/foundation.dart';
import 'session_manager.dart';
import 'google_auth_platform_stub.dart'
    if (dart.library.html) 'google_auth_platform_web.dart';

class GoogleAuthService {
  /// Generate the backend Google OAuth redirect endpoint URL
  static String getGoogleAuthUrl({String mode = 'login', String? ref, String? customReturnUrl}) {
    final activeBase = SessionManager.getBaseUrl();
    String hostBase = activeBase.replaceAll('/api/v1/mobile', '');
    if (hostBase.isEmpty) {
      hostBase = 'http://127.0.0.1:8000';
    }

    String redirectEndpoint = '$hostBase/api/member/auth/google/redirect';

    final params = <String, String>{
      'mode': mode,
    };

    if (ref != null && ref.trim().isNotEmpty) {
      params['ref'] = ref.trim();
    }

    final origin = customReturnUrl ?? getCurrentOrigin();
    if (origin.isNotEmpty) {
      params['return_url'] = origin;
    }

    final query = Uri(queryParameters: params).query;
    return '$redirectEndpoint?$query';
  }

  /// Launch Google OAuth Flow
  static void launchGoogleAuth({String mode = 'login', String? ref}) {
    final url = getGoogleAuthUrl(mode: mode, ref: ref);
    if (kDebugMode) {
      debugPrint('[GoogleAuthService] Launching OAuth redirect: $url');
    }
    redirectToUrl(url);
  }
}
