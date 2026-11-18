<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Converts image assets to WebP, optionally cropping to the ratio the template
 * actually displays.
 *
 * Two problems motivated this, both in public/images:
 *
 *  - The /work screenshots are full-page captures saved as PNG: 87 files,
 *    roughly 50 MB, 587 KB average, with the largest single cover image at
 *    1.9 MB. A visitor on a 3G connection spent about forty seconds on one
 *    cover image. As WebP the same set is 7 MB.
 *  - about/product-workshop.png is 2.6 MB, and it is displayed in a 3:4 box
 *    with object-cover, so more than half its pixels are thrown away after
 *    they have already been downloaded. Cropping before encoding removes the
 *    waste instead of paying for it.
 *
 * PNG is lossless and photographs badly. These are screenshots and flat
 * artwork, which is where WebP holds edges cleanly at these settings.
 *
 * Source files are left in place. Nothing needs them once callers prefer WebP,
 * but they are the source of truth for a re-run at different settings, and the
 * files are not tracked in git, so deleting them here would be irreversible.
 * Pass --purge once the output has been checked.
 *
 * Results are written next to the originals with a .webp extension. Callers
 * must point at that extension explicitly rather than globbing a wildcard:
 * with both formats in one folder a wildcard interleaves x-01.png with
 * x-01.webp and the natural sort produces two entries per capture.
 */
class OptimizeImages extends Command
{
    protected $signature = 'images:optimize
                            {path? : Directory under public/ to convert, relative to public/ (default: images/works)}
                            {--quality=82 : WebP quality, 1-100}
                            {--max-width=0 : Target maximum width in pixels; 0 means keep the original width. Files already at or below the target are left alone}
                            {--min-bytes=0 : Skip source files smaller than this many bytes, which are cheaper to serve as they are}
                            {--crop= : Centre-crop to this ratio before encoding, e.g. 3:4}
                            {--extensions=png,jpg,jpeg : Comma-separated source extensions}
                            {--only= : Filename filter, e.g. product-workshop*. Skips everything else in the directory}
                            {--dry-run : Report what would change without writing anything}
                            {--purge : Delete the source files after a successful conversion}';

    protected $description = 'Convert image assets to WebP so the site ships a fraction of the bytes';

    public function handle(): int
    {
        if (! function_exists('imagewebp')) {
            $this->error('This PHP build has no GD WebP support. Install ext-gd with WebP, or run on a host that has it.');

            return self::FAILURE;
        }

        $quality = (int) $this->option('quality');
        $maxWidth = (int) $this->option('max-width');
        $minBytes = (int) $this->option('min-bytes');
        $crop = $this->parseCrop((string) $this->option('crop'));
        $dryRun = (bool) $this->option('dry-run');

        if ($quality < 1 || $quality > 100) {
            $this->error('--quality must be between 1 and 100.');

            return self::FAILURE;
        }

        $relative = trim((string) ($this->argument('path') ?: 'images/works'), '/');
        $root = public_path($relative);

        if (! is_dir($root)) {
            $this->error("No image directory at {$root}.");

            return self::FAILURE;
        }

        [$sources, $skipped] = $this->selectSources($root, (string) $this->option('extensions'), $maxWidth, $minBytes);

        if ($sources === []) {
            $this->info('Nothing to convert: every candidate was already at or below the given size.');

            return self::SUCCESS;
        }

        $this->line(sprintf(
            'Converting %d image(s) from %s at quality %d%s%s',
            count($sources),
            $relative,
            $quality,
            $maxWidth > 0 ? ", max width {$maxWidth}px" : '',
            $crop !== null ? ", cropped {$crop[0]}:{$crop[1]}" : '',
        ));

        if ($skipped !== []) {
            $this->line('Leaving alone: '.implode(', ', $skipped));
        }

        $this->newLine();

        $rows = [];
        $before = 0;
        $after = 0;
        $failures = 0;

        foreach ($sources as $source) {
            $name = substr($source, strlen($root) + 1);
            $target = preg_replace('/\.(png|jpe?g)$/i', '.webp', $source);
            $before += filesize($source);

            $resultSize = $dryRun
                ? $this->encode($source, null, $quality, $maxWidth, $crop)
                : $this->encode($source, (string) $target, $quality, $maxWidth, $crop);

            if ($resultSize === null) {
                $this->twoColumnDetail('FAILED  '.$name, '');
                $failures++;

                continue;
            }

            $after += $resultSize;
            $rows[] = [$name, filesize($source), $resultSize];
        }

        $this->renderTable($rows);
        $this->newLine();

        if ($failures > 0) {
            $this->error("{$failures} image(s) failed; their sources were left untouched.");

            return self::FAILURE;
        }

        if ($dryRun) {
            $this->line(sprintf('Dry run. %.1f MB would become %.1f MB.', $before / 1048576, $after / 1048576));

            return self::SUCCESS;
        }

        if ($this->option('purge')) {
            foreach ($sources as $source) {
                @unlink($source);
            }

            $this->line('Source files removed.');
        } else {
            $this->line('Sources kept. Re-run with --purge once you have checked the output.');
        }

        $this->line(sprintf('Done. %.1f MB becomes %.1f MB of WebP.', $before / 1048576, $after / 1048576));

        return self::SUCCESS;
    }

