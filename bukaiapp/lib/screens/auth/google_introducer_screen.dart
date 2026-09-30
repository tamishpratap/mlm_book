import 'dart:async';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../providers/auth_provider.dart';
import '../member/member_shell.dart';
import 'login_screen.dart';

class GoogleIntroducerScreen extends StatefulWidget {
  final String token;
  final String? initialRef;

  const GoogleIntroducerScreen({
    super.key,
    required this.token,
    this.initialRef,
  });

  @override
  State<GoogleIntroducerScreen> createState() => _GoogleIntroducerScreenState();
}

class _GoogleIntroducerScreenState extends State<GoogleIntroducerScreen> {
  final _phoneController = TextEditingController();
  final _introducerController = TextEditingController();

  String _countryCode = '+91';
  bool _isLoadingPending = true;
  String? _loadError;
  Map<String, dynamic>? _pendingData;

  // Phone Validation State
  String _phoneStatus = 'idle'; // 'idle', 'checking', 'available', 'taken', 'invalid'
  String? _phoneMessage;
  Timer? _phoneDebounce;

  // Introducer Validation State
  String _introducerStatus = 'idle'; // 'idle', 'checking', 'valid', 'invalid'
  String? _introducerName;
  String? _introducerMessage;
  Timer? _introducerDebounce;

  bool _isSubmitting = false;
  String? _formError;

  static const List<Map<String, String>> countryCodes = [
    {'code': '+91', 'label': 'India (+91)'},
    {'code': '+1', 'label': 'USA / Canada (+1)'},
    {'code': '+44', 'label': 'UK (+44)'},
    {'code': '+971', 'label': 'UAE (+971)'},
    {'code': '+966', 'label': 'Saudi Arabia (+966)'},
    {'code': '+65', 'label': 'Singapore (+65)'},
    {'code': '+60', 'label': 'Malaysia (+60)'},
    {'code': '+61', 'label': 'Australia (+61)'},
    {'code': '+49', 'label': 'Germany (+49)'},
    {'code': '+33', 'label': 'France (+33)'},
    {'code': '+81', 'label': 'Japan (+81)'},
    {'code': '+880', 'label': 'Bangladesh (+880)'},
    {'code': '+977', 'label': 'Nepal (+977)'},
    {'code': '+94', 'label': 'Sri Lanka (+94)'},
    {'code': '+92', 'label': 'Pakistan (+92)'},
    {'code': '+234', 'label': 'Nigeria (+234)'},
    {'code': '+27', 'label': 'South Africa (+27)'},
    {'code': '+55', 'label': 'Brazil (+55)'},
    {'code': '+7', 'label': 'Russia (+7)'},
    {'code': '+86', 'label': 'China (+86)'},
  ];

  @override
  void initState() {
    super.initState();
    if (widget.initialRef != null && widget.initialRef!.isNotEmpty) {
      _introducerController.text = widget.initialRef!;
    }
    _fetchPendingData();
  }

  @override
  void dispose() {
    _phoneDebounce?.cancel();
    _introducerDebounce?.cancel();
    _phoneController.dispose();
    _introducerController.dispose();
    super.dispose();
  }

  Future<void> _fetchPendingData() async {
    setState(() {
      _isLoadingPending = true;
      _loadError = null;
    });

    final auth = context.read<AuthProvider>();
    final data = await auth.getPendingGoogleSignup(widget.token);

    if (!mounted) return;

    if (data != null) {
      setState(() {
        _isLoadingPending = false;
        _pendingData = data;
        if (data['ref'] != null && data['ref'].toString().isNotEmpty && _introducerController.text.isEmpty) {
          _introducerController.text = data['ref'].toString();
          _checkIntroducer(data['ref'].toString());
        }
      });
    } else {
      setState(() {
        _isLoadingPending = false;
        _loadError = auth.errorMessage ?? 'Your Google signup session has expired or is invalid. Please sign in with Google again.';
      });
    }
  }

  void _onPhoneChanged(String val) {
    _phoneDebounce?.cancel();
    final cleanDigits = val.replaceAll(RegExp(r'\D'), '');

    if (cleanDigits.isEmpty) {
      setState(() {
        _phoneStatus = 'idle';
        _phoneMessage = null;
        _formError = null;
      });
      return;
    }

    if (_countryCode == '+91' && cleanDigits.length != 10) {
      setState(() {
        _phoneStatus = 'invalid';
        _phoneMessage = 'Please enter a valid 10-digit mobile number.';
      });
      return;
    } else if (cleanDigits.length < 7 || cleanDigits.length > 15) {
      setState(() {
        _phoneStatus = 'invalid';
        _phoneMessage = 'Please enter a valid phone number (7-15 digits).';
      });
      return;
    }

    setState(() {
      _phoneStatus = 'checking';
      _phoneMessage = 'Checking availability...';
      _formError = null;
    });

    _phoneDebounce = Timer(const Duration(milliseconds: 500), () async {
      final auth = context.read<AuthProvider>();
      final res = await auth.checkPhoneAvailability(cleanDigits, _countryCode);

      if (!mounted) return;

      final isAvailable = res['available'] == true;
      setState(() {
        if (isAvailable) {
          _phoneStatus = 'available';
          _phoneMessage = 'Phone number is available.';
        } else {
          _phoneStatus = 'taken';
          _phoneMessage = res['message'] ?? 'This number already exists. Please use the existing login option.';
        }
      });
    });
  }

