<?php

namespace Tests\Feature\Inquiry;

use App\Models\InquiryMessage;
use App\Models\ProjectInquiry;
use App\Services\InquiryThreadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Which thread an inbound email reply belongs to.
 *
 * The failure this guards against is not a dropped message, it is a message
 * attached to the wrong client. A mis-filed reply is emailed to the team as a
 * client response and then answered, so one client's project detail can be
 * read back to another — a confidentiality problem, not a cosmetic one. So
 * these tests care as much about what the resolver *refuses* to do as what it
 * matches.
 */
class InquiryThreadResolutionTest extends TestCase
{
    use RefreshDatabase;

    private function thread(): InquiryThreadService
    {
        return app(InquiryThreadService::class);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function inquiry(string $email, string $token, array $overrides = []): ProjectInquiry
    {
        return ProjectInquiry::create(array_merge([
            'name' => 'Test Client',
            'email' => $email,
            'message' => 'We need a website.',
            'status' => ProjectInquiry::STATUS_NEW,
            'thread_token' => $token,
        ], $overrides));
    }

    private function outbound(ProjectInquiry $inquiry, string $messageId): InquiryMessage
    {
        return InquiryMessage::create([
            'project_inquiry_id' => $inquiry->id,
            'direction' => InquiryMessage::DIR_OUTBOUND,
            'type' => 'reply',
            'subject' => 'Re: your project',
            'body' => 'Here is our reply.',
            'message_id' => $messageId,
            'received_at' => now(),
        ]);
    }

    public function test_it_resolves_by_the_message_id_being_replied_to(): void
    {
        $inquiry = $this->inquiry('client@example.com', 'abcd1234');
        $this->outbound($inquiry, 'our-message-1@example.com');

        $resolved = $this->thread()->resolveInquiry('our-message-1@example.com', []);

        $this->assertNotNull($resolved);
        $this->assertSame($inquiry->id, $resolved->id);
    }

    public function test_it_resolves_through_the_references_chain(): void
    {
        $inquiry = $this->inquiry('client@example.com', 'abcd1234');
        $this->outbound($inquiry, 'our-message-1@example.com');

        // A client replying months later usually has the whole chain, not just
        // the last hop, so the immediate In-Reply-To is someone else's message.
        $resolved = $this->thread()->resolveInquiry(
            'their-forward@example.com',
            ['their-forward@example.com', 'our-message-1@example.com']
        );

        $this->assertNotNull($resolved);
        $this->assertSame($inquiry->id, $resolved->id);
    }

    /**
     * Regression: the subject token pattern accepted [a-f0-9] while tokens are
     * generated with Str::random(8) over [A-Za-z0-9], so it matched roughly one
     * token in 370,000. The branch was dead, every reply fell through to the
     * sender guess, and that is what made mis-filing routine.
     */
    public function test_it_resolves_the_thread_token_from_the_subject_despite_its_character_set(): void
    {
        $token = Str::random(8);
        $inquiry = $this->inquiry('client@example.com', $token);

        $resolved = $this->thread()->resolveInquiry('', [], "Received — project brief #{$inquiry->id} [WX-{$token}]");

        $this->assertNotNull($resolved, "failed to resolve a subject carrying the real token [WX-{$token}]");
        $this->assertSame($inquiry->id, $resolved->id);
    }

    public function test_the_subject_token_wins_over_the_sender_guess(): void
    {
        // One person, two projects. The subject says which thread; the sender
        // cannot, because both inquiries share the address.
        $token = Str::random(8);
        $newer = $this->inquiry('repeat@example.com', Str::random(8));
        $older = $this->inquiry('repeat@example.com', $token);
        $this->travel(1)->day();

        $resolved = $this->thread()->resolveInquiry('', [], "Re: project brief [WX-{$token}]");

        $this->assertNotNull($resolved);
        $this->assertSame($older->id, $resolved->id, 'the token should win over "most recent inquiry"');
    }

    public function test_it_falls_back_to_the_sender_when_they_have_exactly_one_inquiry(): void
    {
        // The common case: a client with one project, replying from a phone
        // that stripped every identifier. Refusing here would lose real mail.
        $inquiry = $this->inquiry('solo@example.com', Str::random(8));

        $resolved = $this->thread()->resolveInquiry('', [], null, 'solo@example.com');

        $this->assertNotNull($resolved);
        $this->assertSame($inquiry->id, $resolved->id);
    }

    public function test_it_refuses_to_guess_when_the_sender_has_several_inquiries(): void
    {
        Log::spy();

        $this->inquiry('repeat@example.com', Str::random(8));
        $this->travel(1)->day();
        $this->inquiry('repeat@example.com', Str::random(8));

        // No message-id, no token: nothing distinguishes the threads, so the
        // reply must be left alone rather than pinned to the newest inquiry.
        $resolved = $this->thread()->resolveInquiry('', [], null, 'repeat@example.com');

        $this->assertNull($resolved, 'an ambiguous reply was filed against a guessed thread');

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $message, array $context = []): bool => str_contains($message, 'Ambiguous inquiry reply')
                && ($context['sender'] ?? null) === 'repeat@example.com')
            ->once();
    }

    public function test_an_unknown_sender_matches_nothing(): void
    {
        $this->inquiry('client@example.com', Str::random(8));

        $this->assertNull($this->thread()->resolveInquiry('', [], null, 'stranger@elsewhere.test'));
    }

    public function test_a_closed_inquiry_is_still_reachable_by_token(): void
    {
        // Closing a deal must not make a late reply unmatchable — the token
        // identifies the thread regardless of status.
        $token = Str::random(8);
        $inquiry = $this->inquiry('client@example.com', $token, [
            'status' => ProjectInquiry::STATUS_CLOSED,
        ]);

        $resolved = $this->thread()->resolveInquiry('', [], "Re: brief [WX-{$token}]");

        $this->assertNotNull($resolved);
        $this->assertSame($inquiry->id, $resolved->id);
    }
}
