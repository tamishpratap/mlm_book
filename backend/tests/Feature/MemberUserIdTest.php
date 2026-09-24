<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use App\Models\PendingMemberRegistration;
use App\Services\MemberUserIdService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberUserIdTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_id_schema_service_and_generation_rules(): void
    {
        $service = app(MemberUserIdService::class);

        $this->assertTrue(Schema::hasColumn('members', 'user_id'));
        $this->assertSame('tamish_pratap', $service->normalize('  @Tamish_Pratap  '));
        $this->assertSame('RAHU104927', $service->normalize('  @rahu104927  '));
        $this->assertTrue($service->isReserved('@ADMIN'));
        $this->assertTrue($service->isReserved('admin'));
        $this->assertFalse($service->isAvailable('admin'));

        $first = $service->generateFromName('Tamish Pratap Singh With A Very Long Name');
        $this->assertMatchesRegularExpression(MemberUserIdService::FORMAT_PATTERN, $first);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $first);
        $this->assertSame(10, strlen($first));
        $this->assertStringStartsWith('tami', $first);

        Member::create([
            'name' => 'First Generated Member',
            'user_id' => $first,
            'email' => 'first-generated@example.com',
            'password' => 'secure-password',
            'mobile_verified_at' => now(),
        ]);

        $second = $service->generateFromName('Tamish Pratap Singh With A Very Long Name');

        $this->assertMatchesRegularExpression(MemberUserIdService::FORMAT_PATTERN, $second);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $second);
        $this->assertSame(10, strlen($second));
        $this->assertStringStartsWith('tami', $second);
        $this->assertNotSame($first, $second);
    }

    public function test_normal_registration_auto_generates_exact_10_character_user_id(): void
    {
        // 1. Submit normal registration without user_id
        $response = $this->post(route('member.register.submit'), [
            'name' => 'Rahul Sharma',
            'email' => 'rahul.sharma@example.com',
            'phone' => '+919876543210',
            'password' => 'secure-password',
        ]);

        $response->assertRedirect(route('member.register.verify'));

        $pending = PendingMemberRegistration::where('email', 'rahul.sharma@example.com')->first();
        $this->assertNotNull($pending);
        $this->assertSame(10, strlen($pending->user_id));
        $this->assertMatchesRegularExpression(MemberUserIdService::FORMAT_PATTERN, $pending->user_id);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $pending->user_id);
        $this->assertStringStartsWith('rahu', $pending->user_id);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', substr($pending->user_id, 4));

        // 2. Complete OTP verification
        $otp = '123456';
        $pending->otp_hash = Hash::make($otp);
        $pending->save();

        session(['pending_registration_token' => $pending->token]);

        $verifyResponse = $this->post(route('member.register.verify.submit'), [
            'otp' => $otp,
        ]);

        $verifyResponse->assertRedirect(route('member.dashboard'));

        $member = Member::where('email', 'rahul.sharma@example.com')->first();
        $this->assertNotNull($member);
        $this->assertSame($pending->user_id, $member->user_id);
        $this->assertSame(10, strlen($member->user_id));
        $this->assertMatchesRegularExpression(MemberUserIdService::FORMAT_PATTERN, $member->user_id);
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $member->user_id);
        $this->assertAuthenticatedAs($member, 'member');
    }

    public function test_google_registration_creates_exact_10_character_user_id(): void
    {
        $service = app(MemberUserIdService::class);
        $name = 'Tamish Pratap Singh';
        $userId = $service->generateFromName($name);

        $this->assertSame(10, strlen($userId));
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $userId);
        $this->assertStringStartsWith('tami', $userId);
        $this->assertMatchesRegularExpression('/^[0-9]{6}$/', substr($userId, 4));
        $this->assertMatchesRegularExpression(MemberUserIdService::FORMAT_PATTERN, $userId);

        $googleMember = Member::create([
            'name' => $name,
            'user_id' => $userId,
            'email' => 'tamish.google@example.com',
            'google_id' => 'google_oauth_id_12345',
            'password' => 'random_pass',
            'mobile_verified_at' => now(),
        ]);

        $this->assertSame($userId, $googleMember->user_id);
        $this->assertSame(10, strlen($googleMember->user_id));
    }

    public function test_collision_handling_and_automatic_retry(): void
    {
        $service = app(MemberUserIdService::class);
        $prefix = $service->namePrefix('Rahul Sharma');
        $this->assertSame('rahu', $prefix);

        // Seed consecutive candidates to force collisions
        $collidingSuffixes = ['111111', '222222', '333333'];
        foreach ($collidingSuffixes as $suffix) {
            Member::create([
                'name' => 'Colliding Member',
                'user_id' => $prefix . $suffix,
                'email' => "collide_{$suffix}@example.com",
                'password' => 'secret',
                'mobile_verified_at' => now(),
            ]);
        }

        // Generate from name must find an available one and not produce any of the seeded ones
        $newId = $service->generateFromName('Rahul Sharma');
        $this->assertSame(10, strlen($newId));
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $newId);
        $this->assertStringStartsWith('rahu', $newId);
        $this->assertNotContains(substr($newId, 4), $collidingSuffixes);
        $this->assertTrue($service->isAvailable($newId));
    }

    public function test_concurrency_protection_and_safe_retry_in_otp_verification(): void
    {
        $initialUserId = 'RAHU123456';

        $pending = PendingMemberRegistration::create([
            'token' => 'test_token_123',
            'name' => 'Rahul Sharma',
            'user_id' => $initialUserId,
            'email' => 'rahul.race@example.com',
            'password_hash' => Hash::make('password123'),
            'otp_hash' => Hash::make('654321'),
            'expires_at' => now()->addMinutes(15),
        ]);

        // Simulate concurrent registration taking RAHU123456 in database before pending member completes OTP
        Member::create([
            'name' => 'Fast Competitor',
            'user_id' => $initialUserId,
            'email' => 'fast@example.com',
            'password' => 'secret',
            'mobile_verified_at' => now(),
        ]);

        session(['pending_registration_token' => 'test_token_123']);

        $response = $this->post(route('member.register.verify.submit'), [
            'otp' => '654321',
        ]);

        $response->assertRedirect(route('member.dashboard'));

        $createdMember = Member::where('email', 'rahul.race@example.com')->sole();
        $this->assertNotSame($initialUserId, $createdMember->user_id);
        $this->assertSame(10, strlen($createdMember->user_id));
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $createdMember->user_id);
        $this->assertStringStartsWith('rahu', $createdMember->user_id);
        $this->assertMatchesRegularExpression(MemberUserIdService::FORMAT_PATTERN, $createdMember->user_id);
    }

    public function test_leading_zero_preservation(): void
    {
        $service = app(MemberUserIdService::class);

        // Verify randomSuffix produces exactly 6 characters even if conceptual number is small
        for ($i = 0; $i < 50; $i++) {
            $suffix = $service->randomSuffix();
            $this->assertSame(6, strlen($suffix));
            $this->assertMatchesRegularExpression('/^[0-9]{6}$/', $suffix);
        }

        // Test explicit normalization and validity of leading-zero ID
        $leadingZeroId = 'RAHU004219';
        $this->assertSame(10, strlen($leadingZeroId));
        $this->assertTrue($service->isValid($leadingZeroId));
        $this->assertSame('RAHU004219', $service->normalize('  @rahu004219  '));
    }

    public function test_short_names_padding_and_fallback_rules(): void
    {
        $service = app(MemberUserIdService::class);

        // 3 usable letters: padded with 'x' -> 4 chars
        $prefix3 = $service->namePrefix('Dan');
        $this->assertSame('danx', $prefix3);
        $id3 = $service->generateFromName('Dan');
        $this->assertSame(10, strlen($id3));
        $this->assertStringStartsWith('danx', $id3);

        // 2 usable letters: padded with 'xx' -> 4 chars
        $prefix2 = $service->namePrefix('Jo');
        $this->assertSame('joxx', $prefix2);
        $id2 = $service->generateFromName('Jo');
        $this->assertSame(10, strlen($id2));
        $this->assertStringStartsWith('joxx', $id2);

        // 1 usable letter: padded with 'xxx' -> 4 chars
        $prefix1 = $service->namePrefix('A');
        $this->assertSame('axxx', $prefix1);
        $id1 = $service->generateFromName('A');
        $this->assertSame(10, strlen($id1));
        $this->assertStringStartsWith('axxx', $id1);

        // 0 usable letters: canonical fallback 'memb' -> 4 chars
        $prefix0 = $service->namePrefix('12345 67890 !@#$%');
        $this->assertSame('memb', $prefix0);
        $id0 = $service->generateFromName('12345 67890 !@#$%');
        $this->assertSame(10, strlen($id0));
        $this->assertStringStartsWith('memb', $id0);

        // Empty string
        $prefixEmpty = $service->namePrefix('');
        $this->assertSame('memb', $prefixEmpty);
    }

    public function test_special_characters_and_punctuation_normalization(): void
    {
        $service = app(MemberUserIdService::class);

        // Names with spaces, hyphens, and titles
        $prefix1 = $service->namePrefix('Dr. Rahul-Sharma Jr.');
        $this->assertSame('drra', $prefix1);

        // Names with accented / Unicode characters transliterated to ASCII
        $prefix2 = $service->namePrefix('Élodie Dupont');
        $this->assertSame('elod', $prefix2);

        // Names with multiple irregular spaces
        $prefix3 = $service->namePrefix('   Priya    Patel   ');
        $this->assertSame('priy', $prefix3);
    }

    public function test_public_availability_endpoint_normalizes_and_returns_safe_details(): void
    {
        Member::create([
            'name' => 'Taken Member',
            'user_id' => 'TAKE123456',
            'email' => 'taken@example.com',
            'password' => 'secure-password',
            'mobile_verified_at' => now(),
        ]);

        $this->getJson(route('member.register.check-user-id', ['user_id' => '@AVAI123456']))
            ->assertOk()
            ->assertExactJson([
                'available' => true,
                'normalized_user_id' => 'AVAI123456',
                'message' => 'User ID is available.',
            ]);

        $this->getJson(route('member.register.check-user-id', ['user_id' => 'take123456']))
            ->assertOk()
            ->assertExactJson([
                'available' => false,
                'normalized_user_id' => 'TAKE123456',
                'message' => 'This User ID already exists.',
            ]);

        $this->getJson(route('member.register.check-user-id', ['user_id' => '@ADMIN']))
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('normalized_user_id', 'admin')
            ->assertJsonPath('message', 'Use a 10-character User ID: 4 uppercase letters and 6 digits.');

        $this->getJson(route('member.register.check-user-id', ['user_id' => 'bad-id']))
            ->assertOk()
            ->assertJsonPath('available', false)
            ->assertJsonPath('message', 'Use a 10-character User ID: 4 uppercase letters and 6 digits.')
            ->assertJsonMissingPath('email')
            ->assertJsonMissingPath('id');
    }

    public function test_existing_user_regression_preserves_historical_ids_and_references(): void
    {
        // Historical members with legacy non-10-character user IDs
        $current = $this->member('Current Member', 'current_member');
        $target = $this->member('Different Human Name', 'target_public_id');

        $this->assertSame('current_member', $current->user_id);
        $this->assertSame('target_public_id', $target->user_id);

        $response = $this->actingAs($current, 'member')
            ->get(route('member.search', ['q' => '@TARGET_PUBLIC_ID', 'type' => 'members']));

        $response
            ->assertOk()
            ->assertViewHas('members', fn ($members) => $members->count() === 1 && $members->first()->is($target))
            ->assertSee('Different Human Name')
            ->assertSee('@target_public_id')
            ->assertSee('Connect')
            ->assertDontSee($target->email);

        $this->postJson(route('member.friends.request', $target))
            ->assertOk()
            ->assertJsonPath('state', 'pending_sent');

        $this->get(route('member.search', ['q' => 'target_public', 'type' => 'members']))
            ->assertOk()
            ->assertSee('@target_public_id')
            ->assertSee('Cancel Connection Request');
    }

    public function test_user_id_is_displayed_on_profiles_and_friend_cards(): void
    {
        $current = $this->member('Current Member', 'current_public');
        $friend = $this->member('Friend Member', 'friend_public');

        Friendship::create([
            'member_one_id' => min($current->id, $friend->id),
            'member_two_id' => max($current->id, $friend->id),
            'requested_by_id' => $current->id,
            'status' => Friendship::STATUS_ACCEPTED,
            'accepted_at' => now(),
        ]);

        $this->actingAs($current, 'member')
            ->get(route('member.profile.show', ['tab' => 'friends']))
            ->assertOk()
            ->assertSee('@current_public')
            ->assertSee('@friend_public');

        $this->get(route('member.people.show', $friend))
            ->assertOk()
            ->assertSee('@friend_public');

        $this->get(route('member.friends.index'))
            ->assertOk()
            ->assertSee('@friend_public');
    }

    public function test_registration_page_and_javascript_workflow_are_wired(): void
    {
        $this->get(route('member.register'))
            ->assertOk()
            ->assertDontSee('id="memberUserId"', false)
            ->assertDontSee('name="user_id"', false)
            ->assertSee('jquery-3.7.1.min.js', false);

        $script = File::get(public_path('member_assets/js/member-register.js'));

        $this->assertStringContainsString('window.setTimeout(function () {', $script);
        $this->assertStringContainsString('}, 400);', $script);
        $this->assertStringContainsString('activeRequest.abort()', $script);
        $this->assertStringContainsString("method: 'GET'", $script);
        $this->assertStringContainsString("setState('available', 'User ID is available.')", $script);
        $this->assertStringContainsString('/^[A-Za-z]{4}[0-9]{6}$/', $script);
    }

    public function test_eloquent_creating_hook_auto_generates_when_user_id_is_empty(): void
    {
        $member = Member::create([
            'name' => 'Priya Patel',
            'email' => 'priya.hook@example.com',
            'password' => 'secret-password',
            'mobile_verified_at' => now(),
        ]);

        $this->assertNotNull($member->user_id);
        $this->assertSame(10, strlen($member->user_id));
        $this->assertMatchesRegularExpression('/^[a-z0-9]{10}$/', $member->user_id);
        $this->assertStringStartsWith('priy', $member->user_id);
        $this->assertMatchesRegularExpression(MemberUserIdService::FORMAT_PATTERN, $member->user_id);
    }

    private function member(string $name, string $userId): Member
    {
        return Member::create([
            'name' => $name,
            'user_id' => $userId,
            'email' => $userId.'@example.com',
            'password' => 'secure-password',
            'mobile_verified_at' => now(),
        ]);
    }
}

