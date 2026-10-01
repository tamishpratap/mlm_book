import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import 'package:share_plus/share_plus.dart';
import 'package:url_launcher/url_launcher.dart';
import '../core/app_toast.dart';
import '../providers/auth_provider.dart';

class ReferralProgramDialog extends StatelessWidget {
  const ReferralProgramDialog({super.key});

  static Future<void> show(BuildContext context) {
    return showDialog(
      context: context,
      barrierDismissible: true,
      builder: (ctx) => const ReferralProgramDialog(),
    );
  }

  Future<void> _launchShareUrl(
    BuildContext context, {
    required String primaryUrl,
    String? fallbackUrl,
  }) async {
    try {
      final primaryUri = Uri.parse(primaryUrl);
      if (await canLaunchUrl(primaryUri)) {
        await launchUrl(primaryUri, mode: LaunchMode.externalApplication);
        return;
      }
      if (fallbackUrl != null) {
        final fallbackUri = Uri.parse(fallbackUrl);
        if (await canLaunchUrl(fallbackUri)) {
          await launchUrl(fallbackUri, mode: LaunchMode.externalApplication);
          return;
        }
      }
      if (context.mounted) {
        AppToast.error(context, 'App not installed. Opening standard share.');
      }
    } catch (_) {
      if (fallbackUrl != null) {
        try {
          final fallbackUri = Uri.parse(fallbackUrl);
          await launchUrl(fallbackUri, mode: LaunchMode.externalApplication);
          return;
        } catch (_) {}
      }
      if (context.mounted) {
        AppToast.error(context, 'Could not open app for sharing.');
      }
    }
  }

  void _shareViaNative(BuildContext context, String referralLink, String referralCode) {
    Share.share(
      'Join me on MLM Book! Use my referral code $referralCode or register directly here: $referralLink',
      subject: 'MLM Book Invitation',
    );
  }

