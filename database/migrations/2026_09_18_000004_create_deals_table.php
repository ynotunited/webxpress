<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_inquiry_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('company')->nullable();
            $table->string('project_title')->nullable();
            $table->string('project_type')->nullable();
            $table->text('scope')->nullable();
            $table->decimal('amount', 15, 2);
            $table->unsignedTinyInteger('upfront_percent')->default(70);
            $table->unsignedTinyInteger('delivery_percent')->default(30);
            $table->date('start_date')->nullable();
            $table->string('duration_label')->nullable();
            $table->text('milestones')->nullable();
            $table->text('terms')->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('access_token', 24)->unique();
            $table->string('created_via')->default('manual');
            $table->timestamp('documents_sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deals');
    }
};
