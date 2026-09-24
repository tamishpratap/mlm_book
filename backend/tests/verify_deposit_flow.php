<?php

/**
 * Automated Verification Script for DApp Deposit & Manual Deposit Verification System.
 * Tests all requirements, validation rules, on-chain verification mock flows,
 * duplicate hash protection, admin approval/rejection synchronization, and balance updates.
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\AdDeposit;
use App\Models\ImportFund;
use App\Models\Member;
use App\Models\Setting;
use App\Services\BscTransactionVerifierService;
use App\Http\Controllers\Member\DepositVerificationController;
use App\Http\Controllers\Admin\AdDepositSettingsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

$passed = 0;
$failed = 0;

function assertTest(bool $condition, string $testName, string $details = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo " [PASS] {$testName}\n";
    } else {
        $failed++;
        echo " [FAIL] {$testName} - {$details}\n";
    }
}

echo "=======================================================\n";
echo " RUNNING DEPOSIT SYSTEM AUTOMATED VERIFICATION SUITE\n";
echo "=======================================================\n\n";

// Enable mock verification mode for automated test suite
BscTransactionVerifierService::fake([]);

// 1. Setup Test Member and Receiver Wallet
$testMember = Member::first();
if (!$testMember) {
    $testMember = Member::create([
        'user_id' => 'MBRTEST' . rand(1000, 9999),
        'name' => 'Test User',
        'email' => 'testuser' . rand(1000, 9999) . '@example.com',
        'phone' => '9999999999',
        'password' => bcrypt('secret123'),
        'ad_balance' => 100.00,
    ]);
}
$initialBalance = (float) ($testMember->ad_balance ?? 0.00);
$initialP2pBalance = (float) ($testMember->p2p_wallet ?? 0.00);

Setting::set('deposit_crypto_wallet_address', '0x55d398326f99059fF775485246999027B3197955');
Setting::set('bsc_network', 'mainnet');

$verifier = new BscTransactionVerifierService();
$controller = new DepositVerificationController();
$adminController = new AdDepositSettingsController();

// -------------------------------------------------------------
// TEST 1: Config endpoint returns min deposit ($10) and BEP-20
// -------------------------------------------------------------
$reqConfig = Request::create('/api/member/deposit/config', 'GET');
$reqConfig->setUserResolver(fn() => $testMember);
$configRes = $controller->getDepositConfig($reqConfig);
$configData = $configRes->getData(true);

assertTest(
    $configData['success'] === true && $configData['config']['min_deposit'] == 10.00 && $configData['config']['network'] === 'BEP-20',
    'Test 1: GET /deposit/config returns min_deposit=10.00 and network=BEP-20',
    json_encode($configData)
);

// -------------------------------------------------------------
// TEST 2: Amount < $10 is rejected
// -------------------------------------------------------------
$caughtMinEx = false;
try {
    $reqLow = Request::create('/api/member/deposit/verify', 'POST', [
        'amount' => 9.99,
        'transaction_hash' => '0x' . str_repeat('a', 64),
    ]);
    $reqLow->setUserResolver(fn() => $testMember);
    $controller->verify($reqLow, $verifier);
} catch (\Illuminate\Validation\ValidationException $e) {
    $caughtMinEx = isset($e->errors()['amount']);
}

assertTest($caughtMinEx, 'Test 2: Validation rejects amounts below $10.00 USD');

// -------------------------------------------------------------
// TEST 3: Invalid Hash Format is rejected
// -------------------------------------------------------------
$caughtHashEx = false;
try {
    $reqInvalidHash = Request::create('/api/member/deposit/verify', 'POST', [
        'amount' => 50.00,
        'transaction_hash' => '0xInvalidHash123',
    ]);
    $reqInvalidHash->setUserResolver(fn() => $testMember);
    $controller->verify($reqInvalidHash, $verifier);
} catch (\Illuminate\Validation\ValidationException $e) {
    $caughtHashEx = isset($e->errors()['transaction_hash']);
}

assertTest($caughtHashEx, 'Test 3: Validation rejects invalid format transaction hash');

// -------------------------------------------------------------
// TEST 4: On-chain verifier rejects transferred amount below minimum
// -------------------------------------------------------------
$lowTxHash = '0xLOWAMOUNT_' . bin2hex(random_bytes(16));
$lowVerify = $verifier->verifyTransaction($lowTxHash, 5.00, '0x55d398326f99059fF775485246999027B3197955', 10.00);
assertTest(
    $lowVerify['verified'] === false && $lowVerify['status'] === 'amount_too_low',
    'Test 4: Verifier rejects transaction where transferred amount is below $10.00'
);

// -------------------------------------------------------------
// TEST 5: On-chain verifier rejects amount mismatch
// -------------------------------------------------------------
$mismatchTxHash = '0xAMOUNTMISMATCH_' . bin2hex(random_bytes(16));
$mismatchVerify = $verifier->verifyTransaction($mismatchTxHash, 50.00, '0x55d398326f99059fF775485246999027B3197955', 10.00, true);
assertTest(
    $mismatchVerify['verified'] === false && $mismatchVerify['status'] === 'amount_mismatch',
    'Test 5: Verifier rejects transaction when entered amount mismatches on-chain amount'
);

// -------------------------------------------------------------
// TEST 6: Independent verify endpoint verifies without DB creation
// -------------------------------------------------------------
$uniqueTestHash = '0x' . bin2hex(random_bytes(32)); // Exactly 66 characters hex
$countBefore = ImportFund::count();

$reqVerify = Request::create('/api/member/deposit/verify', 'POST', [
    'amount' => 25.00,
    'transaction_hash' => $uniqueTestHash,
]);
$reqVerify->setUserResolver(fn() => $testMember);
$verifyRes = $controller->verify($reqVerify, $verifier);
$verifyData = $verifyRes->getData(true);
$countAfter = ImportFund::count();

assertTest(
    $verifyData['success'] === true && $verifyData['verified'] === true && $countBefore === $countAfter,
    'Test 6: Independent Verify verifies transaction successfully without inserting to DB'
);

// -------------------------------------------------------------
// TEST 7: Manual Request creates record in import_funds (awaiting admin)
// -------------------------------------------------------------
$reqSubmit = Request::create('/api/member/deposit/manual-request', 'POST', [
    'amount' => 25.00,
    'transaction_hash' => $uniqueTestHash,
]);
$reqSubmit->setUserResolver(fn() => $testMember);
$submitRes = $controller->submitManualRequest($reqSubmit, $verifier);
$submitData = $submitRes->getData(true);

$createdImportFund = ImportFund::where('transaction_hash', $uniqueTestHash)->first();
$syncedAdDeposit = AdDeposit::where('transaction_hash', $uniqueTestHash)->first();

assertTest(
    $submitRes->getStatusCode() === 201 &&
    $createdImportFund !== null &&
    $createdImportFund->deposit_status === ImportFund::STATUS_VERIFIED &&
    $createdImportFund->amount == 25.00 &&
    $syncedAdDeposit !== null &&
    $syncedAdDeposit->status === AdDeposit::STATUS_PENDING &&
    $syncedAdDeposit->verification_status === 'verified',
    'Test 7: Manual Request creates verified record in import_funds and syncs to ad_deposits'
);

// -------------------------------------------------------------
// TEST 8: Balance NOT credited upon submission (awaiting admin approval)
// -------------------------------------------------------------
$testMember->refresh();
assertTest(
    (float) $testMember->p2p_wallet === $initialP2pBalance &&
    (float) $testMember->ad_balance === $initialBalance,
    'Test 8: Member balance remains uncredited while deposit is awaiting admin approval'
);

// -------------------------------------------------------------
// TEST 9: Duplicate Hash submission rejected (same member)
// -------------------------------------------------------------
$dupReq = Request::create('/api/member/deposit/manual-request', 'POST', [
    'amount' => 25.00,
    'transaction_hash' => $uniqueTestHash,
]);
$dupReq->setUserResolver(fn() => $testMember);
$dupRes = $controller->submitManualRequest($dupReq, $verifier);

assertTest(
    $dupRes->getStatusCode() === 409 || $dupRes->getStatusCode() === 422,
    'Test 9: Submitting duplicate transaction hash is rejected (prevents duplicate submission)'
);

// -------------------------------------------------------------
// TEST 10: Duplicate Hash submission rejected (different member)
// -------------------------------------------------------------
$anotherMember = Member::where('id', '!=', $testMember->id)->first();
if (!$anotherMember) {
    $anotherMember = Member::create([
        'user_id' => 'OTHER' . rand(1000, 9999),
        'name' => 'Other Member',
        'email' => 'other' . rand(1000, 9999) . '@example.com',
        'phone' => '8888888888',
        'password' => bcrypt('secret123'),
        'ad_balance' => 0.00,
    ]);
}
$diffMemberReq = Request::create('/api/member/deposit/manual-request', 'POST', [
    'amount' => 25.00,
    'transaction_hash' => $uniqueTestHash,
]);
$diffMemberReq->setUserResolver(fn() => $anotherMember);
$diffMemberRes = $controller->submitManualRequest($diffMemberReq, $verifier);

assertTest(
    $diffMemberRes->getStatusCode() === 409 || $diffMemberRes->getStatusCode() === 422,
    'Test 10: Different member attempting to claim same transaction hash is rejected'
);

// -------------------------------------------------------------
// TEST 11: Admin Approval atomically credits p2p_wallet (Fund Wallet) and updates both tables
// -------------------------------------------------------------
$approveReq = Request::create("/api/admin/ad-deposits/{$syncedAdDeposit->id}/approve", 'POST', [
    'admin_notes' => 'Verified on explorer by Admin',
]);
$approveRes = $adminController->approveDeposit($approveReq, (int) $syncedAdDeposit->id);

$testMember->refresh();
$createdImportFund->refresh();
$syncedAdDeposit->refresh();

assertTest(
    (float) $testMember->p2p_wallet === ($initialP2pBalance + 25.00) &&
    (float) $testMember->ad_balance === ($initialBalance + 25.00) &&
    $syncedAdDeposit->status === AdDeposit::STATUS_APPROVED &&
    $createdImportFund->deposit_status === ImportFund::STATUS_APPROVED &&
    $createdImportFund->verification_status === 'verified',
    'Test 11: Admin Approval credits p2p_wallet (Fund Wallet) exactly and syncs status to import_funds and ad_deposits'
);

// -------------------------------------------------------------
// TEST 12: Double-Approval prevention
// -------------------------------------------------------------
$secondApproveRes = $adminController->approveDeposit($approveReq, (int) $syncedAdDeposit->id);
$testMember->refresh();

assertTest(
    $secondApproveRes->getStatusCode() === 422 &&
    (float) $testMember->p2p_wallet === ($initialP2pBalance + 25.00) &&
    (float) $testMember->ad_balance === ($initialBalance + 25.00),
    'Test 12: Double-approval is blocked and does not credit member balance twice'
);

// -------------------------------------------------------------
// TEST 13: Admin Rejection Flow (no balance credit, status updated)
// -------------------------------------------------------------
$rejectTestHash = '0x' . bin2hex(random_bytes(32));
$reqRejectSubmit = Request::create('/api/member/deposit/manual-request', 'POST', [
    'amount' => 50.00,
    'transaction_hash' => $rejectTestHash,
]);
$reqRejectSubmit->setUserResolver(fn() => $testMember);
$controller->submitManualRequest($reqRejectSubmit, $verifier);

$rejectImportFund = ImportFund::where('transaction_hash', $rejectTestHash)->first();
$rejectAdDeposit = AdDeposit::where('transaction_hash', $rejectTestHash)->first();
$balanceBeforeReject = (float) $testMember->fresh()->ad_balance;

$rejectReq = Request::create("/api/admin/ad-deposits/{$rejectAdDeposit->id}/reject", 'POST', [
    'admin_notes' => 'Rejected due to invalid recipient wallet',
]);
$rejectRes = $adminController->rejectDeposit($rejectReq, (int) $rejectAdDeposit->id);

$rejectImportFund->refresh();
$rejectAdDeposit->refresh();
$balanceAfterReject = (float) $testMember->fresh()->ad_balance;

assertTest(
    $rejectAdDeposit->status === AdDeposit::STATUS_REJECTED &&
    $rejectImportFund->deposit_status === ImportFund::STATUS_REJECTED &&
    $balanceBeforeReject === $balanceAfterReject,
    'Test 13: Admin Rejection updates status to rejected and does NOT credit balance'
);

// -------------------------------------------------------------
// TEST 14: Database Backward Compatibility - view import_fund
// -------------------------------------------------------------
$viewResult = DB::select("SELECT COUNT(*) as cnt FROM import_fund WHERE transaction_hash = ?", [$uniqueTestHash]);
assertTest(
    !empty($viewResult) && $viewResult[0]->cnt >= 1,
    'Test 14: SQL view `import_fund` correctly mirrors `import_funds` for backward compatibility'
);

// -------------------------------------------------------------
// TEST 15: Admin Web DepositManagementWebController::index loads successfully
// -------------------------------------------------------------
$webCtrl = new \App\Http\Controllers\Admin\DepositManagementWebController();
$webIndexReq = Request::create('/admin/deposits', 'GET');
$webIndexRes = $webCtrl->index($webIndexReq);
assertTest(
    $webIndexRes instanceof \Illuminate\View\View,
    'Test 15: Admin Web DepositManagementWebController::index loads Blade view successfully'
);

// -------------------------------------------------------------
// TEST 16: Admin Web On-Chain Live Verification endpoint returns verified data
// -------------------------------------------------------------
$webVerifyHash = '0x' . bin2hex(random_bytes(32));
$newDepositReq = Request::create('/api/member/deposit/manual-request', 'POST', [
    'amount' => 75.00,
    'transaction_hash' => $webVerifyHash,
]);
$newDepositReq->setUserResolver(fn() => $testMember);
$controller->submitManualRequest($newDepositReq, $verifier);

$targetDeposit = ImportFund::where('transaction_hash', $webVerifyHash)->first();
$webVerifyReq = Request::create("/admin/deposits/{$targetDeposit->id}/verify-onchain", 'POST');
$webVerifyRes = $webCtrl->verifyOnChain($webVerifyReq, $targetDeposit->id, $verifier);
$webVerifyData = $webVerifyRes->getData(true);

assertTest(
    $webVerifyData['success'] === true &&
    $webVerifyData['verified'] === true &&
    $webVerifyData['data']['amount_usdt'] == 75.00,
    'Test 16: Admin Web On-Chain Live Verification verifies transaction on BSC and returns details'
);

// -------------------------------------------------------------
// TEST 17: Admin Web Approve credits p2p_wallet (Fund Wallet) and updates deposit status
// -------------------------------------------------------------
$balanceBeforeWeb = (float) $testMember->fresh()->ad_balance;
$p2pBeforeWeb = (float) $testMember->fresh()->p2p_wallet;
$webApproveReq = Request::create("/admin/deposits/{$targetDeposit->id}/approve", 'POST', [
    'admin_notes' => 'Approved via Web Admin Panel',
]);
$webApproveRes = $webCtrl->approve($webApproveReq, $targetDeposit->id);

$targetDeposit->refresh();
$balanceAfterWeb = (float) $testMember->fresh()->ad_balance;
$p2pAfterWeb = (float) $testMember->fresh()->p2p_wallet;

assertTest(
    $targetDeposit->deposit_status === ImportFund::STATUS_APPROVED &&
    $p2pAfterWeb === ($p2pBeforeWeb + 75.00) &&
    $balanceAfterWeb === ($balanceBeforeWeb + 75.00),
    'Test 17: Admin Web Approve credits member p2p_wallet (Fund Wallet) exactly (+$75.00) and marks status approved'
);

// -------------------------------------------------------------
// TEST 18: Admin Web Reject marks status as rejected without crediting
// -------------------------------------------------------------
$webRejectHash = '0x' . bin2hex(random_bytes(32));
$newRejectReq = Request::create('/api/member/deposit/manual-request', 'POST', [
    'amount' => 30.00,
    'transaction_hash' => $webRejectHash,
]);
$newRejectReq->setUserResolver(fn() => $testMember);
$controller->submitManualRequest($newRejectReq, $verifier);

$targetRejectDeposit = ImportFund::where('transaction_hash', $webRejectHash)->first();
$balanceBeforeRejectWeb = (float) $testMember->fresh()->ad_balance;
$webRejectReq = Request::create("/admin/deposits/{$targetRejectDeposit->id}/reject", 'POST', [
    'rejection_reason' => 'Invalid transaction test',
]);
$webRejectRes = $webCtrl->reject($webRejectReq, $targetRejectDeposit->id);

$targetRejectDeposit->refresh();
$balanceAfterRejectWeb = (float) $testMember->fresh()->ad_balance;

assertTest(
    $targetRejectDeposit->deposit_status === ImportFund::STATUS_REJECTED &&
    $balanceBeforeRejectWeb === $balanceAfterRejectWeb,
    'Test 18: Admin Web Reject marks deposit as rejected and does NOT credit funds'
);

echo "\n=======================================================\n";
echo " TEST SUMMARY: Passed: {$passed} | Failed: {$failed}\n";
echo "=======================================================\n";

exit($failed > 0 ? 1 : 0);