  @override
  Widget build(BuildContext context) {
    final member = context.watch<AuthProvider>().currentMember;
    final referralCode = member?.userId ?? 'MEMBER';
    final referralId = '@$referralCode';
    final referralLink = 'https://mlmbookai.com/member/register?ref=$referralCode';
    final shareMessage = 'Join me on MLM Book! Register using my referral link: $referralLink';

    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      child: Container(
        width: 520,
        constraints: const BoxConstraints(maxHeight: 780),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(24),
          boxShadow: [
            BoxShadow(
              color: Colors.black.withOpacity(0.18),
              blurRadius: 32,
              offset: const Offset(0, 12),
            ),
          ],
        ),
        clipBehavior: Clip.antiAlias,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            // 1. Header (Gift Icon, Subtitle, Title, Close)
            Container(
              padding: const EdgeInsets.fromLTRB(20, 20, 16, 16),
              decoration: const BoxDecoration(
                color: Color(0xFFF8FAFC),
                border: Border(bottom: BorderSide(color: Color(0xFFE2E8F0))),
              ),
              child: Row(
                children: [
                  // Blue Gradient Gift Squircle
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      gradient: const LinearGradient(
                        colors: [Color(0xFF3B82F6), Color(0xFF2563EB)],
                        begin: Alignment.topLeft,
                        end: Alignment.bottomRight,
                      ),
                      borderRadius: BorderRadius.circular(14),
                      boxShadow: [
                        BoxShadow(
                          color: const Color(0xFF2563EB).withOpacity(0.3),
                          blurRadius: 10,
                          offset: const Offset(0, 3),
                        ),
                      ],
                    ),
                    child: const Icon(Icons.card_giftcard_rounded, color: Colors.white, size: 23),
                  ),
                  const SizedBox(width: 14),

                  // Title and Eyebrow Text
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: const [
                        Text(
                          'MLM REFERRAL PROGRAM',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF2563EB),
                            letterSpacing: 0.8,
                          ),
                        ),
                        SizedBox(height: 2),
                        Text(
                          'Your Referral Details',
                          style: TextStyle(
                            fontSize: 18,
                            fontWeight: FontWeight.bold,
                            color: Color(0xFF0F172A),
                            letterSpacing: -0.2,
                          ),
                        ),
                      ],
                    ),
                  ),

                  // Close Icon Button
                  IconButton(
                    icon: Container(
                      padding: const EdgeInsets.all(4),
                      decoration: const BoxDecoration(
                        color: Colors.white,
                        shape: BoxShape.circle,
                      ),
                      child: const Icon(Icons.close_rounded, color: Color(0xFF64748B), size: 20),
                    ),
                    onPressed: () => Navigator.of(context).pop(),
                  ),
                ],
              ),
            ),

            // 2. Scrollable Body
            Flexible(
              child: SingleChildScrollView(
                padding: const EdgeInsets.fromLTRB(20, 18, 20, 22),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    // Status Card: Mobile Verified Member & Eligible to Refer
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                      decoration: BoxDecoration(
                        color: const Color(0xFFECFDF5),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: const Color(0xFFA7F3D0)),
                      ),
                      child: Row(
                        children: [
                          const Icon(Icons.verified_user_outlined, color: Color(0xFF059669), size: 20),
                          const SizedBox(width: 10),
                          const Expanded(
                            child: Text(
                              'Mobile Verified Member',
                              style: TextStyle(
                                color: Color(0xFF065F46),
                                fontWeight: FontWeight.bold,
                                fontSize: 13.5,
                              ),
                            ),
                          ),
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                            decoration: BoxDecoration(
                              color: const Color(0xFFD1FAE5),
                              borderRadius: BorderRadius.circular(20),
                            ),
                            child: const Text(
                              'Eligible to Refer',
                              style: TextStyle(
                                color: Color(0xFF047857),
                                fontSize: 11.5,
                                fontWeight: FontWeight.bold,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 18),

                    // Section 1: YOUR REFERRAL ID
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Row(
                        children: [
                          Container(
                            width: 38,
                            height: 38,
                            decoration: BoxDecoration(
                              color: const Color(0xFFEFF6FF),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: const Color(0xFFBFDBFE)),
                            ),
                            child: const Center(
                              child: Icon(Icons.badge_outlined, color: Color(0xFF2563EB), size: 20),
                            ),
                          ),
                          const SizedBox(width: 12),
                          Expanded(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                const Text(
                                  'YOUR REFERRAL ID',
                                  style: TextStyle(
                                    fontSize: 10.5,
                                    fontWeight: FontWeight.w800,
                                    color: Color(0xFF64748B),
                                    letterSpacing: 0.5,
                                  ),
                                ),
                                const SizedBox(height: 2),
                                Text(
                                  referralId,
                                  style: const TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.bold,
                                    color: Color(0xFF0F172A),
                                    letterSpacing: 0.3,
                                  ),
                                ),
                              ],
                            ),
                          ),
                          ElevatedButton.icon(
                            onPressed: () async {
                              await Clipboard.setData(ClipboardData(text: referralId));
                              if (context.mounted) {
                                AppToast.success(context, 'Referral ID copied to clipboard!');
                              }
                            },
                            icon: const Icon(Icons.copy_rounded, size: 15),
                            label: const Text('Copy ID'),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF3B82F6),
                              foregroundColor: Colors.white,
                              elevation: 0,
                              padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 9),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              textStyle: const TextStyle(fontWeight: FontWeight.bold, fontSize: 12.5),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 14),

                    // Section 2: YOUR REFERRAL LINK
                    Container(
                      padding: const EdgeInsets.all(14),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(16),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Row(
                            children: [
                              Container(
                                width: 28,
                                height: 28,
                                decoration: BoxDecoration(
                                  color: const Color(0xFFEFF6FF),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: const Icon(Icons.link_rounded, color: Color(0xFF2563EB), size: 17),
                              ),
                              const SizedBox(width: 8),
                              const Text(
                                'YOUR REFERRAL LINK',
                                style: TextStyle(
                                  fontSize: 11,
                                  fontWeight: FontWeight.w800,
                                  color: Color(0xFF64748B),
                                  letterSpacing: 0.5,
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 10),
                          InkWell(
                            onTap: () async {
                              await Clipboard.setData(ClipboardData(text: referralLink));
                              if (context.mounted) {
                                AppToast.success(context, 'Referral link copied to clipboard!');
                              }
                            },
                            borderRadius: BorderRadius.circular(12),
                            child: Container(
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                border: Border.all(color: const Color(0xFFCBD5E1)),
                              ),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Text(
                                      referralLink,
                                      maxLines: 1,
                                      overflow: TextOverflow.ellipsis,
                                      style: const TextStyle(
                                        fontSize: 12.5,
                                        color: Color(0xFF334155),
                                        fontFamily: 'monospace',
                                        fontWeight: FontWeight.w500,
                                      ),
                                    ),
                                  ),
                                  const SizedBox(width: 8),
                                  Container(
                                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                                    decoration: BoxDecoration(
                                      color: const Color(0xFF2563EB),
                                      borderRadius: BorderRadius.circular(8),
                                      boxShadow: [
                                        BoxShadow(
                                          color: const Color(0xFF2563EB).withOpacity(0.25),
                                          blurRadius: 6,
                                          offset: const Offset(0, 2),
                                        ),
                                      ],
                                    ),
                                    child: Row(
                                      mainAxisSize: MainAxisSize.min,
                                      children: const [
                                        Icon(Icons.copy_rounded, color: Colors.white, size: 14),
                                        SizedBox(width: 5),
                                        Text(
                                          'Copy Link',
                                          style: TextStyle(
                                            color: Colors.white,
                                            fontWeight: FontWeight.bold,
                                            fontSize: 12,
                                          ),
                                        ),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    const SizedBox(height: 20),

                    // Section 3: SHARE LINK VIA (App Icon Grid)
                    Row(
                      mainAxisAlignment: MainAxisAlignment.spaceBetween,
                      children: [
                        const Text(
                          'SHARE TO APPS',
                          style: TextStyle(
                            fontSize: 11,
                            fontWeight: FontWeight.w800,
                            color: Color(0xFF64748B),
                            letterSpacing: 0.6,
                          ),
                        ),
                        Text(
                          'Tap icon to share',
                          style: TextStyle(
                            fontSize: 11,
                            color: Colors.grey.shade500,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 12),

                    // Grid of App Icons (4 columns x 3 rows)
                    GridView.count(
                      crossAxisCount: 4,
                      shrinkWrap: true,
                      physics: const NeverScrollableScrollPhysics(),
                      mainAxisSpacing: 14,
                      crossAxisSpacing: 10,
                      childAspectRatio: 0.88,
                      children: [
                        // 1. WhatsApp
                        _buildAppTile(
                          context: context,
                          label: 'WhatsApp',
                          badge: _buildWhatsAppBadge(),
                          onTap: () {
                            final text = Uri.encodeComponent(shareMessage);
                            _launchShareUrl(
                              context,
                              primaryUrl: 'whatsapp://send?text=$text',
                              fallbackUrl: 'https://api.whatsapp.com/send?text=$text',
                            );
                          },
                        ),

                        // 2. Telegram
                        _buildAppTile(
                          context: context,
                          label: 'Telegram',
                          badge: _buildTelegramBadge(),
                          onTap: () {
                            final url = Uri.encodeComponent(referralLink);
                            final text = Uri.encodeComponent('Join me on MLM Book!');
                            _launchShareUrl(
                              context,
                              primaryUrl: 'tg://msg_url?url=$url&text=$text',
                              fallbackUrl: 'https://t.me/share/url?url=$url&text=$text',
                            );
                          },
                        ),

                        // 3. Instagram
                        _buildAppTile(
                          context: context,
                          label: 'Instagram',
                          badge: _buildInstagramBadge(),
                          onTap: () async {
                            await Clipboard.setData(ClipboardData(text: referralLink));
                            if (context.mounted) {
                              AppToast.success(context, 'Link copied! Opening Instagram...');
                            }
                            if (context.mounted) {
                              _launchShareUrl(
                                context,
                                primaryUrl: 'instagram://app',
                                fallbackUrl: 'https://www.instagram.com',
                              );
                            }
                          },
                        ),

                        // 4. Facebook
                        _buildAppTile(
                          context: context,
                          label: 'Facebook',
                          badge: _buildFacebookBadge(),
                          onTap: () {
                            final url = Uri.encodeComponent(referralLink);
                            final quote = Uri.encodeComponent('Join me on MLM Book!');
                            _launchShareUrl(
                              context,
                              primaryUrl: 'fb://facewebmodal/f?href=https://www.facebook.com/sharer/sharer.php?u=$url',
                              fallbackUrl: 'https://www.facebook.com/sharer/sharer.php?u=$url&quote=$quote',
                            );
                          },
                        ),

                        // 5. X (Twitter)
                        _buildAppTile(
                          context: context,
                          label: 'X (Twitter)',
                          badge: _buildXBadge(),
                          onTap: () {
                            final url = Uri.encodeComponent(referralLink);
                            final text = Uri.encodeComponent('Join me on MLM Book! #MLMBook ');
                            _launchShareUrl(
                              context,
                              primaryUrl: 'twitter://post?message=$text$url',
                              fallbackUrl: 'https://twitter.com/intent/tweet?url=$url&text=$text',
                            );
                          },
                        ),

                        // 6. LinkedIn
                        _buildAppTile(
                          context: context,
                          label: 'LinkedIn',
                          badge: _buildLinkedInBadge(),
                          onTap: () {
                            final url = Uri.encodeComponent(referralLink);
                            _launchShareUrl(
                              context,
                              primaryUrl: 'linkedin://shareArticle?mini=true&url=$url',
                              fallbackUrl: 'https://www.linkedin.com/sharing/share-offsite/?url=$url',
                            );
                          },
                        ),

                        // 7. Reddit
                        _buildAppTile(
                          context: context,
                          label: 'Reddit',
                          badge: _buildRedditBadge(),
                          onTap: () {
                            final url = Uri.encodeComponent(referralLink);
                            final title = Uri.encodeComponent('Join MLM Book');
                            _launchShareUrl(
                              context,
                              primaryUrl: 'https://www.reddit.com/submit?url=$url&title=$title',
                            );
                          },
                        ),

                        // 8. Gmail / Email
                        _buildAppTile(
                          context: context,
                          label: 'Gmail',
                          badge: _buildGmailBadge(),
                          onTap: () {
                            final subject = Uri.encodeComponent('Invitation to join MLM Book');
                            final body = Uri.encodeComponent(
                              'Hello,\n\nI invite you to join MLM Book. Click the link below to get started:\n$referralLink\n\nReferral ID: $referralId\n\nBest regards!',
                            );
                            _launchShareUrl(
                              context,
                              primaryUrl: 'mailto:?subject=$subject&body=$body',
                            );
                          },
                        ),

                        // 9. Messages (SMS)
                        _buildAppTile(
                          context: context,
                          label: 'Messages',
                          badge: _buildMessagesBadge(),
                          onTap: () {
                            final text = Uri.encodeComponent(shareMessage);
                            _launchShareUrl(
                              context,
                              primaryUrl: 'sms:?body=$text',
                            );
                          },
                        ),

                        // 10. Snapchat
                        _buildAppTile(
                          context: context,
                          label: 'Snapchat',
                          badge: _buildSnapchatBadge(),
                          onTap: () async {
                            await Clipboard.setData(ClipboardData(text: referralLink));
                            if (context.mounted) {
                              AppToast.success(context, 'Link copied! Opening Snapchat...');
                            }
                            if (context.mounted) {
                              _launchShareUrl(
                                context,
                                primaryUrl: 'snapchat://',
                                fallbackUrl: 'https://www.snapchat.com',
                              );
                            }
                          },
                        ),

                        // 11. Copy Link
                        _buildAppTile(
                          context: context,
                          label: 'Copy Link',
                          badge: _buildCopyLinkBadge(),
                          onTap: () async {
                            await Clipboard.setData(ClipboardData(text: referralLink));
                            if (context.mounted) {
                              AppToast.success(context, 'Referral link copied to clipboard!');
                            }
                          },
                        ),

                        // 12. More / System Share
                        _buildAppTile(
                          context: context,
                          label: 'More Apps',
                          badge: _buildMoreAppsBadge(),
                          onTap: () => _shareViaNative(context, referralLink, referralCode),
                        ),
                      ],
                    ),
                    const SizedBox(height: 20),

                    // Section 4: How referral tracking works
                    Container(
                      padding: const EdgeInsets.all(15),
                      decoration: BoxDecoration(
                        color: const Color(0xFFF8FAFC),
                        borderRadius: BorderRadius.circular(14),
                        border: Border.all(color: const Color(0xFFE2E8F0)),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: const [
                          Text(
                            'How referral tracking works:',
                            style: TextStyle(
                              fontSize: 13,
                              fontWeight: FontWeight.bold,
                              color: Color(0xFF1E293B),
                            ),
                          ),
                          SizedBox(height: 6),
                          Text(
                            'When someone creates an account using your link, you are saved as their introducer. Once they complete mobile verification, your direct referral count increments automatically.',
                            style: TextStyle(
                              fontSize: 12.5,
                              color: Color(0xFF64748B),
                              height: 1.45,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }

  // -------------------------------------------------------------
  // App Tile Component (Android Share Sheet Item)
  // -------------------------------------------------------------

  Widget _buildAppTile({
    required BuildContext context,
    required String label,
    required Widget badge,
    required VoidCallback onTap,
  }) {
    return Material(
      color: Colors.transparent,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(16),
        splashColor: const Color(0xFF2563EB).withOpacity(0.12),
        highlightColor: const Color(0xFF2563EB).withOpacity(0.06),
        child: Padding(
          padding: const EdgeInsets.symmetric(vertical: 4, horizontal: 2),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              badge,
              const SizedBox(height: 6),
              Text(
                label,
                style: const TextStyle(
                  fontSize: 11,
                  fontWeight: FontWeight.w600,
                  color: Color(0xFF334155),
                  letterSpacing: -0.2,
                ),
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                textAlign: TextAlign.center,
              ),
            ],
          ),
        ),
      ),
    );
  }

  // -------------------------------------------------------------
  // Custom Authentic App Brand Badges (48x48 Squircles)
  // -------------------------------------------------------------

  Widget _buildWhatsAppBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        color: const Color(0xFF25D366),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF25D366).withOpacity(0.32),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Icon(Icons.chat_bubble_rounded, color: Colors.white, size: 24),
      ),
    );
  }

  Widget _buildTelegramBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF2AABEE), Color(0xFF229ED9)],
          begin: Alignment.topCenter,
          end: Alignment.bottomCenter,
        ),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF229ED9).withOpacity(0.32),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Icon(Icons.send_rounded, color: Colors.white, size: 22),
      ),
    );
  }

  Widget _buildInstagramBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [
            Color(0xFF833AB4),
            Color(0xFFFD1D1D),
            Color(0xFFF77737),
            Color(0xFFFFDC80),
          ],
          begin: Alignment.bottomLeft,
          end: Alignment.topRight,
        ),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFFD1D1D).withOpacity(0.30),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Icon(Icons.camera_alt_rounded, color: Colors.white, size: 23),
      ),
    );
  }

  Widget _buildFacebookBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        color: const Color(0xFF1877F2),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF1877F2).withOpacity(0.32),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Text(
          'f',
          style: TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w900,
            fontSize: 27,
            height: 1.05,
            fontFamily: 'sans-serif',
          ),
        ),
      ),
    );
  }

  Widget _buildXBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.28),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Text(
          '𝕏',
          style: TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.bold,
            fontSize: 22,
            height: 1.0,
          ),
        ),
      ),
    );
  }

  Widget _buildLinkedInBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        color: const Color(0xFF0A66C2),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0A66C2).withOpacity(0.32),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Text(
          'in',
          style: TextStyle(
            color: Colors.white,
            fontWeight: FontWeight.w900,
            fontSize: 22,
            height: 1.05,
          ),
        ),
      ),
    );
  }

  Widget _buildRedditBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        color: const Color(0xFFFF4500),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFFF4500).withOpacity(0.32),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Icon(Icons.forum_rounded, color: Colors.white, size: 22),
      ),
    );
  }

  Widget _buildGmailBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        color: const Color(0xFFEA4335),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFFEA4335).withOpacity(0.30),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Icon(Icons.mail_rounded, color: Colors.white, size: 23),
      ),
    );
  }

  Widget _buildMessagesBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF0D9488), Color(0xFF059669)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF0D9488).withOpacity(0.30),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Icon(Icons.sms_rounded, color: Colors.white, size: 22),
      ),
    );
  }

  Widget _buildSnapchatBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        color: const Color(0xFFFFFC00),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: Colors.amber.withOpacity(0.35),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Icon(Icons.flash_on_rounded, color: Colors.black, size: 24),
      ),
    );
  }

  Widget _buildCopyLinkBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        color: const Color(0xFF6366F1),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF6366F1).withOpacity(0.30),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Icon(Icons.link_rounded, color: Colors.white, size: 24),
      ),
    );
  }

  Widget _buildMoreAppsBadge() {
    return Container(
      width: 46,
      height: 46,
      decoration: BoxDecoration(
        gradient: const LinearGradient(
          colors: [Color(0xFF8B5CF6), Color(0xFF6D28D9)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: BorderRadius.circular(14),
        boxShadow: [
          BoxShadow(
            color: const Color(0xFF7C3AED).withOpacity(0.30),
            blurRadius: 8,
            offset: const Offset(0, 3),
          ),
        ],
      ),
      child: const Center(
        child: Icon(Icons.share_rounded, color: Colors.white, size: 22),
      ),
    );
  }
}

