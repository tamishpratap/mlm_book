<?php

namespace App\Services\ContentModeration;

class ModerationResult
{
    /**
     * @param bool $success Whether the scan and classification succeeded
     * @param string $action 'ALLOW' or 'BLOCK'
     * @param string $mediaType 'image'
     * @param array<int, array{className: string, probability: float}> $predictions
     * @param string $dominantClass
     * @param float $confidence
     * @param string $model
     * @param string $modelVersion
     * @param int $durationMs
     * @param string $source
     * @param string $fileHash
     * @param string|null $providerRequestId
     * @param string|null $errorCode
     * @param string|null $userMessage
     * @param string|null $reasonCode
     */
    public function __construct(
        public bool $success,
        public string $action,
        public string $mediaType = 'image',
        public array $predictions = [],
        public string $dominantClass = 'Neutral',
        public float $confidence = 0.0,
        public string $model = 'MobileNetV2',
        public string $modelVersion = 'v1',
        public int $durationMs = 0,
        public string $source = 'remote_service',
        public string $fileHash = '',
        public ?string $providerRequestId = null,
        public ?string $errorCode = null,
        public ?string $userMessage = null,
        public ?string $reasonCode = null,
    ) {
    }

    /**
     * Determine if the file is explicitly allowed.
     */
    public function isAllowed(): bool
    {
        return $this->success && $this->action === 'ALLOW';
    }

    /**
     * Determine if the file is blocked by policy or failure.
     */
    public function isBlocked(): bool
    {
        return ! $this->isAllowed();
    }

    /**
     * Determine if the moderation check experienced a provider or internal failure.
     */
    public function isFailed(): bool
    {
        return ! $this->success;
    }

    /**
     * Get probability for a specific classification class.
     */
    public function getProbability(string $className): float
    {
        foreach ($this->predictions as $prediction) {
            if (strcasecmp($prediction['className'] ?? '', $className) === 0) {
                return (float) ($prediction['probability'] ?? 0.0);
            }
        }

        return 0.0;
    }

    /**
     * Factory for an allowed result.
     */
    public static function allow(
        array $predictions,
        string $fileHash,
        int $durationMs = 0,
        ?string $providerRequestId = null,
        string $source = 'remote_service',
        string $dominantClass = 'Neutral',
        float $confidence = 0.0,
        string $reasonCode = 'SAFE'
    ): self {
        return new self(
            success: true,
            action: 'ALLOW',
            mediaType: 'image',
            predictions: $predictions,
            dominantClass: $dominantClass,
            confidence: $confidence,
            model: 'MobileNetV2',
            modelVersion: 'v1',
            durationMs: $durationMs,
            source: $source,
            fileHash: $fileHash,
            providerRequestId: $providerRequestId,
            errorCode: null,
            userMessage: null,
            reasonCode: $reasonCode
        );
    }

    /**
     * Factory for a blocked result.
     */
    public static function block(
        array $predictions,
        string $fileHash,
        string $reasonCode,
        string $userMessage,
        int $durationMs = 0,
        ?string $providerRequestId = null,
        string $source = 'remote_service',
        string $dominantClass = 'Porn',
        float $confidence = 0.0
    ): self {
        return new self(
            success: true,
            action: 'BLOCK',
            mediaType: 'image',
            predictions: $predictions,
            dominantClass: $dominantClass,
            confidence: $confidence,
            model: 'MobileNetV2',
            modelVersion: 'v1',
            durationMs: $durationMs,
            source: $source,
            fileHash: $fileHash,
            providerRequestId: $providerRequestId,
            errorCode: null,
            userMessage: $userMessage,
            reasonCode: $reasonCode
        );
    }

    /**
     * Factory for a failure / fail-closed result.
     */
    public static function failClosed(
        string $errorCode,
        string $userMessage,
        string $fileHash = '',
        ?string $providerRequestId = null,
        int $durationMs = 0,
        string $source = 'remote_service'
    ): self {
        return new self(
            success: false,
            action: 'BLOCK',
            mediaType: 'image',
            predictions: [],
            dominantClass: 'Unknown',
            confidence: 0.0,
            model: 'MobileNetV2',
            modelVersion: 'v1',
            durationMs: $durationMs,
            source: $source,
            fileHash: $fileHash,
            providerRequestId: $providerRequestId,
            errorCode: $errorCode,
            userMessage: $userMessage,
            reasonCode: $errorCode
        );
    }

    /**
     * Convert result to array.
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'action' => $this->action,
            'media_type' => $this->mediaType,
            'predictions' => $this->predictions,
            'dominant_class' => $this->dominantClass,
            'confidence' => $this->confidence,
            'model' => $this->model,
            'model_version' => $this->modelVersion,
            'duration_ms' => $this->durationMs,
            'source' => $this->source,
            'file_hash' => $this->fileHash,
            'provider_request_id' => $this->providerRequestId,
            'error_code' => $this->errorCode,
            'user_message' => $this->userMessage,
            'reason_code' => $this->reasonCode,
        ];
    }
}
