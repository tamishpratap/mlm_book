<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Server-Side Content Moderation Enabled
    |--------------------------------------------------------------------------
    |
    | When enabled, Laravel will enforce server-side content moderation
    | before any uploaded media is compressed or committed to public storage.
    |
    */

    'enabled' => env('CONTENT_MODERATION_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Content Moderation Provider
    |--------------------------------------------------------------------------
    |
    | Supported providers: "remote", "null" (testing/mock)
    |
    */

    'provider' => env('CONTENT_MODERATION_PROVIDER', 'remote'),

    /*
    |--------------------------------------------------------------------------
    | Remote Moderation Service Connection
    |--------------------------------------------------------------------------
    |
    | Connection configuration for the independent Node.js moderation microservice.
    |
    */

    'url' => rtrim(env('CONTENT_MODERATION_URL', 'http://127.0.0.1:3001'), '/'),
    'secret' => env('CONTENT_MODERATION_SECRET', 'mlm-moderation-dev-secret-2026'),
    'timeout' => (int) env('CONTENT_MODERATION_TIMEOUT', 10),
    'max_image_mb' => (int) env('CONTENT_MODERATION_MAX_IMAGE_MB', 15),

    /*
    |--------------------------------------------------------------------------
    | Quarantine Path
    |--------------------------------------------------------------------------
    |
    | Storage path where raw uploaded files are staged before moderation.
    | This directory must NOT be publicly web-accessible.
    |
    */

    'quarantine_path' => storage_path('app/quarantine/moderation'),

    /*
    |--------------------------------------------------------------------------
    | Policy Thresholds Per Context
    |--------------------------------------------------------------------------
    |
    | Authoritative server-side policy thresholds for each member upload context.
    | Any image with a probability meeting or exceeding these thresholds will be BLOCKED.
    |
    */

    'contexts' => [
        'PROFILE_PHOTO' => [
            'Porn' => 0.50,
            'Hentai' => 0.50,
            'Sexy' => 0.70,
        ],
        'PROFILE_COVER' => [
            'Porn' => 0.50,
            'Hentai' => 0.50,
            'Sexy' => 0.75,
        ],
        'POST_IMAGE' => [
            'Porn' => 0.60,
            'Hentai' => 0.60,
            'Sexy' => 0.80,
        ],
        'STORY_IMAGE' => [
            'Porn' => 0.60,
            'Hentai' => 0.60,
            'Sexy' => 0.80,
        ],
        'COMMUNITY_IMAGE' => [
            'Porn' => 0.60,
            'Hentai' => 0.60,
            'Sexy' => 0.80,
        ],
        'BUSINESS_IMAGE' => [
            'Porn' => 0.55,
            'Hentai' => 0.55,
            'Sexy' => 0.75,
        ],
        'REVIEW_IMAGE' => [
            'Porn' => 0.60,
            'Hentai' => 0.60,
            'Sexy' => 0.80,
        ],
        'MARKETPLACE_IMAGE' => [
            'Porn' => 0.50,
            'Hentai' => 0.50,
            'Sexy' => 0.75,
        ],
        'MESSAGE_IMAGE' => [
            'Porn' => 0.65,
            'Hentai' => 0.65,
            'Sexy' => 0.85,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Video Content Moderation Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for server-side video moderation via FFmpeg frame sampling,
    | microservice frame scoring, and deterministic video aggregation policy.
    |
    */

    'video' => [
        'enabled' => env('CONTENT_MODERATION_VIDEO_ENABLED', true),
        'quarantine_path' => storage_path('app/quarantine/video-moderation'),
        'max_duration_seconds' => (int) env('CONTENT_MODERATION_MAX_VIDEO_DURATION', 300),
        'max_filesize_mb' => (int) env('CONTENT_MODERATION_MAX_VIDEO_MB', 60),

        'sampling' => [
            'short_video_threshold' => 5.0,
            'long_video_threshold' => 30.0,
            'short_frames' => 6,
            'normal_frames' => 8,
            'long_frames' => 12,
            'max_frames_cap' => 12,
            'start_margin_pct' => 0.05,
            'end_margin_pct' => 0.95,
        ],

        'aggregation' => [
            'max_failed_frame_ratio' => 0.25,
            'repeated_sexy_threshold_count' => 2,
        ],

        'contexts' => [
            'POST_VIDEO' => [
                'Porn' => 0.60,
                'Hentai' => 0.60,
                'Sexy' => 0.80,
            ],
            'STORY_VIDEO' => [
                'Porn' => 0.60,
                'Hentai' => 0.60,
                'Sexy' => 0.80,
            ],
            'COMMUNITY_VIDEO' => [
                'Porn' => 0.60,
                'Hentai' => 0.60,
                'Sexy' => 0.80,
            ],
            'BUSINESS_VIDEO' => [
                'Porn' => 0.55,
                'Hentai' => 0.55,
                'Sexy' => 0.75,
            ],
            'MARKETPLACE_VIDEO' => [
                'Porn' => 0.50,
                'Hentai' => 0.50,
                'Sexy' => 0.75,
            ],
        ],
    ],
];

