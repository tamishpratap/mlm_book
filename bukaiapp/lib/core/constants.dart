import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

class AppConstants {
  // Base URLs
  static const String localApiUrl = 'http://127.0.0.1:8000/api/v1/mobile';
  static const String androidEmulatorApiUrl = 'http://10.0.2.2:8000/api/v1/mobile';
  static const String liveProductionApiUrl = 'https://mlmbookai.com/backend/public/api/v1/mobile';

  // Active baseApiUrl: automatically connects to local server during development
  static String get baseApiUrl {
    if (kIsWeb) {
      return localApiUrl;
    }
    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return androidEmulatorApiUrl;
      case TargetPlatform.windows:
      case TargetPlatform.linux:
      case TargetPlatform.macOS:
        return localApiUrl;
      default:
        return localApiUrl;
    }
  }
  
  // Storage Keys
  static const String tokenKey = 'mobile_auth_token';
  static const String roleKey = 'mobile_auth_role'; // 'member' or 'admin'
  static const String userKey = 'mobile_auth_user';
  static const String serverUrlKey = 'mobile_api_server_url';

  // App Strings
  static const String appName = 'MLM Book';
  static const String currencySymbol = r'$';
  static const String currencyCode = 'USDT';

  // Resolve media URL to active backend host if uploaded locally or relative
  static String? resolveMediaUrl(String? url, [String? activeApiBase]) {
    if (url == null || url.trim().isEmpty) return null;
    String cleanUrl = url.trim();

    final apiBase = activeApiBase ?? (kIsWeb ? localApiUrl : localApiUrl);
    final hostBase = apiBase.replaceAll('/api/v1/mobile', '');

    // If relative path
    if (cleanUrl.startsWith('/')) {
      return '$hostBase$cleanUrl';
    }
    if (!cleanUrl.startsWith('http://') && !cleanUrl.startsWith('https://')) {
      return '$hostBase/$cleanUrl';
    }

    // If url contains mlmbookai.com/uploads/ (from backend default APP_URL)
    if (cleanUrl.contains('mlmbookai.com/uploads/')) {
      return cleanUrl
          .replaceFirst('https://mlmbookai.com', hostBase)
          .replaceFirst('http://mlmbookai.com', hostBase);
    }

    if (cleanUrl.contains('/uploads/')) {
      if (cleanUrl.startsWith('http://localhost:8000')) {
        return cleanUrl.replaceFirst('http://localhost:8000', hostBase);
      }
      if (cleanUrl.startsWith('http://127.0.0.1:8000')) {
        return cleanUrl.replaceFirst('http://127.0.0.1:8000', hostBase);
      }
    }

    return cleanUrl;
  }
}

class AppColors {
  // Brand Palette - Exact MLM Book Web Theme
  static const Color primary = Color(0xFF176BFF); // Signature Electric Blue
  static const Color primaryDark = Color(0xFF0F5BE8);
  static const Color secondary = Color(0xFF7A3FF2); // Signature Royal Purple
  static const Color secondarySoft = Color(0xFFF3EDFD);
  static const Color primarySoft = Color(0xFFEEF3FF);
  static const Color accent = Color(0xFF22B45B); // Emerald Green for rewards
  static const Color accentSoft = Color(0xFFE8F8EE);
  
  // Backgrounds & Card Surfaces
  static const Color background = Color(0xFFF6F8FE); // Soft Ice-Blue Page Background
  static const Color surface = Color(0xFFFFFFFF); // Pure White Card Surface
  static const Color surfaceSoft = Color(0xFFF2F5FF);
  static const Color surfaceLight = Color(0xFFEDF1F7);
  
  // Typography
  static const Color textHeading = Color(0xFF111C35);
  static const Color textPrimary = Color(0xFF111827);
  static const Color textSecondary = Color(0xFF667085);
  static const Color textMuted = Color(0xFF98A2B3);
  static const Color border = Color(0xFFE2E7F0);
  static const Color borderSoft = Color(0xFFEDF1F7);
  
  // Status Colors
  static const Color success = Color(0xFF22B45B);
  static const Color warning = Color(0xFFF59E0B);
  static const Color danger = Color(0xFFEF4444);
  static const Color info = Color(0xFF176BFF);

  // Gradients matching Web Portal
  static const LinearGradient primaryGradient = LinearGradient(
    colors: [Color(0xFF176BFF), Color(0xFF7A3FF2)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );

  static const LinearGradient walletGradient = LinearGradient(
    colors: [Color(0xFF059669), Color(0xFF22B45B), Color(0xFF34D399)],
    begin: Alignment.topLeft,
    end: Alignment.bottomRight,
  );
}
