<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
config([
    'database.connections.mysql.database' => 'mlm_book',
    'database.connections.mysql.username' => 'root',
    'database.connections.mysql.password' => '',
]);
\Illuminate\Support\Facades\DB::purge('mysql');

echo "=== REPORT COUNTS ===\n";
echo "ReportedPost: " . \App\Models\ReportedPost::count() . "\n";
echo "CommunityReport: " . \App\Models\CommunityReport::count() . "\n";
echo "ReportedProduct: " . \App\Models\ReportedProduct::count() . "\n";
echo "BusinessReviewReport: " . \App\Models\BusinessReviewReport::count() . "\n";
echo "Post count: " . \App\Models\Post::count() . "\n";
echo "Member count: " . \App\Models\Member::count() . "\n\n";

echo "=== LATEST POSTS WITH MEDIA ===\n";
foreach (\App\Models\Post::whereNotNull('media_path')->latest()->take(5)->get() as $p) {
    echo "Post #{$p->id} | Type: {$p->media_type} | Path: {$p->media_path} | URL: {$p->media_url}\n";
}

echo "=== REPORTED POSTS ===\n";
foreach (\App\Models\ReportedPost::with(['member', 'post'])->get() as $r) {
    echo "Report ID: " . $r->id . "\n";
    echo "  Member ID: " . $r->member_id . " (" . ($r->member?->name ?? 'N/A') . " - " . ($r->member?->user_id ?? 'N/A') . ")\n";
    echo "  Post ID: " . $r->post_id . "\n";
    echo "  Reason: " . $r->reason . "\n";
    echo "  Description: " . $r->description . "\n";
    echo "  Status: " . $r->status . "\n";
    echo "  Created: " . $r->created_at . "\n";
    if ($r->post) {
        echo "  Post details:\n";
        echo "    Body: " . $r->post->body . "\n";
        echo "    Media Type: " . $r->post->media_type . "\n";
        echo "    Media Path: " . $r->post->media_path . "\n";
        echo "    Media URL: " . $r->post->media_url . "\n";
    } else {
        echo "  Post: NULL (not found)\n";
    }
    echo "----------------------------------------\n";
}

echo "\n=== COMMUNITY REPORTS ===\n";
foreach (\App\Models\CommunityReport::with(['reporter', 'community'])->get() as $r) {
    echo "Report ID: " . $r->id . " | Community ID: " . $r->community_id . " | Reason: " . $r->reason . " | Status: " . $r->status . "\n";
}

echo "\n=== REPORTED PRODUCTS ===\n";
foreach (\App\Models\ReportedProduct::with(['member', 'product'])->get() as $r) {
    echo "Report ID: " . $r->id . " | Product ID: " . $r->product_id . " | Reason: " . $r->reason . "\n";
}

echo "\n=== BUSINESS REVIEW REPORTS ===\n";
foreach (\App\Models\BusinessReviewReport::with(['reporter', 'review'])->get() as $r) {
    echo "Report ID: " . $r->id . " | Review ID: " . $r->business_review_id . " | Reason: " . $r->reason . " | Status: " . $r->status . "\n";
}
