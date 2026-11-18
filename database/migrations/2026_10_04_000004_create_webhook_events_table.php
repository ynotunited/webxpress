<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The webhook ledger.
 *
 * Paystack retries a failed event every 3 minutes for four attempts and then
 * hourly for 72 hours, and offers to resend any event from the dashboard. A
 * single payment therefore arrives several times by design, and the endpoint
 * cannot tell a retry from an attacker's replay of a captured request — both
 * arrive with an identical, valid signature.
 *
 * So every accepted event is written here before anything else happens, and
 * `dedupe_key` is unique. The insert is what makes a repeat delivery a no-op:
 * the second one collides at the database rather than depending on the
 * application having already flipped a status column. That is the difference
 * between "we checked" and "we could not have processed it twice".
 *
 * Rows are never updated except to stamp processing outcome, and the model
 * refuses updates and deletes outright. This is the record of money arriving,
 * so it has to be independent of the row it describes: a dispute six months
 * from now is settled against what Paystack actually sent, not against what
 * the payments table currently believes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webhook_events', function (Blueprint $table) {
            $table->id();

            // Which gateway. Not an enum: the column exists so a second
            // provider can be added without a migration, and every query that
            // matters filters on it.
            $table->string('provider', 32)->index();

            // The event type from the payload, e.g. charge.success.
            $table->string('event', 64)->index();

            // The transaction reference the event refers to, when it carries
            // one. Indexed because "show me everything that happened to this
            // payment" is the first question asked during a dispute.
            $table->string('reference', 100)->nullable()->index();

            /*
             * Identity of the event for replay purposes.
             *
             * Paystack sends no unique id with the payload, so this is derived
             * from the parts that are guaranteed stable across retries: the
             * provider, the event type and the transaction reference. A genuine
             * resend of the same charge produces the same key and is therefore
             * suppressed; a different charge against the same payment produces
             * a different key and is not.
             */
            $table->string('dedupe_key', 64)->unique();

            // The exact bytes that were signed. Kept verbatim because the
            // signature is only meaningful over the original body — a
            // re-encoded array would not re-verify.
            $table->longText('payload');

            // Where the request appeared to come from, for the allowlist audit.
            $table->string('source_ip', 45)->nullable();

            // Whether the HMAC matched. A rejected request is still recorded so
            // a burst of failures is visible as a pattern rather than as
            // individual log lines.
            $table->boolean('signature_valid')->default(false);

            // received | ignored | queued | processed | failed
            $table->string('status', 16)->default('received')->index();

            // Why an event was refused or could not be processed. This is the
            // field that answers "why is this payment still pending".
            $table->string('failure_reason', 255)->nullable();

            $table->timestamp('payload_received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);

            $table->timestamps();

            $table->index(['provider', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_events');
    }
};