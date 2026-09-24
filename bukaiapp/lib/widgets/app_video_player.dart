// ignore_for_file: avoid_web_libraries_in_flutter
import 'dart:html' as html;
import 'dart:ui_web' as ui_web;
import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

class AppVideoPlayer extends StatefulWidget {
  final String videoUrl;
  final bool autoPlay;
  final bool loop;
  final bool isMuted;

  const AppVideoPlayer({
    super.key,
    required this.videoUrl,
    this.autoPlay = true,
    this.loop = true,
    this.isMuted = false,
  });

  @override
  State<AppVideoPlayer> createState() => _AppVideoPlayerState();
}

class _AppVideoPlayerState extends State<AppVideoPlayer> {
  late String _viewId;
  html.VideoElement? _videoElement;

  @override
  void initState() {
    super.initState();
    _viewId = 'video_${widget.videoUrl.hashCode}_${DateTime.now().millisecondsSinceEpoch}';

    if (kIsWeb) {
      _videoElement = html.VideoElement()
        ..src = widget.videoUrl
        ..autoplay = widget.autoPlay
        ..loop = widget.loop
        ..muted = widget.isMuted
        ..controls = true
        ..setAttribute('playsinline', 'true')
        ..setAttribute('webkit-playsinline', 'true')
        ..style.border = 'none'
        ..style.height = '100%'
        ..style.width = '100%'
        ..style.objectFit = 'contain'
        ..style.backgroundColor = 'black';

      ui_web.platformViewRegistry.registerViewFactory(
        _viewId,
        (int viewId) => _videoElement!,
      );

      if (widget.autoPlay) {
        _videoElement!.play().catchError((_) {
          _videoElement!.muted = true;
          _videoElement!.play().catchError((_) {});
        });
      }
    }
  }

  @override
  void didUpdateWidget(covariant AppVideoPlayer oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (kIsWeb && _videoElement != null) {
      if (oldWidget.isMuted != widget.isMuted) {
        _videoElement!.muted = widget.isMuted;
        if (!widget.isMuted) {
          _videoElement!.volume = 1.0;
          _videoElement!.play().catchError((_) {});
        } else {
          _videoElement!.volume = 0.0;
        }
      }
      if (oldWidget.videoUrl != widget.videoUrl) {
        _videoElement!.src = widget.videoUrl;
        if (widget.autoPlay) {
          _videoElement!.play().catchError((_) {
            _videoElement!.muted = true;
            _videoElement!.play().catchError((_) {});
          });
        }
      }
    }
  }

  @override
  void dispose() {
    if (kIsWeb && _videoElement != null) {
      _videoElement!.pause();
      _videoElement!.src = '';
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    if (!kIsWeb) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Icon(Icons.play_circle_fill, size: 56, color: Colors.white),
            const SizedBox(height: 8),
            Text(
              widget.videoUrl.split('/').last,
              style: const TextStyle(color: Colors.white70, fontSize: 13),
            ),
          ],
        ),
      );
    }

    return HtmlElementView(key: ValueKey(_viewId), viewType: _viewId);
  }
}
