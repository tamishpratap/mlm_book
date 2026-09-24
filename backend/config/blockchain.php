<?php

return [
    /*
    |--------------------------------------------------------------------------
    | BNB Smart Chain (BSC) Configuration
    |--------------------------------------------------------------------------
    */
    'bsc' => [
        'network' => env('BSC_NETWORK', 'mainnet'), // 'mainnet' or 'testnet'
        'test_mode' => (bool) env('DEPOSIT_TEST_MODE', env('APP_ENV') === 'local'),

        'rpc_endpoints' => [
            'mainnet' => [
                'https://bsc-dataseed.binance.org/',
                'https://bsc-dataseed1.defibit.io/',
                'https://bsc-dataseed1.ninicoin.io/',
                'https://binance.llamarpc.com',
                'https://rpc.ankr.com/bsc',
            ],
            'testnet' => [
                'https://data-seed-prebsc-1-s1.binance.org:8545/',
                'https://data-seed-prebsc-2-s1.binance.org:8545/',
                'https://rpc.ankr.com/bsc_testnet_chapel',
            ],
        ],

        // Binance-Peg USDT (BEP-20) Contract Addresses
        'usdt_contract' => [
            'mainnet' => env('BSC_USDT_CONTRACT', '0x55d398326f99059fF775485246999027B3197955'),
            'testnet' => env('BSC_TESTNET_USDT_CONTRACT', '0x337610d27c682E347C9cD60BD4b3b107C9d34dDd'),
        ],

        // Binance-Peg USDT uses 18 decimals on BSC
        'usdt_decimals' => (int) env('BSC_USDT_DECIMALS', 18),

        // Minimum required block confirmations
        'min_confirmations' => (int) env('BSC_MIN_CONFIRMATIONS', 1),

        // Timeout for RPC requests in seconds
        'rpc_timeout' => (int) env('BSC_RPC_TIMEOUT', 10),

        // Explorer URL templates
        'explorer_tx_url' => [
            'mainnet' => 'https://bscscan.com/tx/',
            'testnet' => 'https://testnet.bscscan.com/tx/',
        ],
    ],
];