  void _onIntroducerChanged(String val) {
    _introducerDebounce?.cancel();
    final cleanRef = val.trim();

    if (cleanRef.isEmpty) {
      setState(() {
        _introducerStatus = 'idle';
        _introducerName = null;
        _introducerMessage = null;
      });
      return;
    }

    setState(() {
      _introducerStatus = 'checking';
      _introducerMessage = 'Verifying Introducer ID...';
    });

    _introducerDebounce = Timer(const Duration(milliseconds: 500), () async {
      _checkIntroducer(cleanRef);
    });
  }

  Future<void> _checkIntroducer(String cleanRef) async {
    final auth = context.read<AuthProvider>();
    final res = await auth.checkIntroducer(cleanRef);

    if (!mounted) return;

    if (res['valid'] == true) {
      setState(() {
        _introducerStatus = 'valid';
        _introducerName = res['name'] ?? res['user_id'];
        _introducerMessage = 'Introduced by ${_introducerName ?? cleanRef}';
      });
    } else {
      setState(() {
        _introducerStatus = 'invalid';
        _introducerName = null;
        _introducerMessage = res['message'] ?? 'The selected Introducer ID does not exist.';
      });
    }
  }

  Future<void> _submit() async {
    final phone = _phoneController.text.trim().replaceAll(RegExp(r'\D'), '');
    if (phone.isEmpty) {
      setState(() => _formError = 'Please enter your WhatsApp/mobile number.');
      return;
    }

    if (_countryCode == '+91' && phone.length != 10) {
      setState(() => _formError = 'Please enter a valid 10-digit mobile number.');
      return;
    }

    if (_phoneStatus == 'taken') {
      setState(() => _formError = 'This phone number already exists. Please use login.');
      return;
    }

    if (_introducerController.text.trim().isNotEmpty && _introducerStatus == 'invalid') {
      setState(() => _formError = 'Please enter a valid Introducer ID or leave it blank.');
      return;
    }

    setState(() {
      _isSubmitting = true;
      _formError = null;
    });

    final auth = context.read<AuthProvider>();
    final res = await auth.completeGoogleSignup(
      token: widget.token,
      phone: phone,
      countryCode: _countryCode,
      introducerId: _introducerController.text.trim(),
    );

    if (!mounted) return;
    setState(() => _isSubmitting = false);

    if (res.success) {
      // Check if session token was returned directly
      if (res.data is Map && res.data['token'] != null) {
        await auth.loginWithMobileToken(
          res.data['token'].toString(),
          initialMemberData: res.data['member'] as Map<String, dynamic>?,
        );
        if (!mounted) return;
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(builder: (_) => const MemberShell()),
          (route) => false,
        );
      } else {
        // Navigate to Login with success confirmation banner
        Navigator.pushAndRemoveUntil(
          context,
          MaterialPageRoute(
            builder: (_) => const LoginScreen(
              initialSuccessMessage: 'Your account has been created successfully. You can now log in.',
            ),
          ),
          (route) => false,
        );
      }
    } else {
      setState(() {
        _formError = res.message ?? 'Failed to complete registration. Please try again.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: const Color(0xFFF6F8FE),
      appBar: AppBar(
        title: const Text('Complete Account Setup'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pushReplacement(
            context,
            MaterialPageRoute(builder: (_) => const LoginScreen()),
          ),
        ),
      ),
      body: Center(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
          child: ConstrainedBox(
            constraints: const BoxConstraints(maxWidth: 480),
            child: _isLoadingPending
                ? _buildLoadingState()
                : _loadError != null
                    ? _buildErrorState()
                    : _buildFormState(),
          ),
        ),
      ),
    );
  }

