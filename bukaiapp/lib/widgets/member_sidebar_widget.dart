import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../providers/auth_provider.dart';
import 'referral_program_dialog.dart';

enum MemberSidebarItem {
  home,
  socials,
  profile,
  connections,
  newConnections,
  savedPosts,
  disconnections,
  watch,
  community,
  businessPages,
  businessDirectory,
  events,
  accountSettings,
  feedback,
}

class MemberSidebarWidget extends StatelessWidget {
  final MemberSidebarItem activeItem;
  final ValueChanged<MemberSidebarItem> onSelectItem;
  final VoidCallback? onOpenAdmin;
  final VoidCallback? onLogout;
  final bool isDrawer;

  const MemberSidebarWidget({
    super.key,
    required this.activeItem,
    required this.onSelectItem,
    this.onOpenAdmin,
    this.onLogout,
    this.isDrawer = false,
  });

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final member = auth.currentMember;

    final initials = member?.name.isNotEmpty == true
        ? member!.name
            .trim()
            .split(' ')
            .map((n) => n.isNotEmpty ? n[0] : '')
            .take(2)
            .join('')
            .toUpperCase()
        : 'MB';

    return Container(
      width: 255,
      decoration: BoxDecoration(
        color: Colors.white,
        border: isDrawer ? null : const Border(right: BorderSide(color: Color(0xFFE2E8F0), width: 1.0)),
      ),
      child: SafeArea(
        child: Column(
          children: [
            // Scrollable Menu Area
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 12),
                children: [
                  // 1. Profile Card at Top (Exact 1:1 Match)
                  MouseRegion(
                    cursor: SystemMouseCursors.click,
                    child: GestureDetector(
                      behavior: HitTestBehavior.opaque,
                      onTap: () => onSelectItem(MemberSidebarItem.profile),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 10),
                        margin: const EdgeInsets.only(bottom: 12),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 42,
                              height: 42,
                              decoration: const BoxDecoration(
                                shape: BoxShape.circle,
                                gradient: LinearGradient(
                                  colors: [Color(0xFF176BFF), Color(0xFF7146ED)],
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
                                  fontSize: 14,
                                ),
                              ),
                            ),
                            const SizedBox(width: 10),
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    member?.name ?? 'Member',
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.bold,
                                      fontSize: 13.5,
                                      color: Color(0xFF0F172A),
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  const Text(
                                    'View profile',
                                    style: TextStyle(
                                      fontSize: 11.5,
                                      color: Color(0xFF64748B),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            const Icon(
                              Icons.chevron_right,
                              size: 18,
                              color: Color(0xFF94A3B8),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),

                  // 2. Canonical 14 Navigation Items (Exact Screenshot Match)
                  _buildNavItem(
                    item: MemberSidebarItem.home,
                    icon: Icons.home_outlined,
                    label: 'Home',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.socials,
                    icon: Icons.auto_awesome_outlined,
                    label: 'Socials',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.profile,
                    icon: Icons.person_outline_rounded,
                    label: 'My Profile',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.connections,
                    icon: Icons.people_outline_rounded,
                    label: 'Connections',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.newConnections,
                    icon: Icons.auto_awesome,
                    label: 'New Connections',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.savedPosts,
                    icon: Icons.bookmark_border_rounded,
                    label: 'Saved Posts',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.disconnections,
                    icon: Icons.person_off_outlined,
                    label: 'Disconnections',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.watch,
                    icon: Icons.smart_display_outlined,
                    label: 'Watch',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.community,
                    icon: Icons.people_alt_outlined,
                    label: 'Community',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.businessPages,
                    icon: Icons.business_outlined,
                    label: 'Business Pages',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.businessDirectory,
                    icon: Icons.explore_outlined,
                    label: 'Business Directory',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.events,
                    icon: Icons.calendar_today_outlined,
                    label: 'Events',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.accountSettings,
                    icon: Icons.settings_outlined,
                    label: 'Account Settings',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.feedback,
                    icon: Icons.chat_bubble_outline_rounded,
                    label: 'Feedback & Suggestions',
                  ),

                  // 3. Divider
                  const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 8, vertical: 8),
                    child: Divider(color: Color(0xFFE2E8F0), height: 1),
                  ),

                  // 4. Shortcuts Heading (Exact Screenshot Match)
                  const Padding(
                    padding: EdgeInsets.only(left: 12, top: 4, bottom: 6),
                    child: Text(
                      'Shortcuts',
                      style: TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w600,
                        color: Color(0xFF64748B),
                      ),
                    ),
                  ),

                  // 5. Referral Program Shortcut
                  MouseRegion(
                    cursor: SystemMouseCursors.click,
                    child: GestureDetector(
                      behavior: HitTestBehavior.opaque,
                      onTap: () {
                        showDialog(
                          context: context,
                          builder: (_) => const ReferralProgramDialog(),
                        );
                      },
                      child: Container(
                        height: 42,
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(
                          color: Colors.transparent,
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 28,
                              height: 28,
                              decoration: BoxDecoration(
                                color: const Color(0xFF176BFF).withOpacity(0.12),
                                borderRadius: BorderRadius.circular(8),
                              ),
                              child: const Icon(
                                Icons.card_giftcard_rounded,
                                size: 16,
                                color: Color(0xFF176BFF),
                              ),
                            ),
                            const SizedBox(width: 12),
                            const Text(
                              'Referral Program',
                              style: TextStyle(
                                fontSize: 13,
                                fontWeight: FontWeight.w500,
                                color: Color(0xFF1E293B),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),
                ],
              ),
            ),

            // Bottom Footer Actions (Admin Control & Log Out)
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
              decoration: const BoxDecoration(
                border: Border(top: BorderSide(color: Color(0xFFE2E8F0))),
              ),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  if (onOpenAdmin != null)
                    MouseRegion(
                      cursor: SystemMouseCursors.click,
                      child: GestureDetector(
                        behavior: HitTestBehavior.opaque,
                        onTap: onOpenAdmin,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 7),
                          child: Row(
                            children: const [
                              Icon(Icons.admin_panel_settings_outlined, size: 18, color: Color(0xFF7C3AED)),
                              SizedBox(width: 10),
                              Text(
                                'Admin Control Center',
                                style: TextStyle(
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFF7C3AED),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                  if (onLogout != null)
                    MouseRegion(
                      cursor: SystemMouseCursors.click,
                      child: GestureDetector(
                        behavior: HitTestBehavior.opaque,
                        onTap: onLogout,
                        child: Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 7),
                          child: Row(
                            children: const [
                              Icon(Icons.logout_rounded, size: 18, color: Color(0xFFEF4444)),
                              SizedBox(width: 10),
                              Text(
                                'Log Out',
                                style: TextStyle(
                                  fontSize: 12.5,
                                  fontWeight: FontWeight.w600,
                                  color: Color(0xFFEF4444),
                                ),
                              ),
                            ],
                          ),
                        ),
                      ),
                    ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildNavItem({
    required MemberSidebarItem item,
    required IconData icon,
    required String label,
  }) {
    final isActive = activeItem == item;

    return _SidebarItemRow(
      icon: icon,
      label: label,
      isActive: isActive,
      onTap: () => onSelectItem(item),
    );
  }
}

class _SidebarItemRow extends StatefulWidget {
  final IconData icon;
  final String label;
  final bool isActive;
  final VoidCallback onTap;

  const _SidebarItemRow({
    required this.icon,
    required this.label,
    required this.isActive,
    required this.onTap,
  });

  @override
  State<_SidebarItemRow> createState() => _SidebarItemRowState();
}

class _SidebarItemRowState extends State<_SidebarItemRow> {
  bool _isHovered = false;

  @override
  Widget build(BuildContext context) {
    Color bg = Colors.transparent;
    Color fg = const Color(0xFF2F394B);
    Color iconColor = const Color(0xFF4C5768);
    FontWeight weight = FontWeight.w500;

    if (widget.isActive) {
      bg = const Color(0xFFEBF3FF); // Exact soft blue from screenshot
      fg = const Color(0xFF176BFF); // Active text blue
      iconColor = const Color(0xFF176BFF);
      weight = FontWeight.w600;
    } else if (_isHovered) {
      bg = const Color(0xFFF5F8FC);
      fg = const Color(0xFF176BFF);
      iconColor = const Color(0xFF176BFF);
    }

    return MouseRegion(
      cursor: SystemMouseCursors.click,
      onEnter: (_) => setState(() => _isHovered = true),
      onExit: (_) => setState(() => _isHovered = false),
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: widget.onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 120),
          height: 41,
          margin: const EdgeInsets.only(bottom: 2),
          padding: const EdgeInsets.symmetric(horizontal: 11, vertical: 8),
          decoration: BoxDecoration(
            color: bg,
            borderRadius: BorderRadius.circular(13),
          ),
          child: Row(
            children: [
              Icon(widget.icon, size: 19, color: iconColor),
              const SizedBox(width: 14),
              Expanded(
                child: Text(
                  widget.label,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: weight,
                    color: fg,
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
