import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../core/api_client.dart';
import '../../core/constants.dart';
import '../../core/session_manager.dart';
import '../../providers/auth_provider.dart';
import '../../widgets/profile_image_adjust_dialog.dart';

class EditProfileScreen extends StatefulWidget {
  const EditProfileScreen({super.key});

  @override
  State<EditProfileScreen> createState() => _EditProfileScreenState();
}

class _EditProfileScreenState extends State<EditProfileScreen> {
  final _formKey = GlobalKey<FormState>();

  late TextEditingController _nameCtrl;
  late TextEditingController _dobCtrl;
  late TextEditingController _cityCtrl;
  late TextEditingController _countryCtrl;
  late TextEditingController _websiteCtrl;
  late TextEditingController _bioCtrl;

  String? _selectedGender;
  DateTime? _selectedDob;

  final ImagePicker _picker = ImagePicker();

  bool _isLoadingInitial = true;
  bool _isSaving = false;
  String? _errorMessage;
  String? _successMessage;

  @override
  void initState() {
    super.initState();
    final member = context.read<AuthProvider>().currentMember;
    _nameCtrl = TextEditingController(text: member?.name ?? '');
    _dobCtrl = TextEditingController(text: _formatDobForDisplay(member?.dateOfBirth));
    _cityCtrl = TextEditingController(text: member?.city ?? '');
    _countryCtrl = TextEditingController(text: member?.country ?? '');
    _websiteCtrl = TextEditingController(text: member?.website ?? '');
    _bioCtrl = TextEditingController(text: member?.bio ?? '');
    _selectedGender = member?.gender?.isNotEmpty == true ? member!.gender : null;

    // Listen to changes for live preview & character counter
    _nameCtrl.addListener(() => setState(() {}));
    _bioCtrl.addListener(() => setState(() {}));

    _fetchCurrentProfile();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _dobCtrl.dispose();
    _cityCtrl.dispose();
    _countryCtrl.dispose();
    _websiteCtrl.dispose();
    _bioCtrl.dispose();
    super.dispose();
  }

  String _formatDobForDisplay(String? rawDate) {
    if (rawDate == null || rawDate.isEmpty) return '';
    try {
      final parsed = DateTime.parse(rawDate);
      _selectedDob = parsed;
      return '${parsed.month.toString().padLeft(2, '0')}/${parsed.day.toString().padLeft(2, '0')}/${parsed.year}';
    } catch (_) {
      return rawDate;
    }
  }

  Future<void> _fetchCurrentProfile() async {
    final res = await ApiClient.get('/account/settings');
    if (res.success && res.data is Map && res.data['member'] != null) {
      final m = res.data['member'] as Map<String, dynamic>;
      if (mounted) {
        setState(() {
          if (m['name'] != null && m['name'].toString().isNotEmpty) {
            _nameCtrl.text = m['name'].toString();
          }
          if (m['date_of_birth'] != null && m['date_of_birth'].toString().isNotEmpty) {
            _dobCtrl.text = _formatDobForDisplay(m['date_of_birth'].toString());
          }
          if (m['gender'] != null && m['gender'].toString().isNotEmpty) {
            _selectedGender = m['gender'].toString();
          }
          if (m['city'] != null) {
            _cityCtrl.text = m['city'].toString();
          }
          if (m['country'] != null) {
            _countryCtrl.text = m['country'].toString();
          }
          if (m['website'] != null) {
            _websiteCtrl.text = m['website'].toString();
          }
          if (m['bio'] != null) {
            _bioCtrl.text = m['bio'].toString();
          }
          _isLoadingInitial = false;
        });
      }
    } else {
      if (mounted) setState(() => _isLoadingInitial = false);
    }
  }

  Future<void> _pickImage(String type) async {
    try {
      final XFile? file = await _picker.pickImage(source: ImageSource.gallery);
      if (file != null && mounted) {
        final isAvatar = type.toLowerCase().contains('profile') || type == 'avatar';
        final member = context.read<AuthProvider>().currentMember;
        final hasPhoto = isAvatar 
            ? (member?.avatarUrl != null)
            : (member?.coverPhoto != null);

        final updated = await ProfileImageAdjustDialog.show(
          context,
          file: file,
          type: isAvatar ? 'avatar' : 'cover',
          hasExistingPhoto: hasPhoto,
        );

        if (updated == true && mounted) {
          await context.read<AuthProvider>().initAuth();
          await _fetchCurrentProfile();
        }
      }
    } catch (e) {
      debugPrint('Image pick error: $e');
    }
  }

