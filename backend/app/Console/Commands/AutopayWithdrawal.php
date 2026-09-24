<?php

namespace App\Console\Commands;

use App\Models\Member;
use App\Models\WithdrawalRequest;
use App\Models\WithdrawalSetting;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AutopayWithdrawal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:autopay-withdrawal';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle(?Request $request = null)
    {
        
        $setting = WithdrawalSetting::find(1);
        $privateKey = $setting->private_key;
        // BSC Testnet RPC & Mock USDT (Mainnet par switch karne ke liye niche wale uncomment karein)
        $rpcUrl = 'https://data-seed-prebsc-1-s1.binance.org:8545/'; // BSC Testnet RPC
        $tokenAddress ='0x90a1De2cC063786fC7e6C69514988cBb03E6665e'; // Testnet Mock USDT
        // $rpcUrl = 'https://bsc-dataseed.binance.org/'; // BSC Mainnet
        // $tokenAddress = '0x55d398326f99059ff775485246999027b3197955'; // Real USDT (BSC Mainnet)
        $maxAutoAmount = 10000;

        // 1. Fetch candidate Verified Request IDs
        $candidateIds = WithdrawalRequest::where('status', 'Verified')
            ->whereNull('txn_remarks')
            ->orderBy('created_at', 'ASC')
            ->limit(2)
            ->pluck('id')
            ->toArray();

        if (empty($candidateIds)) {
            // return response()->json([
            //     'code' => 1,
            //     'message' => 'No Verified requests found.',
            //     'processed' => 0,
            //     'details' => []
            // ]);
        }

        // 2. ATOMIC CLAIM: Update status from 'Verified' -> 'Processing' atomically per row.
        // If 2 crons/requests run at the exact same millisecond, MySQL atomic UPDATE ensures only ONE process succeeds.
        $claimedIds = [];
        foreach ($candidateIds as $candId) {
            $updated = WithdrawalRequest::where('id', $candId)
                ->where('status', 'Verified')
                ->update(['status' => 'Processing']);

            if ($updated > 0) {
                $claimedIds[] = $candId;
            }
        }

        if (empty($claimedIds)) {
        }

        // 3. Fetch ONLY the rows successfully claimed by THIS thread
        $verifiedRequests = WithdrawalRequest::whereIn('id', $claimedIds)->get();

        $maxAutoAmount = 1000; // Example max auto withdrawal amount
        $processedCount = 0;
        $details = [];

        foreach ($verifiedRequests as $value) {
            $id = $value->id;
            $memberid = $value->memberid;
            $net_amount = (float) $value->net_amount;
            $wallet_address = trim($value->wallet_address);

            // Validation 1: Empty Wallet or Invalid Amount
            if (empty($wallet_address) || $net_amount <= 0) {
                $value->status = 'Verified';
                $value->txn_remarks = 'Invalid wallet address or net amount, wallet_address - '. $wallet_address .', amount - $'. $net_amount;
                $value->save();
                continue;
            }

            // Security Check 2: Max Single Withdrawal Cap
            if ($net_amount > $maxAutoAmount) {
                $value->status = 'Verified'; // Release back to Verified for manual review
                $value->txn_remarks = 'Withdrawal amount exceeds max auto limit of ' . $maxAutoAmount . ', amount - '. $net_amount;
                $value->save();
                continue;
            }

            // Security Check 3: Verify Member Account is Active
            $member = Member::where('user_id', $memberid)->first();
            if (!$member || $member->status !== 'Active') {
                $value->status = 'Verified'; // Release back to Verified
                $value->txn_remarks = 'Member is not Active';
                $value->save();
                continue;
            }

            $payload = [
                'id' => $id,
                'wallet_address' => $wallet_address,
                'memberid' => $memberid,
                'net_amount' => $net_amount,
                'privateKey' => $privateKey,
                'tokenAddress' => $tokenAddress,
                'rpcUrl' => $rpcUrl,
            ];

            $result = $this->dispatchWalletTransaction($payload);

            if ($result && isset($result['success']) && $result['success'] === true) {
                $txHash = $result['txHash'];

                $value->txnid = $txHash;
                $value->txn_remarks = 'AutopayWithdrawal executed successfully';
                $value->status = 'Approved';
                $value->payment_date = date('Y-m-d H:i:s');
                $value->save();
                
            } else {
                $error = $result['error'] ?? 'Unknown execution error';
                
                // Write detailed failure info to storage/logs/laravel.log
                Log::error("Autopay Withdrawal Failed for Request ID [{$id}], Member [{$memberid}], Amount [{$net_amount}], Wallet [{$wallet_address}]: " . (is_array($result) ? json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : $result));

                // Revert status to 'Verified' so it can be retried or reviewed
                $value->status = 'Verified';
                $value->txn_remarks = substr((string) $error, 0, 500);
                $value->save();
            }
        }
    }

    /**
     * Smart Transaction Dispatcher:
     * Method 1: If WALLET_MICROSERVICE_URL is set in .env (e.g. Free Vercel API), calls it securely via HTTPS.
     *           -> 0 Cost, No Hostinger Upgrade needed!
     * Method 2: If not set, executes locally via PHP CLI (proc_open/exec) without calling http://127.0.0.1:8000.
     */
    protected function dispatchWalletTransaction(array $payload)
    {
        $microserviceUrl = env('WALLET_MICROSERVICE_URL');
        $apiKey = env('WALLET_MICROSERVICE_KEY', env('CRON_SECRET_KEY', 'RedCoinCronKey2026!'));

        // Strategy 1: Free Cloud Microservice (Vercel / Render Serverless)
        if (!empty($microserviceUrl)) {
            try {
                $response = Http::withHeaders([
                    'x-api-key' => $apiKey,
                    'Accept' => 'application/json'
                ])->timeout(45)->post($microserviceUrl, $payload);

                if ($response->successful()) {
                    $json = $response->json();
                    if (is_array($json)) {
                        return $json;
                    }
                }

                return [
                    'success' => false,
                    'error' => 'Microservice HTTP Error (' . $response->status() . '): ' . $response->body()
                ];
            } catch (\Exception $e) {
                return [
                    'success' => false,
                    'error' => 'Microservice connection failed: ' . $e->getMessage()
                ];
            }
        }

        // Strategy 2: Direct Local Node execution
        return $this->executeLocalWalletScript($payload);
    }

    /**
     * Execute main_wallet.js directly on the local server without network loopback to port 8000
     */
    protected function executeLocalWalletScript(array $payload)
    {
        $id = $payload['id'];
        $wallet_address = $payload['wallet_address'];
        $memberid = $payload['memberid'];
        $net_amount = $payload['net_amount'];
        $privateKey = $payload['privateKey'];
        $tokenAddress = $payload['tokenAddress'];
        $rpcUrl = $payload['rpcUrl'];

        $scriptPath = public_path('scripts/main_wallet05.js');

        if (!file_exists($scriptPath)) {
            return [
                'success' => false,
                'error' => 'Script file main_wallet.js not found at ' . $scriptPath
            ];
        }

        // Helper to check if a function is disabled in php.ini
        $isAllowed = function ($fn) {
            if (!function_exists($fn)) {
                return false;
            }
            $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
            return !in_array($fn, $disabled, true);
        };

        // 1. Detect Node binary path for Hostinger / Linux / Local environments
        $nodeBinary = env('NODE_PATH') ?? env('NODE_BINARY');

        if ($nodeBinary && (!@file_exists($nodeBinary) && $nodeBinary !== 'node')) {
            $nodeBinary = null;
        }

        $nullDev = DIRECTORY_SEPARATOR === '/' ? '2>/dev/null' : '2>nul';

        // Strategy A: Check 'which node'
        if (!$nodeBinary && $isAllowed('exec')) {
            $whichOut = [];
            @exec("which node {$nullDev}", $whichOut);
            if (!empty($whichOut[0]) && @file_exists(trim($whichOut[0]))) {
                $nodeBinary = trim($whichOut[0]);
            }
        }

        // Strategy B: Check via bash sourcing NVM / bashrc
        if (!$nodeBinary && $isAllowed('exec') && DIRECTORY_SEPARATOR === '/') {
            $bashOut = [];
            @exec("bash -c \"source ~/.nvm/nvm.sh {$nullDev} || source ~/.bashrc {$nullDev}; which node\" {$nullDev}", $bashOut);
            if (!empty($bashOut[0]) && @file_exists(trim($bashOut[0]))) {
                $nodeBinary = trim($bashOut[0]);
            }
        }

        // Strategy C: Scan known Hostinger / Linux / CloudLinux / cPanel filesystem locations
        if (!$nodeBinary) {
            $home = getenv('HOME') ?: ($_SERVER['HOME'] ?? '');
            if (!$home && isset($_SERVER['DOCUMENT_ROOT'])) {
                if (preg_match('#^(/home[0-9]?/[^/]+)#', $_SERVER['DOCUMENT_ROOT'], $m)) {
                    $home = $m[1];
                }
            }

            $searchCandidates = [
                '/usr/bin/node',
                '/usr/local/bin/node',
                '/bin/node',
            ];

            if ($home) {
                $searchCandidates[] = $home . '/.local/bin/node';
                $searchCandidates[] = $home . '/bin/node';
                $nvmVersions = @glob($home . '/.nvm/versions/node/*/bin/node');
                if ($nvmVersions) {
                    rsort($nvmVersions);
                    $searchCandidates = array_merge($searchCandidates, $nvmVersions);
                }
            }

            $globalNvm = @glob('/usr/local/nvm/versions/node/*/bin/node');
            if ($globalNvm) {
                rsort($globalNvm);
                $searchCandidates = array_merge($searchCandidates, $globalNvm);
            }

            $altNode = @glob('/opt/alt/alt-nodejs*/root/usr/bin/node');
            if ($altNode) {
                rsort($altNode);
                $searchCandidates = array_merge($searchCandidates, $altNode);
            }

            $eaNode = @glob('/opt/cpanel/ea-nodejs*/root/usr/bin/node');
            if ($eaNode) {
                rsort($eaNode);
                $searchCandidates = array_merge($searchCandidates, $eaNode);
            }

            $searchCandidates[] = 'C:\\Program Files\\nodejs\\node.exe';
            $searchCandidates[] = 'C:\\Program Files (x86)\\nodejs\\node.exe';

            foreach ($searchCandidates as $candidate) {
                if (!empty($candidate) && @file_exists($candidate) && @is_executable($candidate)) {
                    $nodeBinary = $candidate;
                    break;
                }
            }
        }

        // Strategy D: Fallback test if 'node' command is accessible directly
        if (!$nodeBinary) {
            $testOut = [];
            $testCode = 1;
            if ($isAllowed('exec')) {
                @exec('node -v 2>&1', $testOut, $testCode);
                if ($testCode === 0) {
                    $nodeBinary = 'node';
                }
            }
        }

        if (!$nodeBinary) {
            return [
                'success' => false,
                'error' => 'Node.js is not found. Either deploy free microservice on Vercel and set WALLET_MICROSERVICE_URL in .env, or set NODE_PATH in .env.'
            ];
        }

        $nodeDir = dirname($nodeBinary);
        if ($nodeDir && $nodeDir !== '.') {
            $currPath = getenv('PATH') ?: '/usr/local/bin:/usr/bin:/bin';
            if (strpos($currPath, $nodeDir) === false) {
                @putenv('PATH=' . $nodeDir . ':' . $currPath);
            }
        }

        $args = [
            (string) ($id ?? ''),
            (string) ($wallet_address ?? ''),
            (string) ($memberid ?? ''),
            (string) ($net_amount ?? ''),
            (string) ($privateKey ?? ''),
            (string) ($tokenAddress ?? ''),
            (string) ($rpcUrl ?? ''),
        ];

        $escapedArgs = array_map(function ($arg) {
            return escapeshellarg($arg);
        }, $args);

        $commandLine = escapeshellcmd($nodeBinary) . ' ' . escapeshellarg($scriptPath) . ' ' . implode(' ', $escapedArgs);

        $output = '';

        $nodeModules = base_path('node_modules');
        if (file_exists($nodeModules)) {
            @putenv('NODE_PATH=' . $nodeModules);
        }
        if ($privateKey) {
            @putenv('ADMIN_PRIVATE_KEY=' . $privateKey);
        }
        if ($tokenAddress) {
            @putenv('TOKEN_CONTRACT_ADDRESS=' . $tokenAddress);
        }
        if ($rpcUrl) {
            @putenv('BSC_RPC_URL=' . $rpcUrl);
        }

        // 1. proc_open
        if ($isAllowed('proc_open')) {
            $descriptors = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w']
            ];
            $pipes = [];
            $process = @proc_open($commandLine, $descriptors, $pipes, base_path(), null);
            if (is_resource($process)) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[0]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                proc_close($process);

                $output = trim($stdout);
                if (empty($output) && !empty(trim($stderr))) {
                    $output = trim($stderr);
                }
            }
        }

        // 2. exec()
        if (empty($output) && $isAllowed('exec')) {
            $execOutput = [];
            $returnVar = 0;
            @exec($commandLine . ' 2>&1', $execOutput, $returnVar);
            $output = trim(implode("\n", $execOutput));
        }

        // 3. popen()
        if (empty($output) && $isAllowed('popen')) {
            $handle = @popen($commandLine . ' 2>&1', 'r');
            if ($handle) {
                $output = trim(stream_get_contents($handle));
                pclose($handle);
            }
        }

        // 4. passthru() / system()
        if (empty($output) && $isAllowed('passthru')) {
            ob_start();
            @passthru($commandLine . ' 2>&1');
            $output = trim(ob_get_clean());
        } elseif (empty($output) && $isAllowed('system')) {
            ob_start();
            @system($commandLine . ' 2>&1');
            $output = trim(ob_get_clean());
        }

        if (empty($output) && !$isAllowed('proc_open') && !$isAllowed('exec') && !$isAllowed('popen') && !$isAllowed('passthru') && !$isAllowed('system')) {
            return [
                'success' => false,
                'error' => 'Command execution functions are disabled in Hostinger php.ini. Please deploy free serverless microservice to Vercel and add WALLET_MICROSERVICE_URL to .env (0 upgrade required).'
            ];
        }

        $result = json_decode($output, true);

        if (!is_array($result) && !empty($output)) {
            if (preg_match('/\{[\s\S]*\}/', $output, $matches)) {
                $result = json_decode($matches[0], true);
            }
        }

        if (is_array($result)) {
            return $result;
        }

        $parseError = 'Failed to parse script response: ' . ($output ?: 'Empty output returned from wallet script');
        Log::error("executeLocalWalletScript Error for Request ID [{$id}]: {$parseError}\nCommand Line: {$commandLine}\nRaw Output: {$output}");

        return [
            'success' => false,
            'error' => $parseError
        ];
    }

    protected function processWallet(Request $request)
    {
        $authKey = $request->header('x-api-key') ?: $request->input('key');
        $validKey = env('WALLET_MICROSERVICE_KEY', env('CRON_SECRET_KEY', 'RedCoinCronKey2026!'));
        if ($authKey && $authKey !== $validKey) {
            return response()->json(['success' => false, 'error' => 'Unauthorized access'], 403);
        }

        $payload = [
            'id' => $request->input('id'),
            'wallet_address' => $request->input('wallet_address'),
            'memberid' => $request->input('memberid'),
            'net_amount' => $request->input('net_amount'),
            'privateKey' => $request->input('privateKey'),
            'tokenAddress' => $request->input('tokenAddress'),
            'rpcUrl' => $request->input('rpcUrl'),
        ];

        $result = $this->executeLocalWalletScript($payload);
        $status = (isset($result['success']) && $result['success'] === true) ? 200 : 500;

        return response()->json($result, $status);
    }
}
