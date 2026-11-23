<?php

use App\Models\Page;
use App\Models\SiteSetting;
use Illuminate\Support\Str;

if (! function_exists('site_setting')) {
    function site_setting(string $key, mixed $default = null): mixed
    {
        return SiteSetting::value($key, $default);
    }
}

if (! function_exists('whatsapp_url')) {
    /**
     * Turn a saved WhatsApp number into a wa.me link. Accepts an existing
     * wa.me URL as well so an editor can paste either form.
     */
    function whatsapp_url(string $number): ?string
    {
        $number = trim($number);

        if ($number === '') {
            return null;
        }

        if (preg_match('#^https?://(?:api\.)?wa\.me/#i', $number)) {
            return $number;
        }

        $digits = preg_replace('/\D+/', '', $number);

        return $digits !== '' ? 'https://wa.me/'.$digits : null;
    }
}

if (! function_exists('site_page')) {
    function site_page(string $slug): ?Page
    {
        return Page::query()->where('slug', $slug)->where('is_published', true)->first();
    }
}

if (! function_exists('site_name')) {
    /**
     * The site's display name, used for logo alt text, aria labels, page
     * titles and structured data. Falls back to APP_NAME.
     */
    function site_name(): string
    {
        $name = trim((string) SiteSetting::value('site_name', ''));

        return $name !== '' ? $name : (string) config('app.name', 'Webxpress Technologies');
    }
}

if (! function_exists('brand_asset')) {
    /**
     * Resolve a brand asset (logo, favicon) to a public URL.
     *
     * Accepts an absolute URL, a root-relative public path, or a path on the
     * public storage disk (which is what the admin uploader writes). Blank or
     * missing settings fall back to the shipped default.
     *
     * Deliberately not memoised: a logo uploaded in the admin must appear on
     * the next request, not after a cache flush.
     */
    function brand_asset(string $key, string $default): string
    {
        $value = trim((string) SiteSetting::value($key, $default));

        if ($value === '') {
            $value = $default;
        }

        if (Str::startsWith($value, ['http://', 'https://', '//', '/'])) {
            return $value;
        }

        return asset('storage/'.$value);
    }
}

if (! function_exists('brand_logo')) {
    /**
     * URL for the site logo. $variant is 'colored' (light backgrounds) or
     * 'white' (dark backgrounds).
     */
    function brand_logo(string $variant = 'colored'): string
    {
        $variant = in_array($variant, ['colored', 'white'], true) ? $variant : 'colored';

        return brand_asset('logo_'.$variant, '/images/logo-'.$variant.'.png');
    }
}

if (! function_exists('brand_favicon')) {
    function brand_favicon(): string
    {
        return brand_asset('favicon', '/favicon.png');
    }
}

if (! function_exists('og_image_url')) {
    /**
     * Absolute URL of the social preview image.
     *
     * Link unfurlers fetch this URL from their own servers, so a root-relative
     * path is not good enough here. Accepts an absolute URL, a root-relative
     * public path, or a path on the public disk (what Filament writes), and
     * falls back to the shipped default when no setting has been saved.
     */
    function og_image_url(): string
    {
        $value = trim((string) SiteSetting::value('og_image', ''));

        if ($value === '') {
            return asset('images/og/webxpress-preview.png');
        }

        if (Str::startsWith($value, ['http://', 'https://', '//'])) {
            return $value;
        }

        return Str::startsWith($value, '/')
            ? url($value)
            : asset('storage/'.$value);
    }
}

if (! function_exists('admin_path')) {
    /**
     * The admin panel's URL segment, sanitised.
     *
     * One definition, read by the panel itself, by the CMS catch-all that must
     * not let a page be created on top of it, and by the admin-facing emails
     * that link into it. Three places deriving the path independently is how a
     * changed ADMIN_PATH quietly breaks the buttons in an email nobody retests.
     *
     * Sanitised rather than trusted: a value carrying a slash, a dot or a space
     * could otherwise mount the panel at a nested path or collide with the
     * catch-all, so anything outside [a-z0-9_-] is dropped and the result is
     * lowercased. A value that strips down to nothing falls back to 'admin'
     * rather than to the site root, where the panel would sit on top of the
     * homepage and every panel URL would collide with a real page.
     */
    function admin_path(?string $suffix = null): string
    {
        $segment = strtolower((string) preg_replace('/[^a-zA-Z0-9_-]/', '', trim((string) config('admin.path', 'admin'))));
        $segment = $segment !== '' ? $segment : 'admin';

        return $suffix === null ? $segment : $segment.'/'.ltrim($suffix, '/');
    }
}

