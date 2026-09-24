<?php

namespace App\Contracts;

use App\Services\ContentModeration\ModerationResult;

interface ContentModerationProvider
{
    /**
     * Moderate an image file against content safety policies.
     *
     * @param string $absolutePath Absolute path to the quarantined file
     * @param string $mimeType Detected byte-level MIME type
     * @param string $sha256 Calculated SHA-256 hash of the quarantined file
     * @param string $context Authoritative upload context (e.g., 'POST_IMAGE')
     * @return ModerationResult
     */
    public function moderateImage(
        string $absolutePath,
        string $mimeType,
        string $sha256,
        string $context
    ): ModerationResult;
}
