<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use App\Models\Story;
use App\Models\StoryView;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Tests\TestCase;

class MemberStoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_story_routes_require_member_authentication(): void
    {
        $story = $this->createMember('Story Author')->stories()->create([
            'media_type' => 'image',
            'media_path' => 'uploads/stories/images/example.jpg',
            'expires_at' => now()->addDay(),
        ]);

        $this->post(route('member.stories.store'))
            ->assertRedirect(route('member.login'));
        $this->get(route('member.stories.show', $story))
            ->assertRedirect(route('member.login'));
    }

    public function test_story_requires_one_supported_media_file_with_safe_limits(): void
    {
        $member = $this->createMember('Story Author');

        $this->actingAs($member, 'member')
            ->postJson(route('member.stories.store'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['media' => 'Please select an image or video.']);

        $this->postJson(route('member.stories.store'), [
            'caption' => str_repeat('a', 501),
            'media' => UploadedFile::fake()->image('photo.jpg'),
        ])->assertUnprocessable()->assertJsonValidationErrors('caption');

        $this->postJson(route('member.stories.store'), [
            'media' => UploadedFile::fake()->create('unsafe.svg', 10, 'image/svg+xml'),
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'media' => 'Upload a JPG, PNG, WebP, MP4, WebM, or MOV file.',
        ]);

        $this->postJson(route('member.stories.store'), [
            'media' => UploadedFile::fake()->image('large.jpg')->size(5121),
        ])->assertUnprocessable()->assertJsonValidationErrors([
            'media' => 'The image must not be larger than 5 MB.',
        ]);

        $largeVideo = $this->fakeVideo('large.mp4', 50 * 1024 + 1);

        try {
            $this->postJson(route('member.stories.store'), ['media' => $largeVideo])
                ->assertUnprocessable()
                ->assertJsonValidationErrors([
                    'media' => 'The video must not be larger than 50 MB.',
                ]);
        } finally {
            File::delete($largeVideo->getPathname());
        }

        $this->postJson(route('member.stories.store'), [
            'media' => [
                UploadedFile::fake()->image('first.jpg'),
                UploadedFile::fake()->image('second.jpg'),
            ],
        ])->assertUnprocessable()->assertJsonValidationErrors('media');

        $this->assertDatabaseCount('stories', 0);
    }

    public function test_php_upload_transport_errors_return_a_specific_message(): void
    {
        $member = $this->createMember('Transport Error Author');
        $invalidUpload = new UploadedFile(
            '',
            'blocked.mp4',
            'video/mp4',
            UPLOAD_ERR_INI_SIZE,
            false,
        );

        $this->actingAs($member, 'member')
            ->postJson(route('member.stories.store'), ['media' => $invalidUpload])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'media' => 'The video is larger than the server upload limit.',
            ]);

        $this->assertDatabaseCount('stories', 0);
    }

    public function test_jpg_png_webp_mp4_webm_and_mov_story_uploads_are_stored_safely(): void
    {
        $member = $this->createMember('Format Story Author');
        $storedPaths = [];
        $temporaryPaths = [];
        $mediaFiles = [
            [UploadedFile::fake()->image('story.jpg'), 'image', 'jpg'],
            [UploadedFile::fake()->image('story.png'), 'image', 'png'],
            [$this->fakeMedia('story.webp', 16, "RIFF\x1a\x00\x00\x00WEBPVP8 "), 'image', 'webp'],
            [$this->fakeVideo('story.MP4', 64), 'video', 'mp4'],
            [$this->fakeMedia('story.webm', 64, hex2bin('1a45dfa39f4286810142f7810142f2810442f381084282847765626d4287810242858102')), 'video', 'webm'],
            [$this->fakeMedia('story.MOV', 64, "\x00\x00\x00\x14ftypqt  \x00\x00\x00\x00qt  "), 'video', 'mov'],
        ];

        try {
            foreach ($mediaFiles as [$media, $expectedType, $expectedExtension]) {
                $temporaryPaths[] = $media->getPathname();

                $this->actingAs($member, 'member')
                    ->postJson(route('member.stories.store'), ['media' => $media])
                    ->assertOk()
                    ->assertJsonPath('success', true);

                $story = Story::query()->latest('id')->firstOrFail();
                $storedPaths[] = $story->media_path;

                $this->assertSame($expectedType, $story->media_type);
                $this->assertStringEndsWith('.'.$expectedExtension, $story->media_path);
                $this->assertStringStartsWith('uploads/stories/'.$expectedType.'s/', $story->media_path);
                $this->assertFileExists(public_path($story->media_path));
                $this->assertGreaterThan(0, File::size(public_path($story->media_path)));
            }
        } finally {
            File::delete($temporaryPaths);
            File::delete(array_map(fn ($path) => public_path($path), $storedPaths));
        }

        $this->assertDatabaseCount('stories', 6);
    }

    public function test_database_failure_removes_the_moved_story_file_and_returns_a_safe_error(): void
    {
        Log::spy();
        $member = $this->createMember('Failed Story Author');
        $filesBefore = File::glob(public_path('uploads/stories/images/*'));

        Story::creating(function (): void {
            throw new RuntimeException('Controlled Story database failure.');
        });

        $this->actingAs($member, 'member')
            ->postJson(route('member.stories.store'), [
                'media' => UploadedFile::fake()->image('cleanup.jpg'),
            ])
            ->assertInternalServerError()
            ->assertExactJson([
                'success' => false,
                'message' => 'We could not save your Story. Please try again.',
            ]);

        $this->assertSame($filesBefore, File::glob(public_path('uploads/stories/images/*')));
        $this->assertDatabaseCount('stories', 0);
        Log::shouldHaveReceived('error')
            ->once()
            ->withArgs(fn (string $message, array $context) => $message === 'Story media upload failed'
                && $context['member_id'] === $member->id
                && $context['exception'] === RuntimeException::class
                && $context['uploaded_mime_type'] === 'image/jpeg'
                && $context['uploaded_extension'] === 'jpg'
                && $context['uploaded_size_bytes'] > 0
                && $context['php_upload_error_code'] === UPLOAD_ERR_OK);
    }

    public function test_story_javascript_handles_validation_and_non_json_upload_failures_safely(): void
    {
        $script = File::get(public_path('member_assets/js/member-stories.js'));

        $this->assertStringContainsString('response.status === 413', $script);
        $this->assertStringContainsString('response.status === 419', $script);
        $this->assertStringContainsString('response.status === 422', $script);
        $this->assertStringContainsString('data.errors?.media?.[0]', $script);
        $this->assertStringContainsString("contentType.includes('application/json')", $script);
        $this->assertStringContainsString('body: new FormData(form)', $script);
        $this->assertStringNotContainsString("'Content-Type': 'multipart/form-data'", $script);
        $this->assertStringContainsString("['mp4', 'mov'].includes(extension)", $script);
        $this->assertStringContainsString("submitLabel.textContent = 'Sharing…'", $script);
    }

    public function test_member_can_share_image_and_video_stories_with_ajax_or_normal_fallback(): void
    {
        $member = $this->createMember('Media Story Author');
        $storedPaths = [];
        $video = $this->fakeVideo('clip.mp4', 64);

        try {
            $this->actingAs($member, 'member')
                ->postJson(route('member.stories.store'), [
                    'caption' => 'A real image Story.',
                    'media' => UploadedFile::fake()->image('photo.jpg', 800, 1200),
                ])
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('message', 'Your Story has been shared.')
                ->assertJsonPath('member_id', $member->id)
                ->assertSee('data-story-link', false);

            $imageStory = Story::query()->latest('id')->firstOrFail();
            $storedPaths[] = $imageStory->media_path;
            $this->assertSame($member->id, $imageStory->member_id);
            $this->assertSame('image', $imageStory->media_type);
            $this->assertSame('A real image Story.', $imageStory->caption);
            $this->assertStringStartsWith('uploads/stories/images/story_'.$member->id.'_', $imageStory->media_path);
            $this->assertFileExists(public_path($imageStory->media_path));
            $this->assertEqualsWithDelta(now()->addHours(24)->timestamp, $imageStory->expires_at->timestamp, 5);

            $this->from(route('member.dashboard'))
                ->post(route('member.stories.store'), [
                    'caption' => 'Normal form video Story.',
                    'media' => $video,
                ])
                ->assertRedirect(route('member.dashboard'))
                ->assertSessionHas('success', 'Your Story has been shared.');

            $videoStory = Story::query()->latest('id')->firstOrFail();
            $storedPaths[] = $videoStory->media_path;
            $this->assertSame('video', $videoStory->media_type);
            $this->assertStringStartsWith('uploads/stories/videos/story_'.$member->id.'_', $videoStory->media_path);
            $this->assertFileExists(public_path($videoStory->media_path));
            $this->assertFalse(str_starts_with($videoStory->media_path, public_path()));
        } finally {
            File::delete($video->getPathname());
            File::delete(array_map(fn ($path) => public_path($path), array_filter($storedPaths)));
        }
    }

    public function test_dashboard_shows_latest_active_story_for_self_and_accepted_friends_only(): void
    {
        $member = $this->createMember('Story Viewer');
        $friend = $this->createMember('Visible Friend');
        $pending = $this->createMember('Pending Story Member');
        $rejected = $this->createMember('Rejected Story Member');
        $unrelated = $this->createMember('Unrelated Story Member');
        $this->createFriendship($member, $friend, Friendship::STATUS_ACCEPTED);
        $this->createFriendship($member, $pending, Friendship::STATUS_PENDING);
        $this->createFriendship($member, $rejected, Friendship::STATUS_REJECTED);

        $ownStory = $this->createStory($member, 'Own active Story');
        $this->createStory($friend, 'Older friend Story');
        $friendStory = $this->createStory($friend, 'Latest friend Story');
        $this->createStory($friend, 'Expired friend Story', now()->subMinute());
        $this->createStory($pending, 'Pending hidden Story');
        $this->createStory($rejected, 'Rejected hidden Story');
        $this->createStory($unrelated, 'Unrelated hidden Story');

        $response = $this->actingAs($member, 'member')->get(route('member.dashboard'));
        $stories = $response->viewData('stories');

        $response
            ->assertOk()
            ->assertSee('Create story')
            ->assertSee('Story Viewer')
            ->assertDontSee('story_2.jpg')
            ->assertDontSee('story_3.jpg')
            ->assertDontSee('story_4.png')
            ->assertDontSee('story_5.jpg');

        $this->assertSame([$ownStory->id, $friendStory->id], $stories->pluck('id')->all());
    }

    public function test_story_detail_allows_owner_and_accepted_friend_but_rejects_other_states(): void
    {
        $author = $this->createMember('Story Owner');
        $friend = $this->createMember('Story Friend');
        $pending = $this->createMember('Story Pending');
        $rejected = $this->createMember('Story Rejected');
        $unrelated = $this->createMember('Story Unrelated');
        $story = $this->createStory($author, '<script>alert(1)</script>');
        $expiredStory = $this->createStory($author, 'Expired Story', now()->subSecond());
        $this->createFriendship($author, $friend, Friendship::STATUS_ACCEPTED);
        $this->createFriendship($author, $pending, Friendship::STATUS_PENDING);
        $this->createFriendship($author, $rejected, Friendship::STATUS_REJECTED);

        $this->actingAs($author, 'member')
            ->get(route('member.stories.show', $story))
            ->assertOk()
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
        $this->actingAs($friend, 'member')
            ->getJson(route('member.stories.show', $story))
            ->assertOk()
            ->assertJsonPath('success', true);
        $this->actingAs($pending, 'member')->get(route('member.stories.show', $story))->assertForbidden();
        $this->actingAs($rejected, 'member')->get(route('member.stories.show', $story))->assertForbidden();
        $this->actingAs($unrelated, 'member')->get(route('member.stories.show', $story))->assertForbidden();
        $this->actingAs($author, 'member')->get(route('member.stories.show', $expiredStory))->assertNotFound();
    }

    public function test_story_view_analytics_are_visible_only_to_story_owner(): void
    {
        $author = $this->createMember('Analytics Owner');
        $friend = $this->createMember('Analytics Friend');
        $viewerOne = $this->createMember('Viewer One');
        $viewerTwo = $this->createMember('Viewer Two');
        $this->createFriendship($author, $friend, Friendship::STATUS_ACCEPTED);

        $story = $this->createStory($author, 'Private analytics Story');
        StoryView::create([
            'story_id' => $story->id,
            'viewer_member_id' => $viewerOne->id,
        ]);
        StoryView::create([
            'story_id' => $story->id,
            'viewer_member_id' => $viewerTwo->id,
        ]);

        $ownerResponse = $this->actingAs($author, 'member')
            ->getJson(route('member.stories.show', $story))
            ->assertOk()
            ->assertJsonPath('views_count', 2);
        $this->assertStringContainsString('data-story-viewers-open="'.$story->id.'"', $ownerResponse->json('html'));
        $this->assertStringContainsString('Seen by 2', $ownerResponse->json('html'));

        $this->actingAs($friend, 'member')
            ->getJson(route('member.stories.show', $story))
            ->assertOk()
            ->assertJsonMissingPath('views_count')
            ->assertDontSee('data-story-viewers-open', false)
            ->assertDontSee('data-story-views-count', false)
            ->assertDontSee('Seen by', false)
            ->assertDontSee('Views', false)
            ->assertDontSee('story-viewer__views-badge-readonly', false);

        $this->actingAs($friend, 'member')
            ->getJson(route('member.stories.viewers', $story))
            ->assertForbidden();
    }

    public function test_story_cards_and_profile_story_grid_do_not_render_view_counts_for_non_owners(): void
    {
        $author = $this->createMember('Story Card Owner');
        $friend = $this->createMember('Story Card Friend');
        $viewer = $this->createMember('Story Card Viewer');
        $this->createFriendship($author, $friend, Friendship::STATUS_ACCEPTED);

        $story = $this->createStory($author, 'Card privacy Story');
        StoryView::create([
            'story_id' => $story->id,
            'viewer_member_id' => $viewer->id,
        ]);

        $this->actingAs($author, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('data-story-viewers-open="'.$story->id.'"', false)
            ->assertSee('Seen by 1', false);

        $this->actingAs($friend, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertDontSee('data-story-viewers-open="'.$story->id.'"', false)
            ->assertDontSee('data-story-views-count="'.$story->id.'"', false)
            ->assertDontSee('Seen by 1', false);

        $this->actingAs($author, 'member')
            ->get(route('member.profile.show', ['tab' => 'stories']))
            ->assertOk()
            ->assertSee('story-card-item__stats', false)
            ->assertSee('<i data-lucide="eye" aria-hidden="true"></i> 1', false);

        $this->actingAs($friend, 'member')
            ->get(route('member.people.show', [$author, 'tab' => 'stories']))
            ->assertOk()
            ->assertDontSee('story-card-item__stats', false)
            ->assertDontSee('<i data-lucide="eye" aria-hidden="true"></i> 1', false);
    }

    private function createStory(Member $member, string $caption, $expiresAt = null): Story
    {
        return $member->stories()->create([
            'caption' => $caption,
            'media_type' => 'image',
            'media_path' => 'uploads/stories/images/'.Str::slug($caption).'.jpg',
            'expires_at' => $expiresAt ?? now()->addDay(),
        ]);
    }

    private function fakeVideo(string $name, int $sizeInKilobytes): UploadedFile
    {
        return $this->fakeMedia(
            $name,
            $sizeInKilobytes,
            "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom",
        );
    }

    private function fakeMedia(
        string $name,
        int $sizeInKilobytes,
        string $header,
    ): UploadedFile {
        $path = storage_path('framework/testing/'.Str::random(12).'-'.$name);
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $header);
        $handle = fopen($path, 'c');
        ftruncate($handle, $sizeInKilobytes * 1024);
        fclose($handle);

        return new UploadedFile($path, $name, null, null, true);
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

    private function createMember(string $name): Member
    {
        return Member::create([
            'name' => $name,
            'email' => str($name)->slug().'-'.Str::lower(Str::random(8)).'@example.com',
            'password' => 'secure-password',
        ]);
    }
}
