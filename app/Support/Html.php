<?php

namespace App\Support;

use Illuminate\Support\Str;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * The one place raw HTML is allowed to become a string on a public page.
 *
 * Every other value an editor types is escaped by Blade. Rich text is the
 * exception: it has to be echoed unescaped to render at all, which makes the
 * rich text fields — page bodies, prose blocks, FAQ answers — stored-XSS
 * surface. They are sanitised on the way in (so the database only ever holds
 * safe markup) and again on the way out (so seeded, imported, or
 * hand-edited rows are covered too).
 *
 * The allowlist is deliberately narrow: the elements a rich text field
 * actually produces, and the attributes those elements need. `class` is
 * allowed because Filament's editor emits alignment classes (`text-center`);
 * `style` is not, because an inline-style allowlist cannot be reasoned about
 * and `style` is the easiest route from "editor" to "defacement".
 *
 * Link and media schemes are restricted rather than merely stripped, so a
 * `javascript:` URL is removed instead of being rewritten into something that
 * still runs.
 */
final class Html
{
    /**
     * Elements a rich text field may emit. Everything else is unwrapped
     * (children survive) or dropped, per the config below.
     *
     * @var list<string>
     */
    private const ALLOWED_ELEMENTS = [
        'p', 'br', 'hr',
        'h2', 'h3', 'h4', 'h5', 'h6',
        'strong', 'b', 'em', 'i', 'u', 's', 'sup', 'sub', 'small', 'mark',
        'a', 'ul', 'ol', 'li',
        'blockquote', 'pre', 'code',
        'figure', 'figcaption', 'img',
        'table', 'thead', 'tbody', 'tfoot', 'tr', 'th', 'td', 'caption',
        'div', 'span', 'section', 'article',
    ];

    /**
     * Attributes permitted per element. `style` is intentionally absent.
     *
     * @var array<string, list<string>>
     */
    private const ALLOWED_ATTRIBUTES = [
        'a' => ['href', 'title', 'target', 'rel', 'class'],
        'img' => ['src', 'alt', 'title', 'width', 'height', 'loading', 'class'],
        'td' => ['colspan', 'rowspan', 'class'],
        'th' => ['colspan', 'rowspan', 'scope', 'class'],
        'ol' => ['start', 'reversed', 'class'],
        '*' => ['class', 'dir', 'lang'],
    ];

    /**
     * Built sanitizers, keyed by the media host list they were compiled for.
     *
     * The allowlist is read from config, and config is not stable for the
     * lifetime of the process: a test that points `app.url` somewhere else
     * would otherwise keep reusing a sanitizer built for the previous host and
     * quietly accept or reject the wrong images.
     *
     * @var array<string, HtmlSanitizer>
     */
    private static array $sanitizers = [];

    /**
     * Reduce rich text to the safe subset of markup.
     *
     * Idempotent: sanitising already-sanitised markup returns it unchanged, so
     * sanitising on save and again on render cannot compound or corrupt.
     */
    public static function sanitize(?string $html): string
    {
        if ($html === null) {
            return '';
        }

        $html = trim($html);

        if ($html === '') {
            return '';
        }

        return self::stripOrphanImages(self::sanitizer()->sanitize($html));
    }

    /**
     * Remove `<img>` elements the URL allowlist emptied.
     *
     * Symfony drops a rejected `src` attribute but keeps the element, which
     * leaves a tag with no source rendering as a broken-image icon plus its
     * alt text. Stripping the whole element is the honest result: there is no
     * image to show.
     */
    private static function stripOrphanImages(string $html): string
    {
        if (! str_contains($html, '<img')) {
            return $html;
        }

        $clean = preg_replace(
            '~<img\b(?![^>]*\bsrc\s*=)[^>]*>\s*~i',
            '',
            $html,
        );

        return $clean ?? $html;
    }

    /**
     * Whether a string is already safe to echo unescaped.
     *
     * Lets the model skip a redundant sanitise pass on hot read paths without
     * ever trusting the value on the strength of the check alone.
     */
    public static function isSanitized(?string $html): bool
    {
        return $html === null || $html === '' || self::sanitize($html) === $html;
    }

