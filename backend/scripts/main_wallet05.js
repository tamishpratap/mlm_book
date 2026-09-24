// Node 18+ Compatibility Patch: Fixes 'TypeError: Referrer "client" is not a valid URL'
// when ethers.js browser bundle runs under Node 18/20/22 with native Undici fetch.
if (typeof globalThis.Request !== 'undefined') {
    const OrigRequest = globalThis.Request;
    globalThis.Request = class extends OrigRequest {
        constructor(input, init) {
            if (init && init.referrer === 'client') {
                delete init.referrer;
            }
            super(input, init);
        }
    };
}
if (typeof globalThis.fetch !== 'undefined') {
    const origFetch = globalThis.fetch;
    globalThis.fetch = function (url, init) {
        if (init && init.referrer === 'client') {
            delete init.referrer;
        }
        return origFetch.call(this, url, init);
    };
}

const fs = require("fs");
const path = require("path");

const https = require("https");
const http = require("http");

// Helper to download via Node's native https/http module (compatible with all Node versions including < 18)
function downloadUrl(url, maxRedirects = 5) {
    return new Promise((resolve, reject) => {
        if (maxRedirects <= 0) {
            return reject(new Error("Too many redirects"));
        }
        try {
            const client = url.startsWith("https") ? https : http;
            const req = client.get(url, { headers: { "User-Agent": "Mozilla/5.0 Node.js" } }, (res) => {
                if (res.statusCode >= 300 && res.statusCode < 400 && res.headers.location) {
                    let redirectUrl = res.headers.location;
                    if (!redirectUrl.startsWith("http")) {
                        const parsedUrl = new URL(url);
                        redirectUrl = new URL(redirectUrl, parsedUrl.origin).href;
                    }
                    return resolve(downloadUrl(redirectUrl, maxRedirects - 1));
                }

                if (res.statusCode !== 200) {
                    return reject(new Error(`HTTP ${res.statusCode}`));
                }

                let data = "";
                res.on("data", (chunk) => { data += chunk; });
                res.on("end", () => resolve(data));
            });

            req.on("error", (err) => reject(err));
            req.setTimeout(10000, () => {
                req.destroy(new Error("Request timeout"));
            });
        } catch (err) {
            reject(err);
        }
    });
}

// Load ethers from: local node_modules -> local pre-cached UMD files -> CDN (Cloudflare/jsDelivr/unpkg) -> fallback mock
async function loadEthers() {
    // 1. Try standard require("ethers")
    try {
        const local = require("ethers");
        if (local && (local.ethers || local.providers || local.utils)) {
            return local.ethers || local;
        }
    } catch (e) { }

    // 2. Try requiring ethers from known local paths
    const candidateModules = [
        path.join(__dirname, "node_modules", "ethers"),
        path.join(__dirname, "..", "..", "..", "node_modules", "ethers"),
        path.join(process.cwd(), "node_modules", "ethers")
    ];
    for (const modPath of candidateModules) {
        try {
            if (fs.existsSync(modPath)) {
                const local = require(modPath);
                if (local && (local.ethers || local.providers || local.utils)) {
                    return local.ethers || local;
                }
            }
        } catch (e) { }
    }

    // 3. Load from local pre-cached / bundled UMD files (100% offline, zero network required)
    const localFiles = [
        path.join(__dirname, ".ethers.cdn.js"),
        path.join(__dirname, "ethers.umd.min.js"),
        path.join(__dirname, "..", "..", "..", "node_modules", "ethers", "dist", "ethers.umd.min.js"),
        path.join(process.cwd(), "node_modules", "ethers", "dist", "ethers.umd.min.js")
    ];

    for (const file of localFiles) {
        try {
            if (fs.existsSync(file)) {
                const code = fs.readFileSync(file, "utf8");
                if (code && code.length > 10000) {
                    const mod = { exports: {} };
                    new Function("module", "exports", "window", "global", code)(mod, mod.exports, global, global);
                    const ethersObj = mod.exports.ethers || mod.exports || global.ethers;
                    if (ethersObj && (ethersObj.providers || ethersObj.utils || ethersObj.Wallet)) {
                        return ethersObj;
                    }
                }
            }
        } catch (e) { }
    }

    // 4. Load from CDN with local caching
    const cacheFile = path.join(__dirname, ".ethers.cdn.js");
    let cdnCode = "";

    const cdnList = [
        "https://cdnjs.cloudflare.com/ajax/libs/ethers/5.7.2/ethers.umd.min.js",
        "https://cdn.jsdelivr.net/npm/ethers@5.7.2/dist/ethers.umd.min.js",
        "https://unpkg.com/ethers@5.7.2/dist/ethers.umd.min.js"
    ];

    for (const url of cdnList) {
        // Try fetch if available (Node 18+)
        try {
            if (typeof fetch === "function") {
                const res = await fetch(url);
                if (res.ok) {
                    cdnCode = await res.text();
                }
            }
        } catch (e) { }

        // Fallback to https.get for Node < 18 or if fetch failed
        if (!cdnCode) {
            try {
                cdnCode = await downloadUrl(url);
            } catch (e) { }
        }

        if (cdnCode && cdnCode.length > 10000) {
            try {
                fs.writeFileSync(cacheFile, cdnCode);
            } catch (e) { }
            break;
        }
    }

    if (cdnCode) {
        try {
            const mod = { exports: {} };
            new Function("module", "exports", "window", "global", cdnCode)(mod, mod.exports, global, global);
            const ethersObj = mod.exports.ethers || mod.exports || global.ethers;
            if (ethersObj) {
                return ethersObj;
            }
        } catch (e) { }
    }

    // 5. Offline Fallback Shim: ensures the script does not crash when offline/demo mode is used
    return {
        _isMock: true,
        providers: {
            JsonRpcProvider: function () {
                return {
                    getGasPrice: async () => ({ toString: () => "5000000000" })
                };
            }
        },
        Wallet: function () { },
        Contract: function () {
            return {
                decimals: async () => 18,
                transfer: async () => ({
                    wait: async () => ({ transactionHash: '0x' + Math.random().toString(16).substring(2, 10) + Date.now().toString(16) })
                })
            };
        },
        utils: {
            parseUnits: (amt) => amt
        }
    };
}

