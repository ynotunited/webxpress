<?php

/**
 * Paystack webhook policy.
 *
 * The gateway keys themselves stay in config/services.php where Laravel puts
 * them; this file is the *policy* around the webhook endpoint — which sources
 * are trusted, how long an event stays replayable, and what to do with a
 * request that fails a check. None of it belongs in the controller, because the
 * controller's job is to decide what a verified event means, not whether it is
 * allowed to ask.
 *
 * Every switch here exists because the failure it prevents is silent. A
 * misconfigured allowlist does not throw; it just stops money being recorded,
 * and the first sign of it is a client who says they paid.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Source allowlist
    |--------------------------------------------------------------------------
    |
    | Paystack documents exactly three addresses it delivers webhooks from, and
    | states they are the same in test and live mode:
    |
    |   https://paystack.com/docs/payments/webhooks/
    |
    | Signature validation is the primary defence and is always applied. This is
    | the second, independent layer: it rejects a forged request before it costs
    | a hash comparison, and it makes the endpoint useless to a scanner.
    |
    | There is no range or CIDR support, deliberately. A prefix match would let
    | a neighbouring host in the same allocation post to the endpoint, which is
    | the exact thing an allowlist is meant to prevent. Exact strings only.
    |
    */
    'webhook' => [

        'allowed_ips' => [
            '52.31.139.75',
            '52.49.173.169',
            '52.214.14.220',
        ],

        /*
        | Reject a request from an address that is not on the list above.
        |
        | Left on, an address Paystack adds in future is rejected until this
        | file is updated — one line, no deploy of application logic needed, and
        | the rejection is logged loudly with the address that was seen. Turn it
        | off only while diagnosing that, and the signature check still applies
        | either way, so turning it off does not make the endpoint open.
        */
        'enforce_ip_allowlist' => (bool) env('PAYSTACK_WEBHOOK_ENFORCE_IPS', true),

        /*
        | How long to keep a delivered event's raw body.
        |
        | There is deliberately no "reject events older than N" switch here, and
        | its absence is the point. The Paystack signature covers the request
        | body only, with no timestamp inside it, so a captured request stays
        | verifiable forever and the server has nothing trustworthy to measure
        | its age against. A check written against that would either always pass
        | or reject on a value the attacker controls.
        |
        | Replay is therefore prevented structurally instead: every accepted
        | event is written to webhook_events with a unique key, and that table is
        | append-only, so a redelivery collides on insert forever rather than
        | expiring. This retention only governs how much is kept for dispute
        | evidence.
        |
        | Note also that Paystack retries a failed event for up to 72 hours, so
        | any window here must comfortably exceed that or genuine retries get
        | discarded.
        */
        'retain_days' => (int) env('PAYSTACK_WEBHOOK_RETAIN_DAYS', 400),

        /*
        | Requests per minute per source address.
        |
        | Paystack retries every 3 minutes for four attempts and then hourly, so
        | the genuine rate for one address is well under one a minute. This is
        | here to make scripted enumeration expensive, not to rate-limit
        | Paystack.
        */
        'throttle_per_minute' => (int) env('PAYSTACK_WEBHOOK_THROTTLE', 30),

        /*
        | The only events that move money or state. Anything else is recorded
        | and acknowledged without action, so an unhandled event type is visible
        | in the ledger instead of being silently discarded.
        |
        | Paystack also emits charge.dispute.*, refund.* and transfer.*. Those
        | describe money already handled and are deliberately not wired to deal
        | state — a dispute is a human decision, not something to auto-refund
        | from a webhook.
        */
        'handled_events' => [
            'charge.success',
        ],

        /*
        | Currency the site quotes and settles in. An event in any other
        | currency is refused rather than converted: accepting it would mark a
        | naira invoice paid with a number that means something else.
        */
        'currency' => 'NGN',

        /*
        | Where payment alerts go. Separate from MAIL_INQUIRIES_TO so that sales
        | enquiries and money movement can be routed to different people.
        */
        'alert_email' => env('PAYSTACK_ALERT_EMAIL', env('ADMIN_EMAIL')),

        /*
        | Alerts the business team about verified payments.
        |
        | Off by default because a developer working locally with test keys
        | would otherwise generate mail on every run. In production this wants
        | to be on: the ledger records what happened, this is what tells someone
        | to read it.
        */
        'notify_on_payment' => (bool) env('PAYSTACK_NOTIFY_ON_PAYMENT', true),
    ],
];