    /**
     * Schemes an `<a href>` may use. `javascript:`, `data:` and `vbscript:`
     * are absent by omission rather than by blocklist, so a scheme nobody has
     * thought of yet is dropped by default.
     *
     * @var list<string>
     */
    private const LINK_SCHEMES = ['http', 'https', 'mailto', 'tel'];

    /**
     * Schemes an `<img src>` may use. Narrower than the link list: neither
     * `mailto:` nor `tel:` is a valid media source, and there is no `data:`,
     * because a data: URI carrying SVG is a standard way to smuggle script
     * past an element filter.
     *
     * @var list<string>
     */
    private const MEDIA_SCHEMES = ['http', 'https'];

    /**
     * Whether an `<img src>` pointing off-site is a sanitise-time failure.
     *
     * Every image this site publishes is stored in the repository and served
     * from the same origin, so a remote `<img>` is either an editor pasting a
     * hotlink by mistake or a page borrowing someone else's bandwidth and
     * quietly breaking when that URL rots. Both are worth refusing.
     *
     * Links are deliberately unaffected: outbound `<a href>` is a normal thing
     * for an editor to write, and the site's own copy links off-site.
     */
    private const ALLOW_REMOTE_IMAGES = false;

    /**
     * Dropped along with their contents. Their text *is* the danger, so
     * unwrapping them would publish a script's source or a stylesheet's body
     * to the reader as body copy.
     *
     * @var list<string>
     */
    private const DROPPED_ELEMENTS = [
        'script', 'style', 'iframe', 'object', 'embed',
        'form', 'input', 'button', 'link', 'meta', 'base',
        'svg', 'math', 'template', 'noscript',
    ];

    /**
     * Blocked, but their children are kept. These wrap ordinary copy that we
     * simply do not support, and the copy is worth more than the wrapper.
     *
     * @var list<string>
     */
    private const BLOCKED_ELEMENTS = [
        'canvas', 'video', 'audio', 'source', 'track', 'map', 'area', 'dialog',
    ];

    /**
     * Upper bound on a single rich text value. An editor pasting a whole
     * document, or a crafted request straight at the admin, should not be able
     * to push megabytes of markup through a text column.
     */
    private const MAX_INPUT_LENGTH = 200_000;

    /**
     * Hosts an absolute `<img src>` may point at.
     *
     * Filament's rich text uploader resolves an attachment through the
     * filesystem disk, which yields an absolute URL on the configured app URL
     * rather than a root-relative path. Without an allowlist that own-domain
     * URL would be indistinguishable from a hotlink, and simply banning
     * `http`/`https` would break the editor's own uploads — so the rule is
     * expressed as "our host, and nothing else".
     *
     * Returned as a plain list because Symfony matches hosts by suffix
     * segments, which means `evil-webxpresstech.com.ng` does not match
     * `webxpresstech.com.ng`: the label boundary has to line up.
     *
     * @return list<string>
     */
    public static function mediaHosts(): array
    {
        if (self::ALLOW_REMOTE_IMAGES) {
            return [];
        }

        $hosts = [];

        foreach ([config('app.url'), config('filesystems.disks.public.url')] as $url) {
            $host = parse_url((string) $url, PHP_URL_HOST);

            if (is_string($host) && $host !== '') {
                $hosts[strtolower($host)] = true;
            }
        }

        return array_keys($hosts);
    }