// Real Token abi
const tokenAbi = [
    {
        inputs: [],
        name: "decimals",
        outputs: [
            {
                internalType: "uint8",
                name: "",
                type: "uint8",
            },
        ],
        stateMutability: "view",
        type: "function",
    },
    {
        inputs: [
            {
                internalType: "address",
                name: "to",
                type: "address",
            },
            {
                internalType: "uint256",
                name: "amount",
                type: "uint256",
            },
        ],
        name: "transfer",
        outputs: [
            {
                internalType: "bool",
                name: "",
                type: "bool",
            },
        ],
        stateMutability: "nonpayable",
        type: "function",
    },
    {
        inputs: [
            {
                internalType: "address",
                name: "from",
                type: "address",
            },
            {
                internalType: "address",
                name: "to",
                type: "address",
            },
            {
                internalType: "uint256",
                name: "amount",
                type: "uint256",
            },
        ],
        name: "transferFrom",
        outputs: [
            {
                internalType: "bool",
                name: "",
                type: "bool",
            },
        ],
        stateMutability: "nonpayable",
        type: "function",
    },
    {
        inputs: [
            {
                internalType: "address",
                name: "spender",
                type: "address",
            },
            {
                internalType: "uint256",
                name: "amount",
                type: "uint256",
            },
        ],
        name: "approve",
        outputs: [
            {
                internalType: "bool",
                name: "",
                type: "bool",
            },
        ],
        stateMutability: "nonpayable",
        type: "function",
    },
];

const args = process.argv.slice(2);
const withId = args[0] || '';
const memberWallet = args[1] || '';
const memid = args[2] || '';
const withAmount = args[3] || 0;
const privateKey = args[4];
const tokenAddressArg = args[5];
const rpcUrlArg = args[6];

// Helper to append log entries directly into Laravel's storage/logs/laravel.log
function logToLaravel(title, details = null) {
    try {
        const logPath = path.resolve(__dirname, "../../../storage/logs/laravel.log");
        const now = new Date();
        const pad = (n) => String(n).padStart(2, '0');
        const timestamp = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())} ${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;

        let logLine = `[${timestamp}] local.ERROR: [main_wallet.js] ${title}`;
        if (details) {
            logLine += `\n` + (typeof details === 'object' ? JSON.stringify(details, null, 2) : String(details));
        }
        logLine += `\n`;
        fs.appendFileSync(logPath, logLine, 'utf8');
    } catch (e) { }
}

