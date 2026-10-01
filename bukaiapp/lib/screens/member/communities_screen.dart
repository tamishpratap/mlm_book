import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../../core/app_bottom_sheet.dart';
import '../../core/app_toast.dart';
import '../../core/constants.dart';
import '../../models/community_model.dart';
import '../../providers/community_provider.dart';
import 'community_detail_screen.dart';

class CommunitiesScreen extends StatefulWidget {
  final VoidCallback? onBack;
  const CommunitiesScreen({super.key, this.onBack});

  @override
  State<CommunitiesScreen> createState() => _CommunitiesScreenState();
}

class _CommunitiesScreenState extends State<CommunitiesScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final TextEditingController _searchController = TextEditingController();
  final ImagePicker _picker = ImagePicker();

  final List<String> _tabs = ['all', 'joined', 'my', 'discover'];

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 4, vsync: this);
    WidgetsBinding.instance.addPostFrameCallback((_) {
      context.read<CommunityProvider>().fetchCommunities(tab: 'all');
    });

    _tabController.addListener(() {
      if (_tabController.indexIsChanging) {
        final selectedTab = _tabs[_tabController.index];
        context.read<CommunityProvider>().fetchCommunities(tab: selectedTab);
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
    final cp = context.read<CommunityProvider>();
    final target = (category == 'All' || cp.selectedCategory == category) ? '' : category;
    cp.fetchCommunities(category: target);
  }

  void _onSearchSubmitted(String query) {
    final cp = context.read<CommunityProvider>();
    cp.fetchCommunities(search: query.trim());
  }

  void _showCreateCommunitySheet() {
    final nameCtrl = TextEditingController();
    final descCtrl = TextEditingController();
    final rulesCtrl = TextEditingController();
    final tagsCtrl = TextEditingController();

    String selectedCategory = 'Technology';
    String selectedVisibility = 'public';

    XFile? pickedLogoFile;
    Uint8List? pickedLogoBytes;
    XFile? pickedCoverFile;
    Uint8List? pickedCoverBytes;
    bool isSubmitting = false;

    AppBottomSheet.show(
      context,
      title: 'Create Community',
      child: StatefulBuilder(
        builder: (ctx, setModalState) {
          final cp = ctx.read<CommunityProvider>();
          final categoryOptions = cp.categories.isNotEmpty
              ? cp.categories
              : [
                  'Technology',
                  'Business',
                  'Crypto',
                  'Marketing',
                  'Gaming',
                  'Education',
                  'Lifestyle',
                  'Health & Wellness',
                  'E-Commerce',
                  'MLM & Direct Sales',
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
                  pickedLogoFile = file;
                  pickedLogoBytes = bytes;
                });
              }
            } catch (e) {
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
                  pickedCoverFile = file;
                  pickedCoverBytes = bytes;
                });
              }
            } catch (e) {
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
                                  colors: [Color(0xFF2563EB), Color(0xFF7C3AED)],
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
                                    Icon(Icons.add_photo_alternate_outlined,
                                        color: Colors.white, size: 20),
                                    SizedBox(width: 8),
                                    Text(
                                      'Add Cover Photo',
                                      style: TextStyle(
                                        color: Colors.white,
                                        fontWeight: FontWeight.w600,
                                        fontSize: 13,
                                      ),
                                    ),
                                  ],
                                ),
                              ),
                            if (pickedCoverBytes != null)
                              Positioned(
                                top: 8,
                                right: 8,
                                child: GestureDetector(
                                  onTap: () {
                                    setModalState(() {
                                      pickedCoverFile = null;
                                      pickedCoverBytes = null;
                                    });
                                  },
                                  child: Container(
                                    padding: const EdgeInsets.all(5),
                                    decoration: BoxDecoration(
                                      color: Colors.black.withOpacity(0.6),
                                      shape: BoxShape.circle,
                                    ),
                                    child: const Icon(Icons.close,
                                        color: Colors.white, size: 14),
                                  ),
                                ),
                              ),
                          ],
                        ),
                      ),
                    ),

                    // Overlapping Logo Avatar
                    Positioned(
                      bottom: -22,
                      left: 18,
                      child: GestureDetector(
                        onTap: pickLogo,
                        child: Container(
                          width: 58,
                          height: 58,
                          decoration: BoxDecoration(
                            shape: BoxShape.circle,
                            color: Colors.white,
                            border: Border.all(color: Colors.white, width: 3),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.12),
                                blurRadius: 8,
                                offset: const Offset(0, 2),
                              ),
                            ],
                            image: pickedLogoBytes != null
                                ? DecorationImage(
                                    image: MemoryImage(pickedLogoBytes!),
                                    fit: BoxFit.cover,
                                  )
                                : null,
                            gradient: pickedLogoBytes == null
                                ? const LinearGradient(
                                    colors: [Color(0xFF00D2FF), Color(0xFF9B51E0)],
                                  )
                                : null,
                          ),
                          child: pickedLogoBytes == null
                              ? const Icon(Icons.camera_alt_outlined,
                                  color: Colors.white, size: 24)
                              : null,
                        ),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 34),

                // 2. Community Name Field
                TextField(
                  controller: nameCtrl,
                  style: const TextStyle(
                    color: Color(0xFF0F172A),
                    fontSize: 14.5,
                    fontWeight: FontWeight.w600,
                  ),
                  decoration: InputDecoration(
                    labelText: 'Community Name *',
                    labelStyle: const TextStyle(color: Color(0xFF64748B), fontSize: 13.5),
                    hintText: 'e.g., Global Crypto Builders',
                    hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
                    ),
                  ),
                ),

                const SizedBox(height: 14),

                // 3. Category Selector Dropdown
                DropdownButtonFormField<String>(
                  value: categoryOptions.contains(selectedCategory)
                      ? selectedCategory
                      : categoryOptions.first,
                  decoration: InputDecoration(
                    labelText: 'Category *',
                    labelStyle: const TextStyle(color: Color(0xFF64748B), fontSize: 13.5),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
                    ),
                  ),
                  items: categoryOptions
                      .map((cat) => DropdownMenuItem(
                            value: cat,
                            child: Text(cat, style: const TextStyle(fontSize: 14, color: Color(0xFF0F172A))),
                          ))
                      .toList(),
                  onChanged: (val) {
                    if (val != null) setModalState(() => selectedCategory = val);
                  },
                ),

                const SizedBox(height: 14),

                // 4. Visibility Selector Radio Cards
                const Text(
                  'Privacy & Visibility',
                  style: TextStyle(
                    color: Color(0xFF0F172A),
                    fontSize: 13.5,
                    fontWeight: FontWeight.w700,
                  ),
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: _buildVisibilityOption(
                        label: 'Public',
                        subLabel: 'Anyone can join',
                        icon: Icons.public,
                        isSelected: selectedVisibility == 'public',
                        onTap: () => setModalState(() => selectedVisibility = 'public'),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildVisibilityOption(
                        label: 'Private',
                        subLabel: 'Approval needed',
                        icon: Icons.lock_outline,
                        isSelected: selectedVisibility == 'private',
                        onTap: () => setModalState(() => selectedVisibility = 'private'),
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: _buildVisibilityOption(
                        label: 'Invite',
                        subLabel: 'Invite only',
                        icon: Icons.mail_outline,
                        isSelected: selectedVisibility == 'invite_only',
                        onTap: () => setModalState(() => selectedVisibility = 'invite_only'),
                      ),
                    ),
                  ],
                ),

                const SizedBox(height: 14),

                // 5. Description Field
                TextField(
                  controller: descCtrl,
                  maxLines: 3,
                  style: const TextStyle(color: Color(0xFF0F172A), fontSize: 13.5),
                  decoration: InputDecoration(
                    labelText: 'About / Description',
                    labelStyle: const TextStyle(color: Color(0xFF64748B), fontSize: 13.5),
                    hintText: 'What is the purpose of this community?',
                    hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
                    ),
                  ),
                ),

                const SizedBox(height: 14),

                // 6. Community Rules & Guidelines
                TextField(
                  controller: rulesCtrl,
                  maxLines: 2,
                  style: const TextStyle(color: Color(0xFF0F172A), fontSize: 13.5),
                  decoration: InputDecoration(
                    labelText: 'Rules & Guidelines (Optional)',
                    labelStyle: const TextStyle(color: Color(0xFF64748B), fontSize: 13.5),
                    hintText: 'e.g. Respect members, No spamming',
                    hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
                    ),
                  ),
                ),

                const SizedBox(height: 14),

                // 7. Tags Field
                TextField(
                  controller: tagsCtrl,
                  style: const TextStyle(color: Color(0xFF0F172A), fontSize: 13.5),
                  decoration: InputDecoration(
                    labelText: 'Tags (Optional)',
                    labelStyle: const TextStyle(color: Color(0xFF64748B), fontSize: 13.5),
                    hintText: 'e.g. mlm, crypto, networking',
                    hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                    filled: true,
                    fillColor: const Color(0xFFF8FAFC),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
                    border: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    enabledBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                    ),
                    focusedBorder: OutlineInputBorder(
                      borderRadius: BorderRadius.circular(12),
                      borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
                    ),
                  ),
                ),

                const SizedBox(height: 22),

                // 8. Submit Button
                ElevatedButton(
                  onPressed: isSubmitting
                      ? null
                      : () async {
                          final name = nameCtrl.text.trim();
                          if (name.isEmpty) {
                            AppToast.error(ctx, 'Please enter a community name.');
                            return;
                          }

                          setModalState(() => isSubmitting = true);

                          final res = await ctx.read<CommunityProvider>().createCommunity(
                                name: name,
                                description: descCtrl.text.trim(),
                                category: selectedCategory,
                                visibility: selectedVisibility,
                                rules: rulesCtrl.text.trim(),
                                tags: tagsCtrl.text.trim(),
                                logoBytes: pickedLogoBytes,
                                logoFileName: pickedLogoFile?.name ?? 'logo.jpg',
                                coverBytes: pickedCoverBytes,
                                coverFileName: pickedCoverFile?.name ?? 'cover.jpg',
                              );

                          setModalState(() => isSubmitting = false);

                          if (ctx.mounted) {
                            Navigator.pop(ctx);
                            if (res['success'] == true) {
                              AppToast.success(context, res['message']?.toString() ?? 'Community created successfully!');
                            } else {
                              AppToast.error(context, res['message']?.toString() ?? 'Failed to create community.');
                            }
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
                          'Create Community',
                          style: TextStyle(fontWeight: FontWeight.w700, fontSize: 14.5),
                        ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  Widget _buildVisibilityOption({
    required String label,
    required String subLabel,
    required IconData icon,
    required bool isSelected,
    required VoidCallback onTap,
  }) {
    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 180),
        padding: const EdgeInsets.symmetric(vertical: 10, horizontal: 8),
        decoration: BoxDecoration(
          color: isSelected ? const Color(0xFFEFF6FF) : const Color(0xFFF8FAFC),
          borderRadius: BorderRadius.circular(10),
          border: Border.all(
            color: isSelected ? const Color(0xFF2563EB) : const Color(0xFFE2E8F0),
            width: isSelected ? 1.5 : 1,
          ),
        ),
        child: Column(
          children: [
            Icon(
              icon,
              size: 20,
              color: isSelected ? const Color(0xFF2563EB) : const Color(0xFF64748B),
            ),
            const SizedBox(height: 4),
            Text(
              label,
              style: TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w700,
                color: isSelected ? const Color(0xFF2563EB) : const Color(0xFF0F172A),
              ),
            ),
            Text(
              subLabel,
              style: TextStyle(
                fontSize: 9.5,
                color: isSelected ? const Color(0xFF3B82F6) : const Color(0xFF94A3B8),
              ),
              textAlign: TextAlign.center,
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final cp = context.watch<CommunityProvider>();

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF0F172A), size: 24),
          tooltip: 'Back',
          onPressed: () {
            if (widget.onBack != null) {
              widget.onBack!();
            } else if (Navigator.canPop(context)) {
              Navigator.pop(context);
            }
          },
        ),
        title: const Text(
          'Communities',
          style: TextStyle(
            color: Color(0xFF0F172A),
            fontSize: 18,
            fontWeight: FontWeight.w800,
            letterSpacing: -0.3,
          ),
        ),
        bottom: PreferredSize(
          preferredSize: const Size.fromHeight(48),
          child: Container(
            decoration: const BoxDecoration(
              border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
            ),
            child: TabBar(
              controller: _tabController,
              isScrollable: true,
              tabAlignment: TabAlignment.start,
              indicatorColor: const Color(0xFF2563EB),
              indicatorWeight: 3,
              indicatorSize: TabBarIndicatorSize.label,
              labelColor: const Color(0xFF2563EB),
              unselectedLabelColor: const Color(0xFF64748B),
              labelStyle: const TextStyle(fontWeight: FontWeight.w700, fontSize: 13.5),
              unselectedLabelStyle: const TextStyle(fontWeight: FontWeight.w500, fontSize: 13.5),
              padding: const EdgeInsets.symmetric(horizontal: 8),
              tabs: [
                const Tab(text: 'All Communities'),
                Tab(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Text('Joined'),
                      if (cp.joinedCount > 0) ...[
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(
                            color: const Color(0xFFEFF6FF),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            '${cp.joinedCount}',
                            style: const TextStyle(
                              color: Color(0xFF2563EB),
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                Tab(
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Text('My Communities'),
                      if (cp.myCount > 0) ...[
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 7, vertical: 2),
                          decoration: BoxDecoration(
                            color: const Color(0xFFEFF6FF),
                            borderRadius: BorderRadius.circular(12),
                          ),
                          child: Text(
                            '${cp.myCount}',
                            style: const TextStyle(
                              color: Color(0xFF2563EB),
                              fontSize: 11,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
                const Tab(text: 'Discover'),
              ],
            ),
          ),
        ),
      ),
      floatingActionButton: FloatingActionButton.extended(
        heroTag: 'communities_fab',
        onPressed: _showCreateCommunitySheet,
        backgroundColor: const Color(0xFF2563EB),
        icon: const Icon(Icons.add, color: Colors.white, size: 20),
        label: const Text(
          'Create',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700, fontSize: 13.5),
        ),
      ),
      body: Column(
        children: [
          // 1. Search Bar (Rounded Pill style matching the image)
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 6),
            child: SizedBox(
              height: 44,
              child: TextField(
                controller: _searchController,
                textInputAction: TextInputAction.search,
                onSubmitted: _onSearchSubmitted,
                onChanged: (val) {
                  if (val.isEmpty) {
                    _onSearchSubmitted('');
                  }
                },
                style: const TextStyle(fontSize: 13.5, color: Color(0xFF0F172A)),
                decoration: InputDecoration(
                  hintText: 'Search communities by name, topic, category...',
                  hintStyle: const TextStyle(color: Color(0xFF94A3B8), fontSize: 13),
                  prefixIcon: const Icon(Icons.search, color: Color(0xFF64748B), size: 20),
                  suffixIcon: _searchController.text.isNotEmpty
                      ? IconButton(
                          icon: const Icon(Icons.clear, size: 16, color: Color(0xFF94A3B8)),
                          onPressed: () {
                            _searchController.clear();
                            _onSearchSubmitted('');
                          },
                        )
                      : null,
                  filled: true,
                  fillColor: Colors.white,
                  contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 0),
                  enabledBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: const BorderSide(color: Color(0xFFE2E8F0)),
                  ),
                  focusedBorder: OutlineInputBorder(
                    borderRadius: BorderRadius.circular(24),
                    borderSide: const BorderSide(color: Color(0xFF2563EB), width: 1.5),
                  ),
                ),
              ),
            ),
          ),

          // 2. Category Filter Chips
          _buildCategoryPills(cp),

          // 3. Main Content List / Loading / Empty
          Expanded(
            child: RefreshIndicator(
              color: const Color(0xFF2563EB),
              backgroundColor: Colors.white,
              onRefresh: () async {
                final selectedTab = _tabs[_tabController.index];
                await cp.fetchCommunities(tab: selectedTab);
              },
              child: cp.isLoading
                  ? const Center(
                      child: CircularProgressIndicator(color: Color(0xFF2563EB)),
                    )
                  : cp.communities.isEmpty
                      ? _buildEmptyState(cp)
                      : ListView.builder(
                          padding: const EdgeInsets.fromLTRB(16, 12, 16, 80),
                          itemCount: cp.communities.length,
                          itemBuilder: (ctx, i) {
                            final community = cp.communities[i];
                            return _buildRedesignedCommunityCard(community);
                          },
                        ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildCategoryPills(CommunityProvider cp) {
    final allCategories = ['All', ...cp.categories];
    final currentCat = cp.selectedCategory ?? 'All';

    return Container(
      height: 48,
      decoration: const BoxDecoration(
        color: Colors.white,
        border: Border(bottom: BorderSide(color: Color(0xFFF1F5F9), width: 1)),
      ),
      child: ListView.separated(
        scrollDirection: Axis.horizontal,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
        itemCount: allCategories.length,
        separatorBuilder: (ctx, i) => const SizedBox(width: 8),
        itemBuilder: (context, index) {
          final cat = allCategories[index];
          final isSelected = cat == currentCat;

          return GestureDetector(
            onTap: () => _onCategorySelected(cat),
            child: AnimatedContainer(
              duration: const Duration(milliseconds: 180),
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
              decoration: BoxDecoration(
                color: isSelected ? const Color(0xFF2563EB) : const Color(0xFFF1F5F9),
                borderRadius: BorderRadius.circular(20),
                border: Border.all(
                  color: isSelected ? const Color(0xFF2563EB) : const Color(0xFFE2E8F0),
                ),
              ),
              child: Center(
                child: Text(
                  cat,
                  style: TextStyle(
                    fontSize: 12,
                    fontWeight: isSelected ? FontWeight.w700 : FontWeight.w500,
                    color: isSelected ? Colors.white : const Color(0xFF475569),
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  Widget _buildEmptyState(CommunityProvider cp) {
    return SingleChildScrollView(
      physics: const AlwaysScrollableScrollPhysics(),
      padding: const EdgeInsets.all(32),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const SizedBox(height: 40),
          Container(
            padding: const EdgeInsets.all(20),
            decoration: BoxDecoration(
              color: const Color(0xFFEFF6FF),
              shape: BoxShape.circle,
            ),
            child: const Icon(Icons.groups_outlined, size: 48, color: Color(0xFF2563EB)),
          ),
          const SizedBox(height: 18),
          const Text(
            'No Communities Found',
            style: TextStyle(fontSize: 17, fontWeight: FontWeight.w700, color: Color(0xFF0F172A)),
          ),
          const SizedBox(height: 8),
          Text(
            cp.searchQuery.isNotEmpty
                ? 'No matching results for "${cp.searchQuery}". Try a different keyword or category.'
                : 'Connect with other members by creating or exploring new groups.',
            textAlign: TextAlign.center,
            style: const TextStyle(color: Color(0xFF64748B), fontSize: 13.5, height: 1.4),
          ),
          const SizedBox(height: 20),
          ElevatedButton.icon(
            onPressed: _showCreateCommunitySheet,
            icon: const Icon(Icons.add, size: 18),
            label: const Text('Create a Community'),
            style: ElevatedButton.styleFrom(
              backgroundColor: const Color(0xFF2563EB),
              foregroundColor: Colors.white,
              elevation: 0,
              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildRedesignedCommunityCard(CommunityModel c) {
    final coverUrl = AppConstants.resolveMediaUrl(c.bannerUrl);
    final logoUrl = AppConstants.resolveMediaUrl(c.avatarUrl);
    final initials = c.name.isNotEmpty ? c.name.trim().substring(0, 1).toUpperCase() : 'C';

    return Container(
      margin: const EdgeInsets.only(bottom: 16),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE2E8F0)),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 10,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: InkWell(
        onTap: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => CommunityDetailScreen(community: c)),
          );
        },
        borderRadius: BorderRadius.circular(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            // 1. Cover Photo with Gradient Fallback & Badges
            Stack(
              clipBehavior: Clip.none,
              children: [
                ClipRRect(
                  borderRadius: const BorderRadius.vertical(top: Radius.circular(16)),
                  child: Container(
                    height: 110,
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
                ),

                // Top Badges (Category & Visibility)
                Positioned(
                  top: 10,
                  left: 10,
                  right: 10,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Category Badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.black.withOpacity(0.55),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.white.withOpacity(0.2)),
                        ),
                        child: Text(
                          c.category,
                          style: const TextStyle(
                            color: Colors.white,
                            fontSize: 11,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),

                      // Visibility Badge
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: Colors.black.withOpacity(0.55),
                          borderRadius: BorderRadius.circular(20),
                          border: Border.all(color: Colors.white.withOpacity(0.2)),
                        ),
                        child: Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(
                              c.visibility == 'private'
                                  ? Icons.lock_outline
                                  : c.visibility == 'invite_only'
                                      ? Icons.mail_outline
                                      : Icons.public,
                              size: 12,
                              color: Colors.white,
                            ),
                            const SizedBox(width: 4),
                            Text(
                              c.visibility == 'private'
                                  ? 'Private'
                                  : c.visibility == 'invite_only'
                                      ? 'Invite'
                                      : 'Public',
                              style: const TextStyle(
                                color: Colors.white,
                                fontSize: 10.5,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ],
                  ),
                ),

                // Overlapping Logo Avatar
                Positioned(
                  bottom: -20,
                  left: 14,
                  child: Container(
                    width: 52,
                    height: 52,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white,
                      border: Border.all(color: Colors.white, width: 2.5),
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
                              errorBuilder: (ctx, err, stack) => _buildFallbackAvatar(initials),
                            )
                          : _buildFallbackAvatar(initials),
                    ),
                  ),
                ),
              ],
            ),

            // 2. Card Content & Body
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 26, 16, 14),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  // Community Name
                  Text(
                    c.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF0F172A),
                    ),
                  ),

                  // Creator / Owner Tag
                  const SizedBox(height: 3),
                  Row(
                    children: [
                      const Text(
                        'Created by ',
                        style: TextStyle(color: Color(0xFF94A3B8), fontSize: 12),
                      ),
                      Text(
                        c.ownerName ?? 'Community Member',
                        style: const TextStyle(
                          color: Color(0xFF475569),
                          fontWeight: FontWeight.w600,
                          fontSize: 12,
                        ),
                      ),
                      if (c.isOwner) ...[
                        const SizedBox(width: 6),
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                          decoration: BoxDecoration(
                            color: const Color(0xFFEFF6FF),
                            borderRadius: BorderRadius.circular(4),
                          ),
                          child: const Text(
                            'Owner',
                            style: TextStyle(
                              color: Color(0xFF2563EB),
                              fontSize: 9.5,
                              fontWeight: FontWeight.w700,
                            ),
                          ),
                        ),
                      ],
                    ],
                  ),

                  // Description
                  if (c.description != null && c.description!.isNotEmpty) ...[
                    const SizedBox(height: 8),
                    Text(
                      c.description!,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(
                        color: Color(0xFF64748B),
                        fontSize: 13,
                        height: 1.35,
                      ),
                    ),
                  ],

                  const SizedBox(height: 14),
                  const Divider(height: 1, color: Color(0xFFF1F5F9)),
                  const SizedBox(height: 12),

                  // 3. Card Footer (Members count + Action Button)
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Row(
                        children: [
                          const Icon(Icons.people_outline, size: 16, color: Color(0xFF64748B)),
                          const SizedBox(width: 6),
                          Text(
                            '${c.membersCount} ${c.membersCount == 1 ? 'member' : 'members'}',
                            style: const TextStyle(
                              color: Color(0xFF64748B),
                              fontSize: 12.5,
                              fontWeight: FontWeight.w600,
                            ),
                          ),
                        ],
                      ),

                      // Join / Joined / Manage Button
                      _buildActionButton(c),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildFallbackAvatar(String initials) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [Color(0xFF00D2FF), Color(0xFF9B51E0)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      child: Center(
        child: Text(
          initials,
          style: const TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w800,
            fontSize: 20,
          ),
        ),
      ),
    );
  }

  Widget _buildActionButton(CommunityModel c) {
    if (c.isOwner) {
      return OutlinedButton.icon(
        onPressed: () {
          Navigator.push(
            context,
            MaterialPageRoute(builder: (_) => CommunityDetailScreen(community: c)),
          );
        },
        icon: const Icon(Icons.settings_outlined, size: 14),
        label: const Text('Manage'),
        style: OutlinedButton.styleFrom(
          foregroundColor: const Color(0xFF2563EB),
          side: const BorderSide(color: Color(0xFF93C5FD)),
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          minimumSize: const Size(0, 32),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
        ),
      );
    }

    if (c.isPending) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
        decoration: BoxDecoration(
          color: const Color(0xFFFEF3C7),
          borderRadius: BorderRadius.circular(8),
        ),
        child: Row(
          mainAxisSize: MainAxisSize.min,
          children: const [
            Icon(Icons.schedule, size: 14, color: Color(0xFFD97706)),
            SizedBox(width: 4),
            Text(
              'Pending',
              style: TextStyle(
                color: Color(0xFFD97706),
                fontSize: 12,
                fontWeight: FontWeight.w700,
              ),
            ),
          ],
        ),
      );
    }

    if (c.isMember) {
      return ElevatedButton.icon(
        onPressed: () async {
          final ok = await context.read<CommunityProvider>().toggleJoin(c);
          if (mounted && ok) {
            AppToast.info(context, 'Left community');
          }
        },
        icon: const Icon(Icons.check, size: 14, color: Color(0xFF16A34A)),
        label: const Text('Joined'),
        style: ElevatedButton.styleFrom(
          backgroundColor: const Color(0xFFDCFCE7),
          foregroundColor: const Color(0xFF16A34A),
          elevation: 0,
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          minimumSize: const Size(0, 32),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
          textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
        ),
      );
    }

    return ElevatedButton.icon(
      onPressed: () async {
        final ok = await context.read<CommunityProvider>().toggleJoin(c);
        if (mounted && ok) {
          AppToast.success(context, 'Joined ${c.name}!');
        }
      },
      icon: const Icon(Icons.add, size: 14),
      label: const Text('Join Community'),
      style: ElevatedButton.styleFrom(
        backgroundColor: const Color(0xFF2563EB),
        foregroundColor: Colors.white,
        elevation: 0,
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        minimumSize: const Size(0, 32),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
        textStyle: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
      ),
    );
  }
}
