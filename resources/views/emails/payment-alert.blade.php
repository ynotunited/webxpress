<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $payment ? 'Payment received' : 'Payment needs review' }}</title>
</head>
<body style="margin:0;padding:0;background:#F5F5F5;font-family:Arial,Helvetica,sans-serif;color:#2E2E2E;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#F5F5F5;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0" style="max-width:600px;width:100%;background:#FFFFFF;border-radius:12px;overflow:hidden;border:1px solid #E5E5E5;">
                    <tr>
                        <td style="background:{{ $payment ? '#1B2D5F' : '#7A2E12' }};padding:28px 36px;">
                            <span style="color:#FFFFFF;font-size:22px;font-weight:bold;letter-spacing:0.5px;">WEBXPRESS</span>
                            <span style="color:#E31937;font-size:22px;font-weight:bold;">.</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:36px;">
                            @if ($payment)
                                <p style="margin:0 0 8px;font-size:13px;color:#9A9A9A;text-transform:uppercase;letter-spacing:1px;">Money in</p>
                                <h1 style="margin:0 0 24px;font-size:24px;color:#1B2D5F;font-weight:bold;">
                                    ₦{{ number_format((float) $payment->amount, 2) }} received
                                </h1>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
                                    <tr>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Client</td>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $deal?->name }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Project</td>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $deal?->project_title ?: '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Phase</td>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">
                                            {{ $payment->kind === 'upfront' ? 'Upfront (' . $deal?->upfront_percent . '%)' : 'Balance on delivery (' . $deal?->delivery_percent . '%)' }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Deal status</td>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $deal?->status }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 0;width:40%;font-size:13px;color:#9A9A9A;">Reference</td>
                                        <td style="padding:12px 0;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $payment->paystack_reference }}</td>
                                    </tr>
                                </table>
                            @else
                                <p style="margin:0 0 8px;font-size:13px;color:#9A9A9A;text-transform:uppercase;letter-spacing:1px;">Needs a person</p>
                                <h1 style="margin:0 0 16px;font-size:22px;color:#7A2E12;font-weight:bold;">
                                    A charge arrived and was not applied
                                </h1>

                                {{-- Stated plainly, because the reason decides the
                                     action: a mismatch is a refund conversation, an
                                     unknown reference is a reconciliation task. --}}
                                <p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#2E2E2E;">
                                    {{ $reason }}
                                </p>

                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-bottom:28px;">
                                    <tr>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Reference</td>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $webhookEvent?->reference ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Event</td>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $webhookEvent?->event }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;width:40%;font-size:13px;color:#9A9A9A;">Received from</td>
                                        <td style="padding:12px 0;border-bottom:1px solid #E5E5E5;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $webhookEvent?->source_ip ?? '—' }}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding:12px 0;width:40%;font-size:13px;color:#9A9A9A;">Recorded</td>
                                        <td style="padding:12px 0;font-size:14px;color:#1B2D5F;font-weight:600;">{{ $webhookEvent?->created_at?->format('F j, Y g:i A') }}</td>
                                    </tr>
                                </table>
                            @endif

                            <table role="presentation" cellspacing="0" cellpadding="0" style="margin-bottom:12px;">
                                <tr>
                                    <td style="border-radius:8px;background:#1B2D5F;padding:14px 28px;">
                                        <a href="{{ admin_url('deals') }}" style="color:#FFFFFF;font-size:14px;font-weight:bold;text-decoration:none;">Open the deals pipeline</a>
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