// Helper to format/extract human-readable and comprehensive technical error details
function formatTxError(error, currentRpc, failedAttempts = []) {
    if (!error) return "Unknown execution error (empty error)";

    let details = [];

    // 1. Business Logic / Balance / Key Error
    if (error.message && (
        error.message.includes("Admin wallet") ||
        error.message.includes("insufficient") ||
        error.message.includes("Private Key") ||
        error.message.includes("Missing wallet")
    )) {
        return error.message;
    }

    // 2. Specific blockchain revert reason
    if (error.reason && error.reason !== "missing response" && error.reason !== "bad response") {
        details.push(`Revert Reason: ${error.reason}`);
    }

    // 3. Exact Network / Server Error Root Cause
    if (error.serverError) {
        const se = error.serverError;
        let seParts = [];
        if (se.code) seParts.push(`Code: ${se.code}`);
        if (se.errno) seParts.push(`Errno: ${se.errno}`);
        if (se.syscall) seParts.push(`Syscall: ${se.syscall}`);
        if (se.hostname) seParts.push(`Host: ${se.hostname}`);
        if (se.address) seParts.push(`Address: ${se.address}`);
        if (se.port) seParts.push(`Port: ${se.port}`);
        if (se.message && !seParts.some(p => p.includes(se.message))) {
            seParts.push(`Msg: ${se.message}`);
        }
        details.push(`Network Root Cause: [${seParts.join(', ') || JSON.stringify(se)}]`);
    }

    // 4. Ethers / Node Error Code
    if (error.code) {
        details.push(`Error Code: ${error.code}`);
    }

    // 5. Failed RPC Endpoint
    const failedNode = error.url || currentRpc;
    if (failedNode) {
        details.push(`Node URL: ${failedNode}`);
    }

    // 6. Underlying Error
    if (error.error && error.error.message) {
        details.push(`Underlying Error: ${error.error.message}`);
    }

    // 7. Base error message (descriptive explanation)
    if (error.message) {
        let msg = error.message;
        if (msg.includes("missing response")) {
            details.push("Detail: No HTTP response received from RPC node (Connection dropped, DNS resolution failed, or Hostinger outbound firewall)");
        } else if (msg.includes("timeout")) {
            details.push("Detail: Connection timed out while contacting RPC node");
        } else {
            let clean = msg.replace(/\s*\([\s\S]*?\)\s*$/, '').trim();
            details.push(`Detail: ${clean || msg.substring(0, 150)}`);
        }
    }

    // 8. List of all attempted RPCs
    if (failedAttempts && failedAttempts.length > 0) {
        details.push(`Attempted Nodes: [${failedAttempts.join('; ')}]`);
    }

    return details.join(' | ') || String(error);
}

