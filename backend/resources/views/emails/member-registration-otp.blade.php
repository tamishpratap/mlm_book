<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verify your email address | MLM Book</title>
</head>
<body style="margin:0;padding:32px 16px;background:#f4f7ff;color:#26354f;font-family:Arial,sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0">
        <tr>
            <td align="center">
                <div style="max-width:560px;margin:0 auto;padding:36px;border:1px solid #e2e7f0;border-radius:18px;background:#ffffff;text-align:left;box-shadow:0 4px 16px rgba(17,28,53,0.06);">
                    <div style="margin-bottom:24px;">
                        <img src="{{ asset('logo/logo.png') }}" alt="MLM Book" style="display:block;width:96px;height:auto;margin:0 0 16px;object-fit:contain;">
                    </div>
                    <h1 style="margin:0 0 12px;color:#111c35;font-size:24px;font-weight:700;">Verify your email address</h1>
                    <p style="margin:0 0 20px;line-height:1.6;font-size:15px;color:#475467;">
                        Hello <strong>{{ $memberName }}</strong>, thank you for registering with MLM Book. Please use the following 6-digit verification code to complete your registration:
                    </p>
                    <div style="padding:20px;border-radius:14px;background:#eef3ff;border:1px solid #d4e2ff;color:#176bff;font-size:36px;font-weight:800;letter-spacing:10px;text-align:center;margin:24px 0;">
                        {{ $otpCode }}
                    </div>
                    <div style="background:#f8fafc;border-radius:10px;padding:14px 18px;margin-top:20px;border-left:4px solid #176bff;">
                        <p style="margin:0;font-size:14px;color:#334155;line-height:1.5;">
                            <strong>Expires in:</strong> 10 minutes<br>
                            <strong>Important:</strong> Do not share this verification code with anyone. MLM Book will never ask for your code.
                        </p>
                    </div>
                    <p style="margin:24px 0 0;color:#98a2b3;font-size:13px;line-height:1.5;">
                        If you did not request to create an MLM Book account with <strong>{{ $memberEmail }}</strong>, you can safely ignore this email.
                    </p>
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
