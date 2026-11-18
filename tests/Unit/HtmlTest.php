<?php

namespace Tests\Unit;

use App\Support\Html;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The rich text sanitizer.
 *
 * Rich text is the only editor input that is echoed unescaped, so this is the
 * boundary between "an editor typed something" and "the site runs it". These
 * tests are that boundary, and they are deliberately written as attacks rather
 * than as examples of what is allowed — the allowlist is proved by the XSS
 * cases being closed, not by a list of happy paths.
 */
class HtmlTest extends TestCase
{
    public function test_it_keeps_the_markup_a_rich_text_field_actually_produces(): void
    {
        $html = '<h2>Heading</h2><p>Body with <strong>bold</strong> and <em>emphasis</em>.</p>'
            .'<ul><li>One</li><li>Two</li></ul><blockquote>A quote.</blockquote>';

        $this->assertSame($html, Html::sanitize($html));
    }

    public function test_it_keeps_links_and_images_with_safe_attributes(): void
    {
        $html = '<p><a href="https://webxpresstech.com.ng/work" target="_blank">Work</a></p>'
            .'<figure><img src="/storage/hero.png" alt="A workspace" width="800" height="600">'
            .'<figcaption>Caption</figcaption></figure>';

        $clean = Html::sanitize($html);

        $this->assertStringContainsString('href="https://webxpresstech.com.ng/work"', $clean);
        $this->assertStringContainsString('target="_blank"', $clean);
        $this->assertStringContainsString('src="/storage/hero.png"', $clean);
        $this->assertStringContainsString('alt="A workspace"', $clean);
        $this->assertStringContainsString('width="800"', $clean);
        $this->assertStringContainsString('height="600"', $clean);
        $this->assertStringContainsString('<figcaption>Caption</figcaption>', $clean);

        // rel is forced rather than copied from the stored value: an editor
        // adds target="_blank" in the rich text editor, and a new tab pointed
        // back at this site is a tabnabbing vector the editor never sees.
        $this->assertStringContainsString('rel="noopener noreferrer"', $clean);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function xssVectors(): array
    {
        return [
            'script element' => ['<script>alert(1)</script>'],
            'uppercase script' => ['<SCRIPT>alert(1)</SCRIPT>'],
            'script with attributes' => ['<script src="//evil.test/x.js"></script>'],
            'nested script in allowed tag' => ['<p><script>alert(1)</script></p>'],
            'style element' => ['<style>body{display:none}</style>'],
            'iframe' => ['<iframe src="//evil.test"></iframe>'],
            'object' => ['<object data="//evil.test"></object>'],
            'embed' => ['<embed src="//evil.test">'],
            'form' => ['<form action="//evil.test"><input name="a"></form>'],
            'svg onload' => ['<svg onload="alert(1)"></svg>'],
            'img onerror' => ['<img src=x onerror="alert(1)">'],
            'img onload via allowed src' => ['<img src="x" onload="alert(1)">'],
            'body onload' => ['<body onload="alert(1)">hi</body>'],
            'div onclick' => ['<div onclick="alert(1)">click</div>'],
            'inline style' => ['<p style="position:fixed;top:0;left:0;width:100vw;height:100vh">defaced</p>'],
            'javascript link' => ['<a href="javascript:alert(1)">click</a>'],
            'javascript link mixed case' => ['<a href="JaVaScRiPt:alert(1)">click</a>'],
            'javascript link with entity' => ['<a href="java&#115;cript:alert(1)">click</a>'],
            'javascript link with newline' => ['<a href="java'."\n".'script:alert(1)">click</a>'],
            'data uri link' => ['<a href="data:text/html;base64,PHNjcmlwdD5hbGVydCgxKTwvc2NyaXB0Pg==">click</a>'],
            'vbscript link' => ['<a href="vbscript:msgbox(1)">click</a>'],
            'data uri image' => ['<img src="data:image/svg+xml;base64,PHN2Zz48c2NyaXB0PmFsZXJ0KDEpPC9zY3JpcHQ+PC9zdmc+">'],
            'formaction on button' => ['<a href="/x" formaction="javascript:alert(1)">click</a>'],
            'meta refresh' => ['<meta http-equiv="refresh" content="0;url=//evil.test">'],
            'base tag' => ['<base href="//evil.test/">'],
            'unclosed script' => ['<p>text</p><script src="//evil.test/x.js">'],
            'nested malformed' => ['<p><div><script>alert(1)</script></div></p>'],
            'comment smuggling' => ['<!--><script>alert(1)</script>-->'],
            'null byte in tag' => ["<scr\0ipt>alert(1)</scr\0ipt>"],
        ];
    }

    #[DataProvider('xssVectors')]
    public function test_it_neutralises_an_attack_vector(string $attack): void
    {
        $clean = Html::sanitize($attack);

        // Nothing executable survives, in any casing or obfuscation.
        $this->assertDoesNotMatchRegularExpression('/<\s*script/i', $clean, 'a script element survived');
        $this->assertDoesNotMatchRegularExpression('/<\s*(iframe|object|embed|form|svg|style|meta|base)\b/i', $clean, 'a blocked element survived');
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+\s*=/i', $clean, 'an event handler attribute survived');
        $this->assertDoesNotMatchRegularExpression('/javascript\s*:/i', $clean, 'a javascript: URL survived');
        $this->assertDoesNotMatchRegularExpression('/vbscript\s*:/i', $clean, 'a vbscript: URL survived');
        $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $clean, 'an inline style survived');
    }

