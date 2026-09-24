import 'package:flutter/material.dart';
import 'video_player/video_player_stub.dart'
    if (dart.library.html) 'video_player/video_player_web.dart';

class AppVideoPlayer extends StatelessWidget {
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
  Widget build(BuildContext context) {
    return buildPlatformVideoPlayer(
      videoUrl: videoUrl,
      autoPlay: autoPlay,
      loop: loop,
      isMuted: isMuted,
    );
  }
}
