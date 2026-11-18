<?php

namespace Tests\Feature\Cms;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The blog body.
 *
 * `body` is echoed unescaped on the post page, which is the only way rich text
 * renders — and also the only route by which editor input reaches a visitor's
 * browser as markup. It had no sanitisation at all, so this is a regression
 * guard rather than a description of existing behaviour.
 */
class BlogBodySafetyTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_post_body_is_stored_sanitised(): void
    {
        $post = Post::create([
            'title' => 'On shipping',
            'slug' => 'on-shipping',
            'body' => '<p>Real copy.</p><script>alert("xss-post")</script>',
            'is_published' => true,
        ]);

        $stored = $post->fresh()->getRawOriginal('body');

        $this->assertStringNotContainsString('alert("xss-post")', $stored);
        $this->assertStringNotContainsString('<script', $stored);
        $this->assertStringContainsString('Real copy.', $stored);
    }

    public function test_a_post_body_is_sanitised_on_render_even_if_written_directly(): void
    {
        // Covers rows written before this existed, and anything that reached
        // the column by import or direct database edit.
        $post = Post::create([
            'title' => 'On shipping',
            'slug' => 'on-shipping',
            'body' => '<p>Real copy.</p>',
            'is_published' => true,
        ]);

        Post::withoutEvents(fn () => Post::query()
            ->whereKey($post->getKey())
            ->update(['body' => '<p>Real copy.</p><img src=x onerror="alert(1)"><script>alert(2)</script>']));

        $this->assertStringNotContainsString('onerror', $post->fresh()->bodyHtml);
        $this->assertStringNotContainsString('alert(2)', $post->fresh()->bodyHtml);
        $this->assertStringContainsString('Real copy.', $post->fresh()->bodyHtml);
    }

    public function test_the_post_page_renders_its_body_safely(): void
    {
        $post = Post::create([
            'title' => 'On shipping',
            'slug' => 'on-shipping',
            'body' => '<p>Real copy.</p><script>alert("xss-post")</script>',
            'is_published' => true,
            'published_at' => now(),
        ]);

        $body = $this->get('/insights/'.$post->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('alert("xss-post")', $body);
        $this->assertStringContainsString('Real copy.', $body);
    }
}
