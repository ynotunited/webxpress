<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('project_inquiries', function (Blueprint $table) {
            $table->string('thread_token', 12)->nullable()->after('timeline');
        });

        Schema::create('inquiry_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_inquiry_id')->constrained()->cascadeOnDelete();
            $table->string('direction'); // inbound | outbound
            $table->string('type')->default('reply'); // confirmation | ack | reply | note
            $table->string('subject')->nullable();
            $table->text('body');
            $table->string('from_email')->nullable();
            $table->string('from_name')->nullable();
            $table->string('message_id')->nullable()->index();
            $table->string('in_reply_to')->nullable();
            $table->text('headers')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inquiry_messages');
        Schema::table('project_inquiries', function (Blueprint $table) {
            $table->dropColumn('thread_token');
        });
    }
};
