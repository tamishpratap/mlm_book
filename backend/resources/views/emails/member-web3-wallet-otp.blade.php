<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web3 Wallet Verification Code</title>
</head>
<body style="margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b;">
    <div style="max-width: 560px; margin: 30px auto; background-color: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);">
        <div style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); padding: 24px; text-align: center; color: #ffffff;">
            <h1 style="margin: 0; font-size: 20px; font-weight: 800; letter-spacing: -0.5px;">MLM Book</h1>
            <p style="margin: 4px 0 0 0; font-size: 13px; opacity: 0.9;">Web3 Wallet Security Verification</p>
        </div>
        <div style="padding: 28px 24px;">
            <p style="margin: 0 0 16px 0; font-size: 15px;">Hello <strong>{{ $memberName }}</strong>,</p>
            <p style="margin: 0 0 16px 0; font-size: 14px; color: #475569; line-height: 1.5;">
                We received a request to verify and activate the following <strong>USDT (BEP-20)</strong> destination wallet address for your MLM Book account:
            </p>
            
            <div style="background-color: #f1f5f9; border-radius: 8px; padding: 12px; font-family: monospace; font-size: 13px; color: #0f172a; word-break: break-all; margin-bottom: 20px; border: 1px solid #cbd5e1;">
                {{ $walletAddress }}
            </div>

            <p style="margin: 0 0 8px 0; font-size: 14px; color: #475569;">
                Use the one-time verification code below to authorize this wallet address:
            </p>

            <div style="text-align: center; margin: 24px 0;">
                <span style="display: inline-block; background-color: #f0fdf4; border: 2px dashed #059669; color: #047857; font-size: 28px; font-weight: 800; letter-spacing: 6px; padding: 12px 28px; border-radius: 10px;">
                    {{ $otpCode }}
                </span>
            </div>

            <p style="margin: 0 0 8px 0; font-size: 12.5px; color: #64748b; line-height: 1.5;">
                ⏰ This verification code is valid for <strong>10 minutes</strong>.
            </p>
            <p style="margin: 0 0 16px 0; font-size: 12.5px; color: #64748b; line-height: 1.5;">
                🛡️ If you did not make this request, please change your password and secure your account immediately. Never share this code or your private keys with anyone.
            </p>
        </div>
        <div style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 16px; text-align: center; font-size: 12px; color: #94a3b8;">
            &copy; {{ date('Y') }} MLM Book. All rights reserved.
        </div>
    </div>
</body>
</html>