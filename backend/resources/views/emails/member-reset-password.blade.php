<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta http-equiv="x-ua-compatible" content="ie=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <title>Reset Your Password | MLM Book</title>
    <style>
        @media only screen and (max-width: 600px) {
            .email-container { width: 100% !important; padding: 24px 16px !important; }
            .content-card { padding: 28px 20px !important; border-radius: 16px !important; }
            .cta-button { display: block !important; width: 100% !important; box-sizing: border-box !important; }
            .header-logo { width: 44px !important; height: 44px !important; }
            .brand-name { font-size: 18px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; width: 100% !important; -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; background-color: #f1f5f9; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #0f172a;">
    <!-- Preheader preview text -->
    <div style="display: none; font-size: 1px; color: #f1f5f9; line-height: 1px; max-height: 0px; max-width: 0px; opacity: 0; overflow: hidden; mso-hide: all;">
        Reset your MLM Book password. This secure link is valid for 60 minutes.
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
                                                    <span style="font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.08em; color: #2563eb; display: block;">Official Security Transmission</span>
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
                            
                            <!-- Security Badge Pill -->
                            <table role="presentation" cellspacing="0" cellpadding="0" border="0" style="margin-bottom: 20px;">
                                <tr>
                                    <td style="background-color: #fff7ed; border: 1px solid #fed7aa; border-radius: 9999px; padding: 6px 14px;">
                                        <span style="font-size: 12px; font-weight: 700; color: #c2410c; text-transform: uppercase; letter-spacing: 0.05em; display: inline-block;">
                                            🔑 Account Security &bull; Password Recovery
                                        </span>
                                    </td>
                                </tr>
                            </table>

                            <!-- Title -->
                            <h1 style="margin: 0 0 16px 0; font-size: 24px; font-weight: 800; color: #0f172a; line-height: 1.3; letter-spacing: -0.02em;">
                                Reset your password
                            </h1>

                            <!-- Greeting & Body Text -->
                            <p style="margin: 0 0 14px 0; font-size: 15px; line-height: 1.6; color: #334155;">
                                Hello <strong style="color: #0f172a;">{{ $memberName }}</strong>,
                            </p>
                            <p style="margin: 0 0 28px 0; font-size: 15px; line-height: 1.6; color: #475569;">
                                We received a request to reset your MLM Book account password. Click the secure button below to set a new password:
                            </p>

                            <!-- Primary CTA Button -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin: 28px 0;">
                                <tr>
                                    <td align="center">
                                        <!--[if mso]>
                                        <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" xmlns:w="urn:schemas-microsoft-com:office:word" href="{{ $resetUrl }}" style="height:48px;v-text-anchor:middle;width:240px;" arcsize="25%" strokecolor="#1d4ed8" fillcolor="#2563eb">
                                        <w:anchorlock/>
                                        <center style="color:#ffffff;font-family:sans-serif;font-size:15px;font-weight:bold;">Reset Password</center>
                                        </v:roundrect>
                                        <![endif]-->
                                        <!--[if !mso]><!-- -->
                                        <a href="{{ $resetUrl }}" target="_blank" class="cta-button" style="display: inline-block; background-color: #2563eb; background-image: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%); color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 700; padding: 15px 36px; border-radius: 12px; box-shadow: 0 6px 18px rgba(37, 99, 235, 0.35); letter-spacing: 0.02em; text-align: center;">
                                            Reset Password
                                        </a>
                                        <!--<![endif]-->
                                    </td>
                                </tr>
                            </table>

                            <!-- Expiry & Security Notice Box -->
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" border="0" style="margin-top: 24px;">
                                <tr>
                                    <td style="background-color: #f8fafc; border-left: 4px solid #f59e0b; border-radius: 8px 12px 12px 8px; padding: 14px 18px;">
                                        <p style="margin: 0 0 6px 0; font-size: 13px; line-height: 1.5; color: #475569;">
                                            ⏱️ <strong>Expiration Notice:</strong> This password reset link is valid for <strong style="color: #0f172a;">60 minutes</strong>.
                                        </p>
                                        <p style="margin: 0; font-size: 13px; line-height: 1.5; color: #475569;">
                                            🛡️ <strong>Security Tip:</strong> If you did not request this change, you can safely ignore this email. Your current password remains active and secure.
                                        </p>
                                    </td>
                                </tr>
                            </table>

                            <!-- Fallback Link Section -->
                            <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid #edf2f7;">
                                <p style="margin: 0 0 8px 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                                    If the button above does not work, copy and paste this link into your web browser:
                                </p>
                                <div style="background-color: #f1f5f9; border-radius: 8px; padding: 10px 14px; word-break: break-all; font-family: 'SFMono-Regular', Consolas, 'Liberation Mono', Menlo, Courier, monospace; font-size: 12px; line-height: 1.5; color: #2563eb; border: 1px solid #e2e8f0;">
                                    <a href="{{ $resetUrl }}" style="color: #2563eb; text-decoration: underline;">{{ $resetUrl }}</a>
                                </div>
                            </div>

                        </td>
                    </tr>

                    <!-- Official Footer -->
                    <tr>
                        <td align="center" style="padding-top: 32px;">
                            <p style="margin: 0 0 8px 0; font-size: 12px; color: #64748b; line-height: 1.5;">
                                This is an automated security transmission sent by <strong style="color: #475569;">MLM Book</strong>.
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
