import 'dart:typed_data';
import 'package:flutter/material.dart';
import 'package:image_picker/image_picker.dart';
import 'package:provider/provider.dart';
import '../core/api_client.dart';
import '../core/session_manager.dart';
import '../providers/auth_provider.dart';

class ProfileImageAdjustDialog extends StatefulWidget {
  final XFile file;
  final String type; // 'avatar' or 'cover'
  final bool hasExistingPhoto;
  final VoidCallback? onRemove;

  const ProfileImageAdjustDialog({
    super.key,
    required this.file,
    this.type = 'avatar',
    this.hasExistingPhoto = false,
    this.onRemove,
  });

  static Future<bool?> show(
    BuildContext context, {
    required XFile file,
    String type = 'avatar',
    bool hasExistingPhoto = false,
    VoidCallback? onRemove,
  }) {
    return showDialog<bool>(
      context: context,
      barrierDismissible: false,
      builder: (ctx) => ProfileImageAdjustDialog(
        file: file,
        type: type,
        hasExistingPhoto: hasExistingPhoto,
        onRemove: onRemove,
      ),
    );
  }

  @override
  State<ProfileImageAdjustDialog> createState() => _ProfileImageAdjustDialogState();
}

class _ProfileImageAdjustDialogState extends State<ProfileImageAdjustDialog> {
  Uint8List? _imageBytes;
  double _zoom = 1.0;
  Offset _pan = Offset.zero;
  bool _isUploading = false;
  String? _errorMessage;

  bool get isAvatar => widget.type == 'avatar';

  @override
  void initState() {
    super.initState();
    _loadImage();
  }

  Future<void> _loadImage() async {
    try {
      final bytes = await widget.file.readAsBytes();
      if (mounted) {
        setState(() {
          _imageBytes = bytes;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _errorMessage = 'Failed to read image data: $e';
        });
      }
    }
  }

  void _resetPositionAndZoom() {
    if (_isUploading) return;
    setState(() {
      _zoom = 1.0;
      _pan = Offset.zero;
    });
  }

