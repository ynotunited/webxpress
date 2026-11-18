<?php

namespace App\Providers;

use App\Listeners\RecordAdminAuthentication;
use App\Models\Category;
use App\Models\ClientReview;
use App\Models\Deal;
use App\Models\InquiryMessage;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Payment;
use App\Models\Post;
use App\Models\ProjectInquiry;
use App\Models\SiteSetting;
use App\Observers\AuditObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * The models whose changes are written to the audit trail.
     *
     * Split by who can change the record, because that decides whether the trail
     * is reconstructing CMS editing or answering "who moved this client's money".
     *
     * The content models are editable only by an admin, so any row for them is by
     * definition an admin action. Deals, payments, inquiries and site settings
     * used to be excluded on the grounds that volume was a separate decision to be
     * made later — and that left the records that matter most to a dispute with no
     * trail at all: a payment status flipped, a deal amount edited, an admin
     * replying to a client, or the site logo and name changed. The volume
     * objection does not hold up in practice, because this panel has one
     * administrator and a handful of clients: an admin touches a handful of deals
     * a month, where they may touch pages weekly.
     *
     * Those models are also written by automated paths — the Paystack webhook
     * marks a payment paid, the contact form creates the inquiry, IMAP creates
     * inbound messages. That is fine and worth having: an audit row with no user
     * is the honest record of a system-driven change, and an audit row *with* a
     * user next to it is the interesting one.
     *
     * User is absent on purpose. Every admin row is written by a user, so
     * auditing the user table would record the act of auditing.
     */
    private const AUDITED = [
        // Content the admin edits by hand.
        Page::class,
        PageSection::class,
        Post::class,
        Category::class,
        ClientReview::class,

        // Site-wide configuration, including the branding and contact details
        // that reach every page and every email.
        SiteSetting::class,

        // Money and commitments.
        Deal::class,
        Payment::class,

        // Client conversations — an admin reply here sends mail to a client.
        ProjectInquiry::class,
        InquiryMessage::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach (self::AUDITED as $model) {
            $model::observe(AuditObserver::class);
        }

        // Registered by hand because the listener exposes handleLogin/handleFailed
        // rather than a single type-hinted handle(), which is what Laravel's
        // listener auto-discovery looks for.
        Event::listen(Login::class, [RecordAdminAuthentication::class, 'handleLogin']);
        Event::listen(Failed::class, [RecordAdminAuthentication::class, 'handleFailed']);
    }
}