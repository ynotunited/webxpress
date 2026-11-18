<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your project brief</title>
</head>
<body style="margin:0;padding:0;background:#F5F5F5;font-family:Arial,Helvetica,sans-serif;color:#2E2E2E;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#F5F5F5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;background:#FFFFFF;border-radius:12px;overflow:hidden;border:1px solid #E5E5E5;">
                    {{-- Header --}}
                    <tr>
                        <td style="background:#1B2D5F;padding:28px 36px;">
                            <span style="color:#FFFFFF;font-size:22px;font-weight:bold;letter-spacing:0.5px;">WEBXPRESS</span>
                            <span style="color:#E31937;font-size:22px;font-weight:bold;">.</span>
                        </td>
                    </tr>
                    {{-- Body --}}
                    <tr>
                        <td style="padding:36px;">
                            <h1 style="margin:0 0 16px;font-size:22px;color:#1B2D5F;font-weight:bold;">Thanks for your brief.</h1>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#2E2E2E;">
                                We've received your project details and will be in touch within <strong style="color:#1B2D5F;">24 hours</strong> to schedule an intro call.
                            </p>

                            {{-- Recap table --}}
                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
                                @if($inquiry->project_type)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Project type</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $inquiry->project_type }}</td>
                                </tr>
                                @endif
                                @if($inquiry->budget)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Budget</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $inquiry->budget }}</td>
                                </tr>
                                @endif
                                @if($inquiry->timeline)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Timeline</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $inquiry->timeline }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Your name</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $inquiry->name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Email</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $inquiry->email }}</td>
                                </tr>
                            </table>

                            {{-- Brief excerpt --}}
                            @if($inquiry->message)
                            <p style="margin:0 0 8px;font-size:13px;color:#9A9A9A;text-transform:uppercase;letter-spacing:1px;">What you told us</p>
                            <p style="margin:0 0 28px;font-size:15px;line-height:1.7;color:#2E2E2E;white-space:pre-line;">{{ Str::limit($inquiry->message, 600) }}</p>
                            @endif

                            {{-- CTA --}}
                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin-bottom:12px;">
                                <tr>
                                    <td style="border-radius:8px;background:#E31937;padding:14px 28px;">
                                        <a href="mailto:{{ config('mail.inquiries_to', 'hello@webxpresstech.com.ng') }}" style="color:#FFFFFF;font-size:14px;font-weight:bold;text-decoration:none;">Reply to this email</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    {{-- Footer --}}
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