  Future<void> _handleSave() async {
    if (_imageBytes == null || _isUploading) return;

    setState(() {
      _isUploading = true;
      _errorMessage = null;
    });

    final endpoint = isAvatar ? '/profile/photo' : '/profile/cover';
    final fieldName = isAvatar ? 'profile_photo' : 'cover_photo';

    final res = await ApiClient.postMultipart(
      endpoint,
      fileBytes: _imageBytes,
      fileName: widget.file.name.isNotEmpty ? widget.file.name : (isAvatar ? 'avatar.jpg' : 'cover.jpg'),
      fileField: fieldName,
    );

    if (!mounted) return;
    setState(() => _isUploading = false);

    if (res.success) {
      if (res.data is Map && res.data['member'] != null) {
        await SessionManager.saveUserData(res.data['member'] as Map<String, dynamic>);
      }
      if (mounted) {
        await context.read<AuthProvider>().initAuth();
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Row(
              children: [
                const Icon(Icons.check_circle, color: Colors.white, size: 18),
                const SizedBox(width: 8),
                Text(isAvatar ? 'Profile photo updated!' : 'Cover photo updated!'),
              ],
            ),
            backgroundColor: const Color(0xFF16A34A),
            behavior: SnackBarBehavior.floating,
          ),
        );
        Navigator.pop(context, true);
      }
    } else {
      setState(() {
        _errorMessage = res.message ?? 'Failed to upload photo. Please try again.';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final modalTitle = isAvatar ? 'Adjust Profile Photo' : 'Adjust Cover Photo';
    final modalSubtitle = isAvatar
        ? 'Drag to reposition and zoom your photo to fit the circular profile avatar.'
        : 'Drag to reposition and zoom your photo to fit the header cover banner.';

    return Dialog(
      backgroundColor: Colors.transparent,
      insetPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 24),
      child: Center(
        child: ConstrainedBox(
          constraints: const BoxConstraints(maxWidth: 500),
          child: Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(20),
              boxShadow: [
                BoxShadow(
                  color: Colors.black.withOpacity(0.25),
                  blurRadius: 30,
                  offset: const Offset(0, 10),
                ),
              ],
            ),
            clipBehavior: Clip.antiAlias,
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                // 1. Modal Header
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                  child: Row(
                    children: [
                      // Camera Icon Badge
                      Container(
                        width: 40,
                        height: 40,
                        decoration: BoxDecoration(
                          color: const Color(0xFFEEF2FF),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(
                          Icons.camera_alt_outlined,
                          color: Color(0xFF4F46E5),
                          size: 20,
                        ),
                      ),
                      const SizedBox(width: 14),

                      // Title & Subhead
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            const Text(
                              'PREVIEW & CROP',
                              style: TextStyle(
                                fontSize: 11,
                                fontWeight: FontWeight.w700,
                                color: Color(0xFF4F46E5),
                                letterSpacing: 0.6,
                              ),
                            ),
                            const SizedBox(height: 2),
                            Text(
                              modalTitle,
                              style: const TextStyle(
                                fontSize: 18,
                                fontWeight: FontWeight.bold,
                                color: Color(0xFF0F172A),
                              ),
                            ),
                          ],
                        ),
                      ),

                      // Close (X) Button
                      IconButton(
                        onPressed: _isUploading ? null : () => Navigator.pop(context),
                        icon: const Icon(Icons.close, size: 20),
                        color: const Color(0xFF64748B),
                        style: IconButton.styleFrom(
                          backgroundColor: const Color(0xFFF1F5F9),
                          shape: const CircleBorder(),
                        ),
                      ),
                    ],
                  ),
                ),

                const Divider(height: 1, color: Color(0xFFF1F5F9)),

                // 2. Modal Body
                Flexible(
                  child: SingleChildScrollView(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        // Subtitle Instruction
                        Text(
                          modalSubtitle,
                          style: const TextStyle(
                            fontSize: 13,
                            color: Color(0xFF64748B),
                            height: 1.4,
                          ),
                        ),
                        const SizedBox(height: 16),

                        // Error Banner if present
                        if (_errorMessage != null)
                          Container(
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 10),
                            margin: const EdgeInsets.only(bottom: 14),
                            decoration: BoxDecoration(
                              color: const Color(0xFFFEE2E2),
                              borderRadius: BorderRadius.circular(10),
                              border: Border.all(color: const Color(0xFFFECACA)),
                            ),
                            child: Row(
                              children: [
                                const Icon(Icons.error_outline, color: Color(0xFFB91C1C), size: 17),
                                const SizedBox(width: 8),
                                Expanded(
                                  child: Text(
                                    _errorMessage!,
                                    style: const TextStyle(color: Color(0xFFB91C1C), fontSize: 12.5),
                                  ),
                                ),
                              ],
                            ),
                          ),

                        // Interactive Crop Viewport (Exact match to screenshot)
                        _buildInteractiveCropViewport(),

                        const SizedBox(height: 16),

                        // Zoom Controls Box (Exact match to screenshot)
                        _buildZoomControlsBox(),
                      ],
                    ),
                  ),
                ),

                const Divider(height: 1, color: Color(0xFFF1F5F9)),

                // 3. Modal Footer Actions (Exact match to screenshot)
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 16),
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      // Remove Photo Button
                      if (widget.hasExistingPhoto && widget.onRemove != null)
                        OutlinedButton.icon(
                          onPressed: _isUploading
                              ? null
                              : () {
                                  Navigator.pop(context);
                                  widget.onRemove!();
                                },
                          icon: const Icon(Icons.delete_outline, size: 16, color: Color(0xFFEF4444)),
                          label: Text(
                            isAvatar ? 'Remove Photo' : 'Remove Cover',
                            style: const TextStyle(color: Color(0xFFEF4444), fontSize: 13, fontWeight: FontWeight.w600),
                          ),
                          style: OutlinedButton.styleFrom(
                            backgroundColor: const Color(0xFFFEF2F2),
                            side: const BorderSide(color: Color(0xFFFEE2E2)),
                            shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 11),
                          ),
                        )
                      else
                        const SizedBox.shrink(),

                      // Right Action Buttons: Cancel & Save Photo
                      Row(
                        children: [
                          OutlinedButton(
                            onPressed: _isUploading ? null : () => Navigator.pop(context),
                            style: OutlinedButton.styleFrom(
                              foregroundColor: const Color(0xFF334155),
                              side: const BorderSide(color: Color(0xFFCBD5E1)),
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              padding: const EdgeInsets.symmetric(horizontal: 18, vertical: 11),
                            ),
                            child: const Text('Cancel', style: TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5)),
                          ),
                          const SizedBox(width: 10),
                          ElevatedButton.icon(
                            onPressed: (_imageBytes == null || _isUploading) ? null : _handleSave,
                            icon: _isUploading
                                ? const SizedBox(
                                    width: 14,
                                    height: 14,
                                    child: CircularProgressIndicator(color: Colors.white, strokeWidth: 2),
                                  )
                                : const Icon(Icons.check, size: 16),
                            label: Text(
                              _isUploading ? 'Saving...' : (isAvatar ? 'Save Photo' : 'Save Cover'),
                              style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 13.5),
                            ),
                            style: ElevatedButton.styleFrom(
                              backgroundColor: const Color(0xFF4F46E5),
                              foregroundColor: Colors.white,
                              elevation: 0,
                              shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(10)),
                              padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 11),
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
        ),
      ),
    );
  }

  // Interactive Viewport with dark background, glowing circular mask, 3x3 grid & drag hint
  Widget _buildInteractiveCropViewport() {
    return Container(
      width: double.infinity,
      height: 290,
      decoration: BoxDecoration(
        color: const Color(0xFF0F172A),
        borderRadius: BorderRadius.circular(14),
      ),
      clipBehavior: Clip.antiAlias,
      child: _imageBytes == null
          ? const Center(
              child: CircularProgressIndicator(color: Color(0xFF4F46E5)),
            )
          : Stack(
              alignment: Alignment.center,
              children: [
                // Draggable Image Inside Viewport
                GestureDetector(
                  onPanUpdate: (details) {
                    if (_isUploading) return;
                    setState(() {
                      _pan += details.delta;
                    });
                  },
                  child: Container(
                    color: Colors.transparent,
                    child: Center(
                      child: isAvatar
                          ? _buildAvatarMask()
                          : _buildCoverMask(),
                    ),
                  ),
                ),
              ],
            ),
    );
  }

  // Circular framing mask for Profile Avatar (Exact screenshot match)
  Widget _buildAvatarMask() {
    const frameSize = 220.0;

    return Stack(
      alignment: Alignment.center,
      children: [
        // Darkened surrounding mask backdrop
        Container(
          width: double.infinity,
          height: double.infinity,
          color: const Color(0xFF0B0F19),
        ),

        // Circular cutout image viewport
        Container(
          width: frameSize,
          height: frameSize,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            border: Border.all(color: const Color(0xFF6366F1), width: 3),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF4F46E5).withOpacity(0.4),
                blurRadius: 18,
                spreadRadius: 2,
              ),
            ],
          ),
          child: ClipOval(
            child: Stack(
              alignment: Alignment.center,
              children: [
                // Scaled and panned image
                Transform.translate(
                  offset: _pan,
                  child: Transform.scale(
                    scale: _zoom,
                    child: Image.memory(
                      _imageBytes!,
                      fit: BoxFit.cover,
                      width: frameSize,
                      height: frameSize,
                    ),
                  ),
                ),

                // 3x3 Grid Overlay (Screenshot framing guide)
                CustomPaint(
                  size: const Size(frameSize, frameSize),
                  painter: _CropGridPainter(isCircular: true),
                ),

                // Floating Drag Pill Hint at bottom of the circle
                Positioned(
                  bottom: 12,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0F172A).withOpacity(0.85),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: Colors.white12),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: const [
                        Icon(Icons.open_with, size: 12, color: Colors.white),
                        SizedBox(width: 5),
                        Text(
                          'Drag to reposition framing',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  // Rectangular framing mask for Cover Banner
  Widget _buildCoverMask() {
    const frameWidth = 360.0;
    const frameHeight = 112.5;

    return Stack(
      alignment: Alignment.center,
      children: [
        Container(
          width: double.infinity,
          height: double.infinity,
          color: const Color(0xFF0B0F19),
        ),
        Container(
          width: frameWidth,
          height: frameHeight,
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(12),
            border: Border.all(color: const Color(0xFF6366F1), width: 3),
            boxShadow: [
              BoxShadow(
                color: const Color(0xFF4F46E5).withOpacity(0.4),
                blurRadius: 18,
                spreadRadius: 2,
              ),
            ],
          ),
          child: ClipRRect(
            borderRadius: BorderRadius.circular(9),
            child: Stack(
              alignment: Alignment.center,
              children: [
                Transform.translate(
                  offset: _pan,
                  child: Transform.scale(
                    scale: _zoom,
                    child: Image.memory(
                      _imageBytes!,
                      fit: BoxFit.cover,
                      width: frameWidth,
                      height: frameHeight,
                    ),
                  ),
                ),
                CustomPaint(
                  size: const Size(frameWidth, frameHeight),
                  painter: _CropGridPainter(isCircular: false),
                ),
                Positioned(
                  bottom: 8,
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                    decoration: BoxDecoration(
                      color: const Color(0xFF0F172A).withOpacity(0.85),
                      borderRadius: BorderRadius.circular(14),
                      border: Border.all(color: Colors.white12),
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: const [
                        Icon(Icons.open_with, size: 12, color: Colors.white),
                        SizedBox(width: 5),
                        Text(
                          'Drag to reposition framing',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 11,
                            fontWeight: FontWeight.w600,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }

  // Zoom Level Controls Box with Slider & Reset (Exact match to screenshot)
  Widget _buildZoomControlsBox() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      decoration: BoxDecoration(
        color: const Color(0xFFF8FAFC),
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: const Color(0xFFE2E8F0)),
      ),
      child: Column(
        children: [
          // Row 1: Zoom Level, percentage & Reset Button
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
              Row(
                children: const [
                  Icon(Icons.zoom_in, color: Color(0xFF4F46E5), size: 18),
                  SizedBox(width: 6),
                  Text(
                    'Zoom Level',
                    style: TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF334155),
                    ),
                  ),
                ],
              ),
              Row(
                children: [
                  Text(
                    '${(_zoom * 100).toInt()}%',
                    style: const TextStyle(
                      fontSize: 13,
                      fontWeight: FontWeight.bold,
                      color: Color(0xFF64748B),
                    ),
                  ),
                  const SizedBox(width: 10),
                  OutlinedButton.icon(
                    onPressed: _resetPositionAndZoom,
                    icon: const Icon(Icons.refresh, size: 14),
                    label: const Text('Reset', style: TextStyle(fontSize: 12, fontWeight: FontWeight.w600)),
                    style: OutlinedButton.styleFrom(
                      foregroundColor: const Color(0xFF475569),
                      backgroundColor: Colors.white,
                      side: const BorderSide(color: Color(0xFFCBD5E1)),
                      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      minimumSize: const Size(0, 30),
                    ),
                  ),
                ],
              ),
            ],
          ),

          const SizedBox(height: 6),

          // Row 2: Zoom Out, Slider, Zoom In
          Row(
            children: [
              IconButton(
                icon: const Icon(Icons.zoom_out, size: 18),
                color: const Color(0xFF334155),
                onPressed: _zoom <= 1.0 || _isUploading
                    ? null
                    : () {
                        setState(() {
                          _zoom = (_zoom - 0.1).clamp(1.0, 3.5);
                        });
                      },
                style: IconButton.styleFrom(
                  backgroundColor: Colors.white,
                  side: const BorderSide(color: Color(0xFFCBD5E1)),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  padding: const EdgeInsets.all(6),
                  minimumSize: const Size(32, 32),
                ),
              ),
              Expanded(
                child: SliderTheme(
                  data: SliderTheme.of(context).copyWith(
                    trackHeight: 4,
                    activeTrackColor: const Color(0xFF4F46E5),
                    inactiveTrackColor: const Color(0xFFCBD5E1),
                    thumbColor: const Color(0xFF4F46E5),
                    thumbShape: const RoundSliderThumbShape(enabledThumbRadius: 7),
                    overlayShape: const RoundSliderOverlayShape(overlayRadius: 14),
                  ),
                  child: Slider(
                    value: _zoom,
                    min: 1.0,
                    max: 3.5,
                    onChanged: _isUploading
                        ? null
                        : (val) {
                            setState(() {
                              _zoom = val;
                            });
                          },
                  ),
                ),
              ),
              IconButton(
                icon: const Icon(Icons.zoom_in, size: 18),
                color: const Color(0xFF334155),
                onPressed: _zoom >= 3.5 || _isUploading
                    ? null
                    : () {
                        setState(() {
                          _zoom = (_zoom + 0.1).clamp(1.0, 3.5);
                        });
                      },
                style: IconButton.styleFrom(
                  backgroundColor: Colors.white,
                  side: const BorderSide(color: Color(0xFFCBD5E1)),
                  shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(8)),
                  padding: const EdgeInsets.all(6),
                  minimumSize: const Size(32, 32),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

// 3x3 Grid Framing Overlay
class _CropGridPainter extends CustomPainter {
  final bool isCircular;
  const _CropGridPainter({required this.isCircular});

  @override
  void paint(Canvas canvas, Size size) {
    final paint = Paint()
      ..color = Colors.white.withOpacity(0.2)
      ..strokeWidth = 1.0
      ..style = PaintingStyle.stroke;

    final stepX = size.width / 3;
    final stepY = size.height / 3;

    // Vertical lines
    canvas.drawLine(Offset(stepX, 0), Offset(stepX, size.height), paint);
    canvas.drawLine(Offset(stepX * 2, 0), Offset(stepX * 2, size.height), paint);

    // Horizontal lines
    canvas.drawLine(Offset(0, stepY), Offset(size.width, stepY), paint);
    canvas.drawLine(Offset(0, stepY * 2), Offset(size.width, stepY * 2), paint);
  }

  @override
  bool shouldRepaint(covariant CustomPainter oldDelegate) => false;
}
