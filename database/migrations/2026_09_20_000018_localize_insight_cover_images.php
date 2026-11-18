<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Point insight covers at the local copies in public/images/insights.
 *
 * Seeded posts stored an absolute images.unsplash.com URL, which made every
 * insight cover depend on a third-party host the site does not control — and
 * fail outright wherever that host is unreachable. The same files now ship
 * with the app, so the column holds a path under public/images and the
 * Post model's existing accessor resolves it to /images/insights/....
 *
 * Only rows still holding the old hotlinked URL are touched, so a cover an
 * editor uploaded through the admin is never overwritten.
 */
return new class extends Migration
{
    private const LOCAL_DIR = 'public/images/insights';

    public function up(): void
    {
        $map = $this->localPathsBySource();
        if ($map === []) {
            return;
        }

        $changed = 0;

        foreach ($map as $source => $local) {
            $changed += DB::table('posts')
                ->where('cover_image', $source)
                ->update(['cover_image' => $local]);
        }

        // Any remaining hotlinked row whose photo has no local copy would stay
        // broken; clear it so the template falls back to no image instead of a
        // request that cannot succeed.
        $changed += DB::table('posts')
            ->where('cover_image', 'like', 'https://images.unsplash.com/%')
            ->whereNotIn('cover_image', array_keys($map))
            ->update(['cover_image' => null]);

        if ($changed > 0) {
            $this->write("localised {$changed} insight cover(s)");
        }
    }

    public function down(): void
    {
        // The local files are a superset of what the old URLs pointed at, and
        // the previous remote URLs are not a reliable thing to restore to, so
        // the reverse step simply drops the local covers.
        DB::table('posts')
            ->where('cover_image', 'like', 'images/insights/%')
            ->update(['cover_image' => null]);
    }

    /**
     * Build source-URL => local-path from the committed manifest, so the
     * mapping lives with the images rather than being duplicated in code.
     *
     * @return array<string, string>
     */
    private function localPathsBySource(): array
    {
        $manifestPath = base_path(self::LOCAL_DIR.'/manifest.json');

        if (! is_file($manifestPath)) {
            $this->write('no manifest at '.self::LOCAL_DIR.'/manifest.json; skipping', warning: true);

            return [];
        }

        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        if (! is_array($manifest)) {
            return [];
        }

        $map = [];
        foreach ($manifest as $entry) {
            $source = $entry['source'] ?? null;
            $path = $entry['path'] ?? null;

            // Only map to a file that actually shipped, otherwise the
            // migration would point at a 404.
            if (! is_string($source) || ! is_string($path)) {
                continue;
            }

            if (! is_file(public_path($path))) {
                continue;
            }

            $map[$source] = $path;
        }

        return $map;
    }

    private function write(string $message, bool $warning = false): void
    {
        if ($warning) {
            fwrite(STDERR, $message.PHP_EOL);

            return;
        }

        fwrite(STDOUT, $message.PHP_EOL);
    }
};
