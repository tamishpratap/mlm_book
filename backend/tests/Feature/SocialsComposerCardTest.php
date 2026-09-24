<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SocialsComposerCardTest extends TestCase
{
    use RefreshDatabase;

    protected function createMember(array $attributes = []): Member
    {
        static $seq = 500;
        $unique = $seq++;

        return Member::create(array_merge([
            'name' => "Member {$unique}",
            'user_id' => "MEMB{$unique}00",
            'email' => "member{$unique}@example.com",
            'password' => 'password123',
            'mobile_verified_at' => now(),
            'reward_balance' => 0.00,
            'ad_balance' => 100.00,
        ], $attributes));
    }

    public function test_socials_page_renders_composer_card_with_create_post_button_and_no_empty_state_duplicate(): void
    {
        $member = $this->createMember();

        $response = $this->actingAs($member, 'member')
            ->get(route('member.socials'));

        $response->assertOk();

        // 1. Composer card exists
        $response->assertSee('data-post-composer', false);

        // 2. Avatar and prompt area exist
        $response->assertSee('composer__input-row', false);
        $response->assertSee('data-post-body', false);
        $response->assertSee("What's on your mind, {$member->name}?", false);

        // 3. Create Post button exists inside composer card
        $response->assertSee('data-post-create-btn', false);
        $response->assertSee('Create Post', false);

        // 4. Photo / video option exists
        $response->assertSee('Photo / video', false);
        $response->assertSee('data-post-media', false);

        // 5. Post submit button exists
        $response->assertSee('data-post-submit', false);
        $response->assertSee('data-post-submit-label', false);

        // 6. When empty, No Posts Available empty-state is shown but WITHOUT a Create Post button
        $response->assertSee('data-post-empty', false);
        $response->assertSee('No Posts Available', false);
        $response->assertSee('Your feed is quiet right now. Share something or connect with friends!', false);

        // Verify the empty state card itself does not contain Create Post
        $content = $response->getContent();
        preg_match('/<section[^>]*data-post-empty[^>]*>(.*?)<\/section>/s', $content, $matches);
        $this->assertNotEmpty($matches, 'Empty state section not found');
        $this->assertStringNotContainsString('Create Post', $matches[1], 'Empty state must NOT contain Create Post button');
    }

    public function test_member_can_create_text_post_through_existing_flow(): void
    {
        $member = $this->createMember();

        $response = $this->actingAs($member, 'member')
            ->postJson(route('member.posts.store'), [
                'body' => 'Hello from Socials Composer Card!',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('posts', [
            'member_id' => $member->id,
            'body' => 'Hello from Socials Composer Card!',
        ]);
    }

    public function test_member_can_create_photo_post_through_existing_flow(): void
    {
        Storage::fake('public');
        $member = $this->createMember();

        $file = UploadedFile::fake()->image('test_post_image.png', 600, 600);

        $response = $this->actingAs($member, 'member')
            ->postJson(route('member.posts.store'), [
                'body' => 'Photo post test',
                'media' => $file,
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('posts', [
            'member_id' => $member->id,
            'body' => 'Photo post test',
        ]);
    }
}