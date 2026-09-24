<?php

namespace Tests\Feature;

use App\Models\FeedbackSuggestion;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberFeedbackSuggestionTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'John Member',
            'email' => 'john.member@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ], $attributes));
    }

    public function test_unauthenticated_user_cannot_access_feedback_api(): void
    {
        $getResponse = $this->getJson('/api/member/feedback-suggestions');
        $getResponse->assertStatus(401);

        $postResponse = $this->postJson('/api/member/feedback-suggestions', [
            'type' => 'feedback',
            'subject' => 'Great platform',
            'message' => 'I love the user interface.',
        ]);
        $postResponse->assertStatus(401);
    }

    public function test_authenticated_member_can_submit_feedback(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $payload = [
            'type' => 'suggestion',
            'subject' => 'Add dark mode toggle',
            'message' => 'It would be great to have a dark mode option for night reading.',
        ];

        $response = $this->postJson('/api/member/feedback-suggestions', $payload);

        $response->assertStatus(201);
        $response->assertJson([
            'success' => true,
            'message' => 'Thank you! Your feedback has been submitted successfully.',
        ]);
        $response->assertJsonPath('feedback.subject', 'Add dark mode toggle');
        $response->assertJsonPath('feedback.type', 'suggestion');
        $response->assertJsonPath('feedback.status', 'new');
        $response->assertJsonPath('feedback.member_id', $member->id);

        $this->assertDatabaseHas('feedback_suggestions', [
            'member_id' => $member->id,
            'type' => 'suggestion',
            'subject' => 'Add dark mode toggle',
            'status' => 'new',
        ]);
    }

    public function test_backend_always_associates_submission_with_authenticated_member_ignoring_spoofed_id(): void
    {
        $member = $this->createMember(['email' => 'legit@example.com']);
        $otherMember = $this->createMember(['email' => 'victim@example.com']);

        $this->actingAs($member, 'member');

        $payload = [
            'member_id' => $otherMember->id, // Attempt to spoof another member's ID
            'type' => 'idea',
            'subject' => 'Crypto wallet staking',
            'message' => 'Proposing staking rewards inside the wallet tab.',
        ];

        $response = $this->postJson('/api/member/feedback-suggestions', $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('feedback.member_id', $member->id);

        $this->assertDatabaseHas('feedback_suggestions', [
            'member_id' => $member->id,
            'subject' => 'Crypto wallet staking',
        ]);

        $this->assertDatabaseMissing('feedback_suggestions', [
            'member_id' => $otherMember->id,
            'subject' => 'Crypto wallet staking',
        ]);
    }

    public function test_validation_requires_valid_type_subject_and_message(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        // Missing all fields
        $response = $this->postJson('/api/member/feedback-suggestions', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type', 'subject', 'message']);

        // Invalid type
        $response = $this->postJson('/api/member/feedback-suggestions', [
            'type' => 'unsupported_type_xyz',
            'subject' => 'Valid Subject',
            'message' => 'Valid message content.',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);

        // Message too short (under 5 characters)
        $response = $this->postJson('/api/member/feedback-suggestions', [
            'type' => 'feedback',
            'subject' => 'Short message test',
            'message' => 'Hi',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);

        // Subject too long (> 191 characters)
        $response = $this->postJson('/api/member/feedback-suggestions', [
            'type' => 'feedback',
            'subject' => str_repeat('A', 192),
            'message' => 'Valid message content here.',
        ]);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['subject']);
    }

    public function test_rapid_duplicate_submission_is_gracefully_prevented(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $payload = [
            'type' => 'idea',
            'subject' => 'Duplicate Prevention Test',
            'message' => 'Testing rapid double click prevention.',
        ];

        // First submission
        $firstResponse = $this->postJson('/api/member/feedback-suggestions', $payload);
        $firstResponse->assertStatus(201);

        // Immediate second submission (rapid double click)
        $secondResponse = $this->postJson('/api/member/feedback-suggestions', $payload);
        $secondResponse->assertStatus(200);
        $secondResponse->assertJson([
            'success' => true,
            'is_duplicate' => true,
        ]);

        // Exactly 1 database record exists
        $this->assertEquals(
            1,
            FeedbackSuggestion::where('member_id', $member->id)
                ->where('subject', 'Duplicate Prevention Test')
                ->count()
        );
    }

    public function test_authenticated_member_can_retrieve_only_their_own_submissions(): void
    {
        $memberA = $this->createMember(['email' => 'user.a@example.com']);
        $memberB = $this->createMember(['email' => 'user.b@example.com']);

        // Create submissions for both members
        FeedbackSuggestion::create([
            'member_id' => $memberA->id,
            'type' => 'feedback',
            'subject' => 'Member A feedback',
            'message' => 'Feedback from member A.',
            'status' => 'new',
        ]);

        FeedbackSuggestion::create([
            'member_id' => $memberB->id,
            'type' => 'suggestion',
            'subject' => 'Member B suggestion',
            'message' => 'Suggestion from member B.',
            'status' => 'new',
        ]);

        // Acting as Member A
        $this->actingAs($memberA, 'member');
        $response = $this->getJson('/api/member/feedback-suggestions');

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
        ]);

        $feedbacks = $response->json('feedbacks');
        $this->assertCount(1, $feedbacks);
        $this->assertEquals('Member A feedback', $feedbacks[0]['subject']);
        $this->assertEquals($memberA->id, $feedbacks[0]['member_id']);
    }

    public function test_sidebar_contains_feedback_and_suggestions_link(): void
    {
        $sidebarPath = base_path('../frontend/src/layouts/components/MemberSidebar.jsx');
        $this->assertFileExists($sidebarPath);

        $content = file_get_contents($sidebarPath);
        $this->assertStringContainsString('Feedback & Suggestions', $content);
        $this->assertStringContainsString('/member/feedback-suggestions', $content);
        $this->assertStringContainsString('MessageSquareText', $content);
    }

    public function test_routes_contains_feedback_and_suggestions_route(): void
    {
        $routesPath = base_path('../frontend/src/routes/AppRoutes.jsx');
        $this->assertFileExists($routesPath);

        $content = file_get_contents($routesPath);
        $this->assertStringContainsString('FeedbackSuggestionsPage', $content);
        $this->assertStringContainsString('/member/feedback-suggestions', $content);
    }
}
