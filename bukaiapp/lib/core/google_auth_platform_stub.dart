import 'package:flutter/foundation.dart';
import 'package:url_launcher/url_launcher.dart';

String getCurrentOrigin() {
  // On native mobile/desktop platforms, use the registered custom URL deep-link scheme
  return 'mlmbook://auth/callback';
}

void redirectToUrl(String url) async {
  try {
    final uri = Uri.parse(url);
    final launched = await launchUrl(
      uri,
      mode: LaunchMode.externalApplication,
    );
    if (!launched && kDebugMode) {
      debugPrint('[GoogleAuthPlatformStub] Could not launch URL: $url');
    }
  } catch (e) {
    if (kDebugMode) {
      debugPrint('[GoogleAuthPlatformStub] Error launching URL: $e');
    }
  }
}