  Widget _buildLoadingState() {
    return Container(
      padding: const EdgeInsets.all(32),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
      ),
      child: const Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          CircularProgressIndicator(strokeWidth: 3, color: AppColors.primary),
          SizedBox(height: 20),
          Text(
            'Preparing your Google signup…',
            style: TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w600,
              color: AppColors.textPrimary,
            ),
          ),
          SizedBox(height: 8),
          Text(
            'Fetching your verified Google credentials',
            style: TextStyle(fontSize: 13, color: AppColors.textSecondary),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState() {
    return Container(
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.border),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            width: 56,
            height: 56,
            decoration: BoxDecoration(
              color: AppColors.danger.withOpacity(0.1),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.error_outline, color: AppColors.danger, size: 30),
          ),
          const SizedBox(height: 16),
          const Text(
            'Session Expired or Invalid',
            style: TextStyle(
              fontSize: 18,
              fontWeight: FontWeight.bold,
              color: AppColors.textHeading,
            ),
          ),
          const SizedBox(height: 10),
          Text(
            _loadError ?? 'Your Google session has expired. Please sign in with Google again.',
            textAlign: TextAlign.center,
            style: const TextStyle(fontSize: 14, color: AppColors.textSecondary, height: 1.4),
          ),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            height: 48,
            child: ElevatedButton(
              onPressed: () => Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const LoginScreen()),
              ),
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
              ),
              child: const Text('Back to Login', style: TextStyle(fontWeight: FontWeight.bold)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildFormState() {
    final name = _pendingData?['name']?.toString() ?? 'Google User';
    final email = _pendingData?['email']?.toString() ?? '';
    final avatar = _pendingData?['avatar']?.toString();

    return Container(
      padding: const EdgeInsets.all(28),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(24),
        border: Border.all(color: const Color(0xFFE2E7F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 20,
            offset: const Offset(0, 8),
          ),
        ],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // Google Profile Badge Card
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(
              gradient: const LinearGradient(
                colors: [Color(0xFFF8FAFC), Color(0xFFEFF6FF)],
                begin: Alignment.topLeft,
                end: Alignment.bottomRight,
              ),
              borderRadius: BorderRadius.circular(16),
              border: Border.all(color: const Color(0xFFDBEAFE)),
            ),
            child: Row(
              children: [
                CircleAvatar(
                  radius: 26,
                  backgroundColor: AppColors.primarySoft,
                  backgroundImage: avatar != null && avatar.isNotEmpty ? NetworkImage(avatar) : null,
                  child: avatar == null || avatar.isEmpty
                      ? Text(
                          name.isNotEmpty ? name[0].toUpperCase() : 'G',
                          style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold, color: AppColors.primary),
                        )
                      : null,
                ),
                const SizedBox(width: 14),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        name,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.bold,
                          color: AppColors.textHeading,
                        ),
                      ),
                      const SizedBox(height: 2),
                      Text(
                        email,
                        style: const TextStyle(
                          fontSize: 13,
                          color: AppColors.textSecondary,
                        ),
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Icon(Icons.verified, size: 14, color: Colors.blue.shade600),
                          const SizedBox(width: 4),
                          Text(
                            'Verified Google Account',
                            style: TextStyle(
                              fontSize: 11,
                              fontWeight: FontWeight.w600,
                              color: Colors.blue.shade700,
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const SizedBox(height: 24),

          // Title & Description
          const Text(
            'Complete Your Account',
            style: TextStyle(
              fontSize: 20,
              fontWeight: FontWeight.bold,
              color: AppColors.textHeading,
            ),
          ),
          const SizedBox(height: 6),
          const Text(
            'Please provide your WhatsApp number for communication and notifications.',
            style: TextStyle(
              fontSize: 13.5,
              color: AppColors.textSecondary,
              height: 1.4,
            ),
          ),

          if (_formError != null) ...[
            const SizedBox(height: 16),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              decoration: BoxDecoration(
                color: const Color(0xFFFEF2F2),
                borderRadius: BorderRadius.circular(10),
                border: Border.all(color: const Color(0xFFFECACA)),
              ),
              child: Row(
                children: [
                  const Icon(Icons.error_outline, color: AppColors.danger, size: 18),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Text(
                      _formError!,
                      style: const TextStyle(fontSize: 13, color: Color(0xFF991B1B), fontWeight: FontWeight.w500),
                    ),
                  ),
                ],
              ),
            ),
          ],

          const SizedBox(height: 20),

          // Country Code & Phone Input
          const Text(
            'WhatsApp / Mobile Number *',
            style: TextStyle(
              fontSize: 13.5,
              fontWeight: FontWeight.w600,
              color: AppColors.textHeading,
            ),
          ),
          const SizedBox(height: 8),
          Row(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Country Code Dropdown
              Container(
                height: 50,
                padding: const EdgeInsets.symmetric(horizontal: 10),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                  border: Border.all(color: const Color(0xFFCBD5E1)),
                ),
                child: DropdownButtonHideUnderline(
                  child: DropdownButton<String>(
                    value: _countryCode,
                    icon: const Icon(Icons.keyboard_arrow_down, size: 20),
                    onChanged: (newVal) {
                      if (newVal != null) {
                        setState(() => _countryCode = newVal);
                        _onPhoneChanged(_phoneController.text);
                      }
                    },
                    items: countryCodes.map((item) {
                      return DropdownMenuItem<String>(
                        value: item['code'],
                        child: Text(
                          item['code']!,
                          style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                        ),
                      );
                    }).toList(),
                  ),
                ),
              ),
              const SizedBox(width: 10),
              // Phone Text Field
              Expanded(
                child: TextField(
                  controller: _phoneController,
                  keyboardType: TextInputType.phone,
                  onChanged: _onPhoneChanged,
                  decoration: InputDecoration(
                    hintText: 'e.g. 9876543210',
                    filled: true,
                    fillColor: Colors.white,
                    contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: BorderSide(
                        color: _phoneStatus == 'available'
                            ? AppColors.success
                            : (_phoneStatus == 'taken' || _phoneStatus == 'invalid')
                                ? AppColors.danger
                                : const Color(0xFFCBD5E1),
                      ),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
                    ),
                  ),
                ),
              ),
            ],
          ),

          if (_phoneMessage != null) ...[
            const SizedBox(height: 6),
            Row(
              children: [
                if (_phoneStatus == 'checking')
                  const SizedBox(
                    width: 12,
                    height: 12,
                    child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary),
                  )
                else
                  Icon(
                    _phoneStatus == 'available'
                        ? Icons.check_circle
                        : Icons.info_outline,
                    size: 14,
                    color: _phoneStatus == 'available'
                        ? AppColors.success
                        : AppColors.danger,
                  ),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    _phoneMessage!,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                      color: _phoneStatus == 'available'
                          ? AppColors.success
                          : AppColors.danger,
                    ),
                  ),
                ),
              ],
            ),
          ],

          const SizedBox(height: 20),

          // Introducer ID Input (Optional)
          const Text(
            'Introducer User ID (Optional)',
            style: TextStyle(
              fontSize: 13.5,
              fontWeight: FontWeight.w600,
              color: AppColors.textHeading,
            ),
          ),
          const SizedBox(height: 8),
          TextField(
            controller: _introducerController,
            onChanged: _onIntroducerChanged,
            decoration: InputDecoration(
              hintText: 'Enter referral ID (e.g. MB123456)',
              filled: true,
              fillColor: Colors.white,
              contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
              border: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: Color(0xFFCBD5E1)),
              ),
              enabledBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: BorderSide(
                  color: _introducerStatus == 'valid'
                      ? AppColors.success
                      : _introducerStatus == 'invalid'
                          ? AppColors.danger
                          : const Color(0xFFCBD5E1),
                ),
              ),
              focusedBorder: OutlineInputBorder(
                borderRadius: BorderRadius.circular(12),
                borderSide: const BorderSide(color: AppColors.primary, width: 1.5),
              ),
            ),
          ),

          if (_introducerMessage != null) ...[
            const SizedBox(height: 6),
            Row(
              children: [
                if (_introducerStatus == 'checking')
                  const SizedBox(
                    width: 12,
                    height: 12,
                    child: CircularProgressIndicator(strokeWidth: 2, color: AppColors.primary),
                  )
                else
                  Icon(
                    _introducerStatus == 'valid' ? Icons.check_circle : Icons.warning_amber_rounded,
                    size: 14,
                    color: _introducerStatus == 'valid' ? AppColors.success : AppColors.danger,
                  ),
                const SizedBox(width: 6),
                Expanded(
                  child: Text(
                    _introducerMessage!,
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w500,
                      color: _introducerStatus == 'valid' ? AppColors.success : AppColors.danger,
                    ),
                  ),
                ),
              ],
            ),
          ],

          const SizedBox(height: 28),

          // Complete Button
          SizedBox(
            height: 50,
            child: ElevatedButton(
              onPressed: _isSubmitting ? null : _submit,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppColors.primary,
                foregroundColor: Colors.white,
                shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                elevation: 0,
              ),
              child: _isSubmitting
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2.5, color: Colors.white),
                    )
                  : const Row(
                      mainAxisAlignment: MainAxisAlignment.center,
                      children: [
                        Text('Complete Registration', style: TextStyle(fontSize: 15, fontWeight: FontWeight.bold)),
                        SizedBox(width: 8),
                        Icon(Icons.arrow_forward, size: 18),
                      ],
                    ),
            ),
          ),

          const SizedBox(height: 16),

          // Cancel / Back to Login
          Center(
            child: TextButton(
              onPressed: () => Navigator.pushReplacement(
                context,
                MaterialPageRoute(builder: (_) => const LoginScreen()),
              ),
              child: const Text(
                'Cancel and Return to Login',
                style: TextStyle(color: AppColors.textSecondary, fontSize: 13.5),
              ),
            ),
          ),
        ],
      ),
    );
  }
}
