<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Webxpress</title>
</head>
<body style="margin:0;padding:0;background:#F5F5F5;font-family:Arial,Helvetica,sans-serif;color:#2E2E2E;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#F5F5F5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;background:#FFFFFF;border-radius:12px;overflow:hidden;border:1px solid #E5E5E5;">
                    <tr>
                        <td style="background:#1B2D5F;padding:28px 36px;">
                            <span style="color:#FFFFFF;font-size:22px;font-weight:bold;letter-spacing:0.5px;">WEBXPRESS</span>
                            <span style="color:#E31937;font-size:22px;font-weight:bold;">.</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px;">
                            @if($inquiry->name)
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#2E2E2E;">Hi {{ $inquiry->name }},</p>
                            @endif
                            <div style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#2E2E2E;white-space:pre-line;">{{ $replyBody }}</div>
                            <p style="margin:0 0 4px;font-size:15px;line-height:1.7;color:#2E2E2E;">Best,</p>
                            <p style="margin:0;font-size:15px;line-height:1.7;color:#1B2D5F;font-weight:bold;">The Webxpress Team</p>
                            <p style="margin:0 0 24px;font-size:13px;color:#9A9A9A;">Lagos, Nigeria</p>

                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="border-radius:8px;background:#E31937;padding:14px 28px;">
                                        <a href="mailto:{{ config('mail.inquiries_to', 'hello@webxpresstech.com.ng') }}" style="color:#FFFFFF;font-size:14px;font-weight:bold;text-decoration:none;">Reply to this email</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 36px;border-top:1px solid #E5E5E5;background:#FFFFFF;">
                            <p style="margin:0;font-size:12px;color:#9A9A9A;">
                                Webxpress Technologies — <a href="{{ url('/') }}" style="color:#1B2D5F;text-decoration:none;">webxpresstech.com.ng</a><br>
                                Lagos, Nigeria
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>