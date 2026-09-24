import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import 'constants.dart';

class SessionManager {
  static SharedPreferences? _prefs;

  static Future<void> init() async {
    _prefs ??= await SharedPreferences.getInstance();
  }

  static Future<void> saveSession({
    required String token,
    required String role,
    required Map<String, dynamic> userData,
  }) async {
    await _prefs?.setString(AppConstants.tokenKey, token);
    await _prefs?.setString(AppConstants.roleKey, role);
    await _prefs?.setString(AppConstants.userKey, jsonEncode(userData));
  }

  static String? getToken() {
    return _prefs?.getString(AppConstants.tokenKey);
  }

  static String? getRole() {
    return _prefs?.getString(AppConstants.roleKey);
  }

  static Map<String, dynamic>? getUserData() {
    final raw = _prefs?.getString(AppConstants.userKey);
    if (raw == null) return null;
    try {
      return jsonDecode(raw) as Map<String, dynamic>;
    } catch (_) {
      return null;
    }
  }

  static Future<void> saveUserData(Map<String, dynamic> data) async {
    await _prefs?.setString(AppConstants.userKey, jsonEncode(data));
  }

  static bool isAuthenticated() {
    final token = getToken();
    return token != null && token.isNotEmpty;
  }

  static String getBaseUrl() {
    return _prefs?.getString(AppConstants.serverUrlKey) ?? AppConstants.baseApiUrl;
  }

  static Future<void> setBaseUrl(String url) async {
    await _prefs?.setString(AppConstants.serverUrlKey, url);
  }

  static Future<void> clearSession() async {
    await _prefs?.remove(AppConstants.tokenKey);
    await _prefs?.remove(AppConstants.roleKey);
    await _prefs?.remove(AppConstants.userKey);
  }
}