    /**
     * Whether a rich text value contains an image this site does not host.
     *
     * Used to tell an editor their pasted image was dropped, so the removal is
     * never silent. Checks the value before sanitising, because sanitising is
     * what removes the evidence.
     */
    public static function hasRemoteImage(?string $html): bool
    {
        if ($html === null || $html === '' || ! str_contains($html, '<img')) {
            return false;
        }

        if (preg_match_all('~<img\b[^>]*\bsrc\s*=\s*["\']?([^"\'\s>]+)~i', $html, $matches) === false) {
            return false;
        }

        foreach ($matches[1] as $src) {
            $src = html_entity_decode($src, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Root-relative and bare paths are ours by construction; only an
            // absolute URL naming another host is somebody else's image.
            if (! Str::startsWith($src, ['http://', 'https://', '//'])) {
                continue;
            }

            $host = strtolower((string) parse_url(Str::startsWith($src, '//') ? 'https:'.$src : $src, PHP_URL_HOST));

            if ($host === '') {
                continue;
            }

            foreach (self::mediaHosts() as $allowed) {
                if ($host === $allowed || Str::endsWith($host, '.'.$allowed)) {
                    continue 2;
                }
            }

            return true;
        }

        return false;
    }

    /**
     * Resolve an uploaded asset to a public URL.
     *
     * Accepts an absolute URL, a root-relative path, or a path on the public
     * storage disk — which is what Filament's uploader writes. Returns null
     * for anything blank or unsafe, so a renderer can `@if` and skip rather
     * than emit a broken <img src>.
     */
    public static function assetUrl(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://', '//'])) {
            return $value;
        }

        if (Str::startsWith($value, '/')) {
            return $value;
        }

        // Shipped public assets are stored under public/ rather than the
        // storage disk used by Filament uploads.
        if (Str::startsWith($value, ['images/', 'build/'])) {
            return asset($value);
        }

        // Reject traversal and any scheme smuggled in without a leading slash.
        if (Str::contains($value, ['..', '://', "\0"])) {
            return null;
        }

        // A bare `javascript:alert(1)` contains no `://` and no leading slash,
        // so it reaches this point looking like a disk path and becomes a
        // same-origin URL pointing at a nonsense location. Rejecting anything
        // that looks like a scheme closes that, and anything genuinely
        // intended as a URL has already been handled above.
        if (preg_match('/^[a-z][a-z0-9+.\-]*:/i', $value) === 1) {
            return null;
        }

        return asset('storage/'.$value);
    }

    private static function sanitizer(): HtmlSanitizer
    {
        $mediaHosts = self::ALLOW_REMOTE_IMAGES ? null : self::mediaHosts();

        $key = $mediaHosts === null ? '*' : implode('|', $mediaHosts);

        if (isset(self::$sanitizers[$key])) {
            return self::$sanitizers[$key];
        }

        // Every one of these calls is immutable — each returns a new config and
        // leaves $config untouched. Discarding a return value silently yields
        // an empty allowlist, which strips every element and takes the page's
        // rich text with it. Each result is assigned back deliberately.
        $config = new HtmlSanitizerConfig;

        // An empty host list means "no host at all", which is what an
        // off-site image should meet — unlike links, where null is the
        // permissive "any host, approved scheme only". Relative media stay
        // allowed, so a root-relative or bare path is unaffected.
        $config = $config
            ->allowLinkSchemes(self::LINK_SCHEMES)
            ->allowMediaSchemes(self::MEDIA_SCHEMES)
            ->allowRelativeLinks(true)
            ->allowRelativeMedias(true)
            ->allowLinkHosts(null)
            ->allowMediaHosts(self::ALLOW_REMOTE_IMAGES ? null : self::mediaHosts())
            ->withMaxInputLength(self::MAX_INPUT_LENGTH);

        // Blocked first: blockElement() drops the tag but keeps its children,
        // which is what we want for a wrapper, while dropElement() is reserved
        // for tags whose *content* is the danger.
        foreach (self::BLOCKED_ELEMENTS as $element) {
            $config = $config->blockElement($element);
        }

        // The contents of these are code, not copy. Unwrapping them would
        // publish the source of a script or the text of a stylesheet as body
        // copy, so they and their contents go entirely.
        foreach (self::DROPPED_ELEMENTS as $element) {
            $config = $config->dropElement($element);
        }

        // Now the allowlist. A dropped tag must not be re-added, so a block
        // list entry always wins.
        $blocked = array_merge(self::BLOCKED_ELEMENTS, self::DROPPED_ELEMENTS);

        foreach (self::ALLOWED_ELEMENTS as $element) {
            if (in_array($element, $blocked, true)) {
                continue;
            }

            $config = $config->allowElement(
                $element,
                self::ALLOWED_ATTRIBUTES[$element] ?? self::ALLOWED_ATTRIBUTES['*'],
            );
        }

        foreach (self::ALLOWED_ATTRIBUTES['*'] as $attribute) {
            $config = $config->allowAttribute($attribute, '*');
        }

        // Any link an editor adds opens with target="_blank" in the editor, so
        // the opener is forced on rather than trusted from the stored value.
        $config = $config->forceAttribute('a', 'rel', 'noopener noreferrer');

        return self::$sanitizers[$key] = new HtmlSanitizer($config);
    }
}
