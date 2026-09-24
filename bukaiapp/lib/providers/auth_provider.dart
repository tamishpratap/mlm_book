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
