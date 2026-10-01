import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../core/constants.dart';
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
  final VoidCallback? onLogout;
  final bool isDrawer;

  const MemberSidebarWidget({
    super.key,
    required this.activeItem,
    required this.onSelectItem,
    this.onLogout,
    this.isDrawer = false,
  });

  @override
  Widget build(BuildContext context) {
    final auth = context.watch<AuthProvider>();
    final member = auth.currentMember;

    final memberName = member != null && member.name.trim().isNotEmpty ? member.name.trim() : 'Member User';
    final initials = memberName.isNotEmpty
        ? memberName.split(' ').map((n) => n.isNotEmpty ? n[0] : '').take(2).join('').toUpperCase()
        : 'MB';
    final avatarUrl = AppConstants.resolveMediaUrl(member?.avatarUrl);
    final userId = member?.userId ?? '';

    return Container(
      width: 275,
      decoration: BoxDecoration(
        color: Colors.white,
        border: isDrawer ? null : const Border(right: BorderSide(color: Color(0xFFE2E8F0), width: 1.0)),
      ),
      child: SafeArea(
        child: Column(
          children: [
            // Scrollable Menu List
            Expanded(
              child: ListView(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 12),
                children: [
                  // 1. Sleek Modern Profile Header Card
                  Material(
                    color: Colors.transparent,
                    borderRadius: BorderRadius.circular(16),
                    child: InkWell(
                      onTap: () => onSelectItem(MemberSidebarItem.profile),
                      borderRadius: BorderRadius.circular(16),
                      child: Container(
                        padding: const EdgeInsets.all(12),
                        margin: const EdgeInsets.only(bottom: 12),
                        decoration: BoxDecoration(
                          gradient: const LinearGradient(
                            colors: [Color(0xFFF8FAFC), Color(0xFFF1F5F9)],
                            begin: Alignment.topLeft,
                            end: Alignment.bottomRight,
                          ),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: const Color(0xFFE2E8F0), width: 1.2),
                          boxShadow: [
                            BoxShadow(
                              color: Colors.black.withOpacity(0.02),
                              blurRadius: 8,
                              offset: const Offset(0, 2),
                            ),
                          ],
                        ),
                        child: Row(
                          children: [
                            // Avatar with online status dot
                            Stack(
                              children: [
                                ClipRRect(
                                  borderRadius: BorderRadius.circular(22),
                                  child: avatarUrl != null && avatarUrl.isNotEmpty
                                      ? Image.network(
                                          avatarUrl,
                                          width: 44,
                                          height: 44,
                                          fit: BoxFit.cover,
                                          errorBuilder: (ctx, err, stack) => _buildInitialsAvatar(initials),
                                        )
                                      : _buildInitialsAvatar(initials),
                                ),
                                Positioned(
                                  bottom: 0,
                                  right: 0,
                                  child: Container(
                                    width: 12,
                                    height: 12,
                                    decoration: BoxDecoration(
                                      color: const Color(0xFF10B981),
                                      shape: BoxShape.circle,
                                      border: Border.all(color: Colors.white, width: 2),
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(width: 12),

                            // Name & View Profile details
                            Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    memberName,
                                    maxLines: 1,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(
                                      fontWeight: FontWeight.w700,
                                      fontSize: 14,
                                      color: Color(0xFF0F172A),
                                      letterSpacing: -0.2,
                                    ),
                                  ),
                                  const SizedBox(height: 2),
                                  Row(
                                    children: [
                                      Text(
                                        userId.isNotEmpty ? '@$userId' : 'View profile',
                                        maxLines: 1,
                                        overflow: TextOverflow.ellipsis,
                                        style: const TextStyle(
                                          fontSize: 12,
                                          fontWeight: FontWeight.w500,
                                          color: Color(0xFF64748B),
                                        ),
                                      ),
                                    ],
                                  ),
                                ],
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.all(4),
                              decoration: BoxDecoration(
                                color: Colors.white.withOpacity(0.8),
                                shape: BoxShape.circle,
                              ),
                              child: const Icon(
                                Icons.chevron_right_rounded,
                                size: 18,
                                color: Color(0xFF94A3B8),
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  ),

                  // 2. Navigation Items
                  _buildNavItem(
                    item: MemberSidebarItem.home,
                    icon: Icons.home_outlined,
                    activeIcon: Icons.home_rounded,
                    label: 'Home',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.socials,
                    icon: Icons.auto_awesome_outlined,
                    activeIcon: Icons.auto_awesome,
                    label: 'Socials',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.profile,
                    icon: Icons.person_outline_rounded,
                    activeIcon: Icons.person_rounded,
                    label: 'My Profile',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.connections,
                    icon: Icons.people_outline_rounded,
                    activeIcon: Icons.people_rounded,
                    label: 'Connections',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.newConnections,
                    icon: Icons.person_add_alt_outlined,
                    activeIcon: Icons.person_add_rounded,
                    label: 'New Connections',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.savedPosts,
                    icon: Icons.bookmark_border_rounded,
                    activeIcon: Icons.bookmark_rounded,
                    label: 'Saved Posts',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.disconnections,
                    icon: Icons.person_off_outlined,
                    activeIcon: Icons.person_off_rounded,
                    label: 'Disconnections',
                  ),
                  // _buildNavItem(
                  //   item: MemberSidebarItem.watch,
                  //   icon: Icons.smart_display_outlined,
                  //   activeIcon: Icons.smart_display_rounded,
                  //   label: 'Watch',
                  // ),
                  _buildNavItem(
                    item: MemberSidebarItem.community,
                    icon: Icons.groups_outlined,
                    activeIcon: Icons.groups_rounded,
                    label: 'Community',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.businessPages,
                    icon: Icons.storefront_outlined,
                    activeIcon: Icons.storefront_rounded,
                    label: 'Business Pages',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.businessDirectory,
                    icon: Icons.explore_outlined,
                    activeIcon: Icons.explore_rounded,
                    label: 'Business Directory',
                  ),
                  // _buildNavItem(
                  //   item: MemberSidebarItem.events,
                  //   icon: Icons.event_outlined,
                  //   activeIcon: Icons.event_rounded,
                  //   label: 'Events',
                  // ),
                  _buildNavItem(
                    item: MemberSidebarItem.accountSettings,
                    icon: Icons.settings_outlined,
                    activeIcon: Icons.settings_rounded,
                    label: 'Account Settings',
                  ),
                  _buildNavItem(
                    item: MemberSidebarItem.feedback,
                    icon: Icons.chat_bubble_outline_rounded,
                    activeIcon: Icons.chat_bubble_rounded,
                    label: 'Feedback & Suggestions',
                  ),

                  // 3. Subtle Section Divider
                  const Padding(
                    padding: EdgeInsets.symmetric(horizontal: 6, vertical: 10),
                    child: Divider(color: Color(0xFFF1F5F9), height: 1, thickness: 1),
                  ),

                  // 4. Shortcuts Heading
                  const Padding(
                    padding: EdgeInsets.only(left: 10, top: 2, bottom: 8),
                    child: Text(
                      'SHORTCUTS',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w700,
                        letterSpacing: 0.9,
                        color: Color(0xFF94A3B8),
                      ),
                    ),
                  ),

                  // 5. Referral Program Card
                  Material(
                    color: Colors.transparent,
                    borderRadius: BorderRadius.circular(14),
                    child: InkWell(
                      onTap: () {
                        showDialog(
                          context: context,
                          builder: (_) => const ReferralProgramDialog(),
                        );
                      },
                      borderRadius: BorderRadius.circular(14),
                      child: Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 8),
                        decoration: BoxDecoration(
                          color: const Color(0xFFF8FAFC),
                          borderRadius: BorderRadius.circular(14),
                          border: Border.all(color: const Color(0xFFE2E8F0)),
                        ),
                        child: Row(
                          children: [
                            Container(
                              width: 32,
                              height: 32,
                              decoration: BoxDecoration(
                                gradient: const LinearGradient(
                                  colors: [Color(0xFF2563EB), Color(0xFF4F46E5)],
                                  begin: Alignment.topLeft,
                                  end: Alignment.bottomRight,
                                ),
                                borderRadius: BorderRadius.circular(10),
                                boxShadow: [
                                  BoxShadow(
                                    color: const Color(0xFF2563EB).withOpacity(0.25),
                                    blurRadius: 6,
                                    offset: const Offset(0, 2),
                                  ),
                                ],
                              ),
                              child: const Icon(
                                Icons.card_giftcard_rounded,
                                size: 17,
                                color: Colors.white,
                              ),
                            ),
                            const SizedBox(width: 12),
                            const Expanded(
                              child: Column(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  Text(
                                    'Referral Program',
                                    style: TextStyle(
                                      fontSize: 13,
                                      fontWeight: FontWeight.w600,
                                      color: Color(0xFF0F172A),
                                    ),
                                  ),
                                  Text(
                                    'Invite friends & earn',
                                    style: TextStyle(
                                      fontSize: 11,
                                      color: Color(0xFF64748B),
                                    ),
                                  ),
                                ],
                              ),
                            ),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                              decoration: BoxDecoration(
                                color: const Color(0xFFEFF6FF),
                                borderRadius: BorderRadius.circular(6),
                                border: Border.all(color: const Color(0xFFBFDBFE)),
                              ),
                              child: const Text(
                                'Earn',
                                style: TextStyle(
                                  fontSize: 10.5,
                                  fontWeight: FontWeight.bold,
                                  color: Color(0xFF2563EB),
                                ),
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

            // 6. Modern Stylish Bottom Logout Switch Card
            if (onLogout != null)
              Container(
                padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
                decoration: const BoxDecoration(
                  color: Colors.white,
                  border: Border(top: BorderSide(color: Color(0xFFF1F5F9), width: 1.5)),
                ),
                child: Material(
                  color: Colors.transparent,
                  child: InkWell(
                    onTap: onLogout,
                    borderRadius: BorderRadius.circular(14),
                    child: Container(
                      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                      decoration: BoxDecoration(
                        color: const Color(0xFFFEF2F2),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: const Color(0xFFFECACA), width: 1),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 34,
                            height: 34,
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEE2E2),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: const Icon(
                              Icons.power_settings_new_rounded,
                              size: 19,
                              color: Color(0xFFDC2626),
                            ),
                          ),
                          const SizedBox(width: 12),
                          const Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                Text(
                                  'Log Out',
                                  style: TextStyle(
                                    fontSize: 13.5,
                                    fontWeight: FontWeight.w700,
                                    color: Color(0xFFDC2626),
                                  ),
                                ),
                                Text(
                                  'End active session',
                                  style: TextStyle(
                                    fontSize: 11,
                                    fontWeight: FontWeight.normal,
                                    color: Color(0xFF991B1B),
                                  ),
                                ),
                              ],
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.all(4),
                            decoration: BoxDecoration(
                              color: Colors.white.withOpacity(0.9),
                              shape: BoxShape.circle,
                            ),
                            child: const Icon(
                              Icons.logout_rounded,
                              size: 15,
                              color: Color(0xFFDC2626),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildInitialsAvatar(String initials) {
    return Container(
      width: 44,
      height: 44,
      decoration: const BoxDecoration(
        shape: BoxShape.circle,
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
          fontSize: 15,
        ),
      ),
    );
  }

  Widget _buildNavItem({
    required MemberSidebarItem item,
    required IconData icon,
    required IconData activeIcon,
    required String label,
  }) {
    final isActive = activeItem == item;

    return _SidebarItemRow(
      icon: icon,
      activeIcon: activeIcon,
      label: label,
      isActive: isActive,
      onTap: () => onSelectItem(item),
    );
  }
}

class _SidebarItemRow extends StatefulWidget {
  final IconData icon;
  final IconData activeIcon;
  final String label;
  final bool isActive;
  final VoidCallback onTap;

  const _SidebarItemRow({
    required this.icon,
    required this.activeIcon,
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
    Color fg = const Color(0xFF334155);
    Color iconColor = const Color(0xFF64748B);
    FontWeight weight = FontWeight.w500;
    Border? border;

    if (widget.isActive) {
      bg = const Color(0xFFEFF6FF);
      fg = const Color(0xFF2563EB);
      iconColor = const Color(0xFF2563EB);
      weight = FontWeight.w700;
      border = Border.all(color: const Color(0xFFDBEAFE), width: 1.0);
    } else if (_isHovered) {
      bg = const Color(0xFFF8FAFC);
      fg = const Color(0xFF0F172A);
      iconColor = const Color(0xFF2563EB);
    }

    return MouseRegion(
      cursor: SystemMouseCursors.click,
      onEnter: (_) => setState(() => _isHovered = true),
      onExit: (_) => setState(() => _isHovered = false),
      child: GestureDetector(
        behavior: HitTestBehavior.opaque,
        onTap: widget.onTap,
        child: AnimatedContainer(
          duration: const Duration(milliseconds: 140),
          height: 42,
          margin: const EdgeInsets.only(bottom: 3),
          padding: const EdgeInsets.symmetric(horizontal: 12),
          decoration: BoxDecoration(
            color: bg,
            borderRadius: BorderRadius.circular(12),
            border: border,
          ),
          child: Row(
            children: [
              if (widget.isActive)
                Container(
                  width: 3.5,
                  height: 16,
                  margin: const EdgeInsets.only(right: 9),
                  decoration: BoxDecoration(
                    color: const Color(0xFF2563EB),
                    borderRadius: BorderRadius.circular(4),
                  ),
                ),
              Icon(
                widget.isActive ? widget.activeIcon : widget.icon,
                size: 20,
                color: iconColor,
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  widget.label,
                  style: TextStyle(
                    fontSize: 13.5,
                    fontWeight: weight,
                    color: fg,
                    letterSpacing: -0.1,
                  ),
                ),
              ),
              if (widget.isActive)
                Container(
                  width: 6,
                  height: 6,
                  decoration: const BoxDecoration(
                    color: Color(0xFF2563EB),
                    shape: BoxShape.circle,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}

