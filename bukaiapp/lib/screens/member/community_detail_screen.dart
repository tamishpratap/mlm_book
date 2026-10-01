import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../core/constants.dart';
import '../../models/community_model.dart';
import '../../providers/community_provider.dart';

class CommunityDetailScreen extends StatefulWidget {
  final CommunityModel community;

  const CommunityDetailScreen({super.key, required this.community});

  @override
  State<CommunityDetailScreen> createState() => _CommunityDetailScreenState();
}

class _CommunityDetailScreenState extends State<CommunityDetailScreen> {
  @override
  Widget build(BuildContext context) {
    final community = widget.community;

    final coverUrl = AppConstants.resolveMediaUrl(community.bannerUrl);
    final logoUrl = AppConstants.resolveMediaUrl(community.avatarUrl);
    final initials = community.name.isNotEmpty ? community.name.trim().substring(0, 1).toUpperCase() : 'C';

    return Scaffold(
      backgroundColor: const Color(0xFFF8FAFC),
      appBar: AppBar(
        backgroundColor: Colors.white,
        elevation: 0,
        surfaceTintColor: Colors.transparent,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF0F172A), size: 24),
          onPressed: () => Navigator.pop(context),
        ),
        title: Text(
          community.name,
          style: const TextStyle(
            color: Color(0xFF0F172A),
            fontWeight: FontWeight.w700,
            fontSize: 18,
          ),
        ),
      ),
      body: SingleChildScrollView(
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            // 1. Cover Photo & Avatar Header
            Stack(
              clipBehavior: Clip.none,
              children: [
                Container(
                  height: 150,
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
                Positioned(
                  bottom: -30,
                  left: 20,
                  child: Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: Colors.white,
                      border: Border.all(color: Colors.white, width: 3.5),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.12),
                          blurRadius: 8,
                          offset: const Offset(0, 3),
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

            const SizedBox(height: 38),

            // 2. Info Header
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    community.name,
                    style: const TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w800,
                      color: Color(0xFF0F172A),
                    ),
                  ),
                  const SizedBox(height: 4),
                  Row(
                    children: [
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 3.5),
                        decoration: BoxDecoration(
                          color: const Color(0xFFEFF6FF),
                          borderRadius: BorderRadius.circular(16),
                          border: Border.all(color: const Color(0xFFDBEAFE)),
                        ),
                        child: Text(
                          community.category,
                          style: const TextStyle(
                            color: Color(0xFF2563EB),
                            fontSize: 12,
                            fontWeight: FontWeight.w700,
                          ),
                        ),
                      ),
                      const SizedBox(width: 8),
                      Text(
                        '•  ${community.membersCount} ${community.membersCount == 1 ? 'member' : 'members'}  •  ${community.visibility.toUpperCase()}',
                        style: const TextStyle(color: Color(0xFF64748B), fontSize: 13, fontWeight: FontWeight.w500),
                      ),
                    ],
                  ),
                  const SizedBox(height: 6),
                  if (community.ownerName != null)
                    Text(
                      'Created by ${community.ownerName}',
                      style: const TextStyle(color: Color(0xFF94A3B8), fontSize: 12.5),
                    ),

                  const SizedBox(height: 18),

                  // Join / Leave Button
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton(
                      onPressed: () async {
                        await context.read<CommunityProvider>().toggleJoin(community);
                        setState(() {});
                      },
                      style: ElevatedButton.styleFrom(
                        backgroundColor: community.isMember
                            ? const Color(0xFFF1F5F9)
                            : const Color(0xFF2563EB),
                        foregroundColor: community.isMember
                            ? const Color(0xFF475569)
                            : Colors.white,
                        elevation: 0,
                        padding: const EdgeInsets.symmetric(vertical: 13),
                        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                      child: Text(
                        community.isMember ? 'Leave Group' : '+ Join Group',
                        style: const TextStyle(fontWeight: FontWeight.w700, fontSize: 14),
                      ),
                    ),
                  ),

                  const SizedBox(height: 20),

                  // 3. About Section
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'About this Community',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF0F172A),
                          ),
                        ),
                        const SizedBox(height: 8),
                        Text(
                          community.description != null && community.description!.isNotEmpty
                              ? community.description!
                              : 'Welcome to the official ${community.name} community. Connect with like-minded members, share ideas, and grow together.',
                          style: const TextStyle(fontSize: 13.5, color: Color(0xFF475569), height: 1.45),
                        ),
                      ],
                    ),
                  ),

                  const SizedBox(height: 16),

                  // 4. Guidelines Section
                  Container(
                    width: double.infinity,
                    padding: const EdgeInsets.all(18),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      border: Border.all(color: const Color(0xFFE2E8F0)),
                    ),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        const Text(
                          'Community Guidelines',
                          style: TextStyle(
                            fontSize: 16,
                            fontWeight: FontWeight.w700,
                            color: Color(0xFF0F172A),
                          ),
                        ),
                        const SizedBox(height: 12),
                        if (community.rules != null && community.rules!.isNotEmpty)
                          Text(
                            community.rules!,
                            style: const TextStyle(fontSize: 13.5, color: Color(0xFF475569), height: 1.45),
                          )
                        else ...[
                          _buildGuidelineRow('1', 'Be respectful and courteous to all community members.'),
                          _buildGuidelineRow('2', 'No spam, malicious links, or unauthorized promotions.'),
                          _buildGuidelineRow('3', 'Share relevant insights and participate actively.'),
                        ],
                      ],
                    ),
                  ),

                  const SizedBox(height: 28),
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
            fontSize: 26,
          ),
        ),
      ),
    );
  }

  Widget _buildGuidelineRow(String num, String text) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8.0),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 10,
            backgroundColor: const Color(0xFFEFF6FF),
            child: Text(
              num,
              style: const TextStyle(fontSize: 10.5, color: Color(0xFF2563EB), fontWeight: FontWeight.w700),
            ),
          ),
          const SizedBox(width: 10),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(fontSize: 13, color: Color(0xFF64748B), height: 1.35),
            ),
          ),
        ],
      ),
    );
  }
}
