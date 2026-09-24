import 'package:flutter/material.dart';

Widget buildPlatformVideoPlayer({
  required String videoUrl,
  required bool autoPlay,
  required bool loop,
  required bool isMuted,
}) {
  return Center(
    child: Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        const Icon(Icons.play_circle_fill, size: 56, color: Colors.white),
        const SizedBox(height: 8),
        Text(
          videoUrl.split('/').last,
          style: const TextStyle(color: Colors.white70, fontSize: 13),
        ),
      ],
    ),
  );
}
