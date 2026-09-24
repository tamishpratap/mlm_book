<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use App\Models\Role;
use App\Models\Story;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStoryMediaTest extends TestCase
{
    use RefreshDatabase;

    protected Admin $admin;
    protected Member $member;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'superadmin_' . uniqid() . '@example.com',
            'password' => 'password123',
        ]);
        $superRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $this->admin->roles()->sync([$superRole->id]);

        $this->member = Member::create([
            'name' => 'Story Author',
            'user_id' => 'author_' . uniqid(),
            'email' => 'author_' . uniqid() . '@example.com',
            'password' => 'password123',
            'status' => 'active',
        ]);
    }

    public function test_story_model_generates_valid_media_url_for_relative_uploads(): void
    {
        $story = Story::create([
            'member_id' => $this->member->id,
            'caption' => 'Test Image Story',
            'media_type' => 'image',
            'media_path' => 'uploads/stories/images/test_image.png',
            'expires_at' => now()->addHours(24),
        ]);

        $this->assertNotNull($story->media_url);
        $this->assertStringStartsWith('http', $story->media_url);
        $this->assertStringEndsWith('/uploads/stories/images/test_image.png', $story->media_url);
        $this->assertTrue($story->isImage());
        $this->assertFalse($story->isVideo());
        $this->assertFalse($story->is_expired);
    }

    public function test_story_model_handles_leading_slash_and_legacy_paths(): void
    {
        $storyWithSlash = Story::create([
            'member_id' => $this->member->id,
            'caption' => 'Leading slash',
            'media_type' => 'image',
            'media_path' => '/uploads/stories/images/slash.png',
            'expires_at' => now()->addHours(24),
        ]);
        $this->assertStringEndsWith('/uploads/stories/images/slash.png', $storyWithSlash->media_url);
        $this->assertStringNotContainsString('//uploads', $storyWithSlash->media_url);

        $storyLegacy = Story::create([
            'member_id' => $this->member->id,
            'caption' => 'Legacy path',
            'media_type' => 'video',
            'media_path' => 'storage/uploads/stories/videos/vid.mp4',
            'expires_at' => now()->addHours(24),
        ]);
        $this->assertStringEndsWith('/uploads/stories/videos/vid.mp4', $storyLegacy->media_url);
        $this->assertTrue($storyLegacy->isVideo());
        $this->assertFalse($storyLegacy->isImage());
    }

    public function test_story_without_media_returns_null_url(): void
    {
        $storyNull = new Story([
            'member_id' => $this->member->id,
            'caption' => 'Text only story',
            'media_type' => 'text',
            'media_path' => null,
            'expires_at' => now()->addHours(24),
        ]);

        $this->assertNull($storyNull->media_url);
        $this->assertFalse($storyNull->isImage());
        $this->assertFalse($storyNull->isVideo());

        $storyEmpty = new Story([
            'member_id' => $this->member->id,
            'caption' => 'Empty path story',
            'media_type' => 'text',
            'media_path' => '',
            'expires_at' => now()->addHours(24),
        ]);

        $this->assertNull($storyEmpty->media_url);
    }

    public function test_admin_member_profile_endpoint_returns_stories_with_media_url(): void
    {
        $story = Story::create([
            'member_id' => $this->member->id,
            'caption' => 'Member Profile Story',
            'media_type' => 'image',
            'media_path' => 'uploads/stories/images/profile_story.jpg',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/members/{$this->member->id}");

        $response->assertOk()
            ->assertJsonPath('member.id', $this->member->id)
            ->assertJsonStructure([
                'member',
                'stories' => [
                    '*' => [
                        'id',
                        'member_id',
                        'caption',
                        'media_type',
                        'media_path',
                        'media_url',
                        'expires_at',
                    ],
                ],
            ]);

        $storiesData = $response->json('stories');
        $this->assertCount(1, $storiesData);
        $this->assertEquals($story->id, $storiesData[0]['id']);
        $this->assertNotNull($storiesData[0]['media_url']);
        $this->assertStringEndsWith('/uploads/stories/images/profile_story.jpg', $storiesData[0]['media_url']);
    }

    public function test_admin_stories_show_endpoint_returns_story_with_media_url(): void
    {
        $story = Story::create([
            'member_id' => $this->member->id,
            'caption' => 'Admin Inspection Story',
            'media_type' => 'video',
            'media_path' => 'uploads/stories/videos/admin_story.mp4',
            'expires_at' => now()->addHours(24),
        ]);

        $response = $this->actingAs($this->admin, 'admin')
            ->getJson("/api/admin/stories/{$story->id}");

        $response->assertOk()
            ->assertJsonPath('story.id', $story->id)
            ->assertJsonPath('story.media_type', 'video')
            ->assertJsonStructure([
                'story' => [
                    'id',
                    'member_id',
                    'caption',
                    'media_type',
                    'media_path',
                    'media_url',
                ],
            ]);

        $storyData = $response->json('story');
        $this->assertNotNull($storyData['media_url']);
        $this->assertStringEndsWith('/uploads/stories/videos/admin_story.mp4', $storyData['media_url']);
    }
}
