<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_route_requires_member_authentication(): void
    {
        $this->get(route('member.search'))
            ->assertRedirect(route('member.login'));

        $this->getJson(route('member.search.results', ['q' => 'test']))
            ->assertUnauthorized();
    }

    public function test_blank_and_one_character_searches_do_not_load_members(): void
    {
        $currentMember = $this->createMember('Current Member', 'current@example.com');
        $this->createMember('Another Member', 'another@example.com');

        $this->actingAs($currentMember, 'member')
            ->get(route('member.search'))
            ->assertOk()
            ->assertViewHas('members', null)
            ->assertSee('Find on MLM Book')
            ->assertSee('Search for Members, Pages, Communities, Posts and Events.')
            ->assertSee('id="memberHeaderSearchForm"', false)
            ->assertSee('data-search-page="true"', false);

        $this->get(route('member.search', ['q' => ' a ']))
            ->assertOk()
            ->assertViewHas('search', 'a')
            ->assertViewHas('members', null)
            ->assertSee('Enter at least 2 characters to search.');
    }

    public function test_search_matches_only_names_and_hides_private_member_details(): void
    {
        $currentMember = $this->createMember('John Current', 'current@example.com');
        $johnAlpha = $this->createMember('John Alpha', 'john.alpha@example.com', [
            'bio' => 'Community builder and MLM Book Member.',
            'city' => 'Pune',
            'country' => 'India',
            'phone' => '+919999999999',
            'google_id' => 'private-google-id',
        ]);
        $johnZulu = $this->createMember('John Zulu', 'john.zulu@example.com');
        $emailOnlyMatch = $this->createMember('Other Person', 'john-only@example.com');
        $photoPath = 'uploads/profile/search-test-'.Str::lower(Str::random(12)).'.jpg';

        File::ensureDirectoryExists(public_path('uploads/profile'));
        File::put(public_path($photoPath), 'test image');
        $johnZulu->update(['profile_photo' => $photoPath]);

        try {
            $response = $this->actingAs($currentMember, 'member')
                ->get(route('member.search', ['q' => 'john', 'type' => 'members']));

            $response
                ->assertOk()
                ->assertViewHas('members', function ($members) use ($johnAlpha, $johnZulu, $currentMember, $emailOnlyMatch) {
                    return $members->pluck('id')->all() === [$johnAlpha->id, $johnZulu->id]
                        && ! $members->pluck('id')->contains($currentMember->id)
                        && ! $members->pluck('id')->contains($emailOnlyMatch->id);
                })
                ->assertSee('John Alpha')
                ->assertSee('John Zulu')
                ->assertSee('JA')
                ->assertSee(asset($photoPath).'?v=', false)
                ->assertDontSee('john.alpha@example.com')
                ->assertDontSee('+919999999999')
                ->assertDontSee('private-google-id')
                ->assertDontSee('john-only@example.com');

            $this->get(route('member.search', ['q' => 'John Alpha']))
                ->assertOk()
                ->assertViewHas('members', fn ($members) => $members->count() === 1
                    && $members->first()->is($johnAlpha));
        } finally {
            File::delete(public_path($photoPath));
        }
    }

    public function test_search_results_are_paginated_and_keep_the_query_string(): void
    {
        $currentMember = $this->createMember('Current Member', 'current@example.com');

        foreach (range(1, 13) as $number) {
            $this->createMember(
                sprintf('Search Member %02d', $number),
                "search{$number}@example.com",
            );
        }

        $this->actingAs($currentMember, 'member')
            ->get(route('member.search', ['q' => 'Search', 'type' => 'members']))
            ->assertOk()
            ->assertViewHas('members', function ($members) {
                return $members->perPage() === 12
                    && $members->count() === 12
                    && $members->total() === 13
                    && $members->lastPage() === 2
                    && str_contains($members->url(2), 'q=Search')
                    && str_contains($members->url(2), 'type=members');
            })
            ->assertSee('q=Search&amp;type=members&amp;page=2', false);

        $this->get(route('member.search', ['q' => 'Search', 'type' => 'members', 'page' => 2]))
            ->assertOk()
            ->assertViewHas('members', fn ($members) => $members->count() === 1
                && $members->currentPage() === 2);
    }

    public function test_search_query_is_limited_to_one_hundred_characters(): void
    {
        $currentMember = $this->createMember('Current Member', 'current@example.com');

        $this->actingAs($currentMember, 'member')
            ->from(route('member.search'))
            ->get(route('member.search', ['q' => str_repeat('a', 101)]))
            ->assertRedirect(route('member.search'))
            ->assertSessionHasErrors('q');
    }

    public function test_ajax_search_returns_rendered_results_counts_and_pagination(): void
    {
        $currentMember = $this->createMember('Current Member', 'current@example.com');

        foreach (range(1, 13) as $number) {
            $this->createMember(
                sprintf('Live Search Person %02d', $number),
                "live-search-{$number}@example.com",
            );
        }

        $this->actingAs($currentMember, 'member')
            ->getJson(route('member.search.results', [
                'q' => 'Live Search',
                'type' => 'members',
            ]))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('query', 'Live Search')
            ->assertJsonPath('type', 'members')
            ->assertJsonPath('counts.all', 13)
            ->assertJsonPath('counts.members', 13)
            ->assertJsonPath('counts.pages', 0)
            ->assertJsonPath('counts.groups', 0)
            ->assertJsonPath('counts.posts', 0)
            ->assertJsonPath('counts.events', 0)
            ->assertJson(fn ($json) => $json
                ->whereType('html', 'string')
                ->whereType('pagination', 'string')
                ->etc())
            ->assertSee('Live Search Person 01', false)
            ->assertSee('type=members', false);
    }

    public function test_all_tab_returns_a_limited_people_group_and_see_all_link(): void
    {
        $currentMember = $this->createMember('Current Member', 'current@example.com');

        foreach (range(1, 7) as $number) {
            $this->createMember("Discover Person {$number}", "discover-{$number}@example.com");
        }

        $response = $this->actingAs($currentMember, 'member')
            ->getJson(route('member.search.results', ['q' => 'Discover', 'type' => 'all']))
            ->assertOk()
            ->assertJsonPath('counts.all', 7)
            ->assertJsonPath('counts.members', 7)
            ->assertJsonPath('pagination', '');

        $this->assertSame(5, substr_count($response->json('html'), 'class="member-result-card"'));
        $this->assertStringContainsString('data-search-type="members"', $response->json('html'));
    }

    public function test_missing_categories_return_zero_and_a_safe_unavailable_state(): void
    {
        $currentMember = $this->createMember('Current Member', 'current@example.com');
        $this->createMember('Available Person', 'available@example.com');
        $this->actingAs($currentMember, 'member');

        foreach (['pages', 'posts', 'events'] as $type) {
            $this->getJson(route('member.search.results', ['q' => 'Available', 'type' => $type]))
                ->assertOk()
                ->assertJsonPath('success', true)
                ->assertJsonPath('type', $type)
                ->assertJsonPath("counts.{$type}", 0)
                ->assertJsonPath('pagination', '')
                ->assertSee('This search category is not available yet.', false);
        }
    }

    public function test_search_type_and_page_are_validated(): void
    {
        $currentMember = $this->createMember('Current Member', 'current@example.com');

        $this->actingAs($currentMember, 'member')
            ->getJson(route('member.search.results', ['q' => 'test', 'type' => 'private-table']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('type');

        $this->getJson(route('member.search.results', ['q' => 'test', 'page' => 0]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('page');
    }

    public function test_header_search_is_wired_for_debounced_navigation_and_initial_ajax_loading(): void
    {
        $currentMember = $this->createMember('Current Member', 'current@example.com');

        $this->actingAs($currentMember, 'member')
            ->get(route('member.dashboard'))
            ->assertOk()
            ->assertSee('id="memberHeaderSearchForm"', false)
            ->assertSee('id="memberHeaderSearchInput"', false)
            ->assertSee('data-search-page="false"', false)
            ->assertSee('name="type" value="all"', false);

        $this->get(route('member.search', ['q' => 'Tamish', 'type' => 'all']))
            ->assertOk()
            ->assertSee('value="Tamish"', false)
            ->assertSee('data-active-type="all"', false)
            ->assertSee('data-search-page="true"', false);

        $script = File::get(public_path('member_assets/js/member-search.js'));

        $this->assertStringContainsString('window.setTimeout(openSearchPage, 400)', $script);
        $this->assertStringContainsString('encodeURIComponent(query)', $script);
        $this->assertStringContainsString("form.dataset.searchPage === 'true'", $script);
        $this->assertStringContainsString('if (input.value.trim().length >= 2', $script);
        $this->assertStringContainsString('loadResults(', $script);
    }

    private function createMember(string $name, string $email, array $attributes = []): Member
    {
        return Member::create(array_merge([
            'name' => $name,
            'email' => $email,
            'password' => 'secure-password',
        ], $attributes));
    }
}