    /**
     * Candidate sources, plus the names deliberately left alone.
     *
     * Two guards keep the command safe to point at a whole directory. An
     * image already narrower than --max-width is already the right size for
     * its slot, and re-encoding it can make it bigger: four of the twelve
     * client logos came out heavier as WebP than they were as PNG. An image
     * under --min-bytes is not worth touching at all. Converting those anyway
     * would churn files for no gain, so they are reported and skipped.
     *
     * @return array{0: array<int, string>, 1: array<int, string>}
     */
    private function selectSources(string $root, string $extensions, int $maxWidth, int $minBytes): array
    {
        $selected = [];
        $skipped = [];

        foreach ($this->sources($root, $extensions) as $file) {
            if (filesize($file) < $minBytes) {
                $skipped[] = basename($file).' (under --min-bytes)';

                continue;
            }

            if ($maxWidth > 0) {
                $info = @getimagesize($file);
                if ($info !== false && $info[0] <= $maxWidth) {
                    $skipped[] = basename($file).' (already '.$info[0].'px wide)';

                    continue;
                }
            }

            $selected[] = $file;
        }

        return [$selected, $skipped];
    }

    /**
     * @return array<int, string>
     */
    private function sources(string $root, string $extensions): array
    {
        $patterns = array_map(
            static fn (string $extension): string => '*.'.trim($extension),
            array_filter(array_map('trim', explode(',', $extensions)))
        );

        $found = [];
        foreach ($patterns as $pattern) {
            foreach (glob($root.'/'.$pattern) ?: [] as $file) {
                if (is_file($file)) {
                    $found[] = $file;
                }
            }
        }

        $found = array_values(array_unique($found));

        $only = (string) $this->option('only');
        if ($only !== '') {
            $found = array_values(array_filter(
                $found,
                static fn (string $file): bool => fnmatch($only, basename($file))
            ));
        }

        sort($found, SORT_NATURAL);

        return $found;
    }

    /**
     * @return array{0: int, 1: int}|null
     */
    private function parseCrop(string $ratio): ?array
    {
        if ($ratio === '') {
            return null;
        }

        if (! preg_match('/^(\d+)\s*[:x\/]\s*(\d+)$/', $ratio, $matches)) {
            $this->error("--crop must look like 3:4, got '{$ratio}'.");

            return null;
        }

        $width = (int) $matches[1];
        $height = (int) $matches[2];

        if ($width < 1 || $height < 1) {
            $this->error('--crop needs positive numbers on both sides.');

            return null;
        }

        return [$width, $height];
    }

