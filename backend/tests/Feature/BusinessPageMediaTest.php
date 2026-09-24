<?php

namespace Tests\Feature;

use App\Models\BusinessPage;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class BusinessPageMediaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function test_business_page_owner_can_upload_and_replace_profile_photo(): void
    {
        $owner = $this->createMember(['email' => 'owner@example.com']);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        $photoFile = UploadedFile::fake()->image('logo.png', 400, 400);

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/photo", [
            'profile_photo' => $photoFile,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Business Page profile photo updated successfully!',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'photo_url',
                'logo',
                'business_page' => ['id', 'logo', 'logo_url'],
            ]);

        $businessPage->refresh();
        $firstLogoPath = $businessPage->logo;

        $this->assertNotNull($firstLogoPath);
        $this->assertStringStartsWith('uploads/business_pages/logos/', $firstLogoPath);
        $this->assertFileExists(public_path($firstLogoPath));

        // Test replacement cleans up previous file
        $replacementFile = UploadedFile::fake()->image('new-logo.webp', 500, 500);

        $replaceResponse = $this->postJson("/api/member/business-pages/{$businessPage->slug}/photo", [
            'profile_photo' => $replacementFile,
        ]);

        $replaceResponse->assertOk();

        $businessPage->refresh();
        $newLogoPath = $businessPage->logo;

        $this->assertNotSame($firstLogoPath, $newLogoPath);
        $this->assertFileExists(public_path($newLogoPath));
        $this->assertFileDoesNotExist(public_path($firstLogoPath));

        // Cleanup
        if (File::exists(public_path($newLogoPath))) {
            File::delete(public_path($newLogoPath));
        }
    }

    public function test_business_page_owner_can_upload_and_replace_cover_photo(): void
    {
        $owner = $this->createMember(['email' => 'owner-cover@example.com']);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        $coverFile = UploadedFile::fake()->image('cover.jpg', 1200, 400);

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/cover", [
            'cover_photo' => $coverFile,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Business Page cover photo updated successfully!',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'photo_url',
                'cover_photo',
                'business_page' => ['id', 'cover_photo', 'cover_url'],
            ]);

        $businessPage->refresh();
        $firstCoverPath = $businessPage->cover_photo;

        $this->assertNotNull($firstCoverPath);
        $this->assertStringStartsWith('uploads/business_pages/covers/', $firstCoverPath);
        $this->assertFileExists(public_path($firstCoverPath));

        // Test replacement
        $replacementCover = UploadedFile::fake()->image('new-cover.png', 1600, 500);

        $replaceResponse = $this->postJson("/api/member/business-pages/{$businessPage->slug}/cover", [
            'cover_photo' => $replacementCover,
        ]);

        $replaceResponse->assertOk();

        $businessPage->refresh();
        $newCoverPath = $businessPage->cover_photo;

        $this->assertNotSame($firstCoverPath, $newCoverPath);
        $this->assertFileExists(public_path($newCoverPath));
        $this->assertFileDoesNotExist(public_path($firstCoverPath));

        // Cleanup
        if (File::exists(public_path($newCoverPath))) {
            File::delete(public_path($newCoverPath));
        }
    }

    public function test_business_page_owner_can_remove_photos(): void
    {
        $owner = $this->createMember(['email' => 'owner-remove@example.com']);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        // Upload logo and cover
        $this->postJson("/api/member/business-pages/{$businessPage->slug}/photo", [
            'profile_photo' => UploadedFile::fake()->image('logo.jpg', 300, 300),
        ])->assertOk();

        $this->postJson("/api/member/business-pages/{$businessPage->slug}/cover", [
            'cover_photo' => UploadedFile::fake()->image('cover.jpg', 800, 300),
        ])->assertOk();

        $businessPage->refresh();
        $logoPath = $businessPage->logo;
        $coverPath = $businessPage->cover_photo;

        $this->assertFileExists(public_path($logoPath));
        $this->assertFileExists(public_path($coverPath));

        // Remove profile photo
        $this->deleteJson("/api/member/business-pages/{$businessPage->slug}/photo")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Business Page profile photo removed.',
            ]);

        $businessPage->refresh();
        $this->assertNull($businessPage->logo);
        $this->assertFileDoesNotExist(public_path($logoPath));

        // Remove cover photo
        $this->deleteJson("/api/member/business-pages/{$businessPage->slug}/cover")
            ->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Business Page cover photo removed.',
            ]);

        $businessPage->refresh();
        $this->assertNull($businessPage->cover_photo);
        $this->assertFileDoesNotExist(public_path($coverPath));
    }

    public function test_visitor_cannot_upload_or_remove_photos(): void
    {
        $owner = $this->createMember(['email' => 'owner-secure@example.com']);
        $visitor = $this->createMember(['email' => 'visitor@example.com']);
        $businessPage = $this->createBusinessPage($owner);

        // Visitor attempts to upload photo
        $this->actingAs($visitor, 'member');

        $this->postJson("/api/member/business-pages/{$businessPage->slug}/photo", [
            'profile_photo' => UploadedFile::fake()->image('hack-logo.png', 300, 300),
        ])->assertStatus(403);

        $this->postJson("/api/member/business-pages/{$businessPage->slug}/cover", [
            'cover_photo' => UploadedFile::fake()->image('hack-cover.png', 300, 300),
        ])->assertStatus(403);

        $this->deleteJson("/api/member/business-pages/{$businessPage->slug}/photo")
            ->assertStatus(403);

        $this->deleteJson("/api/member/business-pages/{$businessPage->slug}/cover")
            ->assertStatus(403);
    }

    public function test_photo_and_cover_upload_validation_rejects_invalid_files(): void
    {
        $owner = $this->createMember(['email' => 'owner-val@example.com']);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        // Non-image file
        $this->postJson("/api/member/business-pages/{$businessPage->slug}/photo", [
            'profile_photo' => UploadedFile::fake()->create('script.php', 10, 'text/x-php'),
        ])->assertStatus(422);

        // Oversized profile photo (> 5MB)
        $this->postJson("/api/member/business-pages/{$businessPage->slug}/photo", [
            'profile_photo' => UploadedFile::fake()->image('huge.png')->size(6000),
        ])->assertStatus(422);

        // Oversized cover photo (> 10MB)
        $this->postJson("/api/member/business-pages/{$businessPage->slug}/cover", [
            'cover_photo' => UploadedFile::fake()->image('huge-cover.jpg')->size(12000),
        ])->assertStatus(422);
    }

    public function test_business_page_api_serializes_logo_url_and_cover_url(): void
    {
        $owner = $this->createMember(['email' => 'owner-serial@example.com']);
        $businessPage = $this->createBusinessPage($owner, [
            'logo' => 'uploads/business_pages/logos/sample-logo.png',
            'cover_photo' => 'uploads/business_pages/covers/sample-cover.jpg',
        ]);

        $this->actingAs($owner, 'member');

        $response = $this->getJson("/api/member/business-pages/{$businessPage->slug}");

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'business_page' => [
                    'id',
                    'slug',
                    'logo',
                    'cover_photo',
                    'logo_url',
                    'cover_url',
                    'initials',
                ],
            ]);
    }

    public function test_business_page_owner_can_publish_text_only_normal_post(): void
    {
        $owner = $this->createMember(['email' => 'owner-post-text@example.com', 'mobile_verified_at' => now()]);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/posts", [
            'body' => 'Exciting updates are coming to our business page!',
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'message' => 'Post published to ' . $businessPage->page_name . ' timeline!',
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'post_id',
                'post' => [
                    'id',
                    'body',
                    'media_type',
                    'media_path',
                    'business_page_id',
                    'member_id',
                ],
            ]);

        $this->assertDatabaseHas('posts', [
            'business_page_id' => $businessPage->id,
            'member_id' => $owner->id,
            'body' => 'Exciting updates are coming to our business page!',
            'media_type' => null,
            'media_path' => null,
        ]);
    }

    public function test_business_page_owner_can_publish_image_post_and_returns_post_object_with_media_path(): void
    {
        $owner = $this->createMember(['email' => 'owner-post-img@example.com', 'mobile_verified_at' => now()]);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        $imageFile = UploadedFile::fake()->image('announcement.png', 800, 600);

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/posts", [
            'body' => 'Check out our new products in stock!',
            'media' => $imageFile,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'message',
                'post_id',
                'post' => [
                    'id',
                    'body',
                    'media_type',
                    'media_path',
                    'media_url',
                    'business_page_id',
                ],
            ]);

        $postId = $response->json('post_id');
        $this->assertDatabaseHas('posts', [
            'id' => $postId,
            'business_page_id' => $businessPage->id,
            'media_type' => 'image',
        ]);

        $post = \App\Models\Post::find($postId);
        $this->assertNotNull($post->media_path);
        $this->assertStringStartsWith('uploads/posts/images/', $post->media_path);
        $this->assertFileExists(public_path($post->media_path));

        // Cleanup
        if (File::exists(public_path($post->media_path))) {
            File::delete(public_path($post->media_path));
        }
    }

    public function test_business_page_owner_can_publish_video_post_and_returns_post_object_with_media_path(): void
    {
        $owner = $this->createMember(['email' => 'owner-post-vid@example.com', 'mobile_verified_at' => now()]);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        $videoFile = UploadedFile::fake()->create('intro.mp4', 1024, 'video/mp4');

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/posts", [
            'body' => 'Watch our team introduction video!',
            'media' => $videoFile,
        ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $postId = $response->json('post_id');
        $post = \App\Models\Post::find($postId);
        $this->assertEquals('video', $post->media_type);
        $this->assertNotNull($post->media_path);
        $this->assertStringStartsWith('uploads/posts/videos/', $post->media_path);
        $this->assertFileExists(public_path($post->media_path));

        // Cleanup
        if (File::exists(public_path($post->media_path))) {
            File::delete(public_path($post->media_path));
        }
    }

    public function test_normal_post_does_not_create_ad_campaign_or_deduct_balances(): void
    {
        $owner = $this->createMember(['email' => 'owner-no-ad@example.com', 'mobile_verified_at' => now()]);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($owner, 'member');

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/posts", [
            'body' => 'A completely organic normal business update post.',
        ]);

        $response->assertOk();
        $postId = $response->json('post_id');

        // Verify no ad campaign was created for this post
        $this->assertDatabaseMissing('ad_campaigns', [
            'post_id' => $postId,
        ]);
        $this->assertDatabaseMissing('ad_campaigns', [
            'business_page_id' => $businessPage->id,
        ]);
    }

    public function test_visitor_cannot_publish_post_to_business_page(): void
    {
        $owner = $this->createMember(['email' => 'owner-locked@example.com', 'mobile_verified_at' => now()]);
        $visitor = $this->createMember(['email' => 'visitor-blocked@example.com', 'mobile_verified_at' => now()]);
        $businessPage = $this->createBusinessPage($owner);

        $this->actingAs($visitor, 'member');

        $response = $this->postJson("/api/member/business-pages/{$businessPage->slug}/posts", [
            'body' => 'Attempting unauthorized posting to this business page',
        ]);

        $response->assertStatus(403);
    }

    private function createMember(array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Test Member',
            'email' => 'member-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ], $attributes));
    }

    private function createBusinessPage(Member $owner, array $attributes = []): BusinessPage
    {
        return BusinessPage::create(array_merge([
            'member_id' => $owner->id,
            'page_name' => 'Crypto Solutions ' . uniqid(),
            'page_username' => 'cryptosol_' . strtolower(uniqid()),
            'slug' => 'crypto-solutions-' . strtolower(uniqid()),
            'category' => 'Crypto, Forex & FinTech MLM',
            'description' => 'A comprehensive business description for testing purposes.',
            'email' => 'biz@example.com',
            'phone' => '+919876543210',
            'country' => 'India',
            'state' => 'Maharashtra',
            'city' => 'Mumbai',
            'address' => '123 Business Street',
            'visibility' => 'public',
            'status' => 'active',
        ], $attributes));
    }
}
