<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An append-only record of sensitive actions.
 *
 * The point is answering "who changed this, when, and what did it say before"
 * months later. That question cannot be answered from the tables being audited:
 * they hold current state only, so an edit is indistinguishable from a
 * creation and a deletion leaves nothing at all. Logging to files is not enough
 * either — those rotate, and they are not queryable.
 *
 * Design notes:
 *
 * - `user_email` is a snapshot, not just a foreign key. If the admin account is
 *   deleted, the trail must still name who did the work, so the FK nulls on
 *   delete while the email stays.
 * - There is no `updated_at`. A row here is a fact about a moment, and
 *   allowing it to be edited afterwards defeats the purpose. The model blocks
 *   updates and deletes for the same reason.
 * - `changes` holds old and new values for edited fields only. Storing the whole
 *   row would bloat the table and bury the part that actually changed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Snapshot, so the trail still names the actor after account removal.
            $table->string('user_email')->nullable();

            // created | updated | deleted | logged_in | login_failed
            $table->string('event', 32);

            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();

            // A sentence a human can read in the admin list without opening the
            // row, e.g. "Updated page: Privacy Policy".
            $table->string('summary');

            // ['title' => ['old' => 'a', 'new' => 'b']]
            $table->json('changes')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // No updated_at: append-only.
            $table->timestamp('created_at')->nullable()->index();
        });

        // The admin list is read newest-first and filtered by model, so the
        // browse query is subject then time, not time alone.
        Schema::table('audit_logs', function (Blueprint $table): void {
            $table->index(['subject_type', 'subject_id']);
            $table->index(['event', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};