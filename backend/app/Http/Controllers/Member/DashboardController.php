<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $currentMember = auth('member')->user();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'member' => $currentMember,
                'quick_shortcuts' => [
                    [
                        'name' => 'Socials Feed',
                        'path' => '/member/socials',
                        'icon' => 'rss',
                    ],
                    [
                        'name' => 'My Connections',
                        'path' => '/member/friends',
                        'icon' => 'users-round',
                    ],
                    [
                        'name' => 'New Connections',
                        'path' => '/member/people/suggestions',
                        'icon' => 'sparkles',
                    ],
                    [
                        'name' => 'Watch Videos',
                        'path' => '/member/watch',
                        'icon' => 'monitor-play',
                    ],
                    [
                        'name' => 'Community Groups',
                        'path' => '/member/community',
                        'icon' => 'users',
                    ],
                    [
                        'name' => 'Business Directory',
                        'path' => '/member/business-directory',
                        'icon' => 'compass',
                    ],
                    [
                        'name' => 'Upcoming Events',
                        'path' => '/member/events',
                        'icon' => 'calendar',
                    ],
                ],
                'capabilities' => [
                    [
                        'title' => 'Social Features & Smart Feed',
                        'description' => 'Share posts, photo & video updates, 24-hour stories, and interact through likes, custom reactions, threaded comments, shares, and saved posts.',
                        'path' => '/member/socials',
                        'icon' => 'rss',
                        'link_text' => 'View Socials',
                    ],
                    [
                        'title' => 'Watch Video Platform',
                        'description' => 'Discover video content uploaded by members, watch video posts seamlessly, and explore visual updates from across the network.',
                        'path' => '/member/watch',
                        'icon' => 'monitor-play',
                        'link_text' => 'Watch Videos',
                    ],
                    [
                        'title' => 'Community Features',
                        'description' => 'Create or join public and private groups, participate in discussion threads, invite peers via custom invite links, and manage group moderation.',
                        'path' => '/member/community',
                        'icon' => 'users',
                        'link_text' => 'Browse Communities',
                    ],
                    [
                        'title' => 'Business Pages & Directory',
                        'description' => 'Establish brand pages, manage customer reviews and inbox messages, track page analytics, and feature your organization in the Business Directory.',
                        'path' => '/member/business-directory',
                        'icon' => 'building-2',
                        'link_text' => 'Business Directory',
                    ],
                    [
                        'title' => 'Events Platform',
                        'description' => 'Host upcoming events, manage attendee lists, track RSVPs, and stay updated on networking meetups and community gatherings.',
                        'path' => '/member/events',
                        'icon' => 'calendar-days',
                        'link_text' => 'Discover Events',
                    ],
                    [
                        'title' => 'New Connections & Discovery',
                        'description' => 'Discover recommended members based on mutual connections and geographic location, build your network, and manage your account privacy.',
                        'path' => '/member/people/suggestions',
                        'icon' => 'sparkles',
                        'link_text' => 'Find Connections',
                    ],
                ],
            ]);
        }

        return view('member.dashboard', compact('currentMember'));
    }
}
