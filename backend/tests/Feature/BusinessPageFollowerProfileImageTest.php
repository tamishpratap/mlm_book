<?php

namespace Tests\Feature;

use App\Models\BusinessFollower;
use App\Models\BusinessPage;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class BusinessPageFollowerProfileImageTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(string $name, ?string $profilePhoto = null): Member
    {
        return Member::create([
            'name' => $name,
            'user_id' => str($name)->slug('_')->toString() . '_' . random_int(1000, 9999),
            'email' => strtolower(str_replace(' ', '', $name)) . '-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'profile_photo' => $profilePhoto,
        ]);
    }

    private function createBusinessPage(Member $owner): BusinessPage
    {
        return BusinessPage::create([
            'page_id' => 'BP_' . strtoupper(Str::random(8)),
            'member_id' => $owner->id,
            'page_name' => 'Tech Solutions Ltd',
            'page_username' => 'techsolutions_' . random_int(100, 999),
            'slug' => 'tech-solutions-' . uniqid(),
            'category' => 'Technology & IT Services',
            'description' => 'A technology company description exceeding twenty characters.',
            'country' => 'India',
            'visibility' => 'public',
            'status' => 'active',
            'cover_photo' => 'uploads/business_pages/covers/cover_test.png',
            'logo' => 'uploads/business_pages/logos/logo_test.png',
        ]);
    }

    public function test_followers_api_returns_member_profile_photo_and_urls(): void
    {
        $owner = $this->createMember('Page Owner');
        $page = $this->createBusinessPage($owner);

        // Follower with profile photo
        $followerWithPhoto = $this->createMember('Tamish Pratap Singh', 'uploads/profile/tamish_photo.png');
        BusinessFollower::create([
            'business_page_id' => $page->id,
            'member_id' => $followerWithPhoto->id,
            'status' => 'accepted',
            'followed_at' => now(),
        ]);

        // Follower without profile photo
        $followerWithoutPhoto = $this->createMember('Priya Sharma', null);
        BusinessFollower::create([
            'business_page_id' => $page->id,
            'member_id' => $followerWithoutPhoto->id,
            'status' => 'accepted',
            'followed_at' => now()->subDay(),
        ]);

        $this->actingAs($owner, 'member');

        $response = $this->getJson("/api/member/business-pages/{$page->slug}?tab=followers");

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('followers_count', 2)
            ->assertJsonCount(2, 'followers.data');

        $followersData = $response->json('followers.data');

        // Check first follower (Tamish)
        $tamishData = collect($followersData)->firstWhere('member_id', $followerWithPhoto->id);
        $this->assertNotNull($tamishData);
        $this->assertSame('uploads/profile/tamish_photo.png', $tamishData['member']['profile_photo']);
        $this->assertNotNull($tamishData['member']['avatar_url']);
        // Verify that business page cover photo or logo is NEVER used as member avatar
        $this->assertNotSame($page->cover_photo, $tamishData['member']['profile_photo']);
        $this->assertNotSame($page->logo, $tamishData['member']['profile_photo']);

        // Check second follower without photo
        $priyaData = collect($followersData)->firstWhere('member_id', $followerWithoutPhoto->id);
        $this->assertNotNull($priyaData);
        $this->assertNull($priyaData['member']['profile_photo']);
        $this->assertNull($priyaData['member']['profile_photo_url']);
        // avatar_url should fall back to default profile asset
        $this->assertStringContainsString('profile.png', $priyaData['member']['avatar_url']);
    }

    public function test_followers_search_preserves_follower_identity_and_photos(): void
    {
        $owner = $this->createMember('Page Owner');
        $page = $this->createBusinessPage($owner);

        $follower1 = $this->createMember('Tamish Pratap Singh', 'uploads/profile/tamish_photo.png');
        BusinessFollower::create([
            'business_page_id' => $page->id,
            'member_id' => $follower1->id,
            'status' => 'accepted',
            'followed_at' => now(),
        ]);

        $follower2 = $this->createMember('Rahul Verma', 'uploads/profile/rahul_photo.png');
        BusinessFollower::create([
            'business_page_id' => $page->id,
            'member_id' => $follower2->id,
            'status' => 'accepted',
            'followed_at' => now()->subDay(),
        ]);

        $this->actingAs($owner, 'member');

        $response = $this->getJson("/api/member/business-pages/{$page->slug}?tab=followers&q=Tamish");

        $response->assertOk()
            ->assertJsonCount(1, 'followers.data')
            ->assertJsonPath('followers.data.0.member.name', 'Tamish Pratap Singh')
            ->assertJsonPath('followers.data.0.member.profile_photo', 'uploads/profile/tamish_photo.png');
    }

    public function test_blade_view_renders_follower_with_fallback_protection(): void
    {
        $owner = $this->createMember('Page Owner');
        $page = $this->createBusinessPage($owner);

        $follower = $this->createMember('Tamish Pratap Singh', 'uploads/profile/tamish_photo.png');
        BusinessFollower::create([
            'business_page_id' => $page->id,
            'member_id' => $follower->id,
            'status' => 'accepted',
            'followed_at' => now(),
        ]);

        $this->actingAs($owner, 'member');

        $response = $this->get("/member/business-pages/{$page->slug}?tab=followers");

        $response->assertOk()
            ->assertSee('Tamish Pratap Singh')
            ->assertSee('onerror="this.onerror=null;this.src=', false)
            ->assertSee('member_assets/images/dashboard/image/profile.png', false);
    }
}
