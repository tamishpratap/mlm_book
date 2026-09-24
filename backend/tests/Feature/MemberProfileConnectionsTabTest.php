<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberProfileConnectionsTabTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Member User',
            'user_id' => 'user_' . uniqid(),
            'email' => 'user-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
            'profile_photo' => null,
            'city' => 'Mumbai',
            'country' => 'India',
        ], $attributes));
    }

    private function createFriendship(Member $m1, Member $m2): Friendship
    {
        [$one, $two] = Friendship::normalizePair($m1->id, $m2->id);

        return Friendship::create([
            'member_one_id' => $one,
            'member_two_id' => $two,
            'requested_by_id' => $m1->id,
            'status' => Friendship::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);
    }

    public function test_profile_api_returns_friends_list_for_connections_tab(): void
    {
        $viewer = $this->createMember(['name' => 'Viewer Member']);
        $profileOwner = $this->createMember(['name' => 'Profile Owner']);
        $friend = $this->createMember(['name' => 'Connected Friend', 'user_id' => 'conn_friend_123']);

        $this->createFriendship($profileOwner, $friend);

        $this->actingAs($viewer, 'member');

        $response = $this->getJson("/member/people/{$profileOwner->id}?tab=friends");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'tab' => 'friends',
            'counts' => [
                'friends' => 1,
            ],
        ]);

        $friendsList = $response->json('friends_list');
        $this->assertCount(1, $friendsList);
        $this->assertEquals($friend->id, $friendsList[0]['id']);
        $this->assertEquals('Connected Friend', $friendsList[0]['name']);
        $this->assertEquals('conn_friend_123', $friendsList[0]['user_id']);
    }

    public function test_blade_friends_partial_renders_compact_horizontal_row(): void
    {
        $profileOwner = $this->createMember(['name' => 'Profile Owner']);
        $friend = $this->createMember([
            'name' => 'Battleground Mobile India',
            'user_id' => 'battleground_mobile_ind_445385',
        ]);

        $this->createFriendship($profileOwner, $friend);

        $friendsList = collect([$friend]);
        $friendsCount = 1;
        $member = $profileOwner;

        $html = view('member.profile.partials.friends', compact('friendsList', 'friendsCount', 'member'))->render();

        // Must use compact horizontal row structure
        $this->assertStringContainsString('connection-requests-list', $html);
        $this->assertStringContainsString('compact-member-row', $html);
        $this->assertStringContainsString('compact-member-row--connection', $html);

        // Must display member name, username, and View Profile button
        $this->assertStringContainsString('Battleground Mobile India', $html);
        $this->assertStringContainsString('@battleground_mobile_ind_445385', $html);
        $this->assertStringContainsString('View Profile', $html);

        // Must NOT use old profile-friends-grid or profile-friend-card
        $this->assertStringNotContainsString('profile-friends-grid', $html);
        $this->assertStringNotContainsString('profile-friend-card', $html);
    }

    public function test_react_member_profile_page_uses_compact_member_row(): void
    {
        $jsxPath = base_path('../frontend/src/pages/profile/MemberProfilePage.jsx');
        $this->assertFileExists($jsxPath);

        $jsx = file_get_contents($jsxPath);

        // Must import and use CompactMemberRow
        $this->assertStringContainsString("import CompactMemberRow from '../../components/friends/CompactMemberRow';", $jsx);
        $this->assertStringContainsString('<CompactMemberRow', $jsx);
        $this->assertStringContainsString('connection-requests-list', $jsx);

        // Must NOT use FriendCard
        $this->assertStringNotContainsString('import FriendCard', $jsx);
        $this->assertStringNotContainsString('<FriendCard', $jsx);
    }

    public function test_react_my_profile_page_uses_compact_member_row(): void
    {
        $jsxPath = base_path('../frontend/src/pages/profile/MyProfilePage.jsx');
        $this->assertFileExists($jsxPath);

        $jsx = file_get_contents($jsxPath);

        // Must import and use CompactMemberRow for connections
        $this->assertStringContainsString("import CompactMemberRow from '../../components/friends/CompactMemberRow';", $jsx);
        $this->assertStringContainsString('<CompactMemberRow', $jsx);
        $this->assertStringContainsString('connection-requests-list', $jsx);
    }

    public function test_compact_member_row_component_has_independent_links_and_view_profile(): void
    {
        $rowPath = base_path('../frontend/src/components/friends/CompactMemberRow.jsx');
        $this->assertFileExists($rowPath);

        $rowContent = file_get_contents($rowPath);

        // Verified independent links
        $this->assertStringContainsString('connection-request-row__avatar-link', $rowContent);
        $this->assertStringContainsString('connection-request-row__name-link', $rowContent);
        $this->assertStringContainsString('connection-request-row__username-link', $rowContent);

        // View profile button for connection mode
        $this->assertStringContainsString('<span>View Profile</span>', $rowContent);

        // Authoritative MemberAvatar used
        $this->assertStringContainsString('<MemberAvatar', $rowContent);
    }

    public function test_react_my_profile_page_uses_compact_member_row_for_referrals(): void
    {
        $jsxPath = base_path('../frontend/src/pages/profile/MyProfilePage.jsx');
        $this->assertFileExists($jsxPath);

        $jsx = file_get_contents($jsxPath);

        // Must import and use CompactMemberRow for referrals
        $this->assertStringContainsString("import CompactMemberRow from '../../components/friends/CompactMemberRow';", $jsx);
        $this->assertStringContainsString('<CompactMemberRow', $jsx);
        $this->assertStringContainsString('mode="referral"', $jsx);

        // Must use connection-requests-list for referrals
        $this->assertStringContainsString('connection-requests-list', $jsx);

        // Must retain Introduced By section
        $this->assertStringContainsString('Introduced By', $jsx);

        // Section header and dynamic count must be preserved
        $this->assertStringContainsString('My Referrals', $jsx);
        $this->assertStringContainsString('Direct Referrals: {directReferralsCount}', $jsx);
    }

    public function test_compact_member_row_supports_referral_mode(): void
    {
        $rowPath = base_path('../frontend/src/components/friends/CompactMemberRow.jsx');
        $this->assertFileExists($rowPath);

        $rowContent = file_get_contents($rowPath);

        // Supports referral mode
        $this->assertStringContainsString("'compact-member-row--referral'", $rowContent);
        $this->assertStringContainsString("currentMode === 'referral'", $rowContent);

        // View profile button is displayed for both connection and referral modes
        $this->assertStringContainsString("currentMode === 'connection' || currentMode === 'referral'", $rowContent);
    }
}
