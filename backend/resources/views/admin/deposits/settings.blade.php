@extends('admin.layouts.master')
@section('title', 'Deposit Settings')
@section('page-subtitle', 'Configure deposit crypto wallet address, BNB Smart Chain network, and instructions.')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-xl-8 col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0 fw-bold text-dark">
                        <i data-feather="settings" class="text-primary me-2"></i> USDT (BEP-20) Deposit Settings
                    </h5>
                    <a href="{{ route('admin.deposits.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i data-feather="arrow-left" style="width: 14px; height: 14px;" class="me-1"></i> Back to Deposits
                    </a>
                </div>

                <div class="card-body p-4">
                    <form action="{{ route('admin.deposits.settings.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf

                        <!-- Destination Crypto Wallet Address -->
                        <div class="mb-4">
                            <label for="crypto_wallet_address" class="form-label fw-bold text-dark">
                                Destination Crypto Wallet Address (BEP-20) <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="crypto_wallet_address" id="crypto_wallet_address" class="form-control font-monospace" placeholder="0x55d398326f99059fF775485246999027B3197955" value="{{ old('crypto_wallet_address', $cryptoWallet) }}" required>
                            <div class="form-text small">
                                Members will send USDT (BEP-20) payments to this wallet address. Must be a valid 42-character Ethereum/BSC hex address.
                            </div>
                        </div>

                        <!-- Network Selection -->
                        <div class="mb-4">
                            <label class="form-label fw-bold text-dark">Blockchain Network</label>
                            <div class="d-flex gap-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="bsc_network" id="net_mainnet" value="mainnet" {{ $network === 'mainnet' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="net_mainnet">
                                        BNB Smart Chain Mainnet (Chain ID 56)
                                        <span class="badge bg-success ms-1">Production Live</span>
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="bsc_network" id="net_testnet" value="testnet" {{ $network === 'testnet' ? 'checked' : '' }}>
                                    <label class="form-check-label fw-semibold" for="net_testnet">
                                        BSC Testnet (Chain ID 97)
                                        <span class="badge bg-warning text-dark ms-1">Testing</span>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Deposit Service Charge / Platform Fee -->
                        <div class="mb-4">
                            <label for="deposit_fee_percent" class="form-label fw-bold text-dark">
                                Deposit Service Charge (%) <span class="badge bg-primary ms-1">Dynamic</span>
                            </label>
                            <div class="input-group">
                                <input type="number" step="0.01" min="0" max="100" name="deposit_fee_percent" id="deposit_fee_percent" class="form-control fw-bold" placeholder="0.00" value="{{ old('deposit_fee_percent', number_format($depositFeePercent ?? 0.00, 2, '.', '')) }}">
                                <span class="input-group-text fw-bold">%</span>
                            </div>
                            <div class="form-text small">
                                Percentage added on top as service charge when members make USDT deposits. Total Payable = Deposit + Fee%. Fund Wallet Credit = Received Amount / (100 + Service Charge %). Set to 0 for Zero Fee mode (100% credited).
                            </div>
                        </div>

                        <!-- Token Contract & Decimals Information -->
                        <div class="mb-4 p-3 bg-light rounded border">
                            <div class="row g-2 small">
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Supported Token:</span>
                                    <strong class="text-dark">USDT (Binance-Peg BEP-20)</strong>
                                </div>
                                <div class="col-sm-6">
                                    <span class="text-muted d-block">Minimum Deposit:</span>
                                    <strong class="text-success fs-6">${{ number_format($minDeposit, 2) }} USD</strong>
                                </div>
                                <div class="col-12 mt-2">
                                    <span class="text-muted d-block">Active USDT Contract:</span>
                                    <code class="text-primary font-monospace">{{ $usdtContract }}</code>
                                </div>
                            </div>
                        </div>

                        <!-- Deposit Instructions -->
                        <div class="mb-4">
                            <label for="deposit_instructions" class="form-label fw-bold text-dark">Member Payment Instructions</label>
                            <textarea name="deposit_instructions" id="deposit_instructions" class="form-control" rows="3" placeholder="Transfer payment in USDT (BEP-20) using the configured crypto wallet address or QR code.">{{ old('deposit_instructions', $instructions) }}</textarea>
                            <div class="form-text small">These instructions are displayed to members on the Deposit page.</div>
                        </div>

                        <!-- QR Code Upload -->
                        <div class="mb-4">
                            <label for="qr_image" class="form-label fw-bold text-dark">Deposit QR Code (Optional)</label>
                            <input type="file" name="qr_image" id="qr_image" class="form-control" accept="image/*">
                            @if(!empty($qrImage))
                                <div class="mt-2 d-flex align-items-center gap-3 p-2 bg-light rounded border" style="width: fit-content;">
                                    <img src="{{ asset($qrImage) }}" alt="Current Deposit QR" class="rounded" style="width: 80px; height: 80px; object-fit: contain; background: #fff;">
                                    <div>
                                        <div class="small fw-bold text-dark">Active QR Code</div>
                                        <span class="small text-muted">Upload a new file to replace</span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                            <a href="{{ route('admin.deposits.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary px-4 fw-bold">
                                <i data-feather="save" style="width: 16px; height: 16px;" class="me-1"></i> Save Deposit Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
