<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the queries the public site actually runs.
 *
 * Every index here was chosen from an EXPLAIN of a real query, not from the
 * columns that look important in the abstract:
 *
 *  - posts: the insights listing is `is_published = 1 AND published_at <= now()
 *    ORDER BY published_at DESC`, and a single insight is looked up by slug.
 *    Both were full table scans (type=ALL, key=NULL). The composite index lets
 *    MySQL satisfy the filter and the sort from one structure.
 *  - posts.slug is declared unique in the create migration, but no unique
 *    index actually existed on the table, so the constraint the code relies on
 *    to keep slugs unambiguous was never enforced.
 *  - client_reviews: testimonials are read as published reviews ordered by
 *    featured, then sort_order, then id. Also a full table scan.
 *
 * Written to be driver-agnostic and idempotent. Index introspection goes
 * through Schema::hasIndex() rather than SHOW INDEX, which is MySQL-only and
 * would fail outright against the SQLite connection the test suite uses. A
 * fresh database gets only what it needs and an existing one is topped up, so
 * this is safe to run against a populated table without a long table lock.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->addIndexIfMissing('posts', 'posts_slug_unique', ['slug'], true);
        $this->addIndexIfMissing('posts', 'posts_category_id_index', ['category_id']);
        $this->addIndexIfMissing('posts', 'posts_published_feed_index', ['is_published', 'published_at']);
        $this->addIndexIfMissing('posts', 'posts_featured_index', ['featured', 'is_published', 'published_at']);

        $this->addIndexIfMissing(
            'client_reviews',
            'client_reviews_published_order_index',
            ['is_published', 'is_featured', 'sort_order', 'id'],
        );
    }

    public function down(): void
    {
        $this->dropIndexIfPresent('posts', 'posts_featured_index');
        $this->dropIndexIfPresent('posts', 'posts_published_feed_index');
        $this->dropIndexIfPresent('posts', 'posts_category_id_index');
        $this->dropIndexIfPresent('posts', 'posts_slug_unique');
        $this->dropIndexIfPresent('client_reviews', 'client_reviews_published_order_index');
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function addIndexIfMissing(string $table, string $name, array $columns, bool $unique = false): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $name)) {
            return;
        }

        // Refuse to add a unique index over duplicate values: it would fail
        // mid-statement and, depending on the engine, could leave the table
        // locked. Report it instead of failing the deploy.
        if ($unique && $this->hasDuplicates($table, $columns)) {
            fwrite(STDERR, "Skipped unique index {$name} on {$table}: duplicate values exist.".PHP_EOL);

            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($name, $columns, $unique): void {
            $unique
                ? $blueprint->unique($columns, $name)
                : $blueprint->index($columns, $name);
        });
    }

    private function dropIndexIfPresent(string $table, string $name): void
    {
        if (Schema::hasTable($table) && Schema::hasIndex($table, $name)) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
        }
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function hasDuplicates(string $table, array $columns): bool
    {
        return DB::table($table)
            ->select(DB::raw(implode(', ', array_map(
                fn (string $column): string => '"'.$column.'"',
                $columns
            )).' , COUNT(*) AS duplicate_count'))
            ->groupBy($columns)
            ->havingRaw('COUNT(*) > 1')
            ->exists();
    }
};