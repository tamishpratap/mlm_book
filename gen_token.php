<?php
require __DIR__ . '/backend/vendor/autoload.php';
$app = require_once __DIR__ . '/backend/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Member;
use App\Models\MobileAccessToken;
use Illuminate\Support\Str;

$member = Member::find(1);
if (!$member) {
    echo "Member 1 not found\n";
    exit;
}

$plainToken = 'test_token_' . Str::random(50);
MobileAccessToken::create([
    'audience' => 'member',
    'actor_id' => $member->id,
    'token_hash' => hash('sha256', $plainToken),
    'device_name' => 'API Test Script',
    'last_ip' => '127.0.0.1',
    'last_used_at' => now(),
    'expires_at' => now()->addMonths(3),
]);

echo "GENERATED_TOKEN:" . $plainToken . "\n";
