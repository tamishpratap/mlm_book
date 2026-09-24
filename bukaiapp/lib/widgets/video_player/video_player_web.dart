// ignore_for_file: avoid_web_libraries_in_flutter
import 'dart:html' as html;
import 'dart:ui_web' as ui_web;
import 'package:flutter/material.dart';

Widget buildPlatformVideoPlayer({
  required String videoUrl,
  required bool autoPlay,
  required bool loop,
  required bool isMuted,
}) {
  return _WebVideoPlayer(
    videoUrl: videoUrl,
    autoPlay: autoPlay,
    loop: loop,
    isMuted: isMuted,
  );
}

class _WebVideoPlayer extends StatefulWidget {
  final String videoUrl;
  final bool autoPlay;
  final bool loop;
  final bool isMuted;

  const _WebVideoPlayer({
    required this.videoUrl,
    required this.autoPlay,
    required this.loop,
    required this.isMuted,
  });

  @override
  State<_WebVideoPlayer> createState() => _WebVideoPlayerState();
}

class _WebVideoPlayerState extends State<_WebVideoPlayer> {
  late String _viewId;
  html.VideoElement? _videoElement;

  @override
  void initState() {
    super.initState();
    _viewId = 'video_${widget.videoUrl.hashCode}_${DateTime.now().millisecondsSinceEpoch}';

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

  @override
  void didUpdateWidget(covariant _WebVideoPlayer oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (_videoElement != null) {
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
    if (_videoElement != null) {
      _videoElement!.pause();
      _videoElement!.src = '';
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return HtmlElementView(key: ValueKey(_viewId), viewType: _viewId);
  }
}