    /**
     * Encode one image, writing to $target or returning the byte count when
     * $target is null (dry run).
     *
     * Every intermediate result is checked. GD signals failure by returning
     * false from the transform and warning on stderr, which is easy to miss
     * and produces a fatal TypeError later if the value reaches imagewebp().
     *
     * @param  array{0: int, 1: int}|null  $crop
     */
    private function encode(string $source, ?string $target, int $quality, int $maxWidth, ?array $crop): ?int
    {
        $image = $this->read($source);

        if ($image === null) {
            return null;
        }

        $image = $this->applyCrop($image, $crop) ?? $image;

        if ($maxWidth > 0 && imagesx($image) > $maxWidth) {
            $width = imagesx($image);
            $height = imagesy($image);
            $scaled = $this->rescale($image, $maxWidth, (int) round($height * ($maxWidth / $width)));

            if ($scaled === null) {
                imagedestroy($image);

                return null;
            }

            imagedestroy($image);
            $image = $scaled;
        }

        if ($target === null) {
            ob_start();
            $ok = imagewebp($image, null, $quality);
            $bytes = (string) ob_get_clean();
            imagedestroy($image);

            return $ok ? strlen($bytes) : null;
        }

        // Write to a temporary name and move into place, so an interrupted run
        // cannot leave a truncated .webp that a template will happily serve.
        $temporary = $target.'.tmp';
        $written = imagewebp($image, $temporary, $quality);
        imagedestroy($image);

        if (! $written || ! is_file($temporary)) {
            @unlink($temporary);

            return null;
        }

        return rename($temporary, $target) ? filesize($target) : null;
    }

    /**
     * Resample in the image's own colour space.
     *
     * The fourth argument to imagescale() is a resampling *quality mode*, not
     * an output format. Passing IMG_WEBP there is invalid, and GD answers by
     * returning false with a warning rather than by refusing the call, which
     * then surfaces much later as a fatal on the untouched false value.
     */
    private function rescale(\GdImage $image, int $width, int $height): ?\GdImage
    {
        $width = max(1, $width);
        $height = max(1, $height);

        $scaled = @imagescale($image, $width, $height, IMG_BICUBIC_FIXED);

        return $scaled === false ? null : $scaled;
    }

    /**
     * Centre-crop to the target ratio.
     *
     * This mirrors what object-cover does in CSS. Cropping here rather than in
     * the browser is the point: the pixels that the crop would discard are
     * never encoded, so they cost nothing to send.
     *
     * @param  array{0: int, 1: int}|null  $crop
     */
    private function applyCrop(\GdImage $image, ?array $crop): ?\GdImage
    {
        if ($crop === null) {
            return $image;
        }

        [$ratioWidth, $ratioHeight] = $crop;
        $width = imagesx($image);
        $height = imagesy($image);

        $current = $width / $height;
        $target = $ratioWidth / $ratioHeight;

        if (abs($current - $target) < 0.001) {
            return $image;
        }

        if ($current > $target) {
            // Too wide: keep full height, trim the sides evenly.
            $cropWidth = (int) round($height * $target);
            $cropHeight = $height;
        } else {
            // Too tall: keep full width, trim top and bottom evenly.
            $cropWidth = $width;
            $cropHeight = (int) round($width / $target);
        }

        $cropWidth = min($cropWidth, $width);
        $cropHeight = min($cropHeight, $height);

        $result = @imagecrop($image, [
            'x' => (int) round(($width - $cropWidth) / 2),
            'y' => (int) round(($height - $cropHeight) / 2),
            'width' => $cropWidth,
            'height' => $cropHeight,
        ]);

        if ($result === false) {
            return null;
        }

        imagedestroy($image);

        return $result;
    }

    private function read(string $source): ?\GdImage
    {
        $info = @getimagesize($source);

        if ($info === false) {
            return null;
        }

        // Dispatch on the real content type, not the file extension. Several
        // files in this tree are WebP data behind a .png name, so matching on
        // the extension alone would hand a WebP to imagecreatefrompng() and
        // fail on a file that decodes perfectly well.
        $image = match ($info[2]) {
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            default => null,
        };

        if ($image === false || $image === null) {
            return null;
        }

        imagepalettetotruecolor($image);

        return $image;
    }

    /**
     * @param  array<int, array{0: string, 1: int, 2: int}>  $rows
     */
    private function renderTable(array $rows): void
    {
        usort($rows, fn (array $a, array $b): int => $b[1] <=> $a[1]);

        $human = fn (int $bytes): string => $this->human($bytes);

        $this->table(
            ['file', 'before', 'WebP', 'saved'],
            array_map(static fn (array $row): array => [
                $row[0],
                $human($row[1]),
                $human($row[2]),
                $row[1] > 0 ? round((1 - $row[2] / $row[1]) * 100).'%' : '-',
            ], $rows)
        );
    }

    private function human(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 2).' MB'
            : round($bytes / 1024).' KB';
    }
}