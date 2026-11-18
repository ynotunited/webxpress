<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('deal_id')->constrained()->cascadeOnDelete();
            $table->string('kind'); // upfront | delivery
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('pending')->index(); // pending | paid | failed
            $table->string('paystack_reference', 100)->nullable()->index();
            $table->string('paystack_access_code', 100)->nullable();
            $table->text('authorization_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