if (! function_exists('admin_url')) {
    /**
     * Absolute URL to a screen in the admin panel.
     *
     * The admin notification emails link into the panel. They resolve the path
     * through here rather than hardcoding /admin, so changing ADMIN_PATH cannot
     * leave a live client-facing email pointing at a 404.
     */
    function admin_url(string $suffix = ''): string
    {
        return url(admin_path($suffix));
    }
}

if (! function_exists('csp_nonce')) {
    /**
     * The content security policy nonce for the current request.
     *
     * Content-Security-Policy blocks inline <script> and <style> unless they
     * carry a nonce the header also names. Every inline block in our own views
     * must therefore print `nonce="{{ csp_nonce() }}"` — an inline tag without
     * it does not execute, which is exactly the behaviour that makes the policy
     * worth having.
     *
     * Held in the container rather than a static so it is one value per request,
     * generated by the middleware before any view renders and read again when
     * the header is assembled afterwards. Generated lazily as a fallback so a
     * template rendered with the middleware switched off still produces a
     * well-formed (if unused) attribute instead of an error.
     */
    function csp_nonce(): string
    {
        if (! app()->bound('csp.nonce')) {
            app()->instance('csp.nonce', bin2hex(random_bytes(16)));
        }

        return app('csp.nonce');
    }
}

if (! function_exists('asset_version')) {
    /**
     * Build stamp used to bust the service worker cache.
     *
     * Derived from the mtime of the newest build artefact (Vite manifest,
     * falling back to the worker script itself) so any asset rebuild
     * invalidates cached HTML, and any edit to the worker invalidates itself.
     * Memoised per request because filemtime hits the disk.
     */
    function asset_version(): string
    {
        static $version = null;

        if ($version !== null) {
            return $version;
        }

        $candidates = [
            public_path('build/manifest.json'),
            public_path('build/.vite/manifest.json'),
            public_path('sw.js'),
        ];

        $latest = 0;
        foreach ($candidates as $path) {
            if (is_file($path)) {
                $latest = max($latest, (int) filemtime($path));
            }
        }

        return $version = $latest > 0 ? (string) $latest : '1';
    }
}

if (! function_exists('client_logos')) {
    /**
     * Client logos for the "trusted by" strips, one entry per logo.
     *
     * Returns ['path' => ..., 'name' => ...] keyed by URL-relative path.
     *
     * Preferring WebP here is the point: the strip renders logos 136px wide,
     * and two of them were being shipped as 1200px PNGs that weighed 660 KB
     * and 560 KB. They are now 19 KB and 23 KB.
     *
     * Both formats sit in the folder for the logos that were converted, so a
     * plain wildcard glob would list each logo twice and the marquee would
     * show every client in pairs. The list is therefore built from filenames
     * with the extension stripped, and each is resolved to the best format
     * available: WebP if there is one, otherwise the original.
     *
     * Memoised per request. Two sections render this strip and the directory
     * listing does not change mid-request.
     *
     * @return array<string, array{path: string, name: string}>
     */
    function client_logos(): array
    {
        static $logos = null;

        if ($logos !== null) {
            return $logos;
        }

        $directory = 'images/clients';

        $found = [];
        foreach (glob(public_path($directory.'/*')) ?: [] as $file) {
            if (! is_file($file)) {
                continue;
            }

            $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            if (! in_array($extension, ['png', 'jpg', 'jpeg', 'webp'], true)) {
                continue;
            }

            $key = pathinfo($file, PATHINFO_FILENAME);
            $existing = $found[$key] ?? null;

            // WebP wins over anything else; otherwise keep what we have.
            if ($existing === null || $extension === 'webp') {
                $found[$key] = $extension;
            }
        }

        ksort($found);

        $logos = [];
        foreach ($found as $key => $extension) {
            $logos[$key] = [
                'path' => $directory.'/'.$key.'.'.$extension,
                'name' => \Illuminate\Support\Str::headline($key),
            ];
        }

return $logos;
}
}
