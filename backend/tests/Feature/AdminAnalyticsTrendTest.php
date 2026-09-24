<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\Member;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AdminAnalyticsTrendTest extends TestCase
{
    use RefreshDatabase;

    protected function createAdmin(array $attrs = []): Admin
    {
        $unique = uniqid('admin_');
        return Admin::create(array_merge([
            'name' => 'Analytics Admin ' . $unique,
            'email' => $unique . '@example.com',
            'password' => 'secret123',
            'status' => 'active',
        ], $attrs));
    }

    protected function createMemberOnDate(string $dateTimeStr, array $attrs = []): Member
    {
        $unique = uniqid('member_');
        $member = new Member(array_merge([
            'name' => 'Member ' . $unique,
            'email' => $unique . '@example.com',
            'phone' => '+91' . rand(6000000000, 9999999999),
            'password' => bcrypt('secret123'),
            'mobile_verified_at' => now(),
        ], $attrs));

        $member->created_at = Carbon::parse($dateTimeStr);
        $member->updated_at = Carbon::parse($dateTimeStr);
        $member->save(['timestamps' => false]);

        return $member;
    }

    /**
     * TEST 1 — LAST 7 DAYS:
     * Returns exactly 7 daily points in chronological order with zero days preserved.
     */
    public function test_1_last_7_days_returns_7_consecutive_days_with_zero_fill(): void
    {
        $admin = $this->createAdmin();

        // Pin current time to 2026-09-12 12:00:00
        Carbon::setTestNow(Carbon::parse('2026-09-12 12:00:00'));

        // Create members on specific days:
        // Sep 07 (1 member), Sep 10 (3 members), Sep 12 (2 members)
        $this->createMemberOnDate('2026-09-07 10:00:00');
        $this->createMemberOnDate('2026-09-10 09:00:00');
        $this->createMemberOnDate('2026-09-10 14:00:00');
        $this->createMemberOnDate('2026-09-10 18:00:00');
        $this->createMemberOnDate('2026-09-12 08:00:00');
        $this->createMemberOnDate('2026-09-12 11:00:00');

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/analytics?period=7days');
        $response->assertStatus(200);

        $dates = $response->json('chartDates');
        $counts = $response->json('chartCounts');
        $newMembers = $response->json('newMembersCount');

        $this->assertCount(7, $dates);
        $this->assertCount(7, $counts);

        // Expected 7 continuous dates: Sep 06 through Sep 12
        $expectedDates = [
            '2026-09-06',
            '2026-09-07',
            '2026-09-08',
            '2026-09-09',
            '2026-09-10',
            '2026-09-11',
            '2026-09-12',
        ];
        $this->assertEquals($expectedDates, $dates);

        // Expected counts: 0 on Sep 06, 1 on Sep 07, 0 on Sep 08/09, 3 on Sep 10, 0 on Sep 11, 2 on Sep 12
        $expectedCounts = [0, 1, 0, 0, 3, 0, 2];
        $this->assertEquals($expectedCounts, $counts);

        // Total New must equal sum of daily counts
        $this->assertEquals(6, $newMembers);
        $this->assertEquals(array_sum($expectedCounts), $newMembers);

        Carbon::setTestNow(); // reset
    }

    /**
     * TEST 2 — YESTERDAY:
     * Returns exactly 1 date point with correct count.
     */
    public function test_2_yesterday_returns_1_date_point_with_correct_count(): void
    {
        $admin = $this->createAdmin();
        Carbon::setTestNow(Carbon::parse('2026-09-12 12:00:00'));

        $this->createMemberOnDate('2026-09-11 15:30:00');
        $this->createMemberOnDate('2026-09-11 19:45:00');

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/analytics?period=yesterday');
        $response->assertStatus(200);

        $dates = $response->json('chartDates');
        $counts = $response->json('chartCounts');
        $newMembers = $response->json('newMembersCount');

        $this->assertCount(1, $dates);
        $this->assertCount(1, $counts);
        $this->assertEquals(['2026-09-11'], $dates);
        $this->assertEquals([2], $counts);
        $this->assertEquals(2, $newMembers);

        Carbon::setTestNow();
    }

    /**
     * TEST 3 — TODAY:
     * Returns exactly 1 date point with correct count (or 0 if none).
     */
    public function test_3_today_returns_1_date_point_with_correct_count(): void
    {
        $admin = $this->createAdmin();
        Carbon::setTestNow(Carbon::parse('2026-09-12 12:00:00'));

        $this->createMemberOnDate('2026-09-12 04:00:00');
        $this->createMemberOnDate('2026-09-12 08:30:00');
        $this->createMemberOnDate('2026-09-12 10:15:00');
        $this->createMemberOnDate('2026-09-12 11:59:00');

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/analytics?period=today');
        $response->assertStatus(200);

        $dates = $response->json('chartDates');
        $counts = $response->json('chartCounts');
        $newMembers = $response->json('newMembersCount');

        $this->assertCount(1, $dates);
        $this->assertCount(1, $counts);
        $this->assertEquals(['2026-09-12'], $dates);
        $this->assertEquals([4], $counts);
        $this->assertEquals(4, $newMembers);

        Carbon::setTestNow();
    }

    /**
     * TEST 4 — LAST 30 DAYS:
     * Returns exactly 30 daily points in chronological order.
     */
    public function test_4_last_30_days_returns_30_consecutive_days(): void
    {
        $admin = $this->createAdmin();
        Carbon::setTestNow(Carbon::parse('2026-09-12 12:00:00'));

        $this->createMemberOnDate('2026-08-20 10:00:00');
        $this->createMemberOnDate('2026-09-01 10:00:00');

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/analytics?period=30days');
        $response->assertStatus(200);

        $dates = $response->json('chartDates');
        $counts = $response->json('chartCounts');
        $newMembers = $response->json('newMembersCount');

        $this->assertCount(30, $dates);
        $this->assertCount(30, $counts);
        $this->assertEquals('2026-08-14', $dates[0]);
        $this->assertEquals('2026-09-12', $dates[29]);
        $this->assertEquals(2, $newMembers);
        $this->assertEquals(array_sum($counts), $newMembers);

        Carbon::setTestNow();
    }

    /**
     * TEST 5 — ZERO-DATA RANGE:
     * Range with 0 registrations returns complete timeline with 0 values, never blank or empty array.
     */
    public function test_5_zero_data_range_returns_continuous_zeros(): void
    {
        $admin = $this->createAdmin();
        Carbon::setTestNow(Carbon::parse('2026-09-12 12:00:00'));

        // No members created
        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/analytics?period=7days');
        $response->assertStatus(200);

        $dates = $response->json('chartDates');
        $counts = $response->json('chartCounts');
        $newMembers = $response->json('newMembersCount');

        $this->assertCount(7, $dates);
        $this->assertCount(7, $counts);
        $this->assertEquals([0, 0, 0, 0, 0, 0, 0], $counts);
        $this->assertEquals(0, $newMembers);

        Carbon::setTestNow();
    }

    /**
     * TEST 6 — TOTAL CONSISTENCY:
     * sum(chartCounts) strictly equals newMembersCount.
     */
    public function test_6_total_new_matches_sum_of_daily_counts(): void
    {
        $admin = $this->createAdmin();
        Carbon::setTestNow(Carbon::parse('2026-09-12 12:00:00'));

        $this->createMemberOnDate('2026-09-08 10:00:00');
        $this->createMemberOnDate('2026-09-08 11:00:00');
        $this->createMemberOnDate('2026-09-09 12:00:00');
        $this->createMemberOnDate('2026-09-11 14:00:00');

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/analytics?date_from=2026-09-07&date_to=2026-09-12');
        $response->assertStatus(200);

        $counts = $response->json('chartCounts');
        $newMembers = $response->json('newMembersCount');

        $this->assertEquals(4, $newMembers);
        $this->assertEquals(array_sum($counts), $newMembers);

        Carbon::setTestNow();
    }

    /**
     * TEST 7 — CHRONOLOGICAL ORDER:
     * Dates are sorted strictly from oldest to newest.
     */
    public function test_7_dates_are_in_ascending_chronological_order(): void
    {
        $admin = $this->createAdmin();
        Carbon::setTestNow(Carbon::parse('2026-09-12 12:00:00'));

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/analytics?period=7days');
        $dates = $response->json('chartDates');

        $sortedDates = $dates;
        sort($sortedDates);

        $this->assertEquals($sortedDates, $dates);

        Carbon::setTestNow();
    }

    /**
     * TEST 8 — SINGLE-DAY CUSTOM RANGE:
     * Explicit same-day range date_from === date_to returns 1 point with valid count.
     */
    public function test_8_single_day_custom_range(): void
    {
        $admin = $this->createAdmin();

        $this->createMemberOnDate('2026-09-05 08:00:00');
        $this->createMemberOnDate('2026-09-05 12:00:00');
        $this->createMemberOnDate('2026-09-05 16:00:00');

        $response = $this->actingAs($admin, 'admin')->getJson('/api/admin/analytics?date_from=2026-09-05&date_to=2026-09-05');
        $response->assertStatus(200);

        $dates = $response->json('chartDates');
        $counts = $response->json('chartCounts');
        $newMembers = $response->json('newMembersCount');

        $this->assertCount(1, $dates);
        $this->assertCount(1, $counts);
        $this->assertEquals(['2026-09-05'], $dates);
        $this->assertEquals([3], $counts);
        $this->assertEquals(3, $newMembers);
    }
}
