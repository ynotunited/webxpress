<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $page = DB::table('pages')->where('slug', 'about')->first();

        if (! $page) {
            return;
        }

        $content = json_decode((string) $page->structured_content, true) ?: [];
        $content['images']['hero'] = '/images/about/hero-product-planning.jpg';

        DB::table('pages')->where('id', $page->id)->update([
            'structured_content' => json_encode($content),
        ]);
    }

    public function down(): void
    {
        $page = DB::table('pages')->where('slug', 'about')->first();

        if (! $page) {
            return;
        }

        $content = json_decode((string) $page->structured_content, true) ?: [];
        $content['images']['hero'] = 'images/about/hero-product-planning.jpg';

        DB::table('pages')->where('id', $page->id)->update([
            'structured_content' => json_encode($content),
        ]);
    }
};
