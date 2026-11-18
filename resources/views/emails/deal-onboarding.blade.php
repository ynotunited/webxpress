<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Welcome aboard</title>
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
                            @if($deal->name)
                            <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#2E2E2E;">Hi {{ $deal->name }},</p>
                            @endif
                            <h1 style="margin:0 0 16px;font-size:22px;color:#1B2D5F;font-weight:bold;">Payment received — welcome aboard.</h1>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#2E2E2E;">
                                Your upfront payment has been confirmed and <strong style="color:#1B2D5F;">{{ $deal->project_title ?: 'your project' }}</strong>
                                is officially underway. Here's what happens next.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Start date</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $deal->start_date ? $deal->start_date->format('F j, Y') : 'To be confirmed on kickoff' }}</td>
                                </tr>
                                @if($deal->duration_label)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Duration</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $deal->duration_label }}</td>
                                </tr>
                                @endif
                                @if($deal->milestones)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Milestones</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;white-space:pre-line;">{{ $deal->milestones }}</td>
                                </tr>
                                @endif
                            </table>

                            <h2 style="margin:0 0 12px;font-size:16px;color:#1B2D5F;font-weight:bold;">Next step — schedule your kickoff</h2>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#2E2E2E;">
                                Reply to this email and pick a time that works for you (or suggest one), and we'll lock in a
                                kickoff call. We'll walk through the plan, collect any remaining details, and get building.
                            </p>

                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td style="border-radius:8px;background:#E31937;padding:14px 28px;">
                                        <a href="mailto:{{ config('mail.inquiries_to', 'hello@webxpresstech.com.ng') }}?subject=Kickoff%20scheduling%20for%20{{ $deal->project_title ? urlencode($deal->project_title) : 'my%20project' }}" style="color:#FFFFFF;font-size:14px;font-weight:bold;text-decoration:none;">Schedule my kickoff</a>
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