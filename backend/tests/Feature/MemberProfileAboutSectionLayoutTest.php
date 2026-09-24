<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberProfileAboutSectionLayoutTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'John Doe',
            'user_id' => 'john_doe_999',
            'email' => 'member-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'city' => 'Mumbai',
            'country' => 'India',
        ], $attributes));
    }

    public function test_api_member_people_profile_returns_data_needed_for_about_section(): void
    {
        $viewer = $this->createMember(['name' => 'Viewer']);
        $target = $this->createMember([
            'name' => 'Target User',
            'user_id' => 'battleground_mobile_ind_445385',
            'city' => 'New Delhi',
            'country' => 'India',
        ]);

        $this->actingAs($viewer, 'member');

        $response = $this->getJson("/api/member/people/{$target->id}?tab=about");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'member' => [
                'id' => $target->id,
                'name' => 'Target User',
                'user_id' => 'battleground_mobile_ind_445385',
                'city' => 'New Delhi',
                'country' => 'India',
            ],
        ]);
        $this->assertArrayHasKey('counts', $response->json());
    }

    public function test_css_defines_profile_about_grid_columns_and_word_break(): void
    {
        $cssPath = base_path('../frontend/src/styles/member-profile.css');
        $this->assertFileExists($cssPath);

        $css = file_get_contents($cssPath);

        // 1. Grid container & columns with minmax(0, 1fr)
        $this->assertStringContainsString('.profile-about-grid', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(3, minmax(0, 1fr));', $css);

        // 2. Responsive tablet & mobile columns
        $this->assertStringContainsString('@media (max-width: 900px)', $css);
        $this->assertStringContainsString('grid-template-columns: repeat(2, minmax(0, 1fr));', $css);
        $this->assertStringContainsString('@media (max-width: 560px)', $css);
        $this->assertStringContainsString('grid-template-columns: 1fr;', $css);

        // 3. Field container with min-width: 0
        $this->assertStringContainsString('.profile-about-field', $css);
        $this->assertStringContainsString('min-width: 0;', $css);

        // 4. Value container with word-break and overflow-wrap
        $this->assertStringContainsString('.profile-about-field__value', $css);
        $this->assertStringContainsString('word-break: break-word;', $css);
        $this->assertStringContainsString('overflow-wrap: break-word;', $css);
    }

    public function test_frontend_member_profile_page_uses_profile_about_grid(): void
    {
        $jsxPath = base_path('../frontend/src/pages/profile/MemberProfilePage.jsx');
        $this->assertFileExists($jsxPath);

        $jsx = file_get_contents($jsxPath);

        // Assert classes exist in MemberProfilePage.jsx
        $this->assertStringContainsString('className="profile-about-grid"', $jsx);
        $this->assertStringContainsString('className="profile-about-field"', $jsx);
        $this->assertStringContainsString('className="profile-about-field__label"', $jsx);
        $this->assertStringContainsString('className="profile-about-field__value"', $jsx);

        // Assert fields are structured with label, value, and title
        $this->assertStringContainsString('>Handle</span>', $jsx);
        $this->assertStringContainsString('>Location</span>', $jsx);
        $this->assertStringContainsString('>Joined</span>', $jsx);
        $this->assertStringContainsString('>Network</span>', $jsx);
        $this->assertStringContainsString('title={member.user_id ? `@${member.user_id}` : \'Not set\'}', $jsx);
        $this->assertStringContainsString('title={location || \'Not provided\'}', $jsx);
    }
}
