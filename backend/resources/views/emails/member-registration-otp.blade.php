<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Verify Your Email Address | MLM Book</title>
    <style>
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; padding: 24px 16px !important; }
            .content-card { padding: 28px 20px !important; border-radius: 16px !important; }
            .otp-code { font-size: 30px !important; letter-spacing: 6px !important; padding: 14px 16px !important; }
            .header-logo { width: 44px !important; height: 44px !important; }
            .brand-name { font-size: 18px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0f172a;">
    <!-- Preheader preview text -->
    <div style="display: none; font-size: 1px; color: #f1f5f9; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        Welcome to MLM Book! Your account verification code is {{ $otpCode }}. Valid for 10 minutes.
    </div>

    <!-- Outer Table Wrapper -->
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="background-color: #f1f5f9; min-height: 100vh;">
        <tr>
            <td align="center" style="padding: 40px 16px;">
                <table role="presentation" class="email-container" width="580" cellspacing="0" cellpadding="0" border="0" style="max-width: 580px; width: 100%; margin: 0 auto;">
                    
                    <!-- Brand Header -->
                    <tr>
                        <td align="center" style="padding-bottom: 24px;">
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                                            <tr>
                                                <td style="vertical-align: middle; padding-right: 12px;">
                                                    <img src="{{ asset('logo/logo.png') }}" alt="MLM Book" width="48" height="48" class="header-logo" style="display: block; width: 48px; height: 48px; border-radius: 12px; object-fit: contain;">
                                                </td>
                                                <td style="vertical-align: middle; text-align: left;">
                                                    <span class="brand-name" style="font-size: 20px; font-weight: 800; letter-spacing: -0.03em; color: #0f172a; text-decoration: none; display: block; line-height: 1.2;">MLM Book</span>
                                                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #2563eb; display: block;">Official Member Registration</span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Main White Content Card -->
                    <tr>
                        <td class="content-card" style="background-color: #ffffff; border-radius: 20px; padding: 40px 36px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.05), 0 8px 10px -6px rgba(15, 23, 42, 0.03);">
                            
                            <!-- Welcome Badge Pill -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 20px;">
                                <tr>
                                    <td style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 9999px; padding: 6px 14px;">
                                        <span style="font-size: 12px; font-weight: 700; color: #15803d; text-transform: uppercase; letter-spacing: 0.05em; display: inline-block;">
                                            ✨ Welcome to MLM Book &bull; Account Activation
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Title -->
                            <h1 style="margin: 0 0 16px 0; font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1.3; letter-spacing: -0.02em;">
                                Verify your email address
                            </h1>

                            <!-- Greeting & Body Text -->
                            <p style="margin: 0 0 14px 0; font-size: 15px; line-height: 1.6; color: #334155;">
                                Hello <strong style="color: #0f172a;">{{ $memberName }}</strong>,
                            </p>
                            <p style="margin: 0 0 20px 0; font-size: 15px; line-height: 1.6; color: #475569;">
                                Welcome to MLM Book! Thank you for signing up. To complete your account registration for <strong style="color: #0f172a;">{{ $memberEmail }}</strong>, please enter the following 6-digit activation code:
                            </p>

                            <!-- Modern OTP Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin: 28px 0;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width: 100%;">
                                            <tr>
                                                <td align="center" style="background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); border: 2px dashed #93c5fd; border-radius: 16px; padding: 22px 24px;">
                                                    <span style="display: block; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.1em; color: #64748b; margin-bottom: 6px;">
                                                        Your Activation Code
                                                    </span>
                                                    <span class="otp-code" style="display: inline-block; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace; font-size: 38px; font-weight: 800; letter-spacing: 10px; color: #2563eb; text-shadow: 0 1px 2px rgba(37, 99, 235, 0.15);">
                                                        {{ $otpCode }}
                                                    </span>
                                                    <span style="display: block; font-size: 12px; font-weight: 500; color: #64748b; margin-top: 8px;">
                                                        ⏱️ Expires in <strong style="color: #0f172a;">10 minutes</strong>
                                                    </span>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Important Security Notice Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top: 24px;">
                                <tr>
                                    <td style="background-color: #f8fafc; border-left: 4px solid #10b981; border-radius: 8px 12px 12px 8px; padding: 14px 18px;">
                                        <p style="margin: 0; font-size: 13px; line-height: 1.6; color: #475569;">
                                            <strong style="color: #0f172a;">Important:</strong> Keep your credentials secure. Never share this code with anyone. MLM Book administrators will never ask for your verification code.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Safety disclaimer -->
                            <p style="margin: 28px 0 0 0; font-size: 13px; line-height: 1.6; color: #94a3b8;">
                                If you did not create an account with MLM Book using this email address, you can safely ignore this message.
                            </p>

                        </td>
                    </tr>

                    <!-- Official Footer -->
                    <tr>
                        <td align="center" style="padding-top: 32px;">
                            <p style="margin: 0 0 8px 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                                This is an automated notification sent by <strong style="color: #475569;">MLM Book</strong>.
                            </p>
                            <p style="margin: 0 0 12px 0; font-size: 12px; color: #94a3b8;">
                                Please do not reply directly to this email as the mailbox is unmonitored.
                            </p>
                            <p style="margin: 0; font-size: 12px; color: #94a3b8;">
                                &copy; {{ date('Y') }} MLM Book Platform. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
