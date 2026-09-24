<?php
require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Member;
use App\Models\Post;
use Illuminate\Http\Request;

$member = Member::find(1);
auth('member')->setUser($member);

$controller = app(\App\Http\Controllers\Mobile\MobileFeedController::class);

$post = Post::latest()->first();
echo "Testing on Post ID: " . $post->id . "\n";

// 1. Test reactPost
echo "--- Testing reactPost ---\n";
try {
    $req = Request::create("/api/v1/mobile/posts/{$post->id}/react", 'POST', ['type' => 'like']);
    $res = $controller->reactPost($req, $post->id);
    echo "reactPost status: " . $res->status() . "\n";
    echo "reactPost data: " . $res->getContent() . "\n";
} catch (\Throwable $e) {
    echo "reactPost ERROR: " . $e->getMessage() . "\n";
}

// 2. Test addComment
echo "--- Testing addComment ---\n";
try {
    $req = Request::create("/api/v1/mobile/posts/{$post->id}/comments", 'POST', ['comment' => 'This is a test comment!']);
    $res = $controller->addComment($req, $post->id);
    echo "addComment status: " . $res->status() . "\n";
    echo "addComment data: " . $res->getContent() . "\n";
} catch (\Throwable $e) {
    echo "addComment ERROR: " . $e->getMessage() . "\n";
}

// 3. Test getComments
echo "--- Testing getComments ---\n";
try {
    $req = Request::create("/api/v1/mobile/posts/{$post->id}/comments", 'GET');
    $res = $controller->getComments($req, $post->id);
    echo "getComments status: " . $res->status() . "\n";
    echo "getComments data: " . $res->getContent() . "\n";
} catch (\Throwable $e) {
    echo "getComments ERROR: " . $e->getMessage() . "\n";
}

// 4. Test toggleSavePost
echo "--- Testing toggleSavePost ---\n";
try {
    $req = Request::create("/api/v1/mobile/posts/{$post->id}/save", 'POST');
    $res = $controller->toggleSavePost($req, $post->id);
    echo "toggleSavePost status: " . $res->status() . "\n";
    echo "toggleSavePost data: " . $res->getContent() . "\n";
} catch (\Throwable $e) {
    echo "toggleSavePost ERROR: " . $e->getMessage() . "\n";
}