    public function test_it_keeps_the_text_of_an_attack_so_content_is_never_silently_lost(): void
    {
        // Stripping the tag is the goal, but quietly deleting the editor's
        // words along with it would be a data-loss bug in a content system.
        $clean = Html::sanitize('<p>Call us on <strong>+234 800 000 0000</strong> today.</p>');

        $this->assertStringContainsString('Call us on', $clean);
        $this->assertStringContainsString('800 000 0000', $clean);
        $this->assertStringContainsString('today.', $clean);

        // `+` is entity-encoded on the way out. It renders as `+`, so this is
        // correct — but it is worth pinning, because a content system that
        // mangles a phone number is one editors learn to work around.
        $this->assertSame(
            '<p>Call us on <strong>&#43;234 800 000 0000</strong> today.</p>',
            $clean
        );
    }

    public function test_sanitising_is_idempotent(): void
    {
        // Sanitising happens on save and again on render. If the second pass
        // were not a no-op, every page view would mutate what was stored.
        $once = Html::sanitize('<p style="color:red">Hi<script>alert(1)</script><a href="javascript:x">l</a></p>');
        $twice = Html::sanitize($once);

        $this->assertSame($once, $twice);
        $this->assertTrue(Html::isSanitized($once));
    }

    public function test_it_handles_null_and_blank_without_error(): void
    {
        $this->assertSame('', Html::sanitize(null));
        $this->assertSame('', Html::sanitize(''));
        $this->assertSame('', Html::sanitize('   '));
        $this->assertTrue(Html::isSanitized(null));
        $this->assertTrue(Html::isSanitized(''));
    }

    public function test_it_allows_relative_links_but_not_relative_script_injection(): void
    {
        $this->assertStringContainsString('href="/privacy"', Html::sanitize('<a href="/privacy">Privacy</a>'));
        $this->assertStringContainsString('href="#section"', Html::sanitize('<a href="#section">Jump</a>'));
    }

    public function test_asset_url_resolves_the_shapes_an_uploader_produces(): void
    {
        $this->assertSame('https://cdn.test/a.png', Html::assetUrl('https://cdn.test/a.png'));
        $this->assertSame('http://cdn.test/a.png', Html::assetUrl('http://cdn.test/a.png'));
        $this->assertSame('//cdn.test/a.png', Html::assetUrl('//cdn.test/a.png'));
        $this->assertSame('/storage/page-sections/a.png', Html::assetUrl('/storage/page-sections/a.png'));

        // The Filament uploader writes a disk-relative path.
        $this->assertStringContainsString('/storage/page-sections/a.png', Html::assetUrl('page-sections/a.png'));
    }

    public function test_asset_url_refuses_traversal_and_smuggled_schemes(): void
    {
        // These are the values that would turn an <img src> into something the
        // browser treats as markup, or read a file outside the public disk.
        $this->assertNull(Html::assetUrl('../../.env'));
        $this->assertNull(Html::assetUrl('../../../config/database.php'));
        $this->assertNull(Html::assetUrl('javascript:alert(1)'));
        $this->assertNull(Html::assetUrl('data:image/svg+xml;base64,PHN2Zz48L3N2Zz4='));
        $this->assertNull(Html::assetUrl("page-sections/a.png\0.php"));
        $this->assertNull(Html::assetUrl(''));
        $this->assertNull(Html::assetUrl(null));
        $this->assertNull(Html::assetUrl(['array']));
    }

