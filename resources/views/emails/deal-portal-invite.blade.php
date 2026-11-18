<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Your project documents</title>
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
                            <h1 style="margin:0 0 16px;font-size:22px;color:#1B2D5F;font-weight:bold;">
                                {{ $deal->project_title ?: 'Your project' }} — documents are ready.
                            </h1>
                            <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#2E2E2E;">
                                We're excited to get started. Your project proposal, timeline and terms are ready to review online —
                                and the upfront payment link is right there too, so everything happens in one place.
                            </p>

                            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Project</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $deal->project_title ?: '—' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Investment</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">₦{{ number_format($deal->amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Expected start</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $deal->start_date ? $deal->start_date->format('F j, Y') : 'To be confirmed' }}</td>
                                </tr>
                                @if($deal->duration_label)
                                <tr>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Duration</td>
                                    <td style="padding:10px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $deal->duration_label }}</td>
                                </tr>
                                @endif
                            </table>

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin-bottom:12px;">
                                <tr>
                                    <td style="border-radius:8px;background:#E31937;padding:14px 28px;">
                                        <a href="{{ $deal->portalUrl() }}" style="color:#FFFFFF;font-size:14px;font-weight:bold;text-decoration:none;">Open your project portal</a>
                                    </td>
                                </tr>
                            </table>
                            <p style="margin:0;font-size:13px;color:#9A9A9A;line-height:1.6;">
                                Prefer to review later? Use this private link: {{ $deal->portalUrl() }}
                            </p>
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