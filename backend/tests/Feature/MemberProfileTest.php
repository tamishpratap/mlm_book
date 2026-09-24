<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MemberProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_member_can_open_all_profile_pages(): void
    {
        $member = $this->createMember();

        $this->actingAs($member, 'member');

        $this->get(route('member.dashboard'))->assertOk()->assertSee('data-profile-menu', false);
        $this->get(route('member.profile.show'))->assertOk()->assertSee($member->email);
        $this->get(route('member.profile.edit'))->assertOk()->assertSee('Edit Profile');
        $this->get(route('member.account.settings'))->assertOk()->assertSee('Account Settings');
        $this->get(route('member.account.security'))->assertOk()->assertSee('Password &amp; Security', false);
    }

    public function test_member_can_update_profile_details_without_changing_account_fields(): void
    {
        $member = $this->createMember([
            'google_id' => 'google-member-123',
        ]);

        $this->actingAs($member, 'member')
            ->put(route('member.profile.update'), [
                'name' => 'Updated Member',
                'phone' => '+91 98765 43210',
                'bio' => 'A short profile biography.',
                'date_of_birth' => '2000-01-15',
                'gender' => 'prefer_not_to_say',
                'city' => 'Pune',
                'country' => 'India',
                'website' => 'https://example.com',
            ])
            ->assertRedirect(route('member.profile.show'))
            ->assertSessionHas('success');

        $member->refresh();

        $this->assertSame('Updated Member', $member->name);
        $this->assertSame('A short profile biography.', $member->bio);
        $this->assertSame('member@example.com', $member->email);
        $this->assertSame('google-member-123', $member->google_id);
    }

    public function test_account_name_and_password_can_be_updated_without_directly_changing_email(): void
    {
        $member = $this->createMember([
            'google_id' => 'google-member-123',
        ]);
        $this->actingAs($member, 'member')
            ->put(route('member.account.settings.update'), [
                'name' => 'Account Name',
                'email' => 'unverified-change@example.com',
            ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $member->refresh();

        $this->assertSame('Account Name', $member->name);
        $this->assertSame('member@example.com', $member->email);
        $this->assertSame('google-member-123', $member->google_id);

        $this->from(route('member.account.security'))
            ->put(route('member.account.password.update'), [
                'current_password' => 'incorrect-password',
                'password' => 'new-secure-password',
                'password_confirmation' => 'new-secure-password',
            ])
            ->assertSessionHasErrors('current_password');

        $this->put(route('member.account.password.update'), [
            'current_password' => 'old-password',
            'password' => 'new-secure-password',
            'password_confirmation' => 'new-secure-password',
        ])
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $this->assertTrue(Hash::check('new-secure-password', $member->fresh()->password));
        $this->assertAuthenticatedAs($member->fresh(), 'member');
    }

    public function test_profile_statistics_show_zero_posts_and_only_accepted_friends(): void
    {
        $member = $this->createMember();
        $acceptedFriend = $this->createMember(['email' => 'accepted@example.com']);
        $pendingFriend = $this->createMember(['email' => 'pending@example.com']);
        $rejectedFriend = $this->createMember(['email' => 'rejected@example.com']);

        $this->createFriendship($member, $acceptedFriend, Friendship::STATUS_ACCEPTED);
        $this->createFriendship($member, $pendingFriend, Friendship::STATUS_PENDING);
        $this->createFriendship($member, $rejectedFriend, Friendship::STATUS_REJECTED);

        $this->actingAs($member, 'member')
            ->get(route('member.profile.show'))
            ->assertOk()
            ->assertSee('<strong>0</strong><small>Posts</small>', false)
            ->assertSee('<strong>1</strong><small>Connections</small>', false)
            ->assertSee('href="'.route('member.friends.index').'"', false)
            ->assertDontSee('profile-metric--followers')
            ->assertDontSee('profile-metric--following')
            ->assertDontSee('profile-metric--connections')
            ->assertDontSee('profile-metric--groups');
    }

    public function test_public_profile_hides_post_count_from_non_friends(): void
    {
        $viewer = $this->createMember();
        $profileMember = $this->createMember(['email' => 'profile@example.com']);
        $profileFriend = $this->createMember(['email' => 'profile-friend@example.com']);
        $viewerFriend = $this->createMember(['email' => 'viewer-friend@example.com']);
        $pendingFriend = $this->createMember(['email' => 'profile-pending@example.com']);

        $this->createFriendship($profileMember, $profileFriend, Friendship::STATUS_ACCEPTED);
        $this->createFriendship($profileMember, $pendingFriend, Friendship::STATUS_PENDING);
        $this->createFriendship($viewer, $viewerFriend, Friendship::STATUS_ACCEPTED);

        $this->actingAs($viewer, 'member')
            ->get(route('member.people.show', $profileMember))
            ->assertOk()
            ->assertSee('<strong aria-label="Private">&mdash;</strong><small>Private Posts</small>', false)
            ->assertSee('<strong>1</strong><small>Friends</small>', false)
            ->assertSee('href="'.route('member.people.friends', $profileMember).'"', false);
    }

    public function test_public_profile_shows_real_post_count_to_accepted_friends(): void
    {
        $viewer = $this->createMember();
        $profileMember = $this->createMember(['email' => 'friend-profile@example.com']);
        $this->createFriendship($viewer, $profileMember, Friendship::STATUS_ACCEPTED);
        $profileMember->posts()->createMany([
            ['body' => 'First private post'],
            ['body' => 'Second private post'],
        ]);

        $this->actingAs($viewer, 'member')
            ->get(route('member.people.show', $profileMember))
            ->assertOk()
            ->assertSee('<strong>2</strong><small>Posts</small>', false)
            ->assertDontSee('Private Posts');
    }

    public function test_member_can_replace_and_remove_profile_and_cover_photos(): void
    {
        $member = $this->createMember();
        $legacyProfilePhoto = "member/profile-photos/legacy-profile-{$member->id}.jpg";
        $legacyCoverPhoto = "member/cover-photos/legacy-cover-{$member->id}.jpg";
        $legacyProfilePath = storage_path('app/public/'.$legacyProfilePhoto);
        $legacyCoverPath = storage_path('app/public/'.$legacyCoverPhoto);
        $createdPublicPhotos = [];

        File::ensureDirectoryExists(dirname($legacyProfilePath));
        File::ensureDirectoryExists(dirname($legacyCoverPath));
        File::put($legacyProfilePath, 'old profile');
        File::put($legacyCoverPath, 'old cover');
        $member->update([
            'profile_photo' => $legacyProfilePhoto,
            'cover_photo' => $legacyCoverPhoto,
        ]);

        try {
            $this->actingAs($member, 'member')
                ->get(route('member.profile.show'))
                ->assertOk();

            $member->refresh();
            $migratedProfilePhoto = $member->profile_photo;
            $migratedCoverPhoto = $member->cover_photo;
            $createdPublicPhotos = [$migratedProfilePhoto, $migratedCoverPhoto];

            $this->assertStringStartsWith('uploads/profile/', $migratedProfilePhoto);
            $this->assertStringStartsWith('uploads/cover/', $migratedCoverPhoto);
            $this->assertFileExists(public_path($migratedProfilePhoto));
            $this->assertFileExists(public_path($migratedCoverPhoto));
            $this->assertFileExists($legacyProfilePath);
            $this->assertFileExists($legacyCoverPath);

            $this->post(route('member.profile.photo.update'), [
                'profile_photo' => UploadedFile::fake()->image('profile.jpg', 400, 400),
            ])->assertSessionHasNoErrors();

            $member->refresh();
            $newProfilePhoto = $member->profile_photo;
            $createdPublicPhotos[] = $newProfilePhoto;

            $this->assertStringStartsWith('uploads/profile/profile_'.$member->id.'_', $newProfilePhoto);
            $this->assertFileExists(public_path($newProfilePhoto));
            $this->assertFileDoesNotExist(public_path($migratedProfilePhoto));
            $this->get(route('member.profile.show'))
                ->assertOk()
                ->assertSee(asset($newProfilePhoto).'?v=', false);

            $this->post(route('member.profile.cover.update'), [
                'cover_photo' => UploadedFile::fake()->image('cover.webp', 1200, 500),
            ])->assertSessionHasNoErrors();

            $member->refresh();
            $newCoverPhoto = $member->cover_photo;
            $createdPublicPhotos[] = $newCoverPhoto;

            $this->assertStringStartsWith('uploads/cover/cover_'.$member->id.'_', $newCoverPhoto);
            $this->assertFileExists(public_path($newCoverPhoto));
            $this->assertFileDoesNotExist(public_path($migratedCoverPhoto));
            $this->get(route('member.profile.show'))
                ->assertOk()
                ->assertSee(asset($newCoverPhoto).'?v=', false);

            $this->delete(route('member.profile.photo.remove'))->assertSessionHas('success');
            $this->delete(route('member.profile.cover.remove'))->assertSessionHas('success');

            $member->refresh();

            $this->assertNull($member->profile_photo);
            $this->assertNull($member->cover_photo);
            $this->assertFileDoesNotExist(public_path($newProfilePhoto));
            $this->assertFileDoesNotExist(public_path($newCoverPhoto));
        } finally {
            File::delete($legacyProfilePath, $legacyCoverPath);
            File::delete(array_map(fn ($path) => public_path($path), array_filter($createdPublicPhotos)));
        }
    }

    public function test_profile_validation_rejects_invalid_website_and_image_type(): void
    {
        $member = $this->createMember();

        $this->actingAs($member, 'member')
            ->from(route('member.profile.edit'))
            ->put(route('member.profile.update'), [
                'name' => 'Test Member',
                'website' => 'not-a-valid-url',
            ])
            ->assertRedirect(route('member.profile.edit'))
            ->assertSessionHasErrors('website');

        $this->from(route('member.profile.show'))
            ->post(route('member.profile.photo.update'), [
                'profile_photo' => UploadedFile::fake()->create('profile.svg', 20, 'image/svg+xml'),
            ])
            ->assertRedirect(route('member.profile.show'))
            ->assertSessionHasErrors('profile_photo');

        $this->from(route('member.profile.show'))
            ->post(route('member.profile.photo.update'), [
                'profile_photo' => UploadedFile::fake()->image('large-profile.jpg')->size(6000),
            ])
            ->assertSessionHasErrors('profile_photo');

        $this->from(route('member.profile.show'))
            ->post(route('member.profile.cover.update'), [
                'cover_photo' => UploadedFile::fake()->image('large-cover.jpg')->size(12000),
            ])
            ->assertSessionHasErrors('cover_photo');
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Test Member',
            'email' => 'member@example.com',
            'password' => 'old-password',
        ], $attributes));
    }

    private function createFriendship(Member $first, Member $second, string $status): Friendship
    {
        [$memberOneId, $memberTwoId] = Friendship::normalizePair($first->id, $second->id);

        return Friendship::create([
            'member_one_id' => $memberOneId,
            'member_two_id' => $memberTwoId,
            'requested_by_id' => $first->id,
            'status' => $status,
            'accepted_at' => $status === Friendship::STATUS_ACCEPTED ? now() : null,
            'rejected_at' => $status === Friendship::STATUS_REJECTED ? now() : null,
        ]);
    }
}
