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
        Schema::create('page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();

            // Block type, resolved to a Blade renderer in the block registry.
            $table->string('type');

            // Stable identity used for idempotent upserts, e.g. 'who-we-are'.
            $table->string('key');

            $table->unsignedInteger('sort_order')->default(0);

            // Typed, field-level content. Deliberately NOT a rendered HTML
            // blob: the design shell stays in Blade, this holds only copy.
            $table->json('data');

            $table->timestamps();

            $table->unique(['page_id', 'key']);
            $table->index(['page_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_sections');
    }
};
