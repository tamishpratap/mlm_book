import 'package:flutter/material.dart';
import '../core/api_client.dart';
import '../core/session_manager.dart';
import '../models/admin_model.dart';
import '../models/member_model.dart';

class AuthProvider extends ChangeNotifier {
  bool _isLoading = false;
  String? _errorMessage;
  MemberModel? _currentMember;
  AdminModel? _currentAdmin;
  String? _activeRole; // 'member' or 'admin'

  bool get isLoading => _isLoading;
  String? get errorMessage => _errorMessage;
  MemberModel? get currentMember => _currentMember;
  AdminModel? get currentAdmin => _currentAdmin;
  String? get activeRole => _activeRole;
  bool get isAuthenticated => SessionManager.isAuthenticated();

  Future<void> initAuth() async {
    await SessionManager.init();
    final role = SessionManager.getRole();
    final data = SessionManager.getUserData();

    if (role != null && data != null) {
      _activeRole = role;
      if (role == 'member') {
        _currentMember = MemberModel.fromJson(data);
      } else if (role == 'admin') {
        _currentAdmin = AdminModel.fromJson(data);
      }
      notifyListeners();
    }
  }

  // Member Login
  Future<bool> loginMember(String email, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final res = await ApiClient.post('/auth/member/login', {
      'email': email.trim(),
      'password': password,
      'device_name': 'Flutter Mobile App',
    });

    _isLoading = false;

    if (res.success && res.data is Map && res.data['token'] != null) {
      final token = res.data['token'].toString();
      final memberJson = res.data['member'] as Map<String, dynamic>;
      _currentMember = MemberModel.fromJson(memberJson);
      _activeRole = 'member';

      await SessionManager.saveSession(
        token: token,
        role: 'member',
        userData: memberJson,
      );

      notifyListeners();
      return true;
    } else {
      _errorMessage = res.message ?? 'Login failed. Please check your credentials.';
      notifyListeners();
      return false;
    }
  }

  // Admin Login
  Future<bool> loginAdmin(String email, String password) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final res = await ApiClient.post('/auth/admin/login', {
      'email': email.trim(),
      'password': password,
      'device_name': 'Flutter Admin Mobile App',
    });

    _isLoading = false;

