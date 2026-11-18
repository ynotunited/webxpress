<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Blog\BlogPostResource\Pages\EditBlogPost;
use App\Filament\Resources\Site\PageSectionResource\Pages\EditPageSection;
use App\Filament\Resources\Site\SitePageResource\Pages\EditSitePage;
use App\Models\Page;
use App\Models\PageSection;
use App\Models\Post;
use App\Models\User;
use App\Support\Html;
use Filament\Forms\Components\RichEditor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Images in the admin's rich text fields.
 *
 * The public rule is that no image on this site is a link to somebody else's
 * server, and rich text is the only place an editor can break it. The
 * sanitizer enforces the rule, but enforcement alone is not enough: an editor
 * with no way to add an image will eventually paste a link instead, and then
 * watch it vanish with no explanation.
 *
 * So these tests hold both halves. Every rich text field must offer a real
 * upload that lands somewhere the web server serves, and must tell the editor
 * when a linked image is removed.
 */
class RichTextImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_pasted_linked_image_is_removed_from_a_post_body(): void
    {
        // Saved through the model, which is how the admin and the seeder write:
        // the column itself should never come to hold a hotlink.
        $post = Post::create([
            'title' => 'On shipping',
            'slug' => 'on-shipping',
            'body' => '<p>Real copy.</p><img src="https://images.unsplash.com/photo-1?w=800" alt="Borrowed">',
            'is_published' => true,
        ]);

        $stored = $post->fresh()->getRawOriginal('body');

        $this->assertStringNotContainsString('unsplash', $stored);
        $this->assertStringNotContainsString('<img', $stored);
        $this->assertStringContainsString('Real copy.', $stored);
    }

    public function test_a_body_row_written_outside_the_model_is_still_cleaned_on_read(): void
    {
        // Covers rows written before this rule existed, imports, and a direct
        // database edit — none of which pass through the model's saving hook.
        $post = Post::create([
            'title' => 'On shipping',
            'slug' => 'on-shipping',
            'body' => '<p>Real copy.</p>',
            'is_published' => true,
        ]);

        Post::withoutEvents(fn () => Post::query()
            ->whereKey($post->getKey())
            ->update(['body' => '<p>Real copy.</p><img src="https://images.unsplash.com/photo-1">']));

        $rendered = $post->fresh()->bodyHtml;

        $this->assertStringNotContainsString('unsplash', $rendered);
        $this->assertStringNotContainsString('<img', $rendered);
        $this->assertStringContainsString('Real copy.', $rendered);
    }

    public function test_the_post_page_never_renders_a_linked_image(): void
    {
        $post = Post::create([
            'title' => 'On shipping',
            'slug' => 'on-shipping',
            'body' => '<p>Real copy.</p>',
            'is_published' => true,
            'published_at' => now(),
        ]);

        Post::withoutEvents(fn () => Post::query()
            ->whereKey($post->getKey())
            ->update(['body' => '<p>Real copy.</p><img src="https://images.unsplash.com/photo-1">']));

        $html = $this->get('/insights/'.$post->slug)->assertOk()->getContent();

        $this->assertStringNotContainsString('images.unsplash.com', $html);
        $this->assertStringContainsString('Real copy.', $html);
    }

    public function test_a_linked_image_in_a_page_block_is_removed(): void
    {
        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        $section = PageSection::create([
            'page_id' => $page->id,
            'key' => 'block-prose',
            'type' => 'prose',
            'sort_order' => 0,
            'data' => ['body' => '<p>Real copy.</p><img src="https://images.unsplash.com/photo-1">'],
        ]);

        $stored = $section->fresh()->data['body'];

        $this->assertStringNotContainsString('unsplash', $stored);
        $this->assertStringNotContainsString('<img', $stored);
        $this->assertStringContainsString('Real copy.', $stored);
    }

    public function test_the_post_editor_offers_a_local_image_upload(): void
    {
        $post = Post::create([
            'title' => 'On shipping',
            'slug' => 'on-shipping',
            'body' => '<p>Real copy.</p>',
        ]);

        $editors = $this->richEditorsIn(EditBlogPost::class, ['record' => $post->getRouteKey()]);

        $this->assertNotEmpty($editors, 'The post body should be a rich text field.');

        foreach ($editors as $editor) {
            $this->assertContains('attachFiles', $this->toolbarButtons($editor));
            $this->assertSame('public', $editor->getFileAttachmentsDiskName());
            $this->assertSame('public', $editor->getFileAttachmentsVisibility());
        }
    }

    public function test_every_page_rich_text_field_offers_a_local_image_upload(): void
    {
        $page = Page::create(['title' => 'Home', 'slug' => 'home']);

        $editors = $this->richEditorsIn(EditSitePage::class, ['record' => $page->getRouteKey()]);

        $this->assertNotEmpty($editors);

        // Every one, not just the first: a page form has a body plus repeaters
        // of blocks, and an editor that quietly lacks the button is one that
        // will end up with a pasted link instead.
        foreach ($editors as $editor) {
            $this->assertContains('attachFiles', $this->toolbarButtons($editor));
            $this->assertSame('public', $editor->getFileAttachmentsDiskName());
        }
    }

    public function test_a_block_editor_offers_a_local_image_upload(): void
    {
        $page = Page::create(['title' => 'Home', 'slug' => 'home', 'is_published' => true]);

        $section = PageSection::create([
            'page_id' => $page->id,
            'key' => 'block-prose',
            'type' => 'prose',
            'sort_order' => 0,
            'data' => ['body' => '<p>Real copy.</p>'],
        ]);

        $editors = $this->richEditorsIn(EditPageSection::class, ['record' => $section->getRouteKey()]);

        $this->assertNotEmpty($editors);

        foreach ($editors as $editor) {
            $this->assertContains('attachFiles', $this->toolbarButtons($editor));
            $this->assertSame('public', $editor->getFileAttachmentsDiskName());
        }
    }

    public function test_body_images_cannot_be_uploaded_as_svg(): void
    {
        // SVG is a document format that can carry script, so accepting it from
        // an upload would walk around the sanitizer through the front door.
        $post = Post::create([
            'title' => 'On shipping',
            'slug' => 'on-shipping',
            'body' => '<p>Real copy.</p>',
        ]);

        $editors = $this->richEditorsIn(EditBlogPost::class, ['record' => $post->getRouteKey()]);

        foreach ($editors as $editor) {
            $types = (array) $editor->getFileAttachmentsAcceptedFileTypes();

            $this->assertNotContains('image/svg+xml', $types);
            $this->assertContains('image/jpeg', $types);
            $this->assertContains('image/png', $types);
            $this->assertContains('image/webp', $types);
        }
    }

    public function test_an_uploaded_image_resolves_to_a_path_this_site_serves(): void
    {
        // The upload must produce a URL the sanitizer's own-host allowlist
        // accepts, or saving a post with an uploaded image would delete it.
        $post = Post::create([
            'title' => 'On shipping',
            'slug' => 'on-shipping',
            'body' => '<p>Real copy.</p>',
        ]);

        $editors = $this->richEditorsIn(EditBlogPost::class, ['record' => $post->getRouteKey()]);

        $url = $editors[0]->getFileAttachmentUrl('posts/body/demo.jpg');

        $this->assertNotNull($url);
        $this->assertStringContainsString('/storage/posts/body/demo.jpg', $url);
        $this->assertFalse(Html::hasRemoteImage('<img src="'.$url.'">'));
        $this->assertStringContainsString('src="', Html::sanitize('<img src="'.$url.'">'));
    }

    /**
     * Every rich text field inside a mounted page or a list of components.
     *
     * @param  array<int, mixed>  $components
     * @return list<RichEditor>
     */
    private function richEditorsIn(string|array $page, array $params = []): array
    {
        config(['admin.email' => '']);
        $this->actingAs(User::factory()->create());

        $found = [];

        $visit = function ($components) use (&$visit, &$found): void {
            foreach ($components as $component) {
                if (! is_object($component)) {
                    continue;
                }

                if ($component instanceof RichEditor) {
                    $found[] = $component;

                    continue;
                }

                if (method_exists($component, 'getChildComponents')) {
                    $visit($component->getChildComponents());
                }
            }
        };

        if (is_string($page)) {
            $instance = Livewire::test($page, $params);
            $visit($instance->instance()->getSchema('form')->getComponents());
        } else {
            $visit($page);
        }

        return $found;
    }

    /**
     * Toolbar button names, flattened out of Filament's grouping.
     *
     * @return list<string>
     */
    private function toolbarButtons(RichEditor $editor): array
    {
        $names = [];

        foreach ($editor->getToolbarButtons() as $group) {
            foreach ((array) $group as $button) {
                $names[] = is_string($button) ? $button : $button->getName();
            }
        }

        return $names;
    }
}

