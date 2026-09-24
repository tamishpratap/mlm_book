<?php

namespace Tests\Feature\ContentModeration;

use App\Models\Admin;
use App\Models\BusinessPage;
use App\Models\Community;
use App\Models\Event;
use App\Models\Member;
use App\Models\Post;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class Phase6UploadIntegrationsTest extends TestCase
{
    use RefreshDatabase;

    private array $createdFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->createdFiles as $path) {
            if (File::exists(public_path($path))) {
                @File::delete(public_path($path));
            }
        }
        parent::tearDown();
    }

    private function createSampleImage(int $width = 300, int $height = 300, string $ext = 'jpg'): UploadedFile
    {
        $dir = storage_path('framework/testing');
        File::ensureDirectoryExists($dir);
        $tempPath = $dir . '/' . uniqid('p6_') . '.' . $ext;
        $im = imagecreatetruecolor($width, $height);
        $col = imagecolorallocate($im, 70, 130, 180);
        imagefill($im, 0, 0, $col);
        imagejpeg($im, $tempPath, 90);
        imagedestroy($im);

        return new UploadedFile($tempPath, 'sample_' . uniqid() . '.' . $ext, 'image/jpeg', null, true);
    }

    private function createDiskFile(string $relativeDir, string $fileName = 'existing_file.jpg'): string
    {
        $dirPath = public_path($relativeDir);
        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true, true);
        }

        $fullPath = $dirPath . '/' . $fileName;
        $im = imagecreatetruecolor(100, 100);
        $col = imagecolorallocate($im, 20, 150, 20);
        imagefill($im, 0, 0, $col);
        imagejpeg($im, $fullPath, 80);
        imagedestroy($im);

        $relativePath = $relativeDir . '/' . $fileName;
        $this->createdFiles[] = $relativePath;

        return $relativePath;
    }

    private function mockSafeModeration(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                $sha256 = $request->header('X-Moderation-SHA256')[0] ?? 'dummy_sha';
                return Http::response([
                    'success' => true,
                    'media_type' => 'image',
                    'predictions' => [
                        ['className' => 'Neutral', 'probability' => 0.95],
                        ['className' => 'Drawing', 'probability' => 0.04],
                        ['className' => 'Sexy', 'probability' => 0.005],
                        ['className' => 'Hentai', 'probability' => 0.003],
                        ['className' => 'Porn', 'probability' => 0.002],
                    ],
                    'dominantClass' => 'Neutral',
                    'confidence' => 0.95,
                    'model' => 'MobileNetV2',
                    'modelVersion' => 'v1',
                    'fileSha256' => $sha256,
                    'durationMs' => 45,
                    'requestId' => 'req_safe',
                ], 200);
            },
        ]);
    }

    private function mockBlockedModeration(): void
    {
        Http::fake([
            '*/v1/moderate/image' => function ($request) {
                $sha256 = $request->header('X-Moderation-SHA256')[0] ?? 'dummy_sha';
                return Http::response([
                    'success' => true,
                    'media_type' => 'image',
                    'predictions' => [
                        ['className' => 'Porn', 'probability' => 0.94],
                        ['className' => 'Neutral', 'probability' => 0.03],
                        ['className' => 'Sexy', 'probability' => 0.02],
                        ['className' => 'Hentai', 'probability' => 0.01],
                        ['className' => 'Drawing', 'probability' => 0.0],
                    ],
                    'dominantClass' => 'Porn',
                    'confidence' => 0.94,
                    'model' => 'MobileNetV2',
                    'modelVersion' => 'v1',
                    'fileSha256' => $sha256,
                    'durationMs' => 50,
                    'requestId' => 'req_blocked',
                ], 200);
            },
        ]);
    }

    private function createTestMember(array $attrs = []): Member
    {
        return Member::create(array_merge([
            'name' => 'Test Member',
            'user_id' => 'u_' . uniqid(),
            'email' => 'member_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ], $attrs));
    }

    private function createTestAdmin(): Admin
    {
        $admin = Admin::create([
            'name' => 'Super Admin',
            'email' => 'superadmin_' . uniqid() . '@example.com',
            'password' => bcrypt('password123'),
        ]);
        $superRole = Role::firstOrCreate(['slug' => 'super-admin'], ['name' => 'Super Admin']);
        $admin->roles()->sync([$superRole->id]);

        return $admin;
    }

    // =========================================================================
    // 1. COMMUNITY MEDIA MODERATION & REPLACEMENT SAFETY
    // =========================================================================

    public function test_community_cover_and_logo_safe_upload_success(): void
    {
        $this->mockSafeModeration();
        $member = $this->createTestMember();
        $community = Community::create([
            'community_id' => 'c_' . uniqid(),
            'owner_id' => $member->id,
            'name' => 'Safe Tech Pioneers',
            'slug' => 'safe-tech-' . uniqid(),
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $this->actingAs($member, 'member');

        $coverFile = $this->createSampleImage();
        $coverRes = $this->postJson("/api/member/community/{$community->slug}/cover", [
            'cover_photo' => $coverFile,
        ]);

        $coverRes->assertOk()->assertJson(['success' => true]);
        $community->refresh();
        $this->assertNotNull($community->cover_photo);
        $this->assertFileExists(public_path($community->cover_photo));
        $this->createdFiles[] = $community->cover_photo;

        $logoFile = $this->createSampleImage();
        $logoRes = $this->postJson("/api/member/community/{$community->slug}/logo", [
            'logo' => $logoFile,
        ]);

        $logoRes->assertOk()->assertJson(['success' => true]);
        $community->refresh();
        $this->assertNotNull($community->logo);
        $this->assertFileExists(public_path($community->logo));
        $this->createdFiles[] = $community->logo;
    }

    public function test_community_cover_blocked_image_throws_422_and_preserves_old_media(): void
    {
        $this->mockBlockedModeration();
        $member = $this->createTestMember();

        $existingCoverPath = $this->createDiskFile('uploads/communities/covers', 'original_cover.jpg');
        $this->assertFileExists(public_path($existingCoverPath));

        $community = Community::create([
            'community_id' => 'c_' . uniqid(),
            'owner_id' => $member->id,
            'name' => 'Preserved Community',
            'slug' => 'preserved-comm-' . uniqid(),
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
            'cover_photo' => $existingCoverPath,
        ]);

        $this->actingAs($member, 'member');

        $blockedFile = $this->createSampleImage();
        $res = $this->postJson("/api/member/community/{$community->slug}/cover", [
            'cover_photo' => $blockedFile,
        ]);

        $res->assertStatus(422);

        // Crucial: Old cover must NOT have been deleted from disk!
        $this->assertFileExists(public_path($existingCoverPath), 'Existing media must remain completely intact if moderation blocks replacement.');
        $community->refresh();
        $this->assertEquals($existingCoverPath, $community->cover_photo);
    }

    // =========================================================================
    // 2. EVENT MEDIA MODERATION & REPLACEMENT SAFETY
    // =========================================================================

    public function test_event_cover_blocked_image_throws_422_and_preserves_old_cover(): void
    {
        $this->mockBlockedModeration();
        $member = $this->createTestMember();

        $existingCoverPath = $this->createDiskFile('uploads/events/covers', 'original_event_cover.jpg');
        $this->assertFileExists(public_path($existingCoverPath));

        $event = Event::create([
            'organizer_id' => $member->id,
            'title' => 'Annual Summit',
            'slug' => 'annual-summit-' . uniqid(),
            'category' => 'Business',
            'event_type' => 'offline',
            'privacy' => 'public',
            'status' => 'published',
            'start_date' => now()->addDays(5)->toDateString(),
            'cover_photo' => $existingCoverPath,
        ]);

        $this->actingAs($member, 'member');

        $blockedFile = $this->createSampleImage();
        $res = $this->putJson("/api/member/events/{$event->id}", [
            'title' => 'Annual Summit Renamed',
            'category' => 'Business',
            'event_type' => 'offline',
            'privacy' => 'public',
            'start_date' => now()->addDays(5)->toDateString(),
            'cover_photo' => $blockedFile,
        ]);

        $res->assertStatus(422);

        // Verification: Existing event cover must NOT have been deleted
        $this->assertFileExists(public_path($existingCoverPath));
        $event->refresh();
        $this->assertEquals($existingCoverPath, $event->cover_photo);
    }

    public function test_event_discussion_post_image_blocked_throws_422_and_creates_zero_posts(): void
    {
        $this->mockBlockedModeration();
        $member = $this->createTestMember();

        $event = Event::create([
            'organizer_id' => $member->id,
            'title' => 'Discussion Forum Event',
            'slug' => 'discussion-forum-' . uniqid(),
            'category' => 'Business',
            'event_type' => 'offline',
            'privacy' => 'public',
            'status' => 'published',
            'start_date' => now()->addDays(5)->toDateString(),
        ]);

        $this->actingAs($member, 'member');

        $blockedFile = $this->createSampleImage();
        $res = $this->postJson("/api/member/events/{$event->id}/posts", [
            'body' => 'Check this discussion image',
            'media' => $blockedFile,
        ]);

        $res->assertStatus(422);
        $this->assertEquals(0, Post::where('event_id', $event->id)->count(), 'Zero post records must be written on moderation violation.');
    }

    // =========================================================================
    // 3. BUSINESS PAGE PHOTO & COVER REPLACEMENT SAFETY
    // =========================================================================

    public function test_business_page_photo_blocked_throws_422_and_preserves_old_photo(): void
    {
        $this->mockBlockedModeration();
        $member = $this->createTestMember();

        $existingLogoPath = $this->createDiskFile('uploads/business_pages/logos', 'original_biz_logo.jpg');
        $this->assertFileExists(public_path($existingLogoPath));

        $page = BusinessPage::create([
            'member_id' => $member->id,
            'page_name' => 'Safe Enterprises',
            'page_username' => 'safe_ent_' . strtolower(uniqid()),
            'slug' => 'safe-enterprises-' . strtolower(uniqid()),
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
            'logo' => $existingLogoPath,
        ]);

        $this->actingAs($member, 'member');

        $blockedFile = $this->createSampleImage();
        $res = $this->postJson("/api/member/business-pages/{$page->slug}/photo", [
            'profile_photo' => $blockedFile,
        ]);

        $res->assertStatus(422);

        // Verification: Existing logo must remain on disk
        $this->assertFileExists(public_path($existingLogoPath));
        $page->refresh();
        $this->assertEquals($existingLogoPath, $page->logo);
    }

    // =========================================================================
    // 4. BUSINESS INBOX IMAGE MODERATION & DOCUMENT BYPASS
    // =========================================================================

    public function test_business_inbox_image_attachment_blocked_throws_422(): void
    {
        $this->mockBlockedModeration();
        $member = $this->createTestMember();
        $customer = $this->createTestMember();

        $page = BusinessPage::create([
            'member_id' => $member->id,
            'page_name' => 'Support Desk',
            'page_username' => 'suppdesk_' . strtolower(uniqid()),
            'slug' => 'support-desk-' . strtolower(uniqid()),
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $conv = \App\Models\BusinessConversation::create([
            'business_page_id' => $page->id,
            'customer_id' => $customer->id,
            'status' => 'active',
            'is_following' => true,
        ]);

        $this->actingAs($member, 'member');

        $blockedFile = $this->createSampleImage();
        $res = $this->postJson("/api/member/business-pages/{$page->slug}/inbox/conversations/{$conv->id}/messages", [
            'message' => 'Attached photo',
            'attachment' => $blockedFile,
        ]);

        $res->assertStatus(422);
        $this->assertEquals(0, \App\Models\BusinessMessage::where('business_conversation_id', $conv->id)->count());
    }

    public function test_business_inbox_document_attachment_bypasses_image_moderation(): void
    {
        $this->mockBlockedModeration(); // Even if image moderation would block, document should bypass safely
        $member = $this->createTestMember();
        $customer = $this->createTestMember();

        $page = BusinessPage::create([
            'member_id' => $member->id,
            'page_name' => 'Doc Support',
            'page_username' => 'docsupp_' . strtolower(uniqid()),
            'slug' => 'doc-support-' . strtolower(uniqid()),
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
        ]);

        $conv = \App\Models\BusinessConversation::create([
            'business_page_id' => $page->id,
            'customer_id' => $customer->id,
            'status' => 'active',
            'is_following' => true,
        ]);

        $this->actingAs($member, 'member');

        $pdfFile = UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf');
        $res = $this->postJson("/api/member/business-pages/{$page->slug}/inbox/conversations/{$conv->id}/messages", [
            'message' => 'Here is the contract PDF',
            'attachment' => $pdfFile,
        ]);

        $res->assertOk()->assertJson(['success' => true]);
        $message = \App\Models\BusinessMessage::where('business_conversation_id', $conv->id)->first();
        $this->assertNotNull($message);
        $this->assertEquals('document', $message->attachment_type);
        if ($message->attachment_path) {
            $this->createdFiles[] = $message->attachment_path;
        }
    }

    // =========================================================================
    // 5. ADMIN MANAGEMENT MODERATION & MEDIA REPLACEMENT SAFETY
    // =========================================================================

    public function test_admin_community_update_blocked_image_throws_422_and_preserves_old_files(): void
    {
        $this->mockBlockedModeration();
        $admin = $this->createTestAdmin();
        $owner = $this->createTestMember();

        $oldLogo = $this->createDiskFile('uploads/communities/logos', 'admin_old_logo.jpg');
        $this->assertFileExists(public_path($oldLogo));

        $community = Community::create([
            'community_id' => 'c_adm_' . uniqid(),
            'owner_id' => $owner->id,
            'name' => 'Admin Monitored Community',
            'slug' => 'admin-monitored-' . uniqid(),
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
            'logo' => $oldLogo,
        ]);

        $this->actingAs($admin, 'admin');

        $blockedFile = $this->createSampleImage();
        $res = $this->putJson("/api/admin/communities/{$community->id}", [
            'name' => 'Admin Monitored Community Renamed',
            'category' => 'Technology',
            'visibility' => 'public',
            'logo' => $blockedFile,
        ]);

        $res->assertStatus(422);
        $this->assertFileExists(public_path($oldLogo));
        $community->refresh();
        $this->assertEquals($oldLogo, $community->logo);
    }

    public function test_admin_event_update_blocked_banner_throws_422_and_preserves_old_files(): void
    {
        $this->mockBlockedModeration();
        $admin = $this->createTestAdmin();
        $owner = $this->createTestMember();

        $oldBanner = $this->createDiskFile('uploads/events/banners', 'admin_old_banner.jpg');
        $this->assertFileExists(public_path($oldBanner));

        $event = Event::create([
            'organizer_id' => $owner->id,
            'title' => 'Admin Event',
            'slug' => 'admin-event-' . uniqid(),
            'category' => 'Business',
            'event_type' => 'offline',
            'privacy' => 'public',
            'status' => 'published',
            'start_date' => now()->addDays(3)->toDateString(),
            'banner' => $oldBanner,
            'cover_photo' => $oldBanner,
        ]);

        $this->actingAs($admin, 'admin');

        $blockedFile = $this->createSampleImage();
        $res = $this->putJson("/api/admin/events/{$event->id}", [
            'title' => 'Admin Event Updated',
            'category' => 'Business',
            'event_type' => 'offline',
            'privacy' => 'public',
            'status' => 'published',
            'start_date' => now()->addDays(3)->toDateString(),
            'banner' => $blockedFile,
        ]);

        $res->assertStatus(422);
        $this->assertFileExists(public_path($oldBanner));
        $event->refresh();
        $this->assertEquals($oldBanner, $event->banner);
    }

    public function test_admin_business_page_update_blocked_cover_throws_422_and_preserves_old_files(): void
    {
        $this->mockBlockedModeration();
        $admin = $this->createTestAdmin();
        $owner = $this->createTestMember();

        $oldCover = $this->createDiskFile('uploads/business_pages/covers', 'admin_old_cover.jpg');
        $this->assertFileExists(public_path($oldCover));

        $page = BusinessPage::create([
            'member_id' => $owner->id,
            'page_name' => 'Admin Verified Brand',
            'page_username' => 'adminbrand_' . strtolower(uniqid()),
            'slug' => 'admin-brand-' . strtolower(uniqid()),
            'category' => 'Technology',
            'visibility' => 'public',
            'status' => 'active',
            'cover_photo' => $oldCover,
        ]);

        $this->actingAs($admin, 'admin');

        $blockedFile = $this->createSampleImage();
        $res = $this->putJson("/api/admin/business-pages/{$page->id}", [
            'page_name' => 'Admin Verified Brand Renamed',
            'category' => 'Technology',
            'visibility' => 'public',
            'cover_photo' => $blockedFile,
        ]);

        $res->assertStatus(422);
        $this->assertFileExists(public_path($oldCover));
        $page->refresh();
        $this->assertEquals($oldCover, $page->cover_photo);
    }

    public function test_admin_post_media_replacement_blocked_image_throws_422_and_preserves_old_media(): void
    {
        $this->mockBlockedModeration();
        $admin = $this->createTestAdmin();
        $member = $this->createTestMember();

        $oldPostMedia = $this->createDiskFile('uploads/posts/images', 'admin_old_post_media.jpg');
        $this->assertFileExists(public_path($oldPostMedia));

        $post = Post::create([
            'member_id' => $member->id,
            'body' => 'Original Post Content',
            'media_path' => $oldPostMedia,
            'media_type' => 'image',
            'visibility' => 'public',
        ]);

        $this->actingAs($admin, 'admin');

        $blockedFile = $this->createSampleImage();
        $res = $this->putJson("/api/admin/posts/{$post->id}", [
            'body' => 'Updated Post Content',
            'media' => $blockedFile,
        ]);

        $res->assertStatus(422);
        $this->assertFileExists(public_path($oldPostMedia));
        $post->refresh();
        $this->assertEquals($oldPostMedia, $post->media_path);
    }
}