    if (res.success && res.data is Map && res.data['token'] != null) {
      final token = res.data['token'].toString();
      final adminJson = res.data['admin'] as Map<String, dynamic>;
      _currentAdmin = AdminModel.fromJson(adminJson);
      _activeRole = 'admin';

      await SessionManager.saveSession(
        token: token,
        role: 'admin',
        userData: adminJson,
      );

      notifyListeners();
      return true;
    } else {
      _errorMessage = res.message ?? 'Admin login failed.';
      notifyListeners();
      return false;
    }
  }

  // Register Member (Initiates 6-digit email OTP)
  Future<String?> registerMember({
    required String name,
    required String userId,
    String? introducerId,
    String? phone,
    String? countryCode,
    required String email,
    required String password,
    String? passwordConfirmation,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final res = await ApiClient.post('/auth/member/register', {
      'name': name.trim(),
      'user_id': userId.trim(),
      if (introducerId != null && introducerId.trim().isNotEmpty) 'introducer_id': introducerId.trim(),
      if (phone != null && phone.trim().isNotEmpty) 'phone': phone.trim(),
      if (countryCode != null && countryCode.trim().isNotEmpty) 'country_code': countryCode.trim(),
      'email': email.trim(),
      'password': password,
      'password_confirmation': passwordConfirmation ?? password,
    });

    _isLoading = false;

    if (res.success && res.data is Map && res.data['registration_token'] != null) {
      notifyListeners();
      return res.data['registration_token'].toString();
    } else {
      _errorMessage = res.message ?? 'Registration failed.';
      notifyListeners();
      return null;
    }
  }

  // Verify Registration OTP & Auto Login
  Future<bool> verifyRegistrationOtp({
    required String registrationToken,
    required String otp,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final res = await ApiClient.post('/auth/member/register/verify', {
      'registration_token': registrationToken,
      'otp': otp.trim(),
      'device_name': 'Flutter Mobile App',
    });

    _isLoading = false;

    if (res.success && res.data is Map && res.data['token'] != null) {
      final token = res.data['token'].toString();
      final memberJson = res.data['member'] as Map<String, dynamic>;
      _currentMember = MemberModel.fromJson(memberJson);
      _activeRole = 'member';

      await SessionManager.saveSession(
        token: token,
        role: 'member',
        userData: memberJson,
      );

      notifyListeners();
      return true;
    } else {
      _errorMessage = res.message ?? 'Verification failed.';
      notifyListeners();
      return false;
    }
  }

  // Send Forgot Password Reset Link
  Future<bool> sendPasswordReset(String email) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final res = await ApiClient.post('/auth/member/forgot-password', {
      'email': email.trim(),
    });

    _isLoading = false;
    if (res.success) {
      notifyListeners();
      return true;
    } else {
      _errorMessage = res.message ?? 'Failed to send password reset link.';
      notifyListeners();
      return false;
    }
  }

  // Login directly with an issued Mobile Bearer Token (e.g. from Google OAuth callback)
  Future<bool> loginWithMobileToken(String token, {Map<String, dynamic>? initialMemberData}) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    try {
      await SessionManager.saveSession(
        token: token,
        role: 'member',
        userData: initialMemberData ?? {},
      );

      final res = await ApiClient.get('/auth/me');
      _isLoading = false;

      if (res.success && res.data is Map && res.data['member'] != null) {
        final memberJson = res.data['member'] as Map<String, dynamic>;
        _currentMember = MemberModel.fromJson(memberJson);
        _activeRole = 'member';

        await SessionManager.saveSession(
          token: token,
          role: 'member',
          userData: memberJson,
        );

        notifyListeners();
        return true;
      } else if (initialMemberData != null && initialMemberData.isNotEmpty) {
        _currentMember = MemberModel.fromJson(initialMemberData);
        _activeRole = 'member';
        notifyListeners();
        return true;
      } else {
        _errorMessage = res.message ?? 'Failed to authenticate session.';
        notifyListeners();
        return false;
      }
    } catch (e) {
      _isLoading = false;
      _errorMessage = 'Session authorization error: $e';
      notifyListeners();
      return false;
    }
  }

  // Fetch pending Google Signup metadata
  Future<Map<String, dynamic>?> getPendingGoogleSignup(String token) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final res = await ApiClient.get('/auth/google/pending?token=$token');
    _isLoading = false;

    if (res.success && res.data is Map) {
      notifyListeners();
      return res.data as Map<String, dynamic>;
    } else {
      _errorMessage = res.message ?? 'Your Google signup session has expired. Please sign in with Google again.';
      notifyListeners();
      return null;
    }
  }

  // Complete Google Signup with Phone & optional Introducer
  Future<ApiResponse> completeGoogleSignup({
    required String token,
    required String phone,
    required String countryCode,
    String? introducerId,
  }) async {
    _isLoading = true;
    _errorMessage = null;
    notifyListeners();

    final res = await ApiClient.post('/auth/google/complete', {
      'token': token,
      'phone': phone.trim(),
      'country_code': countryCode.trim(),
      if (introducerId != null && introducerId.trim().isNotEmpty) 'introducer_id': introducerId.trim(),
    });

    _isLoading = false;

    if (!res.success) {
      _errorMessage = res.message ?? 'Failed to complete registration.';
    }

    notifyListeners();
    return res;
  }

  // Check Phone number availability in real-time
  Future<Map<String, dynamic>> checkPhoneAvailability(String phone, String countryCode) async {
    final cleanDigits = phone.replaceAll(RegExp(r'\D'), '');
    final cleanCode = countryCode.trim();
    final res = await ApiClient.get('/auth/check-phone?phone=$cleanDigits&country_code=${Uri.encodeComponent(cleanCode)}');
    if (res.data is Map) {
      return res.data as Map<String, dynamic>;
    }
    return {
      'available': res.success,
      'message': res.message,
    };
  }

  // Check Introducer ID validity in real-time
  Future<Map<String, dynamic>> checkIntroducer(String introducerId) async {
    final res = await ApiClient.get('/auth/check-introducer?introducer=${Uri.encodeComponent(introducerId.trim())}');
    if (res.data is Map) {
      return res.data as Map<String, dynamic>;
    }
    return {
      'exists': false,
      'valid': false,
      'message': res.message ?? 'Invalid Introducer ID',
    };
  }

  void clearError() {
    _errorMessage = null;
    notifyListeners();
  }

  // Switch role between Member and Admin (if user has both credentials)
  void switchRole(String newRole) {
    if (_activeRole != newRole) {
      _activeRole = newRole;
      notifyListeners();
    }
  }

  // Logout
  Future<void> logout() async {
    await ApiClient.post('/auth/logout');
    await SessionManager.clearSession();
    _currentMember = null;
    _currentAdmin = null;
    _activeRole = null;
    notifyListeners();
  }
}
