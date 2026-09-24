<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Confirm your email change | MLM Book</title>
</head>
<body style="margin:0;padding:32px 16px;background:#f4f7ff;color:#26354f;font-family:Arial,sans-serif;">
    <div style="max-width:560px;margin:0 auto;padding:32px;border:1px solid #e2e7f0;border-radius:18px;background:#ffffff;">
        <img src="{{ asset('logo/logo.png') }}" alt="MLM Book" style="display:block;width:88px;height:auto;margin:0 0 22px;object-fit:contain;">
        <h1 style="margin:0 0 12px;color:#111c35;font-size:24px;">Confirm your email change</h1>
        <p style="margin:0 0 24px;line-height:1.6;">Hello {{ $memberName }}, use this verification code to confirm the new email address requested for your MLM Book account.</p>
        <div style="padding:18px;border-radius:14px;background:#eef3ff;color:#176bff;font-size:32px;font-weight:700;letter-spacing:8px;text-align:center;">{{ $otpCode }}</div>
        <p style="margin:24px 0 0;color:#667085;line-height:1.6;">This code expires in 10 minutes. If you did not request this change, you can safely ignore this email.</p>
    </div>
</body>
</html>