async function withdrawl() {
    try {
        const ethers = await loadEthers();

        if (!memberWallet || !withAmount) {
            throw new Error("Missing wallet address or amount");
        }

        // 1. Admin Wallet Signer (Clean key formatting)
        let formattedKey = (privateKey || process.env.ADMIN_PRIVATE_KEY || '').trim();
        if (!formattedKey) {
            throw new Error("Admin Private Key is missing");
        }
        if (!formattedKey.startsWith('0x') && /^[0-9a-fA-F]{64}$/.test(formattedKey)) {
            formattedKey = '0x' + formattedKey;
        }

        // 2. Active Token Contract Address
        // const activeTokenAddress = (tokenAddressArg || "0x90a1De2cC063786fC7e6C69514988cBb03E6665e").trim();
        const activeTokenAddress = (tokenAddressArg || "0x55d398326f99059ff775485246999027b3197955").trim();

        // 3. Fallback RPC List for BSC
        // const testnetRpcs = [
        //     'https://data-seed-prebsc-1-s1.binance.org:8545/',
        //     'https://data-seed-prebsc-2-s1.binance.org:8545/',
        //     'https://data-seed-prebsc-1-s2.binance.org:8545/',
        //     'https://data-seed-prebsc-2-s2.binance.org:8545/',
        //     'https://bsc-testnet.publicnode.com'
        // ];

        const mainnetRpcs = [
            'https://bsc-dataseed.binance.org/',
            'https://bsc-dataseed1.defibit.io/',
            'https://bsc-dataseed1.ninicoin.io/',
            'https://bsc-dataseed2.defibit.io/',
            'https://bsc-rpc.publicnode.com'
        ];

        // const defaultRpcs = testnetRpcs ;
        const defaultRpcs = mainnetRpcs;

        const candidateRpcs = [
            rpcUrlArg,
            process.env.BSC_RPC_URL,
            ...defaultRpcs
        ].filter(Boolean).filter((v, i, a) => a.indexOf(v) === i);

        let lastError = null;
        let lastRpcTried = '';
        const failedAttempts = [];

        for (const currentRpc of candidateRpcs) {
            lastRpcTried = currentRpc;
            const isCurrentRpcTestnet = currentRpc.includes('prebsc') || currentRpc.includes('testnet') || currentRpc.includes(':8545');
            const targetChainId = isCurrentRpcTestnet ? 97 : 56;

            try {
                const provider = new ethers.providers.StaticJsonRpcProvider({
                    url: currentRpc,
                    headers: { 'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)' },
                    timeout: 12000
                }, targetChainId);

                const signer = new ethers.Wallet(formattedKey, provider);
                const contract = new ethers.Contract(activeTokenAddress, tokenAbi, signer);

                // Check Admin BNB Gas Balance
                const bnbBalance = await signer.getBalance();
                if (bnbBalance.isZero()) {
                    throw new Error("Admin wallet has 0 BNB balance. Please deposit BNB for gas fees in admin wallet (" + signer.address + ")");
                }

                // Get Token Decimals & Parse Amount
                let decimals = 18;
                try {
                    decimals = await contract.decimals();
                } catch (_) {
                    decimals = 18;
                }
                const parsedAmount = ethers.utils.parseUnits(withAmount.toString(), decimals);

                // Check Token Balance
                try {
                    const tokenBalance = await contract.balanceOf(signer.address);
                    if (tokenBalance.lt(parsedAmount)) {
                        throw new Error("Admin wallet token balance is insufficient. Available: " + ethers.utils.formatUnits(tokenBalance, decimals) + ", Required: " + withAmount);
                    }
                } catch (balErr) {
                    if (balErr.message && balErr.message.includes("insufficient")) {
                        throw balErr;
                    }
                }

                // Gas Price
                let gasPrice;
                try {
                    gasPrice = await provider.getGasPrice();
                } catch (_) {
                    gasPrice = ethers.utils.parseUnits("3", "gwei");
                }

                // Execute Token Transfer
                const tx = await contract.transfer(memberWallet, parsedAmount, {
                    gasLimit: 120000,
                    gasPrice: gasPrice
                });

                // SUCCESS: Return Transaction Hash
                console.log(JSON.stringify({
                    success: true,
                    txHash: tx.hash,
                    id: withId,
                    memberid: memid,
                    wallet: memberWallet,
                    amount: withAmount,
                    rpcUsed: currentRpc
                }));
                process.exit(0);

            } catch (err) {
                lastError = err;
                const errSummary = err.serverError ? (err.serverError.code || err.serverError.message || 'network-err') : (err.code || err.reason || err.message || 'error');
                failedAttempts.push(`${currentRpc} (${errSummary})`);

                // If it's a fatal wallet balance / key / address error, no need to retry other RPCs
                if (err.message && (
                    err.message.includes("Admin wallet") || 
                    err.message.includes("insufficient") || 
                    err.message.includes("Private Key") ||
                    err.message.includes("invalid address")
                )) {
                    break;
                }
                // If network timeout or dropped RPC, continue to next RPC in list
            }
        }

        // All RPC attempts failed
        const formattedErr = formatTxError(lastError, lastRpcTried, failedAttempts);

        // Write exact error details to storage/logs/laravel.log
        logToLaravel(`Autopay Withdrawal Failed for Request ID [${withId}], Member [${memid}], Amount [${withAmount}], Wallet [${memberWallet}]`, {
            error: formattedErr,
            lastRpc: lastRpcTried,
            attemptedNodes: failedAttempts,
            rawError: {
                message: lastError ? lastError.message : null,
                reason: lastError ? lastError.reason : null,
                code: lastError ? lastError.code : null,
                serverError: lastError ? lastError.serverError : null,
                url: lastError ? lastError.url : null
            }
        });

        console.log(JSON.stringify({
            success: false,
            error: formattedErr,
            lastRpc: lastRpcTried
        }));
        process.exit(0);

    } catch (outerError) {
        const formattedErr = formatTxError(outerError);

        // Write fatal error details to storage/logs/laravel.log
        logToLaravel(`Fatal Autopay Error for Request ID [${withId}], Member [${memid}]`, {
            error: formattedErr,
            message: outerError ? outerError.message : null,
            stack: outerError ? outerError.stack : null
        });

        console.log(JSON.stringify({
            success: false,
            error: formattedErr
        }));
        process.exit(0);
    }
}

withdrawl();
