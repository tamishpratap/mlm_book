<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BirthdayWidgetViewFriendsButtonPositionTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Sidebar User',
            'user_id' => 'sidebar_user_' . uniqid(),
            'email' => 'sidebar-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'profile_photo' => null,
            'city' => 'Mumbai',
            'country' => 'India',
        ], $attributes));
    }

    public function test_birthday_widget_css_has_proper_flex_and_bottom_action_layout(): void
    {
        $cssPath = base_path('../frontend/src/styles/dashboard.css');
        $this->assertFileExists($cssPath);
        $css = file_get_contents($cssPath);

        // Birthday widget is flex column
        $this->assertMatchesRegularExpression('/\.birthday-widget\s*\{[^}]*display:\s*flex/s', $css);
        $this->assertMatchesRegularExpression('/\.birthday-widget\s*\{[^}]*flex-direction:\s*column/s', $css);

        // Body has bottom spacing before action
        $this->assertMatchesRegularExpression('/\.birthday-widget__body\s*\{[^}]*margin-bottom:\s*14px/s', $css);

        // Action is pinned to bottom with margin-top: auto
        $this->assertMatchesRegularExpression('/\.birthday-widget__action\s*\{[^}]*margin-top:\s*auto/s', $css);
        $this->assertMatchesRegularExpression('/\.birthday-widget__action\s*\{[^}]*width:\s*100%/s', $css);

        // Soft cta button has flex centering and proper height
        $this->assertMatchesRegularExpression('/\.soft-cta\s*\{[^}]*display:\s*flex/s', $css);
        $this->assertMatchesRegularExpression('/\.soft-cta\s*\{[^}]*align-items:\s*center/s', $css);
        $this->assertMatchesRegularExpression('/\.soft-cta\s*\{[^}]*justify-content:\s*center/s', $css);
    }

    public function test_react_feed_right_sidebar_renders_birthday_widget_with_bottom_action(): void
    {
        $jsxPath = base_path('../frontend/src/components/posts/FeedRightSidebar.jsx');
        $this->assertFileExists($jsxPath);
        $jsx = file_get_contents($jsxPath);

        // Contains birthday widget with header, body, visual, and action
        $this->assertStringContainsString('className="card widget birthday-widget"', $jsx);
        $this->assertStringContainsString('<h2>Birthdays</h2>', $jsx);
        $this->assertStringContainsString('className="birthday-widget__body"', $jsx);
        $this->assertStringContainsString('className="gift-visual"', $jsx);
        $this->assertStringContainsString('<Cake size={24}', $jsx);
        $this->assertStringContainsString('className="birthday-widget__action"', $jsx);
        $this->assertStringContainsString('to="/member/friends"', $jsx);
        $this->assertStringContainsString('View Friends', $jsx);
    }

    public function test_blade_socials_view_renders_birthday_widget_with_bottom_action(): void
    {
        $bladePath = resource_path('views/member/socials.blade.php');
        $this->assertFileExists($bladePath);
        $blade = file_get_contents($bladePath);

        $this->assertStringContainsString('class="card widget birthday-widget"', $blade);
        $this->assertStringContainsString('<h2>Birthdays</h2>', $blade);
        $this->assertStringContainsString('class="birthday-widget__body"', $blade);
        $this->assertStringContainsString('class="birthday-widget__action"', $blade);
        $this->assertStringContainsString('route(\'member.friends.index\')', $blade);
        $this->assertStringContainsString('View Friends', $blade);
    }

    public function test_backend_public_dashboard_css_has_updated_birthday_widget_styles(): void
    {
        $publicCssPath = public_path('member_assets/css/dashboard.css');
        $this->assertFileExists($publicCssPath);
        $css = file_get_contents($publicCssPath);

        $this->assertMatchesRegularExpression('/\.birthday-widget\s*\{[^}]*display:\s*flex/s', $css);
        $this->assertMatchesRegularExpression('/\.birthday-widget__action\s*\{[^}]*margin-top:\s*auto/s', $css);
        $this->assertMatchesRegularExpression('/\.soft-cta\s*\{[^}]*display:\s*flex/s', $css);
    }

    public function test_member_can_access_socials_and_friends_routes(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $this->get(route('member.socials'))->assertOk()->assertSee('View Friends');
        $this->get(route('member.friends.index'))->assertOk();
    }
}
