<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Password Reset Code - SmartPOS</title>
</head>
<body style="margin: 0; padding: 0; background-color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;">
    
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0f172a; padding: 40px 10px;">
        <tr>
            <td align="center">
                <!-- Email Container -->
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 520px; background-color: #1e293b; border-radius: 16px; border: 1px solid #334155; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5), 0 8px 10px -6px rgba(0, 0, 0, 0.5); overflow: hidden;">
                    
                    <!-- Header Bar with Gradient Accent -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #d946ef 100%); height: 6px; font-size: 0; line-height: 0;">&nbsp;</td>
                    </tr>

                    <!-- Main Content Padding -->
                    <tr>
                        <td style="padding: 40px 36px 36px 36px;">
                            
                            <!-- Brand Header -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="left">
                                        <div style="display: inline-flex; align-items: center;">
                                            <span style="font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px;">Smart<span style="color: #8b5cf6;">POS</span></span>
                                        </div>
                                    </td>
                                    <td align="right">
                                        <span style="background-color: rgba(99, 102, 241, 0.15); color: #a5b4fc; border: 1px solid rgba(99, 102, 241, 0.3); font-size: 11px; font-weight: 700; text-transform: uppercase; padding: 4px 10px; border-radius: 20px; letter-spacing: 0.5px;">Security Alert</span>
                                    </td>
                                </tr>
                            </table>

                            <div style="height: 32px; line-height: 32px;">&nbsp;</div>

                            <!-- Title Section -->
                            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: #f8fafc; line-height: 1.3;">
                                Password Reset Request
                            </h1>

                            <p style="margin: 12px 0 0 0; font-size: 14px; line-height: 1.6; color: #94a3b8;">
                                We received a request to reset your password for your <strong style="color: #cbd5e1;">SmartPOS</strong> account. Use the 6-digit verification code below to complete the reset process:
                            </p>

                            <div style="height: 28px; line-height: 28px;">&nbsp;</div>

                            <!-- OTP Display Card -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #0f172a; border-radius: 12px; border: 1px solid #334155; text-align: center;">
                                <tr>
                                    <td style="padding: 24px 16px;">
                                        <span style="display: block; font-size: 11px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 1.5px; margin-bottom: 12px;">Your 6-Digit Code</span>
                                        
                                        <!-- Code Box -->
                                        <div style="font-family: 'Courier New', Courier, monospace, sans-serif; font-size: 38px; font-weight: 800; color: #38bdf8; letter-spacing: 12px; padding-left: 12px; text-shadow: 0 0 12px rgba(56, 189, 248, 0.3);">
                                            {{ $code }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div style="height: 20px; line-height: 20px;">&nbsp;</div>

                            <!-- Expiration Badge -->
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center">
                                        <div style="background-color: #1e1b4b; border: 1px solid #3730a3; border-radius: 8px; padding: 10px 16px; display: inline-block;">
                                            <span style="font-size: 13px; color: #c7d2fe; font-weight: 500;">
                                                ⏱️ This code will expire in <strong style="color: #ffffff;">10 minutes</strong>
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <div style="height: 32px; line-height: 32px;">&nbsp;</div>

                            <!-- Divider -->
                            <div style="height: 1px; background-color: #334155; width: 100%;">&nbsp;</div>

                            <div style="height: 24px; line-height: 24px;">&nbsp;</div>

                            <!-- Security Warning -->
                            <p style="margin: 0; font-size: 12px; line-height: 1.6; color: #64748b;">
                                🛡️ <strong>Didn't request this?</strong> If you didn't ask to reset your password, you can safely ignore this email. Someone may have entered your email address by mistake. Your account remains secure.
                            </p>

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #0f172a; padding: 20px 36px; border-top: 1px solid #334155; text-align: center;">
                            <p style="margin: 0; font-size: 12px; color: #475569; line-height: 1.5;">
                                &copy; {{ date('Y') }} SmartPOS Identity Service. All rights reserved.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>

</body>
</html>