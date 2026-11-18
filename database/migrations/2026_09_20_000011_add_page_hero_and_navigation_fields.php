<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns a `pages` row from a metadata stub into a page an editor can build.
 *
 * Before this, a page record could carry a title, an SEO string and a publish
 * flag, and nothing else. Copy lived in Blade, so a page an editor created in
 * the admin had no body, no heading and no way to reach the public site — the
 * record existed and rendered nothing.
 *
 * These columns give the page its own hero, which is the one thing every page
 * needs and the thing a bespoke Blade template cannot hand back to an editor:
 *   eyebrow      the small label above the H1
 *   headline     the H1, kept separate from `title` so the admin-facing title
 *                can stay short while the page can carry a real sentence
 *   intro        the lede paragraph under the H1
 *   show_in_nav  whether the page appears in the header/footer navigation
 *   nav_label    nav text, kept separate from `title` ("Our story" in the menu,
 *                "About Webxpress Technologies" in the admin)
 *   nav_position menu order, so navigation is editable rather than hardcoded
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('eyebrow')->nullable()->after('title');
            $table->string('headline')->nullable()->after('eyebrow');
            $table->text('intro')->nullable()->after('headline');

            $table->boolean('show_in_nav')->default(false)->after('is_published');
            $table->string('nav_label')->nullable()->after('show_in_nav');
            $table->unsignedInteger('nav_position')->default(0)->after('nav_label');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'eyebrow',
                'headline',
                'intro',
                'show_in_nav',
                'nav_label',
                'nav_position',
            ]);
        });
    }
};
