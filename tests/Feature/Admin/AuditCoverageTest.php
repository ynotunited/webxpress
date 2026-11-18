<?php

namespace Tests\Feature\Admin;

use App\Models\AuditLog;
use App\Models\Deal;
use App\Models\InquiryMessage;
use App\Models\Payment;
use App\Models\ProjectInquiry;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * The audit trail across the records an admin can change beyond the CMS.
 *
 * The observer itself is covered in AuditTrailTest; what is at stake here is *which
 * models are wired to it*. Content, deals, payments, inquiries and site settings
 * were once the same set, and then they were split by a decision that deferred the
 * money and client surfaces to "phase 2" — which quietly left the rows anyone would
 * actually need to reconstruct with no trail at all: who marked a payment paid, who
 * changed a deal's amount, who replied to a client, who changed the site's own name.
 *
 * These are the assertions for putting that back. They are deliberately about
 * identity and timestamp, because those are the two things a trail is worthless
 * without — "something changed" without knowing who or when answers no question
 * anyone will ever ask.
 */
class AuditCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin(): User
    {
        $admin = User::factory()->create(['email' => 'boss@webxpresstech.com.ng']);

        Auth::login($admin);

        return $admin;
    }

    /**
     * The audit row for a change, not for the row's existence.
     *
     * An audited model gets one entry when it is created and another every time it
     * changes, so the first row for a model made by a factory is always the
     * creation — with `changes` null, because there is nothing to diff against. A
     * test that reads the first row while asserting on a diff passes its existence
     * check and then dies on the assertion, which looks like the trail is broken
     * when it is the query that is wrong.
     */
    private function changeLogFor(object $model): AuditLog
    {
        $log = AuditLog::forSubject($model)
            ->where('event', 'updated')
            ->latest('created_at')
            ->first();

        $this->assertNotNull($log, sprintf(
            'no updated audit row for %s — it is missing from AppServiceProvider::AUDITED',
            class_basename($model)
        ));

        return $log;
    }

    public function test_editing_a_deal_records_who_made_the_change(): void
    {
        $admin = $this->actingAsAdmin();
        $deal = Deal::factory()->create();

        $deal->update(['amount' => 2_500_000]);

        $log = $this->changeLogFor($deal);

        $this->assertSame($admin->id, $log->user_id);
        $this->assertSame($admin->email, $log->user_email);
        $this->assertNotNull($log->created_at, 'audit row has no timestamp');
        // Compared numerically: the decimal cast hands back the pre-save value as
        // '1000000.00' and the post-save one as an int, so a string comparison
        // would pin this test to a formatting detail rather than to the fact that
        // the amount moved and is recorded as having moved.
        $this->assertEquals(1_000_000, (float) $log->changes['amount']['old']);
        $this->assertEquals(2_500_000, (float) $log->changes['amount']['new']);
    }

    public function test_a_payment_status_change_is_audited_with_the_actor(): void
    {
        $admin = $this->actingAsAdmin();
        $payment = Payment::factory()->create();

        $payment->update(['status' => Payment::STATUS_PAID]);

        $log = $this->changeLogFor($payment);

        $this->assertSame($admin->email, $log->user_email);
        $this->assertSame(Payment::STATUS_PAID, $log->changes['status']['new']);
    }

    public function test_a_system_driven_payment_change_records_no_actor_rather_than_a_wrong_one(): void
    {
        // The Paystack webhook marks payments paid with no one signed in. An audit
        // row with no user is the honest record of that, and it is what makes the
        // rows *with* a user the interesting ones — a forged identity would be
        // worse than none, because it would point the investigation at a person.
        Auth::logout();

        $payment = Payment::factory()->create();
        $payment->update(['status' => Payment::STATUS_PAID]);

        $log = $this->changeLogFor($payment);

        $this->assertNull($log->user_id);
        $this->assertNull($log->user_email);
        $this->assertNotNull($log->created_at);
        $this->assertSame(Payment::STATUS_PAID, $log->changes['status']['new']);
    }

    public function test_an_admin_reply_to_a_client_is_audited(): void
    {
        $admin = $this->actingAsAdmin();

        $inquiry = ProjectInquiry::create([
            'name' => 'Ada Okafor',
            'email' => 'ada@example.com',
            'message' => 'We need a storefront.',
            'status' => ProjectInquiry::STATUS_NEW,
            'thread_token' => 'abcd1234',
        ]);

        InquiryMessage::create([
            'project_inquiry_id' => $inquiry->id,
            'direction' => InquiryMessage::DIR_OUTBOUND,
            'type' => 'reply',
            'subject' => 'Re: your project',
            'body' => 'Thanks — we would love to quote this.',
            'message_id' => 'wx-abcd1234-abc@webxpresstech.com.ng',
            'received_at' => now(),
        ]);

        $inquiry->markContacted();

        $messageLog = AuditLog::forSubject(
            InquiryMessage::where('project_inquiry_id', $inquiry->id)->firstOrFail()
        )->first();

        $this->assertNotNull($messageLog, 'an admin reply to a client wrote no audit entry');
        $this->assertSame($admin->email, $messageLog->user_email);

        $inquiryLog = $this->changeLogFor($inquiry);

        $this->assertSame($admin->email, $inquiryLog->user_email);
        $this->assertSame(
            ProjectInquiry::STATUS_CONTACTED,
            $inquiryLog->changes['status']['new']
        );
    }

    public function test_changing_a_site_setting_is_audited(): void
    {
        $admin = $this->actingAsAdmin();

        SiteSetting::setValue('site_name', 'Webxpress Technologies Limited');

        $log = AuditLog::forSubject(
            SiteSetting::where('key', 'site_name')->firstOrFail()
        )->first();

        $this->assertNotNull($log, 'changing a site-wide setting wrote no audit entry');
        $this->assertSame($admin->email, $log->user_email);
        $this->assertNotNull($log->created_at);
    }

    /**
     * @return array<string, array{0: string, 1: callable(): \Illuminate\Database\Eloquent\Model}>
     */
    public static function auditedModels(): array
    {
        return [
            'deal' => ['Deal', fn (): Deal => Deal::factory()->create()],
            'payment' => ['Payment', fn (): Payment => Payment::factory()->create()],
            'site setting' => ['SiteSetting', fn (): SiteSetting => SiteSetting::create(['key' => 'audit_probe_'.uniqid(), 'value' => 'x'])],
            'inquiry' => ['ProjectInquiry', fn (): ProjectInquiry => ProjectInquiry::create([
                'name' => 'Probe',
                'email' => 'probe@example.com',
                'message' => 'hello',
                'status' => ProjectInquiry::STATUS_NEW,
                'thread_token' => 'probe123',
            ])],
        ];
    }

    /**
     * The property the table exists to provide: timestamp plus identification.
     *
     * Asserted once per newly-covered model rather than per field on one model,
     * because the failure this catches is a model being dropped from the AUDITED
     * list — a one-line change nobody would notice until the moment they needed
     * the record.
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('auditedModels')]
    public function test_every_audited_model_records_a_timestamp_and_an_identity(string $label, callable $factory): void
    {
        $admin = $this->actingAsAdmin();

        $model = $factory();

        $log = AuditLog::forSubject($model)->first();

        $this->assertNotNull($log, "the {$label} is no longer audited — it is missing from AppServiceProvider::AUDITED");
        $this->assertNotNull($log->created_at, "the {$label} audit row carries no timestamp");
        $this->assertSame($admin->id, $log->user_id, "the {$label} audit row does not identify the actor");
        $this->assertSame($admin->email, $log->user_email, "the {$label} audit row does not identify the actor by address");
        $this->assertNotNull($log->event);
        $this->assertNotNull($log->subject_type);
        $this->assertNotNull($log->subject_id);
    }
}