<?php

namespace Tests\Unit\ContentModeration;

use App\Services\ContentModeration\ModerationPolicy;
use Tests\TestCase;

class ModerationPolicyTest extends TestCase
{
    private ModerationPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new ModerationPolicy();
    }

    public function test_safe_content_is_allowed(): void
    {
        $predictions = [
            ['className' => 'Neutral', 'probability' => 0.92],
            ['className' => 'Drawing', 'probability' => 0.05],
            ['className' => 'Sexy', 'probability' => 0.02],
            ['className' => 'Hentai', 'probability' => 0.005],
            ['className' => 'Porn', 'probability' => 0.005],
        ];

        $result = $this->policy->evaluate($predictions, 'POST_IMAGE', 'dummyhash');

        $this->assertTrue($result->isAllowed());
        $this->assertFalse($result->isBlocked());
        $this->assertSame('ALLOW', $result->action);
        $this->assertSame('SAFE', $result->reasonCode);
    }

    public function test_porn_exceeding_threshold_is_blocked(): void
    {
        $predictions = [
            ['className' => 'Porn', 'probability' => 0.65],
            ['className' => 'Neutral', 'probability' => 0.20],
            ['className' => 'Sexy', 'probability' => 0.10],
            ['className' => 'Hentai', 'probability' => 0.04],
            ['className' => 'Drawing', 'probability' => 0.01],
        ];

        $result = $this->policy->evaluate($predictions, 'POST_IMAGE', 'dummyhash');

        $this->assertFalse($result->isAllowed());
        $this->assertTrue($result->isBlocked());
        $this->assertSame('BLOCK', $result->action);
        $this->assertSame('EXCEEDS_PORN_THRESHOLD', $result->reasonCode);
        $this->assertSame(ModerationPolicy::BLOCKED_USER_MESSAGE, $result->userMessage);
    }

    public function test_hentai_exceeding_threshold_is_blocked(): void
    {
        $predictions = [
            ['className' => 'Hentai', 'probability' => 0.62],
            ['className' => 'Drawing', 'probability' => 0.30],
            ['className' => 'Neutral', 'probability' => 0.05],
            ['className' => 'Porn', 'probability' => 0.02],
            ['className' => 'Sexy', 'probability' => 0.01],
        ];

        $result = $this->policy->evaluate($predictions, 'POST_IMAGE', 'dummyhash');

        $this->assertTrue($result->isBlocked());
        $this->assertSame('EXCEEDS_HENTAI_THRESHOLD', $result->reasonCode);
    }

    public function test_sexy_exceeding_threshold_is_blocked(): void
    {
        $predictions = [
            ['className' => 'Sexy', 'probability' => 0.85],
            ['className' => 'Neutral', 'probability' => 0.10],
            ['className' => 'Drawing', 'probability' => 0.03],
            ['className' => 'Porn', 'probability' => 0.01],
            ['className' => 'Hentai', 'probability' => 0.01],
        ];

        $result = $this->policy->evaluate($predictions, 'POST_IMAGE', 'dummyhash');

        $this->assertTrue($result->isBlocked());
        $this->assertSame('EXCEEDS_SEXY_THRESHOLD', $result->reasonCode);
    }

    public function test_context_threshold_strictness_profile_photo(): void
    {
        // For PROFILE_PHOTO, Sexy threshold is 0.70. A score of 0.72 should BLOCK.
        // But for POST_IMAGE, Sexy threshold is 0.80, so 0.72 would ALLOW.
        $predictions = [
            ['className' => 'Sexy', 'probability' => 0.72],
            ['className' => 'Neutral', 'probability' => 0.25],
            ['className' => 'Drawing', 'probability' => 0.01],
            ['className' => 'Porn', 'probability' => 0.01],
            ['className' => 'Hentai', 'probability' => 0.01],
        ];

        $profileResult = $this->policy->evaluate($predictions, 'PROFILE_PHOTO', 'dummyhash');
        $postResult = $this->policy->evaluate($predictions, 'POST_IMAGE', 'dummyhash');

        $this->assertTrue($profileResult->isBlocked(), '0.72 Sexy must be BLOCKED for PROFILE_PHOTO');
        $this->assertTrue($postResult->isAllowed(), '0.72 Sexy must be ALLOWED for POST_IMAGE');
    }

    public function test_context_threshold_strictness_marketplace(): void
    {
        // For MARKETPLACE_IMAGE, Porn threshold is 0.50. Score 0.55 should BLOCK.
        $predictions = [
            ['className' => 'Porn', 'probability' => 0.55],
            ['className' => 'Neutral', 'probability' => 0.40],
            ['className' => 'Sexy', 'probability' => 0.03],
            ['className' => 'Hentai', 'probability' => 0.01],
            ['className' => 'Drawing', 'probability' => 0.01],
        ];

        $result = $this->policy->evaluate($predictions, 'MARKETPLACE_IMAGE', 'dummyhash');

        $this->assertTrue($result->isBlocked());
        $this->assertSame('EXCEEDS_PORN_THRESHOLD', $result->reasonCode);
    }

    public function test_exact_threshold_boundary(): void
    {
        // Exact threshold for POST_IMAGE Porn is 0.60
        $predictionsAtBoundary = [
            ['className' => 'Porn', 'probability' => 0.60],
            ['className' => 'Neutral', 'probability' => 0.40],
        ];

        $result = $this->policy->evaluate($predictionsAtBoundary, 'POST_IMAGE', 'dummyhash');
        $this->assertTrue($result->isBlocked(), 'Score exactly at threshold (0.60) must be blocked');

        $predictionsJustBelow = [
            ['className' => 'Porn', 'probability' => 0.599],
            ['className' => 'Neutral', 'probability' => 0.401],
        ];

        $resultBelow = $this->policy->evaluate($predictionsJustBelow, 'POST_IMAGE', 'dummyhash');
        $this->assertTrue($resultBelow->isAllowed(), 'Score just below threshold (0.599) must be allowed');
    }
}
