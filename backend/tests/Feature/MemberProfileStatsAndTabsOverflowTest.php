<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberProfileStatsAndTabsOverflowTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Profile User',
            'user_id' => 'profile_user_' . uniqid(),
            'email' => 'profile-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'profile_photo' => null,
            'city' => 'Mumbai',
            'country' => 'India',
        ], $attributes));
    }

    public function test_member_profile_css_has_horizontal_overflow_protection_for_stats_and_tabs(): void
    {
        $cssPath = base_path('../frontend/src/styles/member-profile.css');
        $this->assertFileExists($cssPath);
        $css = file_get_contents($cssPath);

        // Stats row: flex container with overflow-x: auto and width constraints
        $this->assertMatchesRegularExpression('/\.profile-metrics-bar\s*\{[^}]*overflow-x:\s*auto/s', $css);
        $this->assertMatchesRegularExpression('/\.profile-metrics-bar\s*\{[^}]*max-width:\s*calc\(100%\s*-\s*36px\)/s', $css);
        $this->assertMatchesRegularExpression('/\.profile-metrics-bar\s*\{[^}]*min-width:\s*0/s', $css);
        $this->assertMatchesRegularExpression('/\.profile-stat-box\s*\{[^}]*min-width:\s*78px/s', $css);

        // Tab row: flex container with justify-content: flex-start, overflow-x: auto, and width constraints
        $this->assertMatchesRegularExpression('/\.profile-nav-tabs\s*\{[^}]*justify-content:\s*flex-start/s', $css);
        $this->assertMatchesRegularExpression('/\.profile-nav-tabs\s*\{[^}]*overflow-x:\s*auto/s', $css);
        $this->assertMatchesRegularExpression('/\.profile-nav-tabs\s*\{[^}]*max-width:\s*calc\(100%\s*-\s*36px\)/s', $css);
        $this->assertMatchesRegularExpression('/\.profile-nav-tabs\s*\{[^}]*min-width:\s*0/s', $css);

        // Individual tab styling: no wrap, content min-width
        $this->assertMatchesRegularExpression('/\.profile-nav-tab\s*\{[^}]*min-width:\s*max-content/s', $css);
        $this->assertMatchesRegularExpression('/\.profile-nav-tab\s*\{[^}]*white-space:\s*nowrap/s', $css);

        // Container safety
        $this->assertMatchesRegularExpression('/\.profile-page\s*\{[^}]*min-width:\s*0/s', $css);
        $this->assertMatchesRegularExpression('/\.profile-hero,\s*\.member-card\s*\{[^}]*min-width:\s*0/s', $css);
    }

    public function test_react_my_profile_page_has_scroll_effects_and_all_stats_and_tabs(): void
    {
        $jsxPath = base_path('../frontend/src/pages/profile/MyProfilePage.jsx');
        $this->assertFileExists($jsxPath);
        $jsx = file_get_contents($jsxPath);

        // Ref and auto-scroll hook attached
        $this->assertStringContainsString('tabsNavRef = useRef(null);', $jsx);
        $this->assertStringContainsString('ref={tabsNavRef}', $jsx);
        $this->assertStringContainsString('container.scrollTo', $jsx);

        // All 8 stats present
        $this->assertStringContainsString('<small>Posts</small>', $jsx);
        $this->assertStringContainsString('<small>Stories</small>', $jsx);
        $this->assertStringContainsString('<small>Connections</small>', $jsx);
        $this->assertStringContainsString('<small>Photos</small>', $jsx);
        $this->assertStringContainsString('<small>Videos</small>', $jsx);
        $this->assertStringContainsString('<small>Shares</small>', $jsx);
        $this->assertStringContainsString('<small>Reactions</small>', $jsx);
        $this->assertStringContainsString('<small>Comments</small>', $jsx);

        // All tabs present
        $this->assertStringContainsString("{ key: 'timeline', label: 'Timeline'", $jsx);
        $this->assertStringContainsString("{ key: 'about', label: 'About'", $jsx);
        $this->assertStringContainsString("{ key: 'photos',", $jsx);
        $this->assertStringContainsString("{ key: 'videos',", $jsx);
        $this->assertStringContainsString("{ key: 'friends',", $jsx);
        $this->assertStringContainsString("{ key: 'referrals',", $jsx);
        $this->assertStringContainsString("{ key: 'stories',", $jsx);
        $this->assertStringContainsString("{ key: 'saved', label: 'Saved Posts'", $jsx);
    }

    public function test_react_member_profile_page_has_scroll_effects_and_tabs_ref(): void
    {
        $jsxPath = base_path('../frontend/src/pages/profile/MemberProfilePage.jsx');
        $this->assertFileExists($jsxPath);
        $jsx = file_get_contents($jsxPath);

        // Ref and auto-scroll hook attached
        $this->assertStringContainsString('tabsNavRef = useRef(null);', $jsx);
        $this->assertStringContainsString('ref={tabsNavRef}', $jsx);
        $this->assertStringContainsString('container.scrollTo', $jsx);
    }

    public function test_backend_public_asset_includes_overflow_fix(): void
    {
        $publicCssPath = public_path('member_assets/css/member-profile.css');
        $this->assertFileExists($publicCssPath);
        $css = file_get_contents($publicCssPath);

        $this->assertStringContainsString('.profile-metrics-bar', $css);
        $this->assertStringContainsString('.profile-nav-tabs', $css);
        $this->assertStringContainsString('overflow-x: auto', $css);
    }

    public function test_profile_api_data_integrity_preserved(): void
    {
        $member = $this->createMember(['name' => 'Data Integrity User']);
        $this->actingAs($member, 'member');

        $response = $this->getJson('/member/profile');
        $response->assertOk();
        $response->assertJsonStructure([
            'member' => ['id', 'name', 'user_id'],
            'stats',
        ]);
    }
}
