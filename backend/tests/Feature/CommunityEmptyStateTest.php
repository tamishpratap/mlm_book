<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunityEmptyStateTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(): Member
    {
        return Member::create([
            'name' => 'Community Explorer',
            'email' => 'explorer-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);
    }

    public function test_community_hub_empty_state_renders_card_and_creation_link(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $response = $this->get(route('member.community.index'));

        $response->assertOk();
        // Assert the empty state card container is present
        $response->assertSee('community-empty-state');
        $response->assertSee('card');
        $response->assertSee('No Communities Found');
        $response->assertSee('Be the first pioneer to create a community for this category!');
        // Assert both header Create Community button and empty-state Create Community button are present
        $response->assertSee(route('member.community.create'));
    }

    public function test_community_api_returns_empty_when_no_communities_exist(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $response = $this->getJson('/api/member/community');

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertCount(0, $response->json('communities.data'));
    }
}
