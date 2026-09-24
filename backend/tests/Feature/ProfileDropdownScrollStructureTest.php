<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\View;
use Tests\TestCase;

class ProfileDropdownScrollStructureTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(): Member
    {
        return Member::create([
            'name' => 'Profile User',
            'email' => 'profile-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);
    }

    public function test_profile_dropdown_blade_renders_header_menu_scroll_and_footer(): void
    {
        $member = $this->createMember();

        $html = View::make('member.partials.profile-dropdown', [
            'member' => $member,
        ])->render();

        // 1. Root dropdown container
        $this->assertStringContainsString('class="profile-dropdown"', $html);

        // 2. Identity Header container
        $this->assertStringContainsString('class="profile-dropdown__header"', $html);
        $this->assertStringContainsString('class="profile-dropdown__summary"', $html);
        $this->assertStringContainsString($member->name, $html);
        $this->assertStringContainsString($member->email, $html);
        $this->assertStringContainsString('View your profile', $html);

        // 3. Scrollable Menu Area container
        $this->assertStringContainsString('class="profile-dropdown__menu-scroll"', $html);
        $this->assertStringContainsString('View Profile', $html);
        $this->assertStringContainsString('Edit Profile', $html);
        $this->assertStringContainsString('Friend Requests', $html);
        $this->assertStringContainsString('Account Settings', $html);
        $this->assertStringContainsString('Password &amp; Security', $html);

        // 4. Footer container
        $this->assertStringContainsString('class="profile-dropdown__footer"', $html);
        $this->assertStringContainsString('Logout', $html);
    }

    public function test_css_defines_max_height_and_internal_menu_scroll(): void
    {
        $cssPath = base_path('../frontend/src/styles/member-profile.css');
        $this->assertFileExists($cssPath);

        $css = file_get_contents($cssPath);

        // Assert max-height rules based on viewport
        $this->assertStringContainsString('max-height: calc(100vh - var(--header-height, 74px) - 20px);', $css);
        $this->assertStringContainsString('max-height: calc(100dvh - var(--header-height, 74px) - 20px);', $css);

        // Assert scrollable menu area rules
        $this->assertStringContainsString('.profile-dropdown__menu-scroll', $css);
        $this->assertStringContainsString('overflow-y: auto;', $css);
        $this->assertStringContainsString('overscroll-behavior: contain;', $css);
        $this->assertStringContainsString('-webkit-overflow-scrolling: touch;', $css);

        // Assert mobile responsive rules
        $this->assertStringContainsString('@media (max-width: 760px)', $css);
        $this->assertStringContainsString('max-height: calc(100vh - var(--header-height, 66px) - 18px);', $css);
        $this->assertStringContainsString('@media (max-width: 520px)', $css);
        $this->assertStringContainsString('width: min(340px, calc(100vw - 16px));', $css);
    }

    public function test_frontend_profile_dropdown_component_contains_scroll_container(): void
    {
        $jsxPath = base_path('../frontend/src/layouts/components/ProfileDropdown.jsx');
        $this->assertFileExists($jsxPath);

        $jsx = file_get_contents($jsxPath);

        // Assert scroll container ref and class
        $this->assertStringContainsString('profile-dropdown__menu-scroll', $jsx);
        $this->assertStringContainsString('ref={scrollAreaRef}', $jsx);
        $this->assertStringContainsString('profile-dropdown__header', $jsx);
        $this->assertStringContainsString('profile-dropdown__footer', $jsx);

        // Assert scroll position reset on open
        $this->assertStringContainsString('scrollAreaRef.current.scrollTop = 0', $jsx);

        // Assert all menu items are present
        $this->assertStringContainsString('Account Verification', $jsx);
        $this->assertStringContainsString('Referral Link &amp; ID', $jsx);
        $this->assertStringContainsString('Reward Wallet', $jsx);
        $this->assertStringContainsString('Web3 USDT Wallet', $jsx);
        $this->assertStringContainsString('View Profile', $jsx);
        $this->assertStringContainsString('Edit Profile', $jsx);
        $this->assertStringContainsString('Friend Requests', $jsx);
        $this->assertStringContainsString('Account Settings', $jsx);
        $this->assertStringContainsString('Password &amp; Security', $jsx);
        $this->assertStringContainsString('Logout', $jsx);
    }
}