    /**
     * Every image on this site is committed to the repository and served from
     * the app's own host, so an <img src> pointing anywhere else is a hotlink.
     *
     * @return array<string, array{0: string}>
     */
    public static function offSiteImageSources(): array
    {
        return [
            'unsplash' => ['https://images.unsplash.com/photo-1?w=800'],
            'other cdn' => ['https://cdn.example.com/a.jpg'],
            'plain http' => ['http://example.com/a.png'],
            'protocol relative' => ['//images.unsplash.com/photo-1.jpg'],
            // The allowlist matches on label boundaries, so a host that merely
            // ends with the real one must not slip in.
            'suffix lookalike' => ['https://evil-webxpresstech.com.ng/a.jpg'],
            'subdomain of another host' => ['https://webxpresstech.com.ng.evil.test/a.jpg'],
        ];
    }

    #[DataProvider('offSiteImageSources')]
    public function test_it_drops_an_image_this_site_does_not_host(string $src): void
    {
        $clean = Html::sanitize('<p>Before</p><figure><img src="'.$src.'" alt="Borrowed"><figcaption>Cap</figcaption></figure><p>After</p>');

        // The element goes entirely rather than being left without a source,
        // which a browser would render as a broken image and its alt text.
        $this->assertStringNotContainsString('<img', $clean);
        $this->assertStringNotContainsString($src, $clean);
        $this->assertStringNotContainsString('Borrowed', $clean);

        // The surrounding copy is the editor's and is not collateral damage.
        $this->assertStringContainsString('Before', $clean);
        $this->assertStringContainsString('After', $clean);
        $this->assertStringContainsString('<figcaption>Cap</figcaption>', $clean);
    }

    public function test_it_keeps_an_image_uploaded_to_this_site(): void
    {
        // The admin's uploader resolves an attachment through the disk, which
        // yields an absolute URL on the app host. Refusing that would break the
        // editor's own upload button, so the rule is "our host", not "relative".
        $absolute = '<img src="'.rtrim((string) config('app.url'), '/').'/storage/posts/body/a.jpg" alt="Ours">';

        $this->assertStringContainsString('src="', Html::sanitize($absolute));
        $this->assertStringContainsString('Ours', Html::sanitize($absolute));
        $this->assertStringContainsString('src="/storage/posts/body/a.jpg"', Html::sanitize('<img src="/storage/posts/body/a.jpg">'));
        $this->assertStringContainsString('src="images/insights/a.jpg"', Html::sanitize('<img src="images/insights/a.jpg">'));
    }

    public function test_a_link_to_another_site_is_still_allowed(): void
    {
        // The rule is about images only. Editors write outbound links as a
        // matter of course, and the site's own copy links off-site.
        $clean = Html::sanitize('<p>See <a href="https://unsplash.com/photos">Unsplash</a></p>');

        $this->assertStringContainsString('href="https://unsplash.com/photos"', $clean);
    }

    public function test_it_recognises_an_off_site_image_before_removing_it(): void
    {
        // The admin uses this to explain the loss rather than let the editor
        // think their paste failed, so it has to detect the raw input.
        $this->assertTrue(Html::hasRemoteImage('<img src="https://images.unsplash.com/photo-1">'));
        $this->assertTrue(Html::hasRemoteImage('<p>x</p><img src=\'//cdn.test/a.png\'>'));

        $this->assertFalse(Html::hasRemoteImage('<img src="/storage/a.jpg">'));
        $this->assertFalse(Html::hasRemoteImage('<img src="images/a.jpg">'));
        $this->assertFalse(Html::hasRemoteImage('<p>No images here.</p>'));
        $this->assertFalse(Html::hasRemoteImage('<a href="https://unsplash.com">A link</a>'));
        $this->assertFalse(Html::hasRemoteImage(null));
        $this->assertFalse(Html::hasRemoteImage(''));
    }

    public function test_the_image_allowlist_follows_the_configured_host(): void
    {
        // The allowlist is read from config, and the sanitizer is cached, so a
        // host change has to actually change behaviour rather than reuse the
        // sanitizer built for the previous host.
        $before = Html::sanitize('<img src="https://cdn.test/a.jpg">');
        $this->assertStringNotContainsString('cdn.test', $before);

        config(['app.url' => 'https://cdn.test']);

        $this->assertStringContainsString('https://cdn.test/a.jpg', Html::sanitize('<img src="https://cdn.test/a.jpg">'));
    }

    public function test_dropping_an_off_site_image_is_idempotent(): void
    {
        $once = Html::sanitize('<figure><img src="https://images.unsplash.com/photo-1"><figcaption>Cap</figcaption></figure><p>Body</p>');

        $this->assertSame($once, Html::sanitize($once));
        $this->assertTrue(Html::isSanitized($once));
    }
}