  Future<void> _pickDateOfBirth() async {
    final now = DateTime.now();
    final initialDate = _selectedDob ?? DateTime(now.year - 25, 1, 1);
    final picked = await showDatePicker(
      context: context,
      initialDate: initialDate,
      firstDate: DateTime(1920),
      lastDate: now,
      builder: (context, child) {
        return Theme(
          data: Theme.of(context).copyWith(
            colorScheme: const ColorScheme.light(
              primary: Color(0xFF2563EB),
              onPrimary: Colors.white,
              onSurface: Color(0xFF1E293B),
            ),
          ),
          child: child!,
        );
      },
    );

    if (picked != null && mounted) {
      setState(() {
        _selectedDob = picked;
        _dobCtrl.text = '${picked.month.toString().padLeft(2, '0')}/${picked.day.toString().padLeft(2, '0')}/${picked.year}';
      });
    }
  }

  Future<void> _handleSave() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isSaving = true;
      _errorMessage = null;
      _successMessage = null;
    });

    String? dobForBackend;
    if (_selectedDob != null) {
      dobForBackend = '${_selectedDob!.year}-${_selectedDob!.month.toString().padLeft(2, '0')}-${_selectedDob!.day.toString().padLeft(2, '0')}';
    }

    final payload = {
      'name': _nameCtrl.text.trim(),
      'date_of_birth': dobForBackend,
      'gender': _selectedGender,
      'city': _cityCtrl.text.trim(),
      'country': _countryCtrl.text.trim(),
      'website': _websiteCtrl.text.trim(),
      'bio': _bioCtrl.text.trim(),
    };

    final res = await ApiClient.post('/account/profile', payload);

    if (!mounted) return;
    setState(() => _isSaving = false);

    if (res.success && res.data is Map && res.data['member'] != null) {
      final updatedMember = res.data['member'] as Map<String, dynamic>;
      await SessionManager.saveUserData(updatedMember);
      if (mounted) {
        await context.read<AuthProvider>().initAuth();
        setState(() {
          _successMessage = 'Your profile has been updated successfully.';
        });
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Row(
              children: [
                Icon(Icons.check_circle, color: Colors.white, size: 18),
                SizedBox(width: 8),
                Text('Profile updated successfully!'),
              ],
            ),
            backgroundColor: Color(0xFF16A34A),
            behavior: SnackBarBehavior.floating,
          ),
        );
        Future.delayed(const Duration(milliseconds: 900), () {
          if (mounted) Navigator.pop(context);
        });
      }
    } else {
      setState(() {
        _errorMessage = res.message ?? 'Failed to update profile. Please try again.';
      });
    }
  }

  String _getInitials(String name) {
    if (name.trim().isEmpty) return 'AS';
    final parts = name.trim().split(RegExp(r'\s+'));
    return parts.take(2).map((p) => p.isNotEmpty ? p[0].toUpperCase() : '').join('');
  }

  @override
  Widget build(BuildContext context) {
    final member = context.watch<AuthProvider>().currentMember;

    final displayName = _nameCtrl.text.isNotEmpty ? _nameCtrl.text : (member?.name ?? 'Abhay Sahany');
    final displayUserId = member?.userId.isNotEmpty == true ? member!.userId : 'ABHY123456';
    final displayEmail = member?.email.isNotEmpty == true ? member!.email : 'abhaysahany1982@gmail.com';
    final initials = _getInitials(displayName);
    final avatarUrl = AppConstants.resolveMediaUrl(member?.avatarUrl, ApiClient.baseUrl);
    final coverUrl = AppConstants.resolveMediaUrl(member?.coverPhoto, ApiClient.baseUrl);

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        title: const Text(
          'Edit Profile',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 17, color: Color(0xFF0F172A)),
        ),
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF0F172A)),
          onPressed: () => Navigator.pop(context),
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(1.0),
          child: Container(color: const Color(0xFFE2E8F0), height: 1.0),
        ),
      ),
      body: _isLoadingInitial
          ? const Center(
              child: CircularProgressIndicator(color: Color(0xFF2563EB)),
            )
          : LayoutBuilder(
              builder: (context, constraints) {
                final isWide = constraints.maxWidth >= 768;

                return SingleChildScrollView(
                  padding: EdgeInsets.symmetric(
                    horizontal: isWide ? 32 : 16,
                    vertical: 24,
                  ),
                  child: Center(
                    child: ConstrainedBox(
                      constraints: const BoxConstraints(maxWidth: 1060),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          if (_successMessage != null)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                              margin: const EdgeInsets.only(bottom: 20),
                              decoration: BoxDecoration(
                                color: const Color(0xFFDCFCE7),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: const Color(0xFFBBF7D0)),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.check_circle, color: Color(0xFF15803D), size: 18),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      _successMessage!,
                                      style: const TextStyle(color: Color(0xFF15803D), fontSize: 13.5, fontWeight: FontWeight.w600),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          if (_errorMessage != null)
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                              margin: const EdgeInsets.only(bottom: 20),
                              decoration: BoxDecoration(
                                color: const Color(0xFFFEE2E2),
                                borderRadius: BorderRadius.circular(10),
                                border: Border.all(color: const Color(0xFFFECACA)),
                              ),
                              child: Row(
                                children: [
                                  const Icon(Icons.error_outline, color: Color(0xFFB91C1C), size: 18),
                                  const SizedBox(width: 10),
                                  Expanded(
                                    child: Text(
                                      _errorMessage!,
                                      style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 13.5),
                                    ),
                                  ),
                                ],
                              ),
                            ),

                          if (isWide)
                            Row(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                // Left Preview Card (Width ~280)
                                SizedBox(
                                  width: 280,
                                  child: _buildProfilePreviewCard(
                                    displayName: displayName,
                                    userId: displayUserId,
                                    email: displayEmail,
                                    initials: initials,
                                    avatarUrl: avatarUrl,
                                    coverUrl: coverUrl,
                                  ),
                                ),
                                const SizedBox(width: 24),
                                // Right Details Form Card
                                Expanded(
                                  child: _buildDetailsFormCard(context),
                                ),
                              ],
                            )
                          else
                            Column(
                              children: [
                                _buildProfilePreviewCard(
                                  displayName: displayName,
                                  userId: displayUserId,
                                  email: displayEmail,
                                  initials: initials,
                                  avatarUrl: avatarUrl,
                                  coverUrl: coverUrl,
                                ),
                                const SizedBox(height: 20),
                                _buildDetailsFormCard(context),
                              ],
                            ),
                        ],
                      ),
                    ),
                  ),
                );
              },
            ),
    );
  }

  // 1. LEFT PROFILE PREVIEW CARD (Exact Screenshot Match)
  Widget _buildProfilePreviewCard({
    required String displayName,
    required String userId,
    required String email,
    required String initials,
    String? avatarUrl,
    String? coverUrl,
  }) {
    return Container(
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        children: [
          // Gradient Cover Banner (120px) with tap to change & explicit "Change Banner" button
          GestureDetector(
            onTap: () => _pickImage('cover'),
            child: Stack(
              children: [
                Container(
                  height: 120,
                  width: double.infinity,
                  decoration: BoxDecoration(
                    gradient: coverUrl == null
                        ? const LinearGradient(
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                            colors: [
                              Color(0xFF4F46E5),
                              Color(0xFF7C3AED),
                            ],
                          )
                        : null,
                    image: coverUrl != null
                        ? DecorationImage(
                            image: NetworkImage(coverUrl),
                            fit: BoxFit.cover,
                          )
                        : null,
                  ),
                ),
                Positioned(
                  top: 10,
                  right: 10,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 5),
                    decoration: BoxDecoration(
                      color: Colors.black.withOpacity(0.6),
                      borderRadius: BorderRadius.circular(20),
                      border: Border.all(color: Colors.white24),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: const [
                        Icon(Icons.camera_alt_outlined, size: 13, color: Colors.white),
                        SizedBox(width: 5),
                        Text(
                          'Change Banner',
                          style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.w600),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),

          // Content below cover with overlapping avatar
          Transform.translate(
            offset: const Offset(0, -38),
            child: Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Column(
                children: [
                  // Circular Avatar (76x76) with tap to change & camera badge
                  GestureDetector(
                    onTap: () => _pickImage('avatar'),
                    child: Stack(
                      children: [
                        Container(
                          width: 76,
                          height: 76,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            gradient: avatarUrl == null
                                ? const LinearGradient(
                                    begin: Alignment.topLeft,
                                    end: Alignment.bottomRight,
                                    colors: [
                                      Color(0xFF6366F1),
                                      Color(0xFF4F46E5),
                                    ],
                                  )
                                : null,
                            border: Border.all(color: Colors.white, width: 4),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.12),
                                blurRadius: 12,
                                offset: const Offset(0, 4),
                              ),
                            ],
                            image: avatarUrl != null
                                ? DecorationImage(
                                    image: NetworkImage(avatarUrl),
                                    fit: BoxFit.cover,
                                  )
                                : null,
                          ),
                          alignment: Alignment.center,
                          child: avatarUrl == null
                              ? Text(
                                  initials,
                                  style: const TextStyle(
                                    color: Colors.white,
                                    fontSize: 24,
                                    fontWeight: FontWeight.bold,
                                    letterSpacing: 0.5,
                                  ),
                                )
                              : null,
                        ),
                        Positioned(
                          bottom: 0,
                          right: 0,
                          child: Container(
                            width: 26,
                            height: 26,
                            decoration: BoxDecoration(
                              color: Colors.white,
                              shape: BoxShape.circle,
                              border: Border.all(color: const Color(0xFFE2E8F0)),
                              boxShadow: [
                                BoxShadow(
                                  color: Colors.black.withOpacity(0.15),
                                  blurRadius: 4,
                                ),
                              ],
                            ),
                            child: const Icon(Icons.camera_alt_outlined, size: 14, color: Color(0xFF4F46E5)),
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Full Name
                  Text(
                    displayName,
                    style: const TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF111827),
                    ),
                    textAlign: TextAlign.center,
                  ),
                  const SizedBox(height: 3),

                  // Handle
                  Text(
                    '@$userId',
                    style: const TextStyle(
                      fontSize: 13,
                      color: Color(0xFF6B7280),
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                  const SizedBox(height: 12),

                  // Email with Mail Icon
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(
                        Icons.mail_outline_rounded,
                        size: 15,
                        color: Color(0xFF9CA3AF),
                      ),
                      const SizedBox(width: 6),
                      Flexible(
                        child: Text(
                          email,
                          style: const TextStyle(
                            fontSize: 13,
                            color: Color(0xFF4B5563),
                          ),
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ),
                  const SizedBox(height: 4),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }

  // 2. RIGHT DETAILS FORM CARD (Exact Screenshot Match)
  Widget _buildDetailsFormCard(BuildContext context) {
    final bioCharCount = _bioCtrl.text.length;

    return Container(
      padding: const EdgeInsets.all(24),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Form(
        key: _formKey,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // Header
            const Text(
              'Profile details',
              style: TextStyle(
                fontSize: 18,
                fontWeight: FontWeight.bold,
                color: Color(0xFF111827),
              ),
            ),
            const SizedBox(height: 4),
            const Text(
              'These details appear on your Member profile.',
              style: TextStyle(
                fontSize: 13,
                color: Color(0xFF6B7280),
              ),
            ),
            const SizedBox(height: 16),
            const Divider(color: Color(0xFFF1F5F9), height: 1),
            const SizedBox(height: 16),

            // Banner & Profile Photo Quick Action Buttons
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
              margin: const EdgeInsets.only(bottom: 20),
              decoration: BoxDecoration(
                color: const Color(0xFFF8FAFC),
                borderRadius: BorderRadius.circular(12),
                border: Border.all(color: const Color(0xFFE2E8F0)),
              ),
              child: Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _pickImage('avatar'),
                      icon: const Icon(Icons.account_circle_outlined, size: 16),
                      label: const Text('Change Photo', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: const Color(0xFF4F46E5),
                        backgroundColor: Colors.white,
                        side: const BorderSide(color: Color(0xFFE2E8F0)),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        padding: const EdgeInsets.symmetric(vertical: 9),
                      ),
                    ),
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () => _pickImage('cover'),
                      icon: const Icon(Icons.image_outlined, size: 16),
                      label: const Text('Change Banner', style: TextStyle(fontSize: 12.5, fontWeight: FontWeight.w600)),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: const Color(0xFF2563EB),
                        backgroundColor: Colors.white,
                        side: const BorderSide(color: Color(0xFFE2E8F0)),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                        padding: const EdgeInsets.symmetric(vertical: 9),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Field 1: Full name *
            _buildFieldLabel('Full name', isRequired: true),
            TextFormField(
              controller: _nameCtrl,
              validator: (v) {
                if (v == null || v.trim().isEmpty) {
                  return 'Please enter your full name';
                }
                return null;
              },
              decoration: _buildInputDecoration(hintText: 'Your name'),
              style: const TextStyle(fontSize: 14, color: Color(0xFF1E293B)),
            ),
            const SizedBox(height: 18),

            // Field 2 & 3: Date of birth & Gender (2-Column Row)
            LayoutBuilder(
              builder: (ctx, box) {
                final isTwoCol = box.maxWidth >= 460;
                if (isTwoCol) {
                  return Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _buildFieldLabel('Date of birth'),
                            InkWell(
                              onTap: _pickDateOfBirth,
                              borderRadius: BorderRadius.circular(10),
                              child: IgnorePointer(
                                child: TextFormField(
                                  controller: _dobCtrl,
                                  decoration: _buildInputDecoration(
                                    hintText: 'mm/dd/yyyy',
                                    suffixIcon: const Icon(
                                      Icons.calendar_today_outlined,
                                      size: 17,
                                      color: Color(0xFF64748B),
                                    ),
                                  ),
                                  style: const TextStyle(fontSize: 14, color: Color(0xFF1E293B)),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _buildFieldLabel('Gender'),
                            _buildGenderDropdown(),
                          ],
                        ),
                      ),
                    ],
                  );
                } else {
                  return Column(
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildFieldLabel('Date of birth'),
                          InkWell(
                            onTap: _pickDateOfBirth,
                            borderRadius: BorderRadius.circular(10),
                            child: IgnorePointer(
                              child: TextFormField(
                                controller: _dobCtrl,
                                decoration: _buildInputDecoration(
                                  hintText: 'mm/dd/yyyy',
                                  suffixIcon: const Icon(
                                    Icons.calendar_today_outlined,
                                    size: 17,
                                    color: Color(0xFF64748B),
                                  ),
                                ),
                                style: const TextStyle(fontSize: 14, color: Color(0xFF1E293B)),
                              ),
                            ),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildFieldLabel('Gender'),
                          _buildGenderDropdown(),
                        ],
                      ),
                    ],
                  );
                }
              },
            ),
            const SizedBox(height: 18),

            // Field 4 & 5: City & Country (2-Column Row)
            LayoutBuilder(
              builder: (ctx, box) {
                final isTwoCol = box.maxWidth >= 460;
                if (isTwoCol) {
                  return Row(
                    children: [
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _buildFieldLabel('City'),
                            TextFormField(
                              controller: _cityCtrl,
                              decoration: _buildInputDecoration(hintText: 'Your city'),
                              style: const TextStyle(fontSize: 14, color: Color(0xFF1E293B)),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            _buildFieldLabel('Country'),
                            TextFormField(
                              controller: _countryCtrl,
                              decoration: _buildInputDecoration(hintText: 'Your country'),
                              style: const TextStyle(fontSize: 14, color: Color(0xFF1E293B)),
                            ),
                          ],
                        ),
                      ),
                    ],
                  );
                } else {
                  return Column(
                    children: [
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildFieldLabel('City'),
                          TextFormField(
                            controller: _cityCtrl,
                            decoration: _buildInputDecoration(hintText: 'Your city'),
                            style: const TextStyle(fontSize: 14, color: Color(0xFF1E293B)),
                          ),
                        ],
                      ),
                      const SizedBox(height: 18),
                      Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          _buildFieldLabel('Country'),
                          TextFormField(
                            controller: _countryCtrl,
                            decoration: _buildInputDecoration(hintText: 'Your country'),
                            style: const TextStyle(fontSize: 14, color: Color(0xFF1E293B)),
                          ),
                        ],
                      ),
                    ],
                  );
                }
              },
            ),
            const SizedBox(height: 18),

            // Field 6: Website
            _buildFieldLabel('Website'),
            TextFormField(
              controller: _websiteCtrl,
              decoration: _buildInputDecoration(hintText: 'https://example.com'),
              style: const TextStyle(fontSize: 14, color: Color(0xFF1E293B)),
            ),
            const SizedBox(height: 18),

            // Field 7: Bio
            _buildFieldLabel('Bio'),
            TextFormField(
              controller: _bioCtrl,
              maxLines: 4,
              maxLength: 500,
              buildCounter: (
                BuildContext context, {
                required int currentLength,
                required bool isFocused,
                required int? maxLength,
              }) =>
                  null, // Custom bottom counter below
              decoration: _buildInputDecoration(hintText: 'Share a little about yourself...'),
              style: const TextStyle(fontSize: 14, color: Color(0xFF1E293B)),
            ),
            const SizedBox(height: 4),

            // Character Hint Row (Exact Match)
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                const Text(
                  'Maximum 500 characters.',
                  style: TextStyle(fontSize: 12, color: Color(0xFF9CA3AF)),
                ),
                Text(
                  '$bioCharCount/500',
                  style: TextStyle(
                    fontSize: 12,
                    color: bioCharCount > 450 ? const Color(0xFFF59E0B) : const Color(0xFF9CA3AF),
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 24),

            // Bottom Buttons (Cancel & Save Changes)
            Row(
              mainAxisAlignment: MainAxisAlignment.end,
              children: [
                OutlinedButton(
                  onPressed: () => Navigator.pop(context),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: const Color(0xFF374151),
                    backgroundColor: Colors.white,
                    side: const BorderSide(color: Color(0xFFD1D5DB)),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                  ),
                  child: const Text('Cancel', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                ),
                const SizedBox(width: 12),
                ElevatedButton.icon(
                  onPressed: _isSaving ? null : _handleSave,
                  icon: _isSaving
                      ? const SizedBox(
                          width: 14,
                          height: 14,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Icon(Icons.save_outlined, size: 16),
                  label: Text(
                    _isSaving ? 'Saving...' : 'Save Changes',
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5),
                  ),
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2563EB),
                    foregroundColor: Colors.white,
                    elevation: 0,
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                    padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                  ),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFieldLabel(String label, {bool isRequired = false}) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Text(
            label,
            style: const TextStyle(
              fontSize: 13,
              fontWeight: FontWeight.w600,
              color: Color(0xFF374151),
            ),
          ),
          if (isRequired) ...[
            const SizedBox(width: 3),
            const Text(
              '*',
              style: TextStyle(
                fontSize: 13,
                fontWeight: FontWeight.bold,
                color: Color(0xFFEF4444),
              ),
            ),
          ],
        ],
      ),
    );
  }

  InputDecoration _buildInputDecoration({
    required String hintText,
    Widget? suffixIcon,
  }) {
    return InputDecoration(
      hintText: hintText,
      hintStyle: const TextStyle(color: Color(0xFF9CA3AF), fontSize: 13.5),
      suffixIcon: suffixIcon,
      isDense: true,
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
      filled: true,
      fillColor: Colors.white,
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Color(0xFFD1D5DB)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Color(0xFFD1D5DB)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
      ),
      errorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Color(0xFFEF4444)),
      ),
      focusedErrorBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Color(0xFFEF4444), width: 1.5),
      ),
    );
  }

  Widget _buildGenderDropdown() {
    return DropdownButtonFormField<String>(
      value: _selectedGender,
      icon: const Icon(Icons.keyboard_arrow_down_rounded, size: 20, color: Color(0xFF64748B)),
      decoration: _buildInputDecoration(hintText: 'Select an option'),
      dropdownColor: Colors.white,
      style: const TextStyle(fontSize: 14, color: Color(0xFF1E293B)),
      items: const [
        DropdownMenuItem(value: null, child: Text('Select an option', style: TextStyle(color: Color(0xFF9CA3AF)))),
        DropdownMenuItem(value: 'male', child: Text('Male')),
        DropdownMenuItem(value: 'female', child: Text('Female')),
        DropdownMenuItem(value: 'other', child: Text('Other')),
        DropdownMenuItem(value: 'prefer_not_to_say', child: Text('Prefer not to say')),
      ],
      onChanged: (val) {
        setState(() {
          _selectedGender = val;
        });
      },
    );
  }
}
