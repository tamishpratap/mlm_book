import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../core/app_bottom_sheet.dart';
import '../../core/app_dialog.dart';
import '../../core/app_toast.dart';
import '../../core/constants.dart';
import '../../models/business_page_model.dart';
import '../../providers/business_provider.dart';
import 'business_detail_screen.dart';

class BusinessScreen extends StatefulWidget {
  final int initialTab;
  final VoidCallback? onBack;

  const BusinessScreen({super.key, this.initialTab = 0, this.onBack});

  @override
  State<BusinessScreen> createState() => _BusinessScreenState();
}

class _BusinessScreenState extends State<BusinessScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final TextEditingController _searchController = TextEditingController();
  final ImagePicker _picker = ImagePicker();

  final List<String> _tabs = ['all', 'my'];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(
      length: 2,
      vsync: this,
      initialIndex: widget.initialTab.clamp(0, 1),
    );

    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<BusinessProvider>().fetchBusinessPages(tab: 'all');
    });

    _tabController.addListener(() {
      if (_tabController.indexIsChanging) {
        final selectedTab = _tabs[_tabController.index];
        context.read<BusinessProvider>().fetchBusinessPages(tab: selectedTab);
      }
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    _searchController.dispose();
    super.dispose();
  }

  void _onCategorySelected(String category) {
    final bp = context.read<BusinessProvider>();
    final target = (category == 'All' || bp.selectedCategory == category) ? '' : category;
    bp.fetchBusinessPages(category: target);
  }

  void _onSearchSubmitted(String query) {
    final bp = context.read<BusinessProvider>();
    bp.fetchBusinessPages(search: query.trim());
  }

  void _showCreatePageSheet() {
    final nameCtrl = TextEditingController();
    final usernameCtrl = TextEditingController();
    final descCtrl = TextEditingController();
    final websiteCtrl = TextEditingController();
    final emailCtrl = TextEditingController();
    final phoneCtrl = TextEditingController();
    final addressCtrl = TextEditingController();
    final cityCtrl = TextEditingController();
    final stateCtrl = TextEditingController();

    String selectedCategory = 'Technology & IT';
    String selectedVisibility = 'public';
    String selectedDialCode = '+91';
    String selectedCountry = 'India';

    Uint8List? pickedLogoBytes;
    Uint8List? pickedCoverBytes;
    bool isSubmitting = false;

    AppBottomSheet.show(
      context,
      title: 'Create Business Page',
      child: StatefulBuilder(
        builder: (ctx, setModalState) {
          final bp = ctx.read<BusinessProvider>();
          final categoryOptions = bp.categories.isNotEmpty
              ? bp.categories
              : [
                  'Technology & IT',
                  'Financial Services',
                  'E-Commerce & Retail',
                  'Health & Wellness',
                  'Real Estate',
                  'Education & Training',
                  'Marketing & Advertising',
                  'Direct Selling & MLM',
                  'Entertainment',
                  'Food & Beverages',
                  'Travel & Tourism',
                  'Consulting',
                ];

          // 1. Prepare country list from table data (with fallback)
          final List<Map<String, dynamic>> countryList = bp.countries.isNotEmpty
              ? bp.countries
              : [
                  {'name': 'India', 'iso2': 'IN', 'phone_code': '+91'},
                  {'name': 'United States', 'iso2': 'US', 'phone_code': '+1'},
                  {'name': 'United Kingdom', 'iso2': 'GB', 'phone_code': '+44'},
                  {'name': 'United Arab Emirates', 'iso2': 'AE', 'phone_code': '+971'},
                  {'name': 'Canada', 'iso2': 'CA', 'phone_code': '+1'},
                  {'name': 'Australia', 'iso2': 'AU', 'phone_code': '+61'},
                  {'name': 'Germany', 'iso2': 'DE', 'phone_code': '+49'},
                  {'name': 'Singapore', 'iso2': 'SG', 'phone_code': '+65'},
                  {'name': 'Saudi Arabia', 'iso2': 'SA', 'phone_code': '+966'},
                  {'name': 'South Africa', 'iso2': 'ZA', 'phone_code': '+27'},
                  {'name': 'Nigeria', 'iso2': 'NG', 'phone_code': '+234'},
                  {'name': 'Malaysia', 'iso2': 'MY', 'phone_code': '+60'},
                ];

          final countryNames = countryList.map((c) => c['name']?.toString() ?? '').where((n) => n.isNotEmpty).toList();
          if (!countryNames.contains(selectedCountry) && countryNames.isNotEmpty) {
            selectedCountry = countryNames.first;
          }

          // 2. Prepare dialing code options
          final List<Map<String, String>> dialingCodeOptions = [
            {'code': '+91', 'label': 'IN +91', 'country': 'India'},
            {'code': '+1', 'label': 'US +1', 'country': 'United States'},
            {'code': '+44', 'label': 'GB +44', 'country': 'United Kingdom'},
            {'code': '+971', 'label': 'AE +971', 'country': 'UAE'},
            {'code': '+61', 'label': 'AU +61', 'country': 'Australia'},
            {'code': '+49', 'label': 'DE +49', 'country': 'Germany'},
            {'code': '+33', 'label': 'FR +33', 'country': 'France'},
            {'code': '+65', 'label': 'SG +65', 'country': 'Singapore'},
            {'code': '+966', 'label': 'SA +966', 'country': 'Saudi Arabia'},
            {'code': '+27', 'label': 'ZA +27', 'country': 'South Africa'},
            {'code': '+234', 'label': 'NG +234', 'country': 'Nigeria'},
            {'code': '+60', 'label': 'MY +60', 'country': 'Malaysia'},
            {'code': '+62', 'label': 'ID +62', 'country': 'Indonesia'},
            {'code': '+63', 'label': 'PH +63', 'country': 'Philippines'},
            {'code': '+880', 'label': 'BD +880', 'country': 'Bangladesh'},
            {'code': '+977', 'label': 'NP +977', 'country': 'Nepal'},
            {'code': '+94', 'label': 'LK +94', 'country': 'Sri Lanka'},
            {'code': '+92', 'label': 'PK +92', 'country': 'Pakistan'},
          ];

          Future<void> pickLogo() async {
            try {
              final file = await _picker.pickImage(
                source: ImageSource.gallery,
                maxWidth: 800,
                maxHeight: 800,
                imageQuality: 85,
              );
              if (file != null) {
                final bytes = await file.readAsBytes();
                setModalState(() {
                  pickedLogoBytes = bytes;
                });
              }
            } catch (_) {
              AppToast.error(ctx, 'Failed to select logo image');
            }
          }

          Future<void> pickCover() async {
            try {
              final file = await _picker.pickImage(
                source: ImageSource.gallery,
                maxWidth: 1200,
                maxHeight: 600,
                imageQuality: 85,
              );
              if (file != null) {
                final bytes = await file.readAsBytes();
                setModalState(() {
                  pickedCoverBytes = bytes;
                });
              }
            } catch (_) {
              AppToast.error(ctx, 'Failed to select cover image');
            }
          }

          return SingleChildScrollView(
            padding: EdgeInsets.only(
              left: 20,
              right: 20,
              top: 8,
              bottom: MediaQuery.of(ctx).viewInsets.bottom + 24,
            ),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // 1. Cover Photo & Overlapping Logo Section
                Stack(
                  clipBehavior: Clip.none,
                  children: [
                    // Cover Photo Banner
                    GestureDetector(
                      onTap: pickCover,
                      child: Container(
                        height: 120,
                        width: double.infinity,
                        decoration: BoxDecoration(
                          borderRadius: BorderRadius.circular(14),
                          color: const Color(0xFFF1F5F9),
                          image: pickedCoverBytes != null
                              ? DecorationImage(
                                  image: MemoryImage(pickedCoverBytes!),
                                  fit: BoxFit.cover,
                                )
                              : null,
                          gradient: pickedCoverBytes == null
                              ? const LinearGradient(
                                  colors: [Color(0xFF176BFF), Color(0xFF7146ED)],
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                )
                              : null,
                        ),
                        child: Stack(
                          children: [
                            if (pickedCoverBytes == null)
                              Center(
                                child: Row(
                                  mainAxisSize: MainAxisSize.min,
                                  children: const [
                                    Icon(Icons.camera_alt_outlined, color: Colors.white, size: 18),
                                    SizedBox(width: 6),
                                    Text(
                                      'Add Cover Photo',
                                      style: TextStyle(color: Colors.white, fontSize: 13, fontWeight: FontWeight.w600),
                                    ),
                                  ],
                                ),
                              ),
                            if (pickedCoverBytes != null)
                              Positioned(
                                top: 8,
                                right: 8,
                                child: Container(
                                  padding: const EdgeInsets.all(6),
                                  decoration: BoxDecoration(
                                    color: Colors.black.withOpacity(0.55),
                                    shape: BoxShape.circle,
                                  ),
                                  child: const Icon(Icons.edit, color: Colors.white, size: 14),
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),

                    // Overlapping Logo Avatar
                    Positioned(
                      bottom: -24,
                      left: 16,
                      child: GestureDetector(
                        onTap: pickLogo,
                        child: Container(
                          width: 64,
                          height: 64,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: Colors.white,
                            border: Border.all(color: Colors.white, width: 3),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.12),
                                blurRadius: 6,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: ClipOval(
                            child: pickedLogoBytes != null
                                ? Image.memory(pickedLogoBytes!, fit: BoxFit.cover)
                                : Container(
                                    color: const Color(0xFFEFF6FF),
                                    child: const Center(
                                      child: Icon(Icons.add_a_photo_outlined, color: Color(0xFF2563EB), size: 24),
                                    ),
                                  ),
                          ),
                        ),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 36),

                // 2. Business Name
                const Text(
                  'Business Name *',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, color: Color(0xFF0F172A)),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: nameCtrl,
                  decoration: _inputDecoration('e.g. Apex Global Solutions'),
                ),

                const SizedBox(height: 14),

                // 3. Username / Handle
                const Text(
                  'Username / Handle',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, color: Color(0xFF0F172A)),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: usernameCtrl,
                  decoration: _inputDecoration('e.g. apex_global', prefixText: '@'),
                ),

                const SizedBox(height: 14),

                // 4. Category Dropdown
                const Text(
                  'Category *',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, color: Color(0xFF0F172A)),
                ),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: categoryOptions.contains(selectedCategory) ? selectedCategory : categoryOptions.first,
                  decoration: _inputDecoration(''),
                  icon: const Icon(Icons.keyboard_arrow_down, color: Color(0xFF64748B)),
                  items: categoryOptions.map((cat) {
                    return DropdownMenuItem<String>(
                      value: cat,
                      child: Text(cat, style: const TextStyle(fontSize: 14, color: Color(0xFF0F172A))),
                    );
                  }).toList(),
                  onChanged: (val) {
                    if (val != null) setModalState(() => selectedCategory = val);
                  },
                ),

                const SizedBox(height: 14),

                // 5. Visibility Selection
                const Text(
                  'Visibility',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, color: Color(0xFF0F172A)),
                ),
                const SizedBox(height: 6),
                Row(
                  children: [
                    Expanded(
                      child: _buildVisibilityRadio(
                        title: 'Public',
                        subtitle: 'Visible to everyone',
                        value: 'public',
                        groupValue: selectedVisibility,
                        onChanged: (v) => setModalState(() => selectedVisibility = v!),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildVisibilityRadio(
                        title: 'Private',
                        subtitle: 'Members only',
                        value: 'private',
                        groupValue: selectedVisibility,
                        onChanged: (v) => setModalState(() => selectedVisibility = v!),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 14),

                // 6. Description / Bio
                const Text(
                  'About / Bio',
                  style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, color: Color(0xFF0F172A)),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: descCtrl,
                  maxLines: 3,
                  decoration: _inputDecoration('Describe your products, mission, and services...'),
                ),

                const SizedBox(height: 20),
                const Divider(height: 1, color: Color(0xFFE2E8F0)),
                const SizedBox(height: 16),

                // ==========================================
                // CONTACT & LOCATION DETAILS SECTION
                // ==========================================
                const Text(
                  'Contact & Location Details',
                  style: TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.bold,
                    color: Color(0xFF0F172A),
                  ),
                ),
                const SizedBox(height: 14),

                // 7. Website URL (Optional)
                Row(
                  children: const [
                    Text('Website URL ', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, color: Color(0xFF0F172A))),
                    Text('(Optional)', style: TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8))),
                  ],
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: websiteCtrl,
                  keyboardType: TextInputType.url,
                  decoration: _inputDecoration('https://example.com'),
                ),

                const SizedBox(height: 14),

                // 8. Business Email *
                RichText(
                  text: const TextSpan(
                    text: 'Business Email ',
                    style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, color: Color(0xFF0F172A)),
                    children: [
                      TextSpan(text: '*', style: TextStyle(color: Color(0xFFEF4444), fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: emailCtrl,
                  keyboardType: TextInputType.emailAddress,
                  decoration: _inputDecoration('contact@example.com'),
                ),

                const SizedBox(height: 14),

                // 9. Phone Number * (With Dialing Code Dropdown)
                RichText(
                  text: const TextSpan(
                    text: 'Phone Number ',
                    style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, color: Color(0xFF0F172A)),
                    children: [
                      TextSpan(text: '*', style: TextStyle(color: Color(0xFFEF4444), fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
                const SizedBox(height: 6),
                Row(
                  children: [
                    // Dialing code dropdown
                    Container(
                      height: 48,
                      padding: const EdgeInsets.symmetric(horizontal: 10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: DropdownButtonHideUnderline(
                        child: DropdownButton<String>(
                          value: dialingCodeOptions.any((d) => d['code'] == selectedDialCode) ? selectedDialCode : '+91',
                          icon: const Icon(Icons.keyboard_arrow_down, color: Color(0xFF64748B), size: 18),
                          items: dialingCodeOptions.map((item) {
                            return DropdownMenuItem<String>(
                              value: item['code'],
                              child: Text(
                                item['label']!,
                                style: const TextStyle(fontSize: 13.5, fontWeight: FontWeight.w600, color: Color(0xFF0F172A)),
                              ),
                            );
                          }).toList(),
                          onChanged: (val) {
                            if (val != null) setModalState(() => selectedDialCode = val);
                          },
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    // Phone Number text field
                    Expanded(
                      child: TextField(
                        controller: phoneCtrl,
                        keyboardType: TextInputType.phone,
                        decoration: _inputDecoration('9876543210'),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 14),

                // 10. Street Address (Optional)
                Row(
                  children: const [
                    Text('Street Address ', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, color: Color(0xFF0F172A))),
                    Text('(Optional)', style: TextStyle(fontSize: 12.5, color: Color(0xFF94A3B8))),
                  ],
                ),
                const SizedBox(height: 6),
                TextField(
                  controller: addressCtrl,
                  decoration: _inputDecoration('123 Business Way'),
                ),

                const SizedBox(height: 14),

                // 11. City (Optional) & State / Region (Optional)
                Row(
                  children: [
                    // City
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: const [
                              Text('City ', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: Color(0xFF0F172A))),
                              Text('(Optional)', style: TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8))),
                            ],
                          ),
                          const SizedBox(height: 6),
                          TextField(
                            controller: cityCtrl,
                            decoration: _inputDecoration('New York'),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(width: 10),
                    // State / Region
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: const [
                              Text('State / Region ', style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13, color: Color(0xFF0F172A))),
                              Text('(Optional)', style: TextStyle(fontSize: 11.5, color: Color(0xFF94A3B8))),
                            ],
                          ),
                          const SizedBox(height: 6),
                          TextField(
                            controller: stateCtrl,
                            decoration: _inputDecoration('NY'),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 14),

                // 12. Country * (Select option populated from database table)
                RichText(
                  text: const TextSpan(
                    text: 'Country ',
                    style: TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5, color: Color(0xFF0F172A)),
                    children: [
                      TextSpan(text: '*', style: TextStyle(color: Color(0xFFEF4444), fontWeight: FontWeight.bold)),
                    ],
                  ),
                ),
                const SizedBox(height: 6),
                DropdownButtonFormField<String>(
                  value: countryNames.contains(selectedCountry) ? selectedCountry : (countryNames.isNotEmpty ? countryNames.first : 'India'),
                  decoration: _inputDecoration('Select Country'),
                  isExpanded: true,
                  icon: const Icon(Icons.keyboard_arrow_down, color: Color(0xFF64748B)),
                  items: countryNames.map((cName) {
                    return DropdownMenuItem<String>(
                      value: cName,
                      child: Text(
                        cName,
                        style: const TextStyle(fontSize: 14, color: Color(0xFF0F172A)),
                        overflow: TextOverflow.ellipsis,
                      ),
                    );
                  }).toList(),
                  onChanged: (val) {
                    if (val != null) {
                      setModalState(() {
                        selectedCountry = val;
                        // Auto-match dialing code if available
                        final match = countryList.firstWhere(
                          (c) => c['name'] == val,
                          orElse: () => <String, dynamic>{},
                        );
                        if (match['phone_code'] != null && match['phone_code'].toString().isNotEmpty) {
                          final pCode = match['phone_code'].toString();
                          if (dialingCodeOptions.any((d) => d['code'] == pCode)) {
                            selectedDialCode = pCode;
                          }
                        }
                      });
                    }
                  },
                ),

                const SizedBox(height: 26),

                // 13. Submit Button
                ElevatedButton(
                  onPressed: isSubmitting
                      ? null
                      : () async {
                          final name = nameCtrl.text.trim();
                          if (name.isEmpty) {
                            AppToast.error(ctx, 'Please enter a business name.');
                            return;
                          }
                          final email = emailCtrl.text.trim();
                          if (email.isEmpty) {
                            AppToast.error(ctx, 'Please enter a business email.');
                            return;
                          }
                          final phone = phoneCtrl.text.trim();
                          if (phone.isEmpty) {
                            AppToast.error(ctx, 'Please enter a phone number.');
                            return;
                          }

                          setModalState(() => isSubmitting = true);
                          final ok = await ctx.read<BusinessProvider>().createBusinessPage(
                                name: name,
                                username: usernameCtrl.text.trim().isNotEmpty ? usernameCtrl.text.trim() : null,
                                category: selectedCategory,
                                description: descCtrl.text.trim().isNotEmpty ? descCtrl.text.trim() : null,
                                website: websiteCtrl.text.trim().isNotEmpty ? websiteCtrl.text.trim() : null,
                                email: email,
                                phoneCountryCode: selectedDialCode,
                                phoneNumber: phone,
                                address: addressCtrl.text.trim().isNotEmpty ? addressCtrl.text.trim() : null,
                                city: cityCtrl.text.trim().isNotEmpty ? cityCtrl.text.trim() : null,
                                state: stateCtrl.text.trim().isNotEmpty ? stateCtrl.text.trim() : null,
                                country: selectedCountry,
                                visibility: selectedVisibility,
                                logoBytes: pickedLogoBytes,
                                coverBytes: pickedCoverBytes,
                              );

                          setModalState(() => isSubmitting = false);
                          if (ok && mounted) {
                            Navigator.pop(ctx);
                            AppToast.success(context, 'Business Page created successfully!');
                          } else if (!ok && mounted) {
                            final err = ctx.read<BusinessProvider>().lastError ?? 'Failed to create business page.';
                            AppToast.error(context, err);
                          }
                        },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: const Color(0xFF2563EB),
                    foregroundColor: Colors.white,
                    elevation: 0,
                    padding: const EdgeInsets.symmetric(vertical: 14),
                    shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                  ),
                  child: isSubmitting
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                        )
                      : const Text(
                          'Create Page',
                          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 15),
                        ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  InputDecoration _inputDecoration(String hint, {String? prefixText, IconData? prefixIcon}) {
    return InputDecoration(
      hintText: hint,
      hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13.5),
      prefixText: prefixText,
      prefixStyle: const TextStyle(color: Color(0xFF2563EB), fontWeight: FontWeight.bold, fontSize: 14),
      prefixIcon: prefixIcon != null ? Icon(prefixIcon, size: 18, color: const Color(0xFF64748B)) : null,
      filled: true,
      fillColor: const Color(0xFFF8FAFC),
      contentPadding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      border: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      enabledBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
      ),
      focusedBorder: OutlineInputBorder(
        borderRadius: BorderRadius.circular(10),
        borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
      ),
    );
  }

  Widget _buildVisibilityRadio({
    required String title,
    required String subtitle,
    required String value,
    required String groupValue,
    required ValueChanged<String?> onChanged,
  }) {
    final isSelected = value == groupValue;
    return InkWell(
      onTap: () => onChanged(value),
      borderRadius: BorderRadius.circular(10),
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSelected ? const Color(0xFF2563EB) : const Color(0xFFE2E8F0),
            width: isSelected ? 1.5 : 1,
          ),
        ),
        child: Row(
          children: [
            Icon(
              isSelected ? Icons.radio_button_checked : Icons.radio_button_off,
              color: isSelected ? const Color(0xFF2563EB) : const Color(0xFF94A3B8),
              size: 18,
            ),
            const SizedBox(width: 8),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    title,
                    style: TextStyle(
                      fontWeight: FontWeight.bold,
                      fontSize: 13,
                      color: isSelected ? const Color(0xFF1E40AF) : const Color(0xFF0F172A),
                    ),
                  ),
                  Text(
                    subtitle,
                    style: const TextStyle(fontSize: 10.5, color: Color(0xFF64748B)),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final bp = context.watch<BusinessProvider>();

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF0F172A), size: 24),
          onPressed: widget.onBack ?? () => Navigator.maybePop(context),
        ),
        title: const Text(
          'Business Pages',
          style: TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 18,
            fontWeight: FontWeight.w700,
          ),
        ),
        actions: [
          IconButton(
            icon: bp.isRefreshing
                ? const SizedBox(
                    width: 18,
                    height: 18,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Color(0xFF2563EB)),
                  )
                : const Icon(Icons.refresh_rounded, color: Color(0xFF64748B), size: 22),
            tooltip: 'Refresh',
            onPressed: bp.isRefreshing ? null : () => bp.fetchBusinessPages(isRefresh: true),
          ),
          IconButton(
            icon: const Icon(Icons.add_circle_outline_rounded, color: Color(0xFF2563EB), size: 24),
            tooltip: 'Create Business Page',
            onPressed: _showCreatePageSheet,
          ),
        ],
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(48),
          child: Container(
            decoration: const BoxDecoration(
              color: Colors.white,
              border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0), width: 1)),
            ),
            child: TabBar(
              controller: _tabController,
              indicatorColor: const Color(0xFF2563EB),
              indicatorWeight: 2.5,
              labelColor: const Color(0xFF2563EB),
              unselectedLabelColor: const Color(0xFF64748B),
              labelStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
              unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5),
              tabs: [
                const Tab(
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Icon(Icons.grid_view_rounded, size: 16),
                      SizedBox(width: 6),
                      Text('All Pages'),
                    ],
                  ),
                ),
                Tab(
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Icon(Icons.business_center_outlined, size: 16),
                      const SizedBox(width: 6),
                      Text('My Pages (${bp.myCount})'),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
      body: Column(
        children: [
          // 1. Search Bar & Category Chips Filter Strip
          Container(
            color: Colors.white,
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // Rounded Search Bar
                Container(
                  height: 42,
                  decoration: BoxDecoration(
                    color: const Color(0xFFF1F5F9),
                    borderRadius: BorderRadius.circular(24),
                  ),
                  child: TextField(
                    controller: _searchController,
                    onSubmitted: _onSearchSubmitted,
                    textInputAction: TextInputAction.search,
                    style: const TextStyle(fontSize: 13.5, color: Color(0xFF0F172A)),
                    decoration: InputDecoration(
                      hintText: 'Search business pages by name, category, city...',
                      hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                      prefixIcon: const Icon(Icons.search_rounded, color: Color(0xFF64748B), size: 20),
                      suffixIcon: _searchController.text.isNotEmpty
                          ? IconButton(
                              icon: const Icon(Icons.clear, color: Color(0xFF94A3B8), size: 18),
                              onPressed: () {
                                _searchController.clear();
                                _onSearchSubmitted('');
                              },
                            )
                          : null,
                      border: InputBorder.none,
                      contentPadding: const EdgeInsets.symmetric(vertical: 10, horizontal: 14),
                    ),
                  ),
                ),

                const SizedBox(height: 10),

                // Horizontal Category Filter Chips
                SingleChildScrollView(
                  scrollDirection: Axis.horizontal,
                  physics: const BouncingScrollPhysics(),
                  child: Row(
                    children: [
                      _buildCategoryChip('All', bp.selectedCategory.isEmpty || bp.selectedCategory == 'All'),
                      ...bp.categories.map((cat) {
                        return _buildCategoryChip(cat, bp.selectedCategory == cat);
                      }),
                    ],
                  ),
                ),
              ],
            ),
          ),

          const Divider(height: 1, color: Color(0xFFE2E8F0)),

          // 2. Business Pages List Content
          Expanded(
            child: bp.isLoading
                ? const Center(child: CircularProgressIndicator(color: Color(0xFF2563EB)))
                : RefreshIndicator(
                    onRefresh: () => bp.fetchBusinessPages(isRefresh: true),
                    color: const Color(0xFF2563EB),
                    child: _buildPagesList(bp),
                  ),
          ),
        ],
      ),
      floatingActionButton: FloatingActionButton.extended(
        heroTag: 'business_page_fab',
        onPressed: _showCreatePageSheet,
        backgroundColor: const Color(0xFF2563EB),
        foregroundColor: Colors.white,
        elevation: 3,
        icon: const Icon(Icons.add, size: 20),
        label: const Text(
          'Create Page',
          style: TextStyle(fontWeight: FontWeight.bold, fontSize: 13.5),
        ),
      ),
    );
  }

  Widget _buildCategoryChip(String label, bool isSelected) {
    return Padding(
      padding: const EdgeInsets.only(right: 6),
      child: ChoiceChip(
        label: Text(label),
        selected: isSelected,
        onSelected: (_) => _onCategorySelected(label),
        selectedColor: const Color(0xFF2563EB),
        backgroundColor: const Color(0xFFF1F5F9),
        labelStyle: TextStyle(
          color: isSelected ? Colors.white : const Color(0xFF475569),
          fontWeight: isSelected ? FontWeight.bold : FontWeight.w500,
          fontSize: 12,
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(20),
          side: BorderSide(
            color: isSelected ? const Color(0xFF2563EB) : const Color(0xFFE2E8F0),
            width: 1,
          ),
        ),
        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
        showCheckmark: false,
      ),
    );
  }

  Widget _buildPagesList(BusinessProvider bp) {
    final list = _tabController.index == 1 ? bp.myPages : bp.pages;

    if (list.isEmpty) {
      return _buildEmptyState(
        _tabController.index == 1 ? 'No Business Pages Created' : 'No Business Pages Found',
        _tabController.index == 1
            ? 'Create dedicated business pages to run campaigns, build presence, and scale your brand.'
            : 'Try adjusting your search query or category filters.',
        action: _tabController.index == 1
            ? ElevatedButton.icon(
                onPressed: _showCreatePageSheet,
                icon: const Icon(Icons.add, size: 16),
                label: const Text('Create Business Page'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: const Color(0xFF2563EB),
                  foregroundColor: Colors.white,
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                ),
              )
            : null,
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.fromLTRB(16, 14, 16, 80),
      itemCount: list.length,
      itemBuilder: (ctx, i) {
        final page = list[i];
        return _buildBusinessCard(page);
      },
    );
  }

  Widget _buildBusinessCard(BusinessPageModel page) {
    final coverUrl = AppConstants.resolveMediaUrl(page.bannerUrl);
    final logoUrl = AppConstants.resolveMediaUrl(page.avatarUrl);

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(18),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.03),
            blurRadius: 10,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // 1. Cover Banner with Floating Visibility Pill
          Stack(
            clipBehavior: Clip.none,
            children: [
              Container(
                height: 100,
                width: double.infinity,
                decoration: BoxDecoration(
                  gradient: const LinearGradient(
                    colors: [Color(0xFF176BFF), Color(0xFF7146ED)],
                    begin: Alignment.topLeft,
                    end: Alignment.bottomRight,
                  ),
                  image: coverUrl != null && coverUrl.isNotEmpty
                      ? DecorationImage(
                          image: NetworkImage(coverUrl),
                          fit: BoxFit.cover,
                        )
                      : null,
                ),
              ),

              // Floating Visibility Pill
              Positioned(
                top: 10,
                right: 12,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 9, vertical: 4),
                  decoration: BoxDecoration(
                    color: Colors.black.withOpacity(0.55),
                    borderRadius: BorderRadius.circular(14),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      if (page.visibility == 'private') ...[
                        const Icon(Icons.lock, color: Colors.white, size: 11),
                        const SizedBox(width: 4),
                        const Text('Private', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                      ] else if (page.visibility == 'draft') ...[
                        const Icon(Icons.edit_note, color: Colors.white, size: 12),
                        const SizedBox(width: 4),
                        const Text('Draft', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                      ] else ...[
                        Container(
                          width: 6,
                          height: 6,
                          decoration: const BoxDecoration(color: Color(0xFF22C55E), shape: BoxShape.circle),
                        ),
                        const SizedBox(width: 5),
                        const Text('Public', style: TextStyle(color: Colors.white, fontSize: 11, fontWeight: FontWeight.bold)),
                      ],
                    ],
                  ),
                ),
              ),

              // Overlapping Circular Logo
              Positioned(
                bottom: -22,
                left: 16,
                child: Container(
                  width: 52,
                  height: 52,
                  decoration: BoxDecoration(
                    shape: BoxShape.circle,
                    color: Colors.white,
                    border: Border.all(color: Colors.white, width: 3),
                    boxShadow: [
                      BoxShadow(
                        color: Colors.black.withOpacity(0.12),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ],
                  ),
                  child: ClipOval(
                    child: logoUrl != null && logoUrl.isNotEmpty
                        ? Image.network(
                            logoUrl,
                            fit: BoxFit.cover,
                            errorBuilder: (ctx, err, stack) => _buildFallbackAvatar(page.initials),
                          )
                        : _buildFallbackAvatar(page.initials),
                  ),
                ),
              ),
            ],
          ),

          const SizedBox(height: 28),

          // 2. Card Body
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                // Business Name & Handle
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        page.pageName,
                        style: const TextStyle(
                          fontSize: 16,
                          fontWeight: FontWeight.w700,
                          color: Color(0xFF0F172A),
                        ),
                        maxLines: 1,
                        overflow: TextOverflow.ellipsis,
                      ),
                    ),
                    if (page.isVerified) ...[
                      const SizedBox(width: 4),
                      const Icon(Icons.verified, color: Color(0xFF16A34A), size: 16),
                    ],
                  ],
                ),
                const SizedBox(height: 2),
                Text(
                  '@${page.pageUsername}',
                  style: const TextStyle(
                    color: Color(0xFF2563EB),
                    fontSize: 12.5,
                    fontWeight: FontWeight.w600,
                  ),
                ),

                const SizedBox(height: 8),

                // Category Badge Pill
                if (page.category.isNotEmpty) ...[
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 3),
                    decoration: BoxDecoration(
                      color: const Color(0xFFEFF6FF),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.tag, size: 11, color: Color(0xFF2563EB)),
                        const SizedBox(width: 4),
                        Text(
                          page.category,
                          style: const TextStyle(
                            color: Color(0xFF2563EB),
                            fontSize: 11,
                            fontWeight: FontWeight.bold,
                          ),
                        ),
                      ],
                    ),
                  ),
                  const SizedBox(height: 8),
                ],

                // Description Clamped
                Text(
                  (page.description != null && page.description!.isNotEmpty)
                      ? page.description!
                      : 'Verified Business Page on MLM Book ecosystem.',
                  style: const TextStyle(
                    fontSize: 12.5,
                    color: Color(0xFF475569),
                    height: 1.35,
                  ),
                  maxLines: 2,
                  overflow: TextOverflow.ellipsis,
                ),

                const SizedBox(height: 10),

                // Location & Meta Info Row
                Row(
                  children: [
                    if (page.locationString.isNotEmpty) ...[
                      const Icon(Icons.location_on_outlined, size: 13, color: Color(0xFF64748B)),
                      const SizedBox(width: 3),
                      Expanded(
                        child: Text(
                          page.locationString,
                          style: const TextStyle(fontSize: 11, color: Color(0xFF64748B)),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ),
                    ],
                  ],
                ),

                const SizedBox(height: 14),
                const Divider(height: 1, color: Color(0xFFF1F5F9)),
                const SizedBox(height: 10),

                // 3. Action Buttons Strip
                Row(
                  children: [
                    // View Page Button
                    Expanded(
                      child: OutlinedButton.icon(
                        onPressed: () {
                          Navigator.push(
                            context,
                            MaterialPageRoute(
                              builder: (_) => BusinessDetailScreen(businessPage: page),
                            ),
                          );
                        },
                        icon: const Icon(Icons.visibility_outlined, size: 15, color: Color(0xFF2563EB)),
                        label: const Text(
                          'View Page',
                          style: TextStyle(color: Color(0xFF2563EB), fontWeight: FontWeight.bold, fontSize: 12.5),
                        ),
                        style: OutlinedButton.styleFrom(
                          backgroundColor: const Color(0xFFEFF6FF),
                          side: const BorderSide(color: Color(0xFFDBEAFE)),
                          padding: const EdgeInsets.symmetric(vertical: 8),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                      ),
                    ),

                    if (page.isOwner) ...[
                      const SizedBox(width: 8),
                      // Delete Button
                      IconButton(
                        onPressed: () async {
                          final confirmed = await AppDialog.confirm(
                            context,
                            title: 'Delete Business Page',
                            message: 'Are you sure you want to delete "${page.pageName}"? This action cannot be undone.',
                            confirmText: 'Delete',
                            isDestructive: true,
                            icon: Icons.delete_outline,
                          );
                          if (confirmed == true && mounted) {
                            final ok = await context.read<BusinessProvider>().deleteBusinessPage(page.slug);
                            if (ok && mounted) {
                              AppToast.success(context, 'Business page deleted.');
                            }
                          }
                        },
                        icon: const Icon(Icons.delete_outline, color: Color(0xFFEF4444), size: 19),
                        tooltip: 'Delete Page',
                        style: IconButton.styleFrom(
                          backgroundColor: const Color(0xFFFEF2F2),
                          padding: const EdgeInsets.all(8),
                          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                        ),
                      ),
                    ],
                  ],
                ),
              ],
            ),
          ),

          const SizedBox(height: 12),
        ],
      ),
    );
  }

  Widget _buildFallbackAvatar(String initials) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFF2563EB), Color(0xFF7C3AED)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      alignment: Alignment.center,
      child: Text(
        initials,
        style: const TextStyle(
          color: Colors.white,
          fontWeight: FontWeight.bold,
          fontSize: 16,
        ),
      ),
    );
  }

  Widget _buildEmptyState(String title, String subtitle, {Widget? action}) {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: const BoxDecoration(
                color: Color(0xFFEFF6FF),
                shape: BoxShape.circle,
              ),
              child: const Icon(Icons.storefront_outlined, size: 40, color: Color(0xFF2563EB)),
            ),
            const SizedBox(height: 16),
            Text(
              title,
              style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF0F172A)),
            ),
            const SizedBox(height: 6),
            Text(
              subtitle,
              textAlign: TextAlign.center,
              style: const TextStyle(color: Color(0xFF64748B), fontSize: 13),
            ),
            if (action != null) ...[
              const SizedBox(height: 16),
              action,
            ],
          ],
        ),
      ),
    );
  }
}
