<?php

namespace Tests\Feature;

use App\Models\BusinessPage;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessPageOptionalAddressTest extends TestCase
{
    use RefreshDatabase;

    private function createMember(): Member
    {
        return Member::create([
            'name' => 'Test Member',
            'email' => 'test-' . uniqid() . '@example.com',
            'password' => 'secret123',
            'mobile_verified_at' => now(),
        ]);
    }

    public function test_business_page_can_be_created_with_empty_address_city_and_state(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $response = $this->postJson('/api/member/business-pages', [
            'page_name' => 'Acme Corporation',
            'category' => 'E-Commerce & Affiliate MLM',
            'description' => 'This is a valid business page description with more than twenty characters.',
            'email' => 'acme@example.com',
            'phone_country_code' => '+91',
            'phone_number' => '9876543210',
            'country' => 'India',
            'address' => '',
            'city' => '',
            'state' => '',
            'visibility' => 'public',
        ]);

        $response->assertCreated()
            ->assertJson([
                'success' => true,
                'message' => 'Business Page created successfully!',
            ]);

        $this->assertDatabaseHas('business_pages', [
            'page_name' => 'Acme Corporation',
            'country' => 'India',
            'address' => null,
            'city' => null,
            'state' => null,
        ]);
    }

    public function test_country_remains_required_when_creating_business_page(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $response = $this->postJson('/api/member/business-pages', [
            'page_name' => 'Acme Corporation Without Country',
            'category' => 'E-Commerce & Affiliate MLM',
            'description' => 'This is a valid business page description with more than twenty characters.',
            'email' => 'acme@example.com',
            'phone_country_code' => '+91',
            'phone_number' => '9876543210',
            'country' => '',
            'address' => '',
            'city' => '',
            'state' => '',
            'visibility' => 'public',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country'])
            ->assertJsonMissingValidationErrors(['address', 'city', 'state']);
    }

    public function test_business_page_stores_address_city_and_state_when_provided(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $response = $this->postJson('/api/member/business-pages', [
            'page_name' => 'Provided Location Corp',
            'category' => 'E-Commerce & Affiliate MLM',
            'description' => 'This is a valid business page description with more than twenty characters.',
            'email' => 'location@example.com',
            'phone_country_code' => '+91',
            'phone_number' => '9876543210',
            'country' => 'India',
            'address' => '456 Tech Park Boulevard',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
            'visibility' => 'public',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('business_pages', [
            'page_name' => 'Provided Location Corp',
            'country' => 'India',
            'address' => '456 Tech Park Boulevard',
            'city' => 'Bengaluru',
            'state' => 'Karnataka',
        ]);
    }

    public function test_business_page_web_form_can_be_submitted_with_empty_address_city_and_state(): void
    {
        $member = $this->createMember();
        $this->actingAs($member, 'member');

        $response = $this->post('/member/business-pages', [
            'page_name' => 'Web Route Corp',
            'category' => 'E-Commerce & Affiliate MLM',
            'description' => 'This is a valid business page description with more than twenty characters.',
            'email' => 'web@example.com',
            'phone_country_code' => '+91',
            'phone_number' => '9876543210',
            'country' => 'India',
            'address' => '',
            'city' => '',
            'state' => '',
            'visibility' => 'public',
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $this->assertDatabaseHas('business_pages', [
            'page_name' => 'Web Route Corp',
            'country' => 'India',
            'address' => null,
            'city' => null,
            'state' => null,
        ]);
    }
}
