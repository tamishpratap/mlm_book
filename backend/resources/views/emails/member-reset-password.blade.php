<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Your Password | MLM Book</title>
</head>
<body style="margin: 0; padding: 32px 16px; background-color: #f4f7ff; color: #26354f; font-family: 'Inter', Arial, sans-serif;">
    <div style="max-width: 560px; margin: 0 auto; padding: 36px 32px; border: 1px solid #e2e7f0; border-radius: 20px; background-color: #ffffff; box-shadow: 0 10px 30px rgba(35, 48, 80, 0.05);">
        <!-- Logo -->
        <div style="margin-bottom: 28px;">
            <img src="{{ asset('logo/logo.png') }}" alt="MLM Book" style="display: block; width: 92px; height: auto; object-fit: contain;">
        </div>

        <!-- Heading -->
        <h1 style="margin: 0 0 12px; color: #111c35; font-size: 24px; font-weight: 700; letter-spacing: -0.02em;">Reset Your Password</h1>
        
        <p style="margin: 0 0 20px; color: #475467; font-size: 15px; line-height: 1.6;">
            Hello <strong>{{ $memberName }}</strong>,
        </p>
        
        <p style="margin: 0 0 24px; color: #475467; font-size: 15px; line-height: 1.6;">
            We received a request to reset the password for your MLM Book account. Click the button below to set a new password.
        </p>

        <!-- CTA Reset Button -->
        <div style="margin: 32px 0; text-align: center;">
            <a href="{{ $resetUrl }}" target="_blank" style="display: inline-block; padding: 14px 32px; background: linear-gradient(135deg, #176bff 0%, #7146ed 100%); color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; border-radius: 12px; box-shadow: 0 10px 24px rgba(23, 107, 255, 0.28);">
                Reset Password
            </a>
        </div>

        <p style="margin: 0 0 16px; color: #64748b; font-size: 13.5px; line-height: 1.6;">
            Or copy and paste this link into your browser:<br>
            <a href="{{ $resetUrl }}" style="color: #176bff; word-break: break-all; font-size: 13px;">{{ $resetUrl }}</a>
        </p>

        <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid #edf2f7; color: #94a3b8; font-size: 13px; line-height: 1.5;">
            <p style="margin: 0 0 8px;"><strong>Expiration notice:</strong> This password reset link will expire in 60 minutes.</p>
            <p style="margin: 0 0 8px;"><strong>Security note:</strong> If you did not request a password reset, no further action is required. Your account password remains unchanged and secure.</p>
            <p style="margin: 16px 0 0; color: #cbd5e1; font-size: 12px;">&copy; {{ date('Y') }} MLM Book. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
