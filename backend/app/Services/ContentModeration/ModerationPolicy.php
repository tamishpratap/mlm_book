<?php

namespace App\Services\ContentModeration;

class ModerationPolicy
{
    /**
     * Default fallback thresholds if context is unknown or unconfigured.
     */
    protected const DEFAULT_THRESHOLDS = [
        'Porn' => 0.60,
        'Hentai' => 0.60,
        'Sexy' => 0.80,
    ];

    /**
     * Standard user-facing message on content violation.
     */
    public const BLOCKED_USER_MESSAGE = 'This image cannot be uploaded because it may contain restricted content.';

    /**
     * Evaluate raw predictions against context thresholds and return an authoritative ModerationResult.
     *
     * @param array<int, array{className: string, probability: float}> $predictions
     * @param string $context
     * @param string $fileHash
     * @param int $durationMs
     * @param string|null $providerRequestId
     * @param string $source
     * @return ModerationResult
     */
    public function evaluate(
        array $predictions,
        string $context,
        string $fileHash,
        int $durationMs = 0,
        ?string $providerRequestId = null,
        string $source = 'remote_service'
    ): ModerationResult {
        $thresholds = $this->getThresholdsForContext($context);

        // Map class probabilities
        $probMap = [];
        $dominantClass = 'Neutral';
        $maxProb = -1.0;

        foreach ($predictions as $p) {
            $name = $p['className'] ?? '';
            $prob = (float) ($p['probability'] ?? 0.0);
            $probMap[$name] = $prob;

            if ($prob > $maxProb) {
                $maxProb = $prob;
                $dominantClass = $name;
            }
        }

        $pornProb = $probMap['Porn'] ?? 0.0;
        $hentaiProb = $probMap['Hentai'] ?? 0.0;
        $sexyProb = $probMap['Sexy'] ?? 0.0;

        // 1. Explicit Porn check
        if ($pornProb >= ($thresholds['Porn'] ?? 0.60)) {
            return ModerationResult::block(
                predictions: $predictions,
                fileHash: $fileHash,
                reasonCode: 'EXCEEDS_PORN_THRESHOLD',
                userMessage: self::BLOCKED_USER_MESSAGE,
                durationMs: $durationMs,
                providerRequestId: $providerRequestId,
                source: $source,
                dominantClass: $dominantClass,
                confidence: $maxProb
            );
        }

        // 2. Explicit Hentai check
        if ($hentaiProb >= ($thresholds['Hentai'] ?? 0.60)) {
            return ModerationResult::block(
                predictions: $predictions,
                fileHash: $fileHash,
                reasonCode: 'EXCEEDS_HENTAI_THRESHOLD',
                userMessage: self::BLOCKED_USER_MESSAGE,
                durationMs: $durationMs,
                providerRequestId: $providerRequestId,
                source: $source,
                dominantClass: $dominantClass,
                confidence: $maxProb
            );
        }

        // 3. Sexy check (context-specific threshold)
        if ($sexyProb >= ($thresholds['Sexy'] ?? 0.80)) {
            return ModerationResult::block(
                predictions: $predictions,
                fileHash: $fileHash,
                reasonCode: 'EXCEEDS_SEXY_THRESHOLD',
                userMessage: self::BLOCKED_USER_MESSAGE,
                durationMs: $durationMs,
                providerRequestId: $providerRequestId,
                source: $source,
                dominantClass: $dominantClass,
                confidence: $maxProb
            );
        }

        // 4. Allowed
        return ModerationResult::allow(
            predictions: $predictions,
            fileHash: $fileHash,
            durationMs: $durationMs,
            providerRequestId: $providerRequestId,
            source: $source,
            dominantClass: $dominantClass,
            confidence: $maxProb,
            reasonCode: 'SAFE'
        );
    }

    protected ?array $configuredContexts = null;

    public function __construct(?array $configuredContexts = null)
    {
        $this->configuredContexts = $configuredContexts;
    }

    /**
     * Get thresholds for a given context from config.
     *
     * @return array{Porn: float, Hentai: float, Sexy: float}
     */
    public function getThresholdsForContext(string $context): array
    {
        $normalized = strtoupper(trim($context));

        if ($this->configuredContexts !== null && isset($this->configuredContexts[$normalized])) {
            return array_merge(self::DEFAULT_THRESHOLDS, $this->configuredContexts[$normalized]);
        }

        if (function_exists('app') && app()->bound('config')) {
            $configThresholds = config("content_moderation.contexts.{$normalized}");

            if (is_array($configThresholds)) {
                return array_merge(self::DEFAULT_THRESHOLDS, $configThresholds);
            }
        }

        return self::DEFAULT_THRESHOLDS;
    }
}
