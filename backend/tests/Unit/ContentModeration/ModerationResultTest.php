<?php

namespace Tests\Unit\ContentModeration;

use App\Services\ContentModeration\ModerationResult;
use PHPUnit\Framework\TestCase;

class ModerationResultTest extends TestCase
{
    public function test_allow_factory(): void
    {
        $predictions = [
            ['className' => 'Neutral', 'probability' => 0.95],
            ['className' => 'Drawing', 'probability' => 0.05],
        ];

        $result = ModerationResult::allow($predictions, 'abc123hash', 120, 'req_1', 'remote_service', 'Neutral', 0.95);

        $this->assertTrue($result->success);
        $this->assertSame('ALLOW', $result->action);
        $this->assertTrue($result->isAllowed());
        $this->assertFalse($result->isBlocked());
        $this->assertFalse($result->isFailed());
        $this->assertSame(0.95, $result->getProbability('Neutral'));
        $this->assertSame(0.05, $result->getProbability('Drawing'));
        $this->assertSame(0.0, $result->getProbability('NonExistent'));
        $this->assertSame('abc123hash', $result->fileHash);
        $this->assertSame('req_1', $result->providerRequestId);
    }

    public function test_block_factory(): void
    {
        $predictions = [
            ['className' => 'Porn', 'probability' => 0.88],
            ['className' => 'Neutral', 'probability' => 0.12],
        ];

        $result = ModerationResult::block(
            $predictions,
            'hash456',
            'EXCEEDS_PORN_THRESHOLD',
            'Restricted content',
            150,
            'req_2',
            'remote_service',
            'Porn',
            0.88
        );

        $this->assertTrue($result->success);
        $this->assertSame('BLOCK', $result->action);
        $this->assertFalse($result->isAllowed());
        $this->assertTrue($result->isBlocked());
        $this->assertFalse($result->isFailed());
        $this->assertSame('EXCEEDS_PORN_THRESHOLD', $result->reasonCode);
        $this->assertSame('Restricted content', $result->userMessage);
    }

    public function test_fail_closed_factory(): void
    {
        $result = ModerationResult::failClosed('PROVIDER_TIMEOUT', 'Service unavailable', 'hash789');

        $this->assertFalse($result->success);
        $this->assertSame('BLOCK', $result->action);
        $this->assertFalse($result->isAllowed());
        $this->assertTrue($result->isBlocked());
        $this->assertTrue($result->isFailed());
        $this->assertSame('PROVIDER_TIMEOUT', $result->errorCode);
        $this->assertSame('Service unavailable', $result->userMessage);
    }

    public function test_to_array_serialization(): void
    {
        $result = ModerationResult::allow([], 'hash1', 50);
        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertTrue($array['success']);
        $this->assertSame('ALLOW', $array['action']);
        $this->assertSame('image', $array['media_type']);
        $this->assertSame('hash1', $array['file_hash']);
        $this->assertSame(50, $array['duration_ms']);
    }
}
