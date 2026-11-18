<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ties a client voice to the work it describes.
 *
 * The `company` column is free text, so it drifts: reviews ended up crediting
 * organisations that appear nowhere in the portfolio, which reads as fabricated
 * on a page whose whole claim is that the work is real. `project_slug` points
 * at the ProjectCatalog entry instead, so the credited company is whatever the
 * catalogue says that project's client is — one source of truth.
 *
 * No foreign key: the catalogue is a PHP array, not a table, so the database
 * cannot enforce it. ClientReview::workProject() resolves the slug and the
 * template falls back to the stored `company` when a slug no longer resolves,
 * so a renamed or removed project degrades to plain text instead of a 500.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('client_reviews', 'project_slug')) {
            Schema::table('client_reviews', function (Blueprint $table): void {
                $table->string('project_slug')->nullable()->after('company');
            });
        }

        // Only the admin filter reads this, but it reads it on every row.
        if (! Schema::hasIndex('client_reviews', 'client_reviews_project_slug_index')) {
            Schema::table('client_reviews', function (Blueprint $table): void {
                $table->index('project_slug');
            });
        }
    }

    public function down(): void
    {
        Schema::table('client_reviews', function (Blueprint $table): void {
            if (Schema::hasIndex('client_reviews', 'client_reviews_project_slug_index')) {
                $table->dropIndex('client_reviews_project_slug_index');
            }

            if (Schema::hasColumn('client_reviews', 'project_slug')) {
                $table->dropColumn('project_slug');
            }
        });
    }
};