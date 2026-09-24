<?php

namespace Tests\Feature;

use App\Models\Friendship;
use App\Models\Member;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberPostTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_routes_require_member_authentication(): void
    {
        $post = Post::create([
            'member_id' => $this->createMember('Author')->id,
            'body' => 'Protected post',
        ]);

        $this->post(route('member.posts.store'), ['body' => 'Test'])
            ->assertRedirect(route('member.login'));
        $this->get(route('member.posts.show', $post))
            ->assertRedirect(route('member.login'));
    }

    public function test_member_can_create_text_post_with_ajax_or_normal_form(): void
    {
        $member = $this->createMember('Post Author');

        $this->actingAs($member, 'member')
            ->postJson(route('member.posts.store'), ['body' => 'A real database post.'])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Your post has been published.')
            ->assertSee('A real database post.', false);

        $this->assertDatabaseHas('posts', [
            'member_id' => $member->id,
            'body' => 'A real database post.',
            'media_type' => null,
            'media_path' => null,
        ]);

        $this->from(route('member.dashboard'))
            ->post(route('member.posts.store'), ['body' => 'Normal form post.'])
            ->assertRedirect(route('member.dashboard'))
            ->assertSessionHas('success', 'Your post has been published.');
    }

    public function test_post_requires_text_or_one_supported_media_file(): void
    {
        $member = $this->createMember('Post Author');

        $this->actingAs($member, 'member')
            ->postJson(route('member.posts.store'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body', 'media']);

        $this->postJson(route('member.posts.store'), [
            'media' => UploadedFile::fake()->create('unsafe.svg', 10, 'image/svg+xml'),
        ])->assertUnprocessable()->assertJsonValidationErrors('media');

        $this->postJson(route('member.posts.store'), [
            'media' => UploadedFile::fake()->image('large.jpg')->size(5121),
        ])->assertUnprocessable()->assertJsonValidationErrors('media');

        $largeVideo = $this->fakeVideo('large.mp4', 60 * 1024 + 1);

        try {
            $this->postJson(route('member.posts.store'), ['media' => $largeVideo])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('media');
        } finally {
            File::delete($largeVideo->getPathname());
        }

        $this->assertDatabaseCount('posts', 0);
    }

    public function test_member_can_create_image_and_video_posts_with_safe_relative_paths(): void
    {
        $member = $this->createMember('Media Author');
        $storedPaths = [];
        $video = $this->fakeVideo('clip.mp4', 64);

        try {
            $this->actingAs($member, 'member')
                ->postJson(route('member.posts.store'), [
                    'media' => UploadedFile::fake()->image('photo.jpg', 800, 600),
                ])
                ->assertOk()
                ->assertJsonPath('success', true);

            $imagePost = Post::query()->latest('id')->firstOrFail();
            $storedPaths[] = $imagePost->media_path;
            $this->assertSame('image', $imagePost->media_type);
            $this->assertStringStartsWith('uploads/posts/images/post_'.$member->id.'_', $imagePost->media_path);
            $this->assertFileExists(public_path($imagePost->media_path));
            $this->assertFalse(str_starts_with($imagePost->media_path, public_path()));

            $this->postJson(route('member.posts.store'), [
                'body' => 'Video with text',
                'media' => $video,
            ])->assertOk()->assertJsonPath('success', true);

            $videoPost = Post::query()->latest('id')->firstOrFail();
            $storedPaths[] = $videoPost->media_path;
            $this->assertSame('video', $videoPost->media_type);
            $this->assertStringStartsWith('uploads/posts/videos/post_'.$member->id.'_', $videoPost->media_path);
            $this->assertFileExists(public_path($videoPost->media_path));
        } finally {
            File::delete($video->getPathname());
            File::delete(array_map(fn ($path) => public_path($path), array_filter($storedPaths)));
        }
    }

    public function test_dashboard_feed_contains_only_own_and_accepted_friend_posts_newest_first(): void
    {
        $member = $this->createMember('Feed Member');
        $friend = $this->createMember('Accepted Friend');
        $pending = $this->createMember('Pending Member');
        $unrelated = $this->createMember('Unrelated Member');
        $this->createFriendship($member, $friend, Friendship::STATUS_ACCEPTED);
        $this->createFriendship($member, $pending, Friendship::STATUS_PENDING);

        $ownPost = $member->posts()->create(['body' => 'My older post']);
        $friendPost = $friend->posts()->create(['body' => 'Friend newest post']);
        $pending->posts()->create(['body' => 'Pending hidden post']);
        $unrelated->posts()->create(['body' => 'Unrelated hidden post']);
        $friendPost->update(['created_at' => now()->addSecond()]);

        $response = $this->actingAs($member, 'member')
            ->get(route('member.dashboard'));
        $posts = $response->viewData('posts');

        $response
            ->assertOk()
            ->assertViewHas('posts')
            ->assertSeeInOrder(['Friend newest post', 'My older post'])
            ->assertDontSee('Pending hidden post')
            ->assertDontSee('Unrelated hidden post')
            ->assertDontSee('1.2K')
            ->assertDontSee('128 comments');

        $this->assertSame(2, $posts->total());
        $this->assertSame([$friendPost->id, $ownPost->id], $posts->pluck('id')->all());
    }

    public function test_post_detail_allows_owner_and_accepted_friend_only(): void
    {
        $author = $this->createMember('Author');
        $friend = $this->createMember('Friend');
        $unrelated = $this->createMember('Unrelated');
        $post = $author->posts()->create(['body' => 'Friends-only detail']);
        $this->createFriendship($author, $friend, Friendship::STATUS_ACCEPTED);

        $this->actingAs($author, 'member')->get(route('member.posts.show', $post))->assertOk();
        $this->actingAs($friend, 'member')->get(route('member.posts.show', $post))->assertOk();
        $this->actingAs($unrelated, 'member')->get(route('member.posts.show', $post))->assertForbidden();
    }

    public function test_profile_posts_statistic_uses_real_posts_count(): void
    {
        $member = $this->createMember('Profile Author');
        $member->posts()->createMany([
            ['body' => 'First profile post'],
            ['body' => 'Second profile post'],
        ]);

        $this->actingAs($member, 'member')
            ->get(route('member.profile.show'))
            ->assertOk()
            ->assertSee('<strong>2</strong><small>Posts</small>', false);
    }

    private function fakeVideo(string $name, int $sizeInKilobytes): UploadedFile
    {
        $path = storage_path('framework/testing/'.Str::random(12).'-'.$name);
        File::ensureDirectoryExists(dirname($path));

        if ($sizeInKilobytes <= 1024) {
            $ffmpeg = (new \App\Http\Controllers\VideoCompressionController())->resolveFfmpegBinary() ?? 'ffmpeg';
            $process = new \Symfony\Component\Process\Process([
                $ffmpeg, '-y', '-f', 'lavfi', '-i', 'testsrc=duration=1:size=320x240:rate=24',
                '-c:v', 'libx264', '-pix_fmt', 'yuv420p', $path
            ]);
            $process->run();
            if (! File::exists($path) || filesize($path) === 0) {
                File::put($path, "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom");
            }
        } else {
            File::put($path, "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom");
            $handle = fopen($path, 'c');
            ftruncate($handle, $sizeInKilobytes * 1024);
            fclose($handle);
        }

        return new UploadedFile($path, $name, 'video/mp4', null, true);
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
        ]);
    }

    private function createMember(string $name): Member
    {
        return Member::create([
            'name' => $name,
            'email' => str($name)->slug().'-'.Str::lower(Str::random(8)).'@example.com',
            'phone' => '+9198765' . random_int(10000, 99999),
            'mobile_verified_at' => now(),
            'password' => 'secure-password',
        ]);
    }
}
